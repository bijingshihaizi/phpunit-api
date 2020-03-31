FROM php:7.4-fpm

MAINTAINER cvl <1225448773@qq.com>

# Timezone
RUN /bin/cp /usr/share/zoneinfo/Asia/Shanghai /etc/localtime \
    && echo 'Asia/Shanghai' > /etc/timezone \
    && echo "deb http://mirrors.aliyun.com/debian/ buster main non-free contrib \
\ndeb-src http://mirrors.aliyun.com/debian/ buster main non-free contrib \
\ndeb http://mirrors.aliyun.com/debian-security buster/updates main \
\ndeb-src http://mirrors.aliyun.com/debian-security buster/updates main \
\ndeb http://mirrors.aliyun.com/debian/ buster-updates main non-free contrib \
\ndeb-src http://mirrors.aliyun.com/debian/ buster-updates main non-free contrib \
\ndeb http://mirrors.aliyun.com/debian/ buster-backports main non-free contrib \
\ndeb-src http://mirrors.aliyun.com/debian/ buster-backports main non-free contrib">/etc/apt/sources.list


RUN apt-get update \
    && apt-get install -y \
        curl \
        wget \
        git \
        vim \
        bash \
        openssl\
    openssh-server \
    && apt-get clean \
    && apt-get autoremove

RUN  mkdir /var/run/sshd \
    && sed -i 's/#PermitRootLogin/PermitRootLogin/' /etc/ssh/sshd_config \
    && sed -i 's/PermitRootLogin prohibit-password/PermitRootLogin yes/' /etc/ssh/sshd_config \
    && sed -i 's/#PubkeyAuthentication/PubkeyAuthentication/' /etc/ssh/sshd_config \
    && sed 's@session\s*required\s*pam_loginuid.so@session optional pam_loginuid.so@g' -i /etc/pam.d/sshd

    # Composer
RUN wget https://mirrors.aliyun.com/composer/composer.phar  -O composer.phar  \
    && cp composer.phar /usr/bin/composer \
    && chmod +x /usr/bin/composer \
    && composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/

	# Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# PDO extensionS
RUN docker-php-ext-install pdo_mysql bcmath gd mcrypt

ADD . /home/unit
WORKDIR /home/unit

RUN rm -rf composer.lock && composer install

