###
### CI TEST
### ¯¯¯¯¯¯¯¯¯¯¯
### ¯¯¯¯¯¯¯¯¯¯¯
### ¯¯¯¯¯¯¯¯¯¯¯
### ¯¯¯¯¯¯¯¯¯¯¯
### ¯¯¯¯¯¯¯¯¯¯¯

HELP += $(call help,test.all,			Run all tests)
test.all: test.ecs.fix test.ecs test.yaml test.twig test.schema test.phpstan test.eslint

# Check coding standard
HELP += $(call help,test.ecs,			Run ECS)
test.ecs:
	cd ${APP_DIR} && (ENV=$(ENV) docker compose exec php vendor/bin/ecs check ${PLUGIN_DIR}/src)

HELP += $(call help,test.phpstan,			Run PHPStan)
test.phpstan: ## Run PHPStan
	cd ${APP_DIR} && (ENV=$(ENV) docker compose exec php vendor/bin/phpstan analyse --level=7 -c phpstan.neon ${PLUGIN_DIR}/src)

HELP += $(call help,test.ecs.fix,			Fix coding standard)
test.ecs.fix:
	cd ${APP_DIR} && (ENV=$(ENV) docker compose exec php vendor/bin/ecs --fix check ${PLUGIN_DIR}/src)

HELP += $(call help,test.yaml,			Lint the symfony Yaml files)
test.yaml: ## Lint the symfony Yaml files
	cd ${APP_DIR} && (ENV=$(ENV) docker compose exec php bin/console lint:yaml --parse-tags ${PLUGIN_DIR}/templates ${PLUGIN_DIR}/translations)

HELP += $(call help,test.schema,			Validate MySQL Schema)
test.schema: ## Validate MySQL Schema
	cd ${APP_DIR} && (ENV=$(ENV) docker compose exec php bin/console doctrine:cache:clear-metadata)
	cd ${APP_DIR} && (ENV=$(ENV) docker compose exec php bin/console doctrine:schema:validate)

HELP += $(call help,test.twig,			Validate Twig templates)
test.twig: ## Validate Twig templates
	cd ${APP_DIR} && (ENV=$(ENV) docker compose exec php bin/console lint:twig --no-debug ${PLUGIN_DIR}/templates)
	cd ${APP_DIR} && (ENV=$(ENV) docker compose exec php vendor/bin/twigcs ${PLUGIN_DIR}/templates --severity error --display blocking)

HELP += $(call help,test.eslint,			Validate Twig templates)
test.eslint: ## Validate eslint
	cd ${APP_DIR} && (ENV=$(ENV) docker compose run --rm -i nodejs "npm --prefix ${PLUGIN_DIR} run lint")


HELP += $(call help,test.phpunit,			Run phpunit)
test.phpunit: ## Validate eslint
	cd ${APP_DIR} && (ENV=$(ENV) docker compose exec php vendor/bin/phpunit --colors=always)

