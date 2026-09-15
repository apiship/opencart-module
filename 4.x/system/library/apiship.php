<?php
namespace Opencart\System\Library\Extension\Apiship;
/**
 * Class Apiship
 *
 * HTTP-клиент API ApiShip и общие расчётные функции модуля.
 * Используется моделями админки и витрины: new \Opencart\System\Library\Extension\Apiship\Apiship($registry, $params, $log)
 *
 * @package Opencart\System\Library\Extension\Apiship
 */
class Apiship {
	private const API_URL = 'https://api.apiship.ru/v1/';
	private const API_URL_DEV = 'http://api.dev.apiship.ru/v1/';
	private const PLATFORM = 'opencart_v4';

	/**
	 * @var \Opencart\System\Engine\Registry
	 */
	private \Opencart\System\Engine\Registry $registry;
	/**
	 * @var array<string, mixed>
	 */
	private array $apiship_params;
	/**
	 * @var object
	 */
	private object $log;
	/**
	 * Один curl-хэндл на запрос страницы: keep-alive к API вместо нового TLS-соединения на каждый вызов
	 *
	 * @var \CurlHandle|null
	 */
	private ?\CurlHandle $curl = null;

	/**
	 * @param \Opencart\System\Engine\Registry $registry
	 * @param array<string, mixed>             $apiship_params
	 * @param object                           $log
	 */
	public function __construct(\Opencart\System\Engine\Registry $registry, array $apiship_params, object $log) {
		$this->registry = $registry;
		$this->apiship_params = $apiship_params;
		$this->log = $log;

		$this->apiship_params['shipping_apiship_url'] = self::API_URL;

		// Тестовый контур: define('APISHIP_API_URL', 'http://host/v1/') в config.php, либо define('APISHIP_TEST_MOD', true) как в 3.x
		if (defined('APISHIP_API_URL') && APISHIP_API_URL) {
			$this->apiship_params['shipping_apiship_url'] = rtrim((string)APISHIP_API_URL, '/') . '/';
		} elseif (defined('APISHIP_TEST_MOD')) {
			$this->apiship_params['shipping_apiship_url'] = self::API_URL_DEV;
		}
	}

	/**
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function __get(string $name) {
		return $this->registry->get($name);
	}

	/**
	 * @param string $prefix
	 * @param mixed  $data
	 * @param bool   $error
	 *
	 * @return void
	 */
	public function toLog(string $prefix, $data = '', bool $error = false): void {
		if ($this->isDebug() || $error) {
			$this->log->write($prefix . PHP_EOL . print_r($data, true));
		}
	}

	/**
	 * @return bool
	 */
	private function isDebug(): bool {
		return ($this->apiship_params['shipping_apiship_mode'] ?? '') == 'shipping_apiship_mode_debug';
	}

	/**
	 * @return array<int, string>
	 */
	private function getHeaders(): array {
		return [
			'Platform: ' . self::PLATFORM,
			'Content-Type: application/json',
			'Authorization: ' . (string)($this->apiship_params['shipping_apiship_token'] ?? ''),
			'Accept: application/json'
		];
	}

	/**
	 * @param string $url
	 *
	 * @return array<string, mixed>
	 */
	private function curl_get(string $url): array {
		return $this->curl_request($url, null);
	}

	/**
	 * @param string               $url
	 * @param array<string, mixed> $data
	 *
	 * @return array<string, mixed>
	 */
	private function curl_post(string $url, array $data): array {
		return $this->curl_request($url, json_encode($data));
	}

