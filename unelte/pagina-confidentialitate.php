<?php
/*
  Creeaza pagina "Politica de confidentialitate" (alias confidentialitate), doar cu structura:
  cele 9 parti din ghid, fiecare cu intrebarile la care trebuie sa raspunda textul.
  Pagina sta in meniul ascuns, iar linkul din subsol apare singur.

  Folosire:
    php pagina-confidentialitate.php C:\xampp\htdocs\joomla
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

if ($one("SELECT id FROM #__content WHERE alias = 'confidentialitate'")) {
	exit("pagina exista deja\n");
}

$parti = [
	'1. Cine suntem' => 'Care e denumirea exactă a asociației, sediul, e-mailul și telefonul? Cine răspunde la întrebări despre date? (Un responsabil cu protecția datelor nu e obligatoriu pentru un muzeu mic; dacă nu există, scrie asta.)',
	'2. Ce date strângem și de unde' => 'Pentru fiecare situație, separat: telefon și e-mail; bilete cumpărate prin Booktes; fotografii de la evenimente; asistentul (dacă va exista); jurnalele serverului de găzduire; harta Google (se încarcă doar dacă vizitatorul o cere).',
	'3. De ce și pe ce temei' => 'Pentru fiecare situație: scopul și temeiul (consimțământ, contract, obligație legală sau interes legitim). Dacă e interes legitim, care e interesul?',
	'4. Cui le dăm' => 'Firma de găzduire, Booktes, Google (doar pentru hartă, după clic), contabilul. Pleacă date în afara Uniunii Europene? Dacă da, unde și cu ce garanții?',
	'5. Cât timp le păstrăm' => 'Un termen sau un criteriu pentru fiecare situație.',
	'6. Ce drepturi ai' => 'Acces, rectificare, ștergere, restricționare, opoziție, portabilitate, retragerea consimțământului, plângere la ANSPDCP (dataprotection.ro).',
	'7. Fotografiile de la evenimente' => 'Cum anunțați că se fotografiază, ce faceți pentru copii, cum cere cineva scoaterea unei fotografii.',
	'8. Cookie-uri și stocare locală' => 'Ce folosește site-ul și ce nu. Ca punct de plecare, din cod: în browser se salvează doar preferințele de accesibilitate (mărimea textului, contrastul, linkurile subliniate), iar Joomla poate folosi un cookie de sesiune. Fonturile și scripturile sunt pe serverul site-ului, iar harta Google se încarcă doar la clic.',
	'9. Data ultimei actualizări' => 'Ziua în care textul a fost aprobat de muzeu.',
];
$html = '<p><em>Ciornă. Textul îl scrie Diana, cu cuvintele ei, iar înainte de publicare îl aprobă muzeul. Fiecare parte de mai jos are întrebările la care trebuie să răspundă.</em></p>';
foreach ($parti as $titlu => $intrebari) {
	$html .= "\n<h2>" . htmlspecialchars($titlu) . "</h2>\n<p>[De completat] " . htmlspecialchars($intrebari) . '</p>';
}

$userId = (int) $one('SELECT id FROM #__users ORDER BY id LIMIT 1');
$stage  = (int) $one('SELECT id FROM #__workflow_stages WHERE `default` = 1 AND workflow_id = 1');
$t = new Content($db);
$t->bind([
	'title' => 'Politica de confidențialitate', 'alias' => 'confidentialitate', 'introtext' => $html, 'fulltext' => '',
	'catid' => 2, 'state' => 1, 'access' => 1, 'language' => '*', 'created' => Factory::getDate()->toSql(),
	'created_by' => $userId, 'publish_up' => Factory::getDate()->toSql(), 'images' => '{}', 'urls' => '{}',
	'attribs' => '{}', 'metadata' => '{}', 'metakey' => '', 'metadesc' => '',
]);
if (!$t->check() || !$t->store()) {
	exit('EROARE pagina: ' . $t->getError() . "\n");
}
$o = (object) ['item_id' => (int) $t->id, 'stage_id' => $stage, 'extension' => 'com_content.article'];
$db->insertObject('#__workflow_associations', $o);
echo "pagina: #{$t->id}\n";

// elementul de meniu, in meniul ascuns "altepagini"
$m = new Menu($db);
$m->bind([
	'menutype' => 'altepagini', 'title' => 'Politica de confidențialitate', 'alias' => 'confidentialitate',
	'type' => 'component', 'link' => 'index.php?option=com_content&view=article&id=' . $t->id,
	'component_id' => (int) $one("SELECT extension_id FROM #__extensions WHERE element = 'com_content' AND type = 'component'"),
	'published' => 1, 'access' => 1, 'language' => '*', 'client_id' => 0, 'browserNav' => 0,
	'params' => $one('SELECT params FROM #__menu WHERE id = 104'), 'img' => '', 'template_style_id' => 0,
]);
$m->setLocation(1, 'last-child');
if (!$m->check() || !$m->store()) {
	exit('EROARE meniu: ' . $m->getError() . "\n");
}
$m->rebuildPath($m->id);
echo "meniu: #{$m->id} ({$m->path})\n";
