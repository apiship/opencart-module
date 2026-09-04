#!/bin/bash
# Установка пакета 4.x на стенд через HTTP-эндпоинты админки OpenCart 4.1:
# логин → Установщик (загрузка apiship.ocmod.zip, распаковка) → Расширения/Доставка → install → сохранение настроек.
# Использование: dev/oc4/install-module.sh [путь к apiship.ocmod.zip]
set -euo pipefail

BASE="${OC_BASE_URL:-http://localhost:8080}"
ADMIN_USER="${OC_ADMIN_USER:-admin}"
ADMIN_PASS="${OC_ADMIN_PASS:-admin1234}"
ZIP="${1:-apiship.ocmod.zip}"
COMPOSE="docker compose -f $(dirname "$0")/docker-compose.yml"
JAR="$(mktemp)"

trap 'rm -f "$JAR"' EXIT

if [ ! -f "$ZIP" ]; then
	echo "Archive not found: $ZIP (run: make build-oc4)" >&2
	exit 1
fi

if [ "$(basename "$ZIP")" != "apiship.ocmod.zip" ]; then
	echo "Archive must be named apiship.ocmod.zip (OpenCart 4 takes the extension code from the file name)" >&2
	exit 1
fi

json_get() {
	python3 -c 'import json, sys; d = json.load(sys.stdin); v = d.get(sys.argv[1], ""); print(v if isinstance(v, str) else json.dumps(v, ensure_ascii=False))' "$1"
}

db() {
	$COMPOSE exec -T db mariadb -uopencart -popencart opencart -N -e "$1" 2>/dev/null
}

echo "1. Login as ${ADMIN_USER}"
# Страница логина выдаёт одноразовый login_token, без него POST отклоняется
LOGIN_TOKEN=$(curl -s -c "$JAR" -b "$JAR" "${BASE}/admin/index.php?route=common/login" | grep -o "login_token=[A-Za-z0-9]*" | head -1 | cut -d= -f2 || true)
LOGIN=$(curl -s -c "$JAR" -b "$JAR" -X POST --data-urlencode "username=${ADMIN_USER}" --data-urlencode "password=${ADMIN_PASS}" "${BASE}/admin/index.php?route=common/login.login&login_token=${LOGIN_TOKEN}")
TOKEN=$(echo "$LOGIN" | grep -o 'user_token=[A-Za-z0-9]*' | head -1 | cut -d= -f2 || true)

if [ -z "$TOKEN" ]; then
	echo "Login failed: $LOGIN" >&2
	exit 1
fi

ADMIN="${BASE}/admin/index.php?user_token=${TOKEN}&route="

INSTALL_ID=$(db "SELECT extension_install_id FROM oc_extension_install WHERE code='apiship'" || true)

if [ -z "$INSTALL_ID" ]; then
	echo "2. Upload ${ZIP}"
	UPLOAD=$(curl -s -c "$JAR" -b "$JAR" -F "file=@${ZIP}" "${ADMIN}marketplace/installer.upload")

	if [ -n "$(echo "$UPLOAD" | json_get error)" ]; then
		echo "Upload failed: $UPLOAD" >&2
		exit 1
	fi

	INSTALL_ID=$(db "SELECT extension_install_id FROM oc_extension_install WHERE code='apiship'")
fi

if $COMPOSE exec -T web test -f /var/www/html/extension/apiship/install.json; then
	echo "3. Extract: skipped, extension/apiship already exists"
else
	echo "3. Extract (extension_install_id=${INSTALL_ID})"
	NEXT="${ADMIN}marketplace/installer.install&extension_install_id=${INSTALL_ID}&page=1"

	while [ -n "$NEXT" ]; do
		RESP=$(curl -s -c "$JAR" -b "$JAR" "$NEXT")

		if [ -n "$(echo "$RESP" | json_get error)" ]; then
			echo "Install step failed: $RESP" >&2
			exit 1
		fi

		NEXT=$(echo "$RESP" | json_get next)
	done
fi

if [ -n "$(db "SELECT extension_id FROM oc_extension WHERE type='shipping' AND code='apiship'")" ]; then
	echo "4. Enable shipping extension: skipped, already installed"
else
	echo "4. Enable shipping extension"
	RESP=$(curl -s -c "$JAR" -b "$JAR" "${ADMIN}extension/shipping.install&extension=apiship&code=apiship")

	if [ -n "$(echo "$RESP" | json_get error)" ]; then
		echo "Shipping install failed: $RESP" >&2
		exit 1
	fi
fi

