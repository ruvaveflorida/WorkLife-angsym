#!/bin/bash
echo "=== INICIANDO MIGRACIONES ==="
php bin/console doctrine:migrations:migrate --no-interaction -vvv
echo "=== MIGRACIONES COMPLETADAS ==="
echo "=== INICIANDO SERVIDOR ==="
php -S 0.0.0.0:8080 -t public
