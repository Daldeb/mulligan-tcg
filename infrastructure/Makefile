.PHONY: up down shell logs reset

up:
	docker compose -f docker-compose.yaml up -d

down:
	docker compose -f docker-compose.yaml down

shell:
	docker exec -it tcg_local_app bash

logs:
	docker compose -f docker-compose.yaml logs -f

reset:
	docker compose -f docker-compose.yaml down -v && docker compose -f docker-compose.yaml up --build -d
