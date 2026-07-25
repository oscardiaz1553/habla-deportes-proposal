# Kit de Elementor — Habla Deportes

Identidad de marca (colores + tipografías) del prototipo aprobado, lista para
cargar en los **Ajustes del sitio** de Elementor del sitio `habla-deportes-final`.

## Archivos

| Archivo | Qué es |
|---|---|
| `elementor-kit-habla-deportes.zip` | **Kit importable** (formato v3.0). Es lo que se sube en Elementor → Herramientas → Importar. Contiene `manifest.json` + `site-settings.json` en la RAÍZ del zip (obligatorio: si estuvieran dentro de una carpeta, Elementor daría `invalid-zip-file`). |
| `elementor-kit-habla-deportes.json` | El `site-settings.json` suelto, por si hace falta inspeccionarlo o regenerar el zip. **Elementor NO importa un `.json` suelto desde la UI** (el drop-zone solo acepta `zip`). |
| `elementor-kit-habla-deportes-src/` | Fuente del zip (`manifest.json` + `site-settings.json`). Para regenerar: comprimir el CONTENIDO de esta carpeta, no la carpeta. |

Formato verificado contra el Elementor **4.2.0** instalado en el sitio final
(módulo `import-export-customization`, `FORMAT_VERSION = '3.0'`).

## Vía A — Importar el kit (recomendada)

1. WordPress → **Elementor → Herramientas → Importar/Exportar** (o *Importar kit*).
2. Sube `elementor-kit-habla-deportes.zip`.
3. En el asistente, marca **Ajustes del sitio / Site settings** (si no se marca, el
   importador no aplica nada: es el requisito de `should_import`).
4. Importar.

Qué hace exactamente:
- Aplica los 4 colores del sistema y los 4 estilos de tipografía del sistema.
- **Añade** los 7 colores personalizados (no reemplaza: si importas dos veces, se duplican).
- No toca tema, plantillas, contenido, plugins ni experimentos (van a `false` en el manifest).

Advertencias del propio Elementor:
- Al importar se **crea un kit nuevo** y se activa; el anterior queda guardado como
  `elementor_previous_kit` y se puede revertir desde Herramientas.
- Las fuentes de Google **no se instalan**: Elementor resuelve `Archivo` e `Inter`
  contra Google Fonts en tiempo de ejecución. Ambas existen en Google Fonts.

## Vía B — Pegado manual (respaldo garantizado)

Elementor → hamburguesa (☰) → **Ajustes del sitio**.

### Colores globales → Colores del sistema

| Nombre | Hex | Uso en el prototipo |
|---|---|---|
| Primary | `#17222E` | Texto primario / titulares (tinta con matiz navy) |
| Secondary | `#4A5866` | Texto secundario |
| Text | `#17222E` | Cuerpo |
| Accent | `#294D6E` | **Navy de marca** — CTAs, enlaces, barras de sección, botón play |

### Colores globales → Colores personalizados

| Nombre | Hex | Uso |
|---|---|---|
| Navy hover | `#335F86` | Hover del navy sobre fondo claro |
| Navy oscuro | `#1E3A55` | Pressed / titulares sobre claro |
| Azul interacción | `#3E729F` | Azul secundario más brillante (enlaces alternos) |
| Rojo vivo | `#D20000` | EN VIVO / AL AIRE y CTA "Suscribirse". Reservado, no usar como color de marca |
| Fondo página | `#F4F6F8` | Fondo general del sitio (gris muy claro, aireado) |
| Texto atenuado | `#5E6B77` | Meta, timestamps, kickers |
| Naranja hover | `#E36209` | Hover de titulares/enlaces |

### Fuentes globales → Tipografía del sistema

Poner **Familia** y **Grosor**; el resto (tamaño, interlineado) se deja al criterio de
cada sección en Elementor.

| Estilo | Familia | Grosor |
|---|---|---|
| Primary | **Archivo** | 700 |
| Secondary | **Archivo** | 600 |
| Text | **Inter** | 400 |
| Accent | **Inter** | 600 |

> En el prototipo, Archivo se usa en 600–800 y Inter en 400–700. Si Elementor
> ofrece cargar solo algunos pesos, conviene habilitar Archivo 600/700/800 e
> Inter 400/500/600/700.
