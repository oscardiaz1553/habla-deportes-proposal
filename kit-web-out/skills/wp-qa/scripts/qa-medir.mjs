// qa-medir.mjs — geometría, tipografía, imágenes y consola por página y ancho, con Chrome headless propio
// (sin tocar el navegador del usuario ni el de otra sesión). Mediciones M5 (y M3/M18) de
// ../references/mediciones.md; compara dos salidas con qa-cmp.py (M6).
//
// Propósito: los subagentes y el QA no usan las herramientas de navegador de la sesión (abren
//   pestañas que dejan oculta la del usuario: rAF e IntersectionObserver en pausa, capturas en
//   blanco). Este script lanza su propio Chrome, emula prefers-reduced-motion, recorre la página
//   (lazy-load e IntersectionObserver), fuerza el estado final de las entradas animadas y mide.
// Requisitos: Node >= 18, Google Chrome y puppeteer-core en la carpeta de trabajo (npm i con el
//   package.json de esta carpeta: HomeMaker usó ^23.11.1).
// Uso: node qa-medir.mjs <salida.json> <BASE> "/,/<ruta>/,/<IDIOMA_2>/" [anchos=1440,1280,768,375]
// Entorno opcional:
//   QA_REVELAR   selectores a los que se añade .is-in antes de medir (WP: '[data-<PREFIJO>-reveal],[data-<PREFIJO>-stagger]';
//                prototipo: sus clases de revelado, p. ej. '.reveal,.reveal-zoom')
//   QA_SECCIONES selector de las secciones a medir (por defecto 'main section'; se quedan las de primer nivel)
//   QA_IDIOMA    'clave=valor' de localStorage para fijar el idioma del prototipo antes de cargar (p. ej. '<PREFIJO>-lang=en')
//   CHROME       ruta del ejecutable de Chrome (por defecto, la de macOS)
// Salida: JSON { "<ancho></ruta/>": { lang, titulo, ancho, util, doc, sw, desborde, secs[[x,y,w,h]],
//   heads[[tag,texto,x,y,w,h,fontFamily,fontSize,lineHeight,fontWeight,letterSpacing,color,textTransform]],
//   imgs[[archivo,x,y,w,h,sizes,loading,fetchpriority,alt]], ocultos, fuentes, consola } } y una línea por medida.
// Ojo: Puppeteer lanza Chrome con --hide-scrollbars, así que el ancho útil es el del viewport (1440, no
//   los 1425 de un navegador con barra clásica). Mide prototipo y WordPress con esta misma herramienta.
// Estado: generalizado a partir de snap.mjs y console.mjs (sesión SEO de HomeMaker); probado solo en
//   local contra una página de muestra; no probado fuera de HomeMaker.
// Riesgos: solo lectura; GET a las URL indicadas (con ?nocache=, nunca ?m=).
import puppeteer from 'puppeteer-core';
import fs from 'fs';

