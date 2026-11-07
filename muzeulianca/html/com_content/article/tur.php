<?php
/*
  Pagina Tur virtual (aspect separat al articolului)

  Cate un card pentru fiecare corp al muzeului (partials/sectiuni.php). Fotografia
  sălii se misca usor dupa mouse sau deget, ca privita printr-o fereastra (vezi
  initTour din template.js). Turul 360 se incarca doar cand apesi "Intră în sală",
  din linkul pus in setarile temei (tabul Vizită și tur), si se deschide mare,
  pe tot ecranul, cu X pentru inchidere. Venind de pe Acasa (usile, #tour-0...),
  turul corpului ales se deschide singur. Fara link, cardul arata
  "Tur 360° în curând". Textul articolului, daca exista, apare sub titlu.
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

/** @var \Joomla\Component\Content\Site\View\Article\HtmlView $this */

$app = Factory::getApplication();
$app->set('muzeulianca.ownHead', true);

$item     = $this->item;
$e        = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$params   = $app->getTemplate(true)->params;
$size     = require dirname(__DIR__, 3) . '/partials/img-size.php';
$sectiuni = require dirname(__DIR__, 3) . '/partials/sectiuni.php';
$text     = trim(strip_tags((string) $item->text, '<img><iframe>')) !== '' ? (string) $item->text : '';

if (!trim((string) $item->metadesc)) {
	$this->document->setDescription('Turul virtual al Muzeului Ianca: cele patru corpuri ale muzeului, de vizitat de oriunde.');
}
?>
<article class="tour-page" data-tours>
	<header class="evp-head">
		<div class="wrap">
			<p class="eyebrow">Muzeul în imagini</p>
			<h1 class="evp-head__title"><span><?php echo $e($item->title); ?></span></h1>
			<?php if ($text) : ?>
				<div class="evp-head__meta"><div class="evp-head__lead"><?php echo $text; ?></div></div>
			<?php endif; ?>
		</div>
	</header>

	<div class="wrap tours">
		<?php foreach ($sectiuni as $i => $s) : ?>
			<?php
			$url = trim((string) $params->get('tur' . ($i + 1)));
			$img = Uri::root(true) . '/' . $s['img'];
			?>
			<section class="tour reveal<?php echo $i % 2 ? ' tour--flip' : ''; ?>" id="tour-<?php echo $i; ?>" aria-labelledby="tour-t-<?php echo $i; ?>">
				<div class="tour__view" data-tour-view<?php echo $url ? ' data-tour="' . $e($url) . '"' : ''; ?>>
					<div class="tour__pan"><img src="<?php echo $e($img); ?>"<?php echo $size($img); ?> alt="" loading="<?php echo $i ? 'lazy' : 'eager'; ?>" draggable="false"></div>
					<span class="tour__compass" aria-hidden="true"><svg><use href="#i-360"/></svg></span>
					<span class="tour__letter" aria-hidden="true"><?php echo $e($s['corp']); ?></span>
					<?php if ($url) : ?>
						<button type="button" class="tour__go" data-tour-go><span data-tour-label>Intră în sală</span> <svg aria-hidden="true"><use href="#i-arrow"/></svg></button>
					<?php else : ?>
						<span class="tour__soon">Tur 360° în curând</span>
					<?php endif; ?>
				</div>
				<div class="tour__body">
					<p class="tour__corp">Corpul <?php echo $e($s['corp']); ?></p>
					<h2 class="tour__title" id="tour-t-<?php echo $i; ?>"><?php echo $e($s['titlu']); ?></h2>
					<p class="tour__text"><?php echo $e($s['text']); ?></p>
				</div>
			</section>
		<?php endforeach; ?>
	</div>
</article>
