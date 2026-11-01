<?php
/*
  Pagina principala a temei Muzeul Ianca (Joomla 6)

  Aici se construieste fiecare pagina a site-ului: bara de sus cu programul,
  adresa si butoanele de accesibilitate, antetul cu logo si meniu, continutul
  paginii si subsolul cu datele muzeului. Continutul de pe Acasa e in
  partials/home.php.

  Licenta: GNU GPL 2 sau mai noua.
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var Joomla\CMS\Document\HtmlDocument $this */

$app    = Factory::getApplication();
$params = $this->params;
$menu   = $app->getMenu();
$active = $menu->getActive();
$isHome = $active && (int) $active->home === 1;

$this->getWebAssetManager()->usePreset('template.muzeulianca');
$this->setMetaData('viewport', 'width=device-width, initial-scale=1');

// iconitele site-ului (logo-ul Mi pe fundal crem), din media/templates/site/muzeulianca/images
$icons = Uri::root(true) . '/media/templates/site/muzeulianca/images/';
$this->addHeadLink($icons . 'favicon-32.png', 'icon', 'rel', ['type' => 'image/png', 'sizes' => '32x32']);
$this->addHeadLink($icons . 'favicon-48.png', 'icon', 'rel', ['type' => 'image/png', 'sizes' => '48x48']);
$this->addHeadLink($icons . 'icon-192.png', 'icon', 'rel', ['type' => 'image/png', 'sizes' => '192x192']);
$this->addHeadLink($icons . 'apple-touch-icon.png', 'apple-touch-icon', 'rel', ['sizes' => '180x180']);

$e = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$siteName = $app->get('sitename');
$museumName = trim((string) $params->get('heroName', 'Muzeul Ianca')) ?: 'Muzeul Ianca';
$homeUrl  = Uri::base(true) . '/';
$logo     = $params->get('logo') ? HTMLHelper::_('cleanImageURL', $params->get('logo'))->url : '';
$tagline  = $params->get('siteTagline', 'Câmpia Brăilei · din 1999');
$telefon  = $params->get('telefon', '0723 609 033');
$telLink  = 'tel:+4' . preg_replace('/\D/', '', $telefon);
$bilete   = $params->get('bileteUrl', 'https://muzeul-ianca.booktes.com/');

$pageClass = $active ? $active->getParams()->get('pageclass_sfx', '') : '';

// titlul paginii de Acasa: numele muzeului si zona, in loc de "Acasa - Muzeul Ianca"
if ($isHome) {
	$this->setTitle($museumName . ' · ' . $tagline);
}

// tot textul site-ului e in romana, dar Joomla are instalata doar engleza,
// asa ca pagina se declara in romana ca s-o citeasca corect cititoarele de ecran
$htmlLang = str_starts_with((string) $this->language, 'en') ? 'ro-RO' : $this->language;

// paginile de evenimente si paginile obisnuite isi pun singure titlul (vezi html/com_content)
// componenta ruleaza inaintea acestui fisier, deci aici stim deja daca au facut-o
$ownHead = !$isHome && $app->get('muzeulianca.ownHead', false);
?>
<!DOCTYPE html>
<html lang="<?php echo $e($htmlLang); ?>" dir="<?php echo $this->direction; ?>" class="no-js">
<head>
	<script>if ('scrollRestoration' in history) { history.scrollRestoration = 'manual'; }</script>
	<jdoc:include type="metas" />
	<jdoc:include type="styles" />
	<jdoc:include type="scripts" />
</head>
<body class="<?php echo $isHome ? 'is-home' : 'is-page'; ?><?php echo $ownHead ? ' has-own-head' : ''; ?> <?php echo $e($pageClass); ?>">

<?php require __DIR__ . '/partials/icons.php'; ?>

<a class="skip-link" href="#main">Sari la conținut</a>

<!-- bara cu programul, adresa si telefonul ramane sus impreuna cu antetul, pe orice pagina -->
<div class="site-top">
<div class="topbar">
	<div class="wrap">
		<div class="topbar__info" aria-label="Program, adresă și telefon">
			<span><svg><use href="#i-clock"/></svg><?php echo $e($params->get('program')); ?></span>
			<span><svg><use href="#i-pin"/></svg><?php echo $e($params->get('adresa')); ?></span>
			<span><svg><use href="#i-phone"/></svg><a href="<?php echo $e($telLink); ?>"><?php echo $e($telefon); ?></a></span>
		</div>
		<div class="topbar__actions">
			<div class="a11y" role="group" aria-label="Accesibilitate">
				<button type="button" data-a11y="dec" aria-label="Micșorează textul">A−</button>
				<span class="a11y__size" data-a11y-size aria-live="polite">100%</span>
				<button type="button" data-a11y="inc" aria-label="Mărește textul">A+</button>
				<button type="button" data-a11y="contrast" aria-pressed="false" aria-label="Contrast ridicat"><svg><use href="#i-contrast"/></svg></button>
				<button type="button" data-a11y="links" aria-pressed="false" aria-label="Subliniază linkurile"><svg><use href="#i-link"/></svg></button>
			</div>
			<?php if ($bilete) : ?>
				<a class="topbar__tickets" href="<?php echo $e($bilete); ?>" target="_blank" rel="noopener">Bilete online</a>
			<?php endif; ?>
		</div>
	</div>
