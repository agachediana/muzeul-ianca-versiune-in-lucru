<?php
/*
  Pagina Corpurile muzeului (aspect separat al articolului)

  Articolul are cate un subtitlu de tip Titlu 2 (h2) pentru fiecare corp, in
  ordinea A, B, C, D, cu textul dedesubt. Pentru fiecare corp pagina pune:
  litera mare, fotografia salii (care se misca dupa mouse sau deget, ca la
  Tur virtual), turul 360 (se incarca doar la clic, din setarile temei) si
  cateva poze din albumele corpului, cu link spre Galerii foto.
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
$params   = $app->getTemplate(true)->params;
$size     = require dirname(__DIR__, 3) . '/partials/img-size.php';
$sectiuni = require dirname(__DIR__, 3) . '/partials/sectiuni.php';
$albume   = require dirname(__DIR__, 3) . '/partials/albume.php';
$galerii  = $app->getMenu()->getItems('alias', 'galerii-foto', true);
$galLink  = $galerii ? Route::_('index.php?Itemid=' . $galerii->id) : '';

// textul fiecarui corp: ce e intre doua subtitluri h2
$parti = preg_split('#<h2[^>]*>(.*?)</h2>#si', (string) $item->text, -1, PREG_SPLIT_DELIM_CAPTURE);
$inceput = array_shift($parti);
$texte   = [];
for ($i = 0; $i + 1 < count($parti); $i += 2) {
	$texte[] = ['titlu' => trim(strip_tags($parti[$i])), 'html' => $parti[$i + 1]];
}

// cateva poze din albumele fiecarui corp
$pozeCorp = static function (string $corp) use ($albume): array {
	$out = [];
	foreach (glob(JPATH_ROOT . '/images/muzeu/albume/*', GLOB_ONLYDIR) ?: [] as $d) {
		$f = basename($d);
		if (strtoupper(($albume[$f]['corp'] ?? $f[0])) !== $corp) {
			continue;
		}
		$l = glob($d . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];
		natcasesort($l);
		foreach (array_slice(array_values($l), 0, 2) as $p) {
			$out[] = Uri::root(true) . '/images/muzeu/albume/' . $f . '/' . rawurlencode(basename($p));
		}
	}

	return array_slice($out, 0, 5);
};
// cate fotografii are corpul in toate albumele lui (pentru ultimul patrat din grila)
$nrPoze = static function (string $corp) use ($albume): int {
	$n = 0;
	foreach (glob(JPATH_ROOT . '/images/muzeu/albume/*', GLOB_ONLYDIR) ?: [] as $d) {
		if (strtoupper(($albume[basename($d)]['corp'] ?? basename($d)[0])) === $corp) {
			$n += count(glob($d . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: []);
		}
	}

	return $n;
};

if (!trim((string) $item->metadesc)) {
	$this->document->setDescription('Cele patru corpuri ale Muzeului Ianca: secția de artă, biserica, armata și școala, casa țărănească și depozitul de unelte, cu tur virtual 360°.');
}
?>
<article class="corps-page" data-tours>
	<header class="evp-head">
		<div class="wrap">
			<p class="eyebrow">Despre muzeu</p>
			<h1 class="evp-head__title"><span><?php echo $e($item->title); ?></span></h1>
			<p class="hand-note" aria-hidden="true">patru clădiri, o singură poveste</p>
			<?php if (trim(strip_tags((string) $inceput)) !== '') : ?>
				<div class="evp-head__meta"><div class="evp-head__lead"><?php echo $inceput; ?></div></div>
			<?php endif; ?>
		</div>
	</header>

	<!-- cuprinsul: cele patru litere, fiecare duce la corpul ei -->
	<nav class="wrap corps-toc" aria-label="Corpurile muzeului">
		<?php foreach ($sectiuni as $i => $s) : ?>
			<a class="corps-toc__link reveal" style="--d:<?php echo $i; ?>" href="#corp-<?php echo strtolower($s['corp']); ?>"><span><?php echo $e($s['corp']); ?></span><?php echo $e($s['titlu']); ?></a>
		<?php endforeach; ?>
	</nav>

	<div class="wrap corps">
		<?php foreach ($sectiuni as $i => $s) : ?>
			<?php
			$t    = $texte[$i] ?? ['titlu' => $s['titlu'], 'html' => '<p>' . $e($s['text']) . '</p>'];
			$url  = trim((string) $params->get('tur' . ($i + 1)));
			$img  = Uri::root(true) . '/' . $s['img'];
			$poze = $pozeCorp($s['corp']);
			?>
			<section class="corp" id="corp-<?php echo strtolower($s['corp']); ?>" aria-labelledby="corp-t-<?php echo $i; ?>">
				<span class="corp__letter" aria-hidden="true"><?php echo $e($s['corp']); ?></span>
				<div class="corp__grid">
					<!-- in stanga: sala (cu turul) si sub ea pozele corpului, langa text -->
					<div class="corp__media">
						<div class="tour__view reveal" data-tour-view<?php echo $url ? ' data-tour="' . $e($url) . '"' : ''; ?>>
							<div class="tour__pan"><img src="<?php echo $e($img); ?>"<?php echo $size($img); ?> alt="" loading="<?php echo $i ? 'lazy' : 'eager'; ?>" draggable="false"></div>
							<span class="tour__compass" aria-hidden="true"><svg><use href="#i-360"/></svg></span>
							<?php if ($url) : ?>
								<button type="button" class="tour__go" data-tour-go><span data-tour-label>Intră în sală</span> <svg aria-hidden="true"><use href="#i-arrow"/></svg></button>
							<?php else : ?>
								<span class="tour__soon">Tur 360° în curând</span>
							<?php endif; ?>
						</div>
						<?php if ($poze) : ?>
							<ul class="corp__strip">
								<?php foreach ($poze as $k => $p) : ?>
									<li class="reveal" style="--d:<?php echo $k + 1; ?>"><a class="develop" href="<?php echo $e($p); ?>" data-lightbox data-caption="<?php echo $e($s['titlu']); ?>"><img src="<?php echo $e($p); ?>"<?php echo $size($p); ?> alt="<?php echo $e($s['titlu'] . ', fotografia ' . ($k + 1)); ?>" loading="lazy"></a></li>
								<?php endforeach; ?>
								<?php if ($galLink) : ?><li class="corp__strip-more reveal" style="--d:6"><a href="<?php echo $e($galLink); ?>#gal-corp-<?php echo $e($s['corp']); ?>"><strong><?php echo $e($nrPoze($s['corp'])); ?></strong>fotografii în galerie <svg aria-hidden="true"><use href="#i-arrow"/></svg></a></li><?php endif; ?>
							</ul>
						<?php endif; ?>
					</div>
					<div class="corp__body">
						<p class="tour__corp">Corpul <?php echo $e($s['corp']); ?></p>
						<h2 class="tour__title split" id="corp-t-<?php echo $i; ?>"><?php echo $e(preg_replace('/^Corpul\s+[A-D]\s*·\s*/u', '', $t['titlu'])); ?></h2>
						<div class="corp__text item-page__body"><?php echo $t['html']; ?></div>
					</div>
				</div>
			</section>
		<?php endforeach; ?>
	</div>
</article>
