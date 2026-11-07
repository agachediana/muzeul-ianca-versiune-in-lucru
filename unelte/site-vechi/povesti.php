<?php
/*
  Umple pagina Povești ale locului (categoria Articole istorice) cu ce are
  turul virtual al muzeului (turvirtual.muzeulianca.ro): scrisoarea la 22 de ani
  de la infiintarea muzeului si interviurile video. Videoclipurile raman pe
  serverul turului si se incarca doar cand apesi pe ele; copertele sunt in
  images/muzeu/povesti. Pagina foloseste aspectul povesti al temei.
  Se poate rula de mai multe ori, ce exista deja nu se mai creeaza.

  Folosire:
    php povesti.php C:\xampp\htdocs\joomla
*/

const _JEXEC = 1;
define('JPATH_BASE', rtrim($argv[1], '\\/'));
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Content;
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
$e   = static fn ($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

$cat   = (int) $one("SELECT id FROM #__categories WHERE alias = 'articole-istorice' AND extension = 'com_content'");
$user  = (int) $one('SELECT id FROM #__users ORDER BY id LIMIT 1');
$stage = (int) $one('SELECT id FROM #__workflow_stages WHERE `default` = 1 AND workflow_id = 1');
$tur   = 'https://turvirtual.muzeulianca.ro/media/';

// interviurile: titlul, o propozitie de prezentare, coperta si videoclipul de pe serverul turului
$video = static fn (string $fisier, string $text) => '<p class="video-poveste"><a class="video-link" href="' . $tur . $fisier . '">' . $text . '</a></p>';
$scrisoare = [
	'Călătoria nu are sens, până când nu ajungi la destinație. Abia atunci când partea activă a vieții mele s-a încheiat, m-am găsit în situația de a mă uita înapoi, pentru a rememora o parte din evenimentele care m-au marcat. Am lucrat la Ianca în perioada 1983-1987, fiind comandantul unității de aviație.',
	'Am găsit aici militari care m-au ajutat să-mi conturez personalitatea și cărora le port o mare prețuire; același sentiment îl am față de oamenii locului, pe care i-am cunoscut ca fiind hotărâți, destoinici și foarte curajoși.',
	'În acea perioadă l-am cunoscut pe profesorul Ion Bărbuceanu. Inițiativa înființării și, ulterior, a dezvoltării muzeului orașului îi aparține și este, cu siguranță, cea mai bună faptă din viața sa.',
	'Muzeul, o prezență importantă în cultura și tradiția locală, nu poate pretinde că oferă imaginea completă a trecutului, dar ne ajută să construim punți cu trecutul, cu gândul la prezent, iar pe cei mai tineri îi ajută să aprindă scânteia năzuinței către viitor.',
	'Se respiră în această casă-muzeu, donată de un inimos cetățean al urbei, aerul îmbibat de mireasma de autentic vechi autohton, fiind, fără exagerare, depozitul valoros pentru istoria civilizației zonei.',
	'La aniversarea a 22 de ani de la înființarea acestui muzeu, adresez felicitări tuturor celor care au participat la îndeplinirea acestui act de cultură. Efectul permanentei căutări de creștere a atractivității este, începând din acest an, modernizarea prezentării exponatelor și informațiilor, prin abordarea narativă a vizionării online a muzeului.',
	'Turul virtual interactiv, cu toate avantajele sale, lansează ideea de muzeu din categoria „Noua Generație”.',
	'La mulți ani!',
];
$povesti = [
	['alias' => 'la-22-de-ani-de-la-infiintarea-muzeului', 'titlu' => 'La 22 de ani de la înființarea muzeului', 'img' => 'images/muzeu/evenimente/cladirea-muzeului.jpg',
		'text' => '<p>Scrisoarea generalului de aviație (r) Constantin Gheorghe, comandantul unității de aviație de la Ianca între 1983 și 1987, la aniversarea muzeului.</p><blockquote>'
			. implode('', array_map(static fn ($r) => '<p>' . $e($r) . '</p>', $scrisoare))
			. '<p><em>Gl. av. (r) Constantin Gheorghe, București, 30 decembrie 2021</em></p></blockquote>'],
	['alias' => 'interviu-dr-brindusa-ilie', 'titlu' => 'Interviu cu dr. Brîndușa Ilie', 'img' => 'images/muzeu/povesti/dr-brindusa-ilie.jpg',
		'text' => '<p>Dr. Brîndușa Ilie, muzeograf la Secția Etnografie a Muzeului Brăilei „Carol I”, a coordonat sprijinul de specialitate pentru casa țărănească strămutată în curtea muzeului.</p>' . $video('video_6C7B2F4C_77B0_CA4F_41DD_5B38B354F0BF_en.mp4', 'Vezi interviul')],
	['alias' => 'interviu-preotul-paroh', 'titlu' => 'Interviu cu preotul paroh', 'img' => 'images/muzeu/povesti/preotul-paroh.jpg',
		'text' => '<p>Preotul paroh din Ianca, filmat în fața casei țărănești a muzeului.</p>' . $video('video_572AE40A_76D0_7DCB_41DC_3BF31B13A919_en.mp4', 'Vezi interviul')],
	['alias' => 'interviu-col-gaju', 'titlu' => 'Interviu cu col. Gaju', 'img' => 'images/muzeu/povesti/col-gaju.jpg',
		'text' => '<p>Un interviu din turul virtual al Muzeului Ianca.</p>' . $video('video_573F7DD0_76B3_CE57_41C9_CFF98B67CD14_en.mp4', 'Vezi interviul')],
	['alias' => 'interviu-comandor-dorin-ilie', 'titlu' => 'Interviu cu comandorul (r) aviator Dorin Ilie', 'img' => 'images/muzeu/povesti/comandor-dorin-ilie.jpg',
		'text' => '<p>Comandorul (r) aviator Dorin Ilie, fost comandant la Ianca și membru al Asociației „Muzeul” Ianca.</p>' . $video('video_107F3AF9_012D_DB71_4157_082241E6480A_en.mp4', 'Vezi interviul')],
	['alias' => 'muzeul-ianca-la-tvr-3', 'titlu' => 'Muzeul Ianca la TVR 3', 'img' => 'images/muzeu/povesti/tvr3-cantec-si-poveste.jpg',
		'text' => '<p>Prezentarea muzeului făcută de prof. Ion Bărbuceanu în emisiunea „Cântec și poveste” de la TVR 3.</p>' . $video('video_5FAC84CF_7D70_5E49_41C1_1174691096B2_en.mp4', 'Vezi emisiunea')],
	['alias' => 'film-muzeul-ianca-ion-barbuceanu', 'titlu' => 'Film: Muzeul Ianca și prof. Ion M. Bărbuceanu', 'img' => 'images/muzeu/povesti/film-ion-barbuceanu.jpg',
		'text' => '<p>Un film despre muzeu și despre fondatorul lui. Filmul e mare (aproape 1,2 GB) și pornește după ce apeși pe el.</p>' . $video('video_171F9188_012C_A99F_4123_F2CF3E1BDA20_en.mp4', 'Vezi filmul')],
];

foreach ($povesti as $k => $p) {
	$id = (int) $one('SELECT id FROM #__content WHERE catid = ' . $cat . ' AND alias = ' . $db->quote($p['alias']));
	$t  = new Content($db);
	if ($id) {
		$t->load($id);
	}
	// ordinea de pe pagina: scrisoarea prima, apoi interviurile
	$data = Factory::getDate('2021-12-30 10:00:00')->modify('-' . $k . ' minutes')->toSql();
	$t->bind([
		'title' => $p['titlu'], 'alias' => $p['alias'], 'introtext' => $p['text'], 'fulltext' => '', 'catid' => $cat, 'state' => 1,
		'access' => 1, 'language' => '*', 'publish_up' => $data,
		'images' => json_encode(['image_intro' => $p['img'], 'image_intro_alt' => $p['titlu'], 'image_fulltext' => $p['img'], 'image_fulltext_alt' => $p['titlu']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
	] + ($id ? [] : ['created' => $data, 'created_by' => $user, 'urls' => '{}', 'attribs' => '{}', 'metadata' => '{}', 'metakey' => '', 'metadesc' => '']));
	if (!$t->check() || !$t->store()) {
		exit('EROARE ' . $p['titlu'] . ': ' . $t->getError() . "\n");
	}
	if (!$id) {
		$o = (object) ['item_id' => (int) $t->id, 'stage_id' => $stage, 'extension' => 'com_content.article'];
		$db->insertObject('#__workflow_associations', $o);
	}
	echo ($id ? 'actualizat' : 'creat') . " #{$t->id}: {$p['titlu']}\n";
}

// pagina din meniu si categoria folosesc aspectul povesti
$db->setQuery('UPDATE #__menu SET link = ' . $db->quote("index.php?option=com_content&view=category&layout=muzeulianca:povesti&id=$cat")
	. " WHERE client_id = 0 AND alias = 'povesti-ale-locului'")->execute();
$cp = json_decode((string) $one("SELECT params FROM #__categories WHERE id = $cat"), true) ?: [];
$cp['category_layout'] = 'muzeulianca:povesti';
$db->setQuery('UPDATE #__categories SET params = ' . $db->quote(json_encode($cp)) . " WHERE id = $cat")->execute();
echo "Povești ale locului: aspectul povesti\n";
