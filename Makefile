.PHONY: \
    build-containers \
    build-project \
    fix-permissions \
    cache-clear \
    migrate \
    migrate-rollback \
    fixtures \
    docker-up \
    npm-dev \
    npm-prod \
    php \
    mysql \
    mysql-import-dump-main \
    mysql-export-dump-main

DB ?= supplax

# =========================
# Docker
# =========================

# Построение контейнеров + запуск
build-containers:
	docker compose build --no-cache
	$(MAKE) docker-up

docker-up:
	docker compose up -d

# =========================
# Project setup
# =========================

# Подготовка проекта к работе
build-project:
	cp .env .env.local && \
	docker compose exec php bash -c "composer install" && \
	docker compose exec php bash -c "php bin/console cache:clear" && \
	docker compose exec php bash -c "php bin/console assets:install public" && \
	docker compose exec php bash -c "npm install" && \
	docker compose exec php bash -c "npm run dev"

# =========================
# Permissions (Linux only / по необходимости)
# =========================

# Фикс прав (использовать ТОЛЬКО если есть проблемы с доступом)
fix-permissions:
	docker compose exec -u root php bash -c "\
	    chown -R www-data:www-data var docker/volume || true && \
	    chmod -R ug+rw var docker/volume \
	"

# ⚠️ КРАЙНИЙ СЛУЧАЙ (Ubuntu, старые volume)
# make fix-permissions-777
fix-permissions-777:
	docker compose exec -u root php bash -c "\
	    chmod -R 777 var docker/volume \
	"

# =========================
# Symfony
# =========================

# Очистка и прогрев кэша
cache-clear:
	docker compose exec php bash -c "php bin/console cache:clear"

cache-warmup:
	docker compose exec php bash -c "php bin/console cache:warmup"

# Применить все новые миграции
migrate:
	docker compose exec php bash -c "php bin/console doctrine:migrations:migrate --no-interaction"

# Откатить последнюю миграцию
migrate-rollback:
	docker compose exec php bash -c "php bin/console doctrine:migrations:migrate prev --no-interaction"

# Создать новую пустую миграцию
migrate-diff:
	docker compose exec php bash -c "php bin/console doctrine:migrations:diff"

# Загрузить фикстуры (fixtures:load)
fixtures:
	docker compose exec php bash -c "php bin/console doctrine:fixtures:load --no-interaction"

# =========================
# Frontend
# =========================

npm-dev:
	docker compose exec php bash -c "npm run dev"

npm-prod:
	docker compose exec php bash -c "npm run build"

npm-watch:
	docker compose exec php bash -c "npm run watch"

# =========================
# Shell access
# =========================

php:
	docker compose exec -u root -it php bash

mysql:
	docker compose exec mysql bash -c "mysql -u root -p'root' $(DB)"

mysql-import-dump-main:
	docker compose exec mysql bash -c "mysql -u root -p'root' $(DB) < /var/dumps/$(DB).sql"

mysql-export-dump-main:
	docker compose exec mysql bash -c "mysqldump -u root -p'root' $(DB) > /var/dumps/$(DB).sql"