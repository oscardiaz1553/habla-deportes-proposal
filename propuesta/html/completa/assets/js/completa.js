/* Habla Deportes — Edición completa. JS mínimo: menú, pestañas, carruseles, reproductor, encuesta, minuto en vivo. */
(function(){
  'use strict';
  var $=function(s,c){return (c||document).querySelector(s)},$$=function(s,c){return [].slice.call((c||document).querySelectorAll(s))};

  // Menú móvil
  var b=$('[data-burger]'),m=$('#mnav');
  if(b&&m)b.addEventListener('click',function(){var o=m.classList.toggle('is-open');b.setAttribute('aria-expanded',o);b.setAttribute('aria-label',o?'Cerrar menú':'Abrir menú')});
  // "Más" desplegable
  var more=$('[data-more]');
  if(more){var dd=more.nextElementSibling;
    more.addEventListener('click',function(e){e.stopPropagation();var o=dd.classList.toggle('is-open');more.setAttribute('aria-expanded',o)});
    document.addEventListener('click',function(){dd.classList.remove('is-open');more.setAttribute('aria-expanded','false')});
    document.addEventListener('keydown',function(e){if(e.key==='Escape'){dd.classList.remove('is-open');more.setAttribute('aria-expanded','false')}});}
  // Buscar (demo)
  var s=$('[data-search]');if(s)s.addEventListener('click',function(){var q=window.prompt('Buscar en Habla Deportes (demo):');if(q)window.alert('En producción: resultados de "'+q+'" por noticias, videos, jugadores y partidos.')});

  // Pestañas accesibles
  $$('[data-tabs]').forEach(function(root){
    var tabs=$$('[role=tab]',root),panels=$$('[role=tabpanel]',root);
    function sel(t){tabs.forEach(function(x,i){var on=x===t;x.setAttribute('aria-selected',on);x.tabIndex=on?0:-1;panels[i].hidden=!on})}
    tabs.forEach(function(t,i){
      t.addEventListener('click',function(){sel(t)});
      t.addEventListener('keydown',function(e){var k=e.key,n=null;
        if(k==='ArrowRight')n=tabs[(i+1)%tabs.length];if(k==='ArrowLeft')n=tabs[(i-1+tabs.length)%tabs.length];
        if(n){n.focus();sel(n);e.preventDefault()}});
    });
  });
  // Abrir pestaña por hash (#goleadores)
  if(location.hash){var t=$('[role=tab][aria-controls="'+location.hash.slice(1)+'"]');if(t)t.click()}

  // Carruseles con botones
  $$('[data-rail]').forEach(function(w){
    var r=$('.rail',w),p=$('[data-prev]',w),n=$('[data-next]',w);
    function step(){return Math.max(240,r.clientWidth*.8)}
    if(p)p.addEventListener('click',function(){r.scrollBy({left:-step(),behavior:'smooth'})});
    if(n)n.addEventListener('click',function(){r.scrollBy({left:step(),behavior:'smooth'})});
  });

  // Reproductor de radio (simulado)
  $$('[data-player]').forEach(function(p){
    var btn=$('.player__b',p),on=true;
    function set(){p.classList.toggle('is-paused',!on);btn.setAttribute('aria-pressed',on);btn.setAttribute('aria-label',on?'Pausar radio en vivo':'Reproducir radio en vivo');
      btn.innerHTML=on?'<svg class="ico" style="width:22px;height:22px"><use href="#i-pause"/></svg>':'<svg class="ico" style="width:22px;height:22px;margin-left:3px"><use href="#i-play"/></svg>'}
    btn.addEventListener('click',function(){on=!on;set()});set();
  });

  // Encuesta
  $$('[data-poll]').forEach(function(p){
    var opts=$$('.poll__o',p),base=[48,31,21];
    opts.forEach(function(o,i){o.addEventListener('click',function(){
      var tot=100,v=base.slice();v[i]+=3;var sum=v.reduce(function(a,b){return a+b},0);
      opts.forEach(function(x,j){var pc=Math.round(v[j]/sum*100);$('i',x).style.width=pc+'%';$('b',x).textContent=pc+'%'});
      o.classList.add('is-mine');p.classList.add('is-voted');var n=$('[data-poll-n]',p);if(n)n.textContent='12.847 votos · Gracias por votar';
    })});
  });

  // Newsletter (demo, sin envío)
  $$('[data-nl]').forEach(function(f){f.addEventListener('submit',function(e){e.preventDefault();var s=$('small',f.parentNode);if(s)s.textContent='¡Listo! Te escribiremos mañana a las 6:00 a.m. (demo, no se envió nada).';f.reset()})});

  // Minuto en vivo simulado
  $$('[data-minuto]').forEach(function(el){var mn=parseInt(el.getAttribute('data-minuto'),10);
    setInterval(function(){if(mn<90){mn++;el.textContent=mn+"'"}},6000)});

  // Filtros de chips (demo visual)
  $$('[data-filter]').forEach(function(g){$$('.chip',g).forEach(function(c){c.addEventListener('click',function(){$$('.chip',g).forEach(function(x){x.setAttribute('aria-pressed','false')});c.setAttribute('aria-pressed','true')})})});

  // Copiar enlace
  $$('[data-copy]').forEach(function(b){b.addEventListener('click',function(){var o=b.textContent;try{navigator.clipboard.writeText(location.href)}catch(e){}b.textContent='¡Enlace copiado!';setTimeout(function(){b.textContent=o},1800)})});

  // Marcador rotativo (una línea)
  $$('[data-sbd]').forEach(function(root){
    var ms=$$('.sbd__m',root),pg=$$('[data-sbd-pg] i',root),i=0,t;
    function show(){ms.forEach(function(m,k){m.classList.toggle('is-on',k===i)});pg.forEach(function(d,k){d.className=k===i?'on':''})}
    function go(n){i=(i+n+ms.length)%ms.length;show();reset()}
    function reset(){clearInterval(t);if(!matchMedia('(prefers-reduced-motion: reduce)').matches)t=setInterval(function(){go(1)},4500)}
    var pv=$('[data-sbd-prev]',root),nx=$('[data-sbd-next]',root);
    if(pv)pv.addEventListener('click',function(){go(-1)});if(nx)nx.addEventListener('click',function(){go(1)});
    root.addEventListener('mouseenter',function(){clearInterval(t)});root.addEventListener('mouseleave',reset);
    show();reset();
  });

  // Megamenús: solo se abren con hover (CSS); con teclado, al enfocar el botón

  // Banner de partidos (slider)
  $$('[data-mc]').forEach(function(root){
    var ss=$$('.mc__s',root),ds=$$('[data-mc-dots] i',root),i=0,t;
    function show(){ss.forEach(function(s,k){s.classList.toggle('is-on',k===i)});ds.forEach(function(d,k){d.className=k===i?'on':''})}
    function go(n){i=(i+n+ss.length)%ss.length;show();reset()}
    function reset(){clearInterval(t);if(!matchMedia('(prefers-reduced-motion: reduce)').matches)t=setInterval(function(){go(1)},6000)}
    var pv=$('[data-mc-prev]',root),nx=$('[data-mc-next]',root);
    if(pv)pv.addEventListener('click',function(){go(-1)});if(nx)nx.addEventListener('click',function(){go(1)});
    root.addEventListener('mouseenter',function(){clearInterval(t)});root.addEventListener('mouseleave',reset);
    show();reset();
  });

  // Tablas y zonas con desplazamiento horizontal: enfocables con teclado y con nombre
  $$('.tbl-wrap,.rx-grid-w,.tw').forEach(function(el){if(!el.hasAttribute('tabindex'))el.setAttribute('tabindex','0');el.setAttribute('role','region');if(!el.getAttribute('aria-label')){var c=el.querySelector('caption');el.setAttribute('aria-label',c?c.textContent.trim():'Tabla desplazable')}});
})();
