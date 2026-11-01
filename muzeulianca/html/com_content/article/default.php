<?php
/*
  Pagina unui articol obisnuit (Istoric, Contact, Etnografie...)

  Titlul apare o singura data, ca titlu mare al paginii, tema nu mai pune inca
  unul (vezi index.php). Fara autor, date, vizualizari si fara butoanele
  Previous article / Next article, pentru ca la paginile muzeului se ajunge din meniu.
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;

/** @var \Joomla\Component\Content\Site\View\Article\HtmlView $this */

$app = Factory::getApplication();
// titlul paginii il pune fisierul asta, nu tema (vezi index.php)
$app->set('muzeulianca.ownHead', true);

$item   = $this->item;
$params = $item->params;
// titlul: cel din elementul de meniu daca e setat acolo, altfel titlul articolului
$title  = $this->params->get('show_page_heading') && $this->params->get('page_heading')
	? $this->params->get('page_heading')
	: $item->title;

// descrierea pentru Google: cea scrisa in articol, altfel inceputul textului (daca pagina are text)
$text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $item->text)));
if (!trim((string) $item->metadesc) && $text !== '') {
	$this->document->setDescription(HTMLHelper::_('string.truncate', $text, 160, true, false));
}
?>
<div class="page-head">
	<div class="wrap">
		<h1 class="reveal"><?php echo $this->escape($title); ?></h1>
	</div>
</div>
<div class="content">
	<div class="wrap">
		<article class="item-page<?php echo $this->pageclass_sfx; ?>">
			<?php if ($params->get('access-edit')) : ?>
				<?php echo LayoutHelper::render('joomla.content.icons', ['params' => $params, 'item' => $item]); ?>
			<?php endif; ?>

			<?php echo $item->event->afterDisplayTitle; ?>
			<?php echo $item->event->beforeDisplayContent; ?>

			<?php if ($params->get('access-view')) : ?>
				<?php echo LayoutHelper::render('joomla.content.full_image', $item); ?>
				<div class="item-page__body">
					<?php echo $item->text; ?>
				</div>
			<?php else : ?>
				<?php echo LayoutHelper::render('joomla.content.intro_image', $item); ?>
				<?php echo HTMLHelper::_('content.prepare', $item->introtext); ?>
			<?php endif; ?>

			<?php echo $item->event->afterDisplayContent; ?>
		</article>
	</div>
</div>
