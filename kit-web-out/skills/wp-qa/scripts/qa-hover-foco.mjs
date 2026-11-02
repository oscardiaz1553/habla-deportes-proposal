// qa-hover-foco.mjs — hover real (ratón) y foco real (Tab) con Chrome headless propio: contraste en hover,
// foco visible, el magenta #c36 del reset de Hello y foco que entra en paneles aria-hidden.
// M9b de ../references/mediciones.md.
//
// Propósito: el fallo típico de Elementor 4 es un botón <a> cuya variante hover solo cambia el fondo:
//   el a:hover del kit (0,2,1) gana a .elementor .<clase> (0,2,0) y el texto desaparece. Además, un
//   foco con :where() pierde contra el reset de Hello y el panel móvil del menú de Pro deja entrar el
//   Tab con el panel cerrado. Leer el CSS no basta: se pasa el ratón y se tabula de verdad.
// Requisitos: Node >= 18, Google Chrome y puppeteer-core en la carpeta de trabajo (npm i con el
//   package.json de esta carpeta).
// Uso: node qa-hover-foco.mjs <BASE> </ruta/> [ancho=1440] [selector='a.e-button-base,button,[role="button"]'] [tabs=40]
//   Entorno opcional: CHROME = ruta del ejecutable de Chrome.
// Salida: una línea «ok»/«FALLA» por botón (colores en reposo y en hover, ratio WCAG, mínimo 4,5 o 3)
//   y por cada Tab (etiqueta, nombre, outline, sombra, avisos). Fondo con imagen o vídeo: no se
//   calcula; se avisa para medirlo con contrast.py (M10b).
// Estado: generalizado, no probado fuera de HomeMaker (reúne sobre puppeteer las comprobaciones que allí
//   se hicieron a mano: botones invisibles en hover, foco global contra el reset de Hello, #c36 en los
//   <button> y aria-hidden-focus del panel móvil); probado solo en local contra una página de muestra.
// Riesgos: solo lectura; GET de una URL. Pasa el ratón y pulsa Tab: no hace clic en ningún enlace.
import puppeteer from 'puppeteer-core';

const [base, ruta, anchoArg, selArg, tabsArg] = process.argv.slice(2);
if (!base || !ruta) { console.error('uso: node qa-hover-foco.mjs <BASE> </ruta/> [ancho] [selector] [tabs]'); process.exit(1); }
const ancho = Number(anchoArg || 1440);
const SEL = selArg || 'a.e-button-base,button,[role="button"]';
const TABS = Number(tabsArg || 40);
const browser = await puppeteer.launch({
  executablePath: process.env.CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  headless: 'new', args: ['--no-sandbox'],
});
const page = await browser.newPage();
await page.setViewport({ width: ancho, height: 900, deviceScaleFactor: 1 });
await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
await page.goto(base.replace(/\/+$/, '') + ruta + (ruta.includes('?') ? '&' : '?') + 'nocache=' + Date.now(), { waitUntil: 'networkidle2', timeout: 60000 });

