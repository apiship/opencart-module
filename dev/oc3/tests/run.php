<?php
/**
 * Юнит-тесты библиотеки пакетов 2.1/2.3/3.x без ядра OpenCart.
 * Запуск: make test-oc3 (docker php:7.4-cli и php:8.3-cli) или APISHIP_PACKAGE=3.x php dev/oc3/tests/run.php
 * Пакет выбирается переменной окружения APISHIP_PACKAGE (2.1, 2.3, 3.x); библиотеки пакетов отличаются только заголовком Platform.
 */

$package = getenv('APISHIP_PACKAGE') ? getenv('APISHIP_PACKAGE') : '3.x';

$library = __DIR__ . '/../../../' . $package . '/upload/system/library/apiship/apiship.php';

if (!is_file($library)) {
	fwrite(STDERR, "no library for package $package: $library\n");
	exit(2);
}

require_once $library;

// Минимальный реестр вместо ядра OpenCart
class Registry {
	private $data = array();

	public function get($key) {
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}

	public function set($key, $value) {
		$this->data[$key] = $value;
	}

	public function has($key) {
		return isset($this->data[$key]);
	}
}

class StubSession {
	public $data = array();
}

// Кеш OpenCart 2/3: set($key, $value) без TTL, get() возвращает false/пустой массив при промахе
class StubCache {
	public $items = array();
	public $sets = array();
	public $gets = array();

	public function get($key) {
		$this->gets[] = $key;

		return isset($this->items[$key]) ? $this->items[$key] : false;
	}

	public function set($key, $value) {
		$this->items[$key] = $value;
		$this->sets[] = $key;
	}

	public function delete($key) {
		unset($this->items[$key]);
	}
}

class StubLog {
	public $lines = array();

	public function write($message) {
		$this->lines[] = (string)$message;
	}

	public function text() {
		return implode("\n", $this->lines);
	}
}

class StubLoader {
	public function model($route) {
	}
}

class StubProductModel {
	public $products = array();

	public function getProduct($product_id) {
		return isset($this->products[$product_id]) ? $this->products[$product_id] : array();
	}
}

class StubConverter {
	public function convert($value, $from, $to) {
		return $value;
	}
}

class StubConfig {
	public function get($key) {
		return $key == 'config_currency' ? 'RUB' : null;
	}
}

// Библиотека без сети: ответы API подставляются по подстроке url, каждый запрос записывается
class StubApi extends Apiship {
	public $responses = array();
	public $requests = array();
	public $http_code = 200;
	public $reg = null;

	protected function curl_request($url, $body) {
		$this->requests[] = $url;

		foreach ($this->responses as $needle => $payload) {
			if (strpos($url, $needle) !== false) {
				return array('body' => json_encode($payload), 'headers' => array('x-tracing-id' => array('t-1')), 'code' => $this->http_code);
			}
		}

		return array('body' => '{"message":"not found"}', 'headers' => array(), 'code' => 404);
	}

	public function requests_to($needle) {
		$count = 0;

		foreach ($this->requests as $url) {
			if (strpos($url, $needle) !== false) $count++;
		}

		return $count;
	}

	public function last_request($needle) {
		$last = '';

		foreach ($this->requests as $url) {
			if (strpos($url, $needle) !== false) $last = $url;
		}

		return $last;
	}

	public function cacheKeyFor($key) {
		return $this->cacheKey($key);
	}
}

function registry() {
	$registry = new Registry();

	$registry->set('session', new StubSession());
	$registry->set('cache', new StubCache());
	$registry->set('log', new StubLog());
	$registry->set('load', new StubLoader());
	$registry->set('length', new StubConverter());
	$registry->set('weight', new StubConverter());
	$registry->set('currency', new StubConverter());
	$registry->set('config', new StubConfig());

	$products = new StubProductModel();
	$products->products = array(
		10 => array('sku' => 'SKU-10', 'upc' => '', 'weight_class_id' => 1, 'length_class_id' => 1),
		20 => array('sku' => '', 'upc' => 'UPC-20', 'weight_class_id' => 1, 'length_class_id' => 1)
	);

	$registry->set('model_catalog_product', $products);

	return $registry;
}

