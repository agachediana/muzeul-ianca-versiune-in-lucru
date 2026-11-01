/*
  Face copia statica a site-ului din XAMPP, pentru GitHub Pages (folderul docs/).

  - ia toate paginile pornind de la Acasa si le salveaza ca fisiere .html
    (index.html, despre-istoric.html, eveniment-....html)
  - pune css-ul, js-ul, pozele si fonturile in docs/assets si rescrie linkurile relativ
  - inlocuieste adresele localhost cu adresa de pe GitHub
  - goleste codurile csrf.token ale sesiunilor Joomla
  - pune pe fiecare pagina noindex (sa nu apara in Google) si o banda
    "Versiune de lucru, nu este site-ul oficial"

  Dupa ea se ruleaza ascunde-poze.ps1, pentru pozele cu copii.

  Folosire:
    node unelte/copie-statica.js http://localhost/joomla/ github-repo/docs https://agachediana.github.io/muzeul-ianca/
*/
const fs = require('fs');
const path = require('path');
const [site, out, live] = process.argv.slice(2);
if (!site || !out || !live) {
  console.log('folosire: node copie-statica.js <adresa-site> <folder-docs> <adresa-github>');
  process.exit(1);
}
const origin = new URL(site).origin;
const root = new URL(site).pathname; // /joomla/
fs.rmSync(out, { recursive: true, force: true });
fs.mkdirSync(path.join(out, 'assets'), { recursive: true });

// numele fisierului pentru o pagina, ex. index.php/despre/istoric -> despre-istoric.html
const pageFile = (p) => {
  if (p === '' || p === 'index.php') return 'index.html';
  p = p.replace(/^index\.php\//, '').replace(/\/$/, '');
  p = p.replace(/^stiri-si-evenimente\/evenimente\/(.+)/, 'eveniment-$1');
  return p.replace(/\//g, '-') + '.html';
};
const isPage = (p) => p === '' || p === 'index.php' || /^index\.php\/[^?]*$/.test(p);

// fisierele (css, js, poze) ajung toate in assets/, cu nume unice
const assets = new Map();
const used = new Set();
const assetName = (p) => {
  if (assets.has(p)) return assets.get(p);
  let name = path.basename(p);
  for (let i = 2; used.has(name); i++) name = path.basename(p, path.extname(p)) + '-' + i + path.extname(p);
  used.add(name);
  assets.set(p, name);
  return name;
};

// banda de sus si eticheta noindex
const robots = '<meta name="robots" content="noindex, nofollow">';
const banda = '<div role="note" style="background:#3d0b14;color:#efe5d3;text-align:center;padding:8px 16px;'
  + 'font:600 .78rem/1.4 \'Source Sans 3\',system-ui,sans-serif;letter-spacing:.08em">'
  + 'Versiune de lucru · nu este site-ul oficial al Muzeului Ianca</div>';

// curata o pagina: adresele locale, codurile de sesiune, noindex si banda
const curata = (html) => {
  const liveEsc = live.replace(/\//g, '\\/');
  const liveRoot = new URL(live).pathname.replace(/\/$/, '').replace(/\//g, '\\/');
  html = html.split('http:\\/\\/localhost' + root.replace(/\//g, '\\/')).join(liveEsc);
  html = html.split('http://localhost' + root).join(live);
  html = html.split('"' + root.replace(/\/$/, '').replace(/\//g, '\\/') + '"').join('"' + liveRoot + '"');
  html = html.replace(/"csrf\.token":"[0-9a-f]+"/g, '"csrf.token":""');
  html = html.replace(/<meta name="robots"[^>]*>\s*/g, '');
  html = html.replace(/<head>/, '<head>\n\t' + robots);
  html = html.replace(/(<body[^>]*>)/, '$1\n' + banda);
  return html;
};

const queue = [''];
const seen = new Set(queue);
const pages = [];

(async () => {
  while (queue.length) {
    const p = queue.shift();
    const res = await fetch(origin + root + p);
    if (!res.ok) { console.log('sarit ' + res.status + ' ' + p); continue; }
    let html = await res.text();

    // linkurile spre site (href, src) devin relative
    html = html.replace(/(href|src|poster|content)="(\/[^"]*)"/g, (m, attr, url) => {
      if (!url.startsWith(root)) return m;
      const [full, hash = ''] = url.split('#');
      const rel = full.slice(root.length).split('?')[0];
      const query = full.includes('?') ? full.slice(full.indexOf('?')) : '';
      if (isPage(rel) && !query.includes('option=')) {
        if (!seen.has(rel)) { seen.add(rel); queue.push(rel); }
        return `${attr}="${pageFile(rel)}${hash ? '#' + hash : ''}"`;
      }
      if (/\.(css|js|png|jpe?g|gif|webp|svg|ico|woff2?|ttf|mp4|pdf)$/i.test(rel)) {
        return `${attr}="assets/${assetName(rel)}"`;
      }
      return m;
    });
    const file = pageFile(p);
    fs.writeFileSync(path.join(out, file), curata(html));
    if (!pages.includes(file)) pages.push(file);
  }

  // fisierele noi gasite in css (fonturile) se adauga in assets si sunt luate tot in bucla asta
  let bytes = 0;
  for (const [p, name] of assets) {
    const res = await fetch(origin + root + p);
    if (!res.ok) { console.log('lipsa ' + res.status + ' ' + p); continue; }
    let buf = Buffer.from(await res.arrayBuffer());
    if (p.endsWith('.css')) {
      const css = buf.toString('utf8').replace(/url\(\s*(['"]?)([^'")]+)\1\s*\)/g, (m, q, url) => {
        // url(%23n) e o trimitere din interiorul unui svg pus direct in css, nu un fisier
        if (/^(data:|https?:|\/\/|#|%23)/.test(url)) return m;
        const rel = path.posix.normalize(path.posix.join(path.posix.dirname(p), url.split(/[?#]/)[0]));
        return `url(${q}${assetName(rel)}${q})`;
      });
      buf = Buffer.from(css, 'utf8');
    }
    fs.writeFileSync(path.join(out, 'assets', name), buf);
    bytes += buf.length;
  }
  // fara Jekyll, GitHub Pages serveste fisierele exact cum sunt
  fs.writeFileSync(path.join(out, '.nojekyll'), '');
  console.log(`${pages.length} pagini, ${assets.size} fisiere (${(bytes / 1048576).toFixed(1)} MB)`);
})();
