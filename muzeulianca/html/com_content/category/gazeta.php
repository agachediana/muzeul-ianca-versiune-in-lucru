<?php
/*
  Pagina Știri, ca prima pagina a unui ziar: "Gazeta muzeului"

  Sus e frontispiciul ziarului, cu numarul (cate stiri sunt) si luna ultimei stiri.
  Cea mai noua stire e articolul principal, cu poza si textul pe doua coloane.
  Urmatoarele sase stau pe coloane despartite de linii, ca in ziar, iar restul
  apar jos, in arhiva pe luni. Randurile apar pe rand cand derulezi, ca la tipar.
  Stirile sunt articolele din categoria Știri (cea legata de elementul de meniu).
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseInterface;

/** @var \Joomla\Component\Content\Site\View\Category\HtmlView $this */

$app = Factory::getApplication();
$app->set('muzeulianca.ownHead', true);

$e      = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$size   = require dirname(__DIR__, 3) . '/partials/img-size.php';
$params = $app->getTemplate(true)->params;
$db     = Factory::getContainer()->get(DatabaseInterface::class);
$tz     = new DateTimeZone($app->get('offset', 'Europe/Bucharest'));
$luni   = ['ianuarie', 'februarie', 'martie', 'aprilie', 'mai', 'iunie', 'iulie', 'august', 'septembrie', 'octombrie', 'noiembrie', 'decembrie'];

$active = $app->getMenu()->getActive();
$title  = $active && $active->title ? $active->title : $this->category->title;

if (!trim((string) $this->category->metadesc)) {
	$this->document->setDescription('Știrile Muzeului Ianca: noutăți, anunțuri și povești din viața muzeului.');
}

// stirile publicate din categorie, cele mai noi primele
$now   = Factory::getDate()->toSql();
$query = $db->getQuery(true)
	->select($db->quoteName(['id', 'title', 'alias', 'introtext', 'fulltext', 'images', 'catid', 'language', 'publish_up']))
	->from($db->quoteName('#__content'))
	->where('catid = ' . (int) $this->category->id)
	->where('state = 1')
	->where('(publish_up IS NULL OR publish_up <= ' . $db->quote($now) . ')')
	->where('(publish_down IS NULL OR publish_down > ' . $db->quote($now) . ')')
	->whereIn('access', $app->getIdentity()->getAuthorisedViewLevels())
	->order('publish_up DESC');
try {
	$rows = $db->setQuery($query)->loadObjectList();
} catch (\RuntimeException $ex) {
	$rows = [];
}

$stiri = array_map(static function ($r) use ($tz, $luni) {
	$d      = (new DateTime($r->publish_up ?: 'now', new DateTimeZone('UTC')))->setTimezone($tz);
	$images = json_decode((string) $r->images);
	$img    = ($images->image_intro ?? '') ?: ($images->image_fulltext ?? '');
	$img    = $img ? HTMLHelper::_('cleanImageURL', $img)->url : '';
	$text   = trim(preg_replace('/\s+/', ' ', strip_tags($r->introtext . ' ' . $r->fulltext)));

	return (object) [
		'title' => $r->title,
		'link'  => Route::_(RouteHelper::getArticleRoute($r->id . ':' . $r->alias, $r->catid, $r->language)),
		'img'   => $img ? (preg_match('#^(https?:)?//#', $img) ? $img : Uri::root(true) . '/' . ltrim($img, '/')) : '',
		'alt'   => ($images->image_intro_alt ?? '') ?: '',
		'text'  => $text,
		'date'  => $d,
		'zi'    => $d->format('j') . ' ' . $luni[(int) $d->format('n') - 1] . ' ' . $d->format('Y'),
		'luna'  => $luni[(int) $d->format('n') - 1] . ' ' . $d->format('Y'),
	];
}, $rows);