function library($params = array(), $registry = null) {
	$registry = $registry ? $registry : registry();

	$defaults = array(
		'shipping_apiship_rub_select'    => 'RUB',
		'shipping_apiship_gr_select'     => 1,
		'shipping_apiship_cm_select'     => 1,
		'shipping_apiship_token'         => 'test',
		'shipping_apiship_mode'          => 'shipping_apiship_mode_debug',
		'shipping_apiship_provider'      => array(),
		'shipping_apiship_articul_mode'  => 'sku',
		'shipping_apiship_parcel_length' => 10,
		'shipping_apiship_parcel_width'  => 10,
		'shipping_apiship_parcel_height' => 10,
		'shipping_apiship_parcel_weight' => 500,
		'shipping_apiship_error_timeout' => 'timeout',
		'shipping_apiship_no_shipping'   => 'no shipping for %s',
		'shipping_apiship_error_select_city' => 'select city'
	);

	$api = new StubApi($registry, $params + $defaults, $registry->get('log'));
	$api->reg = $registry;

	return $api;
}

$failures = 0;
$passed = 0;

function check($name, $condition, $details = '') {
	global $failures, $passed;

	if ($condition) {
		$passed++;

		echo "  ok   $name\n";
	} else {
		$failures++;

		echo "  FAIL $name" . ($details ? " — $details" : '') . "\n";
	}
}

echo "package $package, PHP " . PHP_VERSION . "\n";

echo "check_cron_key\n";

check('header X-Apiship-Key', Apiship::check_cron_key('secret', array('HTTP_X_APISHIP_KEY' => 'secret'), array()));
check('POST field key', Apiship::check_cron_key('secret', array(), array('key' => 'secret')));
check('wrong header', !Apiship::check_cron_key('secret', array('HTTP_X_APISHIP_KEY' => 'other'), array()));
check('key only in query string is ignored', !Apiship::check_cron_key('secret', array('QUERY_STRING' => 'key=secret'), array()));
check('empty configured key never matches', !Apiship::check_cron_key('', array('HTTP_X_APISHIP_KEY' => ''), array('key' => '')));
check('array in POST key is not a key', !Apiship::check_cron_key('secret', array(), array('key' => array('secret'))));

echo "esc / safe_url\n";

check('esc escapes html', Apiship::esc('<script>"a"&\'b\'') === '&lt;script&gt;&quot;a&quot;&amp;&#039;b&#039;');
check('safe_url https', Apiship::safe_url('https://files.apiship.ru/label.pdf') === 'https://files.apiship.ru/label.pdf');
check('safe_url http', Apiship::safe_url('http://files.apiship.ru/label.pdf') === 'http://files.apiship.ru/label.pdf');
check('safe_url javascript rejected', Apiship::safe_url('javascript:alert(1)') === '');
check('safe_url ftp rejected', Apiship::safe_url('ftp://files.apiship.ru/label.pdf') === '');
check('safe_url relative rejected', Apiship::safe_url('/label.pdf') === '');
check('safe_url empty', Apiship::safe_url('') === '');

echo "parce_code\n";

$lib = library();

$point = $lib->parce_code('apiship.point_cdek_136_3_2');

check('point: fields', $point['delivery_type'] == 'point' && $point['provider'] == 'cdek' && $point['tariff_id'] == '136' && $point['point_id'] == '3' && $point['pickup_type'] == '2' && $point['short_code'] == 'point_cdek_136_3_2');

$door = $lib->parce_code('apiship.door_cdek_137_1');

check('door: pickup_type from 4th part, point_id empty', $door['delivery_type'] == 'door' && $door['pickup_type'] == '1' && $door['point_id'] === '');

$empty = $lib->parce_code('flat.flat');

check('foreign code: no crash', $empty['provider'] === '' && $empty['short_code'] == 'flat');

echo "get_address\n";

check('no regionType → empty string', $lib->get_address(array('region' => 'Москва')) === '');

$address = $lib->get_address(array(
	'regionType' => 'г',
	'region'     => 'Москва',
	'city'       => 'Москва',
	'cityType'   => 'г',
	'street'     => 'Тверская',
	'streetType' => 'ул',
	'house'      => '12',
	'block'      => '',
	'office'     => '5'
));

