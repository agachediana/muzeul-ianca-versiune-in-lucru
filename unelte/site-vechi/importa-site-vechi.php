<?php
/*
  Pune in Joomla ce a adus preia-site-vechi.js de pe site-ul vechi:
    - textele paginilor Asociația, Istoric muzeu, Oamenii muzeului, Corpurile muzeului,
      Amplasament si Susține muzeul (luate de pe muzeulianca.ro, aproape neschimbate)
    - cele patru tururi 360 (Matterport), cate unul pe corp, si colectia de scanari 3D
    - e-mailul muzeului in setarile temei
    - albumele foto, redenumite si asezate pe corpuri (vezi partials/albume.php)
    - descrierile pozelor din catalog (descrieri.txt in fiecare folder)
  Se poate rula de mai multe ori.

  Folosire:
    php importa-site-vechi.php C:\xampp\htdocs\joomla date.json
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
$db   = $container->get(DatabaseInterface::class);
$date = json_decode(file_get_contents($argv[2]), true);
$img  = JPATH_BASE . '/images/muzeu';
$e    = static fn ($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

// textul unui articol, dupa id
$text = static function (int $id, string $html) use ($db) {
	$t = new Content($db);
	$t->load($id);
	$t->introtext = $html;
	$t->fulltext  = '';
	$t->store();
	echo "pagina #$id: {$t->title} (" . mb_strlen(strip_tags($html)) . " caractere)\n";
};
$p = static fn (array $randuri) => implode("\n", array_map(static fn ($r) => '<p>' . htmlspecialchars($r, ENT_QUOTES, 'UTF-8') . '</p>', $randuri));

/*
  Asociația
*/
$text(1, $p([
	'Asociația cultural-istorică „Muzeul” Ianca este un O.N.G. de utilitate publică ce are în subordine Muzeul din orașul Ianca, județul Brăila. Conform statutului, Asociația noastră militează pentru valorificarea prin muzeu a tradițiilor locale din zonă. Președintele de onoare al Asociației este prof. univ. dr. Ionel Cândea, managerul Muzeului Brăilei „Carol I”, membru corespondent al Academiei Române.',
	'Asociația a fost constituită juridic la jumătatea anului 2000, când Guvernul României anulează prevederile Legii nr. 21/1924 și emite Ordonanța nr. 26/2000.',
]) . "\n<h2>Obiectivele Asociației</h2>\n<ol>"
	. '<li>Cercetarea în vederea valorificării științifice, educative și culturale a zonei de câmpie a Brăilei, reflectată în patrimoniul muzeului.</li>'
	. '<li>Cercetare pe domenii, cât și interdisciplinar, pentru identificarea, conservarea și valorificarea muzeală a patrimoniului cultural și natural.</li>'
	. '<li>Implicarea în activitățile legale menite să contribuie la ocrotirea mediului înconjurător și a patrimoniului cultural național.</li></ol>'
	. "\n<h2>Membrii Asociației</h2>\n<ul class=\"membri\">"
	. '<li><strong>Prof. univ. dr. Ionel Cândea</strong> Managerul Muzeului Brăilei „Carol I”, membru corespondent al Academiei Române, președinte de onoare</li>'
	. '<li><strong>Prof. Ion M. Bărbuceanu</strong> Președinte</li>'
	. '<li><strong>Dr. Marcel Isac</strong> Chestor-general (r)</li>'
	. '<li><strong>Ion Moțoc</strong> Chestor-general (r)</li>'
	. '<li><strong>Ștefan Marin</strong> Comandant aviație civilă</li>'
	. '<li><strong>Nicolae Ionașcu</strong> Comandor (r) aviator</li>'
	. '<li><strong>Dorin Ilie</strong></li>'
	. '<li><strong>Ecaterina Bărbuceanu</strong> Educatoare</li>'
	. '<li><strong>Marius Diaconu</strong> Manager</li></ul>'
	. "\n" . $p(['Muzeul nu este bugetat, se autofinanțează prin cotizații ale membrilor Asociației, sponsorizări, donații și accesări, prin concurs, de proiecte cu finanțare nerambursabilă. Muzeul Ianca a primit un sprijin valoros din partea Consiliului Județean Brăila, a Consiliului Local și Primăriei Ianca (primari Oprișan Tudor și Fănel George Chiriță), Muzeului Brăilei „Carol I”, prin prof. univ. dr. Ionel Cândea și dr. Brîndușa Ilie, cât și din partea unor persoane fizice din zonă; dintre acestea, amintim pe domnii: comandant aviație civilă Marin Ștefan, manager Nae Moise, manager Stanciu Niță, manager Mitache Gicu și profesor Dragomir Ștefănuț.'])
);

