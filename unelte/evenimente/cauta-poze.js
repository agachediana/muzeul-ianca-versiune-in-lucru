/*
  Importul evenimentelor, pasul 2 din 5.
  Pentru fiecare poza din pagina veche incearca mai multe adrese (serverele HelloWeb
  si muzeulianca.ro) si o pastreaza pe prima care raspunde cu o imagine.
  Lista pozelor pe evenimente (date/poze-pe-eveniment.json) e luata din pagina veche.

  Folosire:
    node cauta-poze.js ./date/poze-pe-eveniment.json date/poze-gasite.json
*/
const list = require(process.argv[2]);
const names = [...new Set(list.flat())];
const variants = n => {
  const m = n.match(/^(.*?)(-\d+w)?(\.[a-z]+)$/i);
  const base = m[1], ext = m[3];
  return [
    `https://irp.cdn-website.com/1c319cab/dms3rep/multi/${base}${ext}`,
    `https://irp.cdn-website.com/1c319cab/dms3rep/multi/opt/${base}-1920w${ext}`,
    `https://lirp.cdn-website.com/1c319cab/dms3rep/multi/opt/${base}-1920w${ext}`,
    `https://lirp.cdn-website.com/1c319cab/dms3rep/multi/opt/${base}-640w${ext}`,
    `https://irp.cdn-website.com/c85bdb24/dms3rep/multi/opt/${base}-1920w${ext}`,
    `https://www.muzeulianca.ro/Resources/images/${base}-640w${ext}`,
    `https://www.muzeulianca.ro/Resources/images/${base}-1920w${ext}`
  ];
};
(async () => {
  const found = {};
  for (const n of names) {
    found[n] = null;
    for (const u of variants(n)) {
      try {
        const r = await fetch(u, { headers: { 'User-Agent': 'Mozilla/5.0', Referer: 'https://www.muzeulianca.ro/' } });
        const type = r.headers.get('content-type') || '';
        if (r.ok && type.startsWith('image/')) { found[n] = u; break; }
      } catch (e) {}
    }
    console.log((found[n] ? 'OK  ' : 'LIPS') + ' ' + n + (found[n] ? '  <- ' + found[n].split('/').slice(2, 4).join('/') + '/…/' + found[n].split('/').pop() : ''));
  }
  require('fs').writeFileSync(process.argv[3], JSON.stringify(found, null, 1));
})();
