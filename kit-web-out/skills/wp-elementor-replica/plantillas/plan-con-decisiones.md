# Plantilla · plan con decisiones

Lo que se presenta al usuario **antes** de una fase que no se puede deshacer a la ligera
(decisiones de base, ronda grande de ajustes, migración, lanzamiento). Regla del caso de
referencia: **no se ejecuta nada hasta cerrar las decisiones que lo bloquean**, y mientras el
usuario decide se avanza en lo que no depende de ellas.

- Variante **A** — plan de ronda o de fase (F1, F8, cualquier lote de ajustes).
- Variante **B** — plan de fase crítica con decisiones numeradas y puertas GO/NO-GO
  (F12: migración y lanzamiento). El runbook técnico completo está en
  [`wp-migracion/references/runbook.md`](../../wp-migracion/references/runbook.md); esta
  plantilla es la capa que el usuario lee y decide.
- Catálogo de decisiones con la recomendación por defecto y su porqué:
  [`decisiones.md`](../references/decisiones.md) §3.
- **Migración y lanzamiento: el catálogo canónico es el D1–D16 del runbook**
  ([`runbook.md`](../../wp-migracion/references/runbook.md) §1: opciones, recomendación y qué
  cambia en cada paso). [`decisiones.md`](../references/decisiones.md) §3.8 es su resumen, con
  la misma numeración y el porqué de cada una. Usa el mismo número para la misma decisión y
  numera desde D17 lo que añada el proyecto. *Por qué:* el usuario responde por número («D7: a») y el runbook ejecuta por
  número; si el plan renumera, una respuesta acaba aplicada a la decisión equivocada.

## Cómo se presenta

1. **Documento compartible** (el usuario lo reenvía a su equipo o al cliente): publícalo como
   página o documento y dale el enlace; en el chat, solo el resumen y las preguntas.
2. **Preguntas con AskUserQuestion**, agrupando en una llamada las que bloquean lo mismo (en
   la migración del caso fueron cuatro juntas). 2–3 opciones por pregunta, cada una con su
   **consecuencia concreta**; la recomendada va primera y su etiqueta termina en
   «(Recomendado)».
3. **Numera siempre**: el usuario responde por número («1. … 2. … de resto, todo aprobado»).
4. Si elige contra la recomendación, se acata, se anota en la memoria el riesgo que queda y
   cómo se mitiga, y se recuerda cuando ese riesgo toque una tarea.
5. Nada de secretos en el plan: ni hosts, ni usuarios, ni IPs de otros clientes, ni la llave de
   una cortina. Marcadores o alias.

---

## Variante A · Plan de ronda o de fase

```markdown
# <Proyecto> · plan de <fase o ronda> (<fecha>)

**Estado:** propuesta. No he tocado nada todavía.

## Lo que más pesa
- <contradicción con una ronda anterior, con la cita de cada una>
- <adjunto o material que falta, y de quién>
- <dependencia externa (sesión de fotos, cotización, DNS)>
- <efecto colateral que nadie pidió: «si ocultamos X quedan N botones apuntando a una página
  que ya no existe»; «el botón del pie es global: cambia en todas las páginas»>

## Resumen
| | Ejecutable ya | Bloqueado por material | Necesita tu decisión |
|---|---|---|---|
| <Página 1> | n | n | n |
| **Total** | **n** | **n** | **n** |

## <Página 1>
| # | Ajuste | Estado | Qué haré / qué falta | Verificación |
|---|---|---|---|---|
| 1.1 | <…> | ejecutable ya | <…> | medido a 1440/375 |
| 1.2 | <…> | bloqueado | falta <material> de <quién> | — |
| 1.3 | <…> | decisión | ver D2 | — |

## Decisiones
**D1 · <título>**
- Contexto (medido, no supuesto): <…>
- Opciones: (a) <…> → <consecuencia>. (b) <…> → <consecuencia>.
- **Recomendación: (a)**, porque <…>. Coste: <…>. Es tu llamada.

## Qué haré mientras decides
- <lo que no depende de ninguna decisión>

Cuando me confirmes D1–Dn, arranco por <paso o página>.
```

