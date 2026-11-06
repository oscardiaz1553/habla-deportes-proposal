// console.mjs — errores y avisos de consola, pageerror y peticiones fallidas por página y ancho.
// Pásalo tras CADA cambio de JS (jQuery al pie, vídeo diferido, revelado): un error de consola
// cuesta puntos de Buenas prácticas (errors-in-console) y suele delatar un orden de carga roto.
//
// Estado: generalizado del caso de referencia (HomeMaker); no probado fuera de HomeMaker.
// Uso:   BASE=https://<STAGING_URL> node console.mjs
//        Páginas: PAGINAS (./paginas.txt, nombre|/ruta/). CHROME = ruta del ejecutable.
// Riesgos: no cubre el editor de Elementor (Claude no inicia sesión): esa prueba se le pide al usuario.
import puppeteer from 'puppeteer-core';
import fs from 'fs';

const BASE = (process.env.BASE || '').replace(/\/$/, '');
if (!BASE) { console.error('uso: BASE=https://<STAGING_URL> node console.mjs'); process.exit(2); }
const CHROME = process.env.CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const pages = fs.readFileSync(process.env.PAGINAS || './paginas.txt', 'utf8').split('\n')
  .map(l => l.trim()).filter(l => l && !l.startsWith('#')).map(l => l.split('|')[1]);

const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox'] });
for (const vp of [{ w: 1440, h: 900 }, { w: 375, h: 812, m: true }]) {
  const page = await browser.newPage();
  await page.setViewport({ width: vp.w, height: vp.h, isMobile: !!vp.m, hasTouch: !!vp.m });
  const errs = [];
  page.on('console', m => { if (['error', 'warning'].includes(m.type())) errs.push(m.type() + ': ' + m.text().slice(0, 160)); });
  page.on('pageerror', e => errs.push('pageerror: ' + e.message.slice(0, 160)));
  page.on('requestfailed', r => errs.push('failed: ' + r.url().slice(0, 120) + ' ' + (r.failure() || {}).errorText));
  for (const p of pages) {
    errs.length = 0;
    try {
      await page.goto(BASE + p, { waitUntil: 'networkidle2', timeout: 60000 });
      await page.evaluate(async () => { for (let y = 0; y < document.body.scrollHeight; y += 500) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 50)); } });
      await new Promise(r => setTimeout(r, 1500));
      console.log(vp.w, p, errs.length ? errs : 'sin errores');
    } catch (e) { console.log(vp.w, p, 'ERR', e.message); }
  }
  await page.close();
}
await browser.close();
