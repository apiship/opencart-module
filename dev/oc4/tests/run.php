<?php
/**
 * Юнит-тесты библиотеки пакета 4.x без ядра OpenCart.
 * Запуск: make test (docker php:8.3-cli) или php dev/oc4/tests/run.php
 */
declare(strict_types=0);

namespace Opencart\System\Engine {
	// Минимальный реестр вместо ядра OpenCart
	class Registry {
		private array $data = [];

		public function get(string $key) {
			return $this->data[$key] ?? null;
		}

		public function set(string $key, $value): void {
			$this->data[$key] = $value;
		}

		public function has(string $key): bool {
			return isset($this->data[$key]);
		}
	}
}

namespace ApishipTests {
	require_once __DIR__ . '/../../../4.x/system/library/apiship.php';

	use Opencart\System\Library\Extension\Apiship\Apiship;

	class StubSession {
		public array $data = [];

		public function getId(): string {
			return 'test-session';
		}
	}

	class StubCache {
		public array $items = [];
		public array $sets = [];
		public array $gets = [];

		public function get(string $key) {
			$this->gets[] = $key;

			return $this->items[$key] ?? [];
		}

		public function set(string $key, $value, int $expire = 0): void {
			$this->items[$key] = $value;
			$this->sets[] = $key;
		}

		public function delete(string $key): void {
			unset($this->items[$key]);
		}
	}

	class StubLog {
		public array $lines = [];

		public function write($message): void {
			$this->lines[] = (string)$message;
		}
	}

	class StubLoader {
		public function model(string $route): void {
		}
	}

	class StubProductModel {
		public array $products = [];

		public function getProduct(int $product_id): array {
			return $this->products[$product_id] ?? [];
		}
	}

	class StubConverter {
		public function convert(float $value, $from, $to): float {
			return $value;
		}
	}

	class StubCurrency {
		public function convert(float $value, string $from, string $to): float {
			return $value;
		}
	}

	class StubConfig {
		public function get(string $key) {
			return $key == 'config_currency' ? 'RUB' : null;
		}
	}

	// Библиотека без сети: ответы API подставляются по подстроке url, каждый запрос записывается
	class StubApi extends Apiship {
		public array $responses = [];
		public array $requests = [];

		protected function curl_request(string $url, ?string $body): array {
			$this->requests[] = $url;

			foreach ($this->responses as $needle => $payload) {
				if (strpos($url, $needle) !== false) {
					return ['body' => json_encode($payload), 'headers' => ['x-tracing-id' => ['t-1']], 'code' => 200];
				}
			}

			return ['body' => '{"message":"not found"}', 'headers' => [], 'code' => 404];
		}

		public function requests_to(string $needle): int {
			return count(array_filter($this->requests, fn($url) => strpos($url, $needle) !== false));
		}

		public function cacheKeyFor(string $key): string {
			return $this->cacheKey($key);
		}
	}

	function registry(): \Opencart\System\Engine\Registry {
		$registry = new \Opencart\System\Engine\Registry();

		$registry->set('session', new StubSession());
		$registry->set('cache', new StubCache());
		$registry->set('log', new StubLog());
		$registry->set('load', new StubLoader());
		$registry->set('length', new StubConverter());
		$registry->set('weight', new StubConverter());
		$registry->set('currency', new StubCurrency());
		$registry->set('config', new StubConfig());

		$products = new StubProductModel();
		$products->products = [
			10 => ['sku' => 'SKU-10', 'upc' => '', 'weight_class_id' => 1, 'length_class_id' => 1],
			20 => ['sku' => '', 'upc' => 'UPC-20', 'weight_class_id' => 1, 'length_class_id' => 1]
		];

		$registry->set('model_catalog_product', $products);

		return $registry;
	}

	function library(array $params = [], ?\Opencart\System\Engine\Registry $registry = null): Apiship {
		$registry = $registry ?? registry();

		$defaults = [
			'shipping_apiship_rub_select'   => 'RUB',
			'shipping_apiship_gr_select'    => 1,
			'shipping_apiship_cm_select'    => 1,
			'shipping_apiship_token'        => 'test',
			'shipping_apiship_mode'         => 'shipping_apiship_mode_normal',
			'shipping_apiship_provider'     => [],
			'shipping_apiship_articul_mode' => 'sku',
			'shipping_apiship_parcel_length' => 10,
			'shipping_apiship_parcel_width'  => 10,
			'shipping_apiship_parcel_height' => 10,
			'shipping_apiship_parcel_weight' => 500
		];

		return new Apiship($registry, $params + $defaults, $registry->get('log'));
	}

	$failures = 0;
	$passed = 0;

	function check(string $name, bool $condition, string $details = ''): void {
		global $failures, $passed;

		if ($condition) {
			$passed++;

			echo "  ok   $name\n";
		} else {
			$failures++;

			echo "  FAIL $name" . ($details ? " — $details" : '') . "\n";
		}
	}

