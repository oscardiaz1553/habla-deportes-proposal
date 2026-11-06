# Kit web de Out studio

Skills y agentes para construir un sitio de cliente de punta a punta: de la maqueta navegable
a WordPress con Elementor 4 (editor V4 atómico), con QA, SEO y rendimiento, migración y lanzamiento.

## Instalar en un proyecto

En Claude Code, dentro de la carpeta del proyecto:

```
/plugin marketplace add oscardiaz1553/habla-deportes-proposal
/plugin install kit-web-out@out-studio
```

Para traer los cambios que se suban después: `/plugin marketplace update out-studio`.

## Qué trae

### Skills (WordPress)

| Skill | Para qué |
|---|---|
| `wp-elementor-replica` | Punto de entrada: fases F0–F12 de la maqueta aprobada al sitio publicado. Dice qué skill usar en cada paso. |
| `elementor4-atomico` | Construir páginas y plantillas de Elementor 4 por WP-CLI (clases, variables, Theme Builder). |
| `wp-qa` | Revisar una página contra la maqueta: medidas, editabilidad, accesibilidad, consola. |
| `wp-seo-rendimiento` | PageSpeed en verde, Yoast, accesibilidad AA, redirecciones. |
| `wp-migracion` | Paso de desarrollo a producción, correo, lanzamiento y vuelta atrás. |

### Agentes

- **WordPress:** `elementor-dev` (construye), `qa-elementor` (revisa), `wp-seo` (SEO y velocidad).
- **Maqueta y propuesta:** dirección de arte, maquetación, contenidos, accesibilidad, pauta, UX/UI,
  SEO técnico, competencia, arquitectura de contenido, estrategia WordPress y narrativa de la propuesta.
  Son genéricos: leen la marca y el brief del `CLAUDE.md` del proyecto.

## Cómo empezar un proyecto nuevo

1. Copia `skills/wp-elementor-replica/plantillas/CLAUDE.md` al proyecto y rellénalo (marca, accesos por alias, nunca contraseñas).
2. Pide: «arranca el proyecto con wp-elementor-replica».
3. Elementor recomendado: `wordpress/plugins/elementor-4.3.1.zip` en este mismo repositorio.