/*
  Istoric muzeu: fiecare reper e un subtitlu (anul) si textul de sub el,
  pagina le arata ca o linie a timpului (aspectul istoric)
*/
$repere = [
	['1833', 'Este menționată în documentele vremii existența primei Biserici Ortodoxe la Ianca, pe moșia cucoanei Elena Drugănescu, construită din cărămidă și acoperită cu olane, cu preotul Vasile.'],
	['1930–1941', 'Învățătorii Moroianu Ștefan (de la Școala nr. 1 Ianca), Dogărescu Mihai (director la Școala nr. 2 Sărățeni Ianca, revizor școlar de zonă) și Boboc Ghiță de la Școala din satul Oprișănești au avut în preocuparea lor și realizarea unor scurte monografii ale localității, ale școlii și bisericii.'],
	['1938', 'Este menționată în localitatea Ianca prima școală, cu primul ei învățător, Tudor Sârbu Rizea.'],
	['1958–1968', 'Profesorii de istorie de la Liceul Teoretic „Constantin Angelescu”, precum prof. Liviu Cojocaru și prof. Costel Paraschiv, au amenajat în holul central mai multe vitrine cu obiecte de valoare istorică: colecții numismatice, de documente, de istorie locală, plachete, decorații, brevete și medalii ale unor veterani de război, fotografii vechi din zonă.'],
	['1968–1978', 'Învățătorul Anastasiu Dumitru, fost director al Școlii din satul Târlele Filiu, director de Cămin Cultural din Ianca, a amenajat în 3 camere din această instituție o expoziție de etnografie, cu multe obiecte colecționate din zonă. După plecarea sa din localitate, acest mic muzeu s-a desființat.'],
	['1979–1989', 'Prof. Ion M. Bărbuceanu avea amenajate în Școlile nr. 1 și nr. 2 din Ianca colțuri muzeistice.'],
	['1995', 'Prof. Ion M. Bărbuceanu solicită Consiliului Local al orașului Ianca un spațiu pentru muzeu. Prin HCL 45/21 dec. 1995, Consiliul Local aprobă deschiderea unui muzeu în localul Școlii nr. 1 Ianca, unde acesta funcționa ca profesor.'],
	['1995–1998', 'Mai multe solicitări ale prof. Ion M. Bărbuceanu adresate Consiliului Local Ianca pentru acordarea unei clădiri pentru muzeu.'],
	['1998', 'La Unitatea Militară de Aviație Ianca, după plecarea trupelor sovietice și dizlocarea Regimentului 49 Aviație Vânătoare-Bombardament, s-a amenajat o secție de istoria aviației numită Sala de Tradiție, cu uniforme militare de aviație, chiar și din perioada interbelică, și documente legate de începuturile aeronauticii reactive în România. Aici, la Ianca, la 26 iunie 1951, a avut loc primul zbor al unui avion militar cu reacție în România. În timpul Revoluției, o parte din această colecție a fost luată pentru viitorul Muzeu al Aviației de la București.'],
	['26 noiembrie 1998', 'Prof. Ion M. Bărbuceanu obține de la Consiliul Local, pentru muzeu, o locație stabilă: clădirea din strada Gării nr. 9 (H.C.L. nr. 102/26.XI.1998).'],
	['1999', 'Prof. Ion M. Bărbuceanu contactează Muzeul Brăilei „Carol I”, pe prof. univ. dr. Ionel Cândea, managerul muzeului, care acordă sprijin logistic și unele exponate în custodie pentru amenajarea muzeului.'],
	['25 februarie 1999', 'Prof. Ion M. Bărbuceanu obține repartizarea, în folosință gratuită, a unei camere din imobilul muzeului, pentru înființarea unei asociații și fundații cultural-umanitare (H.C.L. nr. 21/25.02.1999).'],
	['25 octombrie 1999', 'Fundația cultural-istorică „Bălașa” dobândește personalitate juridică la Tribunalul Brăila. Membrii fondatori: dr. Nicolae Baltă, născut în satul Perișoru, medic, șef de secție la Spitalul Filantropia din București, preot paroh Ispas Vasile, de la Biserica din Ianca, și prof. Ion M. Bărbuceanu. Dr. Nicolae Robitu, fiu al localității Ianca, fost deținut politic, a fost inițiatorul acestei fundații pentru înființarea muzeului. Fundația „Bălașa” a contribuit, împreună cu Muzeul Brăilei „Carol I” și Primăria orașului Ianca, la întemeierea muzeului.'],
	['1 decembrie 1999', 'Deschiderea oficială a muzeului de către prof. Ion M. Bărbuceanu, în prezența prof. univ. dr. Ionel Cândea, directorul Muzeului Brăilei „Carol I”, a comandorului Sandache Paul, directorul Muzeului Național al Aviației Române, și a unor personalități județene și locale (președintele CJ Brăila, conf. univ. dr. Aurel Gabriel Simionescu, și primarul orașului Ianca, prof. Oprișan Tudor). Coordonator și muzeograf voluntar, de la înființare și până azi: prof. Ion M. Bărbuceanu.'],
	['Februarie 2000', 'Se extinde activitatea muzeului și în Corpul B, anexă a Corpului A (H.C.L. nr. 10/24.02.2000).'],
	['22 iulie 2003', 'Ministerul Culturii și Cultelor aprobă înființarea instituției culturale „Muzeul Ianca” (adresa nr. 1519/30.07.2003).'],
	['2004', 'Încheierea Protocolului de parteneriat între Consiliul Local al orașului Ianca și Asociația cultural-istorică „Muzeul” Ianca (H.C.L. nr. 8/2004, primar Fănel George Chiriță).'],
	['2009', 'Acreditarea muzeului de către Ministerul Culturii, Cultelor și Patrimoniului Național (ordinul ministrului nr. 2465/3.XI.2009).'],
	['2010', 'Darea în folosință gratuită a întregului Corp B de către Primăria orașului Ianca, după mutarea acesteia într-o nouă locație.'],
	['2011–2012', 'Strămutarea unei case țărănești din satul Oprișenești în curtea muzeului și deschiderea ei, cu sprijinul specialiștilor de la Secția Etnografie a Muzeului Brăilei „Carol I”, coordonați de dr. Brîndușa Ilie, și al prof. univ. dr. Ionel Cândea. Principalul sponsor material a fost ROMSILVA. Inițiator și realizator al proiectului: prof. Ion M. Bărbuceanu, în parteneriat cu Muzeul Brăilei „Carol I” și Primăria Ianca.'],
	['2012', 'Consiliul Județean Brăila aprobă protocolul de parteneriat între Muzeul Brăilei „Carol I” și Asociația cultural-istorică „Muzeul” Ianca (H.C.J. nr. 107/25.09.2012) și sprijină financiar reabilitarea acoperișului Corpului B.'],
	['2013–2014', 'Proiectul „Gospodăria țărănească din zona de câmpie a Brăilei”, finanțat de AFCN (anexă la casa țărănească), inaugurat la 23 noiembrie 2013. „Școala de la Muzeu”, curs de inițiere „Calculatorul, prietenul meu”, finanțat de Fundația PACT București. Reabilitarea sălii de conferințe și amenajarea bibliotecii muzeului, cu sprijinul Fundației PACT.'],
	['2014', 'Reparații capitale la Corpul B, în valoare de 10.000 lei, de către SC Miofan Agrotrans SRL (ing. Marin Ionuț și cdt. aviație Marin Ștefan). Proiectul „Valori cultural-istorice ale orașului Ianca”, finanțat de Consiliul Județean Brăila.'],
	['23 iulie 2014', 'Asociației cultural-istorice „Muzeul” Ianca i se recunoaște, prin Hotărârea nr. 613, statutul de asociație de utilitate publică.'],
	['2016', 'Proiectul „Totul pentru o planetă vie”, finanțat de Fundația pentru Parteneriat (Programul Spații Verzi), și proiectul „Pionieri ai aeronauticii din spațiul brăilean. Ianca și aeronautica”, finanțat de Consiliul Județean Brăila. S-a reamenajat Secția Aviație a muzeului.'],
	['2017', 'Proiectul „Uniforme și însemne ale unităților militare brăilene în Primul Război Mondial. Ianca Eroică”, finanțat de Consiliul Județean Brăila, și broșura „Ianca Eroică”.'],
	['2018', 'Proiectul „Ianca – Centenar – Unire și Recunoștință”, cu broșura cu același nume, și pliantul „Muzeul Ianca – Un început pentru viitor”.'],
	['2019', 'Proiectul „Muzeul Ianca – promotor al tradiției locale”, finanțat de Consiliul Județean Brăila, în parteneriat cu Secția Etnografie a Muzeului Brăilei „Carol I”.'],
	['2020–2021', 'Proiectul „Muzeul Ianca – de la muzeul real la cel virtual”, cu consultant științific dr. Brîndușa Ilie (Muzeul Brăilei „Carol I”, Secția Etnografie).'],
];
$html = '';
foreach ($repere as [$an, $t]) {
	$html .= '<h3>' . $e($an) . "</h3>\n<p>" . $e($t) . "</p>\n";
}
$text(14, $html . '<h2>Publicații</h2><ul><li>Pionieri ai aeronauticii din spațiul brăilean</li><li>Ianca Eroică</li><li>Ianca – Centenar – Unire și Recunoștință, coord. Ion M. Bărbuceanu, Editura Lucas, Brăila</li><li>Muzeul Ianca – Un început pentru viitor</li><li>Muzeul Ianca – promotor al tradiției locale, editori Ion M. Bărbuceanu și Brîndușa Ilie, Editura Lucas, Brăila</li></ul>'
	. '<p><em>La toate proiectele de mai sus, manager de proiect a fost prof. Ion M. Bărbuceanu.</em></p>');