$lead  = $stiri[0] ?? null;
$coloane = array_slice($stiri, 1, 6);
$arhiva  = [];
foreach (array_slice($stiri, 7) as $s) {
	$arhiva[$s->luna][] = $s;
}
?>
<div class="gz" data-gazeta>
	<header class="gz-mast">
		<div class="wrap">
			<p class="gz-mast__top"><span>Muzeul Ianca</span><span><?php echo $e($params->get('adresa')); ?></span></p>
			<h1 class="gz-mast__title"><span><?php echo $e($title === 'Știri' ? 'Gazeta muzeului' : $title); ?></span></h1>
			<p class="gz-mast__line">
				<span>Nr. <?php echo count($stiri); ?></span>
				<span><?php echo $lead ? $e($lead->luna) : $e($luni[(int) (new DateTime('now', $tz))->format('n') - 1] . ' ' . (new DateTime('now', $tz))->format('Y')); ?></span>
				<span>Știri și povești din viața muzeului</span>
			</p>
		</div>
	</header>

	<div class="wrap">
		<?php if (!$lead) : ?>
			<p class="gz-empty">Primul număr al gazetei este în pregătire.</p>
		<?php else : ?>
			<!-- articolul principal -->
			<article class="gz-lead">
				<?php if ($lead->img) : ?>
					<a class="gz-lead__img develop" href="<?php echo $e($lead->link); ?>" tabindex="-1" aria-hidden="true">
						<img src="<?php echo $e($lead->img); ?>"<?php echo $size($lead->img); ?> alt="" fetchpriority="high">
					</a>
				<?php endif; ?>
				<div class="gz-lead__body">
					<p class="gz-date"><time datetime="<?php echo $lead->date->format('Y-m-d'); ?>"><?php echo $e($lead->zi); ?></time></p>
					<h2 class="gz-lead__title press"><a href="<?php echo $e($lead->link); ?>"><?php echo $e($lead->title); ?></a></h2>
					<?php if ($lead->text) : ?>
						<p class="gz-lead__text press"><?php echo $e(HTMLHelper::_('string.truncate', $lead->text, 700, true, false)); ?></p>
					<?php endif; ?>
					<a class="link-arrow" href="<?php echo $e($lead->link); ?>">Citește tot <svg width="16" height="16"><use href="#i-arrow"/></svg></a>
				</div>
			</article>

			<?php if ($coloane) : ?>
				<!-- urmatoarele stiri, pe coloane ca in ziar -->
				<div class="gz-cols">
					<?php foreach ($coloane as $k => $s) : ?>
						<article class="gz-col press" style="--d:<?php echo $k % 3; ?>">
							<?php if ($s->img) : ?>
								<a class="gz-col__img develop" href="<?php echo $e($s->link); ?>" tabindex="-1" aria-hidden="true"><img src="<?php echo $e($s->img); ?>"<?php echo $size($s->img); ?> alt="" loading="lazy"></a>
							<?php endif; ?>
							<p class="gz-date"><time datetime="<?php echo $s->date->format('Y-m-d'); ?>"><?php echo $e($s->zi); ?></time></p>
							<h3 class="gz-col__title"><a href="<?php echo $e($s->link); ?>"><?php echo $e($s->title); ?></a></h3>
							<?php if ($s->text) : ?><p class="gz-col__text"><?php echo $e(HTMLHelper::_('string.truncate', $s->text, 220, true, false)); ?></p><?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ($arhiva) : ?>
				<!-- restul stirilor, pe luni -->
				<section class="gz-archive" aria-labelledby="gz-arhiva">
					<h2 id="gz-arhiva" class="gz-archive__title">Numere mai vechi</h2>
					<?php foreach ($arhiva as $luna => $lista) : ?>
						<div class="gz-archive__month press">
							<p class="gz-archive__luna"><?php echo $e($luna); ?></p>
							<ul>
								<?php foreach ($lista as $s) : ?>
									<li><a href="<?php echo $e($s->link); ?>"><?php echo $e($s->title); ?></a><time datetime="<?php echo $s->date->format('Y-m-d'); ?>"><?php echo $e($s->date->format('j')); ?></time></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endforeach; ?>
				</section>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>
