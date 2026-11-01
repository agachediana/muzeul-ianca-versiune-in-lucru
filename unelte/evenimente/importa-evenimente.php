<?php
/*
  Importul evenimentelor, pasul 5 din 5.
  Pune evenimentele de pe site-ul vechi in categoria evenimente din Joomla:
    - face campurile data-sfarsit si doar-luna, daca lipsesc
    - actualizeaza articolele care exista deja si le creeaza pe celelalte
    - pune poza principala si galeria (restul pozelor) in articol
    - leaga meniul Evenimente si categoria de aspectele temei (evenimente, eveniment)

  Folosire:
    php importa-evenimente.php C:\xampp\htdocs\joomla date/evenimente.json
*/

const _JEXEC = 1;

$root = rtrim($argv[1], '\\/');
$data = json_decode(file_get_contents($argv[2]), true);

define('JPATH_BASE', $root);
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Content;
use Joomla\Component\Fields\Administrator\Table\FieldTable;
use Joomla\Database\DatabaseInterface;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(\Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(\Joomla\Session\Session::class, 'session.cli')
	->alias(\Joomla\Session\SessionInterface::class, 'session.cli');
$app = $container->get(\Joomla\Console\Application::class);
Factory::$application = $app;
\JLoader::registerNamespace('Joomla\\Component\\Fields\\Administrator', JPATH_ADMINISTRATOR . '/components/com_fields/src');
$db = $container->get(DatabaseInterface::class);

$one = static fn (string $sql) => $db->setQuery($sql)->loadResult();
$tz  = new DateTimeZone('Europe/Bucharest');
$utc = new DateTimeZone('UTC');

$catId  = (int) $one("SELECT id FROM #__categories WHERE alias='evenimente' AND extension='com_content'");
$userId = (int) $one("SELECT id FROM #__users ORDER BY id LIMIT 1");
$stage  = (int) $one("SELECT id FROM #__workflow_stages WHERE `default` = 1 AND workflow_id = 1");

// campurile: le afiseaza tema, asa ca Joomla nu le mai afiseaza singur (display = 0)
$noDisplay = '{"hint":"","class":"","label_class":"","show_on":"","showon":"","render_class":"","value_render_class":"","showlabel":"1","label_render_class":"","display":"0","prefix":"","suffix":"","layout":"","display_readonly":"2","searchindex":"0"}';
$fieldId = static function (string $name) use ($one) {
	return (int) $one("SELECT id FROM #__fields WHERE name=" . Factory::getContainer()->get(DatabaseInterface::class)->quote($name) . " AND context='com_content.article'");
};
$ensureField = static function (array $def) use ($db, $fieldId, $noDisplay, $userId) {
	$id = $fieldId($def['name']);
	if ($id) {
		return $id;
	}
	$t = new FieldTable($db);
	$t->bind($def + [
		'context' => 'com_content.article', 'group_id' => 0, 'state' => 1, 'access' => 1, 'language' => '*',
		'params' => $noDisplay, 'note' => '', 'description' => '', 'required' => 0,
		'created_user_id' => $userId, 'created_time' => Factory::getDate()->toSql(),
	]);
	if (!$t->check() || !$t->store()) {
		exit('Câmp eșuat: ' . $def['name'] . ' ' . $t->getError() . "\n");
	}
	echo "câmp creat: {$def['name']} (#{$t->id})\n";

	return (int) $t->id;
};

$fStart = $fieldId('data-eveniment');
$db->setQuery("UPDATE #__fields SET params=" . $db->quote($noDisplay) . " WHERE id=" . $fStart)->execute();
$fEnd = $ensureField(['title' => 'Data de sfârșit', 'name' => 'data-sfarsit', 'label' => 'Data de sfârșit (opțional)',
	'type' => 'calendar', 'default_value' => '', 'fieldparams' => '{"showtime":1}',
	'description' => 'Pentru evenimente de mai multe zile. Gol = evenimentul ține o singură zi.']);
$fMonth = $ensureField(['title' => 'Afișează doar luna și anul', 'name' => 'doar-luna', 'label' => 'Afișează doar luna și anul',
	'type' => 'radio', 'default_value' => '0',
	'fieldparams' => '{"options":{"options0":{"name":"Nu","value":"0"},"options1":{"name":"Da","value":"1"}}}',
	'description' => 'Da = ziua nu e cunoscută; pe site apare doar luna și anul.']);

$setValue = static function (int $field, int $item, ?string $value) use ($db) {
	$db->setQuery("DELETE FROM #__fields_values WHERE field_id=$field AND item_id=" . $db->quote((string) $item))->execute();
	if ($value === null || $value === '') {
		return;
	}
	$o = (object) ['field_id' => $field, 'item_id' => (string) $item, 'value' => $value];
	$db->insertObject('#__fields_values', $o);
};
$toUtc = static function (string $localDate, string $time) use ($tz, $utc) {
	$d = new DateTime($localDate . ' ' . $time, $tz);
	$d->setTimezone($utc);

	return $d->format('Y-m-d H:i:s');
};

// evenimentele
foreach ($data as $ev) {
	$id = (int) $ev['existingId'];
	if (!$id) {
		$id = (int) $one("SELECT id FROM #__content WHERE catid=$catId AND alias=" . $db->quote($ev['alias']));
	}

	$photos = $ev['photos'];
	$intro  = '<p>' . htmlspecialchars($ev['text'], ENT_QUOTES, 'UTF-8') . '</p>';
	$full   = '';
	if (count($photos) > 1) {
		$full = '<div class="ev-galerie">';
		foreach (array_slice($photos, 1) as $k => $ph) {
			$alt  = htmlspecialchars($ev['title'] . ' – fotografia ' . ($k + 2), ENT_QUOTES, 'UTF-8');
			$full .= '<figure><a href="' . $ph['file'] . '"><img src="' . $ph['file'] . '" alt="' . $alt . '" loading="lazy"></a></figure>';
		}
		$full .= '</div>';
	}

	$table = new Content($db);
	if ($id) {
		$table->load($id);
	}
	$images = $table->images ?: '{}';
	if ($photos) {
		$alt = $ev['title'];
		$images = json_encode([
			'image_intro' => $photos[0]['file'], 'image_intro_alt' => $alt, 'float_intro' => '', 'image_intro_caption' => '',
			'image_fulltext' => $photos[0]['file'], 'image_fulltext_alt' => $alt, 'float_fulltext' => '', 'image_fulltext_caption' => '',
		], JSON_UNESCAPED_UNICODE);
	}

	$table->bind([
		'title' => $ev['title'], 'alias' => $ev['alias'], 'introtext' => $intro, 'fulltext' => $full,
		'catid' => $catId, 'state' => 1, 'access' => 1, 'language' => '*', 'images' => $images,
		'publish_up' => $toUtc($ev['start'], '08:00:00'),
	] + ($id ? [] : [
		'created' => Factory::getDate()->toSql(), 'created_by' => $userId,
		'urls' => '{}', 'attribs' => '{}', 'metadata' => '{}', 'metakey' => '', 'metadesc' => '',
	]));
	if (!$table->check() || !$table->store()) {
		echo "EROARE {$ev['title']}: " . $table->getError() . "\n";
		continue;
	}
	$id = (int) $table->id;

	if (!$one("SELECT COUNT(*) FROM #__workflow_associations WHERE item_id=$id AND extension='com_content.article'") && $stage) {
		$a = (object) ['item_id' => $id, 'stage_id' => $stage, 'extension' => 'com_content.article'];
		$db->insertObject('#__workflow_associations', $a);
	}

	$setValue($fStart, $id, $toUtc($ev['start'], '00:00:00'));
	$setValue($fEnd, $id, $ev['end'] ? $toUtc($ev['end'], '23:59:00') : null);
	$setValue($fMonth, $id, $ev['monthOnly'] ? '1' : '0');

	echo ($ev['existingId'] ? 'actualizat' : 'creat') . " #$id: {$ev['title']} (" . count($photos) . " poze)\n";
}

// pagina Evenimente si articolele ei folosesc aspectele temei
$menuId = (int) $one("SELECT id FROM #__menu WHERE menutype='mainmenu' AND link LIKE 'index.php?option=com_content&view=category%id=$catId' AND published=1");
if ($menuId) {
	$params = json_decode($one("SELECT params FROM #__menu WHERE id=$menuId"), true);
	$params['article_layout'] = 'muzeulianca:eveniment';
	$db->setQuery("UPDATE #__menu SET link=" . $db->quote("index.php?option=com_content&view=category&layout=muzeulianca:evenimente&id=$catId")
		. ", params=" . $db->quote(json_encode($params)) . " WHERE id=$menuId")->execute();
	echo "meniul Evenimente (#$menuId) folosește layout-ul „evenimente”\n";
}
$cparams = json_decode($one("SELECT params FROM #__categories WHERE id=$catId"), true) ?: [];
$cparams['category_layout'] = 'muzeulianca:evenimente';
$cparams['article_layout']  = 'muzeulianca:eveniment';
$db->setQuery("UPDATE #__categories SET params=" . $db->quote(json_encode($cparams)) . " WHERE id=$catId")->execute();
echo "categoria Evenimente: layout-uri setate\n";
