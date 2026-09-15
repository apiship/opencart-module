<?php
/**
 * HTTP-клиент API ApiShip и общие расчётные функции модуля (OpenCart 2.x/3.x).
 * Используется моделями админки и витрины: new Apiship($registry, $params, $log)
 *
 * Синтаксис — PHP 5.6: без ??, типов свойств и стрелочных функций.
 */
class Apiship {

	private $log;
	private $registry;
	private $apiship_params;
	private $platform = 'opencart_v2.3';

	// Один curl-хэндл на запрос страницы: keep-alive к API вместо нового TLS-соединения на каждый вызов
	private $curl = null;

	/**
	 * TTL кеша (минуты). Срок хранится внутри значения и проверяется при чтении, но он только сокращает
	 * жизнь записи: файл кеша OpenCart 2/3 удаляется через config cache_expire (по умолчанию 3600 с),
	 * продлить его из модуля нельзя. Справочники (точки, службы, статусы, подключения) поэтому живут
	 * min(CACHE_LISTS_MINUTES, cache_expire) — по умолчанию час, 6 часов только при cache_expire >= 21600;
	 * расчёт стоимости зависит от корзины и адреса — 10 минут
	 */
	const CACHE_LISTS_MINUTES = 360;
	const CACHE_CALCULATOR_MINUTES = 10;

	/**
	 * Индекс точек в кеше разбит на шарды по id: точечный запрос (выбранный ПВЗ, экспорт) декодирует один
	 * файл, а не весь справочник. 64 шарда по 1500 обрезанных точек — до 96 000 точек на магазин;
	 * при переполнении шарда старые записи вытесняются и запрашиваются у API заново
	 */
	const POINTS_INDEX_SHARDS = 64;
	const POINTS_INDEX_SHARD_LIMIT = 1500;

	/**
	 * Поля точки lists/points, которые использует модуль (карта, адрес ПВЗ, выбор в чекауте, экспорт, поиск в админке).
	 * Только они запрашиваются у API (параметр fields) и хранятся в кеше: полная строка точки — около 5 КБ,
	 * для Москвы это десятки тысяч точек и выход за memory_limit
	 */
	private static $point_fields = array(
		'id', 'code', 'providerKey', 'name', 'type', 'lat', 'lng',
		'regionType', 'region', 'area', 'cityType', 'city', 'communityType', 'community',
		'streetType', 'street', 'house', 'block', 'office', 'postIndex',
		'phone', 'timetable', 'description', 'paymentCash', 'paymentCard'
	);

	public function __construct($registry, $apiship_params, $log) {
		$this->registry = $registry;
		$this->apiship_params = $apiship_params;
		$this->log = $log;

		$this->apiship_params['shipping_apiship_url'] = "https://api.apiship.ru/v1/";

		// Тестовый контур: define('APISHIP_API_URL', 'http://host/v1/') в config.php, либо define('APISHIP_TEST_MOD', true)
		if (defined('APISHIP_API_URL') && APISHIP_API_URL) {
			$this->apiship_params['shipping_apiship_url'] = rtrim((string)APISHIP_API_URL, '/') . '/';
		} elseif (defined('APISHIP_TEST_MOD')) {
			$this->apiship_params['shipping_apiship_url'] = "http://api.dev.apiship.ru/v1/";
		}
	}

	public function __get($name) {
		return $this->registry->get($name);
	}

	public function __destruct() {
		if ($this->curl !== null) {
			curl_close($this->curl);
			$this->curl = null;
		}
	}

	public function toLog($prefix, $data = '', $error = false) {
		if ($this->isDebug() || $error == true) {
			$this->log->write($prefix . PHP_EOL . print_r($data, 1));
		}
	}

	private function isDebug() {
		return isset($this->apiship_params['shipping_apiship_mode']) && $this->apiship_params['shipping_apiship_mode'] == 'shipping_apiship_mode_debug';
	}

	/**
	 * Проверка cron-ключа: только заголовок X-Apiship-Key или POST-поле key, в query string ключ не принимается,
	 * чтобы он не оседал в логах веб-сервера и прокси
	 *
	 * @param string $config_key ключ из настроек модуля
	 * @param array  $server     $this->request->server
	 * @param array  $post       $this->request->post
	 *
	 * @return bool
	 */
	public static function check_cron_key($config_key, $server, $post) {
		$key = '';

		if (isset($server['HTTP_X_APISHIP_KEY'])) {
			$key = (string)$server['HTTP_X_APISHIP_KEY'];
		} elseif (isset($post['key']) && is_scalar($post['key'])) {
			$key = (string)$post['key'];
		}

		$config_key = (string)$config_key;

		return $config_key != '' && $key != '' && hash_equals($config_key, $key);
	}

