-- Vederi (VIEW) cu nume romanesti peste baza de date Joomla
--
-- Joomla are nevoie de toate cele 76 de tabele ale lui (sv49d_...), chiar si de
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
-- Rulare: C:\xampp\mysql\bin\mysql.exe -uroot joomla_db < baza_de_date_vederi.sql
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
     CONCAT(ELT(MONTH(muzeu_ora_ro(vs.value)), 'ianuarie','februarie','martie','aprilie','mai','iunie','iulie','august','septembrie','octombrie','noiembrie','decembrie'), ' ', YEAR(muzeu_ora_ro(vs.value))),
     CONCAT(DAY(muzeu_ora_ro(vs.value)), ' ', ELT(MONTH(muzeu_ora_ro(vs.value)), 'ianuarie','februarie','martie','aprilie','mai','iunie','iulie','august','septembrie','octombrie','noiembrie','decembrie'), ' ', YEAR(muzeu_ora_ro(vs.value)))
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
  CASE a.state WHEN 1 THEN 'publicat' WHEN 0 THEN 'nepublicat' WHEN 2 THEN 'arhivat' WHEN -2 THEN 'în coș' END AS publicare,
  a.alias
FROM sv49d_content a
JOIN sv49d_categories c ON c.id = a.catid AND c.extension = 'com_content' AND c.alias = 'evenimente'
LEFT JOIN sv49d_fields fs ON fs.context = 'com_content.article' AND fs.name = 'data-eveniment'
LEFT JOIN sv49d_fields_values vs ON vs.field_id = fs.id AND vs.item_id = a.id
LEFT JOIN sv49d_fields fe ON fe.context = 'com_content.article' AND fe.name = 'data-sfarsit'
LEFT JOIN sv49d_fields_values ve ON ve.field_id = fe.id AND ve.item_id = a.id
LEFT JOIN sv49d_fields fm ON fm.context = 'com_content.article' AND fm.name = 'doar-luna'
LEFT JOIN sv49d_fields_values vm ON vm.field_id = fm.id AND vm.item_id = a.id
ORDER BY inceput DESC;

-- paginile site-ului, adica articolele care nu sunt evenimente
CREATE OR REPLACE VIEW muzeu_pagini AS
SELECT
  a.id,
  a.title AS titlu,
  c.title AS categorie,
  CASE a.state WHEN 1 THEN 'publicat' WHEN 0 THEN 'nepublicat' WHEN 2 THEN 'arhivat' WHEN -2 THEN 'în coș' END AS publicare,
  (SELECT GROUP_CONCAT(m.title ORDER BY m.lft SEPARATOR ', ')
     FROM sv49d_menu m
    WHERE m.client_id = 0 AND m.published = 1
      AND m.link = CONCAT('index.php?option=com_content&view=article&id=', a.id)) AS in_meniu_ca,
  muzeu_ora_ro(a.modified) AS modificat_ultima_data,
  a.alias
FROM sv49d_content a
JOIN sv49d_categories c ON c.id = a.catid
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
    WHEN m.link LIKE '%view=article%' THEN CONCAT('pagina „', (SELECT a.title FROM sv49d_content a WHERE a.id = SUBSTRING_INDEX(m.link, 'id=', -1)), '”')
    WHEN m.link LIKE '%view=category%' THEN CONCAT('lista din categoria „', (SELECT c.title FROM sv49d_categories c WHERE c.id = SUBSTRING_INDEX(m.link, 'id=', -1)), '”')
    ELSE m.link
  END AS ce_deschide,
  m.path AS adresa,
  IF(m.published = 1, 'da', 'nu') AS publicat
FROM sv49d_menu m
LEFT JOIN sv49d_menu p ON p.id = m.parent_id
WHERE m.menutype = 'mainmenu' AND m.client_id = 0 AND m.published >= 0
ORDER BY m.lft;

-- categoriile articolelor
CREATE OR REPLACE VIEW muzeu_categorii AS
SELECT
  c.id,
  c.title AS titlu,
  (SELECT COUNT(*) FROM sv49d_content a WHERE a.catid = c.id AND a.state = 1) AS articole_publicate,
  (SELECT COUNT(*) FROM sv49d_content a WHERE a.catid = c.id AND a.state = -2) AS in_cos,
  IF(c.published = 1, 'da', 'nu') AS publicata,
  c.alias
