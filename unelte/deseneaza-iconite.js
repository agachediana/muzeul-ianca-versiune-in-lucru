/*
  Face iconitele PNG (logo-ul si felinarul) din desenele SVG ale temei.
  Scrie doua fisiere intr-un folder din XAMPP: render.html, care deseneaza fiecare
  iconita pe un canvas, si save.php, care o salveaza ca PNG. Deschizi render.html
  in browser (http://localhost/...), astepti titlul "gata", apoi stergi folderul.

  Folosire:
    node deseneaza-iconite.js muzeulianca C:\xampp\htdocs\iconite-temp iconite
*/
const fs = require('fs');
const path = require('path');
const [tpl, out, pngDir] = process.argv.slice(2);
fs.mkdirSync(out, { recursive: true });

// desenele din tema: logo-ul din partials/logo.php, felinarul din partials/home.php
const logoInner = fs.readFileSync(path.join(tpl, 'partials/logo.php'), 'utf8')
  .match(/<svg[^>]*>([\s\S]*)<\/svg>/)[1].replace(/<!--[\s\S]*?-->/g, '');
const lanternInner = fs.readFileSync(path.join(tpl, 'partials/home.php'), 'utf8')
  .match(/<div class="hero__lantern"[^>]*>\s*<svg[^>]*>([\s\S]*?)<\/svg>/)[1];

// logo-ul: contur cu culoarea data; la iconitele mici fara liniile fine si cu contur mai gros
const logo = (color, small) => {
  const inner = small ? logoInner.replace(/<(path|circle|rect)[^>]*stroke-width="[^"]*"[^>]*\/>/g, '') : logoInner;
  return { vb: '0 10 106 86', w: 106, h: 86,
    body: `<g fill="none" stroke="${color}" stroke-width="${small ? 5 : 2.4}" stroke-linejoin="round" stroke-linecap="round">${inner}</g>` };
};
// felinarul, cu o aura calda in jur
const lantern = () => ({ vb: '-4 -4 42 66', w: 42, h: 66,
  defs: '<filter id="g" x="-40%" y="-40%" width="180%" height="180%"><feDropShadow dx="0" dy="0" stdDeviation="1.6" flood-color="#ffb45a" flood-opacity=".65"/></filter>',
  body: `<g filter="url(#g)">${lanternInner}</g>` });

// pune desenul centrat pe o panza w x h, cu fundal optional rotunjit
const compose = (w, h, art, { bg = null, radius = 0, fill = 0.9 } = {}) => {
  const scale = Math.min((w * fill) / art.w, (h * fill) / art.h);
  const aw = art.w * scale, ah = art.h * scale;
  return `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}">`
    + (bg ? `<rect width="${w}" height="${h}" rx="${radius}" fill="${bg}"/>` : '')
    + `<svg x="${(w - aw) / 2}" y="${(h - ah) / 2}" width="${aw}" height="${ah}" viewBox="${art.vb}" overflow="visible">`
    + `<defs>${art.defs || ''}</defs>${art.body}</svg></svg>`;
};

const burgundy = '#6b1525', crem = '#f6f0e4', inchis = '#3d0b14';
const icons = [
  ['logo-mi', compose(1024, 832, logo(burgundy), { fill: 0.96 })],
  ['logo-mi-crem', compose(1024, 832, logo('#efe5d3'), { fill: 0.96 })],
  ['felinar', compose(620, 1024, lantern(), { fill: 1 })],
  ['felinar-icon-512', compose(512, 512, lantern(), { bg: inchis, radius: 96, fill: 0.82 })],
  ['logo-icon-512', compose(512, 512, logo(burgundy), { bg: crem, radius: 96, fill: 0.74 })],
  ['icon-192', compose(192, 192, logo(burgundy), { bg: crem, radius: 36, fill: 0.74 })],
  ['apple-touch-icon', compose(180, 180, logo(burgundy), { bg: crem, fill: 0.72 })],
  ['favicon-48', compose(48, 48, logo(burgundy, true), { bg: crem, radius: 10, fill: 0.86 })],
  ['favicon-32', compose(32, 32, logo(burgundy, true), { bg: crem, radius: 6, fill: 0.88 })],
];

const html = `<!doctype html><meta charset="utf-8"><title>lucrez</title><body>
<script>
const icons = ${JSON.stringify(icons)};
(async () => {
  let ok = 0;
  for (const [name, svg] of icons) {
    const img = new Image();
    img.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
    await img.decode();
    const c = document.createElement('canvas');
    c.width = img.naturalWidth; c.height = img.naturalHeight;
    c.getContext('2d').drawImage(img, 0, 0);
    const blob = await new Promise((r) => c.toBlob(r, 'image/png'));
    const res = await fetch('save.php?name=' + name, { method: 'POST', body: blob });
    if (res.ok) ok++;
    document.body.insertAdjacentHTML('beforeend', '<p>' + name + ' ' + c.width + 'x' + c.height + ' ' + res.status + '</p>');
  }
  document.title = 'gata ' + ok + '/' + icons.length;
})();
</script>`;
fs.writeFileSync(path.join(out, 'render.html'), html);

const php = `<?php
// salveaza o iconita primita de la render.html (folder temporar, se sterge dupa)
$name = preg_replace('/[^a-z0-9-]/', '', $_GET['name'] ?? '');
if ($name === '' || $_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') { http_response_code(400); exit; }
file_put_contents(${JSON.stringify(pngDir.replace(/\\/g, '/'))} . '/' . $name . '.png', file_get_contents('php://input'));
echo 'ok';
`;
fs.writeFileSync(path.join(out, 'save.php'), php);
console.log('pagini scrise in ' + out + ' (' + icons.length + ' iconite)');