echo "5. Save settings"
GR=$(db "SELECT weight_class_id FROM oc_weight_class_description WHERE unit='g' LIMIT 1")
CM=$(db "SELECT length_class_id FROM oc_length_class_description WHERE unit='cm' LIMIT 1")
CURRENCY=$(db "SELECT value FROM oc_setting WHERE \`key\`='config_currency' LIMIT 1")
ST_PENDING=$(db "SELECT order_status_id FROM oc_order_status WHERE name='Pending' LIMIT 1")
ST_PROCESSING=$(db "SELECT order_status_id FROM oc_order_status WHERE name='Processing' LIMIT 1")
ST_SHIPPED=$(db "SELECT order_status_id FROM oc_order_status WHERE name='Shipped' LIMIT 1")
ST_CANCELED=$(db "SELECT order_status_id FROM oc_order_status WHERE name='Canceled' LIMIT 1")
ST_FAILED=$(db "SELECT order_status_id FROM oc_order_status WHERE name='Failed' LIMIT 1")

RESP=$(curl -s -c "$JAR" -b "$JAR" -X POST "${ADMIN}extension/apiship/shipping/apiship.save" \
	--data-urlencode "shipping_apiship_status=1" \
	--data-urlencode "shipping_apiship_token=${APISHIP_TOKEN:-mock-token}" \
	--data-urlencode "shipping_apiship_rub_select=${CURRENCY}" \
	--data-urlencode "shipping_apiship_gr_select=${GR}" \
	--data-urlencode "shipping_apiship_cm_select=${CM}" \
	--data-urlencode "shipping_apiship_title=ApiShip" \
	--data-urlencode "shipping_apiship_title_point_template=%type %company %name %address %tariff %time" \
	--data-urlencode "shipping_apiship_title_door_template=%type %company %time" \
	--data-urlencode "shipping_apiship_icon_show=1" \
	--data-urlencode "shipping_apiship_error_stub_show=1" \
	--data-urlencode "shipping_apiship_sending_country_code=RU" \
	--data-urlencode "shipping_apiship_sending_region=Москва" \
	--data-urlencode "shipping_apiship_sending_city=Москва" \
	--data-urlencode "shipping_apiship_sending_street=Тверская" \
	--data-urlencode "shipping_apiship_sending_house=1" \
	--data-urlencode "shipping_apiship_contact_organization=ООО Тест" \
	--data-urlencode "shipping_apiship_contact_inn=7700000000" \
	--data-urlencode "shipping_apiship_contact_name=Иван Иванов" \
	--data-urlencode "shipping_apiship_contact_phone=+79990000000" \
	--data-urlencode "shipping_apiship_contact_email=test@example.com" \
	--data-urlencode "shipping_apiship_parcel_length=10" \
	--data-urlencode "shipping_apiship_parcel_width=10" \
	--data-urlencode "shipping_apiship_parcel_height=10" \
	--data-urlencode "shipping_apiship_parcel_weight=500" \
	--data-urlencode "shipping_apiship_export_status=${ST_PROCESSING}" \
	--data-urlencode "shipping_apiship_cancel_export_status=${ST_PENDING}" \
	--data-urlencode "shipping_apiship_group_export_status_ready=${ST_SHIPPED}" \
	--data-urlencode "shipping_apiship_group_export_status_ok=${ST_PROCESSING}" \
	--data-urlencode "shipping_apiship_group_export_status_error=${ST_FAILED}" \
	--data-urlencode "shipping_apiship_paid_orders[]=${ST_PROCESSING}" \
	--data-urlencode "shipping_apiship_add_pickup_date=1" \
	--data-urlencode "shipping_apiship_cron_key=${APISHIP_CRON_KEY:-devkey123}" \
	--data-urlencode "shipping_apiship_mode=shipping_apiship_mode_debug" \
	--data-urlencode "shipping_apiship_sort_order=1" \
	--data-urlencode "shipping_apiship_provider[cdek][pickup_type]=1" \
	--data-urlencode "shipping_apiship_provider[cdek][id]=6" \
	--data-urlencode "shipping_apiship_provider[cdek][courier_type]=1" \
	--data-urlencode "shipping_apiship_provider[boxberry][courier_type]=1" \
	--data-urlencode "shipping_apiship_mapping_status[canceled][use]=1" \
	--data-urlencode "shipping_apiship_mapping_status[canceled][order_status_id]=${ST_CANCELED}")

if [ -n "$(echo "$RESP" | json_get error)" ]; then
	echo "Settings save failed: $RESP" >&2
	exit 1
fi

echo "Done. Admin: ${BASE}/admin/index.php?route=extension/apiship/shipping/apiship&user_token=${TOKEN}"
echo "Cron key: ${APISHIP_CRON_KEY:-devkey123}"