	echo "parce_code\n";

	$lib = library();

	$point = $lib->parce_code('apiship.point_cdek_136_3_2');

	check('point: delivery_type', $point['delivery_type'] == 'point');
	check('point: provider', $point['provider'] == 'cdek');
	check('point: tariff_id', $point['tariff_id'] == '136');
	check('point: point_id', $point['point_id'] == '3');
	check('point: pickup_type', $point['pickup_type'] == '2');
	check('point: short_code', $point['short_code'] == 'point_cdek_136_3_2');

	$error = $lib->parce_code('apiship.point_boxberry_1_error_1');

	check('point error: point_id = error', $error['point_id'] == 'error');
	check('point error: pickup_type', $error['pickup_type'] == '1');

	$door = $lib->parce_code('apiship.door_cdek_137_1');

	check('door: delivery_type', $door['delivery_type'] == 'door');
	check('door: pickup_type taken from 4th part', $door['pickup_type'] == '1');
	check('door: point_id empty', $door['point_id'] === '');

	$empty = $lib->parce_code('flat.flat');

	check('foreign code: no crash, empty provider', $empty['provider'] === '' && $empty['short_code'] == 'flat');

	echo "get_address\n";

	check('no regionType → empty string', $lib->get_address(['region' => 'Москва']) === '');

	$address = $lib->get_address([
		'regionType' => 'г',
		'region'     => 'Москва',
		'city'       => 'Москва',
		'cityType'   => 'г',
		'street'     => 'Тверская',
		'streetType' => 'ул',
		'house'      => '12',
		'block'      => '',
		'office'     => '5'
	]);

	check('city-region address', $address === 'Москва, ул Тверская, д.12, офис 5', $address);

	$address = $lib->get_address([
		'regionType' => 'обл',
		'region'     => 'Московская',
		'city'       => 'Химки',
		'cityType'   => 'г',
		'street'     => 'Ленина',
		'streetType' => 'ул',
		'house'      => 'д. 7',
		'block'      => '2',
		'postIndex'  => '141400'
	], true);

	check('region address with postcode and block', $address === '141400, Московская обл, г Химки, ул Ленина д. 7 корпус 2', $address);

	echo "calculate_places\n";

	$products = [
		['product_id' => 10, 'name' => 'Товар A', 'model' => 'A', 'quantity' => 2, 'price' => 100, 'length' => 20, 'width' => 10, 'height' => 5, 'length_class_id' => 1, 'weight' => 800, 'weight_class_id' => 1],
		['product_id' => 20, 'name' => 'Товар B', 'model' => 'B', 'quantity' => 1, 'price' => 50, 'length' => 0, 'width' => 0, 'height' => 0, 'length_class_id' => 1, 'weight' => 0, 'weight_class_id' => 1]
	];

	$result = $lib->calculate_places($products, 200.0);

	$items_total = 0;

	foreach ($result['items'] as $item) {
		$items_total += $item['cost'] * $item['quantity'];
	}

	check('items cost sum equals order total after discount', abs($items_total - 200.0) < 0.01, (string)$items_total);
	check('articul from sku', in_array('SKU-10', array_column($result['items'], 'articul')));
	check('fallback to parcel defaults for product without dimensions', $result['total_weight'] == 800 + 500, (string)$result['total_weight']);
	check('place length = max side', $result['total_length'] == 20, (string)$result['total_length']);
	check('place height = sum of smallest sides', $result['total_height'] == 5 + 5 + 10, (string)$result['total_height']);
	check('assessed cost equals items cost by default', abs($result['assessed_cost'] - 200.0) < 0.01, (string)$result['assessed_cost']);

	$fixed = library(['shipping_apiship_use_fix_product_assessed_cost' => 1, 'shipping_apiship_fix_product_assessed_cost' => 10])->calculate_places($products, 200.0);

	check('fixed assessed cost per item', abs($fixed['assessed_cost'] - 30.0) < 0.01, (string)$fixed['assessed_cost']);

	// Без переопределения вес = 800 (товар A) + 500 (по умолчанию для B) = 1300, поэтому override берём заведомо другой
	$override = library(['shipping_apiship_place_weight' => 2000, 'shipping_apiship_package_weight' => 100])->calculate_places($products, 200.0);

	check('place weight override + package weight', $override['total_weight'] == 2100, (string)$override['total_weight']);

	$package_only = library(['shipping_apiship_package_weight' => 100])->calculate_places($products, 200.0);

	check('package weight added to calculated weight', $package_only['total_weight'] == 1400, (string)$package_only['total_weight']);

	$dims = library(['shipping_apiship_place_length' => 50, 'shipping_apiship_place_width' => 40, 'shipping_apiship_place_height' => 30])->calculate_places($products, 200.0);

	check('place dimensions override', $dims['total_length'] == 50 && $dims['total_width'] == 40 && $dims['total_height'] == 30, $dims['total_length'] . 'x' . $dims['total_width'] . 'x' . $dims['total_height']);

