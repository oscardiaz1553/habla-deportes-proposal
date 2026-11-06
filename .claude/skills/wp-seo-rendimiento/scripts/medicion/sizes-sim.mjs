// sizes-sim.mjs — simula EN VIVO (sin desplegar nada) el cambio de atributos de las imágenes
// V4 y dice cuáles cambiarían de tamaño en pantalla. Se usó para decidir el envoltorio del
// transformador `image` (sizes="auto, 100vw"): las imágenes con width:auto/height:auto (logos)
// colapsan porque el navegador aplica contain:size a img[sizes^="auto"]; esas necesitan un
// sizes fijo en Avanzado › Atributos.
//
// Estado: generalizado del caso de referencia (HomeMaker; fusiona sus sizes-sim y sizes-sim2);
//   no probado fuera de HomeMaker.
// Uso:   BASE=https://<STAGING_URL> node sizes-sim.mjs [--lazy]
//        Sin --lazy: pone sizes="auto, 100vw" y compara (sizes=auto solo actúa con loading=lazy).
//        Con --lazy: fuerza además loading="lazy" (lo que haría el filtro) y muestra contain y la
//        imagen que acaba bajando. Páginas: PAGINAS (./paginas.txt). CHROME = ruta del ejecutable.
import puppeteer from 'puppeteer-core';
import fs from 'fs';

const BASE = (process.env.BASE || '').replace(/\/$/, '');
if (!BASE) { console.error('uso: BASE=https://<STAGING_URL> node sizes-sim.mjs [--lazy]'); process.exit(2); }
const LAZY = process.argv.includes('--lazy');
const CHROME = process.env.CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const pages = fs.readFileSync(process.env.PAGINAS || './paginas.txt', 'utf8').split('\n')
  .map(l => l.trim()).filter(l => l && !l.startsWith('#')).map(l => l.split('|')[1]);

const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox'] });
for (const vp of [{ w: 1440, h: 900 }, { w: 1024, h: 800 }, { w: 375, h: 812, m: true }]) {
  const page = await browser.newPage();
  await page.setViewport({ width: vp.w, height: vp.h, isMobile: !!vp.m, hasTouch: !!vp.m });
  await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
  for (const p of pages) {
    try {
      await page.goto(BASE + p, { waitUntil: 'networkidle2', timeout: 90000 });
      await page.evaluate(async () => { for (let y = 0; y < document.body.scrollHeight; y += 500) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 40)); } window.scrollTo(0, 0); });
      await new Promise(r => setTimeout(r, 600));
      const out = await page.evaluate(async lazy => {
        const imgs = [...document.images].filter(i => i.srcset && i.offsetParent);
        const before = imgs.map(i => [i.clientWidth, i.clientHeight, i.currentSrc.split('/').pop()]);
        imgs.forEach(i => { if (lazy) i.setAttribute('loading', 'lazy'); i.setAttribute('sizes', 'auto, 100vw'); });
        await new Promise(r => setTimeout(r, lazy ? 1500 : 300));
        return imgs.map((i, k) => ({
          f: before[k][2], cls: i.className.replace(/e-image-base|e-default-img/g, '').trim(), id: i.dataset.id,
          contain: getComputedStyle(i).contain, b: before[k].slice(0, 2), a: [i.clientWidth, i.clientHeight], now: i.currentSrc.split('/').pop(),
        })).filter(x => Math.abs(x.b[0] - x.a[0]) > 1 || Math.abs(x.b[1] - x.a[1]) > 1 || (lazy && x.now !== x.f));
      }, LAZY);
      console.log(vp.w, p, out.length ? JSON.stringify(out) : 'sin cambios');
    } catch (e) { console.log(vp.w, p, 'ERR', e.message); }
  }
  await page.close();
}
await browser.close();
