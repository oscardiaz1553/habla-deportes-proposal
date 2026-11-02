/*
 * medir-vs-prototipo.js — Mide cajas (x, y absoluta, ancho, alto) y estilos computados de las
 * secciones y de los elementos que elijas, con las animaciones de entrada ya resueltas, para
 * comparar número a número el WordPress con el prototipo. Mide, no mira.
 *
 * PROPÓSITO (síntoma → causa)
 *   - Las capturas engañan: reveals sin disparar (translateY 28px → «+28px en su top»), pestaña
 *     oculta (rAF e IntersectionObserver en pausa), bandas vacías. getBoundingClientRect y
 *     getComputedStyle, con los reveals FORZADOS en los dos lados, son la verdad.
 *   - Con barra de scroll, el ancho útil a 1440 es 1425: se reporta para anotarlo en el spec.
 *
 * USO
 *   1. Abre prototipo y WP en dos pestañas con el MISMO viewport (1440×900, 375×812; cabecera y
 *      pie también a 1100 y 900). Fija el idioma del prototipo si lo guarda en localStorage.
 *   2. Ajusta CONFIG (selectores equivalentes en los dos lados) y pega el archivo entero en la
 *      consola, o pásalo a la herramienta de JS del navegador / page.evaluate() de Puppeteer.
 *   3. Primero en el prototipo: copia el JSON. Luego en WP con CONFIG.referencia = ese JSON:
 *      devuelve además la lista de diferencias por encima de la tolerancia.
 *   Modo varias URLs en una llamada (iframes del mismo origen; las media queries van por el ancho
 *   del iframe): CONFIG.urls = [ '/', '/<IDIOMA_2>/' ] → alto del documento, desborde y alto de
 *   cada sección por URL. No recargues la página dentro del script: corta el valor devuelto.
 *
 * QUÉ EDITAR
 *   CONFIG: lado, selectores de revelado, raíz de secciones, elementos a medir, tolerancia.
 *   Los selectores de WP usan clases «pfx-» / atributos data-pfx-* del proyecto (<PREFIJO>).
 *
 * ESTADO
 *   Núcleo probado en HomeMaker (los fragmentos de medición de páginas, del pie y de iframes: forzar is-in +
 *   transition:none, R(el) con scrollY, T = propiedades tipográficas, secciones de primer nivel,
 *   scrollWidth > innerWidth, iframes a 375). Generalizado, no probado fuera de HomeMaker: CONFIG,
 *   el modo scroll como alternativa, el recuento de revelados pendientes y la comparación
 *   automática contra la referencia.
 *
 * RIESGOS
 *   Solo lee (añade clases de revelado y transition:none en la pestaña actual; recarga para
 *   volver al estado normal). Tolerancia (la canónica, wp-elementor-replica/references/verificacion.md):
 *   objetivo ≤1 px con reveals forzados; ≤3 px por sección en anchos intermedios; idioma 2 frente
 *   al idioma base, ≤1 px. CONFIG.tolerancia = 1 por defecto; súbela a 3 solo en anchos intermedios.
 */
