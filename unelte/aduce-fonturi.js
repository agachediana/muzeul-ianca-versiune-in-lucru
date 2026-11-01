/*
  Aduce fonturile (Google Fonts) si GSAP pe serverul site-ului, ca pagina
  sa nu mai trimita nimic la Google sau jsDelivr cand o deschide cineva.

  - fonturile: Bodoni Moda, Playfair Display (italic), Source Sans 3,
    doar literele latine (cu diacriticele romanesti), in media/fonts,
    iar regulile @font-face in media/css/fonts.css
  - GSAP 3.12.5 (gsap, ScrollTrigger, Flip) in media/js/vendor

  Folosire:  node unelte/aduce-fonturi.js muzeulianca/media
*/
const fs = require('fs');
const path = require('path');
const media = process.argv[2];
if (!media) { console.log('folosire: node aduce-fonturi.js <folder-media-tema>'); process.exit(1); }

const fontUrl = 'https://fonts.googleapis.com/css2?family=Bodoni+Moda:opsz,wght@6..96,400..700'
  + '&family=Playfair+Display:ital,wght@1,700;1,800&family=Source+Sans+3:wght@400;600;700&display=swap';
const gsap = ['gsap.min.js', 'ScrollTrigger.min.js', 'Flip.min.js'];

(async () => {
  fs.mkdirSync(path.join(media, 'fonts'), { recursive: true });
  fs.mkdirSync(path.join(media, 'js', 'vendor'), { recursive: true });

  // Google da fisiere woff2 doar unui browser modern, de aceea trimit un User-Agent de Chrome
  const css = await (await fetch(fontUrl, { headers: { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36' } })).text();

  // fiecare bloc are inaintea lui un comentariu cu setul de litere (latin, latin-ext, cyrillic...)
  const blocks = [...css.matchAll(/\/\*\s*([\w-]+)\s*\*\/\s*(@font-face\s*{[^}]+})/g)]
    .filter((m) => m[1] === 'latin' || m[1] === 'latin-ext');

  const saved = new Map();
  let out = '/*\n   Fonturile site-ului, puse pe server (fara cereri la Google).\n   Generat de unelte/aduce-fonturi.js, doar literele latine si diacriticele.\n*/\n';
  for (const [, subset, block] of blocks) {
    const family = /font-family:\s*'([^']+)'/.exec(block)[1];
    const style = /font-style:\s*(\w+)/.exec(block)[1];
    const url = /url\((https:[^)]+\.woff2)\)/.exec(block)[1];
    if (!saved.has(url)) {
      const name = `${family.toLowerCase().replace(/\s+/g, '-')}-${style}-${subset}-${saved.size + 1}.woff2`;
      const buf = Buffer.from(await (await fetch(url)).arrayBuffer());
      fs.writeFileSync(path.join(media, 'fonts', name), buf);
      saved.set(url, name);
      console.log(`font: ${name} (${Math.round(buf.length / 1024)} KB)`);
    }
    out += `\n/* ${family} ${style}, ${subset} */\n` + block.replace(url, `../fonts/${saved.get(url)}`) + '\n';
  }
  fs.writeFileSync(path.join(media, 'css', 'fonts.css'), out);

  for (const f of gsap) {
    const buf = Buffer.from(await (await fetch(`https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/${f}`)).arrayBuffer());
    fs.writeFileSync(path.join(media, 'js', 'vendor', f), buf);
    console.log(`gsap: ${f} (${Math.round(buf.length / 1024)} KB)`);
  }
})();
