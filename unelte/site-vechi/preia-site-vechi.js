/*
  Ia de pe site-ul vechi (muzeulianca.ro, facut cu HelloWeb) ce a ramas de adus:
    - albumele din Galerie foto (pozele, pe albume)
    - catalogul selectiv: obiectele de la Etnografie si Arte (numele tuturor
      obiectelor si pozele celor care au poza) si scanarile 3D
    - textele paginilor: Despre muzeu, Repere cronologice, Corpurile muzeului,
      Contact, Dati un leu, Acreditare
  Pozele se descarca in folderul dat, iar tot ce s-a gasit se scrie in date.json.
  Dupa el se ruleaza micsoreaza-poze.ps1 pe pozele descarcate si importa-site-vechi.php.

  Folosire:
    node unelte/site-vechi/preia-site-vechi.js C:\xampp\htdocs\joomla unelte/site-vechi/date.json
*/
const fs = require('fs');
const path = require('path');
const [joomla, outJson] = process.argv.slice(2);
if (!joomla || !outJson) {
  console.log('folosire: node preia-site-vechi.js <cale-joomla> <date.json>');
  process.exit(1);
}
const SITE = 'https://www.muzeulianca.ro/';
const HEAD = { 'User-Agent': 'Mozilla/5.0', Referer: SITE };

// literele romanesti scrise ca entitati HTML
const NUME = {
  nbsp: ' ', amp: '&', quot: '"', apos: "'", lt: '<', gt: '>', acirc: 'â', Acirc: 'Â', icirc: 'î', Icirc: 'Î', abreve: 'ă', Abreve: 'Ă',
  bdquo: '„', ldquo: '“', rdquo: '”', lsquo: '‘', rsquo: '’', laquo: '«', raquo: '»', ndash: '–', mdash: '—', hellip: '…', deg: '°',
  scedil: 'ş', Scedil: 'Ş', tcedil: 'ţ', Tcedil: 'Ţ', eacute: 'é', ocirc: 'ô', middot: '·', copy: '©'
};
const text = (s) => s.replace(/<br\s*\/?>/gi, '\n').replace(/<[^>]+>/g, ' ')
  .replace(/&#(\d+);/g, (_, n) => String.fromCharCode(+n)).replace(/&#x([0-9a-f]+);/gi, (_, n) => String.fromCharCode(parseInt(n, 16)))
  .replace(/&([a-zA-Z]+);/g, (m, n) => NUME[n] ?? m)
  // literele vechi cu sedila devin cele corecte, cu virgula
  .replace(/ş/g, 'ș').replace(/Ş/g, 'Ș').replace(/ţ/g, 'ț').replace(/Ţ/g, 'Ț')
  .replace(/[ \t]+/g, ' ').replace(/\s*\n\s*/g, '\n').trim();
const slug = (s) => s.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase()
  .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 50).replace(/-$/, '');

const pagina = async (p) => (await fetch(SITE + p, { headers: HEAD })).text();
const fara = (html) => html.replace(/<script[\s\S]*?<\/script>|<style[\s\S]*?<\/style>/g, '');

// pozele din galeriile HelloWeb: legatura cu poza mare, descrierea si titlul de sub ea
const pozeGalerie = (html) => {
  const out = [];
  const re = /<a ([^>]*data-image-url="[^"]*"[^>]*)>\s*<img([^>]*)>[\s\S]*?<h3[^>]*caption-title[^>]*>([\s\S]*?)<\/h3>\s*<div[^>]*caption-text[^>]*>([\s\S]*?)<\/div>/g;
  const attr = (s, n) => (new RegExp(n + '="([^"]*)"').exec(s) || [])[1] || '';
  let m;
  while ((m = re.exec(html))) {
    out.push({ at: m.index, url: attr(m[1], 'data-image-url'), pagina: attr(m[1], 'data-target-page-alias'), alt: text(attr(m[2], 'alt')), titlu: text(m[3]), descriere: text(m[4]) });
  }
  return out;
};

// textul unei pagini: titlurile si paragrafele din continut, fara meniu si subsol
const SUBSOL = /^(Asociația|Istoricul|Amplasamentul|Colecția|Oamenii Muzeului|Susține Muzeul Ianca|Scrie-ne|Proiect cultural co-finanțat|Proiectul nu reprezintă|Politica de Confidentialitate|A\.N\.P\. ?C\.|Acreditarea acordată|Toate drepturile|Nu este permisă|© )/;
const continut = (html) => {
  const out = [];
  const re = /<(h[1-6])[^>]*>([\s\S]*?)<\/\1>|<(p|li)[^>]*>([\s\S]*?)<\/\3>|<iframe[^>]+src="([^"]+)"|(?:data-src|src)="([^"]*\/Resources\/images\/[^"]+?\.(?:jpe?g|png))"/g;
  let m;
  const body = fara(html).replace(/<nav[\s\S]*?<\/nav>/g, '');
  while ((m = re.exec(body))) {
    if (m[1]) { const t = text(m[2]); if (t && !SUBSOL.test(t)) out.push({ tip: 'h', t }); }
    else if (m[3]) { const t = text(m[4]); if (t.length > 1 && !SUBSOL.test(t) && !/^PROGRAM:/.test(t)) out.push({ tip: m[3], t }); }
    else if (m[5]) out.push({ tip: 'iframe', t: m[5] });
    else if (m[6] && !/Logo|HELLO-LOGO/i.test(m[6])) out.push({ tip: 'img', t: m[6].replace(/-\d+w(\.\w+)$/, '$1') });
  }
  return out;
};

