.PHONY: up down reset shell logs sf front

# --- Docker local (si tu veux bosser en containers à l'occasion)
up:
	docker compose -f docker-compose.yaml up -d

down:
	docker compose -f docker-compose.yaml down

reset:
	docker compose -f docker-compose.yaml down -v && docker compose -f docker-compose.yaml up --build -d

shell:
	docker exec -it tcg_local_app bash

logs:
	docker compose -f docker-compose.yaml logs -f


# --- Local dev hors Docker

sf:
	php -S 127.0.0.1:8000 -t app/symfony/public

front:
	cd app/vuejs && npm run dev
