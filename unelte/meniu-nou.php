<?php
/*
  Noua structura de meniu:
  Despre: Asociatia, Amplasament, Comunicate de presa, Istoric muzeu
  Muzeul in imagini: Tur virtual (pagina noastra), Galerii foto > Etnografie, Stiinte, Arta
  Corpurile muzeului, Oamenii muzeului si Sustine muzeul se muta intr-un meniu ascuns,
  ca linkurile din subsol si de pe Acasa sa mearga in continuare.
  Numerele elementelor de meniu (104, 110, 124...) sunt cele din baza mea de date,
  asa ca scriptul s-a rulat o singura data, pe 24.10.2026.

  Folosire:
    php meniu-nou.php C:\xampp\htdocs\joomla
*/

const _JEXEC = 1;
define('JPATH_BASE', rtrim($argv[1], '\\/'));
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Content;
use Joomla\CMS\Table\Menu;
use Joomla\CMS\Table\MenuType;
use Joomla\Database\DatabaseInterface;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(\Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(\Joomla\Session\Session::class, 'session.cli')
	->alias(\Joomla\Session\SessionInterface::class, 'session.cli');
$app = $container->get(\Joomla\Console\Application::class);
Factory::$application = $app;
$db  = $container->get(DatabaseInterface::class);
$one = static fn (string $sql) => $db->setQuery($sql)->loadResult();

$fail = static function (string $what, $table) {
	exit("EROARE la $what: " . $table->getError() . "\n");
};

$userId = (int) $one('SELECT id FROM #__users ORDER BY id LIMIT 1');
$stage  = (int) $one('SELECT id FROM #__workflow_stages WHERE `default` = 1 AND workflow_id = 1');
$comId  = (int) $one("SELECT extension_id FROM #__extensions WHERE element = 'com_content' AND type = 'component'");
$params = $one('SELECT params FROM #__menu WHERE id = 104');

// pagini noi, goale (doar titlul)
$article = static function (string $title, string $alias) use ($db, $one, $userId, $stage, $fail) {
	$id = (int) $one('SELECT id FROM #__content WHERE catid = 2 AND alias = ' . $db->quote($alias));
	if ($id) {
		echo "pagina exista deja: $title (#$id)\n";

		return $id;
	}
	$t = new Content($db);
	$t->bind([
		'title' => $title, 'alias' => $alias, 'introtext' => '', 'fulltext' => '', 'catid' => 2, 'state' => 1,
		'access' => 1, 'language' => '*', 'created' => Factory::getDate()->toSql(), 'created_by' => $userId,
		'publish_up' => Factory::getDate()->toSql(), 'images' => '{}', 'urls' => '{}', 'attribs' => '{}',
		'metadata' => '{}', 'metakey' => '', 'metadesc' => '',
	]);
	if (!$t->check() || !$t->store()) {
		$fail("pagina $title", $t);
	}
	$o = (object) ['item_id' => (int) $t->id, 'stage_id' => $stage, 'extension' => 'com_content.article'];
	$db->insertObject('#__workflow_associations', $o);
	echo "pagina noua: $title (#{$t->id})\n";

	return (int) $t->id;
};
$amp = $article('Amplasament', 'amplasament');
$tur = $article('Tur virtual', 'tur-virtual');
$gal = $article('Galerii foto', 'galerii-foto');

// meniul ascuns
if (!$one("SELECT COUNT(*) FROM #__menu_types WHERE menutype = 'altepagini'")) {
	$mt = new MenuType($db);
	$mt->bind(['menutype' => 'altepagini', 'title' => 'Alte pagini (ascuns)', 'description' => 'Pagini care nu apar in meniu, dar au linkuri in subsol sau pe Acasa', 'client_id' => 0]);
	if (!$mt->check() || !$mt->store()) {
		$fail('meniul ascuns', $mt);
	}
	echo "meniu ascuns creat: altepagini\n";
}

// muta / modifica un element de meniu
$item = static function (int $id, array $data, ?array $where = null) use ($db, $fail) {
	$t = new Menu($db);
	$t->load($id);
	$t->bind($data);
	if ($where) {
		$t->setLocation($where[0], $where[1]);
	}
	if (!$t->check() || !$t->store()) {
		$fail("elementul de meniu #$id", $t);
	}
	echo "meniu #$id: {$t->title} ({$t->menutype}, parinte {$t->parent_id})\n";
};
$asPage = static fn (string $title, int $articleId) => [
	'title' => $title, 'type' => 'component', 'component_id' => $comId,
	'link' => 'index.php?option=com_content&view=article&id=' . $articleId,
	'params' => $params, 'browserNav' => 0, 'published' => 1,
];

// Despre: scot Corpurile muzeului, Oamenii muzeului si Sustine muzeul in meniul ascuns
foreach ([113, 122, 109] as $id) {
	$item($id, ['menutype' => 'altepagini'], [1, 'last-child']);
}
// Amplasament (elementul vechi din cos, refolosit ca pagina) dupa Asociatia, apoi Comunicate, apoi Istoric
$item(110, $asPage('Amplasament', $amp), [104, 'after']);
$item(118, [], [110, 'after']);
$item(121, [], [118, 'after']);

// Muzeul in imagini: Tur virtual devine pagina noastra
$item(124, $asPage('Tur virtual', $tur));
// elementele vechi din cos de sub Galerii foto primesc alt alias, ca sa nu se bata cu cele noi
foreach ([126 => 'etnografie-vechi', 129 => 'stiinte-vechi', 132 => 'arta-vechi'] as $id => $alias) {
	$item($id, ['alias' => $alias]);
}
$item(125, $asPage('Galerii foto', $gal), [124, 'after']);
foreach ([127, 130, 133] as $id) {
	$item($id, [], [125, 'last-child']);
}

// refac ordinea si adresele (path) pentru tot meniul
$t = new Menu($db);
$t->rebuild();
echo "meniu refacut\n";
