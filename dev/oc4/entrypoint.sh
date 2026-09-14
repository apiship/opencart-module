#!/bin/bash
# Первый запуск: установка OpenCart через cli_install.php, подключение mock API ApiShip.
set -e

cd /var/www/html

DB_HOST="${OC_DB_HOST:-db}"
DB_NAME="${OC_DB_NAME:-opencart}"
DB_USER="${OC_DB_USER:-opencart}"
DB_PASS="${OC_DB_PASS:-opencart}"
HTTP_SERVER="${OC_HTTP_SERVER:-http://localhost:8080/}"
ADMIN_USER="${OC_ADMIN_USER:-admin}"
ADMIN_PASS="${OC_ADMIN_PASS:-admin1234}"
ADMIN_EMAIL="${OC_ADMIN_EMAIL:-admin@example.com}"

echo "Waiting for database ${DB_HOST}..."
for i in $(seq 1 60); do
	if mysqladmin ping -h "${DB_HOST}" -u"${DB_USER}" -p"${DB_PASS}" --silent 2>/dev/null; then
		break
	fi
	sleep 2
done

if [ ! -s config.php ] && [ -d install ]; then
	echo "Installing OpenCart..."
	cp -n config-dist.php config.php
	cp -n admin/config-dist.php admin/config.php

	php install/cli_install.php install \
		--username "${ADMIN_USER}" \
		--password "${ADMIN_PASS}" \
		--email "${ADMIN_EMAIL}" \
		--http_server "${HTTP_SERVER}" \
		--db_driver mysqli \
		--db_hostname "${DB_HOST}" \
		--db_username "${DB_USER}" \
		--db_password "${DB_PASS}" \
		--db_database "${DB_NAME}" \
		--db_port 3306 \
		--db_prefix oc_

	rm -rf install

	if [ -n "${APISHIP_API_URL}" ]; then
		echo "define('APISHIP_API_URL', '${APISHIP_API_URL}');" >> config.php
		echo "define('APISHIP_API_URL', '${APISHIP_API_URL}');" >> admin/config.php
	fi

	chown -R www-data:www-data /var/www/html
	echo "OpenCart installed: ${HTTP_SERVER} (admin: ${ADMIN_USER} / ${ADMIN_PASS})"
fi

exec "$@"