	echo "currency direction\n";

	// Базовая валюта USD, «рубль» в настройках RUB, курс 1 USD = 2 RUB: суммы для ApiShip должны удвоиться
	$registry_rate = registry();

	$registry_rate->set('config', new class {
		public function get(string $key) {
			return $key == 'config_currency' ? 'USD' : null;
		}
	});

	$registry_rate->set('currency', new class {
		public function convert(float $value, string $from, string $to): float {
			if ($from == 'USD' && $to == 'RUB') {
				return $value * 2;
			}

			if ($from == 'RUB' && $to == 'USD') {
				return $value / 2;
			}

			return $value;
		}
	});

	$lib_rate = new Apiship($registry_rate, [
		'shipping_apiship_rub_select'    => 'RUB',
		'shipping_apiship_gr_select'     => 1,
		'shipping_apiship_cm_select'     => 1,
		'shipping_apiship_token'         => 'test',
		'shipping_apiship_mode'          => 'shipping_apiship_mode_normal',
		'shipping_apiship_provider'      => [],
		'shipping_apiship_articul_mode'  => 'sku',
		'shipping_apiship_parcel_length' => 10,
		'shipping_apiship_parcel_width'  => 10,
		'shipping_apiship_parcel_height' => 10,
		'shipping_apiship_parcel_weight' => 500
	], $registry_rate->get('log'));

	$rate = $lib_rate->calculate_places($products, 400.0);

	$rate_items_total = 0;

	foreach ($rate['items'] as $item) {
		$rate_items_total += $item['cost'] * $item['quantity'];
	}

	check('item costs converted store currency → rub_select (USD 250 → RUB 500 before discount, then scaled to 400)', abs($rate_items_total - 400.0) < 0.01, (string)$rate_items_total);
	check('assessed cost in rub_select', abs($rate['assessed_cost'] - 400.0) < 0.01, (string)$rate['assessed_cost']);

	$fixed_rate = new Apiship($registry_rate, [
		'shipping_apiship_rub_select'                    => 'RUB',
		'shipping_apiship_gr_select'                     => 1,
		'shipping_apiship_cm_select'                     => 1,
		'shipping_apiship_token'                         => 'test',
		'shipping_apiship_mode'                          => 'shipping_apiship_mode_normal',
		'shipping_apiship_provider'                      => [],
		'shipping_apiship_use_fix_product_assessed_cost' => 1,
		'shipping_apiship_fix_product_assessed_cost'     => 10,
		'shipping_apiship_parcel_length'                 => 10,
		'shipping_apiship_parcel_width'                  => 10,
		'shipping_apiship_parcel_height'                 => 10,
		'shipping_apiship_parcel_weight'                 => 500
	], $registry_rate->get('log'));

	$fixed_assessed = $fixed_rate->calculate_places($products, 400.0)['assessed_cost'];

	check('fixed assessed cost converted to rub_select (10 USD → 20 RUB × 3 items)', abs($fixed_assessed - 60.0) < 0.01, (string)$fixed_assessed);

	echo "cash on delivery\n";

	check('exact code match', $lib->is_cash_on_delivery('filterit_cash', ['cod', 'filterit_cash']));
	check('OC4 option code cod.cod matches extension code cod', $lib->is_cash_on_delivery('cod.cod', ['cod']));
	check('other payment is not COD', !$lib->is_cash_on_delivery('bank_transfer.bank_transfer', ['cod']));
	check('empty payment code is not COD', !$lib->is_cash_on_delivery('', ['cod']));
	check('empty COD list is never COD', !$lib->is_cash_on_delivery('cod.cod', []));

	echo "distribute_place_weight\n";

	$weights = $lib->distribute_place_weight([
		['quantity' => 2, 'weight' => 100],
		['quantity' => 1, 'weight' => 100]
	], 1000.0);

	check('per-unit weight = floor(1000 / 3) = 333', $weights[0]['weight'] == 333 && $weights[0]['quantity'] == 2);
	check('remainder 1 goes to the single-unit last item', $weights[1]['weight'] == 334);
	check('no extra items when last item has quantity 1', count($weights) == 2);

	$weights = $lib->distribute_place_weight([
		['quantity' => 1, 'weight' => 100],
		['quantity' => 2, 'weight' => 100]
	], 1000.0);

	check('last item with quantity > 1 is split to carry the remainder', count($weights) == 3 && $weights[1]['quantity'] == 1 && $weights[2]['quantity'] == 1 && $weights[2]['weight'] == 334);

	$sum = 0;

	foreach ($weights as $item) {
		$sum += $item['weight'] * $item['quantity'];
	}

	check('sum of unit weights equals place weight', $sum == 1000, (string)$sum);

	$weights = $lib->distribute_place_weight([['quantity' => 4, 'weight' => 1]], 1000.0);

	check('exact division leaves no split', count($weights) == 1 && $weights[0]['weight'] == 250);