/*
  Oamenii muzeului: fondatorul
*/
$text(15, '<h2>Prof. Ion M. Bărbuceanu, muzeograf</h2>' . $p([
	'Profesor de istorie pensionar, pasionat de artă și literatură, a pus bazele muzeului din orașul brăilean Ianca, mergând din casă în casă pentru a aduna exponate. Pentru profesorul Ion Bărbuceanu nimic nu este prea mult atunci când vine vorba despre zona în care s-a născut și trăiește. O zonă eminamente rurală a dat țării oameni de cultură și eroi care ar cădea definitiv în uitare dacă nu ar exista persoane care să readucă în mintea contemporanilor faptele lor din trecut.',
]) . '<p class="sursa">Sursa: <a href="https://adevarul.ro/" target="_blank" rel="noopener">Adevărul</a></p>'
	. '<ul class="fisa"><li><strong>Născut</strong> 15 decembrie 1946, Oprișănești, Brăila</li><li><strong>Studii</strong> Facultatea de Istorie și Filozofie, București</li></ul>'
	. '<h3>Gala Premiilor Culturale Brăilene, 27 ianuarie 2012</h3>'
	. $p(['Diploma de excelență și trofeul au fost obținute de Muzeul din Ianca, prin prof. Ion Bărbuceanu, pentru salvgardarea și promovarea mărturiilor tradiționale ale locuirii sătești specifice zonei Brăilei.'])
	. '<h3>Un profesor, un muzeu, un eveniment zonal</h3>'
	. $p([
		'De remarcat cum un singur om, profesorul Bărbuceanu, a reușit să promoveze la Ianca un proiect pentru care prin alte locuri se cheltuie prea mult banul public sau nu se face nimic. Respectabil, omul a reușit să atragă alături autoritățile și oamenii de suflet, astfel că, din 1999, a înființat și administrează un muzeu bogat și în piese rare.',
	]) . '<p class="sursa">Sursa: <a href="https://www.viata-libera.ro/vlg-cultura/97867-un-profesor-un-muzeu-un-eveniment-zonal" target="_blank" rel="noopener">Viața Liberă</a></p>');

