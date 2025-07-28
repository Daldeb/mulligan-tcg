.PHONY: sf cache logs migrate fixtures jwt

# ➤ Lancer le serveur Symfony localement
sf:
	php -S 127.0.0.1:8000 -t public

# ➤ Vider le cache
cache:
	php bin/console cache:clear

# ➤ Suivre les logs
logs:
	tail -f var/log/dev.log

# ➤ Lancer les migrations
migrate:
	php bin/console doctrine:migrations:migrate --no-interaction

# ➤ Charger les fixtures
fixtures:
	php bin/console doctrine:fixtures:load --no-interaction

# ➤ Générer les clés JWT (si jamais tu dois les régénérer en local)
jwt:
	mkdir -p config/jwt && \
	openssl genrsa -out config/jwt/private.pem -aes256 4096 && \
	openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
