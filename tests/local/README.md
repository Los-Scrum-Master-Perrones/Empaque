# Pruebas locales del flujo de ERP (sin tocar el ERP real)

Requiere: MySQL local (puerto 3308) con la BD de Empaque.

1. Levantar el ERP de mentira (solo lectura/escritura de prueba):

       php -S 127.0.0.1:8099 tests/local/mock_erp/router.php

2. Correr la prueba:

       php tests/local/test_cambio_documento.php

3. Detener el mock (Ctrl+C o matar el proceso del puerto 8099).

Cubre: cambiar de documento con la viñeta en el ERP (quita del documento
viejo y da de alta en el nuevo), solo en el espejo, ERP caido (no se
cambia), asignar documento nuevo y misma llamada sin cambios.
