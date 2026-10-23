BACKEND = docker compose exec -T backend
TOOLS = docker compose run --rm --no-deps -T -v ./backend:/app --entrypoint "" backend
.PHONY: up down reset test test-backend test-frontend smoke qa cs-fix coverage e2e

up:
	docker compose up -d --build

down:
	docker compose down

reset:
	docker compose down -v && docker compose up -d --build

test: qa test-backend test-frontend smoke e2e

test-backend:
	$(BACKEND) php bin/phpunit

coverage:
	$(BACKEND) php -d pcov.enabled=1 -d pcov.directory=/app/src bin/phpunit --coverage-text --colors=never

test-frontend:
	docker build --target test ./frontend

smoke:
	./scripts/smoke.sh

e2e:
	docker compose --profile e2e run --rm --build e2e

qa:
	$(TOOLS) vendor/bin/php-cs-fixer check --diff
	$(TOOLS) sh -c "bin/console cache:warmup --env=dev -q && vendor/bin/phpstan analyse --no-progress --memory-limit=1G"
	$(TOOLS) vendor/bin/rector process --dry-run --no-progress-bar
	$(TOOLS) vendor/bin/deptrac analyse --no-progress
	$(TOOLS) sh -c "bin/console doctrine:migrations:migrate -n -q --env=test && bin/console doctrine:schema:validate --env=test"

cs-fix:
	$(TOOLS) vendor/bin/php-cs-fixer fix
