.PHONY: test

test:
	docker-compose -f test/docker-compose.yml run php \
		/app/vendor/bin/phpunit --testdox test/

build-test:
	docker-compose -f test/docker-compose.yml build

start:
	docker-compose -f test/docker-compose.yml up -d

wait:
	sleep 60

stop:
	docker-compose -f test/docker-compose.yml down

push-images:
	docker-compose -f test/docker-compose.yml push

pull-images:
	docker-compose -f test/docker-compose.yml pull
