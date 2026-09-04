#!/usr/bin/env python3
"""
Mock-сервер API ApiShip (https://api.apiship.ru/v1/) для сквозной проверки модуля на стенде.
Без зависимостей: только стандартная библиотека Python 3.

Покрывает вызовы модуля: lists/providers, connections, lists/statuses, lists/points,
calculator, orders/sync, orders/{id}, orders/{id}/status, orders/{id}/cancel,
orders/status?clientNumber=, orders/statuses/date/{date}, orders/labels, orders/waybills.
"""
import json
import re
import sys
import uuid
from datetime import datetime, timedelta, timezone
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from urllib.parse import urlparse, parse_qs, unquote

PORT = int(sys.argv[1]) if len(sys.argv) > 1 else 8085

PROVIDERS = [
    {"key": "cdek", "name": "СДЭК"},
    {"key": "boxberry", "name": "Boxberry"},
]

STATUSES = [
    {"key": "uploaded", "name": "Загружен"},
    {"key": "pendingCourier", "name": "Ожидает курьера"},
    {"key": "onWay", "name": "В пути"},
    {"key": "readyForRecipient", "name": "Готов к выдаче"},
    {"key": "delivered", "name": "Доставлен"},
    {"key": "canceled", "name": "Отменён"},
]


def point(pid, provider, code, name, ptype, lat, lng, street, house, post_index="101000"):
    return {
        "id": pid,
        "providerKey": provider,
        "code": code,
        "name": name,
        "type": ptype,
        "lat": lat,
        "lng": lng,
        "countryCode": "RU",
        "postIndex": post_index,
        "region": "Москва",
        "regionType": "г",
        "city": "Москва",
        "cityType": "г",
        "street": street,
        "streetType": "ул",
        "house": house,
        "block": "",
        "office": "",
        "description": "Вход со двора, 1 этаж",
        "phone": "+7 495 000-00-00",
        "timetable": "пн-вс 10:00-20:00",
        "paymentCash": 1,
        "paymentCard": 1 if pid % 2 else 0,
        "availableOperation": 3,
    }


POINTS = [
    point(1, "cdek", "MSK1", "СДЭК Тверская", 1, 55.7600, 37.6120, "Тверская", "12"),
    point(2, "cdek", "MSK2", "СДЭК Арбат", 1, 55.7500, 37.5900, "Арбат", "20"),
    point(3, "cdek", "MSK3", "Постамат СДЭК Ленинский", 2, 55.7050, 37.5700, "Ленинский проспект", "30"),
    point(4, "boxberry", "BX01", "Boxberry Китай-город", 1, 55.7550, 37.6330, "Маросейка", "7"),
    point(5, "boxberry", "BX02", "Boxberry Таганка", 1, 55.7420, 37.6530, "Таганская", "5"),
    point(6, "cdek", "SKL1", "Склад СДЭК Москва", 4, 55.8000, 37.7000, "Складочная", "1"),
]

ORDERS = {}
NEXT_ORDER_ID = [1001]


def now_iso():
    return datetime.now(timezone(timedelta(hours=3))).strftime("%Y-%m-%dT%H:%M:%S+03:00")


def filter_points(filter_string):
    rows = list(POINTS)
    if not filter_string:
        return rows
    for part in filter_string.split(";"):
        part = part.strip()
        if not part:
            continue
        m = re.match(r"^id=\[(.*)\]$", part)
        if m:
            ids = {int(x) for x in m.group(1).split(",") if x.strip().isdigit()}
            rows = [p for p in rows if p["id"] in ids]
            continue
        m = re.match(r"^id=(\d+)$", part)
        if m:
            rows = [p for p in rows if p["id"] == int(m.group(1))]
            continue
        m = re.match(r"^providerKey=(.+)$", part)
        if m:
            rows = [p for p in rows if p["providerKey"] == m.group(1)]
            continue
        m = re.match(r"^code%(.+)$", part)
        if m:
            needle = m.group(1).lower()
            rows = [p for p in rows if needle in p["code"].lower() or needle in p["name"].lower()]
            continue
        # availableOperation и прочие фильтры игнорируем
    return rows