</div>

<header class="header">
	<div class="wrap">
		<a class="brand" href="<?php echo $e($homeUrl); ?>" aria-label="<?php echo $e($museumName); ?> — Acasă">
			<span class="brand__mark">
				<?php if ($logo) : ?><img src="<?php echo $e($logo); ?>" alt=""><?php else : ?><?php require __DIR__ . '/partials/logo.php'; ?><?php endif; ?>
			</span>
			<span class="brand__name"><?php echo $e($museumName); ?></span>
		</a>

		<?php if ($this->countModules('menu')) : ?>
			<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav" aria-label="Deschide meniul"><span></span></button>
			<nav class="nav" id="nav" aria-label="Meniu principal">
				<jdoc:include type="modules" name="menu" style="none" />
			</nav>
			<div class="nav-scrim"></div>
		<?php endif; ?>
	</div>
</header>
</div>

<main id="main">
	<jdoc:include type="message" />

	<?php if ($isHome) : ?>
		<?php require __DIR__ . '/partials/home.php'; ?>
		<?php if ($params->get('homeShowComponent', 0)) : ?>
			<section class="section"><div class="wrap"><jdoc:include type="component" /></div></section>
		<?php endif; ?>
	<?php elseif ($ownHead) : ?>
		<jdoc:include type="component" />
	<?php else : ?>
		<?php // fara fir de navigare (Acasa / ...), logo-ul si meniul duc oricum acasa ?>
		<div class="page-head">
			<div class="wrap">
				<h1 class="reveal"><?php echo $e($active ? $active->title : $this->getTitle()); ?></h1>
			</div>
		</div>
		<div class="content">
			<div class="wrap">
				<jdoc:include type="component" />
				<?php if ($this->countModules('sidebar')) : ?>
					<aside><jdoc:include type="modules" name="sidebar" style="card" /></aside>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
</main>

<?php
// subsolul are aceleasi informatii ca site-ul vechi: vizita, despre muzeu, de interes public, AFCN
$footerLink = static function (string $alias) use ($menu) {
	$item = $menu->getItems('alias', $alias, true);

	return $item ? Route::_('index.php?Itemid=' . $item->id) : '';
};
$despreLinks = [
	'Asociația'            => $footerLink('asociatia'),
	'Istoricul'            => $footerLink('istoric'),
	'Amplasamentul'        => $footerLink('amplasament'),
	'Colecția'             => $footerLink('corpurile-muzeului'),
	'Oamenii muzeului'     => $footerLink('oamenii-muzeului'),
	'Susține Muzeul Ianca' => $footerLink('sustine-muzeul'),
	'Scrie-ne'             => $footerLink('contact'),
];
$publicLinks = [
	'Politica de confidențialitate (GDPR)' => $footerLink('confidentialitate'),
	'A.N.P.C. – Protecția Consumatorilor'  => 'https://anpc.ro/',
	'Soluționarea alternativă a litigiilor (ANPC SAL)' => 'https://reclamatiisal.anpc.ro',
	'Acreditarea acordată de Ministerul Culturii: O.M.C.C.P.N. nr. 2465/03.11.2009' => $footerLink('acreditare'),
];
// pictograma oficiala ANPC SAL (250 x 50 px, de pe anpc.ro); apare cand fisierul e pus in media/.../images
$salImg = is_file(JPATH_ROOT . '/media/templates/site/muzeulianca/images/anpc-sal.png')
	? Uri::root(true) . '/media/templates/site/muzeulianca/images/anpc-sal.png' : '';