check('city-region address', $address === 'Москва, ул Тверская, д.12, офис 5', $address);

$address = $lib->get_address(array(
	'regionType' => 'обл',
	'region'     => 'Московская',
	'city'       => 'Химки',
	'cityType'   => 'г',
	'street'     => 'Ленина',
	'streetType' => 'ул',
	'house'      => 'д. 7',
	'block'      => '2',
	'postIndex'  => '141400'
), true);

check('region address with postcode and block', $address === '141400, Московская обл, г Химки, ул Ленина д. 7 корпус 2', $address);

// Обрезанная точка без streetType/cityType (только поля из fields): адрес строится без notice
$warnings = array();
set_error_handler(function($errno, $errstr) use (&$warnings) { $warnings[] = $errstr; return true; });
$trimmed = $lib->get_address(array('regionType' => 'г', 'region' => 'Москва', 'city' => 'Москва', 'street' => 'Тверская', 'house' => '3'));
restore_error_handler();

check('trimmed point (no streetType/cityType) builds address without warnings', $trimmed === 'Москва,  Тверская, д.3' && !$warnings, $trimmed . ' ' . implode('; ', $warnings));

echo "session data\n";

$lib->setData('k', array('a' => 1), 10);

check('getData returns stored value', $lib->getData('k') == array('a' => 1));
check('getData null for missing key', $lib->getData('missing') === null);
check('stored in OpenCart session, not \$_SESSION', isset($lib->session->data['shipping_apiship']['k']) && !isset($_SESSION));

$lib->session->data['shipping_apiship']['k']['time'] = time() - 1;

check('expired session value is null', $lib->getData('k') === null);

$lib->setData('forever', 'x', 0);

check('timeout 0 = no expiry', $lib->getData('forever') === 'x');

echo "OpenCart cache\n";

$registry = registry();
$lib = library(array(), $registry);
$cache = $registry->get('cache');

$lib->cacheSet('k1', array('v' => 1), 10);

check('cacheGet returns value', $lib->cacheGet('k1') == array('v' => 1));
check('cache key is prefixed and md5-safe', preg_match('/^apiship\.[0-9a-f]{32}$/', $lib->cacheKeyFor('k1')) === 1, $lib->cacheKeyFor('k1'));
check('cache key depends on token', library(array('shipping_apiship_token' => 'other'))->cacheKeyFor('k1') != $lib->cacheKeyFor('k1'));
check('cache key does not depend on session', $lib->cacheKeyFor('k1') === library(array(), registry())->cacheKeyFor('k1'));

$cache->items[$lib->cacheKeyFor('k1')]['time'] = time() - 1;

check('own TTL inside value: expired → null', $lib->cacheGet('k1') === null);
check('missing key → null (cache returns false)', $lib->cacheGet('nope') === null);

$no_cache = new StubApi(new Registry(), array('shipping_apiship_token' => 't'), new StubLog());
$no_cache->cacheSet('x', 1);

check('no cache in registry: cacheSet/cacheGet are no-op', $no_cache->cacheGet('x') === null);

echo "apiship_data: lists cache, fields, pagination\n";

$lib = library();
$lib->responses = array(
	'lists/providers' => array('rows' => array(array('key' => 'cdek', 'name' => 'СДЭК'), array('key' => 'boxberry', 'name' => 'Boxberry'))),
	'lists/statuses'  => array('rows' => array(array('key' => 'created', 'name' => 'Создан')))
);

$providers = $lib->apiship_providers();
$lib->apiship_providers();

check('providers loaded', count($providers) == 2);
check('second call served from cache (one request)', $lib->requests_to('lists/providers') == 1, (string)$lib->requests_to('lists/providers'));
check('short page → no extra empty-page request', $lib->requests_to('lists/providers') == 1);

// Другой покупатель: свой Registry и своя сессия, общий только кеш магазина
function other_customer($lib, $params = array()) {
	$registry = registry();
	$registry->set('cache', $lib->reg->get('cache'));

	return library($params, $registry);
}

$shared = other_customer($lib);
$shared->responses = $lib->responses;
$shared->apiship_providers();

