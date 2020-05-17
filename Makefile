.PHONY: test

test:
	docker-compose -f test/docker-compose.yml run php \
		/app/vendor/bin/phpunit --testdox test/

build-test:
	docker-compose -f test/docker-compose.yml build 

stop:
	docker-compose -f test/docker-compose.yml down