	echo "session cache\n";

	$lib->setData('k', ['a' => 1], 10);

	check('getData returns stored value', $lib->getData('k') == ['a' => 1]);
	check('getData null for missing key', $lib->getData('missing') === null);

	$lib->setData('expired', 'x', -1);

	check('expired value is dropped', $lib->getData('expired') === null);

	echo "api cache (OpenCart cache, not session)\n";

	$cache_registry = registry();
	$lib_cache = library([], $cache_registry);

	$lib_cache->cacheSet('big', ['rows' => range(1, 500)]);

	check('cacheGet returns stored value', count($lib_cache->cacheGet('big')['rows']) == 500);
	check('cacheGet null for missing key', $lib_cache->cacheGet('missing') === null);
	check('api cache does not touch the session', $cache_registry->get('session')->data === []);
	check('api cache lands in the OpenCart cache', count($cache_registry->get('cache')->items) == 1);

	$big_registry = registry();
	$big_lib = new Apiship($big_registry, ['shipping_apiship_token' => 't', 'shipping_apiship_mode' => 'n', 'shipping_apiship_provider' => []], $big_registry->get('log'));

	$big_lib->cacheSet('points', ['all_points' => array_fill(0, 300, ['id' => 1, 'name' => str_repeat('x', 200)])]);

	check('session stays small after caching 300 points', strlen(json_encode($big_registry->get('session')->data)) < 1000, (string)strlen(json_encode($big_registry->get('session')->data)));

	echo "calculator\n";

	$calc = $lib->apiship_calculator('RU', 'Москва', '', '', '', [], $products, 200.0, false);

	check('empty city → message without network call', isset($calc['body']['message']));

	echo "api calls: cache in OpenCart cache, key per request\n";

	$api_params = [
		'shipping_apiship_url'          => 'http://api.test/v1/',
		'shipping_apiship_token'        => 'tok',
		'shipping_apiship_sending_city' => 'Москва'
	];

	$api_responses = [
		'lists/providers' => ['rows' => [['key' => 'cdek']]],
		'lists/points'    => ['rows' => [['id' => 1, 'code' => 'P1'], ['id' => 2, 'code' => 'P2']]],
		'calculator'      => ['deliveryToPoint' => [], 'deliveryToDoor' => []]
	];

	$api_registry = registry();
	$api = new StubApi($api_registry, $api_params + ['shipping_apiship_rub_select' => 'RUB', 'shipping_apiship_gr_select' => 1, 'shipping_apiship_cm_select' => 1, 'shipping_apiship_mode' => 'shipping_apiship_mode_normal', 'shipping_apiship_provider' => [], 'shipping_apiship_parcel_length' => 10, 'shipping_apiship_parcel_width' => 10, 'shipping_apiship_parcel_height' => 10, 'shipping_apiship_parcel_weight' => 500], $api_registry->get('log'));
	$api->responses = $api_responses;

	$api->apiship_providers();
	$api->apiship_providers();

	check('lists: second call is served from cache', $api->requests_to('lists/providers') == 1, (string)$api->requests_to('lists/providers'));

	$api->apiship_points([1, 2]);
	$api->apiship_points([1, 2]);

	check('points: second call with the same ids is served from cache', $api->requests_to('lists/points') == 1, (string)$api->requests_to('lists/points'));

	$api->apiship_points([3]);

	check('points: an id missing from the index is requested from the API', $api->requests_to('lists/points') == 2, (string)$api->requests_to('lists/points'));

	$api->apiship_points([1, 2]);

	check('points: ids loaded earlier are still served from the index after another load', $api->requests_to('lists/points') == 2, (string)$api->requests_to('lists/points'));

	$cart_a = array_map(fn($product) => $product + ['cart_id' => 1001], $products);
	$cart_b = array_map(fn($product) => $product + ['cart_id' => 2002], $products);

	$api->apiship_calculator('RU', 'Москва', 'Москва', '', 'Тверская 10', [], $cart_a, 200.0, false);
	$api->apiship_calculator('RU', 'Москва', 'Москва', '', 'Тверская 10', [], $cart_a, 200.0, false);

	check('calculator: same request is served from cache', $api->requests_to('calculator') == 1, (string)$api->requests_to('calculator'));

	$api->apiship_calculator('RU', 'Москва', 'Москва', '', 'Тверская 10', [], $cart_b, 200.0, false);

	check('calculator: another customer with the same cart and address shares the result (cart_id not in key)', $api->requests_to('calculator') == 1, (string)$api->requests_to('calculator'));

	$api->apiship_calculator('RU', 'Москва', 'Москва', '', 'Тверская 10', [], $cart_a, 200.0, true);

	check('calculator: cash on delivery changes the request → new call', $api->requests_to('calculator') == 2, (string)$api->requests_to('calculator'));