*Referencia de tamaño:* una ronda del caso tuvo 36 ajustes = 18 ejecutables ya, 12 bloqueados
por material y 6 decisiones.

---

## Variante B · Plan de fase crítica (migración, lanzamiento)

```markdown
# <Proyecto> · <migración de staging a producción | lanzamiento> (<fecha>)

**Estado:** listo para ejecutar cuando cierres las decisiones del §1. **No se ha ejecutado
nada.** Para prepararlo solo hice lecturas (§10).
**Fuentes:** auditoría de origen, auditoría de destino, aportes de la sesión de SEO.

## §0 · En una pantalla
| | |
|---|---|
| Origen | `<SSH_ALIAS>` · `<WP_PATH>` · versiones · tamaños de BD y `wp-content` |
| Destino | `<SSH_ALIAS_PROD>` · docroot **comprobado** `<WP_PATH_PROD>` · PHP web/CLI · MySQL |
| Intocable | `<otros sitios de la cuenta, sus BD, su .htaccess>` · ningún `wp` sin `--path` |
| Origen durante la operación | solo lectura; la exportación sale por la salida estándar |
| Método | obra fuera del docroot + cambio de carpeta (dos `mv`) detrás de la cortina 503 |
| Lo publicado hoy | pasa entero a respaldo. **No se borra nunca** |
| Caída para el público | <0 min: ve la misma cortina antes, durante y después> |
| Ventana | preparación <x> · lanzamiento <x> |
| Bloquea el lanzamiento, no el cambio | <correo, OK de SEO, licencias> |

## §1 · Decisiones antes de empezar
Por defecto el plan sigue la opción recomendada.

<!-- Números del catálogo D1–D16 del runbook; las decisiones propias del proyecto, desde D17. -->
### D1 · <título> (dueño: <usuario | sesión SEO | cliente>) · bloquea: <el cambio | el lanzamiento>
- Hoy (comprobado): <dato medido y cómo>.
- (a) <opción> → <consecuencia>.
- (b) <opción> → <consecuencia>.
- (c) <opción a evitar> → <por qué no>.
- **Recomendación: (a).** <porqué en una o dos líneas>.
- Qué cambia en el plan si eliges (b): <pasos que se añaden o se quitan>.

## §2 · Puertas GO / NO-GO
### Puerta A · antes de empezar
- [ ] Decisiones del §1 cerradas; para las que bloquean el lanzamiento hay dueño y fecha.
- [ ] Congelación anunciada al cliente y a las sesiones paralelas; <0> cambios en 30 min.
- [ ] Carpetas de trabajo y BD nuevas no existen todavía; cuota libre suficiente.
**NO-GO:** no se empieza.
### Puerta B · antes del cambio · Puerta C · justo después · Puerta D · lanzamiento
- [ ] <casilla medible con el valor esperado (p. ej. «0 coincidencias del dominio viejo»)>
**NO-GO en C:** vuelta atrás R1 de inmediato.

## §3 · Convenciones
- `[lectura]` no cambia nada · `[ESCRIBE en X]` cambia algo en X · `[MANUAL usuario]`.
- **Cada paso `[ESCRIBE]` necesita tu OK explícito en el chat.**
- Ningún paso borra datos del cliente ni lo publicado; la limpieza final es tuya (§4, último
  paso), con fecha.

## §4 · Pasos
### Paso n · <título> [<marca>] · <duración>
<bloque de comandos autónomo>
**Salida esperada:** <…> · **Si falla:** <…>

## §5 · Vuelta atrás
| Caso | Cómo | Tiempo |
|---|---|---|
| R1 · volver a lo publicado | dos `mv` a la inversa | < 1 min |
| R2 · fallo parcial tras lanzar | cortina de mantenimiento en un segundo | segundos |

## §6 · Tiempos y caída
| Tramo | Duración | Qué ve el público |
|---|---|---|

## §7 · Qué tiene que adaptar la sesión paralela
## §8 · Riesgos que quedan (aceptados o pendientes de decisión)
## §9 · Cambios respecto a la versión anterior del plan (y la evidencia)
## §10 · Comprobaciones de solo lectura hechas (evidencia; sin secretos)
```

