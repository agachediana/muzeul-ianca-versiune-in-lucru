/*
  Importul evenimentelor, pasul 1 din 5.
  Scoate evenimentele din pagina Evenimente a site-ului vechi (muzeulianca.ro),
  salvata ca fisier HTML: titlul, luna si anul, ziua daca apare in text, si textul.

  Folosire:
    node extrage-evenimente.js evenimente.html date/evenimente-pagina-veche.json
*/
const fs = require('fs');
const html = fs.readFileSync(process.argv[2], 'utf8');
const NAMED = { Acirc: 'Â', acirc: 'â', Icirc: 'Î', icirc: 'î', Abreve: 'Ă', abreve: 'ă', bdquo: '„', ldquo: '“', rdquo: '”', laquo: '«', raquo: '»', ndash: '–', mdash: '—', hellip: '…', rsquo: '’', lsquo: '‘' };
const decode = s => s.replace(/&([A-Za-z]+);/g, (m, n) => NAMED[n] || m).replace(/&nbsp;/g, ' ').replace(/&amp;/g, '&').replace(/&quot;/g, '"').replace(/&#39;|&#x27;/g, "'")
  .replace(/&#(\d+);/g, (_, n) => String.fromCharCode(+n)).replace(/&#x([0-9a-f]+);/gi, (_, n) => String.fromCharCode(parseInt(n, 16)));
const body = html.replace(/<script[\s\S]*?<\/script>|<style[\s\S]*?<\/style>/gi, '')
  .replace(/<(br|\/p|\/div|\/h\d|\/li|\/span)[^>]*>/gi, '\n').replace(/<[^>]+>/g, '');
const lines = decode(body).split('\n').map(s => s.replace(/\s+/g, ' ').trim()).filter(s => s.length > 2);
const MONTHS = ['IANUARIE', 'FEBRUARIE', 'MARTIE', 'APRILIE', 'MAI', 'IUNIE', 'IULIE', 'AUGUST', 'SEPTEMBRIE', 'OCTOMBRIE', 'NOIEMBRIE', 'DECEMBRIE'];
const re = new RegExp('^(' + MONTHS.join('|') + ')\\s+(\\d{4})$');
const idx = lines.map((l, i) => re.test(l) ? i : -1).filter(i => i >= 0);
const out = idx.map((i, n) => {
  const [, mon, year] = lines[i].match(re);
  const end = idx[n + 1] ? idx[n + 1] - 1 : Math.min(i + 6, lines.length);
  const text = lines.slice(i + 1, end).join(' ').replace(/\s+([,.!])/g, '$1');
  const dm = text.match(/\b(\d{1,2})[\s.]+(ianuarie|februarie|martie|aprilie|mai|iunie|iulie|august|septembrie|octombrie|noiembrie|decembrie)\b/i);
  const month = MONTHS.indexOf(mon) + 1;
  const day = dm && MONTHS.indexOf(dm[2].toUpperCase()) + 1 === month ? +dm[1] : null;
  return { title: lines[i - 1], month, year: +year, day, text };
});
fs.writeFileSync(process.argv[3], JSON.stringify(out, null, 1));
console.log(out.length + ' evenimente');
out.slice(0, 14).forEach(e => console.log(`${e.year}-${String(e.month).padStart(2, '0')}-${e.day || '??'} | ${e.title.slice(0, 70)} | ${e.text.length} car.`));
