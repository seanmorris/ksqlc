-include .env

.PHONY: test

PHP_VERSION?=7.3

build-test:
	PHP_VERSION=${PHP_VERSION} \
	docker-compose -f test/docker-compose.yml build

test:
	PHP_VERSION=${PHP_VERSION} \
	docker-compose -f test/docker-compose.yml run -T \
		php /app/vendor/bin/phpunit \
			--testdox test \
			--whitelist=source/ \
			--coverage-clover=/app/coverage.xml \
			test/

start:
	PHP_VERSION=${PHP_VERSION} \
	docker-compose -f test/docker-compose.yml up -d

wait:
	sleep 15

stop:
	PHP_VERSION=${PHP_VERSION} \
	docker-compose -f test/docker-compose.yml down

push-images:
	PHP_VERSION=${PHP_VERSION} \
	docker-compose -f test/docker-compose.yml push

pull-images:
	PHP_VERSION=${PHP_VERSION} \
	docker-compose -f test/docker-compose.yml pull

push-coverage:
	wget -qO- https://codecov.io/bash | CODECOV_TOKEN=${CODECOV_TOKEN} bash
