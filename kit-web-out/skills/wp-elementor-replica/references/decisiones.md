# Decisiones que se le presentan al usuario

Qué decide el usuario, qué decide Claude (y lo declara) y cómo se presenta un plan con
decisiones. El catálogo de §3 trae, para cada decisión que salió en el caso de referencia,
las opciones, la recomendada por defecto y el porqué. Si el proyecto nuevo no tiene la
situación, sáltala; si tiene una parecida, presenta la decisión igual.

## 1. Quién decide qué

| Decide el usuario (se pregunta) | Decide Claude y lo declara | Se hace sin preguntar |
|---|---|---|
| Objetivo y alcance; lo que cuesta dinero (plugins, APIs, licencias) | Enfoque técnico dentro de lo aprobado (p. ej. formulario de Pro en vez de `e-form`) | Construcción idempotente por CLI en staging |
| Contenido del cliente: claims, cifras, contactos, copy que no está en el prototipo | Correcciones de defectos funcionales del prototipo (enlaces muertos, entradas que no aparecen, accesibilidad) | Arreglos de fallos confirmados en una revisión (p. ej. renombrar un menú conservando su slug) |
| Cambios visibles respecto al diseño aprobado, incluidos colores para contraste | Diferencias deliberadas de medida (breakpoints de Elementor frente a los del prototipo) | Comprobaciones de solo lectura, sondas y pruebas en seco |
| Plugins: instalar, activar, desactivar, quitar | Nombres de clases, estructura de scripts, orden de construcción | Medición y revisión adversarial |
| Borrados, envíos reales de correo, DNS, lanzamiento, indexación | | |
| Seguridad: cuentas desconocidas, rotación de credenciales, procedencia de plugins | | |

Todo lo de la columna central se cuenta en el informe como «Una decisión mía: …» o «Dos
cosas que decidí yo y conviene que confirmes».

## 2. Cómo se presentan

### 2.1 Plan con decisiones (rondas grandes y fases críticas)
- Un documento compartible (plantilla
  [`../plantillas/plan-con-decisiones.md`](../plantillas/plan-con-decisiones.md)),
  agrupado por página o por bloque y clasificado en **ejecutable ya / bloqueado por
  material / necesita tu decisión**. En el caso, una ronda de 36 ajustes salió 18 / 12 / 6.
- Arriba, **«lo que más pesa»**: contradicciones con rondas anteriores, adjuntos que faltan,
  dependencias (una sesión de fotos) y efectos colaterales que nadie pidió («si ocultamos
  Tiendas quedan siete botones apuntando a una página que ya no existe»; «el botón del pie
  es global»).
- Cierre con la condición de arranque: «Cuando me confirmes los puntos del bloque final,
  arranco por la fase 0» (la primera del plan, no la F0 del playbook).
- Mientras el usuario decide, avanza en lo que no depende de esas decisiones.

### 2.2 Preguntas con AskUserQuestion
- Agrupa en una sola llamada las decisiones que bloquean lo mismo (en la migración del caso
  fueron cuatro preguntas juntas).
- 2–3 opciones por pregunta, cada una con su **consecuencia concreta** (qué pasa, qué cuesta,
  qué se pierde), no con una etiqueta abstracta.
- La recomendada va **primera** y su etiqueta termina en **«(Recomendado)»**. Ejemplos
  reales: «Quitar ambos (Recomendado)» (el optimizador y el conector de IA del hosting de
  origen), «Arreglar DNS (Recomendado)», «Ya, detrás de la
  cortina (Recomendado)». En la fase de prototipo: «Respaldar y reemplazar / Preview antes
  de cambiar / Solo respaldar por ahora»; «SMTP de Microsoft 365 / Agregar el servidor al
  SPF / …».
- El usuario responde rápido y a menudo por número («1. … 2. … de resto, todo aprobado»):
  numera siempre.

### 2.3 Runbook con decisiones numeradas (migración y lanzamiento)
- Cada decisión Dn lleva opciones, **recomendación** y «qué cambia en el runbook» según lo
  que se elija. Por defecto, el runbook sigue la recomendada.
