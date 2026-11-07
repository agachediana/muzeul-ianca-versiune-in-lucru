<?php
/*
  Pagina Istoric muzeu (aspect separat al articolului)

  Fiecare subtitlu de tip Titlu 3 (h3) din articol e un an sau o data, iar textul
  de sub el e reperul. Pe pagina apar ca o linie a timpului cusuta: firul se coase
  pe masura ce derulezi, iar fiecare an se aprinde cand ajungi la el
  (vezi initChrono din template.js). Ce urmeaza dupa primul subtitlu de tip
  Titlu 2 (h2), de exemplu Publicații, apare dedesubt ca text obisnuit.
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

/** @var \Joomla\Component\Content\Site\View\Article\HtmlView $this */

$app = Factory::getApplication();
$app->set('muzeulianca.ownHead', true);

$item = $this->item;
$e    = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$html = (string) $item->text;

// linia timpului e pana la primul h2, restul e text obisnuit
$pos   = stripos($html, '<h2');
$linie = $pos === false ? $html : substr($html, 0, $pos);
$rest  = $pos === false ? '' : substr($html, $pos);
$parti = (require dirname(__DIR__, 3) . '/partials/intrebari.php')($linie);

if (!trim((string) $item->metadesc)) {
	$this->document->setDescription('Istoria Muzeului Ianca, de la primele colecții din școlile orașului până la acreditarea din 2009 și proiectele de azi.');
}
?>
<article class="chrono-page">
	<header class="evp-head">
		<div class="wrap">
			<p class="eyebrow">Despre muzeu</p>
			<h1 class="evp-head__title"><span><?php echo $e($item->title); ?></span></h1>
			<p class="hand-note" aria-hidden="true">din vitrinele școlii până la muzeul de azi</p>
			<?php if ($parti['intrebari']) : ?>
				<div class="evp-head__meta">
					<p class="evp-head__range"><?php echo count($parti['intrebari']); ?> de repere · <?php echo $e($parti['intrebari'][0]['q']); ?> – <?php echo $e(end($parti['intrebari'])['q']); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</header>

	<?php if (trim(strip_tags($parti['inceput'])) !== '') : ?>
		<div class="content vz-text"><div class="wrap"><div class="item-page__body"><?php echo $parti['inceput']; ?></div></div></div>
	<?php endif; ?>

	<?php if ($parti['intrebari']) : ?>
		<div class="wrap">
			<div class="chrono" data-chrono>
			<span class="chrono__thread" aria-hidden="true"><span class="chrono__fill"></span></span>
			<ol>
				<?php foreach ($parti['intrebari'] as $k => $r) : ?>
					<li class="chrono__item<?php echo $k % 2 ? ' chrono__item--r' : ''; ?>">
						<p class="chrono__year"><?php echo $e($r['q']); ?></p>
						<div class="chrono__text"><?php echo $r['a']; ?></div>
					</li>
				<?php endforeach; ?>
			</ol>
			</div>
		</div>
	<?php endif; ?>

	<?php if (trim(strip_tags($rest)) !== '') : ?>
		<div class="content chrono-rest"><div class="wrap"><div class="stitch-sm" aria-hidden="true"></div><div class="item-page__body"><?php echo $rest; ?></div></div></div>
	<?php endif; ?>
</article>
