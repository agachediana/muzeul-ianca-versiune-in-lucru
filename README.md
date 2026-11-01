# Muzeul Ianca

Noul site al Muzeului Ianca (Câmpia Brăilei), refăcut în **Joomla 6** cu o temă proprie: paletă burgundy și crem, banner cu fotografie alb-negru care se colorează în lumina unui felinar, pagină de evenimente cu arhivă pe ani și bară de accesibilitate.

**Versiunea de lucru poate fi văzută aici:** https://agachediana.github.io/muzeul-ianca/

Adresa de mai sus arată o copie statică a site-ului (folderul `docs/`), generată din Joomla. Arată și funcționează la fel pentru vizitatori: felinarul, animațiile, filtrele de evenimente și pozele mărite. Nu are însă partea de administrare. Site-ul complet rulează în Joomla, cu PHP și MySQL.

Copia este o versiune de lucru, nu site-ul oficial al muzeului: fiecare pagină are o bandă care spune asta și eticheta `noindex`, ca să nu apară în Google. Pozele în care se văd copii sunt blurate temporar, până există acordul părinților.

## Ce conține repository-ul

| Folder / fișier | Ce este |
| --- | --- |
| `muzeulianca/` | Tema Joomla (sursa): PHP, CSS, JavaScript, fonturi |
| `muzeulianca.zip` | Tema gata de instalat în Joomla (Sistem → Instalare → Extensii) |
| `docs/` | Copia statică a site-ului, publicată cu GitHub Pages |
| `baza_de_date_vederi.sql` | Vederi cu nume românești care fac baza de date ușor de citit |
| `limba/en-GB.override.ini` | Textele Joomla afișate vizitatorilor, traduse în română |
| `iconite/` | Logo-ul și felinarul ca PNG |
| `unelte/` | Scripturile pentru poze, fonturi și copia statică |

Nu sunt incluse, intenționat: `configuration.php` din Joomla (parola bazei de date și cheia secretă), exportul bazei de date (conține contul de administrator) și copiile de siguranță.

## Unelte folosite

Joomla 6.2, XAMPP (Apache, PHP 8.5, MariaDB), phpMyAdmin, PHP, HTML, CSS, JavaScript, GSAP (ScrollTrigger, Flip), fonturi Google găzduite local (Playfair Display, Bodoni Moda, Source Sans 3), Node.js, PowerShell.

---
## Documentație

Template pentru noul site al Muzeului Ianca: paletă burgundy și crem, hero alb-negru care devine color sub cursor, animații discrete la scroll și bară de accesibilitate.

## Conținut

| Cale | Ce este |
| --- | --- |
| `muzeulianca/` | Template-ul Joomla (sursa) |
| `muzeulianca.zip` | Arhiva gata de instalat în Joomla |
| `limba/en-GB.override.ini` | Textele Joomla afișate vizitatorilor, traduse în română (vezi „Limba site-ului”) |
| `baza_de_date_vederi.sql` | Vederile cu nume românești care fac baza de date ușor de citit (vezi „Baza de date”) |
| `iconite/` | Logo-ul și felinarul ca PNG (vezi „Iconițe”) |
| `unelte/` | Scripturile folosite la poze, fonturi și copia statică (vezi „Unelte”) |

## Instalare

1. Joomla 6 → **Sistem → Instalare → Extensii** → încărcați `muzeulianca.zip`.
2. **Sistem → Stiluri template (Site)** → setați „muzeulianca – Default” ca implicit.
3. În stilul template-ului, tabul **Muzeu** și **Pagina de acasă**: verificați programul, adresa, telefonul și alegeți fotografia hero.
4. Tabul **Date de identificare**: denumirea oficială, sediul, e-mailul, numărul din Registrul asociațiilor și fundațiilor și CIF-ul. Apar în subsol doar cele completate.

## Imagini de încărcat (Media → `images/muzeu/`)

| Fișier | Folosit pentru |
| --- | --- |
| `hero-muzeu.jpg` | Fotografia principală (color; template-ul o face alb-negru) |
| `prezentare.jpg` | Secțiunea „Scurtă prezentare” |
| `sectiuni/arta.jpg`, `sectiuni/biserica.jpg`, `sectiuni/casa.jpg`, `sectiuni/unelte.jpg` | Cardurile celor patru corpuri |

Toate pozele din `images/muzeu/` au cel mult 1600 px lățime și sub 200 KB (micșorate cu `unelte/micsoreaza-poze.ps1`; originalele sunt în `C:\xampp\backup_imagini`). Tema scrie lățimea și înălțimea fiecărei poze în pagină (`partials/img-size.php`), așa că pagina nu mai sare cât se încarcă pozele.

## Meniul principal (Meniuri → Main Menu)