- Al principio, «En una pantalla»: origen, destino, lo intocable, método, caída para el
  público, ventana y **qué bloquea el lanzamiento (y no el cambio)**.
- **Nada se ejecuta hasta cerrar las decisiones que lo bloquean**, y cada paso marcado
  `[ESCRIBE]` necesita el OK explícito del usuario en el chat.

### 2.4 Recomendar con coste y dueño
- Fórmula: «Mi recomendación es X; el coste es Y; es tu llamada». Si implica abandonar algo
  que el usuario pagó (un tema premium), decide él.
- Responde primero a la pregunta literal con evidencia («¿V4 exige Hello?» → no, y así lo
  comprobé) y después da la recomendación con su motivo real.
- Si cambias de recomendación, di qué argumento cayó (en el caso: se recomendó V3 «por el
  ecosistema de plantillas» y, tras volcar el esquema de V4, se pasó a V4 explicando por
  qué).

### 2.5 Cuando el usuario elige otra cosa
Se acata sin reabrir el tema, se deja escrito en la memoria el riesgo que queda y cómo se
mitiga, y se avisa cuando ese riesgo toque una tarea. Ejemplos del caso: se recomendó
TranslatePress y el usuario ya tenía Polylang Pro (se usó Polylang Pro); se recomendó
comprar licencias oficiales y el usuario eligió «Seguir con los actuales» (quedó en memoria
la regla de no usar «Insertar plantilla»).

### 2.6 Hallazgos de paso
Lo que afecta al contenido del cliente se señala con evidencia y opciones («Dime si la
quito, la sustituyo… o la dejas para el shooting»), sin arreglarlo por iniciativa propia.

## 3. Catálogo

### 3.1 Arranque (F0)
| Decisión | Opciones | Recomendado | Por qué |
|---|---|---|---|
| Objetivo del proyecto | réplica exacta del prototipo · diseño nuevo con plantillas | réplica exacta, todo editable, sin plantillas importadas | es lo que el cliente aprobó; las plantillas traen su propio sistema de estilos |
| Para qué es el servidor | despliegue del prototipo · WordPress para reconstruir · otro | preguntarlo siempre | en el caso era un WordPress vacío preparado, y eso cambió el plan |
| Plugins de pago | los instala el usuario · los instala Claude | los instala el usuario | son sus cuentas y sus licencias |
| Procedencia de premium modificados (*nulled*) | licencias oficiales + ZIP limpios de la misma versión · seguir con los actuales | licencias oficiales, como cambio aparte y nunca mezclado con una migración | falsean la licencia, pueden desviar la biblioteca de plantillas a servidores ajenos y no reciben actualizaciones. Imprescindibles en este stack: Elementor Pro (Theme Builder, formularios) y Polylang Pro (slugs traducidos) |
| Funcionalidad con coste sin aprobar (API de mapas, localizador) | integrarla · versión informativa como en el prototipo | informativa, publicada, fuera de menús si el cliente lo pide | depende de una cotización; la estructura queda lista para el embed real |