// En la página: color efectivo de fondo (capas semitransparentes compuestas) y ratio WCAG.
await page.evaluate(() => {
  const rgba = s => { const m = /rgba?\(([^)]+)\)/.exec(s || ''); if (!m) return null; const p = m[1].split(/[\s,/]+/).filter(Boolean).map(Number); return [p[0], p[1], p[2], p.length > 3 ? p[3] : 1]; };
  const sobre = (a, b) => [0, 1, 2].map(i => a[i] * a[3] + b[i] * (1 - a[3]));
  const lum = c => { const s = c.map(v => { v /= 255; return v <= 0.04045 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); }); return 0.2126 * s[0] + 0.7152 * s[1] + 0.0722 * s[2]; };
  window.__qa = el => {
    const capas = []; let img = false;
    for (let e = el; e; e = e.parentElement) {
      const s = getComputedStyle(e);
      if (s.backgroundImage !== 'none') { img = true; break; }
      const c = rgba(s.backgroundColor);
      if (c && c[3] > 0) { capas.push(c); if (c[3] >= 1) break; }
    }
    let bg = [255, 255, 255];
    for (const c of capas.reverse()) bg = sobre(c, bg);
    const s = getComputedStyle(el); const fg = rgba(s.color);
    const tx = fg ? sobre(fg, bg) : null;
    const x = tx ? lum(tx) : 0, y = lum(bg);
    const px = parseFloat(s.fontSize), grande = px >= 24 || (px >= 18.66 && Number(s.fontWeight) >= 700);
    return { color: s.color, fondo: s.backgroundColor, sobreImagen: img, ratio: tx && !img ? Math.round((Math.max(x, y) + 0.05) / (Math.min(x, y) + 0.05) * 100) / 100 : null, minimo: grande ? 3 : 4.5,
      outline: `${s.outlineStyle} ${s.outlineWidth} ${s.outlineColor}`, sombra: s.boxShadow };
  };
});

console.log('== HOVER (' + SEL + ')');
const els = await page.$$(SEL);
for (const el of els.slice(0, 30)) {
  const visible = await el.evaluate(e => !!e.offsetParent && !e.closest('[aria-hidden="true"]'));
  if (!visible) continue;
  const nombre = await el.evaluate(e => (e.getAttribute('aria-label') || e.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 30));
  const antes = await el.evaluate(e => window.__qa(e));
  try { await el.hover(); } catch (e) { continue; }
  await new Promise(z => setTimeout(z, 400)); // deja terminar la transición de color
  const hover = await el.evaluate(e => window.__qa(e));
  const avisos = [];
  if (hover.ratio !== null && hover.ratio < hover.minimo) avisos.push(`contraste en hover ${hover.ratio} < ${hover.minimo}`);
  if (/rgb\(204, 51, 102\)/.test(hover.color + hover.fondo)) avisos.push('#c36 del reset de Hello');
  if (hover.sobreImagen) avisos.push('fondo con imagen: medir con el método de foto/vídeo');
  console.log(`${avisos.length ? 'FALLA' : 'ok   '} «${nombre}» reposo ${antes.color}/${antes.fondo} → hover ${hover.color}/${hover.fondo} ratio ${hover.ratio} ${avisos.join(' · ')}`);
  await page.mouse.move(0, 0);
}

console.log('== FOCO (Tab ×' + TABS + ')');
// Tab desde el principio del documento, sin clic (un clic en (1,1) podía caer en un enlace y navegar).
await page.mouse.move(0, 0);
await page.evaluate(() => { if (document.activeElement && document.activeElement !== document.body) document.activeElement.blur(); window.scrollTo(0, 0); });
for (let i = 0; i < TABS; i++) {
  await page.keyboard.press('Tab');
  const f = await page.evaluate(() => {
    const e = document.activeElement; if (!e || e === document.body) return null;
    const q = window.__qa(e);
    return { tag: e.tagName, nombre: (e.getAttribute('aria-label') || e.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 30),
      oculto: !!e.closest('[aria-hidden="true"]'), visible: !!e.offsetParent, outline: q.outline, sombra: q.sombra, color: q.color, fondo: q.fondo };
  });
  if (!f) continue;
  const avisos = [];
  if (f.oculto) avisos.push('foco dentro de aria-hidden (aria-hidden-focus)');
  if (!f.visible) avisos.push('foco en un elemento no visible');
  if (/^none/.test(f.outline) && f.sombra === 'none') avisos.push('sin indicador de foco');
  if (/rgb\(204, 51, 102\)/.test(f.outline + f.color + f.fondo)) avisos.push('#c36 del reset de Hello');
  console.log(`${avisos.length ? 'FALLA' : 'ok   '} ${i + 1} <${f.tag}> «${f.nombre}» outline ${f.outline} sombra ${f.sombra.slice(0, 40)} ${avisos.join(' · ')}`);
}
await browser.close();