FROM sv49d_categories c
WHERE c.extension = 'com_content'
ORDER BY c.lft;

-- ghidul tabelelor: ce contine fiecare tabel Joomla si cate randuri are
CREATE OR REPLACE VIEW muzeu_ghid_tabele AS
SELECT '1. Conținutul muzeului' AS grupa, 'sv49d_content' AS tabel, 'Articolele: paginile site-ului (Istoric, Contact...) și toate evenimentele' AS ce_contine, (SELECT COUNT(*) FROM sv49d_content) AS randuri
UNION ALL SELECT '1. Conținutul muzeului' AS grupa, 'sv49d_categories' AS tabel, 'Categoriile articolelor: Evenimente, Anunțuri, Știri, Articole istorice' AS ce_contine, (SELECT COUNT(*) FROM sv49d_categories) AS randuri
UNION ALL SELECT '1. Conținutul muzeului' AS grupa, 'sv49d_fields' AS tabel, 'Câmpurile în plus ale evenimentelor: data de început, data de sfârșit, „doar luna”' AS ce_contine, (SELECT COUNT(*) FROM sv49d_fields) AS randuri
UNION ALL SELECT '1. Conținutul muzeului' AS grupa, 'sv49d_fields_values' AS tabel, 'Valorile acelor câmpuri pentru fiecare eveniment (datele, în ora UTC)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_fields_values) AS randuri
UNION ALL SELECT '1. Conținutul muzeului' AS grupa, 'sv49d_fields_categories' AS tabel, 'La ce categorii se aplică fiecare câmp (gol = la toate)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_fields_categories) AS randuri
UNION ALL SELECT '1. Conținutul muzeului' AS grupa, 'sv49d_fields_groups' AS tabel, 'Grupuri de câmpuri (nefolosit)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_fields_groups) AS randuri
UNION ALL SELECT '1. Conținutul muzeului' AS grupa, 'sv49d_menu' AS tabel, 'Meniul site-ului (și meniul administrării)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_menu) AS randuri
UNION ALL SELECT '1. Conținutul muzeului' AS grupa, 'sv49d_menu_types' AS tabel, 'Lista meniurilor (ex. „Main Menu”)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_menu_types) AS randuri
UNION ALL SELECT '1. Conținutul muzeului' AS grupa, 'sv49d_modules' AS tabel, 'Modulele: meniul, firul de navigare și blocurile de pe pagini' AS ce_contine, (SELECT COUNT(*) FROM sv49d_modules) AS randuri
UNION ALL SELECT '1. Conținutul muzeului' AS grupa, 'sv49d_modules_menu' AS tabel, 'Pe ce pagini apare fiecare modul' AS ce_contine, (SELECT COUNT(*) FROM sv49d_modules_menu) AS randuri
UNION ALL SELECT '1. Conținutul muzeului' AS grupa, 'sv49d_template_styles' AS tabel, 'Setările temei „muzeulianca” (program, adresă, telefon, poza din hero...)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_template_styles) AS randuri
UNION ALL SELECT '1. Conținutul muzeului' AS grupa, 'sv49d_content_frontpage' AS tabel, 'Articolele marcate „Recomandate” (nefolosit)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_content_frontpage) AS randuri
UNION ALL SELECT '1. Conținutul muzeului' AS grupa, 'sv49d_history' AS tabel, 'Versiunile anterioare ale articolelor (pentru „Restaurează versiunea”)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_history) AS randuri
UNION ALL SELECT '1. Conținutul muzeului' AS grupa, 'sv49d_contentitem_tag_map' AS tabel, 'Legătura articol–etichetă (nefolosit)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_contentitem_tag_map) AS randuri
UNION ALL SELECT '1. Conținutul muzeului' AS grupa, 'sv49d_tags' AS tabel, 'Etichetele (nefolosit)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_tags) AS randuri
UNION ALL SELECT '1. Conținutul muzeului' AS grupa, 'sv49d_workflow_associations' AS tabel, 'Etapa de publicare a fiecărui articol (cerută de Joomla)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_workflow_associations) AS randuri
UNION ALL SELECT '2. Utilizatori și drepturi' AS grupa, 'sv49d_users' AS tabel, 'Conturile de administrare' AS ce_contine, (SELECT COUNT(*) FROM sv49d_users) AS randuri
UNION ALL SELECT '2. Utilizatori și drepturi' AS grupa, 'sv49d_usergroups' AS tabel, 'Grupurile de utilizatori (Public, Administrator...)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_usergroups) AS randuri
UNION ALL SELECT '2. Utilizatori și drepturi' AS grupa, 'sv49d_user_usergroup_map' AS tabel, 'Ce utilizator în ce grup este' AS ce_contine, (SELECT COUNT(*) FROM sv49d_user_usergroup_map) AS randuri
UNION ALL SELECT '2. Utilizatori și drepturi' AS grupa, 'sv49d_viewlevels' AS tabel, 'Nivelurile de acces (Public, Registered...)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_viewlevels) AS randuri
UNION ALL SELECT '2. Utilizatori și drepturi' AS grupa, 'sv49d_assets' AS tabel, 'Permisiunile pentru fiecare articol, categorie și extensie' AS ce_contine, (SELECT COUNT(*) FROM sv49d_assets) AS randuri
UNION ALL SELECT '2. Utilizatori și drepturi' AS grupa, 'sv49d_session' AS tabel, 'Sesiunile deschise (se golește singur)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_session) AS randuri
UNION ALL SELECT '2. Utilizatori și drepturi' AS grupa, 'sv49d_user_keys' AS tabel, 'Chei pentru „Ține-mă minte” la autentificare' AS ce_contine, (SELECT COUNT(*) FROM sv49d_user_keys) AS randuri
UNION ALL SELECT '2. Utilizatori și drepturi' AS grupa, 'sv49d_user_mfa' AS tabel, 'Autentificare în doi pași' AS ce_contine, (SELECT COUNT(*) FROM sv49d_user_mfa) AS randuri
UNION ALL SELECT '2. Utilizatori și drepturi' AS grupa, 'sv49d_user_notes' AS tabel, 'Notițe despre utilizatori' AS ce_contine, (SELECT COUNT(*) FROM sv49d_user_notes) AS randuri
UNION ALL SELECT '2. Utilizatori și drepturi' AS grupa, 'sv49d_user_profiles' AS tabel, 'Date de profil ale utilizatorilor' AS ce_contine, (SELECT COUNT(*) FROM sv49d_user_profiles) AS randuri
UNION ALL SELECT '2. Utilizatori și drepturi' AS grupa, 'sv49d_webauthn_credentials' AS tabel, 'Autentificare cu cheie de securitate / amprentă' AS ce_contine, (SELECT COUNT(*) FROM sv49d_webauthn_credentials) AS randuri
UNION ALL SELECT '2. Utilizatori și drepturi' AS grupa, 'sv49d_messages' AS tabel, 'Mesajele interne dintre administratori' AS ce_contine, (SELECT COUNT(*) FROM sv49d_messages) AS randuri
UNION ALL SELECT '2. Utilizatori și drepturi' AS grupa, 'sv49d_messages_cfg' AS tabel, 'Setările mesajelor interne' AS ce_contine, (SELECT COUNT(*) FROM sv49d_messages_cfg) AS randuri
UNION ALL SELECT '2. Utilizatori și drepturi' AS grupa, 'sv49d_privacy_consents' AS tabel, 'Consimțăminte GDPR' AS ce_contine, (SELECT COUNT(*) FROM sv49d_privacy_consents) AS randuri
UNION ALL SELECT '2. Utilizatori și drepturi' AS grupa, 'sv49d_privacy_requests' AS tabel, 'Cereri GDPR (export / ștergere date)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_privacy_requests) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_extensions' AS tabel, 'Toate extensiile instalate: componente, module, pluginuri, teme, limbi' AS ce_contine, (SELECT COUNT(*) FROM sv49d_extensions) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_schemas' AS tabel, 'Versiunea structurii bazei de date (pentru actualizări)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_schemas) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_updates' AS tabel, 'Actualizările disponibile găsite' AS ce_contine, (SELECT COUNT(*) FROM sv49d_updates) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_update_sites' AS tabel, 'De unde se descarcă actualizările' AS ce_contine, (SELECT COUNT(*) FROM sv49d_update_sites) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_update_sites_extensions' AS tabel, 'Ce extensie se actualizează de pe ce site' AS ce_contine, (SELECT COUNT(*) FROM sv49d_update_sites_extensions) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_tuf_metadata' AS tabel, 'Verificarea semnăturii actualizărilor Joomla' AS ce_contine, (SELECT COUNT(*) FROM sv49d_tuf_metadata) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_postinstall_messages' AS tabel, 'Mesajele de după instalare / actualizare' AS ce_contine, (SELECT COUNT(*) FROM sv49d_postinstall_messages) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_languages' AS tabel, 'Limbile de conținut (pentru site-uri în mai multe limbi)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_languages) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_overrider' AS tabel, 'Textele de interfață modificate' AS ce_contine, (SELECT COUNT(*) FROM sv49d_overrider) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_template_overrides' AS tabel, 'Evidența fișierelor de temă suprascrise' AS ce_contine, (SELECT COUNT(*) FROM sv49d_template_overrides) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_mail_templates' AS tabel, 'Șabloanele e-mailurilor trimise de Joomla' AS ce_contine, (SELECT COUNT(*) FROM sv49d_mail_templates) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_scheduler_tasks' AS tabel, 'Sarcinile programate (ex. verificarea actualizărilor)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_scheduler_tasks) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_scheduler_logs' AS tabel, 'Jurnalul sarcinilor programate' AS ce_contine, (SELECT COUNT(*) FROM sv49d_scheduler_logs) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_content_types' AS tabel, 'Tipurile de conținut cunoscute de Joomla' AS ce_contine, (SELECT COUNT(*) FROM sv49d_content_types) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_ucm_base' AS tabel, 'Index comun de conținut (folosit de etichete)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_ucm_base) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_ucm_content' AS tabel, 'Index comun de conținut (folosit de etichete)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_ucm_content) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_workflows' AS tabel, 'Fluxul de publicare (unul singur, cel implicit)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_workflows) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_workflow_stages' AS tabel, 'Etapele fluxului de publicare' AS ce_contine, (SELECT COUNT(*) FROM sv49d_workflow_stages) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_workflow_transitions' AS tabel, 'Trecerile dintre etape (Publică, Arhivează, Coș...)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_workflow_transitions) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_associations' AS tabel, 'Legăturile dintre traduceri (nefolosit, site într-o limbă)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_associations) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_guidedtours' AS tabel, 'Turul ghidat din administrare' AS ce_contine, (SELECT COUNT(*) FROM sv49d_guidedtours) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_guidedtour_steps' AS tabel, 'Pașii turului ghidat' AS ce_contine, (SELECT COUNT(*) FROM sv49d_guidedtour_steps) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_schemaorg' AS tabel, 'Date structurate pentru Google (schema.org)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_schemaorg) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_redirect_links' AS tabel, 'Redirecționări de adrese vechi (util la mutarea de pe HelloWeb)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_redirect_links) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_action_logs' AS tabel, 'Jurnalul acțiunilor din administrare (cine ce a modificat)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_action_logs) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_action_logs_extensions' AS tabel, 'Ce extensii sunt urmărite în jurnal' AS ce_contine, (SELECT COUNT(*) FROM sv49d_action_logs_extensions) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_action_logs_users' AS tabel, 'Cine primește notificări din jurnal' AS ce_contine, (SELECT COUNT(*) FROM sv49d_action_logs_users) AS randuri
UNION ALL SELECT '3. Funcționarea Joomla (necesare)' AS grupa, 'sv49d_action_log_config' AS tabel, 'Configurarea jurnalului' AS ce_contine, (SELECT COUNT(*) FROM sv49d_action_log_config) AS randuri
UNION ALL SELECT '4. Funcții Joomla nefolosite pe site' AS grupa, 'sv49d_banners' AS tabel, 'Bannere publicitare' AS ce_contine, (SELECT COUNT(*) FROM sv49d_banners) AS randuri
UNION ALL SELECT '4. Funcții Joomla nefolosite pe site' AS grupa, 'sv49d_banner_clients' AS tabel, 'Clienții bannerelor' AS ce_contine, (SELECT COUNT(*) FROM sv49d_banner_clients) AS randuri
UNION ALL SELECT '4. Funcții Joomla nefolosite pe site' AS grupa, 'sv49d_banner_tracks' AS tabel, 'Statistici bannere' AS ce_contine, (SELECT COUNT(*) FROM sv49d_banner_tracks) AS randuri
UNION ALL SELECT '4. Funcții Joomla nefolosite pe site' AS grupa, 'sv49d_contact_details' AS tabel, 'Componenta Contacte (pagina Contact e un articol)' AS ce_contine, (SELECT COUNT(*) FROM sv49d_contact_details) AS randuri
UNION ALL SELECT '4. Funcții Joomla nefolosite pe site' AS grupa, 'sv49d_newsfeeds' AS tabel, 'Fluxuri de știri RSS externe' AS ce_contine, (SELECT COUNT(*) FROM sv49d_newsfeeds) AS randuri
UNION ALL SELECT '4. Funcții Joomla nefolosite pe site' AS grupa, 'sv49d_content_rating' AS tabel, 'Notele (stelele) date articolelor' AS ce_contine, (SELECT COUNT(*) FROM sv49d_content_rating) AS randuri
UNION ALL SELECT '4. Funcții Joomla nefolosite pe site' AS grupa, 'sv49d_finder_filters' AS tabel, 'Căutare inteligentă: filtre' AS ce_contine, (SELECT COUNT(*) FROM sv49d_finder_filters) AS randuri
UNION ALL SELECT '4. Funcții Joomla nefolosite pe site' AS grupa, 'sv49d_finder_links' AS tabel, 'Căutare inteligentă: indexul paginilor' AS ce_contine, (SELECT COUNT(*) FROM sv49d_finder_links) AS randuri
UNION ALL SELECT '4. Funcții Joomla nefolosite pe site' AS grupa, 'sv49d_finder_links_terms' AS tabel, 'Căutare inteligentă: cuvinte pe pagini' AS ce_contine, (SELECT COUNT(*) FROM sv49d_finder_links_terms) AS randuri
UNION ALL SELECT '4. Funcții Joomla nefolosite pe site' AS grupa, 'sv49d_finder_logging' AS tabel, 'Căutare inteligentă: ce au căutat vizitatorii' AS ce_contine, (SELECT COUNT(*) FROM sv49d_finder_logging) AS randuri
UNION ALL SELECT '4. Funcții Joomla nefolosite pe site' AS grupa, 'sv49d_finder_taxonomy' AS tabel, 'Căutare inteligentă: clasificări' AS ce_contine, (SELECT COUNT(*) FROM sv49d_finder_taxonomy) AS randuri
UNION ALL SELECT '4. Funcții Joomla nefolosite pe site' AS grupa, 'sv49d_finder_taxonomy_map' AS tabel, 'Căutare inteligentă: clasificări pe pagini' AS ce_contine, (SELECT COUNT(*) FROM sv49d_finder_taxonomy_map) AS randuri
UNION ALL SELECT '4. Funcții Joomla nefolosite pe site' AS grupa, 'sv49d_finder_terms' AS tabel, 'Căutare inteligentă: cuvintele indexate' AS ce_contine, (SELECT COUNT(*) FROM sv49d_finder_terms) AS randuri
UNION ALL SELECT '4. Funcții Joomla nefolosite pe site' AS grupa, 'sv49d_finder_terms_common' AS tabel, 'Căutare inteligentă: cuvinte ignorate' AS ce_contine, (SELECT COUNT(*) FROM sv49d_finder_terms_common) AS randuri
UNION ALL SELECT '4. Funcții Joomla nefolosite pe site' AS grupa, 'sv49d_finder_tokens' AS tabel, 'Căutare inteligentă: tabel temporar' AS ce_contine, (SELECT COUNT(*) FROM sv49d_finder_tokens) AS randuri
UNION ALL SELECT '4. Funcții Joomla nefolosite pe site' AS grupa, 'sv49d_finder_tokens_aggregate' AS tabel, 'Căutare inteligentă: tabel temporar' AS ce_contine, (SELECT COUNT(*) FROM sv49d_finder_tokens_aggregate) AS randuri
UNION ALL SELECT '4. Funcții Joomla nefolosite pe site' AS grupa, 'sv49d_finder_types' AS tabel, 'Căutare inteligentă: tipuri de conținut' AS ce_contine, (SELECT COUNT(*) FROM sv49d_finder_types) AS randuri;