Submeniurile se deschid în jos când treci cu mouse-ul (și la click; pe telefon, ca acordeon). Al treilea nivel (Galerii foto → Etnografie, Științe, Artă) apare ca listă sub părintele lui.

```
Acasă
Despre ▾              Asociația „Muzeul” Ianca · Amplasament · Comunicate de presă · Istoric muzeu
Muzeul în imagini ▾   Tur virtual
                      Galerii foto → Etnografie · Științe · Artă
Povești ale locului   (listă de articole din categoria „Articole istorice”)
Știri și evenimente ▾ Știri · Evenimente
Contact
```

- **Tur virtual** este o pagină a site-ului, nu mai trimite la macheta de pe site-ul vechi. Butonul „Tur virtual” de pe Acasă duce tot aici (câmpul „Buton 2: link” din setările temei e gol).
- **Amplasament, Tur virtual și Galerii foto** sunt pagini noi și goale: au doar titlul. Celelalte pagini din meniu sunt și ele goale, iar conținutul se adaugă din **Conținut → Articole**.
- **Corpurile muzeului, Oamenii muzeului și Susține muzeul** nu mai apar în meniu. Sunt mutate în meniul ascuns **Alte pagini (ascuns)**, ca linkurile din subsol și cardurile de pe Acasă să meargă în continuare.

Elementele vechi de meniu și articolele scoase la simplificare sunt în Trash (Coșul de gunoi) și se pot restaura. Copia meniului dinainte de această schimbare: `C:\xampp\mysql\backup_meniu_2026-10-24.sql`.

## Iconițe

Felinarul și logo-ul Mi sunt exportate ca PNG în folderul `iconite/` din proiect și în Joomla, în `images/muzeu/iconite/` (le găsești în **Conținut → Media**):

| Fișier | Ce este |
| --- | --- |
| `logo-mi.png`, `logo-mi-crem.png` | Logo-ul, fundal transparent (burgundy pentru fundal deschis, crem pentru fundal închis) |
| `felinar.png` | Felinarul, fundal transparent, cu aura caldă |
| `felinar-icon-512.png` | Felinarul pe un pătrat burgundy rotunjit |
| `logo-icon-512.png`, `icon-192.png`, `apple-touch-icon.png`, `favicon-48.png`, `favicon-32.png` | Logo-ul pe crem, pentru iconița site-ului (filă, telefon) |

Iconițele site-ului (favicon) sunt și în temă, în `media/images/`, și se pun automat în pagină din `index.php`.

## Evenimente

Toate cele 30 de evenimente de pe site-ul HelloWeb sunt importate, cu fotografiile lor (în `images/muzeu/evenimente/<an>/`). Fiecare eveniment e un articol în categoria **Evenimente** (alias `evenimente`) și are trei câmpuri în tabul **Fields** al articolului:

| Câmp | Ce înseamnă |
| --- | --- |
| **Data evenimentului** (`data-eveniment`) | Când începe. Dacă lipsește, se folosește data publicării. |
| **Data de sfârșit** (`data-sfarsit`) | Opțional, pentru evenimente de mai multe zile. Gol = ține o zi. |
| **Afișează doar luna și anul** (`doar-luna`) | Da = ziua nu e cunoscută; pe site apare de exemplu „noiembrie 2019”. |

Fotografia principală e „Imaginea intro” a articolului. Celelalte fotografii stau în textul complet, într-un bloc `<div class="ev-galerie">`, și se deschid mărite la click.

**Cum se decide starea unui eveniment** (aceeași regulă peste tot, în `partials/events-data.php`):
- **Urmează**: începe în viitor;
- **În desfășurare**: a început și nu s-a terminat încă;
- **Realizat**: s-a încheiat.

**Pagina Evenimente** (meniul Știri și evenimente → Evenimente, layout `evenimente` al temei):
1. **În prim-plan**: următorul eveniment, cu numărătoare inversă; dacă nu e niciunul anunțat, cel în desfășurare; altfel, cel mai recent realizat.
2. **Filtre**: Toate / Viitoare / În desfășurare / Realizate, cu numărul de evenimente. Grupele se rearanjează animat (GSAP Flip).
3. **Urmează** și **În desfășurare**: carduri; dacă nu există, un mesaj scurt.
4. **Realizate**: arhivă pe ani. În stânga e un index al anilor, legat printr-un fir „cusut” care se umple pe măsură ce cobori. Fotografiile sunt alb-negru și prind culoare la trecerea mouse-ului (pe telefon, când apar pe ecran). Fiecare eveniment are un număr de registru, ca eticheta unui obiect de muzeu (N° 01 este cel mai vechi).

