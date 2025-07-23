FRONT_DIR=/var/www/frontend
BACK_DIR=/var/www/backend

.PHONY: dev dev-back dev-front

# 🚀 Lancer frontend + backend
dev:
	php -S 0.0.0.0:8000 -t \$(BACK_DIR)/public & \
	cd \$(FRONT_DIR) && npm run dev

# ⚙️ Lancer juste le backend
dev-back:
	php -S 0.0.0.0:8000 -t \$(BACK_DIR)/public

# 🎨 Lancer juste le frontend
dev-front:
	cd \$(FRONT_DIR) && npm run dev
