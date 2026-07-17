# Rediseño Habla Deportes — Propuesta

Proyecto de rediseño para **Habla Deportes** (https://habladeportes.co/).

## Contexto del cliente

- **Qué es:** Medio deportivo y plataforma de radio de Barranquilla, Colombia.
- **Enfoque editorial:** Fútbol colombiano — Selección Colombia, Junior FC, Copa América, fichajes.
- **Formato principal:** Contenido *video-first* (canal de YouTube, +265 videos) + programa de radio matutino (L–V, 10:00 a.m.–12:00 m.).
- **Monetización:** Patrocinios / sponsors (ej. Transelca).
- **Stack actual:** WordPress (desarrollado por spacerock).
- **Contacto:** info@habladeportes.co

## Objetivo del proyecto

Rediseñar el sitio para modernizar su imagen y experiencia. La entrega se hace en **dos etapas**:

1. **Propuesta navegable en HTML** (esta fase) — un prototipo estático de alta fidelidad que el cliente pueda navegar y aprobar. Rápido de producir, alto impacto visual.
2. **Implementación en WordPress** (post-aprobación) — llevar la propuesta aprobada a producción.

## Modalidad

Trabajo **por recomendación**. El objetivo comercial de esta propuesta, además de ganar al cliente, es que quede tan bien ejecutada que **genere recomendaciones y nuevos clientes potenciales**. La calidad y el pulido de la entrega son parte del entregable.

## Flujo de trabajo (2 fases)

### Fase 1 — Diagnóstico
Auditoría completa del sitio actual antes de diseñar. Agentes:
- `auditor-ux-ui` — experiencia de usuario y diseño visual
- `auditor-tecnico-seo` — rendimiento, SEO técnico, mobile, accesibilidad
- `arquitecto-contenido` — arquitectura de información y estrategia de contenido
- `analista-competencia` — benchmark contra otros medios deportivos

Salida: documentos en `diagnostico/` + informe consolidado.

### Fase 2 — Desarrollo de la propuesta
Construcción del prototipo HTML. Agentes:
- `director-arte` — sistema de diseño e identidad visual
- `desarrollador-frontend` — prototipo HTML/CSS/JS en `propuesta/html/`
- `estratega-wordpress` — plan de implementación en WordPress

### Coordinación
- `director-proyecto` — orquesta las fases, consolida hallazgos y arma la narrativa comercial de la propuesta.

## Estructura del repositorio

```
.
├── README.md              ← este archivo (brief compartido)
├── .claude/agents/        ← definiciones de los agentes del equipo
├── diagnostico/           ← informes de auditoría (Fase 1)
├── propuesta/
│   ├── html/              ← prototipo HTML navegable (Fase 2)
│   └── assets/            ← imágenes, íconos, fuentes
└── referencias/           ← capturas del sitio actual, benchmarks, moodboards
```