check('another customer (own session, same store cache): no API request', count($shared->requests) == 0, (string)count($shared->requests));
check('other customer session stays empty (lists are not in the session)', empty($shared->reg->get('session')->data));

$lib->responses['lists/points'] = array('rows' => array(
	array('id' => 5, 'code' => 'P5', 'providerKey' => 'cdek', 'name' => 'ПВЗ 5', 'type' => 1, 'lat' => 1, 'lng' => 2, 'regionType' => 'г', 'region' => 'Москва', 'cityType' => 'г', 'city' => 'Москва', 'street' => 'Тверская', 'streetType' => 'ул', 'house' => '1', 'postIndex' => '101000', 'phone' => '1', 'timetable' => 'пн-пт', 'description' => 'd', 'paymentCash' => 1, 'paymentCard' => 0, 'extraHuge' => str_repeat('x', 100), 'workTime' => 'x')
));

$points = $lib->apiship_point_by_params(array('id=5'));

check('lists/points requested with fields=', strpos($lib->last_request('lists/points'), 'fields=' . urlencode('id,code,providerKey')) !== false, $lib->last_request('lists/points'));
check('points rows trimmed to module fields', isset($points[0]['id']) && !isset($points[0]['extraHuge']) && !isset($points[0]['workTime']));
check('lists/providers requested without fields', strpos($lib->last_request('lists/providers'), 'fields=') === false);

$log = $lib->reg->get('log')->text();

check('log has row counts, not full lists', strpos($log, '[rows] => 2') !== false && strpos($log, 'СДЭК') === false, substr($log, 0, 200));
check('log has x_tracing_id', strpos($log, 'x_tracing_id') !== false);

$err = library();
$err->responses = array('lists/providers' => array('message' => 'Unauthorized', 'code' => '401'), 'connections' => array('message' => 'Unauthorized', 'code' => '401'));

check('API message → [message => ...]', $err->apiship_providers() == array('message' => 'Unauthorized'));
check('get_providers passes API message', $err->get_providers() == array('message' => 'Unauthorized', 'providers' => array()));
check('API message is not cached', $err->apiship_providers() == array('message' => 'Unauthorized') && $err->requests_to('lists/providers') == 2);

echo "apiship_points: index in cache, only missing ids requested\n";

function point_row($id) {
	return array('id' => $id, 'code' => 'P' . $id, 'providerKey' => 'cdek', 'name' => 'ПВЗ ' . $id, 'type' => 1, 'lat' => 1, 'lng' => 2, 'regionType' => 'г', 'region' => 'Москва', 'cityType' => 'г', 'city' => 'Москва', 'garbage' => 'x');
}

$lib = library();
$lib->responses = array('lists/points' => array('rows' => array(point_row(1), point_row(2), point_row(65))));

$points = $lib->apiship_points(array(1, 2, 65));

check('three points loaded', count($points) == 3);
check('request filter has ids (grouped by shard), fields', strpos($lib->last_request('lists/points'), urlencode('id=[1,65,2]')) !== false && strpos($lib->last_request('lists/points'), 'fields=') !== false, $lib->last_request('lists/points'));
check('rows trimmed', !isset($points[0]['garbage']));

$shard_1 = $lib->cacheGet(Apiship::points_shard_key('1'));

check('shard for id 1 holds points 1 and 65 (65 % 64 = 1)', isset($shard_1['1']) && isset($shard_1['65']) && !isset($shard_1['2']));
check('shard for id 2 holds point 2', ($s = $lib->cacheGet(Apiship::points_shard_key('2'))) && isset($s['2']));

$before = count($lib->requests);
$again = $lib->apiship_points(array(1, 2, 65));

check('second call: no API request', count($lib->requests) == $before && count($again) == 3);

$lib->responses = array('lists/points' => array('rows' => array(point_row(3))));
$superset = $lib->apiship_points(array(1, 2, 3));

check('superset: only missing id requested', strpos($lib->last_request('lists/points'), urlencode('id=[3]')) !== false && count($superset) == 3, $lib->last_request('lists/points'));

$before = count($lib->requests);
$one = $lib->apiship_point('65');

check('apiship_point from index: no request', count($lib->requests) == $before && $one['id'] == 65);
check('apiship_point rejects non-numeric id', $lib->apiship_point('error') === array() && $lib->apiship_point('') === array());

