<?php
/*
  Galeriile foto (aspect separat al articolului)

  Pe pagina Galerii foto: albumele, grupate pe corpurile muzeului (A, B, C, D),
  si scanarile 3D. Pozele se iau singure din images/muzeu/albume/<folder>/,
  titlul si corpul fiecarui album sunt in partials/albume.php. Fiecare album arata
  intai cateva poze, restul se deschid cu "Arată toate". Filtrele de sus aleg
  corpul (se rearanjeaza animat, vezi initGallery din template.js).

  Pe Etnografie, Științe si Artă: obiectele din catalog, din folderele
  images/muzeu/galerii/etnografie, stiinte si arta. Descrierea fiecarei poze vine
  din descrieri.txt ("01-stergar.jpg=Ștergar"), altfel din numele fisierului.
  Sub poze e inventarul colectiei, din inventar.txt (un obiect pe rand).
  La Artă, fiecare tablou are rama si eticheta ca in muzeu.

  Pozele trec din alb-negru in culoare cand apar pe ecran si se deschid marite.
  Textul articolului, daca exista, apare sub titlu.
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var \Joomla\Component\Content\Site\View\Article\HtmlView $this */

$app = Factory::getApplication();
$app->set('muzeulianca.ownHead', true);

$item     = $this->item;
$e        = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$size     = require dirname(__DIR__, 3) . '/partials/img-size.php';
$albume   = require dirname(__DIR__, 3) . '/partials/albume.php';
$sectiuni = require dirname(__DIR__, 3) . '/partials/sectiuni.php';
$tparams  = $app->getTemplate(true)->params;
$text     = trim(strip_tags((string) $item->text, '<img><iframe>')) !== '' ? (string) $item->text : '';
$imgRoot  = JPATH_ROOT . '/images/muzeu';
$imgUrl   = Uri::root(true) . '/images/muzeu';

$colectii = ['etnografie' => 'Etnografie', 'stiinte' => 'Științe', 'arta' => 'Artă'];
// pagina unei colectii are aliasul ei; altfel e pagina cu albumele
$aici = isset($colectii[$item->alias]) ? $item->alias : '';

$menuLink = static function (string $alias) use ($app) {
	$m = $app->getMenu()->getItems('alias', $alias, true);

	return $m ? Route::_('index.php?Itemid=' . $m->id) : '';
};

