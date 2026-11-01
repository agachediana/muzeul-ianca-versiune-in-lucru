/*
  Importul evenimentelor, pasul 3 din 5.
  Face lista finala a celor 30 de evenimente: titlul corectat, data de inceput si
  de sfarsit, daca se stie doar luna, textul curatat si pozele cu numele lor noi.

  Folosire:
    node pregateste-evenimente.js date/evenimente-pagina-veche.json date/poze-pe-eveniment.json date/poze-gasite.json date/evenimente.json
*/
const fs = require('fs');
const [, , evFile, imgFile, foundFile, outFile] = process.argv;
const ev = JSON.parse(fs.readFileSync(evFile, 'utf8'));
const imgs = JSON.parse(fs.readFileSync(imgFile, 'utf8'));
const found = JSON.parse(fs.readFileSync(foundFile, 'utf8'));

// numarul din pagina veche -> [titlu, inceput, sfarsit, doar luna, id-ul articolului daca exista deja]
const plan = {
  0:  ['24 Ianuarie la Muzeul Ianca: Unirea Principatelor Române', '2020-01-24', '', 0, 23],
  1:  ['Moș Crăciun la Muzeul Ianca', '2019-12-01', '', 1],
  2:  ['„Muzeul Ianca – promotor al tradiției locale”', '2019-11-01', '', 1],
  3:  ['Muzeul Ianca la ceas aniversar', '2019-11-29', '', 0, 24],
  4:  ['Lansare de carte la Muzeul Ianca', '2019-11-15', '', 0, 25],
  5:  ['ÎPS Casian în vizită la Muzeul Ianca', '2019-10-31', '', 0, 26],
  6:  ['Școala de vară: „Tradiția la Ianca – lucruri vechi la vremuri noi”', '2019-08-19', '2019-08-31', 0, 27],
  7:  ['„Vremuri de altădată” la aniversarea a 20 de ani a muzeului', '2019-08-09', '', 0, 28],
  8:  ['Ziua Eroilor alături de Muzeul Ianca', '2019-06-01', '', 1],
  9:  ['Noaptea Muzeelor la Muzeul Ianca', '2019-05-01', '', 1],
  10: ['Stejari pentru eroi', '2019-03-29', '', 0, 29],
  11: ['Mica Unire, marcată la Muzeul Ianca', '2019-01-01', '', 1],
  12: ['Datini, tradiții și obiceiuri de Crăciun', '2018-12-01', '', 1],
  14: ['Proiectul „Ianca – Centenar – Unire și Recunoștință”', '2018-11-01', '', 1],
  15: ['Școala de vară: „De la micii sanitari la Regina Maria – mama răniților”', '2018-07-01', '', 1],
  16: ['Noaptea Europeană a Muzeelor la Ianca', '2018-05-01', '', 1],
  17: ['Omagierea Unirii lui Cuza: de la Mica la Marea Unire', '2018-01-24', '', 0, 30],
  18: ['Lecție despre eroul brăilean Stan Poetaș, din Primul Război Mondial', '2018-03-01', '', 1],
  19: ['Expoziție cu obiecte donate de Casa Regală', '2017-12-01', '', 1],
  20: ['Expoziție militară la Muzeul Ianca', '2017-10-20', '', 0],
  21: ['Școala de vară, ediția a X-a: „Ianca Eroică”', '2017-07-01', '', 1],
  22: ['Eroii satelor noastre – povestiri de război', '2016-11-29', '', 0],
  23: ['Expoziție: „Pionieri ai aeronauticii din spațiul brăilean”', '2016-11-01', '', 1],
  24: ['Muzeul Ianca își mărește biblioteca', '2016-08-01', '', 1],
  25: ['Școala de vară la Muzeul Ianca', '2016-07-25', '', 0],
  26: ['Mica Unire la Muzeul Ianca', '2016-01-24', '', 0],
  27: ['In memoriam Georgeta Crainic', '2015-12-01', '2015-12-31', 1],
  28: ['Ziua Națională la Muzeul Ianca', '2015-11-01', '', 1],
  29: ['Ziua Eroilor celebrată la Muzeul Ianca', '2015-05-01', '', 1],
  30: ['Lecție de istorie și de încondeiat ouă', '2015-04-01', '', 1]
};

const slug = s => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()
  .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 60).replace(/-$/, '');
const clean = t => t.replace(/(\s*Button)+/g, '').replace(/,,\s*/g, ' „').replace(/\s+„/g, ' „')
  .replace(/\.(?=[A-ZĂÎÂȘȚŞŢ])/g, '. ').replace(/\s{2,}/g, ' ').trim();

const out = Object.entries(plan).map(([i, [title, start, end, monthOnly, existingId]]) => {
  const s = slug(title);
  const year = start.slice(0, 4);
  const photos = [...new Set(imgs[i])].map((n, k) => ({
    src: found[n], file: `images/muzeu/evenimente/${year}/${s}${k ? '-' + (k + 1) : ''}.jpg`
  })).filter(p => p.src);
  return { idx: +i, title, alias: s, start, end, monthOnly, existingId: existingId || 0, text: clean(ev[i].text), photos };
});
fs.writeFileSync(outFile, JSON.stringify(out, null, 1));
console.log(out.length + ' evenimente, ' + out.reduce((a, e) => a + e.photos.length, 0) + ' poze');
out.forEach(e => console.log(`${e.start}${e.end ? '→' + e.end : ''}${e.monthOnly ? ' (luna)' : ''} | ${e.photos.length} poze | ${e.existingId ? '#' + e.existingId : 'nou'} | ${e.title}`));