$lib->responses = array('lists/points' => array('rows' => array(point_row(9))));
$nine = $lib->apiship_point('9');

check('apiship_point unknown id: one request, then indexed', $nine['id'] == 9 && strpos($lib->last_request('lists/points'), urlencode('id=9')) !== false);

$before = count($lib->requests);
$lib->apiship_point('9');

check('apiship_point second call cached', count($lib->requests) == $before);

$log = $lib->reg->get('log')->text();

check('points log without full point rows', strpos($log, 'ПВЗ 1') === false && strpos($log, '[rows] => 3') !== false);

$limit = library();
$rows = array();
for ($i = 0; $i < Apiship::POINTS_INDEX_SHARD_LIMIT + 5; $i++) $rows[] = point_row($i * Apiship::POINTS_INDEX_SHARDS);
$limit->remember_points($rows);
$shard = $limit->cacheGet(Apiship::points_shard_key('0'));

check('shard limited to POINTS_INDEX_SHARD_LIMIT', count($shard) == Apiship::POINTS_INDEX_SHARD_LIMIT, (string)count($shard));

$fail = library();
$fail->responses = array('lists/points' => array('errors' => array(array('message' => 'bad'))));

check('points API error → empty array, nothing cached', $fail->apiship_points(array(1)) === array() && $fail->cacheGet(Apiship::points_shard_key('1')) === null);

echo "calculator cache\n";

$products = array(
	array('product_id' => 10, 'name' => 'Товар A', 'model' => 'A', 'quantity' => 2, 'price' => 100, 'length' => 20, 'width' => 10, 'height' => 5, 'length_class_id' => 1, 'weight' => 800, 'weight_class_id' => 1),
	array('product_id' => 20, 'name' => 'Товар B', 'model' => 'B', 'quantity' => 1, 'price' => 50, 'length' => 0, 'width' => 0, 'height' => 0, 'length_class_id' => 1, 'weight' => 0, 'weight_class_id' => 1)
);

$calc_ok = array('deliveryToPoint' => array(array('providerKey' => 'cdek', 'tariffs' => array(array('tariffId' => 1, 'tariffName' => 'T', 'deliveryCost' => 100, 'daysMin' => 1, 'daysMax' => 2, 'pickupTypes' => array(1), 'pointIds' => array(111, 222))))), 'deliveryToDoor' => array());

$lib = library();
$lib->responses = array('calculator' => $calc_ok);

$r1 = $lib->apiship_calculator('RU', 'Москва', 'Москва', '', '', array(), $products, 200.0, false);
$r2 = $lib->apiship_calculator('RU', 'Москва', 'Москва', '', '', array(), $products, 200.0, false);

check('calculator result', isset($r1['body']['deliveryToPoint']) && $r1['x-tracing-id'] == 't-1');
check('same request served from cache', $lib->requests_to('calculator') == 1 && $r2['body'] == $r1['body']);

$lib->apiship_calculator('RU', 'Москва', 'Химки', '', '', array(), $products, 200.0, false);

check('different address → new request', $lib->requests_to('calculator') == 2);

$other = other_customer($lib);
$other->responses = array('calculator' => $calc_ok);
$other->apiship_calculator('RU', 'Москва', 'Москва', '', '', array(), $products, 200.0, false);

check('other customer (own session), same cart and address: no request', count($other->requests) == 0);
check('calculator result is not in the session', empty($other->reg->get('session')->data));

$log = $lib->reg->get('log')->text();

check('calculator log: pointIds replaced with count', strpos($log, '2 ids') !== false && strpos($log, '[0] => 111') === false);

$empty = library();

check('empty city → message without request', $empty->apiship_calculator('RU', '', '', '', '', array(), $products, 200.0, false) == array('body' => array('message' => 'select city'), 'x-tracing-id' => '') && count($empty->requests) == 0);

$err = library();
$err->responses = array('calculator' => array('errors' => array(array('message' => 'no tariffs'))));

$e1 = $err->apiship_calculator('RU', 'Москва', 'Москва', '', '', array(), $products, 200.0, false);
$err->apiship_calculator('RU', 'Москва', 'Москва', '', '', array(), $products, 200.0, false);