// datele de identificare ale asociatiei (Legea 365/2002), doar cele completate in setarile temei
$email = trim((string) $params->get('email', ''));
$identitate = array_filter([
	trim((string) $params->get('denumireOficiala', '')),
	($s = trim((string) $params->get('sediu', ''))) ? 'Sediul: ' . $s : '',
	$email ? 'E-mail: ' . $email : '',
	($r = trim((string) $params->get('registru', ''))) ? 'Nr. Registrul asociațiilor și fundațiilor: ' . $r : '',
	($c = trim((string) $params->get('cif', ''))) ? 'CIF: ' . $c : '',
]);
$linkList = static function (array $links) use ($e) {
	foreach ($links as $text => $url) {
		echo '<li>' . ($url ? '<a href="' . $e($url) . '"' . (str_starts_with($url, 'http') ? ' target="_blank" rel="noopener"' : '') . '>' . $e($text) . '</a>' : '<span>' . $e($text) . '</span>') . '</li>';
	}
};
?>
<footer class="footer">
	<div class="wrap">
		<div class="footer__grid">
			<div class="footer__brand">
				<a class="brand" href="<?php echo $e($homeUrl); ?>" aria-label="<?php echo $e($museumName); ?> — Acasă"><span class="brand__mark"><?php require __DIR__ . '/partials/logo.php'; ?></span><span class="brand__name"><?php echo $e($museumName); ?></span></a>
				<h4>Vizitează <?php echo $e($museumName); ?></h4>
				<dl class="footer__visit">
					<div><dt>Adresă</dt><dd><?php echo $e($params->get('adresa')); ?></dd></div>
					<div><dt>Program</dt><dd><?php echo $e($params->get('program')); ?></dd></div>
					<div><dt>Telefon</dt><dd><a href="<?php echo $e($telLink); ?>"><?php echo $e($telefon); ?></a></dd></div>
					<?php if ($email) : ?><div><dt>E-mail</dt><dd><a href="mailto:<?php echo $e($email); ?>"><?php echo $e($email); ?></a></dd></div><?php endif; ?>
				</dl>
				<div class="footer__social">
					<?php if ($params->get('facebook')) : ?><a href="<?php echo $e($params->get('facebook')); ?>" aria-label="Facebook"><svg><use href="#i-fb"/></svg></a><?php endif; ?>
					<?php if ($params->get('youtube')) : ?><a href="<?php echo $e($params->get('youtube')); ?>" aria-label="YouTube"><svg><use href="#i-yt"/></svg></a><?php endif; ?>
				</div>
			</div>
			<?php if ($this->countModules('footer-1')) : ?>
				<div><jdoc:include type="modules" name="footer-1" style="none" headerLevel="4" /></div>
			<?php else : ?>
				<div>
					<h4>Despre muzeu</h4>
					<ul><?php $linkList($despreLinks); ?></ul>
				</div>
			<?php endif; ?>
			<?php if ($this->countModules('footer-2')) : ?>
				<div><jdoc:include type="modules" name="footer-2" style="none" headerLevel="4" /></div>
			<?php else : ?>
				<div>
					<h4>De interes public</h4>
					<ul><?php $linkList($publicLinks); ?></ul>
					<?php if ($salImg) : ?>
						<a class="footer__sal" href="https://reclamatiisal.anpc.ro" target="_blank" rel="noopener"><img src="<?php echo $e($salImg); ?>" width="250" height="50" alt="ANPC – Soluționarea alternativă a litigiilor"></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<?php if ($this->countModules('footer-3')) : ?>
				<div><jdoc:include type="modules" name="footer-3" style="none" headerLevel="4" /></div>
			<?php else : ?>
				<div class="footer__afcn">
					<p><strong>Proiect cultural co-finanțat de Administrația Fondului Cultural Național.</strong></p>
					<p>Proiectul nu reprezintă în mod necesar poziția Administrației Fondului Cultural Național. AFCN nu este responsabilă de conținutul proiectului sau de modul în care rezultatele proiectului pot fi folosite. Acestea sunt în întregime responsabilitatea beneficiarului finanțării.</p>
				</div>
			<?php endif; ?>
		</div>
		<div class="footer__bottom">
			<?php if ($identitate) : ?><p class="footer__id"><?php echo $e(implode(' · ', $identitate)); ?></p><?php endif; ?>
			<span>© <?php echo date('Y'); ?> Toate drepturile rezervate | <?php echo $e($museumName); ?></span>
		</div>
	</div>
	<p class="footer__wordmark" aria-hidden="true"><?php echo $e($museumName); ?></p>
</footer>

<button class="ai-fab" type="button" aria-expanded="false" aria-controls="ai-panel"><svg><use href="#i-chat"/></svg>Întreabă muzeul</button>
<div class="ai-panel" id="ai-panel" role="dialog" aria-labelledby="ai-title">
	<button class="ai-panel__close" type="button" aria-label="Închide"><svg><use href="#i-x"/></svg></button>
	<h3 id="ai-title">Asistentul muzeului</h3>
	<p>Întreabă orice despre Muzeul Ianca: colecții, program, istorie.</p>
	<div class="ai-panel__msg" aria-live="polite">Bună ziua! Cu ce vă pot ajuta?</div>
	<form><label class="sr-only" for="ai-q">Întrebarea ta</label><input id="ai-q" type="text" placeholder="Când a fost înființat muzeul?"><button class="btn" type="submit">Trimite</button></form>
</div>

<jdoc:include type="modules" name="debug" style="none" />
</body>
</html>