(async () => {
  const CONFIG = {
    lado: 'wp', // 'wp' | 'prototipo'
    espera: 2500, // ms antes de medir (fuentes, lazy, JS del tema)
    forzar: 'clases', // 'clases' (añade la clase final) | 'scroll' (recorre la página) | 'no'
    revelar: {
      // is-in lo reciben reveal y stagger (el grupo; sus hijos animan con retraso escalonado);
      // rise es una animación CSS que arranca sola: se fija su estado final, no lleva is-in.
      wp: [
        { sel: '[data-pfx-reveal],[data-pfx-stagger]', clase: 'is-in' },
        { sel: '[data-pfx-stagger] > *, [data-pfx-rise]', estilo: { animation: 'none', opacity: '1' } },
      ],
      prototipo: [
        { sel: '.reveal,.reveal-zoom', clase: 'is-in' },
        { sel: '.stagger-item', estilo: { animation: 'none', opacity: '1' } },
      ],
    },
    // raíz cuyas <section> de primer nivel son las secciones (en WP también vale '[data-elementor-id="<ID>"]')
    raiz: { wp: 'main, .pfx-main', prototipo: 'main' },
    // nombre → selector en cada lado (el primero que coincida)
    elementos: {
      h1: { wp: 'h1', prototipo: 'h1' },
      // cabecera: { wp: '.elementor-location-header', prototipo: 'header' },
    },
    props: ['fontFamily', 'fontSize', 'lineHeight', 'fontWeight', 'letterSpacing', 'color', 'textTransform'],
    referencia: null, // pega aquí el objeto JSON medido en el otro lado
    tolerancia: 1, // px
    urls: [], // modo iframes: [ '/', '/contacto/' ]
    anchoIframe: 375,
    altoIframe: 812,
  };

  const dormir = (ms) => new Promise((r) => setTimeout(r, ms));
  const R = (el) => {
    if (!el) return null;
    const r = el.getBoundingClientRect();
    return { x: Math.round(r.left), y: Math.round(r.top + scrollY), w: Math.round(r.width * 10) / 10, h: Math.round(r.height * 10) / 10 };
  };
  const cs = (el, props) => {
    if (!el) return null;
    const s = getComputedStyle(el);
    return Object.fromEntries(props.map((k) => [k, s[k]]));
  };
  const primerNivel = (root) => {
    if (!root) return [];
    const secs = [...root.querySelectorAll('section')].filter((s, i, a) => !a.some((o) => o !== s && o.contains(s)));
    return secs.length ? secs : [...root.children];
  };

  /* ---------- modo varias URLs (iframes del mismo origen) ---------- */
  if (CONFIG.urls.length) {
    const medir = (u) => new Promise((res) => {
      const f = document.createElement('iframe');
      f.style.cssText = `position:fixed;left:-5000px;top:0;width:${CONFIG.anchoIframe}px;height:${CONFIG.altoIframe}px;border:0`;
      f.src = u + (u.includes('?') ? '&' : '?') + 'nocache=' + Date.now(); // ?m= en WordPress es archivo por fecha: da 404
      f.onload = () => setTimeout(() => {
        const d = f.contentDocument;
        const root = d.querySelector(CONFIG.raiz[CONFIG.lado]);
        if (!root) { res({ u, error: 'sin raíz: ' + d.title }); f.remove(); return; }
        res({
          u, lang: d.documentElement.lang, doc: d.documentElement.scrollHeight,
          desborde: d.documentElement.scrollWidth > CONFIG.anchoIframe,
          secs: primerNivel(root).map((s) => Math.round(s.offsetHeight)),
        });
        f.remove();
      }, CONFIG.espera);
      document.body.appendChild(f);
    });
    const out = [];
    for (const u of CONFIG.urls) out.push(await medir(u));
    const json = JSON.stringify(out);
    console.log(json);
    return json;
  }

  /* ---------- forzar el estado final de las entradas ---------- */
  await dormir(CONFIG.espera);
  const reglas = CONFIG.revelar[CONFIG.lado] || [];
  if (CONFIG.forzar === 'clases') {
    for (const r of reglas) {
      document.querySelectorAll(r.sel).forEach((e) => {
        e.style.transition = 'none';
        if (r.clase) e.classList.add(r.clase);
        if (r.estilo) Object.assign(e.style, r.estilo);
      });
    }
  } else if (CONFIG.forzar === 'scroll') {
    const alto = document.documentElement.scrollHeight;
    for (let y = 0; y < alto; y += 350) { scrollTo(0, y); await dormir(140); }
    scrollTo(0, 0);
  }
  await dormir(300);
  const pendientes = reglas.flatMap((r) => [...document.querySelectorAll(r.sel)])
    .filter((e) => { const s = getComputedStyle(e); return s.transform !== 'none' || parseFloat(s.opacity) < 1; }).length;

  /* ---------- medir ---------- */
  const root = document.querySelector(CONFIG.raiz[CONFIG.lado]);
  const medida = {
    lado: CONFIG.lado,
    viewport: { w: innerWidth, h: innerHeight, util: document.documentElement.clientWidth },
    doc: document.documentElement.scrollHeight,
    desborde: document.documentElement.scrollWidth > innerWidth,
    pendientes,
    secciones: primerNivel(root).map(R),
    elementos: {},
  };
  for (const [nombre, sel] of Object.entries(CONFIG.elementos)) {
    const el = document.querySelector(sel[CONFIG.lado]);
    medida.elementos[nombre] = el ? { caja: R(el), estilos: cs(el, CONFIG.props) } : null;
  }

  /* ---------- comparar con el otro lado ---------- */
  if (CONFIG.referencia) {
    const ref = typeof CONFIG.referencia === 'string' ? JSON.parse(CONFIG.referencia) : CONFIG.referencia;
    const dif = [];
    const caja = (a, b, donde) => {
      if (!a || !b) { if (a !== b) dif.push({ donde, falta: !a ? 'aquí' : 'en la referencia' }); return; }
      for (const k of ['x', 'y', 'w', 'h']) {
        const d = Math.round((a[k] - b[k]) * 10) / 10;
        if (Math.abs(d) > CONFIG.tolerancia) dif.push({ donde: `${donde}.${k}`, aqui: a[k], ref: b[k], d });
      }
    };
    if (Math.abs(medida.doc - ref.doc) > CONFIG.tolerancia) dif.push({ donde: 'alto del documento', aqui: medida.doc, ref: ref.doc, d: medida.doc - ref.doc });
    const n = Math.max(medida.secciones.length, (ref.secciones || []).length);
    for (let i = 0; i < n; i++) caja(medida.secciones[i], (ref.secciones || [])[i], `sección ${i}`);
    for (const nombre of Object.keys(medida.elementos)) {
      const a = medida.elementos[nombre];
      const b = (ref.elementos || {})[nombre];
      caja(a && a.caja, b && b.caja, nombre);
      if (a && b && a.estilos && b.estilos) {
        for (const p of CONFIG.props) {
          const va = a.estilos[p]; const vb = b.estilos[p];
          const na = parseFloat(va); const nb = parseFloat(vb);
          const igual = !Number.isNaN(na) && !Number.isNaN(nb) && /px$/.test(va) && /px$/.test(vb)
            ? Math.abs(na - nb) <= CONFIG.tolerancia : va === vb;
          if (!igual) dif.push({ donde: `${nombre}.${p}`, aqui: va, ref: vb });
        }
      }
    }
    medida.diferencias = dif;
  }

  const json = JSON.stringify(medida);
  console.log(json);
  return json;
})();
