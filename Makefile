-include .env

.PHONY: build-test test start start-fg wait stop push-images pull-images push-coverage

PHP_VERSION?=7.3
DOCKER_COMPOSE?=docker compose

build-test:
	PHP_VERSION=${PHP_VERSION} \
	$(DOCKER_COMPOSE) --progress plain -f test/docker-compose.yml build

test: start wait
	PHP_VERSION=${PHP_VERSION} \
	$(DOCKER_COMPOSE) --progress plain -f test/docker-compose.yml run --quiet-pull --rm -T \
		php /app/vendor/bin/phpunit \
			--whitelist=source/ \
			--coverage-clover=/app/coverage.xml \
			test/

start:
	PHP_VERSION=${PHP_VERSION} \
	$(DOCKER_COMPOSE) --progress plain -f test/docker-compose.yml up --quiet-pull -d \
		ksql-server krest-server

start-fg:
	PHP_VERSION=${PHP_VERSION} \
	$(DOCKER_COMPOSE) --progress plain -f test/docker-compose.yml up --quiet-pull \
		ksql-server krest-server

SECONDS?=120

wait:
	PHP_VERSION=${PHP_VERSION} \
	$(DOCKER_COMPOSE) --progress plain -f test/docker-compose.yml run --quiet-pull --rm -T \
		-e WAIT_SECONDS=${SECONDS} \
		php /app/test/wait.php

stop:
	PHP_VERSION=${PHP_VERSION} \
	$(DOCKER_COMPOSE) --progress plain -f test/docker-compose.yml down --remove-orphans

push-images:
	PHP_VERSION=${PHP_VERSION} \
	$(DOCKER_COMPOSE) --progress plain -f test/docker-compose.yml push

pull-images:
	PHP_VERSION=${PHP_VERSION} \
	$(DOCKER_COMPOSE) --progress plain -f test/docker-compose.yml pull

push-coverage:
	wget -qO- https://codecov.io/bash | CODECOV_TOKEN=${CODECOV_TOKEN} bash
