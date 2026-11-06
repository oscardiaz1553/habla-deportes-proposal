# Respaldos y vuelta atrás (F12)

El cambio a producción se diseña para poder deshacerlo en menos de un minuto, antes o
después de lanzar. Eso solo es posible si el sitio anterior se conserva entero y en el
mismo disco, si cada cambio posterior deja su copia y si nadie borra nada por el camino.

---

## Qué se respalda y dónde

| Qué | Dónde | Cuándo se crea | Se borra |
|---|---|---|---|
| Sitio anterior completo (prototipo estático u otro WP), con su `.htaccess`, su cortina y sus permisos | `~/<PREFIJO>-respaldos/<docroot>.prototipo-<TS>` | en el cambio (F8), por el propio `mv` | **nunca** |
| Marca del último cambio | `~/<PREFIJO>-respaldos/ULTIMO-CAMBIO` (contiene `<TS>`) | en F8 | no |
| `.htaccess` antes de cada cambio posterior | `~/<PREFIJO>-respaldos/htaccess.<momento>-<TS>` (`antes-php`, `con-cortina`, `antes-del-lanzamiento` y cualquier edición a mano) | con `cp -p` antes de tocarlo | no |
| DNS y correo antes de D7 | `dns-antes.txt` en la carpeta local; `~/<PREFIJO>-respaldos/dns-correo-antes-<TS>.txt` en el servidor | F0 y antes de cambiar el correo | no |
| BD y `wp-content` tal como se migraron | carpeta local `L` (`db.sql`, `wpcontent.tgz`) y `~/<PREFIJO>-migracion/` | F1–F2 | por el usuario, a los 7–30 días |
| Copia antes de un cambio de plugins o licencias | `wp db export` a `~/<PREFIJO>-respaldos/` + tar de `wp-content/plugins` | antes de cada cambio aparte | por el usuario |
| BD del sitio anterior (si era WordPress) | en el servidor, intacta; copia local si la hay | — | decisión aparte |

**Por qué en el mismo disco y con `mv`:** un `rename` dentro del mismo sistema de
archivos es instantáneo y atómico. Volver atrás son otros dos `rename`. Si el respaldo
estuviera en otro disco, cada vuelta atrás sería una copia de minutos con el sitio a
medias.

**Por qué la marca de tiempo en un archivo:** la vuelta atrás tiene que saber qué
carpeta restaurar sin que nadie la teclee a mano bajo presión.

---

## R1 · Volver al sitio anterior `[ESCRIBE en destino: dos mv]` · < 1 min

En cualquier momento después de F8, antes o después de lanzar. Script: modo `volver`
de `../scripts/cambio-carpetas.sh`.
```bash
ssh <SSH_ALIAS_DESTINO> 'bash -s' <<'EOF'
set -euo pipefail
D=<WP_PATH>; R=~/<PREFIJO>-respaldos; N=$(basename "$D")
TS=$(cat "$R/ULTIMO-CAMBIO"); P="$R/$N.prototipo-$TS"; X="$R/$N.wp-retirado-$(date +%Y%m%d-%H%M)"
test -d "$P" && test -d "$D" && test ! -e "$X" || { echo "No está el respaldo $P (o ya existe $X): NO-GO"; exit 1; }
mv "$D" "$X"
mv "$P" "$D" || { mv "$X" "$D"; echo "Falló el segundo mv: WP restaurado. NO-GO"; exit 1; }
cp -an "$X/.well-known/." "$D/.well-known/" || true  # retos de AutoSSL escritos mientras estuvo el WP (-n puede salir con 1 al saltar existentes)
stat -c '%U:%G %a %n' "$D"
EOF
curl -sS -o /dev/null -w '%{http_code}  /\n' https://<DOMINIO>/
```
**Por qué la guarda termina en `|| { …; exit 1; }`:** con `set -e`, en `test a && test b`
solo aborta el fallo del último `test`. Sin ella, si `ULTIMO-CAMBIO` apunta a un respaldo
que no existe, el primer `mv` retiraría el docroot y el segundo fallaría: dominio sin sitio.

**Lo esperado:** el docroot vuelve con los permisos originales (en el caso de referencia,
`usuario:nobody 750`) porque vuelve la carpeta exacta: su `.htaccess`, su cortina y sus
scripts. El público ve lo mismo que antes del cambio.

**Qué se conserva para reintentar:** la BD nueva y la carpeta `wp-retirado-*` quedan
intactas. Lo que se editó o se recibió después del lanzamiento (envíos de formularios)
está en esa BD: no se pierde. Para reintentar, se corrige y se repite F8 usando
`wp-retirado-*` como obra.

**No afecta a:** el origen (nunca se tocó) ni el sitio vecino.

### Cuándo volver atrás con R1

- La cortina no da 503 justo después del cambio (puerta C).
- `/.well-known` falla.
- Errores 500 que no se arreglan en 10 minutos.
- No se puede entrar al admin.
- El contenido sale roto.
- El público ve el sitio antes de tiempo.

---

## R2 · Fallo parcial después de lanzar `[ESCRIBE: una opción]` · segundos

Una página rota o un error puntual no justifican deshacer la migración. Se pone la
cortina en un segundo y se corrige detrás:
```bash
wp82 --path=<WP_PATH> option update elementor_maintenance_mode_mode maintenance   # 503 + Retry-After
# … corregir …
wp82 --path=<WP_PATH> option update elementor_maintenance_mode_mode ''
```
Al quitarla, purga la caché de página si la hay.

---

## R3 · Solo el `.htaccess`

```bash
cp -p ~/<PREFIJO>-respaldos/htaccess.<momento>-<TS> <WP_PATH>/.htaccess
```
Las copias se hacen antes de fijar el PHP, antes de retirar la cortina estática y antes
del lanzamiento. Ojo: si restauras una copia anterior a F9b, pierdes el bloque de PHP de
cPanel; vuelve a fijar la versión (o restaura una copia posterior).

---

## R4 · Versión de PHP

`uapi LangPHP php_set_vhost_versions version=inherit vhost=<DOMINIO>`, o MultiPHP
Manager › heredar `[MANUAL usuario]`.

---

## R5 · DNS y correo

- Restaurar los valores guardados en `dns-antes.txt`: SPF, DMARC y el DKIM `default`
  anteriores.
- Enrutamiento de correo otra vez en **Local** si así estaba.
- Comprobar con `dig` contra el NS autoritativo (`correo-dns.md`).

---

## R6 · Plugins o licencias cambiados después

- Reinstalar desde `wpcontent.tgz` de la carpeta local, que tiene los plugins tal como
  se migraron.
- O restaurar el `wp db export` hecho antes del cambio.

---

## Limpieza de los respaldos

No la hace el agente: se le presenta al usuario (tabla de `runbook.md` F13) y cada
borrado lleva su permiso explícito, en comandos separados. El respaldo del sitio
anterior no se borra nunca; `wp-retirado-*` (solo si hubo R1) cuando el lanzamiento
sea estable.