	/**
	 * Экранирование строки для вставки в html
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public static function esc($value) {
		return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}

	/**
	 * Ссылка на файл (ярлык, акт, трекинг) — только http(s), иначе пустая строка
	 *
	 * @param mixed $url
	 *
	 * @return string
	 */
	public static function safe_url($url) {
		$url = trim((string)$url);

		if ($url == '' || !filter_var($url, FILTER_VALIDATE_URL)) {
			return '';
		}

		$scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));

		return in_array($scheme, array('http', 'https')) ? $url : '';
	}

	private function getHeaders() {
		$token = isset($this->apiship_params['shipping_apiship_token']) ? (string)$this->apiship_params['shipping_apiship_token'] : '';

		return array('Platform: ' . $this->platform, 'Content-Type: application/json', 'Authorization: ' . $token, 'Accept: application/json');
	}

	private function curl_get($url) {
		return $this->curl_request($url, null);
	}

	private function curl_post($url, $data) {
		return $this->curl_request($url, json_encode($data));
	}

	/**
	 * HTTP-запрос к API: общий хэндл (keep-alive), сжатие ответа, таймауты соединения и ответа
	 *
	 * @param string      $url
	 * @param string|null $body null — GET, иначе POST с JSON-телом
	 *
	 * @return array body, headers, code
	 */
	protected function curl_request($url, $body) {
		$headers = array();

		if ($this->curl === null) {
			$this->curl = curl_init();
		}

		$ch = $this->curl;

		curl_reset($ch);

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders());
		curl_setopt($ch, CURLOPT_HEADERFUNCTION,
			function($curl, $header) use (&$headers)
			{
				$len = strlen($header);
				$header = explode(':', $header, 2);
				if (count($header) < 2) // ignore invalid headers
					return $len;

				$headers[strtolower(trim($header[0]))][] = trim($header[1]);

				return $len;
			}
		);

		if ($body !== null) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		}

		$result = curl_exec($ch);
		if ($result === false)
		{
			$this->log->write('curl error ' . $url . ' ' . print_r(curl_error($ch), 1));
		}

		$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

		return array('body' => $result, 'headers' => $headers, 'code' => $code);
	}

	private static function point_fields_query() {
		return '&fields=' . urlencode(implode(',', self::$point_fields));
	}

	/**
	 * Оставляет в строке точки только поля из $point_fields
	 */
	public static function trim_point($point) {
		return array_intersect_key($point, array_flip(self::$point_fields));
	}

	/**
	 * Ключ шарда индекса для id точки
	 */
	public static function points_shard_key($id) {
		return 'apiship_points_index.' . ((int)$id % self::POINTS_INDEX_SHARDS);
	}

	/**
	 * Постраничная выгрузка списков API с кешированием в кеше OpenCart (не в сессии): ключ — команда и фильтр
	 */
	private function apiship_data($cmd, $limit, $filter_list = '') {

		$data_key = 'apiship_' . $cmd . md5($limit . print_r($filter_list,1));
		$data_hash = md5($cmd . $limit . print_r($filter_list,1));

		$data = $this->cacheGet($data_key);
		if ($data !== null && isset($data['data_hash']) && $data['data_hash'] == $data_hash) {
			$this->toLog($data_key . ' cached');
			return $data['rows'];
		}

		$offset = 0;
		$rows = array();
		$x_tracing_ids = array();
		$url = '';
		$is_points = ($cmd == 'lists/points');

		do {
			$url = $this->apiship_params['shipping_apiship_url'] . $cmd. '?limit='.$limit.'&offset='.$offset;
			if ($filter_list != '') $url = $url . '&filter=' . urlencode(implode(';', (array)$filter_list));
			if ($is_points) $url = $url . self::point_fields_query();

			$output = $this->curl_get($url);

			$data = json_decode((string)$output['body'], true);
			if (isset($output['headers']['x-tracing-id'][0])) $x_tracing_ids[] = $output['headers']['x-tracing-id'][0]; else $x_tracing_ids[] = '?';

			if (isset($data['errors'])) {
				$this->toLog('shipping_apiship_data '.$cmd.' error1', array('url' => $url, 'output' => $output), true);
				return array();
			}

			if (!isset($data['rows'])) {
				$this->toLog('shipping_apiship_data '.$cmd.' error2', array('url' => $url, 'output' => $output), true);
				if (isset($data['message'])) return array('message' => $data['message']);
				return array();
			}

			$rows = array_merge($rows, $is_points ? array_map(array('Apiship', 'trim_point'), $data['rows']) : $data['rows']);

			$rows_count = count($data['rows']);
			$offset = $offset + $rows_count;

			// Страница короче лимита — это последняя; отдельный запрос за пустой страницей не нужен
		} while ($rows_count >= $limit);

		// Справочники в лог не пишем целиком: список точек — сотни килобайт на каждый расчёт
		$this->toLog('shipping_apiship_data '.$cmd, array('url' => $url, 'x_tracing_id' => $x_tracing_ids, 'rows' => count($rows)));

		$this->cacheSet($data_key, array('rows' => $rows, 'data_hash' => $data_hash), self::CACHE_LISTS_MINUTES);
		return $rows;
	}

	/**
	 * Небольшие данные текущего чекаута в сессии OpenCart: адрес расчёта, выбранные ПВЗ, tracing id.
	 * Справочники API сюда класть нельзя — для них cacheSet/cacheGet
	 */
	public function setData($key, $value, $expired_timeout_minuts = 10) {

		if(!isset($value)) return;

		if ($expired_timeout_minuts == 0)
			$this->session->data['shipping_apiship'][$key] = array('value' => $value);
		else
			$this->session->data['shipping_apiship'][$key] = array('value' => $value, 'time' => time() + 60*$expired_timeout_minuts);

	}

	public function getData($key) {

		if (!isset($this->session->data['shipping_apiship'][$key])) return null;

		$item = $this->session->data['shipping_apiship'][$key];

		if (isset($item['time']) && time() > $item['time']) return null;

		return isset($item['value']) ? $item['value'] : null;
	}

	/**
	 * Ключ кеша OpenCart. Справочники и расчёты зависят только от токена и параметров запроса, поэтому кеш
	 * общий для всех покупателей магазина: ключ без идентификатора сессии. Имя файла кеша — cache.<key>.<expire>,
	 * ключ приводится к md5, чтобы в нём не было лишних символов
	 */
	protected function cacheKey($key) {
		$token = isset($this->apiship_params['shipping_apiship_token']) ? (string)$this->apiship_params['shipping_apiship_token'] : '';

		return 'apiship.' . md5($token . '|' . $key);
	}

	/**
	 * Кеш ответов API (калькулятор, точки, списки) в кеше OpenCart, не в сессии
	 */
	public function cacheSet($key, $value, $expired_timeout_minuts = self::CACHE_CALCULATOR_MINUTES) {
		if (!isset($value) || !$this->registry->has('cache')) return;

		$this->cache->set($this->cacheKey($key), array('value' => $value, 'time' => time() + 60 * max(1, (int)$expired_timeout_minuts)));
	}

	public function cacheGet($key) {
		if (!$this->registry->has('cache')) return null;

		$item = $this->cache->get($this->cacheKey($key));

		if (!is_array($item) || !array_key_exists('value', $item)) return null;

		if (isset($item['time']) && time() > $item['time']) return null;

		return $item['value'];
	}

 	public function apiship_providers() {
		return $this->apiship_data('lists/providers', 10000);
	}

 	public function apiship_statuses() {
		return $this->apiship_data('lists/statuses', 10000);
	}

 	public function apiship_connections() {
		return $this->apiship_data('connections', 100);
	}

 	public function apiship_point_by_params($params_list) {
		return $this->apiship_data('lists/points', 10000, $params_list);
	}

 	public function apiship_labels($order_id) {

		$url = $this->apiship_params['shipping_apiship_url'] . 'orders/labels';

		$params = array(
			'orderIds' => $order_id,
			'format' => 'pdf'
		);

		$output = $this->curl_post($url, $params);
		if (isset($output['headers']['x-tracing-id'][0])) $x_tracing_id = $output['headers']['x-tracing-id'][0]; else $x_tracing_id = '?';

		$data = array(
			'body' => json_decode((string)$output['body'], true),
			'x-tracing-id' => $x_tracing_id
		);

		$this->toLog('shipping_apiship_labels', array('url' => $url, 'params' => $params, 'output' => $data), isset($data['body']['errors']));
		return $data;
	}

 	public function apiship_waybills($order_id) {

		$url = $this->apiship_params['shipping_apiship_url'] . 'orders/waybills';

		$params = array(
			'orderIds' => $order_id,
		);

		$output = $this->curl_post($url, $params);
		if (isset($output['headers']['x-tracing-id'][0])) $x_tracing_id = $output['headers']['x-tracing-id'][0]; else $x_tracing_id = '?';

		$data = array(
			'body' => json_decode((string)$output['body'], true),
			'x-tracing-id' => $x_tracing_id
		);

		$this->toLog('shipping_apiship_waybills', array('url' => $url, 'params' => $params, 'output' => $data), isset($data['body']['errors']));
		return $data;
	}

	/**
	 * Точки по списку id: сначала из индекса в кеше, у API запрашиваются только недостающие (порциями по 1000,
	 * только нужные поля). Индекс пополняется один раз после загрузки. Отдельного кеша по набору id нет:
	 * он дублировал индекс и на Москве (десятки тысяч точек) не помещался в memory_limit
	 */
 	public function apiship_points($points) {

		$by_shard = array();

		foreach (array_unique(array_map('strval', (array)$points)) as $id) {
			$by_shard[self::points_shard_key($id)][] = $id;
		}

		$all_points = array();
		$missing = array();

		// Шарды декодируются по одному: в памяти одновременно один файл индекса, а не весь справочник
		foreach ($by_shard as $shard_key => $ids) {
			$shard = $this->cacheGet($shard_key);

			foreach ($ids as $id) {
				if (is_array($shard) && isset($shard[$id])) {
					$all_points[] = $shard[$id];
				} else {
					$missing[] = $id;
				}
			}
		}

		unset($by_shard, $shard);

		if ($missing) {
			$this->toLog('shipping_apiship points index', array('cached' => count($all_points), 'missing' => count($missing)));
		}

		$loaded = array();

		foreach (array_chunk($missing, 1000) as $part_points) {
			$url = $this->apiship_params['shipping_apiship_url'] . 'lists/points?limit=10000&offset=0&filter=' . urlencode('id=['. implode(',' , $part_points) .']') . self::point_fields_query();
			$output = $this->curl_get($url);
			$data = json_decode((string)$output['body'], true);

			if (isset($output['headers']['x-tracing-id'][0])) $x_tracing_id = $output['headers']['x-tracing-id'][0]; else $x_tracing_id = '?';

			if (isset($data['errors'])) {
				$this->toLog('shipping_apiship points error ', array('url' => $url, 'x_tracing_id' => $x_tracing_id, 'output' => $output), true);
				return array();
			}

			if (!isset($data['rows'])) {
				$this->toLog('shipping_apiship points error2 ', array('url' => $url, 'x_tracing_id' => $x_tracing_id, 'output' => $output), true);
				return array();
			}

			foreach ($data['rows'] as $row) {
				$loaded[] = self::trim_point($row);
			}

			$this->toLog('shipping_apiship points ', array('url' => $url, 'x_tracing_id' => $x_tracing_id, 'rows' => count($data['rows'])));

			unset($data, $output);
		}

		if ($loaded) {
			$this->remember_points($loaded);
		}

		return array_merge($all_points, $loaded);
	}

	/**
	 * Одна точка по id: сначала из уже загруженных справочников в кеше, без запроса к API
	 */
	public function apiship_point($id) {
		$id = trim((string)$id);

		if ($id == '' || !ctype_digit($id)) {
			return array();
		}

		$cached = $this->cacheGet(self::points_shard_key($id));

		if (is_array($cached) && isset($cached[$id])) {
			return $cached[$id];
		}

		$data = $this->apiship_point_by_params(array('id=' . $id));

		$point = (isset($data[0]) && is_array($data[0])) ? $data[0] : array();

		if ($point) {
			$this->remember_points(array($point));
		}

		return $point;
	}

	/**
	 * Индекс точек по id в кеше (шарды по id): пополняется каждым ответом lists/points, чтобы точечные
	 * запросы (выбранный ПВЗ в чекауте, экспорт заказа) и повторные расчёты не ходили в API.
	 * Каждый затронутый шард читается и пишется один раз за вызов
	 */
	public function remember_points($points) {
		if (!$points || !$this->registry->has('cache')) {
			return;
		}

		$by_shard = array();

		foreach ($points as $point) {
			if (isset($point['id'])) {
				$by_shard[self::points_shard_key((string)$point['id'])][(string)$point['id']] = self::trim_point($point);
			}
		}

		foreach ($by_shard as $shard_key => $shard_points) {
			$index = $this->cacheGet($shard_key);

			if (!is_array($index)) {
				$index = array();
			}

			$index = $shard_points + $index;

			// Ограничение размера шарда: старые записи вытесняются
			if (count($index) > self::POINTS_INDEX_SHARD_LIMIT) {
				$index = array_slice($index, 0, self::POINTS_INDEX_SHARD_LIMIT, true);
			}

			$this->cacheSet($shard_key, $index, self::CACHE_LISTS_MINUTES);
		}
	}

 	public function apiship_orders_status($date) {

		$url = $this->apiship_params['shipping_apiship_url'] . 'orders/statuses/date/' . $date;

		$output = $this->curl_get($url);
		$data = json_decode((string)$output['body'], true);

		if (isset($output['headers']['x-tracing-id'][0])) $x_tracing_id = $output['headers']['x-tracing-id'][0]; else $x_tracing_id = '?';

		$this->toLog('shipping_apiship orders_status', array('url' => $url, 'x_tracing_id' => $x_tracing_id, 'output' => $data), isset($data['errors']));

		return is_array($data) ? $data : array();
	}

 	public function apiship_order_status($order_id) {

		$url = $this->apiship_params['shipping_apiship_url'] . 'orders/'. (int)$order_id . '/status';

		$output = $this->curl_get($url);
		$data = json_decode((string)$output['body'], true);

		if (isset($output['headers']['x-tracing-id'][0])) $x_tracing_id = $output['headers']['x-tracing-id'][0]; else $x_tracing_id = '?';

		$this->toLog('shipping_apiship order_status', array('url' => $url, 'x_tracing_id' => $x_tracing_id, 'output' => $data), isset($data['errors']));

		return is_array($data) ? $data : array();
	}

 	public function apiship_oc_order_status($order_id) {

		$url = $this->apiship_params['shipping_apiship_url'] . 'orders/status?clientNumber=' . urlencode($order_id);

		$output = $this->curl_get($url);
		$data = json_decode((string)$output['body'], true);

		if (isset($output['headers']['x-tracing-id'][0])) $x_tracing_id = $output['headers']['x-tracing-id'][0]; else $x_tracing_id = '?';

		$this->toLog('shipping_apiship order_status', array('url' => $url, 'x_tracing_id' => $x_tracing_id, 'output' => $data), isset($data['errors']));

		return is_array($data) ? $data : array();
	}

	/**
	 * Расчёт стоимости доставки. Ответ кешируется в кеше OpenCart по хешу всего запроса (адрес, корзина,
	 * настройки), ошибки API и транспорта не кешируются
	 */
 	public function apiship_calculator($country, $region, $city, $postcode, $ext_address, $providers, $products, $total, $cash_on_delivery) {

		// Калькулятор ApiShip ищет по городу, индекс в адресе назначения ломает подбор тарифов
		$postcode = '';

		if (trim($city) == '') {
			$output = array('message' => isset($this->apiship_params['shipping_apiship_error_select_city']) ? $this->apiship_params['shipping_apiship_error_select_city'] : '');

			return array(
				'body' => $output,
				'x-tracing-id' => ''
			);
		}

		$extraParams = array();
		if (isset($this->apiship_params['shipping_apiship_provider']) && is_array($this->apiship_params['shipping_apiship_provider']))
		foreach($this->apiship_params['shipping_apiship_provider'] as $provider => $data) {
			if (!empty($data['id']))
				$extraParams[$provider . ".pointInId"] = $data['id'];
		}

		$calculate_data = $this->calculate_places($products, $total);

		$cart_length = $calculate_data['total_length'];
		$cart_width = $calculate_data['total_width'];
		$cart_height = $calculate_data['total_height'];
		$cart_weight = $calculate_data['total_weight'];

		$items_cost = $calculate_data['total_cost'];
		$assessed_cost = $calculate_data['assessed_cost'];

		$url = $this->apiship_params['shipping_apiship_url'] . 'calculator';

	 	$places[] = array(
	      	'height' => $cart_height,
	      	'length' => $cart_length,
	      	'width' => $cart_width,
	      	'weight' => $cart_weight
		);

		$params = array(
			'from' => array(
			   	'countryCode' => $this->param('shipping_apiship_sending_country_code', 'RU'),
				'addressString' => $this->get_address(array(
					'area' => '',

					'region' => $this->param('shipping_apiship_sending_region'),
					'regionType' => '',

					'city' => $this->param('shipping_apiship_sending_city'),
					'cityType' => '',

					'street' => $this->param('shipping_apiship_sending_street'),
					'streetType' => '',

					'house' => $this->param('shipping_apiship_sending_house'),
					'block' => $this->param('shipping_apiship_sending_block'),
					'office' => $this->param('shipping_apiship_sending_office')

				))

			),
			'to' => array(
				'countryCode' => $country,
				'addressString' => $this->get_address(array(
					'postIndex' => $postcode,
					'area' => '',

					'region' => $region,
					'regionType' => '',

					'city' => $city,
					'cityType' => '',

					'street' => $ext_address,
					'streetType' => ''
				), true)

			),
			'places' => $places,
			'customCode' => $this->param('shipping_apiship_custom_code'),
		  	'assessedCost' => $assessed_cost,
			'includeFees' => $this->param('shipping_apiship_include_fees', 'false')
		);

		if ($providers!=array()) $params['providerKeys'] = $providers;
		if ($cash_on_delivery == true) $params['codCost'] = $items_cost;
		if ($extraParams!=array()) $params['extraParams'] = $extraParams;

		// Ключ кеша — весь запрос к калькулятору: одинаковые корзины с одним адресом делят один результат
		$data_hash = md5((string)json_encode($params));
		$data_key = 'apiship_calculator.' . $data_hash;

		$data = $this->cacheGet($data_key);
		if ($data !== null && isset($data['data_hash']) && $data['data_hash'] == $data_hash) {
			$this->toLog($data_key . ' cached');
			return $data;
		}

		$output = $this->curl_post($url, $params);
		if (isset($output['headers']['x-tracing-id'][0])) $x_tracing_id = $output['headers']['x-tracing-id'][0]; else $x_tracing_id = '?';

		$data = array(
			'body' => json_decode((string)$output['body'], true),
			'x-tracing-id' => $x_tracing_id,
			'data_hash' => $data_hash
		);

		$this->toLog('shipping_apiship_calculator', array('url' => $url, 'params' => $params, 'output' => $this->summarize_calculator($data)), !is_array($data['body']) || isset($data['body']['errors']));

		// Транспортная ошибка, не-JSON ответ, HTTP-ошибка (401/429/5xx) или тело без разделов расчёта:
		// не кешировать, иначе сбой держится 10 минут после восстановления API
		$http_code = isset($output['code']) ? (int)$output['code'] : 0;

		$is_calculation = is_array($data['body']) && (isset($data['body']['deliveryToPoint']) || isset($data['body']['deliveryToDoor']));

		if (!is_array($data['body']) || $http_code >= 400 || (!$is_calculation && !isset($data['body']['errors']))) {
			$message = (is_array($data['body']) && !empty($data['body']['message'])) ? (string)$data['body']['message'] : $this->param('shipping_apiship_error_timeout');

			return array(
				'body' => array('message' => $message),
				'x-tracing-id' => $x_tracing_id
			);
		}

		if (isset($data['body']['errors'])) {
			$output = array('message' => sprintf($this->param('shipping_apiship_no_shipping', '%s'), $city .', '.$region));

			return array(
				'body' => $output,
				'x-tracing-id' => $x_tracing_id
			);
		}

		$this->cacheSet($data_key, $data, self::CACHE_CALCULATOR_MINUTES);
		return $data;

	}

	private function param($key, $default = '') {
		return isset($this->apiship_params[$key]) ? $this->apiship_params[$key] : $default;
	}

	/**
	 * Ответ калькулятора для лога: тарифы без списков pointIds (в них тысячи id на каждый тариф)
	 */
	private function summarize_calculator($data) {
		if (!isset($data['body']) || !is_array($data['body'])) {
			return $data;
		}

		$body = $data['body'];

		foreach (array('deliveryToPoint', 'deliveryToDoor') as $section) {
			if (!isset($body[$section]) || !is_array($body[$section])) continue;

			foreach ($body[$section] as $i => $provider) {
				if (!isset($provider['tariffs']) || !is_array($provider['tariffs'])) continue;

				foreach ($provider['tariffs'] as $j => $tariff) {
					if (isset($tariff['pointIds'])) {
						$body[$section][$i]['tariffs'][$j]['pointIds'] = count($tariff['pointIds']) . ' ids';
					}
				}
			}
		}

		return array('body' => $body) + $data;
	}

 	public function apiship_order($order_params) {

		$url = $this->apiship_params['shipping_apiship_url'] . 'orders/sync';

		$params = array(
			'order' => array(
		    	'clientNumber' => $order_params['orderId'], // Номер заказа в системе клиента. Может быть переиспользован, если отменить или удалить заказ.
		    	'weight' => $order_params['orderWeight'], // Вес всего заказа в граммах
		    	'providerKey' => $order_params['orderProviderKey'], // Код службы доставки
		    	'pickupType' => $order_params['orderPickupType'], // Тип забора груза 1 - от двери клиента 2 – клиент привозит заказ на склад СД
		    	'deliveryType' => $order_params['orderDeliveryType'], // Тип доставки 1 - до двери 2 – до ПВЗ
		    	'tariffId' => $order_params['orderTariffId'], // Тариф службы доставки по которому осуществляется доставка
				'pointOutId' => $order_params['orderPointOutId'], // ID пункта выдачи заказов из метода /lists/points. Обязательно если доставка до ПВЗ
				'pickupDate' => $order_params['orderPickupDate']
		  	),
		  	'cost' => array(
		    	'assessedCost' => $order_params['assessed_cost'], // Оценочная стоимость / сумма страховки (в рублях)
		    	'codCost' => $order_params['costCodCost'], // Сумма наложенного платежа с учетом НДС (в рублях)
				'deliveryCost' => $order_params['costDeliveryCost']
		  	),
			'sender' => array(
				'email' => $this->param('shipping_apiship_contact_email'),// Контактный email адрес
			    'phone' => $this->param('shipping_apiship_contact_phone'), // Контактный телефон
				'companyName' => $this->param('shipping_apiship_contact_organization'),// Название компании
				'companyInn' => $this->param('shipping_apiship_contact_inn'),// ИНН компании
			    'contactName' => $this->param('shipping_apiship_contact_name'), // ФИО контактного лица
			    'countryCode' => $this->param('shipping_apiship_sending_country_code', 'RU'), // Код страны в соответствии с ISO 3166-1 alpha-2
			    'region' => $this->param('shipping_apiship_sending_region'), // Область или республика или край
			    'city' => $this->param('shipping_apiship_sending_city'), // Город или населенный пункт
				'street' => $this->param('shipping_apiship_sending_street'), // Улица
				'house' => $this->param('shipping_apiship_sending_house'), // Дом
				'block' => $this->param('shipping_apiship_sending_block'), // Строение/Корпус
				'office' => $this->param('shipping_apiship_sending_office') // Офис/Квартира
			),
			'recipient' => array(
				'email' => $order_params['recipientEmail'],
			  	'phone' => $order_params['recipientPhone'], // Контактный телефон
			    'contactName' => $order_params['recipientContactName'], // ФИО контактного лица
				'countryCode' => $order_params['recipientCountryCode'], // Код страны в соответствии с ISO 3166-1 alpha-2
				'addressString' => $order_params['recipientAddressString'], // Адрес одной строкой
				'comment' => $order_params['recipientComment'] // Комментарий
			),
			'returnAddress' => array(
				'email' => $this->param('shipping_apiship_contact_email'),// Контактный email адрес
			    'phone' => $this->param('shipping_apiship_contact_phone'), // Контактный телефон
				'companyName' => $this->param('shipping_apiship_contact_organization'),// Название компании
			    'contactName' => $this->param('shipping_apiship_contact_name'), // ФИО контактного лица
			    'countryCode' => $this->param('shipping_apiship_sending_country_code', 'RU'), // Код страны в соответствии с ISO 3166-1 alpha-2
			    'region' => $this->param('shipping_apiship_sending_region'), // Область или республика или край
			    'city' => $this->param('shipping_apiship_sending_city'), // Город или населенный пункт
				'street' => $this->param('shipping_apiship_sending_street'), // Улица
				'house' => $this->param('shipping_apiship_sending_house'), // Дом
				'block' => $this->param('shipping_apiship_sending_block'), // Строение/Корпус
				'office' => $this->param('shipping_apiship_sending_office') // Офис/Квартира
			),
			'places' => array(
			    	array(
			      	'height' => $order_params['placeHeight'],
			      	'length' => $order_params['placeLength'],
			      	'width' => $order_params['placeWidth'],
			      	'weight' => $order_params['placeWeight'],
			    	)
			)
		);

		$pointInId = $this->get_pickup_id($order_params['orderProviderKey']);
		if ($pointInId != '') $params['order']['pointInId'] = $pointInId;

		if ($order_params['sub_total_cost'] != 0) {
			$koef = $order_params['costAssessedCost']/$order_params['sub_total_cost'];
		} else {
			$koef = 0;
		}

		$total_cost = 0;

		$total_weight = 0;
		$total_count = 0;

		foreach($order_params['items'] as $item) {

			$cost = $this->format_cost(($order_params['costCodCost']==0)?0:$item['cost']*$koef);
			$total_cost = $total_cost + $cost*$item['quantity'];

			$weight = $this->format_weight($item['weight']);
			$width = $this->format_dimension($item['width']);
			$length = $this->format_dimension($item['length']);
			$height = $this->format_dimension($item['height']);

			$total_weight = $total_weight + $weight*$item['quantity'];
			$total_count = $total_count + $item['quantity'];

			$params['places'][0]['items'][] = array(
				'articul' => $item['articul'],
				'description' => $item['description'],
				'quantity' => $item['quantity'],
				'weight' => $weight,
				'width' => $width,
				'length' => $length,
				'height' => $height,
				'cost' => $cost,
				'assessedCost' => $item['assessed_cost']
			);

		}

		// Разница между суммой позиций и наложенным платежом (без bcmath: округление до копеек)
		$cost_dif = round($total_cost - $order_params['costCodCost'] + $order_params['costDeliveryCost'], 2);

		$this->toLog('shipping_apiship_order', array(
			'total_cost' => $total_cost,
			'order_params_costCodCost' => $order_params['costCodCost'],
			'order_params_costDeliveryCost' => $order_params['costDeliveryCost'],
			'cost_dif' => $cost_dif,
			'koef' => $koef,

			'total_weight' => $total_weight,
			'placeWeight' => $order_params['placeWeight'],
			'placeCalculateWeight' => $order_params['placeCalculateWeight'],
			'total_count' => $total_count,
			'params' => $params
		));

		if (!empty($params['places'][0]['items']) && (($cost_dif != 0)||($order_params['placeWeight'] != $total_weight))) {
			if ($params['places'][0]['items'][0]['quantity'] == 1) {
				$params['places'][0]['items'][0]['cost'] = $this->format_cost($params['places'][0]['items'][0]['cost'] - $cost_dif);
				$params['places'][0]['items'][0]['assessedCost'] = $this->format_cost($params['places'][0]['items'][0]['assessedCost'] - $cost_dif);
			} else {

				$params['places'][0]['items'][0]['quantity']--;
				$params['places'][0]['items'][] = array(
					'articul' => $params['places'][0]['items'][0]['articul'],
					'description' => $params['places'][0]['items'][0]['description'],
					'quantity' => 1,
					'weight' => $params['places'][0]['items'][0]['weight'],
					'width' => $params['places'][0]['items'][0]['width'],
					'length' => $params['places'][0]['items'][0]['length'],
					'height' => $params['places'][0]['items'][0]['height'],
					'cost' => $this->format_cost($params['places'][0]['items'][0]['cost'] - $cost_dif),
					'assessedCost' => $this->format_cost($params['places'][0]['items'][0]['assessedCost'] - $cost_dif)
				);


			}
		}


		if ($total_count > 0 && $order_params['placeWeight'] != $total_weight) {
			$params['places'][0]['items'] = $this->distribute_place_weight($params['places'][0]['items'], (float)$order_params['placeWeight']);
		}

		$output = $this->curl_post($url, $params);

		if (isset($output['headers']['x-tracing-id'][0])) $x_tracing_id = $output['headers']['x-tracing-id'][0]; else $x_tracing_id = '?';

		$data = array(
			'body' => json_decode((string)$output['body'], true),
			'x-tracing-id' => $x_tracing_id
		);

		$this->toLog('shipping_apiship_order', array('url' => $url, 'params' => $params, 'output' => $data), isset($data['body']['errors']));

		if (isset($data['body']['errors'][0]['field'])) {
			if ($data['body']['errors'][0]['field'] == 'clientNumber') {
				$order_data = $this->apiship_oc_order_status($order_params['orderId']);
				if (isset($order_data['orderInfo']['orderId'])) return array('body' => $order_data['orderInfo']);
			}
		}

		return $data;


	}

	/**
	 * Распределение веса места по позициям: вес позиции в ApiShip — за единицу, поэтому остаток от деления
	 * на количество единиц отдаётся одной единице (последняя позиция при необходимости разбивается)
	 */
	public function distribute_place_weight($items, $place_weight) {
		$total_count = 0;

		foreach ($items as $item) {
			$total_count += (int)$item['quantity'];
		}

		if ($total_count < 1) {
			return $items;
		}

		$one_item_weight = $this->format_weight($place_weight / $total_count);

		$remainder = $this->format_weight($place_weight - $one_item_weight * $total_count);

		foreach ($items as $key => $item) {
			$items[$key]['weight'] = $one_item_weight;
		}

		if ($remainder > 0) {
			end($items);
			$last = key($items);

			if ((int)$items[$last]['quantity'] > 1) {
				$items[$last]['quantity'] = (int)$items[$last]['quantity'] - 1;

				$single = $items[$last];
				$single['quantity'] = 1;

				$items[] = $single;

				end($items);
				$last = key($items);
			}

			$items[$last]['weight'] += $remainder;
		}

		return $items;
	}

 	public function apiship_cancel_order($apiship_order_id) {

		$url = $this->apiship_params['shipping_apiship_url'] . 'orders/' . (int)$apiship_order_id . '/cancel';

		$output = $this->curl_get($url);

		if (isset($output['headers']['x-tracing-id'][0])) $x_tracing_id = $output['headers']['x-tracing-id'][0]; else $x_tracing_id = '?';

		$data = array(
			'body' => json_decode((string)$output['body'], true),
			'x-tracing-id' => $x_tracing_id
		);

		$this->toLog('shipping_apiship_cancel_order', array('url' => $url, 'output' => $data), isset($data['body']['errors']));

		if (isset($data['body']['code'])) {
			if ($data['body']['code'] == '040081') {
				$order_data = $this->apiship_order_status($apiship_order_id);
				if (isset($order_data['orderInfo'])) {
					return array('body' => $order_data['orderInfo']);
				}
			}
		}
		return $data;
	}

 	public function apiship_order_info($apiship_order_id) {


		$url = $this->apiship_params['shipping_apiship_url'] . 'orders/' . (int)$apiship_order_id;

		$output = $this->curl_get($url);

		if (isset($output['headers']['x-tracing-id'][0])) $x_tracing_id = $output['headers']['x-tracing-id'][0]; else $x_tracing_id = '?';

		$data = array(
			'body' => json_decode((string)$output['body'], true),
			'x-tracing-id' => $x_tracing_id
		);


		$this->toLog('shipping_apiship_order_info', array('url' => $url, 'output' => $data), isset($data['body']['errors']));
		return $data;
	}


	public function get_address($params, $show_post_index = false) {
		if (!isset($params['regionType'])) return '';

		$region = isset($params['region']) ? (string)$params['region'] : '';
		$city = isset($params['city']) ? (string)$params['city'] : '';
		$cityType = isset($params['cityType']) ? (string)$params['cityType'] : '';
		$area = isset($params['area']) ? (string)$params['area'] : '';

		if ($params['regionType'] == 'г') {
			$address = $region;
			if ($city != $region) $address = $address. ', ' . $cityType . ' ' . $city;
			if ($area != '') $address = $address. ', ' . $area . ' р-н';
		}
		else {
			$address = $region . ' '. $params['regionType'];
			if ($area != '') $address = $address. ', ' . $area . ' р-н';
			$address = $address. ', ' . $cityType . ' ' . $city;
		}

		if ($show_post_index == true) {
			if (!empty($params['postIndex'])) $address = $params['postIndex'] . ', ' . $address;
		}

		if (!empty($params['street'])) $full_street = (isset($params['streetType']) ? $params['streetType'] : '') . ' ' . $params['street']; else $full_street = '';
		if (!empty($params['community'])) $full_community = (isset($params['communityType']) ? $params['communityType'] : '') . ' ' . $params['community']; else $full_community = '';

		if (!empty($full_community)) {
			$address = $address. ', ' .$full_community;
		}

		if (!empty($full_street)) {
			$address = $address. ', ' .$full_street;
		}

		if (!empty($params['house'])) {
			if (mb_strpos((string)$params['house'], 'д')===false)
				$address = $address. ', д.' . $params['house'];
			else
				$address = $address. ' ' . $params['house'];

		}
		if (!empty($params['block'])) $address = $address. ' корпус ' . $params['block'];
		if (!empty($params['office'])) $address = $address. ', офис ' . $params['office'];


		return $address;
	}

 	public function get_providers() {

		$provider_keys = array();

		$connections = $this->apiship_connections();
		if(isset($connections['message'])) return array('message' => $connections['message'], 'providers' => array());

		foreach($connections as $connection) {
			if (isset($connection['providerKey'])) $provider_keys[] = $connection['providerKey'];
		}

		$providers = array();

		$all_providers = $this->apiship_providers();
		if (isset($all_providers['message'])) return array('message' => $all_providers['message'], 'providers' => array());

		foreach($all_providers as $provider) {
			if (in_array($provider['key'], $provider_keys)) $providers[] = $provider;
		}

		return array('message' => '', 'providers' => $providers);
	}

 	public function get_providers_points() {
		$points = array();
		if (isset($this->apiship_params['shipping_apiship_provider']) && is_array($this->apiship_params['shipping_apiship_provider']))
		foreach($this->apiship_params['shipping_apiship_provider'] as $provider => $data) {
			if (!empty($data['id'])) $points[] = $data['id'];
		}

		$points_data = array();
		if (!$points) return $points_data;

		$data = $this->apiship_point_by_params(array('id=['.implode(',',$points).']'));

		if (isset($data['message'])) return $points_data;

		foreach($data as $point) {
			$points_data[$point['providerKey']] = array('id' => $point['id'], 'address' => $point['code']. ', ' . $this->get_address($point));
		}
		return $points_data;
	}

 	public function get_integrator_statuses() {

		$data = $this->apiship_statuses();
		$statuses_data = array();
		if (isset($data['message'])) return $statuses_data;

		foreach($data as $status) {
			$statuses_data[] = array('key' => $status['key'], 'name' => $status['name']);
		}
		return $statuses_data;
	}

	public function parce_code($code) {
		$code_parts = explode('.', (string)$code);
		$short_code = "";
		$tariff_parts = array();
		if (isset($code_parts[1])) {
			$tariff_parts = explode('_',$code_parts[1]);
			$short_code = $code_parts[1];
		}

		if (isset($tariff_parts[0])) $delivery_type = $tariff_parts[0]; else $delivery_type = '';
		if (isset($tariff_parts[1])) $provider = $tariff_parts[1]; else $provider = '';
		if (isset($tariff_parts[2])) $tariff_id = $tariff_parts[2]; else $tariff_id = '';
		if (isset($tariff_parts[3])) $point_id = $tariff_parts[3]; else $point_id = '';
		if (isset($tariff_parts[4])) $pickup_type = $tariff_parts[4]; else $pickup_type = '';
		if ($delivery_type == 'door') {
			$pickup_type = $point_id;
			$point_id = '';
		}

		return array(
			'delivery_type' => $delivery_type,
			'provider' => $provider,
			'tariff_id' => $tariff_id,
			'point_id' => $point_id,
			'short_code' => $short_code,
			'pickup_type' => $pickup_type
		);
	}

	/**
	 * Подстановка id точки в code_template тарифа карты (map_tariffs)
	 */
	const POINT_ID_PLACEHOLDER = '{point_id}';

	/**
	 * Тарифы до ПВЗ из ответа калькулятора для карты: одна запись на запись калькулятора и тип забора,
	 * ключ {providerKey}_{tariffId}_{pickupType}. Один tariffId может прийти несколько раз (зоны: своя цена и свой
	 * набор точек) — каждая такая запись остаётся отдельным тарифом с суффиксом _2, _3… в ключе. Тип забора берётся,
	 * если он есть и в тарифе, и в настройках службы доставки. Цена, название и сроки заданы на запись калькулятора,
	 * а не на точку, поэтому карта получает их один раз, а точки ссылаются на тарифы ключами (map_points)
	 *
	 * @param array $providers                deliveryToPoint ответа калькулятора
	 * @param array $pickup_types_by_provider providerKey => включённые в настройках типы забора
	 *
	 * @return array ключ => тариф: provider_key, tariff_id, pickup_type, code_template, name, description, days_min, days_max, cost, point_ids
	 */
	public static function map_tariffs($providers, $pickup_types_by_provider) {
		$tariffs = array();

		foreach ($providers as $provider) {
			$provider_key = isset($provider['providerKey']) ? (string)$provider['providerKey'] : '';

			$allowed_types = isset($pickup_types_by_provider[$provider_key]) ? $pickup_types_by_provider[$provider_key] : array();

			$provider_tariffs = (isset($provider['tariffs']) && is_array($provider['tariffs'])) ? $provider['tariffs'] : array();

			foreach ($provider_tariffs as $tariff) {
				$tariff_id = isset($tariff['tariffId']) ? (string)$tariff['tariffId'] : '';

				$tariff_types = (isset($tariff['pickupTypes']) && is_array($tariff['pickupTypes'])) ? $tariff['pickupTypes'] : array();

				$point_ids = (isset($tariff['pointIds']) && is_array($tariff['pointIds'])) ? array_map('strval', $tariff['pointIds']) : array();

				foreach (array(1, 2) as $pickup_type) {
					if (!in_array($pickup_type, $tariff_types) || !in_array($pickup_type, $allowed_types)) {
						continue;
					}

					$key = $provider_key . '_' . $tariff_id . '_' . $pickup_type;

					for ($n = 2; isset($tariffs[$key]); $n++) {
						$key = $provider_key . '_' . $tariff_id . '_' . $pickup_type . '_' . $n;
					}

					$tariffs[$key] = array(
						'provider_key'  => $provider_key,
						'tariff_id'     => $tariff_id,
						'pickup_type'   => $pickup_type,
						'code_template' => 'apiship.point_' . $provider_key . '_' . $tariff_id . '_' . self::POINT_ID_PLACEHOLDER . '_' . $pickup_type,
						'name'          => isset($tariff['tariffName']) ? (string)$tariff['tariffName'] : '',
						'description'   => isset($tariff['tariffDescription']) ? (string)$tariff['tariffDescription'] : '',
						'days_min'      => isset($tariff['daysMin']) ? $tariff['daysMin'] : '',
						'days_max'      => isset($tariff['daysMax']) ? $tariff['daysMax'] : '',
						'cost'          => isset($tariff['deliveryCost']) ? (float)$tariff['deliveryCost'] : 0.0,
						'point_ids'     => $point_ids
					);
				}
			}
		}

		return $tariffs;
	}

	/**
	 * Точки для карты: каждая точка один раз со списком ключей тарифов (map_tariffs), которые её обслуживают.
	 * Точки, которых нет в $points (не вернул lists/points), пропускаются, тарифы без единой точки — тоже.
	 * Если точка входит в несколько записей калькулятора с одним кодом варианта (один tariffId, разные цены),
	 * у неё остаётся последняя — её же берёт set_point при выборе этого кода
	 *
	 * @param array $tariffs результат map_tariffs
	 * @param array $points  строки lists/points
	 *
	 * @return array tariffs — только использованные, без point_ids; points — id => array('point' => строка, 'tariffs' => ключи)
	 */
	public static function map_points($tariffs, $points) {
		$by_id = array();

		foreach ($points as $point) {
			if (isset($point['id'])) {
				$by_id[(string)$point['id']] = $point;
			}
		}

		$map_points = array();
		$codes = array();

		foreach ($tariffs as $key => $tariff) {
			$code = isset($tariff['code_template']) ? (string)$tariff['code_template'] : (string)$key;

			foreach ($tariff['point_ids'] as $point_id) {
				if (!isset($by_id[$point_id])) {
					continue;
				}

				if (!isset($map_points[$point_id])) {
					$map_points[$point_id] = array('point' => $by_id[$point_id], 'tariffs' => array());
				}

				if (isset($codes[$point_id][$code])) {
					unset($map_points[$point_id]['tariffs'][$codes[$point_id][$code]]);
				}

				$codes[$point_id][$code] = $key;

				$map_points[$point_id]['tariffs'][$key] = true;
			}
		}

		$used = array();

		foreach ($map_points as $point_id => $item) {
			$map_points[$point_id]['tariffs'] = array_keys($item['tariffs']);

			$used += $item['tariffs'];
		}

		$map_tariffs = array();

		foreach ($tariffs as $key => $tariff) {
			if (isset($used[$key])) {
				unset($tariff['point_ids']);

				$map_tariffs[$key] = $tariff;
			}
		}

		return array('tariffs' => $map_tariffs, 'points' => $map_points);
	}

	/**
	 * Код варианта доставки до ПВЗ: code_template тарифа карты с подставленным id точки
	 * (apiship.point_{provider}_{tariff}_{point}_{pickup}, разбирается parce_code)
	 *
	 * @param array  $tariff элемент map_tariffs
	 * @param string $point_id
	 *
	 * @return string
	 */
	public static function point_code($tariff, $point_id) {
		return str_replace(self::POINT_ID_PLACEHOLDER, (string)$point_id, isset($tariff['code_template']) ? (string)$tariff['code_template'] : '');
	}

	public function calculate_places($products, $total_sum) {

		$this->toLog('calculate_places', array('products' => $products, 'total_sum' => $total_sum));

		$total_weight = 0;
		$total_length = 0;
		$total_width = 0;
		$total_height = 0;
		$total_cost = 0;

		$items = array();

		$this->load->model('catalog/product');

		$total_quantity = 0;
		foreach ($products as $product) {

			$product_info = $this->model_catalog_product->getProduct($product['product_id']);

			$quantity = intval($product['quantity']);
			if ($quantity < 1) $quantity = 1;

			$length = (isset($product['length']) && (float)$product['length'] != 0) ? ($this->length->convert($product['length'], $product['length_class_id'], $this->apiship_params['shipping_apiship_cm_select'])) : $this->format_dimension($this->param('shipping_apiship_parcel_length', 0));
			$width = (isset($product['width']) && (float)$product['width'] != 0) ? ($this->length->convert($product['width'], $product['length_class_id'], $this->apiship_params['shipping_apiship_cm_select'])) : $this->format_dimension($this->param('shipping_apiship_parcel_width', 0));
			$height = (isset($product['height']) && (float)$product['height'] != 0) ? ($this->length->convert($product['height'], $product['length_class_id'], $this->apiship_params['shipping_apiship_cm_select'])) : $this->format_dimension($this->param('shipping_apiship_parcel_height', 0));
			$weight = (isset($product['weight']) && (float)$product['weight'] != 0) ? ($this->weight->convert($product['weight'] / $quantity, $product['weight_class_id'], $this->apiship_params['shipping_apiship_gr_select'])) : $this->format_dimension($this->param('shipping_apiship_parcel_weight', 0));

			$cost = $this->format_cost($this->currency->convert($product['price'], $this->apiship_params['shipping_apiship_rub_select'], $this->config->get('config_currency')));

			$shipping_apiship_articul_mode = $this->param('shipping_apiship_articul_mode');
			$articul = $product['model'];
			switch($shipping_apiship_articul_mode)
			{
				case '':
				case '0':
				case 'sku':
					if (!empty($product_info['sku'])) $articul = $product_info['sku'];
				break;

				case 'upc':
					if (!empty($product_info['upc'])) $articul = $product_info['upc'];
				break;

				case 'ean':
					if (!empty($product_info['ean'])) $articul = $product_info['ean'];
				break;

				case 'jan':
					if (!empty($product_info['jan'])) $articul = $product_info['jan'];
				break;

				case 'isbn':
					if (!empty($product_info['isbn'])) $articul = $product_info['isbn'];
				break;

				case 'mpn':
					if (!empty($product_info['mpn'])) $articul = $product_info['mpn'];
				break;

			}
			$articul = mb_strimwidth((string)$articul,0,50);

			$items[] = array(
				'articul' => $articul,
				'description' => $product['name'],
				'quantity' => $quantity,
				'weight' => $weight,
				'height' => $height,
				'length' => $length,
				'width' => $width,
				'cost' => $cost
			);

			$total_quantity = $total_quantity + $quantity;

			for($i=0;$i<$quantity;$i++) {
				$total_weight = $total_weight + $weight;
				$total_cost = $total_cost + $cost;

				$item_ar = array($length, $width, $height);
				rsort($item_ar);

				if ($item_ar[0] > $total_length) $total_length = $item_ar[0];
				if ($item_ar[1] > $total_width) $total_width = $item_ar[1];

				$total_height = $total_height + $item_ar[2];
			}
		}

		// Перераспределение скидки: стоимость позиций приводится к итоговой сумме заказа
		if ($total_cost != 0) {
		   $delta_koef = $total_sum / $total_cost;
		} else {
		   $delta_koef = 0;
		}

		$total_cost = $total_sum;
		foreach($items as &$item) {
			$item['cost'] = $this->format_cost($delta_koef*$item['cost']);
			$total_cost = $this->format_cost($total_cost - $item['cost']*$item['quantity']);
		}
		unset($item);

		rsort($items);
		if ($items && $total_cost != 0) {
			if ($items[count($items)-1]['quantity'] > 1) {
				$items[] = end($items);
				$items[count($items)-2]['quantity'] = $items[count($items)-2]['quantity'] - 1;
				$items[count($items)-1]['quantity'] = 1;
				$items[count($items)-1]['cost'] = $this->format_cost($items[count($items)-1]['cost'] + $total_cost);
			} else {
				$items[count($items)-1]['cost'] = $this->format_cost($items[count($items)-1]['cost'] + $total_cost);
			}
		}

		$assessed_sum = 0;

		foreach($items as &$item) {
			if (!empty($this->apiship_params['shipping_apiship_use_fix_product_assessed_cost'])) {
				$item['assessed_cost'] = $this->format_cost($this->currency->convert($this->param('shipping_apiship_fix_product_assessed_cost', 0), $this->apiship_params['shipping_apiship_rub_select'], $this->config->get('config_currency')));
			} else {
				$item['assessed_cost'] = $item['cost'];
			}

			$assessed_sum = $assessed_sum + $this->format_cost($item['assessed_cost']*$item['quantity']);

		}
		unset($item);

		$place_params_length = $this->param('shipping_apiship_place_length');
		$place_params_width = $this->param('shipping_apiship_place_width');
		$place_params_height = $this->param('shipping_apiship_place_height');
		$place_params_weight = $this->param('shipping_apiship_place_weight');
		$package_params_weight = $this->param('shipping_apiship_package_weight');

		if (!empty($place_params_length)) $total_length = $place_params_length;
		if (!empty($place_params_width)) $total_width = $place_params_width;
		if (!empty($place_params_height)) $total_height = $place_params_height;
		if (!empty($place_params_weight)) $total_weight = $place_params_weight;
		if (!empty($package_params_weight)) $total_weight = $total_weight + $package_params_weight;

		$total_length = intval($total_length);
		$total_width = intval($total_width);
		$total_height = intval($total_height);
		$total_weight = intval($total_weight);

		if ($total_length < 1) $total_length = 1;
		if ($total_width < 1) $total_width = 1;
		if ($total_height < 1) $total_height = 1;
		if ($total_weight < 1) $total_weight = 1;

		return array(
			'items' => $items,
			'total_length' => $total_length,
			'total_width' => $total_width,
			'total_height' => $total_height,
			'total_weight' => $total_weight,
			'total_cost' => $total_sum,
			'assessed_cost' => $assessed_sum

		);
	}

	private function get_pickup_id($provider) {
		if (isset($this->apiship_params['shipping_apiship_provider'][$provider]['id'])) return $this->apiship_params['shipping_apiship_provider'][$provider]['id'];
		return '';
	}

	public function format_cost($cost) {
		return round((float)$cost, 2);
	}

	public function format_weight($weight) {
		return floor((float)$weight);
	}

	public function format_dimension($dimension) {
		return round((float)$dimension);
	}

}
