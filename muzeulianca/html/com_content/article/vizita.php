<?php
/*
  Pagina Vizită și bilete (aspect separat al articolului)

  Programul pe zile vine din setarile temei (tabul Vizită și tur). Pe pagina se
  vede daca muzeul e deschis acum si ziua de azi e marcata (vezi initVisit din
  template.js). Biletul are un cotor care se rupe cand apesi Bilete online.
  Textul articolului apare sub program; fiecare subtitlu (h3) din el devine o
  intrebare din lista de intrebari frecvente. Jos e harta, care se incarca la clic.
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

/** @var \Joomla\Component\Content\Site\View\Article\HtmlView $this */

$app = Factory::getApplication();
// titlul paginii il pune fisierul asta, nu tema (vezi index.php)
$app->set('muzeulianca.ownHead', true);

$item    = $this->item;
$e       = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$params  = $app->getTemplate(true)->params;
$orar    = (require dirname(__DIR__, 3) . '/partials/orar.php')($params);
$parti   = (require dirname(__DIR__, 3) . '/partials/intrebari.php')((string) $item->text);
$telefon = (string) $params->get('telefon');
$email   = (string) $params->get('email');
$bilete  = (string) $params->get('bileteUrl');

if (!trim((string) $item->metadesc)) {
	$this->document->setDescription(trim('Programul, biletele și adresa Muzeului Ianca. ' . $params->get('program') . ', ' . $params->get('adresa') . '.'));
}

// datele pentru starea "deschis acum", calculata in browser dupa ora Romaniei
$orarJs = array_map(static fn ($z) => ['zi' => $z['zi'], 'de' => $z['deschis'], 'la' => $z['inchis']], $orar);
?>
<article class="vz" data-vz>
	<header class="evp-head vz-head">
		<div class="wrap">
			<p class="eyebrow">Planificați vizita</p>
			<h1 class="evp-head__title"><span><?php echo $e($item->title); ?></span></h1>
			<div class="evp-head__meta">
				<p class="vz-status" data-status data-orar="<?php echo $e(json_encode($orarJs, JSON_UNESCAPED_UNICODE)); ?>">
					<span class="vz-status__dot" aria-hidden="true"></span>
					<span data-status-text><?php echo $e($params->get('program')); ?></span>
				</p>
				<?php if ($params->get('adresa')) : ?><p class="evp-head__range"><?php echo $e($params->get('adresa')); ?></p><?php endif; ?>
			</div>
		</div>
	</header>

	<section class="wrap vz-main" aria-label="Program și bilete">
		<!-- programul pe zile, ziua de azi e marcata de o banda care aluneca pana la ea -->
		<div class="vz-week reveal">
			<h2 class="vz-h"><svg aria-hidden="true"><use href="#i-clock"/></svg>Programul săptămânii</h2>
			<div class="vz-days" data-days>
			<span class="vz-days__mark" aria-hidden="true"></span>
			<ol>
				<?php foreach ($orar as $i => $z) : ?>
					<li class="vz-day<?php echo $z['deschis'] ? '' : ' is-closed'; ?>" data-day="<?php echo $i; ?>">
						<span class="vz-day__name"><?php echo $e($z['zi']); ?></span>
						<span class="vz-day__hours"><?php echo $z['deschis'] ? $e($z['deschis'] . ' – ' . $z['inchis']) : 'Închis'; ?></span>
					</li>
				<?php endforeach; ?>
			</ol>
			</div>
		</div>

		<div class="vz-side">
			<!-- biletul: partea mare si cotorul, despartite de o linie perforata -->
			<?php if ($bilete) : ?>
				<div class="vz-ticket reveal" style="--d:1" data-ticket>
					<div class="vz-ticket__main">
						<p class="vz-ticket__kicker">Bilet de intrare</p>
						<p class="vz-ticket__name">Muzeul Ianca</p>
						<p class="vz-ticket__info"><?php echo $e($params->get('program')); ?></p>
					</div>
					<div class="vz-ticket__stub">
						<svg class="vz-ticket__icon" aria-hidden="true"><use href="#i-ticket"/></svg>
						<a class="btn vz-ticket__btn" href="<?php echo $e($bilete); ?>" target="_blank" rel="noopener" data-ticket-link>Bilete online <svg><use href="#i-arrow"/></svg></a>
					</div>
				</div>
			<?php endif; ?>

			<?php if ($telefon || $email) : ?>
				<div class="vz-contact reveal" style="--d:2">
					<h2 class="vz-h"><svg aria-hidden="true"><use href="#i-phone"/></svg>Contact</h2>
					<?php if ($telefon) : ?><p><a href="tel:<?php echo $e(preg_replace('/[^\d+]/', '', $telefon)); ?>"><?php echo $e($telefon); ?></a></p><?php endif; ?>
					<?php if ($email) : ?><p><a href="mailto:<?php echo $e($email); ?>"><?php echo $e($email); ?></a></p><?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php echo $item->event->beforeDisplayContent; ?>

	<?php if (trim(strip_tags($parti['inceput'], '<img><iframe>')) !== '') : ?>
		<section class="content vz-text">
			<div class="wrap"><div class="item-page__body"><?php echo $parti['inceput']; ?></div></div>
		</section>
	<?php endif; ?>

	<?php if ($parti['intrebari']) : ?>
		<section class="vz-faq" aria-labelledby="vz-faq-title">
			<div class="wrap">
				<p class="eyebrow">Întrebări frecvente</p>
				<h2 id="vz-faq-title" class="reveal">Înainte de vizită</h2>
				<div class="vz-faq__list">
					<?php foreach ($parti['intrebari'] as $k => $q) : ?>
						<details class="vz-q reveal" style="--d:<?php echo $k % 4; ?>">
							<summary><span><?php echo $e($q['q']); ?></span><svg aria-hidden="true"><use href="#i-plus"/></svg></summary>
							<div class="vz-q__a"><?php echo $q['a']; ?></div>
						</details>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php echo $item->event->afterDisplayContent; ?>

	<section class="section vz-map" aria-label="Harta">
		<div class="wrap">
			<?php require dirname(__DIR__, 3) . '/partials/harta.php'; ?>
		</div>
	</section>
</article>
