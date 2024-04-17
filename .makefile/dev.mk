.DEFAULT_GOAL := help
SHELL=/bin/bash
APP_DIR=tests/Application
SYMFONY=cd ${APP_DIR} && symfony
COMPOSER=symfony composer
CONSOLE=cd ${APP_DIR} && docker-compose run --rm php bin/console
COMPOSE=docker-compose
DOCKER=cd ${APP_DIR} && DOCKER_USER=$(DOCKER_USER) docker-compose run --rm php bin/console sylius:install -s default -n
YARN=yarn
NPM=npm

###
### DEVELOPMENT
### ¯¯¯¯¯¯¯¯¯¯¯

HELP += $(call help,install,			Install the project)
install: application platform sylius ## Install the plugin
.PHONY: install

HELP += $(call help,reset,			Stop docker and remove dependencies)
reset: ## Stop docker and remove dependencies
	${MAKE} platform_down || true
	rm -rf ${APP_DIR}/node_modules ${APP_DIR}/package-lock.json
	rm -rf ${APP_DIR}
	rm -rf vendor composer.lock
.PHONY: rese

###
### TEST APPLICATION
### ¯¯¯¯¯

application: php.ini .php-version ${APP_DIR} ## Setup the entire Test Application

.php-version: .php-version.dist
	rm -f .php-version
	ln -s .php-version.dist .php-version

php.ini: php.ini.dist
	rm -f php.ini
	ln -s php.ini.dist php.ini

${APP_DIR}:
	(${COMPOSER} create-project --no-interaction --prefer-dist --no-scripts --no-progress --no-install sylius/sylius-standard="~${SYLIUS_VERSION}" ${APP_DIR})
	cd ${APP_DIR} && chmod -R 777 public
	make apply_dist

apply_dist:
	ROOT_DIR=$(shell dirname $(realpath $(firstword $(MAKEFILE_LIST)))); \
	for i in `cd dist && find . -type f`; do \
		FILE_PATH=`echo $$i | sed 's|./||'`; \
		FOLDER_PATH=`dirname $$FILE_PATH`; \
		echo $$FILE_PATH; \
		(cd ${APP_DIR} && rm -f $$FILE_PATH); \
		(cd ${APP_DIR} && mkdir -p $$FOLDER_PATH); \
    done

###
### SYLIUS
### ¯¯¯¯¯¯¯¯
sylius: sylius_install install_bundle

sylius_install:
	cd ${APP_DIR} && docker-compose exec -it -u root php rm -rf public/media/image
	cd ${APP_DIR} && docker-compose run php bin/console sylius:install -s default -n

install_bundle:
	cd ${APP_DIR} && docker-compose run php composer require --no-interaction ${PLUGIN_NAME}="*@dev"
	echo "navigate to http://localhost:8050/"


symlink_plugin:
	#cd ${APP_DIR} && rm -rf vendor/agence-adeliom/sylius-easy-crud-plugin

###
### PLATFORM
### ¯¯¯¯¯¯¯¯

DOCKER_USER ?= "$(shell id -u):$(shell id -g)"
ENV ?= "dev"

platform:
	@if [ ! -e compose.override.yml ]; then \
		cd ${APP_DIR} && cp compose.override.dist.yml compose.override.yml; \
	fi

	make platform_up
	cd ${APP_DIR} && docker-compose run --rm php composer config github-oauth.github.com ${GITHUB_TOKEN}
	cd ${APP_DIR} && docker-compose run --rm php composer config minimum-stability dev
	cd ${APP_DIR} && docker-compose run --rm php composer config extra.symfony.allow-contrib true
	cd ${APP_DIR} && docker-compose run --rm php composer config repositories.plugin '{"type": "path", "url": "../../"}'
	cd ${APP_DIR} && docker-compose run --rm php composer config repositories.adeliom '{"type":"vcs","url":"git@github.com:agence-adeliom/sylius-easy-crud-plugin.git"}'
	cd ${APP_DIR} && docker-compose run --rm php composer config extra.symfony.require "~${SYMFONY_VERSION}"
	cd ${APP_DIR} && docker-compose run --rm php composer require --no-install --no-scripts --no-progress sylius/sylius="~${SYLIUS_VERSION}"
	cd ${APP_DIR} && docker-compose run --rm php composer install --no-interaction --no-scripts --prefer-dist
	make platform_up
	cd ${APP_DIR} && docker-compose run --rm nodejs

platform_debug:
	cd ${APP_DIR} && docker-compose -f compose.yml -f compose.override.yml -f compose.debug.yml up -d

platform_up:
	cd ${APP_DIR} && docker-compose up -d

platform_down:
	cd ${APP_DIR} && docker-compose down

platform_clean:
	cd ${APP_DIR} && docker-compose down -v

php-shell:
	cd ${APP_DIR} && docker-compose exec php sh

node-shell:
	cd ${APP_DIR} && docker-compose run --rm -i nodejs sh

node-watch:
	cd ${APP_DIR} && docker-compose run --rm -i nodejs "npm run watch"





