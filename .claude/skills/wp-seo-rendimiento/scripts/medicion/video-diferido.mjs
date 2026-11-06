// video-diferido.mjs — comprueba el vídeo de fondo diferido de la portada: en qué momento se pide
// el .mp4 (debe ser DESPUÉS de load) con y sin prefers-reduced-motion (con reduce: nunca hasta
// pulsar Reproducir), el estado del <video> y de los controles, y errores de consola.
//
// Estado: generalizado del caso de referencia (HomeMaker); no probado fuera de HomeMaker.
// Uso:   BASE=https://<STAGING_URL> node video-diferido.mjs [/ruta/=/]
// Vars:  VIDEO_SEL (selector del <video>; por defecto 'video.e-background-video__media', el que
//        imprime e-background-video de Elementor 4), CHROME.
// Esperado: no-preference → «mp4 pedido a» > «load»; reduce → «nunca» y, tras pulsar play, el src
//   del mp4 y paused=false.
import puppeteer from 'puppeteer-core';

const BASE = (process.env.BASE || '').replace(/\/$/, '');
if (!BASE) { console.error('uso: BASE=https://<STAGING_URL> node video-diferido.mjs [/ruta/]'); process.exit(2); }
const RUTA = process.argv[2] || '/';
const SEL = process.env.VIDEO_SEL || 'video.e-background-video__media';
const CHROME = process.env.CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';

const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox', '--autoplay-policy=no-user-gesture-required'] });
for (const rm of ['no-preference', 'reduce']) {
  const page = await browser.newPage();
  await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: rm }]);
  const errs = []; let mp4 = null;
  page.on('pageerror', e => errs.push(e.message));
  page.on('console', m => m.type() === 'error' && errs.push(m.text()));
  page.on('request', r => { if (r.url().includes('.mp4') && mp4 === null) mp4 = Date.now(); });
  const t0 = Date.now();
  await page.goto(BASE + RUTA, { waitUntil: 'load', timeout: 90000 });
  const tload = Date.now() - t0;
  await new Promise(r => setTimeout(r, 4000));
  const st = await page.evaluate(sel => {
    const v = document.querySelector(sel);
    if (!v) return { error: 'no hay ' + sel };
    return { src: (v.currentSrc || '').split('/').pop(), paused: v.paused, t: v.currentTime.toFixed(1),
      ctrl: [...document.querySelectorAll('.e-background-video__play,.e-background-video__pause')].map(b => b.className.split(' ')[0] + ':' + getComputedStyle(b.closest('[data-e-type]') || b).display) };
  }, SEL);
  console.log(rm, 'load', tload, 'ms | mp4 pedido a', mp4 ? mp4 - t0 : 'nunca', 'ms |', JSON.stringify(st), errs.length ? errs : 'sin errores');
  if (rm === 'reduce') {
    await new Promise(r => setTimeout(r, 500));
    await page.click('.e-background-video__play').catch(e => console.log('click', e.message));
    await new Promise(r => setTimeout(r, 2500));
    console.log('  tras pulsar play:', await page.evaluate(sel => { const v = document.querySelector(sel); return v ? v.currentSrc.split('/').pop() + ' paused=' + v.paused : 'sin vídeo'; }, SEL));
  }
  await page.close();
}
await browser.close();
