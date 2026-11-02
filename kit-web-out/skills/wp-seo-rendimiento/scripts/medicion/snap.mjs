// snap.mjs — «foto» geométrica de cada página para regresión visual (no compara píxeles).
// Por página y ancho guarda: alto del documento, scrollWidth, caja y font-family de cada
// h1-h4/p/botón, caja/currentSrc/sizes/loading/fetchpriority de cada imagen visible, fuentes
// cargadas, peticiones de fuentes/jQuery/póster/mp4 y hojas/scripts/preloads del <head>.
// Emula prefers-reduced-motion: reduce (todo revelado), recorre la página para disparar lazy y
// espera document.fonts.ready. Compara dos fotos con cmp.py (tolerancia 1 px).
//
// Estado: generalizado del caso de referencia (HomeMaker); no probado fuera de HomeMaker.
// Uso:   BASE=https://<STAGING_URL> node snap.mjs <salida.json>
//        Páginas: PAGINAS (./paginas.txt, formato nombre|/ruta/). Anchos: ANCHOS="1440x900,375x812m"
//        (sufijo m = móvil táctil). CHROME = ruta del ejecutable.
// Riesgos: networkidle2 puede tardar en páginas con vídeo; añade ?vr=<timestamp> a cada URL para
//   saltar la caché de página (no uses ?m=: es la variable de archivo por fecha de WordPress).
import puppeteer from 'puppeteer-core';
import fs from 'fs';

const out = process.argv[2];
const BASE = (process.env.BASE || '').replace(/\/$/, '');
if (!out || !BASE) { console.error('uso: BASE=https://<STAGING_URL> node snap.mjs <salida.json>'); process.exit(2); }
const CHROME = process.env.CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const pages = fs.readFileSync(process.env.PAGINAS || './paginas.txt', 'utf8').split('\n')
  .map(l => l.trim()).filter(l => l && !l.startsWith('#')).map(l => l.split('|')[1]);
const vps = (process.env.ANCHOS || '1440x900,375x812m').split(',').map(s => {
  const m = s.trim().match(/^(\d+)x(\d+)(m?)$/); return { w: +m[1], h: +m[2], mobile: !!m[3] };
});

const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox'] });
const res = {};
for (const vp of vps) {
  const page = await browser.newPage();
  await page.setViewport({ width: vp.w, height: vp.h, deviceScaleFactor: 1, isMobile: vp.mobile, hasTouch: vp.mobile });
  await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
  for (const p of pages) {
    const url = BASE + p + (p.includes('?') ? '&' : '?') + 'vr=' + Date.now();
    try {
      await page.goto(url, { waitUntil: 'networkidle2', timeout: 60000 });
      await page.evaluate(async () => { for (let y = 0; y < document.body.scrollHeight; y += 400) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 40)); } window.scrollTo(0, 0); });
      await page.evaluate(() => document.fonts.ready);
      await new Promise(r => setTimeout(r, 800));
      const data = await page.evaluate(() => {
        const R = el => { const r = el.getBoundingClientRect(); return [Math.round(r.left), Math.round(r.top + scrollY), Math.round(r.width * 10) / 10, Math.round(r.height * 10) / 10]; };
        const txt = el => (el.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 40);
        return {
          doc: document.documentElement.scrollHeight,
          sw: document.documentElement.scrollWidth,
          heads: [...document.querySelectorAll('h1,h2,h3,h4,p,a.e-button-base,button')].filter(e => e.offsetParent).map(e => [e.tagName, txt(e), ...R(e), getComputedStyle(e).fontFamily.slice(0, 40)]),
          imgs: [...document.images].filter(i => i.offsetParent).map(i => [i.currentSrc.split('/').pop(), ...R(i), i.getAttribute('sizes'), i.getAttribute('loading'), i.getAttribute('fetchpriority')]),
          fonts: [...document.fonts].filter(f => f.status === 'loaded').map(f => f.family + ' ' + f.weight),
          reqs: performance.getEntriesByType('resource').map(e => e.name.replace(/\?.*$/, '')).filter(n => /fonts|jquery|\.woff2|poster|\.mp4/.test(n)),
          head: [...document.head.querySelectorAll('script[src],link[rel=stylesheet],link[rel=preload]')].map(e => (e.src || e.href || '').replace(/\?.*$/, '').split('/').slice(-2).join('/')),
        };
      });
      res[`${vp.w}${p}`] = data;
      console.log(vp.w, p, 'doc', data.doc, 'heads', data.heads.length, 'imgs', data.imgs.length);
    } catch (e) { console.log('ERR', vp.w, p, e.message); res[`${vp.w}${p}`] = { error: e.message }; }
  }
  await page.close();
}
await browser.close();
fs.writeFileSync(out, JSON.stringify(res, null, 1));