	/**
	 * HTTP-запрос к API: общий хэндл (keep-alive), сжатие ответа, таймауты соединения и ответа
	 *
	 * @param string      $url
	 * @param string|null $body null — GET, иначе POST с JSON-телом
	 *
	 * @return array<string, mixed> body, headers, code
	 */
	protected function curl_request(string $url, ?string $body): array {
		$headers = [];

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
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$headers) {
			$len = strlen($header);

			$header = explode(':', $header, 2);

			if (count($header) < 2) {
				return $len;
			}

			$headers[strtolower(trim($header[0]))][] = trim($header[1]);

			return $len;
		});

		if ($body !== null) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		}

		$result = curl_exec($ch);

		if ($result === false) {
			$this->log->write('curl error ' . $url . ' ' . print_r(curl_error($ch), true));
		}

		$code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

		return ['body' => $result, 'headers' => $headers, 'code' => $code];
	}

	public function __destruct() {
		if ($this->curl !== null) {
			curl_close($this->curl);
		}
	}

	/**
	 * Постраничная выгрузка списков API с кешированием в кеше OpenCart (не в сессии): ключ — команда и фильтр,
	 * TTL — CACHE_LISTS_MINUTES
	 *
	 * @param string             $cmd
	 * @param int                $limit
	 * @param array<int, string> $filter_list
	 *
	 * @return array<mixed>
	 */
	private function apiship_data(string $cmd, int $limit, array $filter_list = []): array {
		$data_key = 'apiship_' . $cmd . md5($limit . print_r($filter_list, true));
		$data_hash = md5($cmd . $limit . print_r($filter_list, true));

		$data = $this->cacheGet($data_key);

		if ($data !== null && isset($data['data_hash']) && $data['data_hash'] == $data_hash) {
			$this->toLog($data_key . ' cached');

			return $data['rows'];
		}

		$offset = 0;
		$rows = [];
		$x_tracing_ids = [];
		$url = '';
		$is_points = ($cmd == 'lists/points');

		do {
			$url = $this->apiship_params['shipping_apiship_url'] . $cmd . '?limit=' . $limit . '&offset=' . $offset;

			if ($filter_list) {
				$url .= '&filter=' . urlencode(implode(';', $filter_list));
			}

			if ($is_points) {
				$url .= self::point_fields_query();
			}

			$output = $this->curl_get($url);

			$data = json_decode((string)$output['body'], true);

			$x_tracing_ids[] = $output['headers']['x-tracing-id'][0] ?? '?';

			if (isset($data['errors'])) {
				$this->toLog('shipping_apiship_data ' . $cmd . ' error1', ['url' => $url, 'output' => $output], true);

				return [];
			}

			if (!isset($data['rows'])) {
				$this->toLog('shipping_apiship_data ' . $cmd . ' error2', ['url' => $url, 'output' => $output], true);

				if (isset($data['message'])) {
					return ['message' => $data['message']];
				}

				return [];
			}

			$rows = array_merge($rows, $is_points ? array_map([self::class, 'trim_point'], $data['rows']) : $data['rows']);

			$rows_count = count($data['rows']);
			$offset += $rows_count;

			// Страница короче лимита — это последняя; отдельный запрос за пустой страницей не нужен
		} while ($rows_count >= $limit);

		// Справочники в лог не пишем целиком: список точек — сотни килобайт на каждый расчёт
		$this->toLog('shipping_apiship_data ' . $cmd, ['url' => $url, 'x_tracing_id' => $x_tracing_ids, 'rows' => count($rows)]);

		$this->cacheSet($data_key, ['rows' => $rows, 'data_hash' => $data_hash], self::CACHE_LISTS_MINUTES);

		return $rows;
	}

	/**
	 * Небольшие данные текущего чекаута в сессии OpenCart: адрес расчёта, выбранные ПВЗ, tracing id.
	 * Справочники API сюда класть нельзя: сессия OC4 хранится одной JSON-строкой в колонке text (64 КБ),
	 * переполнение молча обрезает её, и ядро теряет адрес доставки и корзину — для них есть cacheSet/cacheGet.
	 *
	 * @param string $key
	 * @param mixed  $value
	 * @param int    $expired_timeout_minuts
	 *
	 * @return void
	 */
	public function setData(string $key, $value, int $expired_timeout_minuts = 10): void {
		if (!isset($value)) {
			return;
		}

		if ($expired_timeout_minuts == 0) {
			$this->session->data['shipping_apiship'][$key] = ['value' => $value];
		} else {
			$this->session->data['shipping_apiship'][$key] = ['value' => $value, 'time' => time() + 60 * $expired_timeout_minuts];
		}
	}

	/**
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function getData(string $key) {
		if (!isset($this->session->data['shipping_apiship'][$key])) {
			return null;
		}

		$item = $this->session->data['shipping_apiship'][$key];

		if (isset($item['time']) && time() > $item['time']) {
			return null;
		}

		return $item['value'] ?? null;
	}

	/**
	 * Ключ файлового кеша OpenCart. Справочники (точки, службы, статусы) и расчёты зависят только от токена
	 * и параметров запроса, поэтому кеш общий для всех покупателей магазина: ключ без идентификатора сессии.
	 * Параметры запроса вызывающий код кладёт в $key (хеш запроса), иначе покупатели вытесняли бы друг друга из одного слота
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	protected function cacheKey(string $key): string {
		$token = (string)($this->apiship_params['shipping_apiship_token'] ?? '');

		return 'apiship.' . md5($token . '|' . $key);
	}

	/**
	 * TTL кеша: справочники (точки, службы, статусы, подключения) меняются редко — 6 часов;
	 * расчёт стоимости зависит от корзины и адреса — 10 минут
	 */
	public const CACHE_LISTS_MINUTES = 360;
	public const CACHE_CALCULATOR_MINUTES = 10;

	/**
	 * Поля точки lists/points, которые использует модуль (карта, адрес ПВЗ, выбор в чекауте, экспорт, поиск в админке).
	 * Только они запрашиваются у API (параметр fields) и хранятся в кеше: полная строка точки в PHP — около 5 КБ,
	 * для Москвы это десятки тысяч точек и выход за memory_limit
	 */
	public const POINT_FIELDS = [
		'id', 'code', 'providerKey', 'name', 'type', 'lat', 'lng',
		'regionType', 'region', 'area', 'cityType', 'city', 'communityType', 'community',
		'streetType', 'street', 'house', 'block', 'office', 'postIndex',
		'phone', 'timetable', 'description', 'paymentCash', 'paymentCard'
	];

	/**
	 * Индекс точек в кеше разбит на шарды по id: точечный запрос (выбранный ПВЗ, экспорт) декодирует один
	 * файл, а не весь справочник. 64 шарда по 1500 обрезанных точек (около 500 байт в JSON каждая) —
	 * до 96 000 точек на магазин, файл шарда до 750 КБ. Потолок — на несколько крупных городов сразу:
	 * при переполнении шарда старые записи вытесняются и запрашиваются у API заново, данные при этом верны
	 */
	public const POINTS_INDEX_SHARDS = 64;
	public const POINTS_INDEX_SHARD_LIMIT = 1500;

	/**
	 * Ключ шарда индекса для id точки
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public static function points_shard_key(string $id): string {
		return 'apiship_points_index.' . ((int)$id % self::POINTS_INDEX_SHARDS);
	}

	/**
	 * Оставляет в строке точки только поля из POINT_FIELDS
	 *
	 * @param array<string, mixed> $point
	 *
	 * @return array<string, mixed>
	 */
	public static function trim_point(array $point): array {
		return array_intersect_key($point, array_flip(self::POINT_FIELDS));
	}

	/**
	 * @return string параметр fields для lists/points
	 */
	private static function point_fields_query(): string {
		return '&fields=' . urlencode(implode(',', self::POINT_FIELDS));
	}

	/**
	 * Кеш ответов API (калькулятор, точки, списки) в кеше OpenCart, не в сессии
	 *
	 * @param string $key
	 * @param mixed  $value
	 * @param int    $expired_timeout_minuts
	 *
	 * @return void
	 */
	public function cacheSet(string $key, $value, int $expired_timeout_minuts = self::CACHE_CALCULATOR_MINUTES): void {
		if (!isset($value) || !$this->registry->has('cache')) {
			return;
		}

		$this->cache->set($this->cacheKey($key), ['value' => $value], 60 * max(1, $expired_timeout_minuts));
	}

	/**
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function cacheGet(string $key) {
		if (!$this->registry->has('cache')) {
			return null;
		}

		$item = $this->cache->get($this->cacheKey($key));

		return is_array($item) && array_key_exists('value', $item) ? $item['value'] : null;
	}

	/**
	 * @return array<mixed>
	 */
	public function apiship_providers(): array {
		return $this->apiship_data('lists/providers', 10000);
	}

	/**
	 * @return array<mixed>
	 */
	public function apiship_statuses(): array {
		return $this->apiship_data('lists/statuses', 10000);
	}

	/**
	 * @return array<mixed>
	 */
	public function apiship_connections(): array {
		return $this->apiship_data('connections', 100);
	}

	/**
	 * @param array<int, string> $params_list
	 *
	 * @return array<mixed>
	 */
	public function apiship_point_by_params(array $params_list): array {
		return $this->apiship_data('lists/points', 10000, $params_list);
	}

	/**
	 * @param array<int, int> $order_id
	 *
	 * @return array<string, mixed>
	 */
	public function apiship_labels(array $order_id): array {
		$url = $this->apiship_params['shipping_apiship_url'] . 'orders/labels';

		$params = [
			'orderIds' => $order_id,
			'format'   => 'pdf'
		];

		$output = $this->curl_post($url, $params);

		$data = [
			'body'         => json_decode((string)$output['body'], true),
			'x-tracing-id' => $output['headers']['x-tracing-id'][0] ?? '?'
		];

		$this->toLog('shipping_apiship_labels', ['url' => $url, 'params' => $params, 'output' => $data], isset($data['body']['errors']));

		return $data;
	}

	/**
	 * @param array<int, int> $order_id
	 *
	 * @return array<string, mixed>
	 */
	public function apiship_waybills(array $order_id): array {
		$url = $this->apiship_params['shipping_apiship_url'] . 'orders/waybills';

		$params = [
			'orderIds' => $order_id
		];

		$output = $this->curl_post($url, $params);

		$data = [
			'body'         => json_decode((string)$output['body'], true),
			'x-tracing-id' => $output['headers']['x-tracing-id'][0] ?? '?'
		];

		$this->toLog('shipping_apiship_waybills', ['url' => $url, 'params' => $params, 'output' => $data], isset($data['body']['errors']));

		return $data;
	}

	/**
	 * Точки по списку id: сначала из индекса в кеше, у API запрашиваются только недостающие (порциями по 1000,
	 * только нужные поля). Индекс пополняется один раз после загрузки. Отдельного кеша по набору id нет:
	 * он дублировал индекс и на Москве (десятки тысяч точек) не помещался в memory_limit при json_encode
	 *
	 * @param array<int, mixed> $points id точек
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function apiship_points(array $points): array {
		$by_shard = [];

		foreach (array_unique(array_map('strval', $points)) as $id) {
			$by_shard[self::points_shard_key($id)][] = $id;
		}

		$all_points = [];
		$missing = [];

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
			$this->toLog('shipping_apiship points index', ['cached' => count($all_points), 'missing' => count($missing)]);
		}

		$loaded = [];

		foreach (array_chunk($missing, 1000) as $part_points) {
			$url = $this->apiship_params['shipping_apiship_url'] . 'lists/points?limit=10000&offset=0&filter=' . urlencode('id=[' . implode(',', $part_points) . ']') . self::point_fields_query();

			$output = $this->curl_get($url);

			$data = json_decode((string)$output['body'], true);

			$x_tracing_id = $output['headers']['x-tracing-id'][0] ?? '?';

			if (isset($data['errors'])) {
				$this->toLog('shipping_apiship points error ', ['url' => $url, 'x_tracing_id' => $x_tracing_id, 'output' => $output], true);

				return [];
			}

			if (!isset($data['rows'])) {
				$this->toLog('shipping_apiship points error2 ', ['url' => $url, 'x_tracing_id' => $x_tracing_id, 'output' => $output], true);

				return [];
			}

			foreach ($data['rows'] as $row) {
				$loaded[] = self::trim_point($row);
			}

			$this->toLog('shipping_apiship points ', ['url' => $url, 'x_tracing_id' => $x_tracing_id, 'rows' => count($data['rows'])]);

			unset($data, $output);
		}

		if ($loaded) {
			$this->remember_points($loaded);
		}

		return array_merge($all_points, $loaded);
	}

	/**
	 * Одна точка по id: сначала из уже загруженных справочников в кеше, без запроса к API
	 *
	 * @param string $id
	 *
	 * @return array<string, mixed>
	 */
	public function apiship_point(string $id): array {
		$id = trim($id);

		if ($id == '' || !ctype_digit($id)) {
			return [];
		}

		$cached = $this->cacheGet(self::points_shard_key($id));

		if (is_array($cached) && isset($cached[$id])) {
			return $cached[$id];
		}

		$data = $this->apiship_point_by_params(['id=' . $id]);

		$point = isset($data[0]) && is_array($data[0]) ? $data[0] : [];

		if ($point) {
			$this->remember_points([$point]);
		}

		return $point;
	}

	/**
	 * Индекс точек по id в кеше (шарды по id, см. points_shard_key): пополняется каждым ответом lists/points,
	 * чтобы точечные запросы (выбранный ПВЗ в чекауте, экспорт заказа) и повторные расчёты не ходили в API.
	 * Каждый затронутый шард читается и пишется один раз за вызов
	 *
	 * @param array<int, array<string, mixed>> $points
	 *
	 * @return void
	 */
	public function remember_points(array $points): void {
		if (!$points || !$this->registry->has('cache')) {
			return;
		}

		$by_shard = [];

		foreach ($points as $point) {
			if (isset($point['id'])) {
				$by_shard[self::points_shard_key((string)$point['id'])][(string)$point['id']] = self::trim_point($point);
			}
		}

		foreach ($by_shard as $shard_key => $shard_points) {
			$index = $this->cacheGet($shard_key);

			if (!is_array($index)) {
				$index = [];
			}

			$index = $shard_points + $index;

			// Ограничение размера шарда: старые записи вытесняются
			if (count($index) > self::POINTS_INDEX_SHARD_LIMIT) {
				$index = array_slice($index, 0, self::POINTS_INDEX_SHARD_LIMIT, true);
			}

			$this->cacheSet($shard_key, $index, self::CACHE_LISTS_MINUTES);
		}
	}

	/**
	 * @param string $date
	 *
	 * @return array<mixed>
	 */
	public function apiship_orders_status(string $date): array {
		$url = $this->apiship_params['shipping_apiship_url'] . 'orders/statuses/date/' . $date;

		$output = $this->curl_get($url);

		$data = json_decode((string)$output['body'], true);

		$this->toLog('shipping_apiship orders_status', ['url' => $url, 'x_tracing_id' => $output['headers']['x-tracing-id'][0] ?? '?', 'output' => $data], isset($data['errors']));

		return is_array($data) ? $data : [];
	}

	/**
	 * @param int $order_id
	 *
	 * @return array<mixed>
	 */
	public function apiship_order_status(int $order_id): array {
		$url = $this->apiship_params['shipping_apiship_url'] . 'orders/' . $order_id . '/status';

		$output = $this->curl_get($url);

		$data = json_decode((string)$output['body'], true);

		$this->toLog('shipping_apiship order_status', ['url' => $url, 'x_tracing_id' => $output['headers']['x-tracing-id'][0] ?? '?', 'output' => $data], isset($data['errors']));

		return is_array($data) ? $data : [];
	}

	/**
	 * @param string $order_id
	 *
	 * @return array<mixed>
	 */
	public function apiship_oc_order_status(string $order_id): array {
		$url = $this->apiship_params['shipping_apiship_url'] . 'orders/status?clientNumber=' . urlencode($order_id);

		$output = $this->curl_get($url);

		$data = json_decode((string)$output['body'], true);

		$this->toLog('shipping_apiship order_status', ['url' => $url, 'x_tracing_id' => $output['headers']['x-tracing-id'][0] ?? '?', 'output' => $data], isset($data['errors']));

		return is_array($data) ? $data : [];
	}

	/**
	 * Расчёт стоимости доставки
	 *
	 * @param string             $country
	 * @param string             $region
	 * @param string             $city
	 * @param string             $postcode    не используется: калькулятор ApiShip ищет по городу, индекс в адресе назначения
	 *                                        ломает подбор тарифов (поведение 3.x); в экспорт заказа индекс уходит отдельно
	 * @param string             $ext_address
	 * @param array<int, string> $providers
	 * @param array<mixed>       $products
	 * @param float              $total
	 * @param bool               $cash_on_delivery
	 *
	 * @return array<string, mixed>
	 */
	public function apiship_calculator(string $country, string $region, string $city, string $postcode, string $ext_address, array $providers, array $products, float $total, bool $cash_on_delivery): array {
		$postcode = '';

		if (trim($city) == '') {
			return [
				'body'         => ['message' => $this->apiship_params['shipping_apiship_error_select_city'] ?? ''],
				'x-tracing-id' => ''
			];
		}

		$extraParams = [];

		if (is_array($this->apiship_params['shipping_apiship_provider'] ?? null)) {
			foreach ($this->apiship_params['shipping_apiship_provider'] as $provider => $data) {
				if (!empty($data['id'])) {
					$extraParams[$provider . '.pointInId'] = $data['id'];
				}
			}
		}

		$calculate_data = $this->calculate_places($products, $total);

		$items_cost = $calculate_data['total_cost'];
		$assessed_cost = $calculate_data['assessed_cost'];

		$url = $this->apiship_params['shipping_apiship_url'] . 'calculator';

		$places[] = [
			'height' => $calculate_data['total_height'],
			'length' => $calculate_data['total_length'],
			'width'  => $calculate_data['total_width'],
			'weight' => $calculate_data['total_weight']
		];

		$params = [
			'from'         => [
				'countryCode'   => $this->apiship_params['shipping_apiship_sending_country_code'] ?? 'RU',
				'addressString' => $this->get_address([
					'area'       => '',
					'region'     => $this->apiship_params['shipping_apiship_sending_region'] ?? '',
					'regionType' => '',
					'city'       => $this->apiship_params['shipping_apiship_sending_city'] ?? '',
					'cityType'   => '',
					'street'     => $this->apiship_params['shipping_apiship_sending_street'] ?? '',
					'streetType' => '',
					'house'      => $this->apiship_params['shipping_apiship_sending_house'] ?? '',
					'block'      => $this->apiship_params['shipping_apiship_sending_block'] ?? '',
					'office'     => $this->apiship_params['shipping_apiship_sending_office'] ?? ''
				])
			],
			'to'           => [
				'countryCode'   => $country,
				'addressString' => $this->get_address([
					'postIndex'  => $postcode,
					'area'       => '',
					'region'     => $region,
					'regionType' => '',
					'city'       => $city,
					'cityType'   => '',
					'street'     => $ext_address,
					'streetType' => ''
				], true)
			],
			'places'       => $places,
			'customCode'   => $this->apiship_params['shipping_apiship_custom_code'] ?? '',
			'assessedCost' => $assessed_cost,
			'includeFees'  => $this->apiship_params['shipping_apiship_include_fees'] ?? 'false'
		];

		if ($providers) {
			$params['providerKeys'] = $providers;
		}

		if ($cash_on_delivery) {
			$params['codCost'] = $items_cost;
		}

		if ($extraParams) {
			$params['extraParams'] = $extraParams;
		}

		// Ключ кеша — весь запрос к калькулятору: адрес отправителя и настройки из админки входят в него,
		// а cart_id покупателя — нет, поэтому одинаковые корзины с одним адресом делят один результат
		$data_hash = md5((string)json_encode($params));
		$data_key = 'apiship_calculator.' . $data_hash;

		$data = $this->cacheGet($data_key);

		if ($data !== null && isset($data['data_hash']) && $data['data_hash'] == $data_hash) {
			$this->toLog($data_key . ' cached');

			return $data;
		}

		$output = $this->curl_post($url, $params);

		$x_tracing_id = $output['headers']['x-tracing-id'][0] ?? '?';

		$data = [
			'body'         => json_decode((string)$output['body'], true),
			'x-tracing-id' => $x_tracing_id,
			'data_hash'    => $data_hash
		];

		$this->toLog('shipping_apiship_calculator', ['url' => $url, 'params' => $params, 'output' => $this->summarize_calculator($data)], !is_array($data['body']) || isset($data['body']['errors']));

		// Транспортная ошибка, не-JSON ответ, HTTP-ошибка (401/429/5xx) или тело без разделов расчёта:
		// не кешировать, иначе сбой держится 10 минут после восстановления API
		$http_code = (int)($output['code'] ?? 0);

		$is_calculation = is_array($data['body']) && (isset($data['body']['deliveryToPoint']) || isset($data['body']['deliveryToDoor']));

		if (!is_array($data['body']) || $http_code >= 400 || (!$is_calculation && !isset($data['body']['errors']))) {
			$message = is_array($data['body']) && !empty($data['body']['message']) ? (string)$data['body']['message'] : ($this->apiship_params['shipping_apiship_error_timeout'] ?? '');

			return [
				'body'         => ['message' => $message],
				'x-tracing-id' => $x_tracing_id
			];
		}

		if (isset($data['body']['errors'])) {
			return [
				'body'         => ['message' => sprintf($this->apiship_params['shipping_apiship_no_shipping'] ?? '%s', $city . ', ' . $region)],
				'x-tracing-id' => $x_tracing_id
			];
		}

		$this->cacheSet($data_key, $data);

		return $data;
	}

	/**
	 * Ответ калькулятора для лога: тарифы без списков pointIds (в них тысячи id на каждый тариф)
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return array<string, mixed>
	 */
	private function summarize_calculator(array $data): array {
		if (!is_array($data['body'] ?? null)) {
			return $data;
		}

		$body = $data['body'];

		foreach (['deliveryToPoint', 'deliveryToDoor'] as $section) {
			foreach ($body[$section] ?? [] as $i => $provider) {
				foreach ($provider['tariffs'] ?? [] as $j => $tariff) {
					if (isset($tariff['pointIds'])) {
						$body[$section][$i]['tariffs'][$j]['pointIds'] = count($tariff['pointIds']) . ' ids';
					}
				}
			}
		}

		return ['body' => $body] + $data;
	}

	/**
	 * Создание (синхронизация) заказа в ApiShip
	 *
	 * @param array<string, mixed> $order_params
	 *
	 * @return array<string, mixed>
	 */
	public function apiship_order(array $order_params): array {
		$url = $this->apiship_params['shipping_apiship_url'] . 'orders/sync';

		$params = [
			'order'         => [
				'clientNumber' => $order_params['orderId'],
				'weight'       => $order_params['orderWeight'],
				'providerKey'  => $order_params['orderProviderKey'],
				'pickupType'   => $order_params['orderPickupType'],
				'deliveryType' => $order_params['orderDeliveryType'],
				'tariffId'     => $order_params['orderTariffId'],
				'pointOutId'   => $order_params['orderPointOutId'],
				'pickupDate'   => $order_params['orderPickupDate']
			],
			'cost'          => [
				'assessedCost' => $order_params['assessed_cost'],
				'codCost'      => $order_params['costCodCost'],
				'deliveryCost' => $order_params['costDeliveryCost']
			],
			'sender'        => [
				'email'       => $this->apiship_params['shipping_apiship_contact_email'] ?? '',
				'phone'       => $this->apiship_params['shipping_apiship_contact_phone'] ?? '',
				'companyName' => $this->apiship_params['shipping_apiship_contact_organization'] ?? '',
				'companyInn'  => $this->apiship_params['shipping_apiship_contact_inn'] ?? '',
				'contactName' => $this->apiship_params['shipping_apiship_contact_name'] ?? '',
				'countryCode' => $this->apiship_params['shipping_apiship_sending_country_code'] ?? 'RU',
				'region'      => $this->apiship_params['shipping_apiship_sending_region'] ?? '',
				'city'        => $this->apiship_params['shipping_apiship_sending_city'] ?? '',
				'street'      => $this->apiship_params['shipping_apiship_sending_street'] ?? '',
				'house'       => $this->apiship_params['shipping_apiship_sending_house'] ?? '',
				'block'       => $this->apiship_params['shipping_apiship_sending_block'] ?? '',
				'office'      => $this->apiship_params['shipping_apiship_sending_office'] ?? ''
			],
			'recipient'     => [
				'email'         => $order_params['recipientEmail'],
				'phone'         => $order_params['recipientPhone'],
				'contactName'   => $order_params['recipientContactName'],
				'countryCode'   => $order_params['recipientCountryCode'],
				'addressString' => $order_params['recipientAddressString'],
				'comment'       => $order_params['recipientComment']
			],
			'returnAddress' => [
				'email'       => $this->apiship_params['shipping_apiship_contact_email'] ?? '',
				'phone'       => $this->apiship_params['shipping_apiship_contact_phone'] ?? '',
				'companyName' => $this->apiship_params['shipping_apiship_contact_organization'] ?? '',
				'contactName' => $this->apiship_params['shipping_apiship_contact_name'] ?? '',
				'countryCode' => $this->apiship_params['shipping_apiship_sending_country_code'] ?? 'RU',
				'region'      => $this->apiship_params['shipping_apiship_sending_region'] ?? '',
				'city'        => $this->apiship_params['shipping_apiship_sending_city'] ?? '',
				'street'      => $this->apiship_params['shipping_apiship_sending_street'] ?? '',
				'house'       => $this->apiship_params['shipping_apiship_sending_house'] ?? '',
				'block'       => $this->apiship_params['shipping_apiship_sending_block'] ?? '',
				'office'      => $this->apiship_params['shipping_apiship_sending_office'] ?? ''
			],
			'places'        => [
				[
					'height' => $order_params['placeHeight'],
					'length' => $order_params['placeLength'],
					'width'  => $order_params['placeWidth'],
					'weight' => $order_params['placeWeight']
				]
			]
		];

		$pointInId = $this->get_pickup_id((string)$order_params['orderProviderKey']);

		if ($pointInId != '') {
			$params['order']['pointInId'] = $pointInId;
		}

		if ($order_params['sub_total_cost'] != 0) {
			$koef = $order_params['costAssessedCost'] / $order_params['sub_total_cost'];
		} else {
			$koef = 0;
		}

		$total_cost = 0;
		$total_weight = 0;
		$total_count = 0;

		foreach ($order_params['items'] as $item) {
			$cost = $this->format_cost(($order_params['costCodCost'] == 0) ? 0 : $item['cost'] * $koef);
			$total_cost += $cost * $item['quantity'];

			$weight = $this->format_weight($item['weight']);
			$width = $this->format_dimension($item['width']);
			$length = $this->format_dimension($item['length']);
			$height = $this->format_dimension($item['height']);

			$total_weight += $weight * $item['quantity'];
			$total_count += $item['quantity'];

			$params['places'][0]['items'][] = [
				'articul'      => $item['articul'],
				'description'  => $item['description'],
				'quantity'     => $item['quantity'],
				'weight'       => $weight,
				'width'        => $width,
				'length'       => $length,
				'height'       => $height,
				'cost'         => $cost,
				'assessedCost' => $item['assessed_cost']
			];
		}

		// Разница между суммой позиций и наложенным платежом (без bcmath: округление до копеек)
		$cost_dif = round($total_cost - $order_params['costCodCost'] + $order_params['costDeliveryCost'], 2);

		$this->toLog('shipping_apiship_order', [
			'total_cost'                    => $total_cost,
			'order_params_costCodCost'      => $order_params['costCodCost'],
			'order_params_costDeliveryCost' => $order_params['costDeliveryCost'],
			'cost_dif'                      => $cost_dif,
			'koef'                          => $koef,
			'total_weight'                  => $total_weight,
			'placeWeight'                   => $order_params['placeWeight'],
			'placeCalculateWeight'          => $order_params['placeCalculateWeight'],
			'total_count'                   => $total_count,
			'params'                        => $params
		]);

		if (!empty($params['places'][0]['items']) && ($cost_dif != 0 || $order_params['placeWeight'] != $total_weight)) {
			if ($params['places'][0]['items'][0]['quantity'] == 1) {
				$params['places'][0]['items'][0]['cost'] = $this->format_cost($params['places'][0]['items'][0]['cost'] - $cost_dif);
				$params['places'][0]['items'][0]['assessedCost'] = $this->format_cost($params['places'][0]['items'][0]['assessedCost'] - $cost_dif);
			} else {
				$params['places'][0]['items'][0]['quantity']--;

				$params['places'][0]['items'][] = [
					'articul'      => $params['places'][0]['items'][0]['articul'],
					'description'  => $params['places'][0]['items'][0]['description'],
					'quantity'     => 1,
					'weight'       => $params['places'][0]['items'][0]['weight'],
					'width'        => $params['places'][0]['items'][0]['width'],
					'length'       => $params['places'][0]['items'][0]['length'],
					'height'       => $params['places'][0]['items'][0]['height'],
					'cost'         => $this->format_cost($params['places'][0]['items'][0]['cost'] - $cost_dif),
					'assessedCost' => $this->format_cost($params['places'][0]['items'][0]['assessedCost'] - $cost_dif)
				];
			}
		}

		if ($total_count > 0 && $order_params['placeWeight'] != $total_weight) {
			$params['places'][0]['items'] = $this->distribute_place_weight($params['places'][0]['items'], (float)$order_params['placeWeight']);
		}

		$output = $this->curl_post($url, $params);

		$data = [
			'body'         => json_decode((string)$output['body'], true),
			'x-tracing-id' => $output['headers']['x-tracing-id'][0] ?? '?'
		];

		$this->toLog('shipping_apiship_order', ['url' => $url, 'params' => $params, 'output' => $data], isset($data['body']['errors']));

		// Заказ с таким clientNumber уже есть — возвращаем существующий
		if (isset($data['body']['errors'][0]['field']) && $data['body']['errors'][0]['field'] == 'clientNumber') {
			$order_data = $this->apiship_oc_order_status((string)$order_params['orderId']);

			if (isset($order_data['orderInfo']['orderId'])) {
				return ['body' => $order_data['orderInfo']];
			}
		}

		return $data;
	}

	/**
	 * Распределение веса места по позициям: вес позиции в ApiShip — за единицу, поэтому остаток от деления
	 * на количество единиц отдаётся одной единице (последняя позиция при необходимости разбивается)
	 *
	 * @param array<int, array<string, mixed>> $items        позиции с quantity и weight
	 * @param float                            $place_weight вес места в граммах
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function distribute_place_weight(array $items, float $place_weight): array {
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
			$last = array_key_last($items);

			if ((int)$items[$last]['quantity'] > 1) {
				$items[$last]['quantity'] = (int)$items[$last]['quantity'] - 1;

				$single = $items[$last];
				$single['quantity'] = 1;

				$items[] = $single;

				$last = array_key_last($items);
			}

			$items[$last]['weight'] += $remainder;
		}

		return $items;
	}

	/**
	 * Наложенный платёж: код способа оплаты из сессии OC4 имеет вид «расширение.опция» (cod.cod),
	 * в настройках хранится код расширения (cod) либо полный код (filterit и т.п.)
	 *
	 * @param string             $payment_code
	 * @param array<int, string> $cod_codes
	 *
	 * @return bool
	 */
	public function is_cash_on_delivery(string $payment_code, array $cod_codes): bool {
		if ($payment_code == '' || !$cod_codes) {
			return false;
		}

		if (in_array($payment_code, $cod_codes)) {
			return true;
		}

		$extension = explode('.', $payment_code, 2)[0];

		return $extension != '' && in_array($extension, $cod_codes);
	}

	/**
	 * @param int $apiship_order_id
	 *
	 * @return array<string, mixed>
	 */
	public function apiship_cancel_order(int $apiship_order_id): array {
		$url = $this->apiship_params['shipping_apiship_url'] . 'orders/' . $apiship_order_id . '/cancel';

		$output = $this->curl_get($url);

		$data = [
			'body'         => json_decode((string)$output['body'], true),
			'x-tracing-id' => $output['headers']['x-tracing-id'][0] ?? '?'
		];

		$this->toLog('shipping_apiship_cancel_order', ['url' => $url, 'output' => $data], isset($data['body']['errors']));

		// 040081 — заказ уже отменён
		if (isset($data['body']['code']) && $data['body']['code'] == '040081') {
			$order_data = $this->apiship_order_status($apiship_order_id);

			if (isset($order_data['orderInfo'])) {
				return ['body' => $order_data['orderInfo']];
			}
		}

		return $data;
	}

	/**
	 * @param int $apiship_order_id
	 *
	 * @return array<string, mixed>
	 */
	public function apiship_order_info(int $apiship_order_id): array {
		$url = $this->apiship_params['shipping_apiship_url'] . 'orders/' . $apiship_order_id;

		$output = $this->curl_get($url);

		$data = [
			'body'         => json_decode((string)$output['body'], true),
			'x-tracing-id' => $output['headers']['x-tracing-id'][0] ?? '?'
		];

		$this->toLog('shipping_apiship_order_info', ['url' => $url, 'output' => $data], isset($data['body']['errors']));

		return $data;
	}

	/**
	 * Адрес одной строкой из полей точки/адреса ApiShip
	 *
	 * @param array<string, mixed> $params
	 * @param bool                 $show_post_index
	 *
	 * @return string
	 */
	public function get_address(array $params, bool $show_post_index = false): string {
		if (!isset($params['regionType'])) {
			return '';
		}

		$region = (string)($params['region'] ?? '');
		$city = (string)($params['city'] ?? '');
		$cityType = (string)($params['cityType'] ?? '');
		$area = (string)($params['area'] ?? '');

		if ($params['regionType'] == 'г') {
			$address = $region;

			if ($city != $region) {
				$address .= ', ' . $cityType . ' ' . $city;
			}

			if ($area != '') {
				$address .= ', ' . $area . ' р-н';
			}
		} else {
			$address = $region . ' ' . $params['regionType'];

			if ($area != '') {
				$address .= ', ' . $area . ' р-н';
			}

			$address .= ', ' . $cityType . ' ' . $city;
		}

		if ($show_post_index && !empty($params['postIndex'])) {
			$address = $params['postIndex'] . ', ' . $address;
		}

		$full_street = !empty($params['street']) ? ($params['streetType'] ?? '') . ' ' . $params['street'] : '';
		$full_community = !empty($params['community']) ? ($params['communityType'] ?? '') . ' ' . $params['community'] : '';

		if ($full_community != '') {
			$address .= ', ' . $full_community;
		}

		if ($full_street != '') {
			$address .= ', ' . $full_street;
		}

		if (!empty($params['house'])) {
			if (mb_strpos((string)$params['house'], 'д') === false) {
				$address .= ', д.' . $params['house'];
			} else {
				$address .= ' ' . $params['house'];
			}
		}

		if (!empty($params['block'])) {
			$address .= ' корпус ' . $params['block'];
		}

		if (!empty($params['office'])) {
			$address .= ', офис ' . $params['office'];
		}

		return $address;
	}

	/**
	 * Службы доставки, по которым есть подключения
	 *
	 * @return array<string, mixed>
	 */
	public function get_providers(): array {
		$provider_keys = [];

		$connections = $this->apiship_connections();

		if (isset($connections['message'])) {
			return ['message' => $connections['message'], 'providers' => []];
		}

		foreach ($connections as $connection) {
			if (isset($connection['providerKey'])) {
				$provider_keys[] = $connection['providerKey'];
			}
		}

		$providers = [];

		$all_providers = $this->apiship_providers();

		if (isset($all_providers['message'])) {
			return ['message' => $all_providers['message'], 'providers' => []];
		}

		foreach ($all_providers as $provider) {
			if (in_array($provider['key'], $provider_keys)) {
				$providers[] = $provider;
			}
		}

		return ['message' => '', 'providers' => $providers];
	}

	/**
	 * Выбранные в настройках точки привоза по службам доставки
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_providers_points(): array {
		$points = [];

		if (is_array($this->apiship_params['shipping_apiship_provider'] ?? null)) {
			foreach ($this->apiship_params['shipping_apiship_provider'] as $provider => $data) {
				if (!empty($data['id'])) {
					$points[] = $data['id'];
				}
			}
		}

		if (!$points) {
			return [];
		}

		$data = $this->apiship_point_by_params(['id=[' . implode(',', $points) . ']']);

		$points_data = [];

		if (isset($data['message'])) {
			return $points_data;
		}

		foreach ($data as $point) {
			$points_data[$point['providerKey']] = ['id' => $point['id'], 'address' => $point['code'] . ', ' . $this->get_address($point)];
		}

		return $points_data;
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	public function get_integrator_statuses(): array {
		$data = $this->apiship_statuses();

		$statuses_data = [];

		if (isset($data['message'])) {
			return $statuses_data;
		}

		foreach ($data as $status) {
			$statuses_data[] = ['key' => $status['key'], 'name' => $status['name']];
		}

		return $statuses_data;
	}

	/**
	 * Разбор кода способа доставки
	 * apiship.point_{provider}_{tariff}_{point}_{pickup} / apiship.door_{provider}_{tariff}_{pickup}
	 *
	 * @param string $code
	 *
	 * @return array<string, string>
	 */
	public function parce_code(string $code): array {
		$code_parts = explode('.', $code);

		$short_code = '';
		$tariff_parts = [];

		if (isset($code_parts[1])) {
			$tariff_parts = explode('_', $code_parts[1]);
			$short_code = $code_parts[1];
		}

		$delivery_type = $tariff_parts[0] ?? '';
		$provider = $tariff_parts[1] ?? '';
		$tariff_id = $tariff_parts[2] ?? '';
		$point_id = $tariff_parts[3] ?? '';
		$pickup_type = $tariff_parts[4] ?? '';

		if ($delivery_type == 'door') {
			$pickup_type = $point_id;
			$point_id = '';
		}

		return [
			'delivery_type' => $delivery_type,
			'provider'      => $provider,
			'tariff_id'     => $tariff_id,
			'point_id'      => $point_id,
			'short_code'    => $short_code,
			'pickup_type'   => $pickup_type
		];
	}

	/**
	 * Подстановка id точки в code_template тарифа карты (map_tariffs)
	 */
	public const POINT_ID_PLACEHOLDER = '{point_id}';

	/**
	 * Тарифы до ПВЗ из ответа калькулятора для карты: одна запись на запись калькулятора и тип забора,
	 * ключ {providerKey}_{tariffId}_{pickupType}. Один tariffId может прийти несколько раз (зоны: своя цена и свой
	 * набор точек) — каждая такая запись остаётся отдельным тарифом с суффиксом _2, _3… в ключе. Тип забора берётся,
	 * если он есть и в тарифе, и в настройках службы доставки. Цена, название и сроки заданы на запись калькулятора,
	 * а не на точку, поэтому карта получает их один раз, а точки ссылаются на тарифы ключами (map_points)
	 *
	 * @param array<int, array<string, mixed>> $providers                deliveryToPoint ответа калькулятора
	 * @param array<string, array<int, int>>   $pickup_types_by_provider providerKey => включённые в настройках типы забора
	 *
	 * @return array<string, array<string, mixed>> ключ => тариф: provider_key, tariff_id, pickup_type, code_template, name, description, days_min, days_max, cost, point_ids
	 */
	public static function map_tariffs(array $providers, array $pickup_types_by_provider): array {
		$tariffs = [];

		foreach ($providers as $provider) {
			$provider_key = (string)($provider['providerKey'] ?? '');

			$allowed_types = $pickup_types_by_provider[$provider_key] ?? [];

			$provider_tariffs = is_array($provider['tariffs'] ?? null) ? $provider['tariffs'] : [];

			foreach ($provider_tariffs as $tariff) {
				$tariff_id = (string)($tariff['tariffId'] ?? '');

				$tariff_types = is_array($tariff['pickupTypes'] ?? null) ? $tariff['pickupTypes'] : [];

				$point_ids = is_array($tariff['pointIds'] ?? null) ? array_map('strval', $tariff['pointIds']) : [];

				foreach ([1, 2] as $pickup_type) {
					if (!in_array($pickup_type, $tariff_types) || !in_array($pickup_type, $allowed_types)) {
						continue;
					}

					$key = $provider_key . '_' . $tariff_id . '_' . $pickup_type;

					for ($n = 2; isset($tariffs[$key]); $n++) {
						$key = $provider_key . '_' . $tariff_id . '_' . $pickup_type . '_' . $n;
					}

					$tariffs[$key] = [
						'provider_key'  => $provider_key,
						'tariff_id'     => $tariff_id,
						'pickup_type'   => $pickup_type,
						'code_template' => 'apiship.point_' . $provider_key . '_' . $tariff_id . '_' . self::POINT_ID_PLACEHOLDER . '_' . $pickup_type,
						'name'          => (string)($tariff['tariffName'] ?? ''),
						'description'   => (string)($tariff['tariffDescription'] ?? ''),
						'days_min'      => $tariff['daysMin'] ?? '',
						'days_max'      => $tariff['daysMax'] ?? '',
						'cost'          => (float)($tariff['deliveryCost'] ?? 0),
						'point_ids'     => $point_ids
					];
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
	 * @param array<string, array<string, mixed>> $tariffs результат map_tariffs
	 * @param array<int, array<string, mixed>>    $points  строки lists/points
	 *
	 * @return array{tariffs: array<string, array<string, mixed>>, points: array<string, array{point: array<string, mixed>, tariffs: array<int, string>}>}
	 *               tariffs — только использованные, без point_ids; points — id => точка и ключи её тарифов
	 */
	public static function map_points(array $tariffs, array $points): array {
		$by_id = [];

		foreach ($points as $point) {
			if (isset($point['id'])) {
				$by_id[(string)$point['id']] = $point;
			}
		}

		$map_points = [];
		$codes = [];

		foreach ($tariffs as $key => $tariff) {
			$code = (string)($tariff['code_template'] ?? $key);

			foreach ($tariff['point_ids'] as $point_id) {
				if (!isset($by_id[$point_id])) {
					continue;
				}

				if (!isset($map_points[$point_id])) {
					$map_points[$point_id] = ['point' => $by_id[$point_id], 'tariffs' => []];
				}

				if (isset($codes[$point_id][$code])) {
					unset($map_points[$point_id]['tariffs'][$codes[$point_id][$code]]);
				}

				$codes[$point_id][$code] = $key;

				$map_points[$point_id]['tariffs'][$key] = true;
			}
		}

		$used = [];

		foreach ($map_points as $point_id => $item) {
			$map_points[$point_id]['tariffs'] = array_keys($item['tariffs']);

			$used += $item['tariffs'];
		}

		$map_tariffs = [];

		foreach ($tariffs as $key => $tariff) {
			if (isset($used[$key])) {
				unset($tariff['point_ids']);

				$map_tariffs[$key] = $tariff;
			}
		}

		return ['tariffs' => $map_tariffs, 'points' => $map_points];
	}

	/**
	 * Код варианта доставки до ПВЗ: code_template тарифа карты с подставленным id точки
	 * (apiship.point_{provider}_{tariff}_{point}_{pickup}, разбирается parce_code)
	 *
	 * @param array<string, mixed> $tariff элемент map_tariffs
	 * @param string               $point_id
	 *
	 * @return string
	 */
	public static function point_code(array $tariff, string $point_id): string {
		return str_replace(self::POINT_ID_PLACEHOLDER, $point_id, (string)($tariff['code_template'] ?? ''));
	}

	/**
	 * Расчёт грузоместа и позиций по товарам корзины/заказа
	 *
	 * @param array<mixed> $products  товары с price в базовой валюте магазина
	 * @param float        $total_sum сумма заказа уже в валюте shipping_apiship_rub_select
	 *
	 * @return array<string, mixed>
	 */
	public function calculate_places(array $products, float $total_sum): array {
		$this->toLog('calculate_places', ['products' => $products, 'total_sum' => $total_sum]);

		$total_weight = 0;
		$total_length = 0;
		$total_width = 0;
		$total_height = 0;
		$total_cost = 0;

		$items = [];

		$this->load->model('catalog/product');

		$cm_select = (int)($this->apiship_params['shipping_apiship_cm_select'] ?? 0);
		$gr_select = (int)($this->apiship_params['shipping_apiship_gr_select'] ?? 0);
		$rub_select = (string)($this->apiship_params['shipping_apiship_rub_select'] ?? '');
		$config_currency = (string)$this->config->get('config_currency');

		$total_quantity = 0;

		foreach ($products as $product) {
			$product_info = $this->model_catalog_product->getProduct((int)$product['product_id']);

			$quantity = (int)$product['quantity'];

			if ($quantity < 1) {
				$quantity = 1;
			}

			$length = (isset($product['length']) && (float)$product['length'] != 0) ? $this->length->convert((float)$product['length'], (int)$product['length_class_id'], $cm_select) : $this->format_dimension((float)($this->apiship_params['shipping_apiship_parcel_length'] ?? 0));
			$width = (isset($product['width']) && (float)$product['width'] != 0) ? $this->length->convert((float)$product['width'], (int)$product['length_class_id'], $cm_select) : $this->format_dimension((float)($this->apiship_params['shipping_apiship_parcel_width'] ?? 0));
			$height = (isset($product['height']) && (float)$product['height'] != 0) ? $this->length->convert((float)$product['height'], (int)$product['length_class_id'], $cm_select) : $this->format_dimension((float)($this->apiship_params['shipping_apiship_parcel_height'] ?? 0));
			$weight = (isset($product['weight']) && (float)$product['weight'] != 0) ? $this->weight->convert((float)$product['weight'] / $quantity, (int)$product['weight_class_id'], $gr_select) : $this->format_dimension((float)($this->apiship_params['shipping_apiship_parcel_weight'] ?? 0));

			// Суммы для ApiShip — в валюте «рубль» из настроек; цены товаров хранятся в базовой валюте магазина
			$cost = $this->format_cost($this->currency->convert((float)$product['price'], $config_currency, $rub_select));

			$articul = (string)$product['model'];

			switch ((string)($this->apiship_params['shipping_apiship_articul_mode'] ?? '')) {
				case '':
				case '0':
				case 'sku':
					if (!empty($product_info['sku'])) {
						$articul = $product_info['sku'];
					}
					break;
				case 'upc':
					if (!empty($product_info['upc'])) {
						$articul = $product_info['upc'];
					}
					break;
				case 'ean':
					if (!empty($product_info['ean'])) {
						$articul = $product_info['ean'];
					}
					break;
				case 'jan':
					if (!empty($product_info['jan'])) {
						$articul = $product_info['jan'];
					}
					break;
				case 'isbn':
					if (!empty($product_info['isbn'])) {
						$articul = $product_info['isbn'];
					}
					break;
				case 'mpn':
					if (!empty($product_info['mpn'])) {
						$articul = $product_info['mpn'];
					}
					break;
			}

			$articul = mb_strimwidth($articul, 0, 50);

			$items[] = [
				'articul'     => $articul,
				'description' => $product['name'],
				'quantity'    => $quantity,
				'weight'      => $weight,
				'height'      => $height,
				'length'      => $length,
				'width'       => $width,
				'cost'        => $cost
			];

			$total_quantity += $quantity;

			for ($i = 0; $i < $quantity; $i++) {
				$total_weight += $weight;
				$total_cost += $cost;

				$item_ar = [$length, $width, $height];

				rsort($item_ar);

				if ($item_ar[0] > $total_length) {
					$total_length = $item_ar[0];
				}

				if ($item_ar[1] > $total_width) {
					$total_width = $item_ar[1];
				}

				$total_height += $item_ar[2];
			}
		}

		// Перераспределение скидки: стоимость позиций приводится к итоговой сумме заказа
		if ($total_cost != 0) {
			$delta_koef = $total_sum / $total_cost;
		} else {
			$delta_koef = 0;
		}

		$total_cost = $total_sum;

		foreach ($items as &$item) {
			$item['cost'] = $this->format_cost($delta_koef * $item['cost']);
			$total_cost = $this->format_cost($total_cost - $item['cost'] * $item['quantity']);
		}

		unset($item);

		rsort($items);

		if ($items && $total_cost != 0) {
			$last = count($items) - 1;

			if ($items[$last]['quantity'] > 1) {
				$items[] = $items[$last];

				$items[$last]['quantity'] = $items[$last]['quantity'] - 1;
				$items[$last + 1]['quantity'] = 1;
				$items[$last + 1]['cost'] = $this->format_cost($items[$last + 1]['cost'] + $total_cost);
			} else {
				$items[$last]['cost'] = $this->format_cost($items[$last]['cost'] + $total_cost);
			}
		}

		$assessed_sum = 0;

		foreach ($items as &$item) {
			if (!empty($this->apiship_params['shipping_apiship_use_fix_product_assessed_cost'])) {
				$item['assessed_cost'] = $this->format_cost($this->currency->convert((float)($this->apiship_params['shipping_apiship_fix_product_assessed_cost'] ?? 0), $config_currency, $rub_select));
			} else {
				$item['assessed_cost'] = $item['cost'];
			}

			$assessed_sum += $this->format_cost($item['assessed_cost'] * $item['quantity']);
		}

		unset($item);

		$place_params_length = (float)($this->apiship_params['shipping_apiship_place_length'] ?? 0);
		$place_params_width = (float)($this->apiship_params['shipping_apiship_place_width'] ?? 0);
		$place_params_height = (float)($this->apiship_params['shipping_apiship_place_height'] ?? 0);
		$place_params_weight = (float)($this->apiship_params['shipping_apiship_place_weight'] ?? 0);
		$package_params_weight = (float)($this->apiship_params['shipping_apiship_package_weight'] ?? 0);

		if ($place_params_length > 0) {
			$total_length = $place_params_length;
		}

		if ($place_params_width > 0) {
			$total_width = $place_params_width;
		}

		if ($place_params_height > 0) {
			$total_height = $place_params_height;
		}

		if ($place_params_weight > 0) {
			$total_weight = $place_params_weight;
		}

		if ($package_params_weight > 0) {
			$total_weight += $package_params_weight;
		}

		$total_length = max(1, (int)$total_length);
		$total_width = max(1, (int)$total_width);
		$total_height = max(1, (int)$total_height);
		$total_weight = max(1, (int)$total_weight);

		return [
			'items'         => $items,
			'total_length'  => $total_length,
			'total_width'   => $total_width,
			'total_height'  => $total_height,
			'total_weight'  => $total_weight,
			'total_cost'    => $total_sum,
			'assessed_cost' => $assessed_sum
		];
	}

	/**
	 * @param string $provider
	 *
	 * @return string
	 */
	private function get_pickup_id(string $provider): string {
		return (string)($this->apiship_params['shipping_apiship_provider'][$provider]['id'] ?? '');
	}

	/**
	 * @param float $cost
	 *
	 * @return float
	 */
	public function format_cost($cost): float {
		return round((float)$cost, 2);
	}

	/**
	 * @param float $weight
	 *
	 * @return float
	 */
	public function format_weight($weight): float {
		return floor((float)$weight);
	}

	/**
	 * @param float $dimension
	 *
	 * @return float
	 */
	public function format_dimension($dimension): float {
		return round((float)$dimension);
	}
}
