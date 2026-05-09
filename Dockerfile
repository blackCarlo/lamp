FROM php:5.6-apache

ENV DEBIAN_FRONTEND=noninteractive \
    MYSQL_ROOT_PASSWORD=root \
    MYSQL_DATABASE=lamp \
    MYSQL_USER=lamp \
    MYSQL_PASSWORD=lamp

# 处理老 Debian Stretch 源失效问题，并安装 MariaDB + supervisor
RUN set -eux; \
    { \
      echo 'Acquire::Check-Valid-Until "false";'; \
      echo 'Acquire::AllowInsecureRepositories "true";'; \
      echo 'Acquire::AllowDowngradeToInsecureRepositories "true";'; \
    } > /etc/apt/apt.conf.d/99archive; \
    sed -i 's/deb.debian.org/archive.debian.org/g' /etc/apt/sources.list || true; \
    sed -i 's/security.debian.org/archive.debian.org/g' /etc/apt/sources.list || true; \
    sed -i '/stretch-updates/d;/jessie-updates/d' /etc/apt/sources.list || true; \
    printf '#!/bin/sh\nexit 101\n' > /usr/sbin/policy-rc.d; \
    chmod +x /usr/sbin/policy-rc.d; \
    apt-get update; \
    if apt-cache show default-mysql-server >/dev/null 2>&1; then \
        MYSQL_PACKAGE=default-mysql-server; \
    else \
        MYSQL_PACKAGE=mysql-server; \
    fi; \
    apt-get install -y --no-install-recommends --allow-unauthenticated \
        $MYSQL_PACKAGE \
        supervisor \
        vim \
        net-tools \
        procps \
    ; \
    rm -f /usr/sbin/policy-rc.d; \
    rm -rf /var/lib/mysql/* /var/run/mysqld/* /var/lib/apt/lists/*; \
    mkdir -p /var/lib/mysql /var/run/mysqld; \
    chown -R mysql:mysql /var/lib/mysql /var/run/mysqld

# PHP 5.6 常用 MySQL 扩展
RUN docker-php-ext-install mysql mysqli pdo pdo_mysql

# Apache 常用模块
RUN a2enmod rewrite \
    && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
    && { \
      echo '<Directory /var/www/html>'; \
      echo '    AllowOverride All'; \
      echo '    Require all granted'; \
      echo '</Directory>'; \
    } > /etc/apache2/conf-available/lamp.conf \
    && a2enconf servername lamp

# PHP 配置
RUN { \
      echo "date.timezone=Asia/Shanghai"; \
      echo "display_errors=On"; \
      echo "error_reporting=E_ALL"; \
      echo "upload_max_filesize=64M"; \
      echo "post_max_size=64M"; \
      echo "memory_limit=256M"; \
      echo "allow_url_include=On"; \
    } > /usr/local/etc/php/conf.d/custom.ini

# Apache DocumentRoot
WORKDIR /var/www/html

COPY www/ /var/www/html/
COPY supervisord.conf /etc/supervisor/supervisord.conf
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN chmod +x /usr/local/bin/docker-entrypoint.sh \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80 3306

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
