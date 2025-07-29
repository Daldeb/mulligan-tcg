#!/bin/bash
set -e

echo "⏳ Attente de MySQL sur le host 'mysql'..."

until mysql -h mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" -e "SELECT 1;" "$MYSQL_DATABASE" >/dev/null 2>&1; do
  echo "⏱ MySQL pas encore prêt, on attend..."
  sleep 2
done

echo "✅ MySQL est prêt. Lancement de Symfony..."

exec "$@"
