/*
  Scanarile 3D ale muzeului, din colectia de pe Sketchfab

  Ia lista modelelor din colectie (in aceeasi ordine ca pozele din
  images/muzeu/scanari-3d), scrie modele.txt ("01-ie.jpg=<id model>") si
  inlocuieste miniaturile cu pozele facute de Sketchfab, mai curate.
  Pe pagina Galerii foto fiecare obiect se deschide apoi singur, mult mai
  repede decat toata colectia odata.

  Folosire:
    node modele-3d.js C:\xampp\htdocs\joomla
*/

const fs = require('fs');
const path = require('path');

const COLECTIE = '33ff92f30f0949f094d1c449583e4077';
const dir = path.join(process.argv[2] || 'C:\\xampp\\htdocs\\joomla', 'images', 'muzeu', 'scanari-3d');

(async () => {
  let url = `https://api.sketchfab.com/v3/collections/${COLECTIE}/models?count=24`;
  const modele = [];
  while (url) {
    const r = await (await fetch(url)).json();
    modele.push(...r.results);
    url = r.next;
  }
  const poze = fs.readdirSync(dir).filter((f) => /^\d+-.+\.jpg$/i.test(f)).sort();
  if (poze.length !== modele.length) {
    console.log(`Atentie: ${poze.length} poze si ${modele.length} modele, verifica ordinea`);
  }

  const randuri = [];
  for (let i = 0; i < Math.min(poze.length, modele.length); i++) {
    const m = modele[i];
    randuri.push(`${poze[i]}=${m.uid}`);
    // miniatura cea mai apropiata de 720px latime
    const img = m.thumbnails.images.filter((t) => t.width >= 600).sort((a, b) => a.width - b.width)[0];
    if (img) {
      const buf = Buffer.from(await (await fetch(img.url)).arrayBuffer());
      fs.writeFileSync(path.join(dir, poze[i]), buf);
    }
    console.log(`${poze[i]} -> ${m.name} (${m.uid})`);
  }
  fs.writeFileSync(path.join(dir, 'modele.txt'), randuri.join('\n') + '\n');
  console.log('modele.txt scris');
})();