def calculator(body):
    places = body.get("places") or [{}]
    weight = float(places[0].get("weight") or 1)
    base = 300 + round(weight / 1000) * 50
    cod = 30 if body.get("codCost") else 0
    return {
        "deliveryToPoint": [
            {
                "providerKey": "cdek",
                "tariffs": [
                    {
                        "tariffId": 136,
                        "tariffName": "Посылка склад-склад",
                        "tariffDescription": "Доставка до ПВЗ",
                        "daysMin": 2,
                        "daysMax": 4,
                        "deliveryCost": base + cod,
                        "deliveryCostOriginal": base,
                        "pickupTypes": [1, 2],
                        "pointIds": [1, 2, 3],
                    }
                ],
            },
            {
                "providerKey": "boxberry",
                "tariffs": [
                    {
                        "tariffId": 1,
                        "tariffName": "Boxberry ПВЗ",
                        "tariffDescription": "",
                        "daysMin": 3,
                        "daysMax": 5,
                        "deliveryCost": base - 50 + cod,
                        "deliveryCostOriginal": base - 50,
                        "pickupTypes": [1, 2],
                        "pointIds": [4, 5],
                    }
                ],
            },
        ],
        "deliveryToDoor": [
            {
                "providerKey": "cdek",
                "tariffs": [
                    {
                        "tariffId": 137,
                        "tariffName": "Посылка склад-дверь",
                        "tariffDescription": "Курьером до двери",
                        "daysMin": 2,
                        "daysMax": 5,
                        "deliveryCost": base + 250 + cod,
                        "deliveryCostOriginal": base + 250,
                        "pickupTypes": [1, 2],
                    }
                ],
            }
        ],
    }


