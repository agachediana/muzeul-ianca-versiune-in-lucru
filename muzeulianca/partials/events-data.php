<?php
/*
  Evenimentele muzeului, luate din categoria evenimente, impreuna cu starea lor:
    viitor   - incepe mai tarziu
    acum     - a inceput si nu s-a terminat
    trecut   - s-a terminat

  Datele vin din campurile articolului:
    data-eveniment  - cand incepe (daca lipseste, se ia data publicarii)
    data-sfarsit    - cand se termina, optional (gol = o zi, sau o luna la doar-luna)
    doar-luna       - Da daca nu se stie ziua, pe site apare doar luna si anul

  Se foloseste asa:
    $loadEvents = require __DIR__ . '/events-data.php';
    $ev = $loadEvents('evenimente');   // all, viitor, acum, trecut, focus

  Il folosesc Acasa, pagina Evenimente si pagina unui eveniment.
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

return static function (string $alias = 'evenimente'): array {
	$app = Factory::getApplication();
	$db  = Factory::getContainer()->get(DatabaseInterface::class);
	$tz  = new DateTimeZone($app->get('offset', 'Europe/Bucharest'));
	$utc = new DateTimeZone('UTC');
	$now = new DateTime('now', $tz);

	$lunaLung  = ['ianuarie', 'februarie', 'martie', 'aprilie', 'mai', 'iunie', 'iulie', 'august', 'septembrie', 'octombrie', 'noiembrie', 'decembrie'];
	$lunaAbrev = ['ian.', 'feb.', 'mar.', 'apr.', 'mai', 'iun.', 'iul.', 'aug.', 'sep.', 'oct.', 'nov.', 'dec.'];

	// valoarea unui camp al articolului, dupa numele campului
	$field = static function (string $name) use ($db) {
		return '(SELECT v.value FROM ' . $db->quoteName('#__fields_values', 'v')
			. ' INNER JOIN ' . $db->quoteName('#__fields', 'f') . ' ON f.id = v.field_id'
			. ' WHERE f.name = ' . $db->quote($name) . " AND f.context = 'com_content.article' AND f.state = 1"
			. ' AND v.item_id = a.id LIMIT 1)';
	};

	$nowSql = Factory::getDate()->toSql();
	$query  = $db->getQuery(true)
		->select($db->quoteName(['a.id', 'a.title', 'a.alias', 'a.introtext', 'a.images', 'a.catid', 'a.language', 'a.publish_up']))
		->select($field('data-eveniment') . ' AS ev_start')
		->select($field('data-sfarsit') . ' AS ev_end')
		->select($field('doar-luna') . ' AS ev_month')
		->from($db->quoteName('#__content', 'a'))
		->join('INNER', $db->quoteName('#__categories', 'c'), 'c.id = a.catid')
		->where('c.alias = :alias')
		->where('c.published = 1')
		->where('a.state = 1')
		->where('(a.publish_up IS NULL OR a.publish_up <= :now1)')
		->where('(a.publish_down IS NULL OR a.publish_down > :now2)')
		->whereIn('a.access', $app->getIdentity()->getAuthorisedViewLevels())
		->bind(':alias', $alias)
		->bind(':now1', $nowSql)
		->bind(':now2', $nowSql);
	if ($app->getLanguageFilter()) {
		$query->whereIn('a.language', [$app->getLanguage()->getTag(), '*'], ParameterType::STRING);
	}

	try {
		$rows = $db->setQuery($query)->loadObjectList();
	} catch (\RuntimeException $ex) {
		$rows = [];
	}

	$img = static function ($path) {
		if (!$path) {
			return '';
		}
		$url = HTMLHelper::_('cleanImageURL', $path)->url;

		return preg_match('#^(https?:)?//#', $url) ? $url : Uri::root(true) . '/' . ltrim($url, '/');
	};
	$local = static function (?string $value) use ($utc, $tz) {
		if (!$value) {
			return null;
		}
		$d = new DateTime($value, $utc);

		return $d->setTimezone($tz);
	};

	$all = [];
	foreach ($rows as $row) {
		$start = $local($row->ev_start) ?: $local($row->publish_up) ?: clone $now;
		$monthOnly = (string) $row->ev_month === '1';

		// sfarsitul: campul data-sfarsit, altfel sfarsitul zilei (sau al lunii)
		$end = $local($row->ev_end);
		if (!$end) {
			$end = clone $start;
			$monthOnly ? $end->modify('last day of this month')->setTime(23, 59, 59) : $end->setTime(23, 59, 59);
		}

		$status = $start > $now ? 'viitor' : ($end >= $now ? 'acum' : 'trecut');
		$m = (int) $start->format('n') - 1;

		// textele cu data
		if ($monthOnly) {
			$dateLong = $lunaLung[$m] . ' ' . $start->format('Y');
			$badgeTop = $lunaAbrev[$m];
			$badgeBottom = $start->format('Y');
		} else {
			$dateLong = $start->format('j') . ' ' . $lunaLung[$m] . ' ' . $start->format('Y');
			$badgeTop = $start->format('j');
			$badgeBottom = $lunaAbrev[$m] . ' ' . $start->format('Y');
			if ($row->ev_end && $end->format('Y-m-d') !== $start->format('Y-m-d')) {
				$me = (int) $end->format('n') - 1;
				$dateLong = $start->format('Y-m') === $end->format('Y-m')
					? $start->format('j') . '–' . $end->format('j') . ' ' . $lunaLung[$m] . ' ' . $start->format('Y')
					: $start->format('j') . ' ' . $lunaLung[$m] . ' – ' . $end->format('j') . ' ' . $lunaLung[$me] . ' ' . $end->format('Y');
			}
		}

		$images = json_decode((string) $row->images);
		$text   = trim(preg_replace('/\s+/', ' ', strip_tags((string) $row->introtext)));

		$all[] = (object) [
			'id'          => (int) $row->id,
			'title'       => $row->title,
			'link'        => Route::_(RouteHelper::getArticleRoute($row->id . ':' . $row->alias, $row->catid, $row->language)),
			'image'       => $img($images->image_intro ?? '') ?: $img($images->image_fulltext ?? ''),
			'imageAlt'    => $images->image_intro_alt ?? '',
			'teaser'      => HTMLHelper::_('string.truncate', $text, 170, true, false),
			'start'       => $start,
			'end'         => $end,
			'date'        => $start,
			'monthOnly'   => $monthOnly,
			'hasTime'     => !$monthOnly && $start->format('H:i') !== '00:00',
			'status'      => $status,
			'dateLong'    => $dateLong,
			'badgeTop'    => $badgeTop,
			'badgeBottom' => $badgeBottom,
			'year'        => $start->format('Y'),
		];
	}

	// cele mai noi primele
	usort($all, static fn ($a, $b) => $b->start <=> $a->start);

	$viitor = array_values(array_filter($all, static fn ($e) => $e->status === 'viitor'));
	$acum   = array_values(array_filter($all, static fn ($e) => $e->status === 'acum'));
	$trecut = array_values(array_filter($all, static fn ($e) => $e->status === 'trecut'));
	usort($viitor, static fn ($a, $b) => $a->start <=> $b->start); // cel mai apropiat primul

	// cel pus in fata: urmatorul eveniment, altfel cel in desfasurare, altfel ultimul realizat
	$focus = $viitor[0] ?? $acum[0] ?? $trecut[0] ?? null;

	return ['all' => $all, 'viitor' => $viitor, 'acum' => $acum, 'trecut' => $trecut, 'focus' => $focus];
};
