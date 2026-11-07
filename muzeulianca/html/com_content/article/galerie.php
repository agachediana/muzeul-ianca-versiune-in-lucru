<?php
/*
  Galeriile foto (aspect separat al articolului)

  Se foloseste pentru pagina Galerii foto si pentru Etnografie, Științe si Artă.
  Pozele se iau singure din folderele images/muzeu/galerii/etnografie, stiinte si arta
  (in administrare: Conținut > Media). Numele fisierului devine descrierea pozei:
  "razboi-de-tesut.jpg" apare ca "Razboi de tesut". Un numar la inceput
  ("01-...") doar stabileste ordinea si nu apare pe site.

  Pe Galerii foto se vad toate pozele, cu filtre (se rearanjeaza animat, vezi
  initGallery din template.js). Pe pagina unei galerii se vad doar pozele ei.
  Pozele trec din alb-negru in culoare cand apar pe ecran si se deschid marite.
  La Artă, fiecare tablou are rama si o eticheta ca in muzeu.
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var \Joomla\Component\Content\Site\View\Article\HtmlView $this */

$app = Factory::getApplication();
$app->set('muzeulianca.ownHead', true);

$item = $this->item;
$e    = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$size = require dirname(__DIR__, 3) . '/partials/img-size.php';
$text = trim(strip_tags((string) $item->text, '<img><iframe>')) !== '' ? (string) $item->text : '';

$galerii = ['etnografie' => 'Etnografie', 'stiinte' => 'Științe', 'arta' => 'Artă'];
// pagina unei galerii are aliasul ei (etnografie, stiinte, arta); altfel e pagina cu toate
$aici = isset($galerii[$item->alias]) ? $item->alias : '';

// linkul spre pagina fiecarei galerii, dupa aliasul elementului de meniu
$menuLink = static function (string $alias) use ($app) {
	$m = $app->getMenu()->getItems('alias', $alias, true);

	return $m ? Route::_('index.php?Itemid=' . $m->id) : '';
};

// descrierea pozei din numele fisierului
$descriere = static function (string $file): string {
	$n = pathinfo($file, PATHINFO_FILENAME);
	$n = preg_replace('/^\d+[\s._-]+/', '', $n);
	$n = trim(preg_replace('/[\s._-]+/', ' ', $n));

	return mb_strtoupper(mb_substr($n, 0, 1)) . mb_substr($n, 1);
};

$poze = [];
foreach ($galerii as $cheie => $nume) {
	$poze[$cheie] = [];
	$dir = JPATH_ROOT . '/images/muzeu/galerii/' . $cheie;
	if (!is_dir($dir)) {
		continue;
	}
	$fisiere = glob($dir . '/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: [];
	natcasesort($fisiere);
	foreach ($fisiere as $f) {
		$url = Uri::root(true) . '/images/muzeu/galerii/' . $cheie . '/' . rawurlencode(basename($f));
		$poze[$cheie][] = ['url' => $url, 'alt' => $descriere(basename($f)), 'galerie' => $cheie, 'nr' => count($poze[$cheie]) + 1];
	}
}

$afisate = $aici ? $poze[$aici] : array_merge(...array_values($poze));
$total   = count($afisate);

if (!trim((string) $item->metadesc)) {
	$this->document->setDescription($aici
		? 'Galeria foto ' . $galerii[$aici] . ' a Muzeului Ianca.'
		: 'Galeriile foto ale Muzeului Ianca: etnografie, științe și artă.');
}

$cate = static fn (int $n) => $n === 1 ? '1 fotografie' : $n . ($n % 100 >= 20 || ($n % 100 === 0 && $n) ? ' de' : '') . ' fotografii';
?>
<article class="gal-page" data-gallery<?php echo $aici ? ' data-only="' . $aici . '"' : ''; ?>>
	<header class="evp-head">
		<div class="wrap">
			<p class="eyebrow"><?php echo $aici ? 'Galerii foto' : 'Muzeul în imagini'; ?></p>
			<h1 class="evp-head__title"><span><?php echo $e($item->title); ?></span></h1>
			<div class="evp-head__meta">
				<?php if ($text) : ?><div class="evp-head__lead"><?php echo $text; ?></div><?php endif; ?>
				<p class="evp-head__range" data-gal-count><?php echo $e($cate($total)); ?></p>
			</div>
		</div>
	</header>

	<div class="wrap">
		<!-- filtrele: pe pagina cu toate sunt butoane, pe pagina unei galerii sunt linkuri spre celelalte -->
		<nav class="gal-filter" aria-label="Galerii">
			<?php if (!$aici) : ?>
				<button type="button" class="gal-chip" data-gal-filter="toate" aria-pressed="true">Toate <span><?php echo $total; ?></span></button>
				<?php foreach ($galerii as $cheie => $nume) : ?>
					<button type="button" class="gal-chip" data-gal-filter="<?php echo $cheie; ?>" aria-pressed="false"><?php echo $e($nume); ?> <span><?php echo count($poze[$cheie]); ?></span></button>
				<?php endforeach; ?>
			<?php else : ?>
				<?php if ($link = $menuLink('galerii-foto')) : ?><a class="gal-chip" href="<?php echo $e($link); ?>">Toate</a><?php endif; ?>
				<?php foreach ($galerii as $cheie => $nume) : ?>
					<?php if ($cheie === $aici) : ?>
						<span class="gal-chip" aria-current="page"><?php echo $e($nume); ?> <span><?php echo count($poze[$cheie]); ?></span></span>
					<?php elseif ($link = $menuLink($cheie)) : ?>
						<a class="gal-chip" href="<?php echo $e($link); ?>"><?php echo $e($nume); ?> <span><?php echo count($poze[$cheie]); ?></span></a>
					<?php endif; ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</nav>

		<?php if ($afisate) : ?>
			<ul class="gal-grid" data-gal-grid>
				<?php foreach ($afisate as $k => $p) : ?>
					<li class="gal-item<?php echo $p['galerie'] === 'arta' ? ' gal-item--art' : ''; ?>" data-galerie="<?php echo $p['galerie']; ?>">
						<a class="gal-item__frame develop" href="<?php echo $e($p['url']); ?>" data-lightbox data-caption="<?php echo $e($p['alt']); ?>">
							<img src="<?php echo $e($p['url']); ?>"<?php echo $size($p['url']); ?> alt="<?php echo $e($p['alt']); ?>" loading="<?php echo $k < 6 ? 'eager' : 'lazy'; ?>" decoding="async">
						</a>
						<?php if ($p['galerie'] === 'arta') : ?>
							<p class="gal-label"><span class="gal-label__title"><?php echo $e($p['alt']); ?></span><span class="gal-label__nr">N° <?php echo str_pad((string) $p['nr'], 2, '0', STR_PAD_LEFT); ?></span></p>
						<?php else : ?>
							<p class="gal-item__cap"><?php echo $e($p['alt']); ?></p>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<p class="gal-empty"<?php echo $afisate ? ' hidden' : ''; ?> data-gal-empty>Fotografiile acestei galerii vor apărea aici în curând.</p>
	</div>
</article>