class Handler(BaseHTTPRequestHandler):
    def log_message(self, fmt, *args):
        sys.stderr.write("%s - %s\n" % (self.command, fmt % args))

    def send_json(self, data, status=200):
        payload = json.dumps(data, ensure_ascii=False).encode("utf-8")
        self.send_response(status)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(payload)))
        self.send_header("x-tracing-id", uuid.uuid4().hex)
        self.end_headers()
        self.wfile.write(payload)

    def read_body(self):
        length = int(self.headers.get("Content-Length") or 0)
        raw = self.rfile.read(length) if length else b""
        try:
            return json.loads(raw.decode("utf-8")) if raw else {}
        except ValueError:
            return {}

    def check_auth(self):
        token = self.headers.get("Authorization", "")
        if not token:
            self.send_json({"code": "010001", "message": "Не передан токен авторизации"}, 401)
            return False
        return True

    def do_GET(self):
        if not self.check_auth():
            return
        url = urlparse(self.path)
        path = url.path.rstrip("/")
        query = parse_qs(url.query)

        if path == "/v1/lists/providers":
            return self.send_json({"rows": PROVIDERS if int(query.get("offset", ["0"])[0]) == 0 else []})
        if path == "/v1/connections":
            rows = [{"providerKey": p["key"], "connectId": i + 1} for i, p in enumerate(PROVIDERS)]
            return self.send_json({"rows": rows if int(query.get("offset", ["0"])[0]) == 0 else []})
        if path == "/v1/lists/statuses":
            return self.send_json({"rows": STATUSES if int(query.get("offset", ["0"])[0]) == 0 else []})
        if path == "/v1/lists/points":
            if int(query.get("offset", ["0"])[0]) > 0:
                return self.send_json({"rows": []})
            return self.send_json({"rows": filter_points(unquote(query.get("filter", [""])[0]))})
        if path == "/v1/orders/status":
            client = query.get("clientNumber", [""])[0]
            for oid, order in ORDERS.items():
                if order["clientNumber"] == client:
                    return self.send_json({"orderInfo": {"orderId": oid, "clientNumber": client}, "status": order["status"]})
            return self.send_json({"code": "040001", "message": "Заказ не найден"}, 404)

        m = re.match(r"^/v1/orders/statuses/date/(.+)$", path)
        if m:
            rows = []
            for oid, order in ORDERS.items():
                rows.append({
                    "orderInfo": {"orderId": oid, "clientNumber": order["clientNumber"], "trackingUrl": order["trackingUrl"]},
                    "status": order["status"],
                })
            return self.send_json(rows)

        m = re.match(r"^/v1/orders/(\d+)/status$", path)
        if m:
            oid = int(m.group(1))
            order = ORDERS.get(oid)
            if not order:
                return self.send_json({"code": "040001", "message": "Заказ не найден"}, 404)
            return self.send_json({
                "orderInfo": {"orderId": oid, "clientNumber": order["clientNumber"], "trackingUrl": order["trackingUrl"]},
                "status": order["status"],
            })

        m = re.match(r"^/v1/orders/(\d+)/cancel$", path)
        if m:
            oid = int(m.group(1))
            order = ORDERS.get(oid)
            if not order:
                return self.send_json({"code": "040001", "message": "Заказ не найден"}, 404)
            if order["status"]["key"] == "canceled":
                return self.send_json({"code": "040081", "message": "Заказ уже отменён"}, 400)
            order["status"] = {"key": "canceled", "name": "Отменён", "created": now_iso()}
            return self.send_json({"orderId": oid})

        m = re.match(r"^/v1/orders/(\d+)$", path)
        if m:
            oid = int(m.group(1))
            order = ORDERS.get(oid)
            if not order:
                return self.send_json({"code": "040001", "message": "Заказ не найден"}, 404)
            return self.send_json(order["request"])

        return self.send_json({"code": "000001", "message": "Unknown route " + path}, 404)

    def do_POST(self):
        if not self.check_auth():
            return
        url = urlparse(self.path)
        path = url.path.rstrip("/")
        body = self.read_body()

        if path == "/v1/calculator":
            if not (body.get("to") or {}).get("addressString"):
                return self.send_json({"code": "020001", "message": "Не указан адрес получателя", "errors": [{"field": "to.addressString", "message": "required"}]}, 400)
            return self.send_json(calculator(body))

        if path == "/v1/orders/sync":
            order = body.get("order") or {}
            client = str(order.get("clientNumber", ""))
            for oid, existing in ORDERS.items():
                if existing["clientNumber"] == client and existing["status"]["key"] != "canceled":
                    return self.send_json({"code": "040010", "message": "Заказ уже существует", "errors": [{"field": "clientNumber", "message": "duplicate"}]}, 400)
            oid = NEXT_ORDER_ID[0]
            NEXT_ORDER_ID[0] += 1
            ORDERS[oid] = {
                "clientNumber": client,
                "providerKey": order.get("providerKey"),
                "trackingUrl": "https://track.example.test/%s" % oid,
                "status": {"key": "uploaded", "name": "Загружен", "created": now_iso()},
                "request": body,
            }
            return self.send_json({"orderId": oid, "providerNumber": "%s-%s" % ((order.get("providerKey") or "").upper(), oid)})

        if path == "/v1/orders/labels":
            ids = body.get("orderIds") or []
            failed = [{"orderId": i, "message": "Заказ не найден"} for i in ids if i not in ORDERS]
            data = {"url": "http://localhost:%d/files/labels_%s.pdf" % (PORT, "_".join(str(i) for i in ids))}
            if failed:
                data["failedOrders"] = failed
            return self.send_json(data)

        if path == "/v1/orders/waybills":
            ids = body.get("orderIds") or []
            items = {}
            for i in ids:
                if i in ORDERS:
                    items[ORDERS[i]["providerKey"]] = {"providerKey": ORDERS[i]["providerKey"], "file": "http://localhost:%d/files/waybill_%s.pdf" % (PORT, ORDERS[i]["providerKey"])}
            data = {"waybillItems": list(items.values())}
            failed = [{"orderId": i, "message": "Заказ не найден"} for i in ids if i not in ORDERS]
            if failed:
                data["failedOrders"] = failed
            return self.send_json(data)

        return self.send_json({"code": "000001", "message": "Unknown route " + path}, 404)


if __name__ == "__main__":
    server = ThreadingHTTPServer(("0.0.0.0", PORT), Handler)
    print("ApiShip mock API on port %d" % PORT, flush=True)
    server.serve_forever()