/*
  Corpurile muzeului: un subtitlu (h2) pentru fiecare corp
*/
$text(8, '<h2>Corpul A · Secția Artă și Expoziții Temporare</h2>' . $p([
	'Clădire construită la jumătatea secolului al XX-lea și donată de proprietar, până la intrarea României în al Doilea Război Mondial, Administrației Plasei Ianca.',
	'A fost, pe rând, Circă fiscală a Plasei Ianca, iar în timpul războiului, atât pentru germani, cât și pentru ruși, sediu al comandamentului militar zonal. După încheierea războiului a fost sediu al PCR Ianca, al Casei Pionierului și, după Revoluție, al Bibliotecii orășenești.',
	'În anul 1998, Consiliul Local al orașului Ianca, la solicitarea profesorului Ion M. Bărbuceanu, acordă această clădire pentru înființarea unui muzeu în orașul Ianca.',
]) . '<h2>Corpul B · Biserica, Armata, Școala și Viața Satului</h2>' . $p([
	'Clădire construită în timpul regimului comunist, anexă a Corpului A, spațiu pentru Casa Pionierului. Începând cu anul 1990 aici au funcționat mai multe servicii ale Primăriei Ianca, instituție vecină acestui așezământ.',
	'După mutarea Primăriei în noul sediu de pe Calea Brăilei, acest corp de clădire a fost dat în folosință gratuită muzeului. Cu sprijinul Consiliului Județean Brăila și al Primăriei Ianca (primar Fănel George Chiriță) s-a realizat un acoperiș nou, iar pentru interior muzeul a beneficiat de sprijinul financiar al domnilor Marin Ștefan și Marin Ionuț. Toate încăperile au fost reabilitate spre a fi folosite în scop muzeal.',
	'În holul clădirii se află o expoziție de costume naționale, piese din cultura țărănească și unelte agricole.',
]) . '<h2>Corpul C · Casa țărănească</h2>' . $p([
	'Casa țărănească a fost strămutată în curtea muzeului din satul Oprișenești. A fost construită de Stan Mortu în perioada 1909–1910. Moștenitorii acestuia au vândut-o familiei Mitrea Stan și Radian, care a construit pe acel teren un complex comercial.',
	'Profesorul Ion M. Bărbuceanu, în înțelegere cu această familie, primește gratuit casa pentru a o aduce în curtea muzeului. În colaborare cu Muzeul Brăilei „Carol I”, cu sprijinul prof. univ. dr. Ionel Cândea, se acordă sprijinul de specialitate pentru valorificarea științifică a casei, prin dr. Brîndușa Ilie, muzeograf la Secția Etnografie. Munca maistrului Radu Nenu și a nepotului său, din satul Plopu, în perioada 2011–2012, a făcut posibilă aducerea casei în muzeu, iar transportul materialelor a fost asigurat de Primăria orașului Ianca.',
	'Interiorul casei reproduce habitatul cotidian al unei familii de țărani din Câmpia Brăilei de acum 100 de ani. Piesele expuse aparțin fondului personal al familiei Ion și Ecaterina Bărbuceanu, strânse din generație în generație de la bunici, părinți, socri și rude apropiate.',
]) . '<h2>Corpul D · Depozitul de unelte</h2>' . $p([
	'În Depozitul de unelte sunt expuse unelte din zona de câmpie a Brăilei: un car, mai multe căruțe, diferite pluguri și unelte care arată civilizația agricolă specifică acestei zone etnografice.',
	'În curtea interioară a muzeului este amenajat un model de gospodărie țărănească din zonă, unde regăsim bucătăria de vară, umbrarul, fântâna cu cumpănă și ulucul din piatră pentru animale, porumbarul și cotețul pentru păsări. Proiectul a fost realizat cu fonduri AFCN, manager de proiect prof. Ion M. Bărbuceanu.',
]));