check('errors → no_shipping message', $e1['body'] == array('message' => 'no shipping for Москва, Москва'));
check('errors not cached', $err->requests_to('calculator') == 2);

$http = library();
$http->responses = array('calculator' => array('message' => 'Too Many Requests'));
$http->http_code = 429;

$h1 = $http->apiship_calculator('RU', 'Москва', 'Москва', '', '', array(), $products, 200.0, false);
$http->apiship_calculator('RU', 'Москва', 'Москва', '', '', array(), $products, 200.0, false);

check('HTTP 429 → API message, not cached', $h1['body'] == array('message' => 'Too Many Requests') && $http->requests_to('calculator') == 2);

$broken = library();
$broken->responses = array('calculator' => 'not json at all');

$b1 = $broken->apiship_calculator('RU', 'Москва', 'Москва', '', '', array(), $products, 200.0, false);

check('non-object body → timeout message', $b1['body'] == array('message' => 'timeout'));

echo "calculate_places\n";

$lib = library();
$result = $lib->calculate_places($products, 200.0);

$items_total = 0;
foreach ($result['items'] as $item) $items_total += $item['cost'] * $item['quantity'];

check('items cost sum equals order total after discount', abs($items_total - 200.0) < 0.01, (string)$items_total);
check('articul from sku', in_array('SKU-10', array_map(function($i) { return $i['articul']; }, $result['items'])));
check('fallback to parcel defaults for product without dimensions', $result['total_weight'] == 800 + 500, (string)$result['total_weight']);
check('place length = max side', $result['total_length'] == 20);
check('place height = sum of smallest sides', $result['total_height'] == 5 + 5 + 10, (string)$result['total_height']);
check('assessed cost equals items cost by default', abs($result['assessed_cost'] - 200.0) < 0.01);

$fixed = library(array('shipping_apiship_use_fix_product_assessed_cost' => 1, 'shipping_apiship_fix_product_assessed_cost' => 10))->calculate_places($products, 200.0);

check('fixed assessed cost per item', abs($fixed['assessed_cost'] - 30.0) < 0.01, (string)$fixed['assessed_cost']);

$override = library(array('shipping_apiship_place_weight' => 2000, 'shipping_apiship_package_weight' => 100))->calculate_places($products, 200.0);

check('place weight override + package weight', $override['total_weight'] == 2100, (string)$override['total_weight']);

echo "distribute_place_weight\n";

$weights = $lib->distribute_place_weight(array(array('quantity' => 2, 'weight' => 100), array('quantity' => 1, 'weight' => 100)), 1000.0);

check('per-unit weight = floor(1000 / 3) = 333, remainder to single last item', $weights[0]['weight'] == 333 && $weights[1]['weight'] == 334 && count($weights) == 2);

$weights = $lib->distribute_place_weight(array(array('quantity' => 1, 'weight' => 100), array('quantity' => 2, 'weight' => 100)), 1000.0);

$sum = 0;
foreach ($weights as $item) $sum += $item['weight'] * $item['quantity'];

check('last item with quantity > 1 is split, sum equals place weight', count($weights) == 3 && $sum == 1000, (string)$sum);

echo "orders\n";

$lib = library();
$lib->responses = array('orders/labels' => array('url' => 'https://f/label.pdf'), 'orders/waybills' => array('waybillItems' => array()));

check('labels', $lib->apiship_labels(array(1, 2)) == array('body' => array('url' => 'https://f/label.pdf'), 'x-tracing-id' => 't-1'));
check('waybills', isset($lib->apiship_waybills(array(1))['body']['waybillItems']));

$lib->responses = array('orders/status?clientNumber=' => array('orderInfo' => array('orderId' => 7)));
$lib->apiship_oc_order_status('a b&c');

check('clientNumber urlencoded', strpos($lib->last_request('orders/status'), 'clientNumber=a+b%26c') !== false, $lib->last_request('orders/status'));

$lib->responses = array('orders/5/status' => 'garbage');

check('non-json status → empty array', $lib->apiship_order_status(5) === array());

echo "map: tariffs and points\n";

