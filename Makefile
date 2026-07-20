DC := docker compose run --rm -u $(shell id -u):$(shell id -g) cli

.PHONY: build install review fix shell test

build: ## Build the dev image
	docker compose build

install: ## composer install inside the container (Lite from Packagist, Pro from the private registry)
	$(DC) composer install

review: ## Full quality gate (php-cs-fixer + rector + phpstan + pest)
	$(DC) composer review

fix: ## Auto-format (rector + php-cs-fixer)
	$(DC) composer fix

test: ## Test suite only
	$(DC) vendor/bin/pest

shell: ## Interactive shell in the container
	$(DC) bash