/*
  Amplasament si Susține muzeul
*/
$text(53, $p([
	'Muzeul din orașul Ianca, județul Brăila, este găzduit într-o casă construită înainte de ultimul război mondial și donată de proprietar autorităților din Ianca. Fondatorul muzeului este profesorul Ion Bărbuceanu, sufletul acestei instituții. Muzeul are mai multe încăperi în care sunt prezentate obiectele colecționate cu multă pasiune de profesorul Ion Bărbuceanu, fondatorul și directorul acestei instituții de cultură din Ianca.',
	'Muzeul ilustrează existența satului românesc, a școlii de altădată, a bisericii strămoșești și a aviației militare de la Aerodromul Ianca.',
]));
$text(6, $p([
	'Istoria și cultura orașului Ianca trăiesc și sunt susținute prin activitatea Muzeului Ianca. Sponsorizările și mecanismul de redirecționare a 3,5% din impozitul pe venit ajută muzeul să-și continue activitatea.',
	'Descarcă și completează formularul 230 sau adaugă online, la Declarația Unică, Asociația „Muzeul” Ianca ca beneficiar al celor 3,5% din impozitul pe venit. Contactează-ne pentru detalii.',
]));

/*
  Setarile temei: tururile pe corpuri, scanarile 3D si e-mailul
*/
// turul 3DVista al muzeului, deschis direct la sala fiecarui corp (tururile Matterport
// de pe site-ul vechi au fost sterse de pe Matterport si nu mai merg)
$tururi = ['tur1' => 'Arta 1', 'tur2' => 'Hol 1 - cladire spate', 'tur3' => 'Casa taraneasca', 'tur4' => 'Panorama Ianca Depozit'];
$params = json_decode((string) $db->setQuery('SELECT params FROM #__template_styles WHERE id = 13')->loadResult(), true);
foreach ($tururi as $k => $sala) {
	$params[$k] = 'https://turvirtual.muzeulianca.ro/#media-name=' . rawurlencode($sala);
}
$params['sketchfab'] = $date['sketchfab'] ?? '';
if (empty($params['email'])) {
	$params['email'] = 'iancamuzeul@yahoo.ro';
}
$db->setQuery('UPDATE #__template_styles SET params = ' . $db->quote(json_encode($params, JSON_UNESCAPED_UNICODE)) . ' WHERE id = 13')->execute();
echo "tema: 4 tururi, scanari 3D, e-mail\n";