**Pagina unui eveniment** (layout `eveniment`): titlul, starea, data și locul, fotografia mare, textul și trecerea la evenimentul anterior sau următor. Dacă evenimentul are mai multe fotografii (blocul `ev-galerie`), ele apar lângă text sub „Fotografii de la eveniment” și se deschid mărite (săgeți, tastatură, glisare pe telefon). Fără alte fotografii, textul stă centrat sub fotografia mare. Pentru evenimentele care urmează apare și numărătoarea inversă sub titlu.

Pe nicio pagină nu apar autorul („Written by”), categoria, data publicării sau numărul de vizualizări.

Pe **Acasă** apar primele evenimente: cele care urmează (sau sunt în desfășurare), apoi ultimele realizate.

## Paginile obișnuite

Paginile din meniu (Istoric, Contact, Etnografie...) folosesc layout-ul `html/com_content/article/default.php` al temei: titlul apare o singură dată, sus, fără autor, date sau butoanele „Previous article / Next article”. O pagină cu textul gol arată doar titlul.

**Politica de confidențialitate** (meniul ascuns, linkul e în subsol) are deocamdată doar structura: cele 9 părți cerute și întrebările la care trebuie să răspundă fiecare. Textul se scrie separat și îl aprobă muzeul înainte de publicare.

## Titlul și descrierea paginilor

- Titlul din filă: „Numele paginii - Muzeul Ianca” (**Sistem → Configurare globală → Site → Numele site-ului în titlu: După**). Acasă are „Muzeul Ianca · Câmpia Brăilei · din 1999”.
- Descrierea pentru Google: cea scrisă în articol (tabul **Publicare → Meta descriere**). Dacă lipsește, tema pune începutul textului evenimentului sau al paginii, iar pagina Evenimente are o descriere proprie. Descrierea generală a site-ului e în **Configurare globală → Meta descriere**.

## Subsol

- Programul, adresa, telefonul și e-mailul (din setările temei).
- Linkurile publice: ANPC, **Soluționarea alternativă a litigiilor (ANPC SAL)** și politica de confidențialitate. Pictograma oficială SAL apare singură când fișierul ei e pus în `media/templates/site/muzeulianca/images/anpc-sal.png` (se descarcă de pe anpc.ro).
- Datele de identificare ale asociației (tabul **Date de identificare** al temei).

## Harta de pe Acasă

Harta Google nu se mai încarcă singură. În locul ei e un panou cu adresa și două butoane: **Încarcă harta** (abia atunci se cere harta de la Google) și **Deschide în Google Maps**. Așa, la deschiderea site-ului nu pleacă nicio cerere spre alte servere.

## Limba site-ului

Joomla are instalată doar limba engleză. Textele Joomla care pot apărea vizitatorilor („There are no articles…”, „Read more”, paginarea, pagina de eroare) sunt traduse în `limba/en-GB.override.ini`, copiat în `language/overrides/` din Joomla. Tema declară paginile în limba română (`lang="ro-RO"`), ca cititoarele de ecran să citească textul corect.

Varianta completă este pachetul de limbă română: **Sistem → Instalare → Limbi → Română**, apoi setată implicită pentru site. Atunci fișierul de traduceri nu mai e necesar.

## Baza de date

Joomla folosește 76 de tabele (`sv49d_...`) și are nevoie de toate, chiar și de cele goale. Fără ele nu mai funcționează administrarea, actualizările sau verificarea bazei. De aceea nu s-a șters niciun tabel.

Ca baza să fie ușor de înțeles, fișierul `baza_de_date_vederi.sql` adaugă cinci **vederi** (tabele „virtuale”, doar pentru citit) cu nume românești. În phpMyAdmin apar primele în lista bazei `joomla_db`:

| Vedere | Ce arată |
| --- | --- |
| `muzeu_evenimente` | Evenimentele: data așa cum apare pe site, început și sfârșit în ora României, starea (urmează / în desfășurare / realizat), poza, câte fotografii are în galerie |
| `muzeu_pagini` | Paginile site-ului, categoria lor, dacă sunt publicate sau în coș și sub ce nume apar în meniu |
| `muzeu_meniu` | Meniul, în ordinea de pe site, cu ce deschide fiecare element |
| `muzeu_categorii` | Categoriile, cu numărul de articole publicate și din coș |
| `muzeu_ghid_tabele` | Toate cele 76 de tabele Joomla, pe patru grupe, cu explicația și numărul de rânduri |

Cele patru grupe de tabele:
1. **Conținutul muzeului** (16): articole, categorii, câmpurile evenimentelor, meniu, module, setările temei.
2. **Utilizatori și drepturi** (15): conturi, grupuri, permisiuni, sesiuni.
3. **Funcționarea Joomla** (28): extensii, actualizări, jurnalul acțiunilor, fluxul de publicare.
4. **Funcții Joomla nefolosite pe site** (17): bannere, contacte, fluxuri RSS, căutare inteligentă. Rămân pentru că fac parte din Joomla.