	$api_other_sender = new StubApi($api_registry, ['shipping_apiship_sending_city' => 'Тула'] + $api_params + ['shipping_apiship_rub_select' => 'RUB', 'shipping_apiship_gr_select' => 1, 'shipping_apiship_cm_select' => 1, 'shipping_apiship_mode' => 'shipping_apiship_mode_normal', 'shipping_apiship_provider' => [], 'shipping_apiship_parcel_length' => 10, 'shipping_apiship_parcel_width' => 10, 'shipping_apiship_parcel_height' => 10, 'shipping_apiship_parcel_weight' => 500], $api_registry->get('log'));
	$api_other_sender->responses = $api_responses;

	$api_other_sender->apiship_calculator('RU', 'Москва', 'Москва', '', 'Тверская 10', [], $cart_a, 200.0, false);

	check('calculator: changed sender settings do not hit the old cache entry', $api_other_sender->requests_to('calculator') == 1, (string)$api_other_sender->requests_to('calculator'));

	check('api calls never write to the session', $api_registry->get('session')->data === [], json_encode($api_registry->get('session')->data));
	check('api calls write to the OpenCart cache', count($api_registry->get('cache')->items) >= 5, (string)count($api_registry->get('cache')->items));

	echo "format\n";

	check('format_cost rounds to 2 digits', $lib->format_cost(10.005) == 10.01);
	check('format_weight floors', $lib->format_weight(10.9) == 10.0);
	check('format_dimension rounds', $lib->format_dimension(10.5) == 11.0);


	echo "points index (cache)\n";

	$registry_points = registry();
	$points_lib = new Apiship($registry_points, [
		'shipping_apiship_rub_select' => 'RUB',
		'shipping_apiship_gr_select'  => 1,
		'shipping_apiship_cm_select'  => 1,
		'shipping_apiship_token'      => 'token-a',
		'shipping_apiship_mode'       => 'shipping_apiship_mode_normal',
		'shipping_apiship_provider'   => []
	], $registry_points->get('log'));

	$points_lib->remember_points([
		['id' => 7, 'code' => 'P7', 'name' => 'Точка 7'],
		['id' => '8', 'code' => 'P8', 'name' => 'Точка 8'],
		['code' => 'no-id']
	]);

	check('point found in index by id without API call', $points_lib->apiship_point('7')['code'] == 'P7');
	check('string id is indexed too', $points_lib->apiship_point('8')['name'] == 'Точка 8');
	check('non-digit id → empty array, no API call', $points_lib->apiship_point('7; DROP') === []);
	check('empty id → empty array', $points_lib->apiship_point('') === []);

	$cache_keys = array_keys($registry_points->get('cache')->items);

	check('index stored under token-scoped keys', $cache_keys && count(array_filter($cache_keys, fn($key) => str_starts_with($key, 'apiship.'))) == count($cache_keys), implode(',', $cache_keys));
	check('index is split into shards by id: ids 7 and 8 land in different cache entries', count($cache_keys) == 2, implode(',', $cache_keys));

	$other_session = registry();
	$other_session->set('cache', $registry_points->get('cache'));
	$other_session->get('session')->data['dummy'] = 'other customer';

	$same_token = new Apiship($other_session, [
		'shipping_apiship_rub_select' => 'RUB',
		'shipping_apiship_gr_select'  => 1,
		'shipping_apiship_cm_select'  => 1,
		'shipping_apiship_token'      => 'token-a',
		'shipping_apiship_mode'       => 'shipping_apiship_mode_normal',
		'shipping_apiship_provider'   => []
	], $other_session->get('log'));

	check('cache is shared between customers of one store (same token)', $same_token->apiship_point('7')['code'] == 'P7');

	$other_token = new Apiship($other_session, [
		'shipping_apiship_rub_select' => 'RUB',
		'shipping_apiship_gr_select'  => 1,
		'shipping_apiship_cm_select'  => 1,
		'shipping_apiship_token'      => 'token-b',
		'shipping_apiship_mode'       => 'shipping_apiship_mode_normal',
		'shipping_apiship_provider'   => []
	], $other_session->get('log'));

	check('another token does not see the index', $other_token->cacheGet(Apiship::points_shard_key('7')) === [] || $other_token->cacheGet(Apiship::points_shard_key('7')) === null);

	echo "points loading: memory (OCM-99, Moscow 10k points)\n";

	$full_point = [
		'id' => 1, 'code' => 'P1', 'providerKey' => 'cdek', 'name' => 'Точка 1', 'lat' => 55.7, 'lng' => 37.6, 'type' => 1,
		'regionType' => 'г', 'region' => 'Москва', 'city' => 'Москва', 'cityType' => 'г', 'street' => 'Тверская', 'streetType' => 'ул', 'house' => '1',
		'phone' => '+7', 'timetable' => '9-18', 'description' => 'desc', 'paymentCash' => 1, 'paymentCard' => 1,
		'countryCode' => 'RU', 'fittingRoom' => 1, 'availableOperation' => 3, 'limits' => ['maxWeight' => 30000], 'url' => 'http://x', 'email' => 'a@b'
	];