### 3.2 Base técnica (F1)
| Decisión | Opciones | Recomendado | Por qué |
|---|---|---|---|
| Editor | V4 atómico · V3 clásico | V4 si el diseño viene de un prototipo con utilidades CSS y no se importan plantillas | clases globales y variables mapean 1:1 con Tailwind y el validador dice la prop exacta que falla; V3 son miles de claves por widget y breakpoint |
| Tema | Hello + hijo · mantener el tema instalado | Hello + hijo | una sola fuente de verdad de estilos; con dos sistemas (tema + Elementor) el cliente cambia el color en el sitio equivocado. Coste: abandonar el tema anterior |
| Menú | menú clásico (`nav-menu` de Pro) · menú atómico | clásico, editable en Apariencia › Menús | el cliente lo edita sin tocar la plantilla. Coste: colapso solo en breakpoints concretos y estilos desde el hijo |
| Plugin de idiomas | el instalado · otro | el que ya esté instalado (en el caso, Polylang Pro) | seguir lo que el usuario pagó; decidirlo antes de maquetar porque cambia el Theme Builder |
| Esquema de URL | idioma base sin prefijo y el segundo en `/<IDIOMA_2>/` · otros | base sin prefijo | conserva las URL del idioma principal y sus 301 |
| Plantillas del Theme Builder por idioma | una por idioma enlazada · una sola | una por idioma, condiciones solo en la base | sin eso, cabecera, pie y fichas salen en el idioma base |
| Slugs de página | los del prototipo · traducidos al idioma base | los del prototipo | sigue valiendo su mapa 301 |
| Plugins residuales del tema anterior | desactivarlos · dejarlos | desactivarlos, lo hace el usuario comprobando que nada cambia | en el caso, uno reescribía el `?ver=` de las hojas del hijo |
| Subida de SVG | habilitarla · SVG desde el tema hijo | desde el hijo (`e-svg` lee un archivo del propio sitio) | habilitar subidas SVG es una decisión de seguridad del cliente |

### 3.3 Diseño y contenido (F2–F8)
| Decisión | Opciones | Recomendado | Por qué |
|---|---|---|---|
| Colores de marca que no pasan AA | cambiarlos · replicar y listar | replicar y listar, con captura comparativa si se propone un cambio | la paleta es del cliente |
| Claims, cifras nutricionales, contactos | lo que diga el cliente | se replican y las contradicciones se muestran con evidencia y una redacción acotada | son responsabilidad legal y de marca del cliente |
| Rellenos antes de producción («demo», «pendiente», Lorem, contactos inventados, redes sin URL) | retirar · publicar | retirar | pedido explícito del usuario en el caso |
| Copy que no está en el prototipo | propuesta de Claude · esperar al cliente | proponer marcado como «propuesta» y confirmar con el cliente | no se inventa voz de marca |
| Dos variantes de diseño posibles | elegir una · enseñar ambas | enseñar ambas con una vista previa por parámetro | decide el usuario viendo |
| Tipografía, si hay que proponerla | desde las referencias del cliente · desde la guía genérica | desde la auditoría de los sitios que admira el cliente | una serif editorial sobre una grotesca neutra fue rechazada por «parecer generada por IA» |
| Fotos o vídeo | reales (banco o cliente) · generados por IA | reales | lo generado «se ve falso» y falla con personas y texto impreso |
| Contenido del cliente que falta | esperar · retirar lo vacío | retirar y anotar | en el caso se retiraron recetas sin contenido en vez de esperarlas |

### 3.4 Formularios y correo (F7)
| Decisión | Opciones | Recomendado | Por qué |
|---|---|---|---|
| Destinatarios de cada formulario y de los avisos del sistema | los que diga el cliente | inventario completo, uno por uno, en cada idioma | cada plugin guarda sus propios destinatarios |
| Durante el desarrollo | destinatarios reales · solo el buzón del usuario | ofrecer que lleguen solo al usuario | cualquier envío válido llega al cliente |
| Salida del correo | sendmail del hosting · SMTP · transaccional | sendmail si entrega y el SPF del dominio del From autoriza al servidor | añadir SMTP mete una clave en el servidor para un problema que puede no existir |
| Prueba real de envío | sí · no | solo al buzón del usuario y con su permiso | nunca un envío que llegue al cliente |
| `admin_email` | por script · por el panel | por script con el filtro `send_site_admin_email_change_email` (fases.md, F7), solo si el usuario lo pide | el panel deja el cambio pendiente de confirmación por correo y avisa a la dirección vieja |

### 3.5 Segundo idioma (F9)
| Decisión | Opciones | Recomendado | Por qué |
|---|---|---|---|
| Slugs del segundo idioma | compartidos · traducidos | páginas y CPT con el mismo slug, base del CPT traducida como cadena y términos con slug propio | así lo resolvió el caso; `pll_insert_post` genera `-2` si no se cuida |
| Texto alternativo | meta propia en el mismo adjunto · traducir la Biblioteca | meta propia | traducir medios duplica adjuntos y complica al cliente |
| Idioma del correo interno de los formularios | el del cliente · el del visitante | el del cliente | lo lee el equipo del cliente |
| Nombre del sitio | traducirlo · no | no se traduce: es la marca | alimenta los títulos de todas las páginas |

