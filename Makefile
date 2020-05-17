.PHONY: test

test:
# 	docker-compose -f test/docker-compose.yml run --entrypoint='bash -c' php ls -al;
	docker-compose -f test/docker-compose.yml run php \
		/app/vendor/bin/phpunit --testdox test/

build-test:
	docker-compose -f test/docker-compose.yml build 
# 	docker build test/ -t seanmorris/ksqlc-test