	$mem_registry = registry();
	$mem = new StubApi($mem_registry, $api_params + ['shipping_apiship_rub_select' => 'RUB', 'shipping_apiship_gr_select' => 1, 'shipping_apiship_cm_select' => 1, 'shipping_apiship_mode' => 'shipping_apiship_mode_debug', 'shipping_apiship_provider' => []], $mem_registry->get('log'));
	$mem->responses = ['lists/points' => ['rows' => [$full_point, ['id' => 2, 'code' => 'P2', 'lat' => 1, 'lng' => 2, 'limits' => []]]]];

	$loaded = $mem->apiship_points([1, 2]);

	check('points: rows are trimmed to the fields the module uses', isset($loaded[0]['lat'], $loaded[0]['street'], $loaded[0]['paymentCash']) && !isset($loaded[0]['limits'], $loaded[0]['fittingRoom'], $loaded[0]['url']), json_encode(array_keys($loaded[0] ?? [])));
	check('points: request asks the API only for the fields it needs', str_contains($mem->requests[0] ?? '', 'fields=id%2Ccode%2C'), $mem->requests[0] ?? '');
	check('points: nothing is cached besides the index shards (no per-id-list copy)', count($mem_registry->get('cache')->items) == 2 && $mem->cacheGet(Apiship::points_shard_key('1'))['1']['code'] == 'P1' && $mem->cacheGet(Apiship::points_shard_key('2'))['2']['code'] == 'P2', implode(',', array_keys($mem_registry->get('cache')->items)));

	$mem->requests = [];
	$mem->responses = ['lists/points' => ['rows' => [['id' => 3, 'code' => 'P3', 'lat' => 1, 'lng' => 2]]]];

	$loaded = $mem->apiship_points([1, 3, 2]);

	check('points: ids already in the index are not requested again', count($mem->requests) == 1 && str_contains($mem->requests[0], urlencode('id=[3]')), implode(' | ', $mem->requests));
	check('points: result joins index hits and freshly loaded rows', count($loaded) == 3, (string)count($loaded));

	$mem->requests = [];
	$mem_registry->get('cache')->sets = [];
	$mem->responses = ['lists/points' => ['rows' => [['id' => 9, 'code' => 'P9']]]];

	$mem->apiship_points(range(100, 2600));

	check('points: 2501 missing ids → 3 batches of 1000', count($mem->requests) == 3, (string)count($mem->requests));
	check('points: each index shard is written once per load, not once per batch', count($mem_registry->get('cache')->sets) == count(array_unique($mem_registry->get('cache')->sets)), (string)count($mem_registry->get('cache')->sets));

	$big_registry = registry();
	$big = new StubApi($big_registry, $api_params + ['shipping_apiship_rub_select' => 'RUB', 'shipping_apiship_gr_select' => 1, 'shipping_apiship_cm_select' => 1, 'shipping_apiship_mode' => 'shipping_apiship_mode_normal', 'shipping_apiship_provider' => []], $big_registry->get('log'));
	$big->remember_points(array_map(fn($id) => ['id' => $id, 'code' => 'P' . $id], range(1, 12000)));

	check('index keeps 12000 points (Moscow) without evicting', $big->apiship_point('1')['code'] == 'P1' && $big->apiship_point('12000')['code'] == 'P12000' && $big->requests === [], implode(' | ', $big->requests));

	$big_registry->get('cache')->gets = [];
	$big->apiship_point('777');

	check('single point lookup reads one cache entry (its shard), not the whole index', count($big_registry->get('cache')->gets) == 1 && $big_registry->get('cache')->gets[0] == $big->cacheKeyFor(Apiship::points_shard_key('777')), implode(',', $big_registry->get('cache')->gets));

	$big_registry->get('cache')->gets = [];
	$big->apiship_points([1, 2, 3]);

	check('loading ids from 3 shards reads 3 cache entries, not all ' . Apiship::POINTS_INDEX_SHARDS, count($big_registry->get('cache')->gets) == 3, (string)count($big_registry->get('cache')->gets));

	$mem->requests = [];
	$mem->responses = ['lists/points' => ['rows' => [['id' => 5, 'code' => 'P5']]]];

	$dup = $mem->apiship_points([5, '5', 5]);

	check('points: duplicate ids are requested once and returned once', count($dup) == 1 && count($mem->requests) == 1 && str_contains($mem->requests[0], urlencode('id=[5]')), count($dup) . ' / ' . implode(' | ', $mem->requests));

	$log_lines = array_filter($mem_registry->get('log')->lines, fn($line) => str_starts_with($line, 'shipping_apiship points'));

	check('points: x-tracing-id of every batch is logged', $log_lines && str_contains(implode("\n", $log_lines), 't-1'), implode("\n", array_slice($log_lines, 0, 1)));

	$mem->requests = [];
	$mem->responses = ['lists/points' => ['rows' => [$full_point]]];

