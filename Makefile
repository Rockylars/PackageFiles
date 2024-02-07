.DEFAULT_GOAL := help
MAKEFLAGS += --silent --warn-undefined-variables
SHELL = /bin/bash

PHP_RUN := docker compose run --rm php-cli
ARGS ?= $(shell read -p "Additional arguments ([enter] for none): " args; echo $$args)

export HOST_UID := $(shell id -u)
export HOST_GID := $(shell id -g)

##>—— Building —————————————

## build:			Build the docker containers
.PHONY: build
build:
	docker compose build --pull

## setup:			Sets up the project for you
.PHONY: setup
setup: build
	${PHP_RUN} composer install --ansi

##>—— Debugging ————————————

## bash:			Go into the container for running things manually
.PHONY: bash
bash:
	${PHP_RUN} bash

## php:			PHP
.PHONY: php
php:
	${PHP_RUN} $(ARGS)

## php-modules:		PHP modules
.PHONY: php-modules
php-modules:
	${PHP_RUN} php -m

## composer:		Runs composer through docker
.PHONY: composer
composer:
	${PHP_RUN} composer $(ARGS) --ansi

##>—— Checks ———————————————

## tests:			Runs PHPUnit tests
.PHONY: test tests
test: tests
tests:
	${PHP_RUN} php vendor/bin/phpunit tests --testdox

## analyze:		Runs PHPStan -> everything
.PHONY: analyse analyze
analyse: analyze
analyze: analyze-all analyze-src

## analyze-all:		Runs PHPStan -> src + tests
.PHONY: analyse-all analyze-all
analyse-all: analyze-all
analyze-all:
	${PHP_RUN} php -d memory_limit=-1 vendor/bin/phpstan analyse --configuration=phpstan-all.neon

## analyze-src:		Runs PHPStan -> src
.PHONY: analyse-src analyze-src
analyse-src: analyze-src
analyze-src:
	${PHP_RUN} php -d memory_limit=-1 vendor/bin/phpstan analyse --configuration=phpstan-src.neon

## baseline:		Runs PHPStan -> baseline
.PHONY: baseline
baseline:
	${PHP_RUN} php -d memory_limit=-1 vendor/bin/phpstan analyse --configuration=phpstan-all.neon --generate-baseline=phpstan-all-baseline.neon

##>—— Styling ——————————————

## cs:			Runs PHPCS through docker
.PHONY: cs
cs:
	${PHP_RUN} php -d memory_limit=-1 vendor/bin/php-cs-fixer fix --verbose --dry-run --diff $(ARGS)
	${PHP_RUN} php -d memory_limit=-1 vendor/bin/php-cs-fixer fix --verbose --dry-run --diff .php-cs-fixer.php

## cs-fix:		Runs PHPCS with fixes through docker
.PHONY: cs-fix
cs-fix:
	${PHP_RUN} php -d memory_limit=-1 vendor/bin/php-cs-fixer fix --verbose --diff $(ARGS)
	${PHP_RUN} php -d memory_limit=-1 vendor/bin/php-cs-fixer fix --verbose --diff .php-cs-fixer.php

##>—— Extra ————————————————

## help:			Print this message
.PHONY: help
help: Makefile
	sed -n 's/^##//p' $<

## tab-makefile:		Tabs the makefile descriptions
.PHONY: tab-makefile
tab-makefile:
	sed -i 's/:	/:		/g' Makefile

## clear-local-branches:	Removes all local branches
.PHONY: clear-local-branches
clear-local-branches:
	git for-each-ref --format '%(refname:short)' refs/heads | grep -v main | xargs git branch -D