/*
  Albumele: folderul primeste numele scurt din partials/albume.php
*/
$redenumiri = [
	'sectia-etnografie' => 'b-sectia-etnografie', 'expozitie' => 'a-expozitie', 'expozitia-biserica-stramoseasca' => 'b-biserica-stramoseasca',
	'expozitia-scoala-de-altadata' => 'b-scoala-de-altadata', 'sectia-aviatie' => 'b-sectia-aviatie', 'curtea-interioar-a' => 'd-curtea-interioara',
	'sectia-de-arta-tablouri-de-geogeta-crainic' => 'a-georgeta-crainic', 'expozi-tia-marii-uniri-si-centenarului' => 'a-marea-unire',
	'casa-taraneasca-stramutata-in-curtea-muzeului-din' => 'c-casa-taraneasca', 'depozitul-de-unelte-din-zona-de-campie-a-brailei-l' => 'd-depozitul-de-unelte',
];
foreach ($redenumiri as $vechi => $nou) {
	if (is_dir("$img/albume/$vechi") && !is_dir("$img/albume/$nou")) {
		rename("$img/albume/$vechi", "$img/albume/$nou");
		echo "album: $nou\n";
	}
}

/*
  Descrierile pozelor din catalog si ale scanarilor 3D
*/
foreach (['etnografie', 'arta'] as $s) {
	$lista = $date['catalog'][$s] ?? [];
	$rand  = [];
	foreach ($lista as $o) {
		if (!empty($o['fisier'])) {
			$rand[] = basename($o['fisier']) . '=' . $o['titlu'];
		}
	}
	file_put_contents("$img/galerii/$s/descrieri.txt", implode("\n", $rand) . "\n");
	// lista tuturor obiectelor colectiei, si a celor fara poza
	file_put_contents("$img/galerii/$s/inventar.txt", implode("\n", array_column($lista, 'titlu')) . "\n");
	echo "catalog $s: " . count($rand) . ' poze, ' . count($lista) . " obiecte\n";
}
$rand = [];
foreach ($date['scanari'] ?? [] as $o) {
	$rand[] = basename($o['fisier']) . '=' . $o['titlu'];
}
file_put_contents("$img/scanari-3d/descrieri.txt", implode("\n", $rand) . "\n");
echo 'scanari 3D: ' . count($rand) . "\n";
