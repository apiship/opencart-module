# Пакеты для OpenCart 2.x/3.x: OCMOD-архив с upload/ + install.xml
FOLDERS := 2.1 2.3 3.x

# Default version
VERSION ?= dev

PHP_IMAGE ?= php:8.3-cli
COMPOSE := docker compose -f dev/oc4/docker-compose.yml

PHP_OC3_IMAGES ?= php:7.4-cli php:8.3-cli

.PHONY: build build-oc4 lint test lint-oc3 test-oc3 oc4-up oc4-down oc4-logs oc4-sync

# Rule for archiving folders
build: build-oc4
	@for folder in $(FOLDERS); do \
		pushd $$folder && zip -r "../apiship-$(VERSION)_$$folder.ocmod.zip" . -x "*.DS_Store" -x "*.git*" -x "*/.git/*" && popd; \
	done

# OpenCart 4.1.x: имя файла обязано быть apiship.ocmod.zip —
# установщик OC4 берёт код расширения (папку extension/<code>/) из имени архива
build-oc4:
	@rm -f apiship.ocmod.zip
	@cd 4.x && zip -r ../apiship.ocmod.zip . -x "*.DS_Store" -x "*.git*" -x "*/.git/*"
	@echo "4.x: apiship.ocmod.zip"

# Проверка синтаксиса пакета 4.x на PHP 8.3
lint:
	@docker run --rm -v "$(CURDIR):/app" -w /app $(PHP_IMAGE) sh -c 'set -e; for f in $$(find 4.x -name "*.php"); do php -l "$$f" > /dev/null; done; echo "lint OK"'

# Юнит-тесты библиотеки пакета 4.x
test:
	@docker run --rm -v "$(CURDIR):/app" -w /app $(PHP_IMAGE) php dev/oc4/tests/run.php

# Проверка синтаксиса пакетов 2.1/2.3/3.x на каждой версии PHP из PHP_OC3_IMAGES
# (код пакетов пишется в синтаксисе PHP 5.6: без ??, типов свойств и стрелочных функций)
lint-oc3:
	@for image in $(PHP_OC3_IMAGES); do \
		docker run --rm -v "$(CURDIR):/app" -w /app $$image sh -c 'set -e; for f in $$(find $(FOLDERS) -name "*.php"); do php -l "$$f" > /dev/null; done; echo "lint $$(php -r "echo PHP_VERSION;") OK"' || exit 1; \
	done

# Юнит-тесты библиотеки пакетов 2.1/2.3/3.x (dev/oc3/tests/run.php) на каждой версии PHP из PHP_OC3_IMAGES
test-oc3:
	@for image in $(PHP_OC3_IMAGES); do \
		for folder in $(FOLDERS); do \
			docker run --rm -e APISHIP_PACKAGE=$$folder -v "$(CURDIR):/app" -w /app $$image php -d error_reporting=E_ALL -d display_errors=1 dev/oc3/tests/run.php || exit 1; \
		done; \
	done

# Стенд OpenCart 4.1.x (dev/oc4/README.md)
oc4-up:
	$(COMPOSE) up -d --build

oc4-down:
	$(COMPOSE) down -v

oc4-logs:
	$(COMPOSE) logs -f web

# Копирование пакета 4.x в контейнер поверх установленного модуля (быстрая проверка правок)
oc4-sync:
	$(COMPOSE) cp 4.x/. web:/var/www/html/extension/apiship/
	$(COMPOSE) exec web sh -c 'chown -R www-data:www-data /var/www/html/extension/apiship && rm -rf /var/www/html/system/storage/cache/template/*'
