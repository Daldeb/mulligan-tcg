APP_CONTAINER=tcg_dev_app
FRONT_DIR=/var/www/frontend
BACK_DIR=/var/www/backend
SSH_KEY_PATH=~/.ssh/id_ed25519
SSH_USER=dev
SSH_HOST=51.178.27.41
SSH_PORT=2222

.PHONY: up down build logs ssh add-key dev test build-front workspace

# 🔄 Démarrer les conteneurs
up:
	docker compose up -d

# 🛑 Stopper les conteneurs
down:
	docker compose down

# 🔁 Rebuild uniquement le conteneur PHP (app)
build:
	docker compose build app

# 📥 Logs Symfony
logs:
	docker compose logs -f app

# 🔐 Ajouter la clé SSH
add-key:
	@echo "🔐 Ajout de la clé SSH publique dans $(APP_CONTAINER)..."
	@[ -f $(SSH_KEY_PATH) ] || (echo "❌ Clé SSH non trouvée : $(SSH_KEY_PATH)" && exit 1)
	docker exec -u root $(APP_CONTAINER) mkdir -p /home/$(SSH_USER)/.ssh
	cat $(SSH_KEY_PATH) | docker exec -i -u root $(APP_CONTAINER) tee /home/$(SSH_USER)/.ssh/authorized_keys > /dev/null
	docker exec -u root $(APP_CONTAINER) chown -R $(SSH_USER):$(SSH_USER) /home/$(SSH_USER)/.ssh
	docker exec -u root $(APP_CONTAINER) chmod 700 /home/$(SSH_USER)/.ssh
	docker exec -u root $(APP_CONTAINER) chmod 600 /home/$(SSH_USER)/.ssh/authorized_keys
	@echo "✅ Clé SSH ajoutée avec succès."

# 💻 Accès SSH direct
ssh:
	ssh $(SSH_USER)@$(SSH_HOST) -p $(SSH_PORT) -i $(SSH_KEY_PATH)

# 🚀 Lancer les serveurs frontend + backend
dev:
	docker exec -u $(SSH_USER) -w $(BACK_DIR) -d $(APP_CONTAINER) php -S 0.0.0.0:8080 -t public
	docker exec -u $(SSH_USER) -w $(FRONT_DIR) -d $(APP_CONTAINER) npm run dev
	@echo "🌐 Frontend : http://localhost:5173"
	@echo "🖥️  Backend  : http://localhost:8080"

# ✅ Tests backend
test:
	docker exec -u $(SSH_USER) -w $(BACK_DIR) $(APP_CONTAINER) ./vendor/bin/phpunit

# 🛠️ Build du frontend
build-front:
	docker exec -u $(SSH_USER) -w $(FRONT_DIR) $(APP_CONTAINER) npm run build

# 📁 Générer un workspace VSCode
workspace:
	@echo "🧱 Génération de tcg-dev.code-workspace..."
	echo '{'                                  > tcg-dev.code-workspace
	echo '  "folders": ['                    >> tcg-dev.code-workspace
	echo '    { "name": "backend", "path": "backend" },' >> tcg-dev.code-workspace
	echo '    { "name": "frontend", "path": "frontend" }' >> tcg-dev.code-workspace
	echo '  ]'                               >> tcg-dev.code-workspace
	echo '}'                                 >> tcg-dev.code-workspace
	@echo "✅ Workspace prêt !"
# 🔁 Rebuild complet pour développement local
dev-init:
	make down
	make build
	make up