Vederile nu modifică nimic. Pentru a le recrea (de exemplu pe serverul final), rulați `baza_de_date_vederi.sql` în phpMyAdmin (Import) sau:

```
C:\xampp\mysql\bin\mysql.exe -uroot joomla_db < baza_de_date_vederi.sql
```

**Curățenie de făcut din administrare** (șterge date definitiv, deci se face manual):
- **Conținut → Articole → Filtre → Stare: Coș de gunoi → Golește coșul**: cele 12 pagini vechi, scoase la simplificarea meniului.
- **Meniuri → Main Menu → Stare: Coș de gunoi → Golește coșul**: elementele vechi de meniu.
- Opțional: **Utilizatori → Jurnal de acțiuni → Șterge tot** și **Componente → Căutare inteligentă → Golește indexul**.

**MySQL în XAMPP**: opriți MySQL din panoul XAMPP (Stop) înainte de a închide laptopul. O oprire bruscă poate strica tabelele Aria și atunci Joomla dă eroarea 500.

## Poziții de module

Site-ul nu mai afișează firul de navigare („Acasă / ...”): logo-ul și meniul duc oricum acasă, iar pagina unui eveniment are „Toate evenimentele”.

`menu`, `sidebar`, `footer-1..3` și, pentru a înlocui o secțiune de pe Acasă: `home-anunturi`, `home-prezentare`, `home-sectiuni`, `home-evenimente`, `home-istoric`, `home-vizita`.

Textele fixe ale secțiunilor și reperele cronologice se editează în `partials/home.php`.

## Fonturi

- **Titlul mare** din hero și numele din subsol folosesc **Playfair Display Bold Italic**, gratuit și pentru uz comercial. Titlul apare ca și cum ar fi scris de la stânga la dreapta.
- Titlurile de secțiuni folosesc **Bodoni Moda**, iar textul **Source Sans 3**.
- Fonturile (de pe Google Fonts, cu litere românești) și GSAP sunt găzduite în temă: `media/fonts/`, `media/css/fonts.css` și `media/js/vendor/`. Se descarcă din nou cu `unelte/aduce-fonturi.js`.
- **Bigilla** și **Milky Walky** din colecția Awwwards sunt gratuite doar pentru uz personal. Pentru un site public e nevoie de licență plătită de la autori.

## Accesibilitate și animații

Bara din antet oferă: mărire și micșorare text (trei trepte în fiecare sens), contrast ridicat și linkuri subliniate. Alegerile se rețin în browser.

Când în sistem e pornită „mișcarea redusă” (în Windows: **Setări → Accesibilitate → Efecte vizuale → Efecte de animație**), dispar paralaxa și scenele legate de derulare. Rămân lumina felinarului, aparițiile prin estompare și filtrele animate.

**Felinarul din hero** se vede tot timpul. La început se plimbă singur peste fotografie, cu lumina în flacără. Pe calculator urmează mouse-ul și se leagănă, iar fiecare click mărește puțin lumina. Pe telefon merge acolo unde atingi și luminează puțin zona, apoi își reia plimbarea. Lumina e mutată doar prin `transform`, deci browserul nu redesenează fotografia la fiecare cadru.

Site-ul are și „Sari la conținut” și navigare completă din tastatură. Textul secundar (`--ink-muted`) are contrast de cel puțin 4,5:1 pe toate fundalurile.

## Unelte

Scripturile din `unelte/`, fiecare cu modul de folosire scris la început:

| Fișier | Ce face |
| --- | --- |
| `micsoreaza-poze.ps1` | Micșorează pozele site-ului la cel mult 1600 px și sub 200 KB, cu copie a originalelor |
| `aduce-fonturi.js` | Descarcă fonturile și GSAP în folderul `media` al temei |
| `copie-statica.js` | Face copia statică a site-ului pentru GitHub Pages (`noindex` și bandă „Versiune de lucru”) |
| `ascunde-poze.ps1` | Blurează în copia statică pozele din `poze-ascunse.txt` și scrie pe ele „Poză blurată temporar” |
| `poze-ascunse.txt` | Pozele cu copii, ascunse până există acordul părinților |

## Încă de făcut

- **Modulul AI**: butonul „Întreabă muzeul” are deocamdată doar interfața, fără răspunsuri reale.
- Paginile interioare (galerii cu descrieri, tur virtual, articole) folosesc stilurile de bază și trebuie lucrate separat.
- De la muzeu: e-mailul, sediul, numărul din registru și CIF-ul (tabul Date de identificare), prețurile biletelor și acordul pentru pozele cu copii.
- Pictograma ANPC SAL (vezi „Subsol”) și textul politicii de confidențialitate.
