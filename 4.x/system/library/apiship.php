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
		$headers = [];

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
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

		$result = curl_exec($ch);

		if ($result === false) {
			$this->log->write('curl error ' . $url . ' ' . print_r(curl_error($ch), true));
		}

		curl_close($ch);

		return ['body' => $result, 'headers' => $headers];
	}

	/**
	 * @param string               $url
	 * @param array<string, mixed> $data
	 *
	 * @return array<string, mixed>
	 */
	private function curl_post(string $url, array $data): array {
		$headers = [];

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
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

		$result = curl_exec($ch);

		if ($result === false) {
			$this->log->write('curl error ' . $url . ' ' . print_r(curl_error($ch), true));
		}

		curl_close($ch);

		return ['body' => $result, 'headers' => $headers];
	}

	/**
	 * Постраничная выгрузка списков API с кешированием в сессии
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

		$data = $this->getData($data_key);

		if ($data !== null && isset($data['data_hash']) && $data['data_hash'] == $data_hash) {
			$this->toLog($data_key . ' cached');

			return $data['rows'];
		}

		$offset = 0;
		$rows = [];
		$x_tracing_ids = [];
		$url = '';

		do {
			$url = $this->apiship_params['shipping_apiship_url'] . $cmd . '?limit=' . $limit . '&offset=' . $offset;

			if ($filter_list) {
				$url .= '&filter=' . urlencode(implode(';', $filter_list));
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

			$rows = array_merge($rows, $data['rows']);

			$rows_count = count($data['rows']);
			$offset += $rows_count;
		} while ($rows_count > 0);

		$this->toLog('shipping_apiship_data ' . $cmd, ['url' => $url, 'x_tracing_id' => $x_tracing_ids, 'output' => $rows]);

		$this->setData($data_key, ['rows' => $rows, 'data_hash' => $data_hash]);

		return $rows;
	}

	/**
	 * Кеш в сессии OpenCart (общий для админки и витрины в рамках сессии)
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
	 * Точки по списку id (порциями по 1000)
	 *
	 * @param array<int, mixed> $points
	 *
	 * @return array<mixed>
	 */
	public function apiship_points(array $points): array {
		$data_hash = md5(print_r($points, true));
		$data_key = 'apiship_points';

		$data = $this->getData($data_key);

		if ($data !== null && isset($data['data_hash']) && $data['data_hash'] == $data_hash) {
			$this->toLog($data_key . ' cached');

			return $data['all_points'];
		}

		$all_points = [];
		$limit = 1000;
		$offset = 0;

		while ($offset * $limit < count($points)) {
			$part_points = array_slice($points, $offset * $limit, $limit);

			$url = $this->apiship_params['shipping_apiship_url'] . 'lists/points?limit=10000&offset=0&filter=' . urlencode('id=[' . implode(',', $part_points) . ']');

			$output = $this->curl_get($url);

			$data = json_decode((string)$output['body'], true);

			if (isset($data['errors'])) {
				$this->toLog('shipping_apiship points error ', ['url' => $url, 'output' => $output], true);

				return [];
			}

			if (!isset($data['rows'])) {
				$this->toLog('shipping_apiship points error2 ', ['url' => $url, 'output' => $output], true);

				return [];
			}

			$all_points = array_merge($all_points, $data['rows']);

			$this->toLog('shipping_apiship points ', ['url' => $url, 'output' => $data]);

			$offset++;
		}

		$this->setData($data_key, ['all_points' => $all_points, 'data_hash' => $data_hash]);

		return $all_points;
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
	 * @param string             $postcode
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

		$data_hash = md5($country . $region . $city . $postcode . $ext_address . print_r($providers, true) . print_r($products, true) . $total . $cash_on_delivery . print_r($extraParams, true));
		$data_key = 'apiship_calculator';

		$data = $this->getData($data_key);

		if ($data !== null && isset($data['data_hash']) && $data['data_hash'] == $data_hash) {
			$this->toLog($data_key . ' cached');

			return $data;
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

		$output = $this->curl_post($url, $params);

		$x_tracing_id = $output['headers']['x-tracing-id'][0] ?? '?';

		$data = [
			'body'         => json_decode((string)$output['body'], true),
			'x-tracing-id' => $x_tracing_id,
			'data_hash'    => $data_hash
		];

		$this->toLog('shipping_apiship_calculator', ['url' => $url, 'params' => $params, 'output' => $data], isset($data['body']['errors']));

		if (isset($data['body']['errors'])) {
			return [
				'body'         => ['message' => sprintf($this->apiship_params['shipping_apiship_no_shipping'] ?? '%s', $city . ', ' . $region)],
				'x-tracing-id' => $x_tracing_id
			];
		}

		$this->setData($data_key, $data);

		return $data;
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
			$one_item_weight = $this->format_weight($order_params['placeWeight'] / $total_count);

			$total_calculation_weight = $order_params['placeWeight'];

			foreach ($params['places'][0]['items'] as $key => $item) {
				$total_calculation_weight -= $one_item_weight * $item['quantity'];

				$params['places'][0]['items'][$key]['weight'] = $one_item_weight;
			}

			// Остаток веса (из-за округления) добавляем последней позиции
			if ($total_calculation_weight > 0) {
				$last = array_key_last($params['places'][0]['items']);

				$params['places'][0]['items'][$last]['weight'] += $this->format_weight($total_calculation_weight / $params['places'][0]['items'][$last]['quantity']);
			}
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
	 * Расчёт грузоместа и позиций по товарам корзины/заказа
	 *
	 * @param array<mixed> $products
	 * @param float        $total_sum
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

			$cost = $this->format_cost($this->currency->convert((float)$product['price'], $rub_select, $config_currency));

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
				$item['assessed_cost'] = $this->format_cost($this->currency->convert((float)($this->apiship_params['shipping_apiship_fix_product_assessed_cost'] ?? 0), $rub_select, $config_currency));
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
