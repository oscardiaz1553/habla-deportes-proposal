#!/usr/bin/env python3
"""audits.py — lista las auditorías con nota < 0,9 de un informe Lighthouse/PSI, por categoría,
con su peso y hasta 8 nodos/URL de detalle. Sirve para decidir qué arreglar y para demostrar
por qué una categoría no llega (p. ej. SEO 66 = solo falla is-crawlable por blog_public=0).

Estado: copiado del caso de referencia (HomeMaker) sin cambios de lógica; genérico.
Uso: python3 audits.py <informe.json>
"""
import json
import sys

if len(sys.argv) < 2:
    sys.exit(__doc__)
d = json.load(open(sys.argv[1]))
a = d['audits']


def short(v):
    if isinstance(v, dict):
        return {k: vv for k, vv in v.items() if k in ('snippet', 'selector', 'nodeLabel', 'url', 'value', 'type')}
    return v


for cat in ['performance', 'accessibility', 'best-practices', 'seo']:
    if cat not in d.get('categories', {}):
        continue
    print('=====', cat)
    for ref in d['categories'][cat]['auditRefs']:
        au = a[ref['id']]
        if au['score'] is not None and au['score'] < 0.9 and au['scoreDisplayMode'] not in ('notApplicable', 'manual', 'informative'):
            print(f"- {ref['id']} ({au['score']}) w{ref.get('weight')}: {au.get('displayValue', '')} | {au['title'][:90]}")
            det = au.get('details') or {}
            items = det.get('items') if isinstance(det.get('items'), list) else []
            for it in items[:8]:
                if not isinstance(it, dict):
                    continue
                s = json.dumps({k: short(v) for k, v in it.items() if k in ('url', 'node', 'wastedBytes', 'wastedMs', 'totalBytes', 'source', 'description', 'element', 'score', 'value', 'label', 'duration')}, ensure_ascii=False)[:400]
                print('     ', s)
