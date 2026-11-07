<?php
/*
  Leaga paginile noi de aspectele temei:
    - creeaza pagina "Vizită și bilete" (alias vizita), goala, si o pune in meniu
      dupa Știri și evenimente
    - Tur virtual foloseste aspectul tur, Galerii foto, Etnografie, Științe si Artă
      folosesc aspectul galerie, Știri foloseste aspectul gazeta, iar Contact,
      Corpurile muzeului si Istoric muzeu au aspectele contact, corpuri si istoric
    - face folderele pentru poze: images/muzeu/galerii/etnografie, stiinte, arta
  Se poate rula de mai multe ori, ce exista deja nu se mai creeaza.

  Folosire:
    php pagini-noi.php C:\xampp\htdocs\joomla
*/

const _JEXEC = 1;
define('JPATH_BASE', rtrim($argv[1], '\\/'));
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Content;
use Joomla\CMS\Table\Menu;
use Joomla\Database\DatabaseInterface;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(\Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(\Joomla\Session\Session::class, 'session.cli')
	->alias(\Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(\Joomla\Console\Application::class);
$db  = $container->get(DatabaseInterface::class);
$one = static fn (string $sql) => $db->setQuery($sql)->loadResult();

// pagina Vizită și bilete
$vizita = (int) $one("SELECT id FROM #__content WHERE alias = 'vizita' AND catid = 2");
if (!$vizita) {
	$t = new Content($db);
	$t->bind([
		'title' => 'Vizită și bilete', 'alias' => 'vizita', 'introtext' => '', 'fulltext' => '', 'catid' => 2, 'state' => 1,
		'access' => 1, 'language' => '*', 'created' => Factory::getDate()->toSql(),
		'created_by' => (int) $one('SELECT id FROM #__users ORDER BY id LIMIT 1'), 'publish_up' => Factory::getDate()->toSql(),
		'images' => '{}', 'urls' => '{}', 'attribs' => '{}', 'metadata' => '{}', 'metakey' => '', 'metadesc' => '',
	]);
	if (!$t->check() || !$t->store()) {
		exit('EROARE pagina: ' . $t->getError() . "\n");
	}
	$vizita = (int) $t->id;
	$o = (object) ['item_id' => $vizita, 'stage_id' => (int) $one('SELECT id FROM #__workflow_stages WHERE `default` = 1 AND workflow_id = 1'), 'extension' => 'com_content.article'];
	$db->insertObject('#__workflow_associations', $o);
	echo "pagina noua: Vizită și bilete (#$vizita)\n";
}

// aspectul temei pentru fiecare pagina: in setarile articolului si in linkul din meniu
$aspecte = [$vizita => 'vizita', 54 => 'tur', 55 => 'galerie', 16 => 'galerie', 18 => 'galerie', 20 => 'galerie', 22 => 'contact', 8 => 'corpuri', 14 => 'istoric'];
foreach ($aspecte as $id => $aspect) {
	$attribs = json_decode((string) $one("SELECT attribs FROM #__content WHERE id = $id"), true) ?: [];
	$attribs['article_layout'] = 'muzeulianca:' . $aspect;
	$db->setQuery('UPDATE #__content SET attribs = ' . $db->quote(json_encode($attribs)) . " WHERE id = $id")->execute();
	$db->setQuery('UPDATE #__menu SET link = ' . $db->quote("index.php?option=com_content&view=article&layout=muzeulianca:$aspect&id=$id")
		. " WHERE client_id = 0 AND link IN ('index.php?option=com_content&view=article&id=$id', 'index.php?option=com_content&view=article&layout=muzeulianca:$aspect&id=$id')")->execute();
	echo "pagina #$id: aspectul $aspect\n";
}

// elementul de meniu pentru Vizită și bilete, dupa Știri și evenimente
if (!$one("SELECT id FROM #__menu WHERE client_id = 0 AND alias = 'vizita' AND menutype = 'mainmenu'")) {
	$m = new Menu($db);
	$m->bind([
		'menutype' => 'mainmenu', 'title' => 'Vizită și bilete', 'alias' => 'vizita', 'type' => 'component',
		'link' => "index.php?option=com_content&view=article&layout=muzeulianca:vizita&id=$vizita",
		'component_id' => (int) $one("SELECT extension_id FROM #__extensions WHERE element = 'com_content' AND type = 'component'"),
		'published' => 1, 'access' => 1, 'language' => '*', 'client_id' => 0, 'browserNav' => 0,
		'params' => $one('SELECT params FROM #__menu WHERE id = 104'), 'img' => '', 'template_style_id' => 0,
	]);
	$m->setLocation((int) $one("SELECT id FROM #__menu WHERE client_id = 0 AND alias = 'stiri-si-evenimente' AND menutype = 'mainmenu'"), 'after');
	if (!$m->check() || !$m->store()) {
		exit('EROARE meniu: ' . $m->getError() . "\n");
	}
	$m->rebuildPath($m->id);
	echo "meniu nou: Vizită și bilete (#{$m->id})\n";
}

// Știri: aspectul gazeta, in meniu si in categorie
$stiri = (int) $one("SELECT id FROM #__categories WHERE alias = 'stiri' AND extension = 'com_content'");
$db->setQuery('UPDATE #__menu SET link = ' . $db->quote("index.php?option=com_content&view=category&layout=muzeulianca:gazeta&id=$stiri")
	. " WHERE client_id = 0 AND alias = 'stiri' AND menutype = 'mainmenu'")->execute();
$cparams = json_decode((string) $one("SELECT params FROM #__categories WHERE id = $stiri"), true) ?: [];
$cparams['category_layout'] = 'muzeulianca:gazeta';
$db->setQuery('UPDATE #__categories SET params = ' . $db->quote(json_encode($cparams)) . " WHERE id = $stiri")->execute();
echo "Știri: aspectul gazeta\n";

// folderele pentru pozele galeriilor
foreach (['etnografie', 'stiinte', 'arta'] as $g) {
	$dir = JPATH_BASE . '/images/muzeu/galerii/' . $g;
	if (!is_dir($dir)) {
		mkdir($dir, 0755, true);
		echo "folder nou: images/muzeu/galerii/$g\n";
	}
}
