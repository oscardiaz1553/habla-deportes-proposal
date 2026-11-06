/*
 * auditar-padding-atomico.js — Busca en la página los contenedores atómicos que conservan los
 * estilos de base de Elementor 4: padding 10px (e-flexbox, e-grid, e-div-block, contenido del
 * vídeo de fondo) y min-width 30px en bloques vacíos. Debe devolver total 0 en cada página.
 *
 * PROPÓSITO (síntoma → causa)
 *   Bloques 20 px más altos que el prototipo, cabeceras estiradas, iconos de 16 px que ocupan 30:
 *   `.elementor .e-flexbox-base` (especificidad 0,2,0) trae padding:10px y flex-direction:row,
 *   `.e-con` trae width:100%, un e-div-block o e-flexbox vacío trae min-width:30px, y un
 *   `dimensions` con solo inline-* deja los 10px verticales. Toda clase de contenedor debe fijar
 *   su padding (0 si no lleva) y los bloques vacíos min-width 0.
 *
 * USO
 *   Pega el archivo en la consola de la página de WordPress (o en la herramienta de JS del
 *   navegador / page.evaluate()). Mide a 1440 y a 375. Devuelve { total, bad[], vacios30[] }:
 *   cada entrada con el documento (data-elementor-id), las clases del proyecto y los lados.
 *
 * QUÉ EDITAR
 *   PREFIJO (<PREFIJO>, «pfx» por defecto) para mostrar solo las clases del proyecto.
 *
 * ESTADO
 *   Núcleo probado en HomeMaker (la auditoría de 10px que se pasó página a página). Generalizado, no
 *   probado fuera de HomeMaker: la lista de bloques vacíos con min-width 30px.
 *
 * RIESGOS
 *   Ninguno (solo lee). Un padding de 10px intencionado también sale: compruébalo contra el spec.
 */
(async () => {
  const PREFIJO = 'pfx';
  await new Promise((r) => setTimeout(r, 3500));
  const clases = (e) => [...e.classList].filter((c) => c.startsWith(PREFIJO + '-')).join(' ') || ('data-' + PREFIJO + '=' + (e.dataset[PREFIJO] || '—'));
  const bad = [];
  const vacios30 = [];
  document.querySelectorAll('[data-elementor-id] .e-flexbox-base, [data-elementor-id] .e-grid-base, [data-elementor-id] .e-div-block-base, [data-elementor-id] .e-background-video-content-base').forEach((e) => {
    const s = getComputedStyle(e);
    const sides = ['Top', 'Right', 'Bottom', 'Left'].filter((k) => s['padding' + k] === '10px');
    const doc = e.closest('[data-elementor-id]').dataset.elementorId;
    if (sides.length) bad.push({ doc, cls: clases(e), tipo: e.dataset.eType || e.dataset.element_type, sides: sides.join('') });
    if (!e.children.length && s.minWidth === '30px') vacios30.push({ doc, cls: clases(e), ancho: Math.round(e.getBoundingClientRect().width) });
  });
  const out = JSON.stringify({ ancho: innerWidth, total: bad.length, bad: bad.slice(0, 20), vacios30: vacios30.slice(0, 20) });
  console.log(out);
  return out;
})();