	$by_params = $mem->apiship_point_by_params(['providerKey=cdek']);

	check('point_by_params: rows are trimmed too', isset($by_params[0]['code']) && !isset($by_params[0]['limits']), json_encode(array_keys($by_params[0] ?? [])));
	check('point_by_params: request asks the API only for the fields it needs', str_contains($mem->requests[0] ?? '', 'fields=id%2Ccode%2C'), $mem->requests[0] ?? '');

	$mem_other = new StubApi($mem_registry, $api_params + ['shipping_apiship_rub_select' => 'RUB', 'shipping_apiship_gr_select' => 1, 'shipping_apiship_cm_select' => 1, 'shipping_apiship_mode' => 'shipping_apiship_mode_normal', 'shipping_apiship_provider' => []], $mem_registry->get('log'));
	$mem_other->responses = ['lists/providers' => ['rows' => [$full_point]]];

	check('other lists are not trimmed', isset($mem_other->apiship_providers()[0]['limits']));

	echo "map: tariffs and points\n";

	$map_providers = [
		['providerKey' => 'cdek', 'tariffs' => [
			['tariffId' => 136, 'tariffName' => 'Склад-склад', 'tariffDescription' => 'до ПВЗ', 'daysMin' => 2, 'daysMax' => 4, 'deliveryCost' => 350, 'pickupTypes' => [1, 2], 'pointIds' => [1, 2, 3]],
			['tariffId' => 137, 'tariffName' => 'Экспресс', 'daysMin' => 1, 'daysMax' => 2, 'deliveryCost' => 500, 'pickupTypes' => [1], 'pointIds' => [1, 99]]
		]],
		['providerKey' => 'boxberry', 'tariffs' => [
			['tariffId' => 1, 'tariffName' => 'Boxberry', 'daysMin' => 3, 'daysMax' => 5, 'deliveryCost' => 300, 'pickupTypes' => [1, 2], 'pointIds' => [4]]
		]],
		['providerKey' => 'off', 'tariffs' => [
			['tariffId' => 5, 'tariffName' => 'Off', 'deliveryCost' => 100, 'pickupTypes' => [1, 2], 'pointIds' => [1]]
		]]
	];

	$map_tariffs = Apiship::map_tariffs($map_providers, ['cdek' => [1, 2], 'boxberry' => [2], 'off' => []]);

	check('one tariff per tariff and pickup type enabled both in tariff and settings', array_keys($map_tariffs) == ['cdek_136_1', 'cdek_136_2', 'cdek_137_1', 'boxberry_1_2'], implode(',', array_keys($map_tariffs)));
	check('code template: provider, tariff, pickup type around the point placeholder', $map_tariffs['cdek_136_2']['code_template'] == 'apiship.point_cdek_136_' . Apiship::POINT_ID_PLACEHOLDER . '_2', $map_tariffs['cdek_136_2']['code_template']);
	check('tariff fields: name, cost, days, point ids as strings', $map_tariffs['cdek_136_1']['name'] == 'Склад-склад' && $map_tariffs['cdek_136_1']['cost'] === 350.0 && $map_tariffs['cdek_136_1']['days_min'] == 2 && $map_tariffs['cdek_136_1']['days_max'] == 4 && $map_tariffs['cdek_136_1']['point_ids'] === ['1', '2', '3']);
	check('missing tariffDescription is an empty string', $map_tariffs['cdek_137_1']['description'] === '');
	check('no providers → no tariffs', Apiship::map_tariffs([], []) === []);

	$map_rows = [
		['id' => 1, 'name' => 'A', 'type' => 1, 'lat' => 55.1, 'lng' => 37.1],
		['id' => 2, 'name' => 'B', 'type' => 1, 'lat' => 55.2, 'lng' => 37.2],
		['id' => 4, 'name' => 'D', 'type' => 2, 'lat' => 55.4, 'lng' => 37.4]
	];

	$grouped = Apiship::map_points($map_tariffs, $map_rows);

	check('each point once, keyed by id', array_keys($grouped['points']) == ['1', '2', '4'], implode(',', array_keys($grouped['points'])));
	check('point lists every tariff serving it', $grouped['points']['1']['tariffs'] == ['cdek_136_1', 'cdek_136_2', 'cdek_137_1'], implode(',', $grouped['points']['1']['tariffs']));
	check('point keeps its lists/points row', $grouped['points']['4']['point']['name'] == 'D' && $grouped['points']['4']['tariffs'] == ['boxberry_1_2']);
	check('points absent from lists/points are skipped', !isset($grouped['points']['3']) && !isset($grouped['points']['99']));
	check('tariffs lose point_ids, all used tariffs kept', count($grouped['tariffs']) == 4 && !isset($grouped['tariffs']['cdek_136_1']['point_ids']));

	$unused = Apiship::map_points(['x_1_1' => ['point_ids' => ['7']]], $map_rows);