### 3.6 404, acceso y «Próximamente» (F10)
| Decisión | Opciones | Recomendado | Por qué |
|---|---|---|---|
| URL de acceso propia | slug que da el usuario | la que diga el usuario; no se publica en ningún sitio | todo acceso de administrador empieza en `/<LOGIN_SLUG>` |
| Cortina en staging | `coming_soon` · `maintenance` | `coming_soon` | staging sin historial: 200 + noindex no hace daño |
| Cuándo encender la cortina | ya · cuando la sesión de rendimiento termine de medir | cuando llegue su «listo» | con la cortina encendida, PageSpeed mide la cortina |

### 3.7 SEO, rendimiento y entrega (F11)
| Decisión | Opciones | Recomendado | Por qué |
|---|---|---|---|
| Cambios visibles para ganar puntos | aplicarlos · presentarlos | presentarlos con su coste; aplicar solo con evidencia | el diseño no se toca sin permiso. En el caso, diferir el vídeo del hero se aplicó con evidencia (PSI 84 → 94) |
| Accesibilidad 100 cambiando colores | sí · quedarse en 93–97 | decisión del cliente; por defecto no se tocan | cambia la marca |
| Perfiles sociales y contacto en el schema | publicarlos · esperar | esperar a que el cliente los confirme | datos del cliente |
| Contenido del documento de entrega | licencias, hosting, firmante, URL de acceso | lo que pida el usuario («solo lo indispensable»); la contraseña, siempre campo vacío | el documento se comparte e imprime |

### 3.8 Migración (F12): las decisiones del dueño
La lista canónica, con «qué cambia en el runbook», está en
[runbook.md](../../wp-migracion/references/runbook.md) §1; aquí va la misma numeración,
D1–D16, con el porqué. Cita siempre estos números. Los documentos del caso usaban otros: la
primera versión del plan tenía D1–D11 (ejemplo en `../plantillas/plan-con-decisiones.md`) y
el runbook final D1–D17, porque el optimizador y el conector de IA del hosting de origen se
decidieron por separado; desde ahí todo se desplaza uno (su D8, el correo, es aquí D7).
Bloquean el **lanzamiento**, no el cambio de servidor: D1 y D2 (OK de SEO), D5 (plugins) y
D7 (correo).

