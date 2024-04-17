###
### CI TEST
### ¯¯¯¯¯¯¯¯¯¯¯
### ¯¯¯¯¯¯¯¯¯¯¯
### ¯¯¯¯¯¯¯¯¯¯¯
### ¯¯¯¯¯¯¯¯¯¯¯
### ¯¯¯¯¯¯¯¯¯¯¯

COMPOSER_CI_ROOT=composer
TEST_DIRECTORY_CI=./install/Application
CONSOLE_CI=cd ./install/Application && php bin/console -e test
COMPOSER_CI=cd ./install/Application && composer
NPM_CI=cd ./install/Application && npm


install-ci: sylius-ci ## Install Plugin on Sylius [SYLIUS_VERSION=1.12.13] [SYMFONY_VERSION=6.4]
install-docker: sylius-docker ## Install Plugin on Sylius [SYLIUS_VERSION=1.12.13] [SYMFONY_VERSION=6.4]
.PHONY: install

reset-ci: ## Remove dependencies
ifneq ("$(wildcard install/Application/bin/console)","")
	${CONSOLE_CI} doctrine:database:drop --force --if-exists || true
endif
	rm -rf install/Application
.PHONY: reset-ci

phpunit-ci: phpunit-configure-ci phpunit-run-ci ## Run PHPUnit
.PHONY: phpunit

###
### OTHER
### ¯¯¯¯¯¯

sylius-ci: sylius-standard-ci update-dependencies-ci install-plugin-ci install-sylius-ci
sylius-docker: sylius-standard-ci update-dependencies-ci install-plugin-ci install-sylius-docker set-proxies
.PHONY: sylius-ci

sylius-standard-ci:
	${COMPOSER_CI_ROOT} create-project sylius/sylius-standard ${TEST_DIRECTORY_CI} "~${SYLIUS_VERSION}" --no-install --no-scripts
	${COMPOSER_CI} config allow-plugins true
	#https://github.com/api-platform/core/issues/6226
	${COMPOSER_CI} req api-platform/core:v2.7.16 --prefer-source --no-scripts --no-install
	${COMPOSER_CI} require sylius/sylius:"~${SYLIUS_VERSION}"

update-dependencies-ci:
	${COMPOSER_CI} config extra.symfony.require "~${SYMFONY_VERSION}"
	${COMPOSER_CI} update --no-progress -n

install-plugin-ci:
	${COMPOSER_CI} config repositories.plugin '{"type": "path", "url": "../../"}'
	(cd ${APP_DIR} && ${COMPOSER} config repositories.adeliom '{"type":"vcs","url":"git@github.com:agence-adeliom/sylius-easy-crud-plugin.git"}')
	${COMPOSER_CI} config extra.symfony.allow-contrib true
	${COMPOSER_CI} config autoload '{"psr-4": {"Adeliom\\SyliusEasyCrudPlugin\\": "../../src/",}}'
	${COMPOSER_CI} config minimum-stability "dev"
	${COMPOSER_CI} config prefer-stable true
	${COMPOSER_CI} req ${PLUGIN_NAME}:* --prefer-source --no-scripts --no-install

install-sylius-ci:
	${CONSOLE_CI} doctrine:database:create --if-not-exists
	${CONSOLE_CI} doctrine:migrations:migrate -n
	${CONSOLE_CI} sylius:fixtures:load default -n
	${NPM_CI} install
	${NPM_CI} run build:prod
	${CONSOLE_CI} cache:clear

install-sylius-docker:
	${NPM_CI} install
	${NPM_CI} run build:prod

phpunit-configure-ci:
	cp phpunit.xml.dist ${TEST_DIRECTORY_CI}/phpunit.xml

phpunit-run-ci:
	cd ${TEST_DIRECTORY_CI} && ./vendor/bin/phpunit --testdox

set-proxies:
	cp .docker/stub/trusted_proxies.yaml ${TEST_DIRECTORY_CI}/config/packages
