<?php
/*
  Pagina Povești ale locului (aspect separat al categoriei)

  Articolele fara video sunt scrisori si amintiri: apar sus, ca o foaie de hartie,
  cu inceputul textului si semnatura. Cele cu video (un link cu clasa video-link
  in text) sunt interviuri si filme: apar ca poze polaroid, iar la clic pe poza
  videoclipul porneste intr-o fereastra peste pagina. Videoclipurile se incarca
  doar atunci, nu odata cu pagina. Fiecare poveste are si pagina ei.
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

$e    = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$size = require dirname(__DIR__, 3) . '/partials/img-size.php';
$db   = Factory::getContainer()->get(DatabaseInterface::class);

$active = $app->getMenu()->getActive();
$title  = $active && $active->title ? $active->title : $this->category->title;

if (!trim((string) $this->category->metadesc)) {
	$this->document->setDescription('Povești ale locului: amintiri, scrisori și interviuri cu oamenii care au făcut Muzeul Ianca.');
}

// povestile publicate din categorie, in ordinea datei (cea mai noua prima)
$now   = Factory::getDate()->toSql();
$query = $db->getQuery(true)
	->select($db->quoteName(['id', 'title', 'alias', 'introtext', 'fulltext', 'images', 'catid', 'language']))
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

$scrisori = [];
$video    = [];
foreach ($rows as $r) {
	$html   = $r->introtext . ' ' . $r->fulltext;
	$images = json_decode((string) $r->images);
	$img    = ($images->image_intro ?? '') ?: ($images->image_fulltext ?? '');
	$img    = $img ? HTMLHelper::_('cleanImageURL', $img)->url : '';
	$film   = preg_match('#<a[^>]+class="[^"]*video-link[^"]*"[^>]*href="([^"]+)"|<a[^>]+href="([^"]+)"[^>]*class="[^"]*video-link#i', $html, $m) ? ($m[1] ?: $m[2]) : '';
	// textul fara linkul catre video si fara citat, doar prezentarea
	$lead = trim(preg_replace('/\s+/', ' ', strip_tags(preg_replace('#<blockquote.*?</blockquote>|<p class="video-poveste">.*?</p>#s', '', $html))));
	$p    = (object) [
		'title' => $r->title,
		'link'  => Route::_(RouteHelper::getArticleRoute($r->id . ':' . $r->alias, $r->catid, $r->language)),
		'img'   => $img ? (preg_match('#^(https?:)?//#', $img) ? $img : Uri::root(true) . '/' . ltrim($img, '/')) : '',
		'lead'  => $lead,
		'film'  => html_entity_decode($film),
	];
	if ($film) {
		$video[] = $p;
		continue;
	}
	// la scrisori: primele randuri din citat si semnatura (ultimul rand din citat)
	preg_match_all('#<p>(.*?)</p>#s', preg_match('#<blockquote>(.*?)</blockquote>#s', $html, $q) ? $q[1] : $html, $par);
	$par        = array_map(static fn ($t) => trim(strip_tags($t)), $par[1]);
	$p->semnat  = count($par) > 1 && preg_match('/\d{4}$/', end($par)) ? array_pop($par) : '';
	$p->randuri = array_slice($par, 0, 2);
	$scrisori[] = $p;
}
?>
<div class="pv">
	<header class="evp-head">
		<div class="wrap">
			<p class="eyebrow">Amintiri și mărturii</p>
			<h1 class="evp-head__title<?php echo mb_strlen($title) > 14 ? ' evp-head__title--long' : ''; ?>"><span><?php echo $e($title); ?></span></h1>
			<p class="hand-note" aria-hidden="true">oamenii locului își spun povestea</p>
			<div class="evp-head__meta">
				<?php if (trim(strip_tags((string) $this->category->description)) !== '') : ?>
					<div class="evp-head__lead"><?php echo $this->category->description; ?></div>
				<?php else : ?>
					<p class="evp-head__lead">Scrisori, interviuri și filme despre Muzeul Ianca și despre cei care l-au făcut: profesori, aviatori, preoți, muzeografi și oameni ai locului.</p>
				<?php endif; ?>
				<?php if ($rows) : ?><p class="evp-head__range"><?php echo count($rows); ?> povești · <?php echo count($video); ?> înregistrări video</p><?php endif; ?>
			</div>
		</div>
	</header>

	<div class="wrap">
		<?php if (!$rows) : ?>
			<p class="gz-empty">Primele povești sunt în pregătire.</p>
		<?php endif; ?>

		<?php foreach ($scrisori as $s) : ?>
			<!-- o scrisoare, ca o foaie de hartie -->
			<article class="pv-letter reveal">
				<div class="pv-letter__paper">
					<p class="pv-letter__tag">Scrisoare</p>
					<h2 class="pv-letter__title"><a href="<?php echo $e($s->link); ?>"><?php echo $e($s->title); ?></a></h2>
					<?php if ($s->lead) : ?><p class="pv-letter__lead"><?php echo $e($s->lead); ?></p><?php endif; ?>
					<?php foreach ($s->randuri as $rand) : ?>
						<p class="pv-letter__text"><?php echo $e($rand); ?></p>
					<?php endforeach; ?>
					<?php if ($s->semnat) : ?><p class="pv-letter__sign"><?php echo $e($s->semnat); ?></p><?php endif; ?>
					<a class="link-arrow" href="<?php echo $e($s->link); ?>">Citește scrisoarea <svg width="16" height="16"><use href="#i-arrow"/></svg></a>
				</div>
				<?php if ($s->img) : ?>
					<figure class="pv-letter__img develop"><img src="<?php echo $e($s->img); ?>"<?php echo $size($s->img); ?> alt="" loading="lazy"></figure>
				<?php endif; ?>
			</article>
		<?php endforeach; ?>

		<?php if ($video) : ?>
			<section class="pv-videos" aria-labelledby="pv-video">
				<div class="pv-videos__head">
					<h2 id="pv-video" class="pv-videos__title">Interviuri și filme</h2>
					<p class="hand-note" aria-hidden="true">apasă pe poză și ascultă</p>
				</div>
				<ul class="pv-grid">
					<?php foreach ($video as $k => $v) : ?>
						<li class="pv-card reveal" style="--d:<?php echo $k % 3; ?>;--r:<?php echo [-1.6, 1.2, -0.6, 1.8, -1.2, 0.8][$k % 6]; ?>deg">
							<button type="button" class="pv-card__play" data-video="<?php echo $e($v->film); ?>" data-video-title="<?php echo $e($v->title); ?>" aria-label="Pornește: <?php echo $e($v->title); ?>">
								<?php if ($v->img) : ?><img src="<?php echo $e($v->img); ?>"<?php echo $size($v->img); ?> alt="" loading="lazy"><?php endif; ?>
								<span class="pv-card__btn" aria-hidden="true"><svg width="26" height="26"><use href="#i-play"/></svg></span>
							</button>
							<h3 class="pv-card__title"><a href="<?php echo $e($v->link); ?>"><?php echo $e($v->title); ?></a></h3>
							<?php if ($v->lead) : ?><p class="pv-card__text"><?php echo $e(HTMLHelper::_('string.truncate', $v->lead, 170, true, false)); ?></p><?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>

			<!-- fereastra in care pornesc videoclipurile -->
			<dialog class="pv-player" data-video-player aria-labelledby="pv-player-title">
				<div class="pv-player__box">
					<div class="pv-player__bar">
						<p id="pv-player-title" class="pv-player__title"></p>
						<button type="button" class="pv-player__close" data-video-close aria-label="Închide videoclipul"><svg width="20" height="20"><use href="#i-x"/></svg></button>
					</div>
					<video controls playsinline preload="none"></video>
				</div>
			</dialog>
		<?php endif; ?>
	</div>
</div>
