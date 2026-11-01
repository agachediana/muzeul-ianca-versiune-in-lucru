<?php
/*
  Pagina unui eveniment (aspect separat pentru articolele din categoria evenimente)

  Are titlul, starea, data si locul, poza mare, textul, celelalte poze de la
  eveniment (langa text, se deschid marite) si legaturile spre evenimentul
  anterior si urmator. Nu apar autorul, categoria, data publicarii sau vizualizarile.
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Site\Helper\RouteHelper;

/** @var \Joomla\Component\Content\Site\View\Article\HtmlView $this */

$app = Factory::getApplication();
// titlul paginii il pune fisierul asta, nu tema (vezi index.php)
$app->set('muzeulianca.ownHead', true);

$item = $this->item;
$e    = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$img  = static function ($path) {
	if (!$path) {
		return '';
	}
	$url = HTMLHelper::_('cleanImageURL', $path)->url;

	return preg_match('#^(https?:)?//#', $url) ? $url : Uri::root(true) . '/' . ltrim($url, '/');
};
$size = require dirname(__DIR__, 3) . '/partials/img-size.php';

// datele evenimentului si evenimentele dinainte si de dupa el
$loadEvents = require dirname(__DIR__, 3) . '/partials/events-data.php';
$all   = $loadEvents((string) ($item->category_alias ?: 'evenimente'))['all'];
$idx   = null;
foreach ($all as $i => $it) {
	if ($it->id === (int) $item->id) {
		$idx = $i;
		break;
	}
}
$cur   = $idx !== null ? $all[$idx] : null;
$newer = $idx !== null && $idx > 0 ? $all[$idx - 1] : null;
$older = $idx !== null ? ($all[$idx + 1] ?? null) : null;
$nr    = $idx !== null ? count($all) - $idx : 0;

$images  = json_decode((string) $item->images);
$photo   = $img(($images->image_fulltext ?? '') ?: ($images->image_intro ?? ''));
$photoAlt = ($images->image_fulltext_alt ?? '') ?: $item->title;

$back    = Route::_(RouteHelper::getCategoryRoute($item->catid, $item->language));
$tparams = $app->getTemplate(true)->params;

// descrierea pentru Google: cea scrisa in articol, altfel inceputul textului evenimentului
if (!trim((string) $item->metadesc) && $cur && $cur->teaser) {
	$this->document->setDescription($cur->teaser);
}

// celelalte poze (blocul <div class="ev-galerie"> din textul articolului) se muta langa text
// daca blocul a fost schimbat in editor si nu mai e recunoscut, ramane in text, tot cu marire la click
$text   = (string) $item->text;
$photos = [];
if (preg_match('#<div class="ev-galerie"[^>]*>(.*?)</div>#s', $text, $gal)) {
	preg_match_all('#<a[^>]+href="([^"]+)"[^>]*>\s*<img[^>]*?alt="([^"]*)"#s', $gal[1], $found, PREG_SET_ORDER);
	foreach ($found as $p) {
		$photos[] = ['src' => $img(html_entity_decode($p[1])), 'alt' => html_entity_decode($p[2], ENT_QUOTES, 'UTF-8')];
	}
	if ($photos) {
		$text = str_replace($gal[0], '', $text);
	}
}

$statusLabel = ['viitor' => 'Urmează', 'acum' => 'În desfășurare', 'trecut' => 'Eveniment realizat'];
$lunaLung    = ['ianuarie', 'februarie', 'martie', 'aprilie', 'mai', 'iunie', 'iulie', 'august', 'septembrie', 'octombrie', 'noiembrie', 'decembrie'];

