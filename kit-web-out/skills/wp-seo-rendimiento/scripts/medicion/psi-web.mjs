// psi-web.mjs — la cifra «de Google» de PageSpeed Insights sin clave de API.
// Abre pagespeed.web.dev con puppeteer-core (Chrome headless propio, nunca el navegador del
// usuario) y guarda el informe de Lighthouse (LHR) que la página recibe en la respuesta
// POST /_/PagespeedUi/data/batchexecute?rpcids=LsX2he.
//
// Estado: generalizado del caso de referencia (HomeMaker) CON EL BUG DE formFactor CORREGIDO.
//   La corrección solo se ha comprobado con `node --check`; la primera vez que lo uses, valida la
//   salida con `python3 lh-sum.py <salida.json>` (columna formFactor, sin «FF!»).
// Bug corregido: la versión original guardaba la PRIMERA respuesta LsX2he que traía
//   categories.performance. pagespeed.web.dev analiza móvil y escritorio a la vez, así que al pedir
//   escritorio podía guardar el móvil (mismo fetchTime, formFactor: mobile) y se llegó a informar
//   un «escritorio 94» que era el móvil. Ahora solo se acepta el LHR cuyo
//   configSettings.formFactor coincide con el pedido, y se imprimen formFactor y fetchTime.
// Uso:   BASE=https://<DOMINIO> node psi-web.mjs </ruta/> <mobile|desktop> <salida.json>
// Vars:  CHROME (ruta del ejecutable; por defecto Google Chrome de macOS), PSI_TIMEOUT_MS (150000).
// Riesgos: la URL tiene que ser pública (PSI no atraviesa una cortina ni ve localhost). Depende de
//   la interfaz interna de pagespeed.web.dev (rpcids=LsX2he, prefijo [["wrb.fr"): si Google la
//   cambia, imprime SIN RESULTADO. La API v5 sin clave devuelve 429 (cuota anónima compartida).
import puppeteer from 'puppeteer-core';
import fs from 'fs';

const [ruta, ff, out] = process.argv.slice(2);
const BASE = (process.env.BASE || '').replace(/\/$/, '');
if (!BASE || !ruta || !['mobile', 'desktop'].includes(ff) || !out) {
  console.error('uso: BASE=https://<DOMINIO> node psi-web.mjs </ruta/> <mobile|desktop> <salida.json>');
  process.exit(2);
}
const CHROME = process.env.CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const LIMITE = Number(process.env.PSI_TIMEOUT_MS || 150000);
const target = BASE + ruta;

const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox'] });
const page = await browser.newPage();
let lhr = null;
let otros = 0; // informes del otro formato que se han descartado

page.on('response', async r => {
  if (!r.url().includes('rpcids=LsX2he') || lhr) return;
  try {
    const t = await r.text();
    const i = t.indexOf('[["wrb.fr"');
    if (i < 0) return;
    const outer = JSON.parse(t.slice(i).split('\n')[0]);
    const inner = JSON.parse(outer[0][2]);
    const walk = x => {
      if (typeof x === 'string' && x.startsWith('{') && x.includes('lighthouseVersion')) return x;
      if (Array.isArray(x)) for (const y of x) { const f = walk(y); if (f) return f; }
      return null;
    };
    const s = walk(inner);
    if (!s) return;
    const j = JSON.parse(s);
    const formato = j.configSettings && j.configSettings.formFactor;
    if (formato !== ff) { otros++; return; } // ← la corrección: nunca aceptar el otro formato
    if ((j.categories && j.categories.performance) || j.runtimeError) lhr = j;
  } catch (e) { /* respuestas parciales o de otro tipo: se ignoran */ }
});

await page.goto(`https://pagespeed.web.dev/analysis?url=${encodeURIComponent(target)}&form_factor=${ff}`, { waitUntil: 'domcontentloaded', timeout: 60000 });
const t0 = Date.now();
while (!lhr && Date.now() - t0 < LIMITE) await new Promise(r => setTimeout(r, 2000));
await browser.close();

if (!lhr) { console.log(ruta, ff, 'SIN RESULTADO', otros ? `(llegaron ${otros} informes del otro formato)` : ''); process.exit(1); }
fs.writeFileSync(out, JSON.stringify(lhr));
if (lhr.runtimeError) { console.log(ruta, ff, 'ERROR', lhr.runtimeError.code); process.exit(0); }
const c = lhr.categories, a = lhr.audits;
const s = k => c[k] ? Math.round(c[k].score * 100) : '-';
console.log(`${ruta} ${ff}: P${s('performance')} A${s('accessibility')} BP${s('best-practices')} SEO${s('seo')} | FCP ${a['first-contentful-paint'].displayValue} LCP ${a['largest-contentful-paint'].displayValue} TBT ${a['total-blocking-time'].displayValue} CLS ${a['cumulative-layout-shift'].displayValue} SI ${a['speed-index'].displayValue} | formFactor=${lhr.configSettings.formFactor} fetchTime=${lhr.fetchTime} lighthouse=${lhr.lighthouseVersion}`);
