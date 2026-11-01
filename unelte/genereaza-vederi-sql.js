/*
  Scrie fisierul baza_de_date_vederi.sql: vederi (VIEW) cu nume romanesti peste
  tabelele Joomla si ghidul celor 76 de tabele, pe grupe.

  Folosire:
    node genereaza-vederi-sql.js baza_de_date_vederi.sql
*/
const fs = require('fs');
const P = 'sv49d_';

const G1 = '1. Conținutul muzeului';
const G2 = '2. Utilizatori și drepturi';
const G3 = '3. Funcționarea Joomla (necesare)';
const G4 = '4. Funcții Joomla nefolosite pe site';

const tables = [
  ['content', G1, 'Articolele: paginile site-ului (Istoric, Contact...) și toate evenimentele'],
  ['categories', G1, 'Categoriile articolelor: Evenimente, Anunțuri, Știri, Articole istorice'],
  ['fields', G1, 'Câmpurile în plus ale evenimentelor: data de început, data de sfârșit, „doar luna”'],
  ['fields_values', G1, 'Valorile acelor câmpuri pentru fiecare eveniment (datele, în ora UTC)'],
  ['fields_categories', G1, 'La ce categorii se aplică fiecare câmp (gol = la toate)'],
  ['fields_groups', G1, 'Grupuri de câmpuri (nefolosit)'],
  ['menu', G1, 'Meniul site-ului (și meniul administrării)'],
  ['menu_types', G1, 'Lista meniurilor (ex. „Main Menu”)'],
  ['modules', G1, 'Modulele: meniul, firul de navigare și blocurile de pe pagini'],
  ['modules_menu', G1, 'Pe ce pagini apare fiecare modul'],
  ['template_styles', G1, 'Setările temei „muzeulianca” (program, adresă, telefon, poza din hero...)'],
  ['content_frontpage', G1, 'Articolele marcate „Recomandate” (nefolosit)'],
  ['history', G1, 'Versiunile anterioare ale articolelor (pentru „Restaurează versiunea”)'],
  ['contentitem_tag_map', G1, 'Legătura articol–etichetă (nefolosit)'],
  ['tags', G1, 'Etichetele (nefolosit)'],
  ['workflow_associations', G1, 'Etapa de publicare a fiecărui articol (cerută de Joomla)'],

  ['users', G2, 'Conturile de administrare'],
  ['usergroups', G2, 'Grupurile de utilizatori (Public, Administrator...)'],
  ['user_usergroup_map', G2, 'Ce utilizator în ce grup este'],
  ['viewlevels', G2, 'Nivelurile de acces (Public, Registered...)'],
  ['assets', G2, 'Permisiunile pentru fiecare articol, categorie și extensie'],
  ['session', G2, 'Sesiunile deschise (se golește singur)'],
  ['user_keys', G2, 'Chei pentru „Ține-mă minte” la autentificare'],
  ['user_mfa', G2, 'Autentificare în doi pași'],
  ['user_notes', G2, 'Notițe despre utilizatori'],
  ['user_profiles', G2, 'Date de profil ale utilizatorilor'],
  ['webauthn_credentials', G2, 'Autentificare cu cheie de securitate / amprentă'],
  ['messages', G2, 'Mesajele interne dintre administratori'],
  ['messages_cfg', G2, 'Setările mesajelor interne'],
  ['privacy_consents', G2, 'Consimțăminte GDPR'],
  ['privacy_requests', G2, 'Cereri GDPR (export / ștergere date)'],

  ['extensions', G3, 'Toate extensiile instalate: componente, module, pluginuri, teme, limbi'],
  ['schemas', G3, 'Versiunea structurii bazei de date (pentru actualizări)'],
  ['updates', G3, 'Actualizările disponibile găsite'],
  ['update_sites', G3, 'De unde se descarcă actualizările'],
  ['update_sites_extensions', G3, 'Ce extensie se actualizează de pe ce site'],
  ['tuf_metadata', G3, 'Verificarea semnăturii actualizărilor Joomla'],
  ['postinstall_messages', G3, 'Mesajele de după instalare / actualizare'],
  ['languages', G3, 'Limbile de conținut (pentru site-uri în mai multe limbi)'],
  ['overrider', G3, 'Textele de interfață modificate'],
  ['template_overrides', G3, 'Evidența fișierelor de temă suprascrise'],
  ['mail_templates', G3, 'Șabloanele e-mailurilor trimise de Joomla'],
  ['scheduler_tasks', G3, 'Sarcinile programate (ex. verificarea actualizărilor)'],
  ['scheduler_logs', G3, 'Jurnalul sarcinilor programate'],
  ['content_types', G3, 'Tipurile de conținut cunoscute de Joomla'],
  ['ucm_base', G3, 'Index comun de conținut (folosit de etichete)'],
  ['ucm_content', G3, 'Index comun de conținut (folosit de etichete)'],
  ['workflows', G3, 'Fluxul de publicare (unul singur, cel implicit)'],
  ['workflow_stages', G3, 'Etapele fluxului de publicare'],
  ['workflow_transitions', G3, 'Trecerile dintre etape (Publică, Arhivează, Coș...)'],
  ['associations', G3, 'Legăturile dintre traduceri (nefolosit, site într-o limbă)'],
  ['guidedtours', G3, 'Turul ghidat din administrare'],
  ['guidedtour_steps', G3, 'Pașii turului ghidat'],
  ['schemaorg', G3, 'Date structurate pentru Google (schema.org)'],
  ['redirect_links', G3, 'Redirecționări de adrese vechi (util la mutarea de pe HelloWeb)'],
  ['action_logs', G3, 'Jurnalul acțiunilor din administrare (cine ce a modificat)'],
  ['action_logs_extensions', G3, 'Ce extensii sunt urmărite în jurnal'],
  ['action_logs_users', G3, 'Cine primește notificări din jurnal'],
  ['action_log_config', G3, 'Configurarea jurnalului'],

  ['banners', G4, 'Bannere publicitare'],
  ['banner_clients', G4, 'Clienții bannerelor'],
  ['banner_tracks', G4, 'Statistici bannere'],
  ['contact_details', G4, 'Componenta Contacte (pagina Contact e un articol)'],
  ['newsfeeds', G4, 'Fluxuri de știri RSS externe'],
  ['content_rating', G4, 'Notele (stelele) date articolelor'],
  ['finder_filters', G4, 'Căutare inteligentă: filtre'],
  ['finder_links', G4, 'Căutare inteligentă: indexul paginilor'],
  ['finder_links_terms', G4, 'Căutare inteligentă: cuvinte pe pagini'],
  ['finder_logging', G4, 'Căutare inteligentă: ce au căutat vizitatorii'],
  ['finder_taxonomy', G4, 'Căutare inteligentă: clasificări'],
  ['finder_taxonomy_map', G4, 'Căutare inteligentă: clasificări pe pagini'],
  ['finder_terms', G4, 'Căutare inteligentă: cuvintele indexate'],
  ['finder_terms_common', G4, 'Căutare inteligentă: cuvinte ignorate'],
  ['finder_tokens', G4, 'Căutare inteligentă: tabel temporar'],
  ['finder_tokens_aggregate', G4, 'Căutare inteligentă: tabel temporar'],
  ['finder_types', G4, 'Căutare inteligentă: tipuri de conținut'],
];

