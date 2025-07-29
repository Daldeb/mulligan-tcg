#!/bin/bash
set -e

echo "⏳ Attente de MySQL sur le host 'mysql'..."

until mysql -h mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" -e "SELECT 1;" "$MYSQL_DATABASE" >/dev/null 2>&1; do
  echo "⏱ MySQL pas encore prêt, on attend..."
  sleep 2
done

echo "✅ MySQL est prêt."

# 🔐 Correction des droits sur les clés JWT
if [ -f /var/www/backend/config/jwt/private.pem ]; then
  echo "🔧 Correction des permissions JWT..."
  chmod 640 /var/www/backend/config/jwt/private.pem
  chmod 644 /var/www/backend/config/jwt/public.pem
  chown www-data:www-data /var/www/backend/config/jwt/private.pem
  chown www-data:www-data /var/www/backend/config/jwt/public.pem
else
  echo "⚠️  Fichier /config/jwt/private.pem introuvable"
fi

echo "🚀 Lancement de Symfony (php-fpm)..."

exec "$@"
