<?php
/*
  Pagina Contact (aspect separat al articolului)

  Cardurile cu telefonul, e-mailul, adresa, programul (cu "deschis acum") si
  biletele vin din setarile temei. Fiecare card se inclina putin dupa mouse.
  Textul articolului, daca exista, apare dedesubt, apoi harta (se incarca la clic).
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

/** @var \Joomla\Component\Content\Site\View\Article\HtmlView $this */

$app = Factory::getApplication();
$app->set('muzeulianca.ownHead', true);

$item    = $this->item;
$e       = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$params  = $app->getTemplate(true)->params;
$telefon = (string) $params->get('telefon');
$email   = (string) $params->get('email');
$bilete  = (string) $params->get('bileteUrl');
$text    = trim(strip_tags((string) $item->text, '<img><iframe>')) !== '' ? (string) $item->text : '';
$orarJs  = array_map(static fn ($z) => ['zi' => $z['zi'], 'de' => $z['deschis'], 'la' => $z['inchis']], (require dirname(__DIR__, 3) . '/partials/orar.php')($params));

if (!trim((string) $item->metadesc)) {
	$this->document->setDescription('Contactul Muzeului Ianca: telefon, e-mail, adresă, program și bilete. ' . $params->get('adresa') . '.');
}
?>
<article class="contact-page">
	<header class="evp-head">
		<div class="wrap">
			<p class="eyebrow">Scrie-ne sau sună-ne</p>
			<h1 class="evp-head__title"><span><?php echo $e($item->title); ?></span></h1>
			<p class="hand-note" aria-hidden="true">răspundem cu drag oricărei întrebări</p>
		</div>
	</header>

	<div class="wrap contact-grid">
		<?php if ($telefon) : ?>
			<a class="contact-card contact-card--big reveal" href="tel:<?php echo $e(preg_replace('/[^\d+]/', '', $telefon)); ?>" data-tilt>
				<svg aria-hidden="true"><use href="#i-phone"/></svg>
				<span class="contact-card__label">Telefon</span>
				<span class="contact-card__value"><?php echo $e($telefon); ?></span>
				<span class="contact-card__hint">pentru vizite, grupuri și școli</span>
			</a>
		<?php endif; ?>
		<?php if ($email) : ?>
			<a class="contact-card reveal" style="--d:1" href="mailto:<?php echo $e($email); ?>" data-tilt>
				<svg aria-hidden="true"><use href="#i-mail"/></svg>
				<span class="contact-card__label">E-mail</span>
				<span class="contact-card__value contact-card__value--sm"><?php echo $e($email); ?></span>
			</a>
		<?php endif; ?>
		<a class="contact-card reveal" style="--d:2" href="#harta" data-tilt>
			<svg aria-hidden="true"><use href="#i-pin"/></svg>
			<span class="contact-card__label">Adresă</span>
			<span class="contact-card__value contact-card__value--sm"><?php echo $e($params->get('adresa')); ?></span>
			<span class="contact-card__hint">vezi pe hartă, mai jos</span>
		</a>
		<div class="contact-card reveal" style="--d:3">
			<svg aria-hidden="true"><use href="#i-clock"/></svg>
			<span class="contact-card__label">Program</span>
			<span class="contact-card__value contact-card__value--sm"><?php echo $e($params->get('program')); ?></span>
			<p class="vz-status vz-status--small" data-status data-orar="<?php echo $e(json_encode($orarJs, JSON_UNESCAPED_UNICODE)); ?>"><span class="vz-status__dot" aria-hidden="true"></span><span data-status-text></span></p>
		</div>
		<?php if ($bilete) : ?>
			<a class="contact-card contact-card--dark reveal" style="--d:4" href="<?php echo $e($bilete); ?>" target="_blank" rel="noopener" data-tilt>
				<svg aria-hidden="true"><use href="#i-ticket"/></svg>
				<span class="contact-card__label">Bilete</span>
				<span class="contact-card__value contact-card__value--sm">Cumpără online</span>
				<span class="contact-card__hint">se deschide site-ul de bilete</span>
			</a>
		<?php endif; ?>
	</div>

	<?php if ($text) : ?>
		<div class="content vz-text"><div class="wrap"><div class="item-page__body"><?php echo $text; ?></div></div></div>
	<?php endif; ?>

	<section class="section vz-map" id="harta" aria-label="Harta">
		<div class="wrap"><?php require dirname(__DIR__, 3) . '/partials/harta.php'; ?></div>
	</section>
</article>
