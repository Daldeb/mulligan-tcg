.PHONY: help build start stop restart logs health test security clean

# Variables
COMPOSE_FILE = docker-compose.yml

# Couleurs
RED=\033[0;31m
GREEN=\033[0;32m
YELLOW=\033[1;33m
BLUE=\033[0;34m
NC=\033[0m

help: ## Affiche l'aide
	@echo "$(BLUE)TCG HUB - Commandes disponibles:$(NC)"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "$(GREEN)  %-20s$(NC) %s\n", $$1, $$2}'

build: ## Build tous les containers
	@echo "$(YELLOW)Building containers...$(NC)"
	docker compose build --no-cache
	@echo "$(GREEN)Build completed!$(NC)"

start: ## Démarre la stack complète
	@echo "$(YELLOW)Starting TCG Hub stack...$(NC)"
	docker compose up -d
	@echo "$(GREEN)Stack started!$(NC)"

dev: ## Mode développement avec PhpMyAdmin
	@echo "$(YELLOW)Starting development mode...$(NC)"
	docker compose --profile dev up -d
	@echo "$(GREEN)Dev mode started! PhpMyAdmin: http://localhost:8080$(NC)"

stop: ## Arrête tous les services
	@echo "$(YELLOW)Stopping all services...$(NC)"
	docker compose down
	@echo "$(GREEN)All services stopped!$(NC)"

restart: ## Redémarre la stack
	@make stop
	@make start

logs: ## Affiche les logs
	docker compose logs -f --tail=100

health: ## Health check complet
	@echo "$(BLUE)Health Check Starting...$(NC)"
	@./scripts/serveur.sh
	@./scripts/front.sh
	@./scripts/reseau.sh

status: ## Status des services
	@docker compose ps

test: ## Lance tous les tests
	@echo "$(YELLOW)Running tests...$(NC)"
	@if [ -f "./backend/vendor/bin/phpunit" ]; then \
		docker compose exec -T app php vendor/bin/phpunit; \
	else \
		echo "$(YELLOW)PHPUnit not found$(NC)"; \
	fi

security: ## Scan de sécurité
	@echo "$(YELLOW)Security scan...$(NC)"
	@./scripts/security.sh

clean: ## Nettoie les containers
	@docker compose down
	@docker system prune -f

reset: ## Reset complet
	@echo "$(RED)This will destroy ALL data! Continue? [y/N]$(NC)" && read ans && [ $${ans:-N} = y ]
	@docker compose down -v --rmi all

install: ## Installation complète
	@echo "$(BLUE)Installing TCG Hub...$(NC)"
	@make build
	@make start
	@sleep 30
	@make health

up: start
down: stop

.DEFAULT_GOAL := help