const descarca = async (url, unde) => {
  if (fs.existsSync(unde)) return true;
  const mare = url.replace(/(\.\w+)$/, '-1920w$1');
  for (const u of [SITE.replace(/\/$/, '') + mare, SITE.replace(/\/$/, '') + url]) {
    try {
      const r = await fetch(u, { headers: HEAD });
      if (r.ok && (r.headers.get('content-type') || '').startsWith('image/')) {
        fs.mkdirSync(path.dirname(unde), { recursive: true });
        fs.writeFileSync(unde, Buffer.from(await r.arrayBuffer()));
        return true;
      }
    } catch (e) {}
  }
  console.log('LIPSA ' + url);
  return false;
};

(async () => {
  const date = { albume: [], catalog: {}, scanari: [], pagini: {} };
  const img = path.join(joomla, 'images', 'muzeu');

  // albumele din Galerie foto: titlul albumului e titlul mare de deasupra pozelor
  const gal = fara(await pagina('galerie-foto'));
  const titluri = [];
  gal.replace(/<(h[1-4])[^>]*>([\s\S]*?)<\/\1>/g, (m, tag, t, at) => { if (!/caption-title/.test(m)) titluri.push({ at, t: text(t).replace(/\s*(de )?la\s*Muzeul Ianca\s*$/i, '').replace(/\s+/g, ' ').trim() }); return m; });
  const poze = pozeGalerie(gal).filter((p) => p.url);
  for (const p of poze) {
    const t = titluri.filter((x) => x.at < p.at).pop();
    if (!t || /^GALERIE FOTO$/i.test(t.t)) continue;
    let a = date.albume.find((x) => x.titlu === t.t);
    if (!a) { a = { titlu: t.t, alias: slug(t.t), poze: [] }; date.albume.push(a); }
    if (a.poze.some((x) => x.url === p.url)) continue;
    a.poze.push({ url: p.url, alt: p.titlu || p.alt || '' });
  }
  for (const a of date.albume) {
    for (const [i, p] of a.poze.entries()) {
      p.fisier = `albume/${a.alias}/${String(i + 1).padStart(2, '0')}.jpg`;
      await descarca(p.url, path.join(img, p.fisier));
    }
    console.log(`album: ${a.titlu} (${a.poze.length} poze)`);
  }

  // catalogul selectiv: obiectele de pe fiecare pagina de patrimoniu
  const cat = fara(await pagina('catalog-selectiv'));
  const obiecte = pozeGalerie(cat.replace(/data-image-url="(?=[^"]*")/g, 'data-image-url="'));
  for (const o of obiecte) {
    const sect = /etnografie/.test(o.pagina) ? 'etnografie' : /arte/.test(o.pagina) ? 'arta' : /scanari/.test(o.pagina) ? 'scanari' : '';
    if (!sect) continue;
    const titlu = o.titlu || o.alt;
    if (sect === 'scanari') { date.scanari.push({ titlu, url: o.url }); continue; }
    (date.catalog[sect] = date.catalog[sect] || []).push({ titlu, descriere: o.descriere, url: o.url });
  }
  for (const [sect, lista] of Object.entries(date.catalog)) {
    let n = 0;
    for (const o of lista) {
      if (!o.url) continue;
      n++;
      o.fisier = `galerii/${sect}/${String(n).padStart(2, '0')}-${slug(o.titlu)}.jpg`;
      await descarca(o.url, path.join(img, o.fisier));
    }
    console.log(`catalog ${sect}: ${lista.length} obiecte, ${n} cu poza`);
  }
  for (const [i, s] of date.scanari.entries()) {
    s.fisier = `scanari-3d/${String(i + 1).padStart(2, '0')}-${slug(s.titlu)}.jpg`;
    if (s.url) await descarca(s.url, path.join(img, s.fisier));
  }
  const sk = /sketchfab\.com\/playlists\/embed\?collection=(\w+)/.exec(cat);
  date.sketchfab = sk ? 'https://sketchfab.com/playlists/embed?collection=' + sk[1] + '&autostart=0' : '';
  console.log(`scanari 3D: ${date.scanari.length}`);

  // textele paginilor
  for (const p of ['despre-muzeu', 'repere-cronologice', 'corpurile-muzeului', 'contact', 'dati-un-leu', 'acreditare-muzeul-ianca']) {
    date.pagini[p] = continut(await pagina(p));
    console.log(`pagina ${p}: ${date.pagini[p].length} bucati`);
  }

  fs.writeFileSync(outJson, JSON.stringify(date, null, 1));
  console.log('scris: ' + outJson);
})();