const q = (s) => "'" + s.replace(/'/g, "''") + "'";
const luni = "'ianuarie','februarie','martie','aprilie','mai','iunie','iulie','august','septembrie','octombrie','noiembrie','decembrie'";
const stareArticol = (col) => `CASE ${col} WHEN 1 THEN 'publicat' WHEN 0 THEN 'nepublicat' WHEN 2 THEN 'arhivat' WHEN -2 THEN 'în coș' END`;

const sql = `-- Vederi (VIEW) cu nume romanesti peste baza de date Joomla
--
-- Joomla are nevoie de toate cele ${tables.length} de tabele ale lui (sv49d_...), chiar si de
-- cele goale, altfel nu mai merg administrarea si actualizarile. De aceea nu am
-- sters tabele, ci am facut cateva vederi, adica tabele doar de citit, care arata
-- continutul muzeului mai clar. In phpMyAdmin apar primele in lista:
--
--   muzeu_evenimente    evenimentele, cu datele in ora Romaniei si starea lor
--   muzeu_pagini        paginile site-ului (articolele care nu sunt evenimente)
--   muzeu_meniu         meniul, in ordine, si ce deschide fiecare element
--   muzeu_categorii     categoriile si cate articole are fiecare
--   muzeu_ghid_tabele   ce contine fiecare tabel Joomla, pe grupe, si cate randuri are
--
-- Vederile nu schimba nimic in Joomla si fisierul se poate rula de mai multe ori.
-- Daca nu mai sunt dorite:
--   DROP VIEW muzeu_evenimente, muzeu_pagini, muzeu_meniu, muzeu_categorii, muzeu_ghid_tabele;
--   DROP FUNCTION muzeu_ora_ro;
--
-- Rulare: C:\\xampp\\mysql\\bin\\mysql.exe -uroot joomla_db < baza_de_date_vederi.sql
-- sau in phpMyAdmin: baza joomla_db, Import, fisierul asta.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Joomla salveaza orele in UTC, functia le trece in ora Romaniei
-- (ora de vara e intre ultima duminica din martie si ultima duminica din octombrie)
DROP FUNCTION IF EXISTS muzeu_ora_ro;
DELIMITER //
CREATE FUNCTION muzeu_ora_ro(utc DATETIME) RETURNS DATETIME DETERMINISTIC
COMMENT 'Ora României dintr-o dată salvată de Joomla în UTC'
BEGIN
  DECLARE vara_inceput DATETIME;
  DECLARE vara_sfarsit DATETIME;
  IF utc IS NULL THEN
    RETURN NULL;
  END IF;
  SET vara_inceput = TIMESTAMP(DATE(CONCAT(YEAR(utc), '-03-31')) - INTERVAL (DAYOFWEEK(CONCAT(YEAR(utc), '-03-31')) - 1) DAY, '01:00:00');
  SET vara_sfarsit = TIMESTAMP(DATE(CONCAT(YEAR(utc), '-10-31')) - INTERVAL (DAYOFWEEK(CONCAT(YEAR(utc), '-10-31')) - 1) DAY, '01:00:00');
  RETURN utc + INTERVAL IF(utc >= vara_inceput AND utc < vara_sfarsit, 3, 2) HOUR;
END//
DELIMITER ;

-- evenimentele, cu aceleasi reguli ca pe site (partials/events-data.php):
--   urmeaza          incepe mai tarziu
--   in desfasurare   a inceput si nu s-a terminat
--   realizat         s-a terminat
CREATE OR REPLACE VIEW muzeu_evenimente AS
SELECT
  a.id,
  a.title AS titlu,
  IF(vm.value = '1',
     CONCAT(ELT(MONTH(muzeu_ora_ro(vs.value)), ${luni}), ' ', YEAR(muzeu_ora_ro(vs.value))),
     CONCAT(DAY(muzeu_ora_ro(vs.value)), ' ', ELT(MONTH(muzeu_ora_ro(vs.value)), ${luni}), ' ', YEAR(muzeu_ora_ro(vs.value)))
  ) AS data_pe_site,
  muzeu_ora_ro(vs.value) AS inceput,
  muzeu_ora_ro(ve.value) AS sfarsit,
  IF(vm.value = '1', 'da', 'nu') AS doar_luna,
  CASE
    WHEN muzeu_ora_ro(vs.value) > muzeu_ora_ro(UTC_TIMESTAMP()) THEN 'urmează'
    WHEN COALESCE(
           muzeu_ora_ro(ve.value),
           IF(vm.value = '1', LAST_DAY(muzeu_ora_ro(vs.value)), DATE(muzeu_ora_ro(vs.value))) + INTERVAL '23:59:59' HOUR_SECOND
         ) >= muzeu_ora_ro(UTC_TIMESTAMP()) THEN 'în desfășurare'
    ELSE 'realizat'
  END AS stare,
  NULLIF(JSON_UNQUOTE(JSON_EXTRACT(a.images, '$.image_intro')), '') AS poza,
  (CHAR_LENGTH(a.fulltext) - CHAR_LENGTH(REPLACE(a.fulltext, '<figure', ''))) DIV 7 AS poze_in_galerie,
  ${stareArticol('a.state')} AS publicare,
  a.alias
FROM ${P}content a
JOIN ${P}categories c ON c.id = a.catid AND c.extension = 'com_content' AND c.alias = 'evenimente'
LEFT JOIN ${P}fields fs ON fs.context = 'com_content.article' AND fs.name = 'data-eveniment'
LEFT JOIN ${P}fields_values vs ON vs.field_id = fs.id AND vs.item_id = a.id
LEFT JOIN ${P}fields fe ON fe.context = 'com_content.article' AND fe.name = 'data-sfarsit'
LEFT JOIN ${P}fields_values ve ON ve.field_id = fe.id AND ve.item_id = a.id
LEFT JOIN ${P}fields fm ON fm.context = 'com_content.article' AND fm.name = 'doar-luna'
LEFT JOIN ${P}fields_values vm ON vm.field_id = fm.id AND vm.item_id = a.id
ORDER BY inceput DESC;

-- paginile site-ului, adica articolele care nu sunt evenimente
CREATE OR REPLACE VIEW muzeu_pagini AS
SELECT
  a.id,
  a.title AS titlu,
  c.title AS categorie,
  ${stareArticol('a.state')} AS publicare,
  (SELECT GROUP_CONCAT(m.title ORDER BY m.lft SEPARATOR ', ')
     FROM ${P}menu m
    WHERE m.client_id = 0 AND m.published = 1
      AND m.link = CONCAT('index.php?option=com_content&view=article&id=', a.id)) AS in_meniu_ca,
  muzeu_ora_ro(a.modified) AS modificat_ultima_data,
  a.alias
FROM ${P}content a
JOIN ${P}categories c ON c.id = a.catid
WHERE c.alias <> 'evenimente'
ORDER BY a.state = -2, c.title, a.title;

-- meniul site-ului in ordinea de pe ecran (subelementele au o liniuta in fata)
CREATE OR REPLACE VIEW muzeu_meniu AS
SELECT
  m.id,
  CONCAT(REPEAT('— ', m.level - 1), m.title) AS element,
  NULLIF(p.title, 'Menu_Item_Root') AS sub,
  CASE
    WHEN m.home = 1 THEN 'pagina de start'
    WHEN m.type = 'url' THEN CONCAT('link: ', m.link)
    WHEN m.type = 'heading' THEN 'doar titlu de grup'
    WHEN m.type = 'alias' THEN 'trimite la alt element de meniu'
    WHEN m.link LIKE '%view=article%' THEN CONCAT('pagina „', (SELECT a.title FROM ${P}content a WHERE a.id = SUBSTRING_INDEX(m.link, 'id=', -1)), '”')
    WHEN m.link LIKE '%view=category%' THEN CONCAT('lista din categoria „', (SELECT c.title FROM ${P}categories c WHERE c.id = SUBSTRING_INDEX(m.link, 'id=', -1)), '”')
    ELSE m.link
  END AS ce_deschide,
  m.path AS adresa,
  IF(m.published = 1, 'da', 'nu') AS publicat
FROM ${P}menu m
LEFT JOIN ${P}menu p ON p.id = m.parent_id
WHERE m.menutype = 'mainmenu' AND m.client_id = 0 AND m.published >= 0
ORDER BY m.lft;

-- categoriile articolelor
CREATE OR REPLACE VIEW muzeu_categorii AS
SELECT
  c.id,
  c.title AS titlu,
  (SELECT COUNT(*) FROM ${P}content a WHERE a.catid = c.id AND a.state = 1) AS articole_publicate,
  (SELECT COUNT(*) FROM ${P}content a WHERE a.catid = c.id AND a.state = -2) AS in_cos,
  IF(c.published = 1, 'da', 'nu') AS publicata,
  c.alias
FROM ${P}categories c
WHERE c.extension = 'com_content'
ORDER BY c.lft;

-- ghidul tabelelor: ce contine fiecare tabel Joomla si cate randuri are
CREATE OR REPLACE VIEW muzeu_ghid_tabele AS
${tables.map(([t, g, d], i) =>
  `${i ? 'UNION ALL ' : ''}SELECT ${q(g)} AS grupa, ${q(P + t)} AS tabel, ${q(d)} AS ce_contine, (SELECT COUNT(*) FROM ${P}${t}) AS randuri`
).join('\n')};
`;

fs.writeFileSync(process.argv[2], sql, 'utf8');
console.log('scris: ' + process.argv[2] + ' (' + tables.length + ' tabele în ghid)');