// pozele unui folder, cu descrierile din descrieri.txt sau din numele fisierului
$poze = static function (string $folder) use ($imgRoot, $imgUrl): array {
	$dir = $imgRoot . '/' . $folder;
	if (!is_dir($dir)) {
		return [];
	}
	$desc = [];
	if (is_file($dir . '/descrieri.txt')) {
		foreach (file($dir . '/descrieri.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $r) {
			if (str_contains($r, '=')) {
				[$f, $d] = explode('=', $r, 2);
				$desc[trim($f)] = trim($d);
			}
		}
	}
	$fisiere = glob($dir . '/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: [];
	natcasesort($fisiere);
	$out = [];
	foreach ($fisiere as $f) {
		$b = basename($f);
		$n = trim(preg_replace('/[\s._-]+/', ' ', preg_replace('/^\d+[\s._-]*/', '', pathinfo($b, PATHINFO_FILENAME))));
		$out[] = [
			'url' => $imgUrl . '/' . $folder . '/' . rawurlencode($b),
			'alt' => $desc[$b] ?? ($n !== '' ? mb_strtoupper(mb_substr($n, 0, 1)) . mb_substr($n, 1) : ''),
		];
	}

	return $out;
};

$cate = static fn (int $n, string $unu, string $multe) => $n === 1 ? '1 ' . $unu : $n . ($n % 100 >= 20 || ($n % 100 === 0 && $n) ? ' de ' : ' ') . $multe;

if ($aici) {
	// o colectie din catalog
	$lista    = $poze('galerii/' . $aici);
	$inventar = is_file("$imgRoot/galerii/$aici/inventar.txt")
		? file("$imgRoot/galerii/$aici/inventar.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
	$sumar = $inventar
		? $cate(count($inventar), 'obiect', 'obiecte') . ' · ' . $cate(count($lista), 'fotografie', 'fotografii')
		: $cate(count($lista), 'fotografie', 'fotografii');
} else {
	// albumele, grupate pe corpuri
	$foldere = array_map('basename', glob($imgRoot . '/albume/*', GLOB_ONLYDIR) ?: []);
	$ordine  = array_keys($albume);
	usort($foldere, static fn ($a, $b) => (array_search($a, $ordine) === false ? 99 : array_search($a, $ordine)) <=> (array_search($b, $ordine) === false ? 99 : array_search($b, $ordine)) ?: strcmp($a, $b));
	$corpuri = [];
	foreach ($sectiuni as $s) {
		$corpuri[$s['corp']] = ['titlu' => $s['titlu'], 'albume' => []];
	}
	$total = 0;
	foreach ($foldere as $f) {
		$p = $poze('albume/' . $f);
		if (!$p) {
			continue;
		}
		$info  = $albume[$f] ?? ['titlu' => ucfirst(trim(str_replace('-', ' ', preg_replace('/^[a-d]-/', '', $f)))), 'corp' => strtoupper($f[0])];
		$corp  = isset($corpuri[$info['corp']]) ? $info['corp'] : 'A';
		$corpuri[$corp]['albume'][] = ['titlu' => $info['titlu'], 'poze' => $p, 'id' => $f];
		$total += count($p);
	}
	$corpuri = array_filter($corpuri, static fn ($c) => $c['albume']);
	$scanari    = $poze('scanari-3d');
	$corpuriUrl = $menuLink('corpurile-muzeului');
	// modelul Sketchfab al fiecarui obiect scanat ("01-ie.jpg=<id>")
	$modele = [];
	if (is_file($imgRoot . '/scanari-3d/modele.txt')) {
		foreach (file($imgRoot . '/scanari-3d/modele.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $r) {
			if (preg_match('/^(.+?)=([0-9a-f]{32})$/', trim($r), $m)) {
				$modele[$m[1]] = $m[2];
			}
		}
	}
	$nAlbume = array_sum(array_map(static fn ($c) => count($c['albume']), $corpuri));
	$sumar   = $cate($total, 'fotografie', 'fotografii') . ' · ' . $cate($nAlbume, 'album', 'albume');
}

if (!trim((string) $item->metadesc)) {
	$this->document->setDescription($aici
		? 'Colecția ' . $colectii[$aici] . ' a Muzeului Ianca: obiectele din catalog, cu fotografii.'
		: 'Galeriile foto ale Muzeului Ianca, pe corpurile muzeului, și scanările 3D ale obiectelor.');
}

// o poza din grila, cu marire la clic
$poza = static function (array $p, string $galerie, int $k, bool $rama = false, string $nr = '') use ($e, $size) {
	ob_start(); ?>
	<li class="gal-item<?php echo $rama ? ' gal-item--art' : ''; ?>" data-galerie="<?php echo $e($galerie); ?>">
		<a class="gal-item__frame develop" href="<?php echo $e($p['url']); ?>" data-lightbox data-caption="<?php echo $e($p['alt']); ?>">
			<img src="<?php echo $e($p['url']); ?>"<?php echo $size($p['url']); ?> alt="<?php echo $e($p['alt']); ?>" loading="<?php echo $k < 4 ? 'eager' : 'lazy'; ?>" decoding="async">
		</a>
		<?php if ($rama) : ?>
			<p class="gal-label"><span class="gal-label__title"><?php echo $e($p['alt']); ?></span><span class="gal-label__nr">N° <?php echo $e($nr); ?></span></p>
		<?php elseif ($p['alt'] !== '' && $galerie !== 'album') : ?>
			<p class="gal-item__cap"><?php echo $e($p['alt']); ?></p>
		<?php endif; ?>
	</li>
	<?php return ob_get_clean();
};
?>
<article class="gal-page" data-gallery>
	<header class="evp-head">
		<div class="wrap">
			<p class="eyebrow"><?php echo $aici ? 'Catalog selectiv' : 'Muzeul în imagini'; ?></p>
			<h1 class="evp-head__title"><span><?php echo $e($item->title); ?></span></h1>
			<p class="hand-note" aria-hidden="true"><?php echo $aici ? 'obiecte strânse din casă în casă' : 'sală cu sală, corp cu corp'; ?></p>
			<div class="evp-head__meta">
				<?php if ($text) : ?><div class="evp-head__lead"><?php echo $text; ?></div><?php endif; ?>
				<p class="evp-head__range"><?php echo $e($sumar); ?></p>
			</div>
		</div>
	</header>

	<div class="wrap">
		<?php if ($aici) : ?>
			<nav class="gal-filter" aria-label="Colecții">
				<?php if ($link = $menuLink('galerii-foto')) : ?><a class="gal-chip" href="<?php echo $e($link); ?>">Albumele foto</a><?php endif; ?>
				<?php foreach ($colectii as $cheie => $nume) : ?>
					<?php if ($cheie === $aici) : ?>
						<span class="gal-chip" aria-current="page"><?php echo $e($nume); ?></span>
					<?php elseif ($link = $menuLink($cheie)) : ?>
						<a class="gal-chip" href="<?php echo $e($link); ?>"><?php echo $e($nume); ?></a>
					<?php endif; ?>
				<?php endforeach; ?>
			</nav>

			<?php if ($lista) : ?>
				<ul class="gal-grid">
					<?php foreach ($lista as $k => $p) : ?>
						<?php echo $poza($p, $aici, $k, $aici === 'arta', str_pad((string) ($k + 1), 2, '0', STR_PAD_LEFT)); ?>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="gal-empty">Fotografiile acestei colecții vor apărea aici în curând.</p>
			<?php endif; ?>

			<?php if ($inventar) : ?>
				<!--
				  inventarul: obiectele din catalogul selectiv, si cele fara fotografie.
				  Nu scriem ca sunt toate obiectele muzeului (lista nu e inca verificata).
				-->
				<section class="gal-inv" aria-labelledby="gal-inv-title">
					<div class="stitch-sm" aria-hidden="true"></div>
					<h2 id="gal-inv-title" class="split">Inventarul colecției</h2>
					<p class="hand-note"><?php echo count($inventar); ?> de obiecte din catalogul selectiv</p>
					<ol class="gal-inv__list">
						<?php foreach ($inventar as $k => $o) : ?>
							<li class="reveal" style="--d:<?php echo $k % 6; ?>"><span><?php echo str_pad((string) ($k + 1), 3, '0', STR_PAD_LEFT); ?></span><?php echo $e($o); ?></li>
						<?php endforeach; ?>
					</ol>
				</section>
			<?php endif; ?>
		<?php else : ?>
			<nav class="gal-filter" aria-label="Corpurile muzeului">
				<button type="button" class="gal-chip" data-gal-filter="toate" aria-pressed="true">Toate <span><?php echo $total; ?></span></button>
				<?php foreach ($corpuri as $c => $info) : ?>
					<button type="button" class="gal-chip" data-gal-filter="<?php echo $c; ?>" aria-pressed="false">Corpul <?php echo $c; ?> <span><?php echo array_sum(array_map(static fn ($a) => count($a['poze']), $info['albume'])); ?></span></button>
				<?php endforeach; ?>
				<?php if ($scanari) : ?>
					<button type="button" class="gal-chip" data-gal-filter="3d" aria-pressed="false">Scanări 3D <span><?php echo count($scanari); ?></span></button>
				<?php endif; ?>
			</nav>

			<?php foreach ($corpuri as $c => $info) : ?>
				<section class="gal-corp" data-corp="<?php echo $c; ?>" aria-labelledby="gal-corp-<?php echo $c; ?>">
					<header class="gal-corp__head">
						<span class="gal-corp__letter" aria-hidden="true"><?php echo $c; ?></span>
						<div>
							<p class="gal-corp__kicker">Corpul <?php echo $c; ?></p>
							<h2 id="gal-corp-<?php echo $c; ?>" class="gal-corp__title split"><?php echo $e($info['titlu']); ?></h2>
						</div>
						<?php if ($corpuriUrl) : ?>
							<!-- inapoi la corpul acesta pe pagina Corpurile muzeului (de acolo vii spre galerie) -->
							<a class="link-arrow gal-corp__link" href="<?php echo $e($corpuriUrl); ?>#corp-<?php echo strtolower($c); ?>">Despre corpul <?php echo $c; ?> <svg width="16" height="16" aria-hidden="true"><use href="#i-arrow"/></svg></a>
						<?php endif; ?>
					</header>
					<?php foreach ($info['albume'] as $a) : ?>
						<div class="gal-album" data-album>
							<div class="gal-album__head">
								<h3 class="gal-album__title"><?php echo $e($a['titlu']); ?></h3>
								<span class="gal-album__count"><?php echo $e($cate(count($a['poze']), 'fotografie', 'fotografii')); ?></span>
							</div>
							<ul class="gal-grid gal-grid--album">
								<?php foreach ($a['poze'] as $k => $p) : ?>
									<?php echo str_replace('<li class="gal-item', '<li' . ($k >= 8 ? ' data-more hidden' : '') . ' class="gal-item', $poza(['url' => $p['url'], 'alt' => $a['titlu'] . ', fotografia ' . ($k + 1)], 'album', $k)); ?>
								<?php endforeach; ?>
							</ul>
							<?php if (count($a['poze']) > 8) : ?>
								<button type="button" class="btn btn--line gal-album__more" data-album-more>Arată toate cele <?php echo count($a['poze']); ?> <svg aria-hidden="true"><use href="#i-chev"/></svg></button>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</section>
			<?php endforeach; ?>

			<?php if ($scanari) : ?>
				<!--
				  scanarile 3D: fiecare obiect e un card; la clic modelul lui se deschide mare,
				  pe tot ecranul (vezi initModels din template.js). Se incarca un singur
				  obiect, nu toata colectia, asa ca porneste mult mai repede.
				  Legatura poza - model e in images/muzeu/scanari-3d/modele.txt
				  (il scrie unelte/site-vechi/modele-3d.js).
				-->
				<section class="gal-corp gal-3d" data-corp="3d" aria-labelledby="gal-3d-title">
					<header class="gal-corp__head">
						<span class="gal-corp__letter" aria-hidden="true">3D</span>
						<div>
							<p class="gal-corp__kicker">Obiecte scanate</p>
							<h2 id="gal-3d-title" class="gal-corp__title split">Scanări 3D</h2>
						</div>
						<p class="hand-note gal-3d__note">întoarce obiectele pe toate părțile</p>
					</header>
					<ul class="gal-3d__grid">
						<?php foreach ($scanari as $k => $p) : ?>
							<?php $model = $modele[basename(rawurldecode($p['url']))] ?? ''; ?>
							<li class="gal-3d__item reveal" style="--d:<?php echo $k % 4; ?>">
								<?php if ($model) : ?>
									<button type="button" class="gal-3d__card" data-model="<?php echo $e($model); ?>" data-model-title="<?php echo $e($p['alt']); ?>">
								<?php else : ?>
									<a class="gal-3d__card" href="<?php echo $e($p['url']); ?>" data-lightbox data-caption="<?php echo $e($p['alt']); ?>">
								<?php endif; ?>
									<span class="gal-3d__img"><img src="<?php echo $e($p['url']); ?>"<?php echo $size($p['url']); ?> alt="" loading="lazy" decoding="async"></span>
									<span class="gal-3d__nr"><?php echo str_pad((string) ($k + 1), 2, '0', STR_PAD_LEFT); ?></span>
									<span class="gal-3d__title"><?php echo $e($p['alt']); ?></span>
									<?php if ($model) : ?><span class="gal-3d__go"><svg aria-hidden="true"><use href="#i-360"/></svg>Rotește în 3D</span><?php endif; ?>
								<?php echo $model ? '</button>' : '</a>'; ?>
							</li>
						<?php endforeach; ?>
					</ul>
					<?php if ($sk = (string) $tparams->get('sketchfab')) : ?>
						<p class="gal-3d__all"><a class="link-arrow" href="<?php echo $e(preg_replace('#/playlists/embed\?collection=([0-9a-f]+).*#', '/collections/$1', $sk)); ?>" target="_blank" rel="noopener">Toată colecția pe Sketchfab <svg width="16" height="16" aria-hidden="true"><use href="#i-arrow"/></svg></a></p>
					<?php endif; ?>
				</section>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</article>