$map_providers = array(
	array('providerKey' => 'cdek', 'tariffs' => array(
		array('tariffId' => 136, 'tariffName' => 'Склад-склад', 'tariffDescription' => 'до ПВЗ', 'daysMin' => 2, 'daysMax' => 4, 'deliveryCost' => 350, 'pickupTypes' => array(1, 2), 'pointIds' => array(1, 2, 3)),
		array('tariffId' => 137, 'tariffName' => 'Экспресс', 'daysMin' => 1, 'daysMax' => 2, 'deliveryCost' => 500, 'pickupTypes' => array(1), 'pointIds' => array(1, 99))
	)),
	array('providerKey' => 'boxberry', 'tariffs' => array(
		array('tariffId' => 1, 'tariffName' => 'Boxberry', 'daysMin' => 3, 'daysMax' => 5, 'deliveryCost' => 300, 'pickupTypes' => array(1, 2), 'pointIds' => array(4))
	)),
	array('providerKey' => 'off', 'tariffs' => array(
		array('tariffId' => 5, 'tariffName' => 'Off', 'deliveryCost' => 100, 'pickupTypes' => array(1, 2), 'pointIds' => array(1))
	))
);

$map_tariffs = Apiship::map_tariffs($map_providers, array('cdek' => array(1, 2), 'boxberry' => array(2), 'off' => array()));

check('one tariff per tariff and pickup type enabled both in tariff and settings', array_keys($map_tariffs) == array('cdek_136_1', 'cdek_136_2', 'cdek_137_1', 'boxberry_1_2'), implode(',', array_keys($map_tariffs)));
check('code template: provider, tariff, pickup type around the point placeholder', $map_tariffs['cdek_136_2']['code_template'] == 'apiship.point_cdek_136_' . Apiship::POINT_ID_PLACEHOLDER . '_2', $map_tariffs['cdek_136_2']['code_template']);
check('tariff fields: name, cost, days, point ids as strings', $map_tariffs['cdek_136_1']['name'] == 'Склад-склад' && $map_tariffs['cdek_136_1']['cost'] === 350.0 && $map_tariffs['cdek_136_1']['days_min'] == 2 && $map_tariffs['cdek_136_1']['days_max'] == 4 && $map_tariffs['cdek_136_1']['point_ids'] === array('1', '2', '3'));
check('missing tariffDescription is an empty string', $map_tariffs['cdek_137_1']['description'] === '');
check('no providers → no tariffs', Apiship::map_tariffs(array(), array()) === array());

$map_rows = array(
	array('id' => 1, 'name' => 'A', 'type' => 1, 'lat' => 55.1, 'lng' => 37.1),
	array('id' => 2, 'name' => 'B', 'type' => 1, 'lat' => 55.2, 'lng' => 37.2),
	array('id' => 4, 'name' => 'D', 'type' => 2, 'lat' => 55.4, 'lng' => 37.4)
);

$grouped = Apiship::map_points($map_tariffs, $map_rows);

check('each point once, keyed by id', array_keys($grouped['points']) == array('1', '2', '4'), implode(',', array_keys($grouped['points'])));
check('point lists every tariff serving it', $grouped['points']['1']['tariffs'] == array('cdek_136_1', 'cdek_136_2', 'cdek_137_1'), implode(',', $grouped['points']['1']['tariffs']));
check('point keeps its lists/points row', $grouped['points']['4']['point']['name'] == 'D' && $grouped['points']['4']['tariffs'] == array('boxberry_1_2'));
check('points absent from lists/points are skipped', !isset($grouped['points']['3']) && !isset($grouped['points']['99']));
check('tariffs lose point_ids, all used tariffs kept', count($grouped['tariffs']) == 4 && !isset($grouped['tariffs']['cdek_136_1']['point_ids']));

$unused = Apiship::map_points(array('x_1_1' => array('point_ids' => array('7'))), $map_rows);

check('tariff without a known point is dropped', $unused['tariffs'] === array() && $unused['points'] === array());

