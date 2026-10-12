#!/bin/bash
# Regenera las guías en PDF tras editar guia.html o guide_en.html (requiere weasyprint y ./fonts).
cd "$(dirname "$0")"
python3 -c "from weasyprint import HTML; HTML('guia.html').write_pdf('Guia_local_eftimeclock.pdf'); print('PDF regenerado: Guia_local_eftimeclock.pdf')"
python3 -c "from weasyprint import HTML; HTML('guide_en.html').write_pdf('Guide_local_eftimeclock_en.pdf'); print('PDF regenerated: Guide_local_eftimeclock_en.pdf')"
