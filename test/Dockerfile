FROM debian:bookworm-20240211-slim as base
MAINTAINER Sean Morris <ksqlc@seanmorr.is>

ARG PHP_VERSION

SHELL ["/bin/bash", "-euxc"]

RUN apt-get update;      \
	apt-get install -y --no-install-recommends software-properties-common \
		ca-certificates  \
		lsb-release      \
		gnupg            \
		wget;            \
	wget -O /etc/apt/trusted.gpg.d/php.gpg             \
		https://packages.sury.org/php/apt.gpg;         \
	sh -c "echo 'deb https://packages.sury.org/php/ $(lsb_release -sc) main' \
		 | tee /etc/apt/sources.list.d/sury-php.list"; \
	wget https://snapshot.debian.org/archive/debian/20190531T031011Z/pool/main/o/openssl/libssl1.1_1.1.1c-1_amd64.deb; \
	dpkg -i libssl1.1_1.1.1c-1_amd64.deb; \
	apt-get update;      \
	apt-get install -y --no-install-recommends \
		php${PHP_VERSION}           \
		php${PHP_VERSION}-cli       \
		php${PHP_VERSION}-common    \
		php${PHP_VERSION}-dom       \
		php${PHP_VERSION}-mbstring  \
		php${PHP_VERSION}-pdo-mysql \
		php${PHP_VERSION}-readline  \
		php${PHP_VERSION}-xml;      \
	for PACKAGE in                  \
		php${PHP_VERSION}-opcache   \
		php${PHP_VERSION}-xdebug;   \
	do                              \
		if apt-cache show ${PACKAGE} >/dev/null 2>&1; then \
			apt-get install -y --no-install-recommends ${PACKAGE}; \
		fi;                         \
	done;                           \
	if [[ "${PHP_VERSION}" = 7* ]]; then \
		for PACKAGE in              \
			php${PHP_VERSION}-json  \
			php${PHP_VERSION}-yaml; \
		do                          \
			if apt-cache show ${PACKAGE} >/dev/null 2>&1; then \
				apt-get install -y --no-install-recommends ${PACKAGE}; \
			fi;                     \
		done;                       \
	fi;                             \
	apt-get remove -y software-properties-common \
		apache2-bin         \
		apt-transport-https \
		ca-certificates     \
		gnupg               \
		lsb-release         \
		perl                \
		python              \
		wget;               \
	apt-get purge -y --auto-remove; \
	apt-get autoremove -y;          \
	apt-get clean;                  \
	rm -rf /var/lib/apt/lists/*;    \
	mkdir /app;

WORKDIR /app

COPY ./test/30-xdebug-cli.ini /etc/php/${PHP_VERSION}/cli/conf.d/30-xdebug-cli.ini

ENTRYPOINT ["php"]