// Один tariffId в двух записях калькулятора (зоны): своя цена и свой набор точек у каждой
$zones = Apiship::map_tariffs(array(array('providerKey' => 'cdek', 'tariffs' => array(
	array('tariffId' => 136, 'tariffName' => 'Зона 1', 'daysMin' => 1, 'daysMax' => 2, 'deliveryCost' => 350, 'pickupTypes' => array(1), 'pointIds' => array(1, 3)),
	array('tariffId' => 136, 'tariffName' => 'Зона 2', 'daysMin' => 3, 'daysMax' => 5, 'deliveryCost' => 450, 'pickupTypes' => array(1), 'pointIds' => array(2, 3))
))), array('cdek' => array(1)));

check('same tariffId twice: two tariffs with distinct keys', array_keys($zones) == array('cdek_136_1', 'cdek_136_1_2') && $zones['cdek_136_1']['cost'] === 350.0 && $zones['cdek_136_1_2']['cost'] === 450.0, implode(',', array_keys($zones)));
check('same tariffId twice: same code template (set_point resolves by point id)', $zones['cdek_136_1']['code_template'] === $zones['cdek_136_1_2']['code_template']);
check('same tariffId twice: each record keeps its own days and name', $zones['cdek_136_1']['days_min'] == 1 && $zones['cdek_136_1']['days_max'] == 2 && $zones['cdek_136_1_2']['days_min'] == 3 && $zones['cdek_136_1_2']['days_max'] == 5 && $zones['cdek_136_1_2']['name'] == 'Зона 2');

$zoned = Apiship::map_points($zones, array(array('id' => 1), array('id' => 2), array('id' => 3)));

check('zones: each point references the record with its own price', $zoned['points']['1']['tariffs'] == array('cdek_136_1') && $zoned['points']['2']['tariffs'] == array('cdek_136_1_2'), json_encode($zoned['points']));
check('zones: point in both records keeps the last one, as set_point does', $zoned['points']['3']['tariffs'] == array('cdek_136_1_2'), implode(',', $zoned['points']['3']['tariffs']));
check('zones: both tariffs stay in the dictionary', count($zoned['tariffs']) == 2);

$only_dup = Apiship::map_points($zones, array(array('id' => 3)));

check('zones: a record left without points after dedupe is dropped', array_keys($only_dup['tariffs']) == array('cdek_136_1_2'), implode(',', array_keys($only_dup['tariffs'])));

echo "map: point_code\n";

$map_code = Apiship::point_code($map_tariffs['cdek_136_2'], '3');
$map_parsed = library()->parce_code($map_code);

check('code with point id', $map_code == 'apiship.point_cdek_136_3_2', $map_code);
check('round trip through parce_code', $map_parsed['provider'] == 'cdek' && $map_parsed['tariff_id'] == '136' && $map_parsed['point_id'] == '3' && $map_parsed['pickup_type'] == '2');
check('tariff without template → empty code', Apiship::point_code(array(), '3') === '');

// Приёмка: 321 точки по три варианта — каждая точка один раз, набор пар «точка, тариф, цена» равен прежнему перечислению
$big_ids = range(1000, 1320);
$big_providers = array(array('providerKey' => 'cdek', 'tariffs' => array(
	array('tariffId' => 136, 'tariffName' => 'A', 'deliveryCost' => 350, 'pickupTypes' => array(1, 2), 'pointIds' => $big_ids),
	array('tariffId' => 137, 'tariffName' => 'B', 'deliveryCost' => 500, 'pickupTypes' => array(1), 'pointIds' => $big_ids)
)));
$big_rows = array();

foreach ($big_ids as $id) {
	$big_rows[] = array('id' => $id, 'name' => 'P' . $id, 'type' => 1, 'lat' => 55, 'lng' => 37);
}

$big_map = Apiship::map_points(Apiship::map_tariffs($big_providers, array('cdek' => array(1, 2))), $big_rows);

$expected_pairs = array();

foreach ($big_providers[0]['tariffs'] as $tariff) {
	foreach ($tariff['pointIds'] as $pid) {
		foreach ($tariff['pickupTypes'] as $pt) {
			$expected_pairs[] = 'apiship.point_cdek_' . $tariff['tariffId'] . '_' . $pid . '_' . $pt . '=' . $tariff['deliveryCost'];
		}
	}
}

$actual_pairs = array();

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

echo "\n$passed passed, $failures failed\n";

exit($failures ? 1 : 0);
