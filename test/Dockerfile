FROM debian:buster-20191118-slim as base
MAINTAINER Sean Morris <ksqlc@seanmorr.is>

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
	apt-get update;      \
	apt-get install -y --no-install-recommends \
		composer         \
		libssl1.1        \
		php7.3           \
		php7.3-cli       \
		php7.3-common    \
		php7.3-dom       \
		php7.3-json      \
		php7.3-mbstring  \
		php7.3-opcache   \
		php7.3-pdo-mysql \
		php7.3-readline  \
		php7.3-xml       \
		php7.3-yaml;     \
apt-get remove -y software-properties-common \
		apache2-bin      \
		apt-transport-https \
		ca-certificates  \
		gnupg            \
		lsb-release      \
		perl             \
		php5.6           \
		python           \
		wget;            \
	php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
	php -r "if (hash_file('sha384', 'composer-setup.php') === 'e0012edf3e80b6978849f5eff0d4b4e4c79ff1609dd1e613307e16318854d24ae64f26d17af3ef0bf7cfb710ca74755a') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
	php composer-setup.php --install-dir=/usr/bin \
	php -r "unlink('composer-setup.php');"        \
	chmod a+x composer.phar;                      \
	apt-get purge -y --auto-remove; \
	apt-get autoremove -y; \
	apt-get clean;         \
	rm -rf /var/lib/apt/lists/* \
	mkdir /app;

WORKDIR /app

ENTRYPOINT ["php"]