### Ejemplo del caso de referencia (saneado, numeración histórica)

Primera versión del plan de migración del caso, con **su** numeración D1–D11 (la versión final
tuvo D1–D17 porque el optimizador y el conector de IA del hosting de origen se decidieron por
separado). Es solo un ejemplo histórico: en un proyecto nuevo numera con el catálogo D1–D16 del
runbook. La columna «Catálogo» da la equivalencia.

| # | Catálogo | Decisión | Opciones | Recomendada |
|---|---|---|---|---|
| D1 | D1 | Cortina durante y después del cambio (dueña: sesión SEO) | (a) estática 503 con llave durante el cambio y la verificación, y después Mantenimiento de Elementor (503) · (b) Mantenimiento de Elementor desde el cambio (solo se verifica con sesión) · (c) «Próximamente» de Elementor: **no** (200 + noindex en todo, `robots.txt` incluido) | (a) |
| D2 | D2 | Mapa 301 del sitio viejo (dueña: sesión SEO) | (a) el del prototipo · (b) el nuevo de SEO, sacado de la BD vieja, con 410 | (b) si llega a tiempo; si no, (a) y se sustituye antes de lanzar |
| D3 | D3 | Optimizador del hosting de origen | quitar · dejar desactivado · dejar activo | quitarlo: fuera de su hosting no hace nada útil |
| D4 | D4 | Plugin de seguridad del origen | mantenerlo (URL de acceso propia, límite de intentos) · sustituirlo | mantenerlo, tras comprobar en su código que funciona fuera |
| D5 | D3 | Conector de IA del origen | no migrarlo (se borran sus opciones con claves en la copia) · mantenerlo | no migrarlo |
| D6 | D5 | Premium modificados | licencias oficiales + ZIP limpios · seguir así | licencias, como cambio aparte; mientras, nadie usa «Insertar plantilla» |
| D7 | D6 | Restos de un tema anterior | retirarlos después · dejarlos | no tocarlos durante la migración |
| D8 | D7 | Correo desde el servidor nuevo | (a) DNS: DKIM sugerido por el panel, SPF que añade `ip4:<IP_SERVIDOR>` al existente y enrutamiento remoto, sin credenciales · (b) SMTP (la clave la pone el usuario) · (c) transaccional | (a). No bloquea el cambio, **sí el lanzamiento** |
| D9 | D9 | PHP y límites | fijar la rama de staging para el dominio · `max_input_vars = 3000` | sí a las dos |
| D10 | D10 | Copias WebP que generaba el hosting de origen | migrarlas y servirlas por `Accept` · no | migrarlas |
| D11 | D11 | Caché de página del destino | decidir con rendimiento tras el lanzamiento | tras el lanzamiento, midiendo |

Respuestas del usuario (AskUserQuestion, cuatro preguntas en una llamada): «Quitar ambos
(Recomendado)» para el optimizador y el conector de IA del hosting (el plugin de seguridad se
mantuvo, D4: da la URL de acceso propia y funciona fuera de ese hosting); «Seguir con los actuales» para los premium
modificados, **contra la recomendación** (quedó en memoria la regla de no usar «Insertar
plantilla»); «Arreglar DNS (Recomendado)» para el correo; «Ya, detrás de la cortina
(Recomendado)» para el momento.

## Después de decidir

- Actualiza el plan con lo elegido (§9 «cambios») y la memoria del proyecto.
- Ejecuta en orden, pidiendo el OK de cada `[ESCRIBE]`, y reporta cada puerta con sus
  casillas.
- Al terminar, informe con: qué se hizo, cómo se comprobó (sin mandar nada real), lo que
  **no** se tocó, cómo se deshace, lo que queda del usuario (DNS en un archivo con los valores
  listos para copiar, limpieza con fechas) y los supuestos que aún no son ciertos.