| # | Decisión | Recomendado | Por qué |
|---|---|---|---|
| D1 | Cortina en el dominio real | cortina estática 503 con llave de revisión durante el cambio y la verificación; si el lanzamiento se retrasa y quieren encenderla desde wp-admin, modo Mantenimiento de Elementor (503 + `Retry-After: 600`). **Nunca** `coming_soon` en un dominio con historial | `coming_soon` responde 200 + `noindex` en todo, `robots.txt` incluido, y desindexa. El 503, días y no semanas |
| D2 | Mapa 301/410 del sitio anterior | el de SEO, sacado de la BD del sitio viejo (410 para lo que no tiene equivalente); si no llega a tiempo, uno provisional que se sustituye antes de lanzar | el prototipo solo conoce sus páginas visibles |
| D3 | Plugins propios del hosting de origen (optimizador, conector de IA) | no migrarlos; sus opciones con claves se borran **en la copia** | fuera de su hosting no hacen nada útil, pueden escribir en `.htaccess` y llevan claves de otra cuenta |
| D4 | Plugin de seguridad del origen y URL de acceso propia | mantenerlo si, revisado su código, funciona fuera; si no viaja, decidir antes la entrada al admin y corregir el documento de entrega | da la URL de acceso que promete la entrega. En el caso se conservó: funciona fuera de su hosting |
| D5 | Premium de procedencia dudosa | licencias oficiales + ZIP limpios de la misma versión, como cambio aparte después de migrar (ver §3.1) | primero se migra lo que ya está verificado; mientras tanto, nadie usa «Insertar plantilla» |
| D6 | Restos de temas o plugins anteriores | no tocarlos durante la migración; retirarlos después con comprobación visual | un cambio cada vez |
| D7 | Correo del dominio desde el servidor nuevo | DNS sin guardar credenciales: SPF que **añade** la IP del servidor al existente, DKIM sugerido por el panel y enrutamiento remoto. Alternativas: SMTP (la clave la pone el usuario) o transaccional | bloquea el lanzamiento; mientras tanto los envíos quedan en Elementor › Envíos |
| D8 | Con o sin `www` | lo que usaba el sitio anterior, comprobado en su BD | conserva la autoridad acumulada |
| D9 | PHP y límites | la misma rama que staging, fijada para el dominio; `.user.ini` con `max_input_vars = 3000`; subir de versión aparte | un dominio adicional hereda la versión del sitio padre |
| D10 | Copias WebP que generaba el hosting de origen | migrarlas y servirlas por `Accept` en `.htaccess`; decidir conversor para las nuevas | las servía la infraestructura, no WordPress |
| D11 | Caché de página en el destino | la de la sesión de rendimiento: WP Super Cache en modo mod_rewrite (o la del plugin del hosting) instalada antes o justo al lanzar; la decide el usuario | sin caché, el TTFB del caso fue de 0,7–1,1 s; allí quedó como decisión pendiente del usuario |
| D12 | Staging | congelado 30 días, con cortina y `blog_public=0`; lo retira el usuario | referencia y red de seguridad |
| D13 | Copia local de la exportación | carpeta 700 fuera de iCloud y Documentos; la borra el usuario a los 30 días | lleva datos personales |
| D14 | Ventana y congelación | 2–3 h acordadas, congelación anunciada al cliente y a las sesiones paralelas, administradores desconocidos confirmados | cualquier cambio posterior a la exportación se pierde |
| D15 | Tareas programadas | la cortina deja pasar `wp-cron.php`; cron del sistema opcional después | una cortina estática bloquea el cron |
| D16 | Qué se conserva | el respaldo de lo que había publicado **no se borra nunca**; la BD antigua se decide aparte | la vuelta atrás depende de él |

Además: **momento** («Ya, detrás de la cortina (Recomendado)» fue la elegida) y
**método** para sustituir lo publicado («Respaldar y reemplazar» frente a «Preview antes de
cambiar» o «Solo respaldar por ahora»).

### 3.9 Lanzamiento y cierre
| Decisión | Recomendado | Por qué |
|---|---|---|
| Cuándo lanzar | en cuanto pasen las puertas: correo comprobado, OK de SEO al mapa 301 y a `blog_public=1`, plugins decididos | un 503 largo en un dominio con historial perjudica |
| Indexación | `blog_public=1` **antes** de retirar la cortina, solo en producción; IndexNow y reindexado solo en producción | al revés hay un intervalo con el sitio visible y en `noindex` |
| Limpieza (volcados, zips, BD antigua, staging) | la hace el usuario, con fecha, uno por uno | ningún borrado lo hace el agente |
| Administrador desconocido | preguntar, sin borrarlo ni degradarlo | puede haberlo creado el propio usuario desde otra sesión |
| Credenciales expuestas (p. ej. por un subagente) | decir qué se expuso y recomendar rotarlas | no se pueden «desimprimir» |

## 4. Lo que no se pregunta

- Si la tarea está dentro de lo aprobado y es reversible, se hace, se verifica y se
  informa. No hagas preguntas innecesarias: el usuario da órdenes cortas y encadenadas
  («Sigue», «Continúa con X», «Status y continúa») y espera ejecución y un resumen.
- Lo que no bloquea se adelanta mientras se espera una decisión («avancemos con lo que
  puedas ejecutar y no sea bloqueante»). Lo que falta lo pide él al cliente.
- Lo que no hiciste también se dice: «No desplegué todavía, dime cuándo».