$navLink = static function ($it, string $dir, string $label) use ($e, $size) {
	if (!$it) {
		return '<span class="evd-nav__link evd-nav__link--' . $dir . ' is-empty" aria-hidden="true"></span>';
	}
	ob_start(); ?>
	<a class="evd-nav__link evd-nav__link--<?php echo $dir; ?>" href="<?php echo $e($it->link); ?>" rel="<?php echo $dir === 'prev' ? 'prev' : 'next'; ?>">
		<span class="evd-nav__img<?php echo $it->image ? '' : ' ev-noimg'; ?>"><?php if ($it->image) : ?><img src="<?php echo $e($it->image); ?>"<?php echo $size($it->image); ?> alt="" loading="lazy"><?php endif; ?></span>
		<span class="evd-nav__text">
			<span class="evd-nav__dir"><svg aria-hidden="true"><use href="#i-arrow"/></svg><?php echo $label; ?></span>
			<span class="evd-nav__title"><?php echo $e($it->title); ?></span>
			<span class="evd-nav__date"><?php echo $e($it->dateLong); ?></span>
		</span>
	</a>
	<?php return ob_get_clean();
};
?>
<article class="evd evd--<?php echo $cur ? $cur->status : 'trecut'; ?>" data-evd>
	<header class="evd-head">
		<div class="wrap">
			<a class="evd-back" href="<?php echo $e($back); ?>"><svg aria-hidden="true"><use href="#i-arrow"/></svg>Toate evenimentele</a>
			<h1 class="evd-title"><?php echo $e($item->title); ?></h1>
			<?php if ($cur) : ?>
				<p class="evd-meta">
					<span class="evd-chip evd-chip--<?php echo $cur->status; ?>"><?php echo $statusLabel[$cur->status]; ?></span>
					<span><svg aria-hidden="true"><use href="#i-cal"/></svg><time datetime="<?php echo $cur->start->format($cur->monthOnly ? 'Y-m' : 'Y-m-d'); ?>"><?php echo $e($cur->dateLong); ?></time></span>
					<?php if ($cur->hasTime) : ?><span><svg aria-hidden="true"><use href="#i-clock"/></svg>ora <?php echo $cur->start->format('H:i'); ?></span><?php endif; ?>
					<?php if ($cur->status === 'acum' && $cur->end->format('Y-m-d') !== $cur->start->format('Y-m-d')) : ?><span>până pe <?php echo $e($cur->end->format('j') . ' ' . $lunaLung[(int) $cur->end->format('n') - 1]); ?></span><?php endif; ?>
					<?php if ($tparams->get('adresa')) : ?><span><svg aria-hidden="true"><use href="#i-pin"/></svg><?php echo $e($tparams->get('adresa')); ?></span><?php endif; ?>
				</p>
				<?php if ($cur->status === 'viitor') : ?>
					<div class="countdown" data-countdown="<?php echo $cur->start->format(DATE_ATOM); ?>" aria-label="Timp rămas până la eveniment">
						<div><strong data-d>0</strong><span>zile</span></div>
						<div><strong data-h>00</strong><span>ore</span></div>
						<div><strong data-m>00</strong><span>min</span></div>
						<div><strong data-s>00</strong><span>sec</span></div>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</header>

	<?php if ($photo) : ?>
		<div class="wrap">
			<figure class="evd-photo develop">
				<a href="<?php echo $e($photo); ?>" data-lightbox aria-label="Mărește fotografia"><img src="<?php echo $e($photo); ?>"<?php echo $size($photo); ?> alt="<?php echo $e($photoAlt); ?>" fetchpriority="high"></a>
				<?php if ($nr) : ?><span class="evc__nr" aria-hidden="true">N° <?php echo str_pad((string) $nr, 2, '0', STR_PAD_LEFT); ?></span><?php endif; ?>
			</figure>
		</div>
	<?php endif; ?>

	<div class="wrap evd-body<?php echo $photos ? ' has-photos' : ''; ?>">
		<div class="evd-text">
			<?php echo $item->event->beforeDisplayContent; ?>
			<?php echo $text; ?>
			<?php echo $item->event->afterDisplayContent; ?>
		</div>

		<?php if ($photos) : ?>
			<aside class="evd-photos" aria-label="Fotografii de la eveniment">
				<p class="evd-photos__label">Fotografii de la eveniment<span><?php echo count($photos) === 1 ? '1 fotografie' : count($photos) . ' fotografii'; ?></span></p>
				<div class="evd-photos__grid">
					<?php foreach ($photos as $k => $p) : ?>
						<a class="evd-photos__item develop" href="<?php echo $e($p['src']); ?>" data-lightbox aria-label="Mărește fotografia <?php echo $k + 2; ?>"><img src="<?php echo $e($p['src']); ?>"<?php echo $size($p['src']); ?> alt="<?php echo $e($p['alt']); ?>" loading="lazy" decoding="async"></a>
					<?php endforeach; ?>
				</div>
			</aside>
		<?php endif; ?>
	</div>

	<?php if ($older || $newer) : ?>
		<nav class="wrap evd-nav" aria-label="Alte evenimente">
			<?php echo $navLink($older, 'prev', 'Evenimentul anterior'); ?>
			<?php echo $navLink($newer, 'next', 'Evenimentul următor'); ?>
		</nav>
	<?php endif; ?>
</article>