const [out, base, rutasArg, anchosArg] = process.argv.slice(2);
if (!out || !base || !rutasArg) { console.error('uso: node qa-medir.mjs <salida.json> <BASE> "/,/ruta/" [1440,1280,768,375]'); process.exit(1); }
const rutas = rutasArg.split(',');
const BASE = base.replace(/\/+$/, ''); // sin barra final: las rutas empiezan por /
const anchos = (anchosArg || '1440,1280,768,375').split(',').map(Number);
const REVELAR = process.env.QA_REVELAR || '';
const SECCIONES = process.env.QA_SECCIONES || 'main section';
const IDIOMA = process.env.QA_IDIOMA || '';
const browser = await puppeteer.launch({
  executablePath: process.env.CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  headless: 'new', args: ['--no-sandbox'],
});
const res = {};
for (const w of anchos) {
  const page = await browser.newPage();
  const movil = w < 768;
  await page.setViewport({ width: w, height: movil ? 812 : 900, deviceScaleFactor: 1, isMobile: movil, hasTouch: movil });
  // El sitio debe revelarlo todo con movimiento reducido; además deja las entradas en su estado final.
  await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
  const errs = [];
  page.on('console', m => { if (['error', 'warning'].includes(m.type())) errs.push(m.type() + ': ' + m.text().slice(0, 160)); });
  page.on('pageerror', e => errs.push('pageerror: ' + e.message.slice(0, 160)));
  page.on('requestfailed', r => errs.push('failed: ' + r.url().slice(0, 120) + ' ' + ((r.failure() || {}).errorText || '')));
  if (IDIOMA) {
    const [k, v] = IDIOMA.split('=');
    await page.evaluateOnNewDocument((k, v) => { try { localStorage.setItem(k, v); } catch (e) {} }, k, v);
  }
  for (const r of rutas) {
    errs.length = 0;
    const url = BASE + r + (r.includes('?') ? '&' : '?') + 'nocache=' + Date.now(); // nunca ?m= (archivo por fecha de WP)
    try {
      await page.goto(url, { waitUntil: 'networkidle2', timeout: 60000 });
      // recorrer la página dispara lazy-load e IntersectionObserver
      await page.evaluate(async () => { for (let y = 0; y < document.body.scrollHeight; y += 400) { window.scrollTo(0, y); await new Promise(z => setTimeout(z, 40)); } window.scrollTo(0, 0); });
      await page.evaluate(() => document.fonts.ready);
      await page.evaluate(sel => {
        if (sel) document.querySelectorAll(sel).forEach(e => { e.style.transition = 'none'; e.classList.add('is-in'); });
        document.getAnimations().forEach(a => { try { if (a.effect.getComputedTiming().iterations !== Infinity) a.finish(); } catch (e) {} });
      }, REVELAR);
      await new Promise(z => setTimeout(z, 800));
      const data = await page.evaluate(SEL => {
        const R = el => { const r = el.getBoundingClientRect(); return [Math.round(r.left), Math.round(r.top + scrollY), Math.round(r.width * 10) / 10, Math.round(r.height * 10) / 10]; };
        const txt = el => (el.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 40);
        const T = el => { const s = getComputedStyle(el); return [s.fontFamily.slice(0, 40), s.fontSize, s.lineHeight, s.fontWeight, s.letterSpacing, s.color, s.textTransform]; };
        const cand = [...document.querySelectorAll(SEL)];
        const secs = cand.filter(s => !cand.some(o => o !== s && o.contains(s)));
        const de = document.documentElement;
        return {
          lang: de.lang, titulo: document.title,
          ancho: innerWidth, util: de.clientWidth, doc: de.scrollHeight, sw: de.scrollWidth, desborde: de.scrollWidth > de.clientWidth,
          secs: secs.map(R),
          heads: [...document.querySelectorAll('h1,h2,h3,h4,p,a.e-button-base,button')].filter(e => e.offsetParent).map(e => [e.tagName, txt(e), ...R(e), ...T(e)]),
          imgs: [...document.images].filter(i => i.offsetParent).map(i => [i.currentSrc.split('/').pop(), ...R(i), i.getAttribute('sizes'), i.getAttribute('loading'), i.getAttribute('fetchpriority'), i.getAttribute('alt')]),
          ocultos: [...document.querySelectorAll('main *')].filter(e => e.offsetParent && getComputedStyle(e).opacity === '0').length,
          fuentes: [...document.fonts].filter(f => f.status === 'loaded').map(f => f.family + ' ' + f.weight),
        };
      }, SECCIONES);
      data.consola = [...errs];
      res[`${w}${r}`] = data;
      console.log(w, r, 'doc', data.doc, 'secs', data.secs.length, data.desborde ? 'DESBORDE' : '', data.ocultos ? `opacidad0=${data.ocultos}` : '', errs.length ? `consola=${errs.length}` : '');
    } catch (e) { console.log('ERR', w, r, e.message); res[`${w}${r}`] = { error: e.message }; }
  }
  await page.close();
}
await browser.close();
fs.writeFileSync(out, JSON.stringify(res, null, 1));
