###
### CI TEST
### ¯¯¯¯¯¯¯¯¯¯¯
### ¯¯¯¯¯¯¯¯¯¯¯
### ¯¯¯¯¯¯¯¯¯¯¯
### ¯¯¯¯¯¯¯¯¯¯¯
### ¯¯¯¯¯¯¯¯¯¯¯

# Check coding standard
test.ecs:
	cd ${APP_DIR} && (ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) docker-compose exec php vendor/bin/ecs check lib/sylius-easy-crud-plugin/src)

# Fix coding standard
test.ecs.fix:
	cd ${APP_DIR} && (ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) docker-compose exec php vendor/bin/ecs --fix check lib/sylius-easy-crud-plugin/src)