	check('tariff without a known point is dropped', $unused['tariffs'] === [] && $unused['points'] === []);

	// Один tariffId в двух записях калькулятора (зоны): своя цена и свой набор точек у каждой
	$zones = Apiship::map_tariffs([['providerKey' => 'cdek', 'tariffs' => [
		['tariffId' => 136, 'tariffName' => 'Зона 1', 'deliveryCost' => 350, 'pickupTypes' => [1], 'pointIds' => [1, 3]],
		['tariffId' => 136, 'tariffName' => 'Зона 2', 'deliveryCost' => 450, 'pickupTypes' => [1], 'pointIds' => [2, 3]]
	]]], ['cdek' => [1]]);

	check('same tariffId twice: two tariffs with distinct keys', array_keys($zones) == ['cdek_136_1', 'cdek_136_1_2'] && $zones['cdek_136_1']['cost'] === 350.0 && $zones['cdek_136_1_2']['cost'] === 450.0, implode(',', array_keys($zones)));
	check('same tariffId twice: same code template (set_point resolves by point id)', $zones['cdek_136_1']['code_template'] === $zones['cdek_136_1_2']['code_template']);

	$zoned = Apiship::map_points($zones, [['id' => 1], ['id' => 2], ['id' => 3]]);

	check('zones: each point references the record with its own price', $zoned['points']['1']['tariffs'] == ['cdek_136_1'] && $zoned['points']['2']['tariffs'] == ['cdek_136_1_2'], json_encode(array_map(fn($p) => $p['tariffs'], $zoned['points'])));
	check('zones: point in both records keeps the last one, as set_point does', $zoned['points']['3']['tariffs'] == ['cdek_136_1_2'], implode(',', $zoned['points']['3']['tariffs']));
	check('zones: both tariffs stay in the dictionary', count($zoned['tariffs']) == 2);

	$only_dup = Apiship::map_points($zones, [['id' => 3]]);

	check('zones: a record left without points after dedupe is dropped', array_keys($only_dup['tariffs']) == ['cdek_136_1_2'], implode(',', array_keys($only_dup['tariffs'])));

	echo "map: point_code\n";

	$map_code = Apiship::point_code($map_tariffs['cdek_136_2'], '3');
	$map_parsed = library()->parce_code($map_code);

	check('code with point id', $map_code == 'apiship.point_cdek_136_3_2', $map_code);
	check('round trip through parce_code', $map_parsed['provider'] == 'cdek' && $map_parsed['tariff_id'] == '136' && $map_parsed['point_id'] == '3' && $map_parsed['pickup_type'] == '2');
	check('tariff without template → empty code', Apiship::point_code([], '3') === '');

	// Приёмка: 321 точки по три варианта — каждая точка один раз, набор пар «точка, тариф, цена» равен прежнему перечислению
	$big_ids = range(1000, 1320);
	$big_providers = [['providerKey' => 'cdek', 'tariffs' => [
		['tariffId' => 136, 'tariffName' => 'A', 'deliveryCost' => 350, 'pickupTypes' => [1, 2], 'pointIds' => $big_ids],
		['tariffId' => 137, 'tariffName' => 'B', 'deliveryCost' => 500, 'pickupTypes' => [1], 'pointIds' => $big_ids]
	]]];
	$big_rows = array_map(fn($id) => ['id' => $id, 'name' => 'P' . $id, 'type' => 1, 'lat' => 55, 'lng' => 37], $big_ids);

	$big_map = Apiship::map_points(Apiship::map_tariffs($big_providers, ['cdek' => [1, 2]]), $big_rows);

	$expected_pairs = [];

	foreach ($big_providers[0]['tariffs'] as $tariff) {
		foreach ($tariff['pointIds'] as $pid) {
			foreach ($tariff['pickupTypes'] as $pt) {
				$expected_pairs[] = 'apiship.point_cdek_' . $tariff['tariffId'] . '_' . $pid . '_' . $pt . '=' . $tariff['deliveryCost'];
			}
		}
	}

	$actual_pairs = [];

	foreach ($big_map['points'] as $pid => $item) {
		foreach ($item['tariffs'] as $key) {
			$actual_pairs[] = Apiship::point_code($big_map['tariffs'][$key], (string)$pid) . '=' . $big_map['tariffs'][$key]['cost'];
		}
	}

	sort($expected_pairs);
	sort($actual_pairs);

	check('321 points: each point once', count($big_map['points']) == 321, (string)count($big_map['points']));
	check('321 points: (point, tariff, cost) pairs equal the per-pair enumeration (963 codes)', $expected_pairs === $actual_pairs && count($actual_pairs) == 963, (string)count($actual_pairs));
	check('321 points: tariffs dictionary has 3 entries, not 963', count($big_map['tariffs']) == 3, (string)count($big_map['tariffs']));

	echo "\n" . ($failures ? "FAILED: $failures, passed: $passed" : "All $passed tests passed") . "\n";

	exit($failures ? 1 : 0);
}
