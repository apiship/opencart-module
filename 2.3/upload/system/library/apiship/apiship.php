<?php
class Apiship {

	private $log;
	private $registry;
	private $apiship_params;

	public function __construct($registry, $apiship_params, $log) {
		$this->registry = $registry; 
		$this->apiship_params = $apiship_params;
		$this->log = $log;

		if ($apiship_params['shipping_apiship_mode']=='shipping_apiship_mode_test') 
			$this->apiship_params['shipping_apiship_url'] = "http://api.dev.apiship.ru/v1/";
		else
			$this->apiship_params['shipping_apiship_url'] = "https://api.apiship.ru/v1/";

	}

	public function __get($name) {
		return $this->registry->get($name);
	}

	public function toLog($prefix, $data='', $error = false) {
		if ($this->isDebug() || $error == true)
		{
			$this->log->write($prefix . PHP_EOL . print_r($data, 1));
		}
	}

	private function isDebug() {
		//return false;
		return $this->apiship_params['shipping_apiship_mode']=='shipping_apiship_mode_debug' || $this->apiship_params['shipping_apiship_mode']=='shipping_apiship_mode_test';
	}

	private function curl_get($url) {

		$ch = curl_init();
	    	curl_setopt($ch, CURLOPT_URL, $url);
	    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    	curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Platform: opencart_v2.3', 'Content-Type: application/json', 'Authorization: '.$this->apiship_params['shipping_apiship_token'] , 'Accept: application/json'));
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
	    	$result = curl_exec($ch); 
		if($result === false)
		{
			$this->log->write('curl error ' . $url . ' ' . print_r(curl_error($ch), 1));
		}
	    	curl_close($ch);

		return ['body' => $result, 'headers' => $headers];
	}

	private function curl_post($url, $data) {

		$ch = curl_init();
	    	curl_setopt($ch, CURLOPT_URL, $url);
	    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    	curl_setopt($ch, CURLOPT_TIMEOUT, 20);
	    	curl_setopt($ch, CURLOPT_POST, 1);
	    	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Platform: opencart_v2.3', 'Content-Type: application/json', 'Authorization: '.$this->apiship_params['shipping_apiship_token'] , 'Accept: application/json'));
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

	    	$result = curl_exec($ch); 
		if($result === false)
		{
			$this->log->write('curl error ' . $url . ' ' . print_r(curl_error($ch), 1));
		}

	    	curl_close($ch);

		return ['body' => $result, 'headers' => $headers];
	}

 	private function apiship_data($cmd, $limit, $filter_list = '') {

		$offset = 0;
		$rows = [];
		$x_tracing_ids = [];

		do {
			$url = $this->apiship_params['shipping_apiship_url'] . $cmd. '?limit='.$limit.'&offset='.$offset;
			if ($filter_list!='') $url = $url . '&filter=' . urlencode(implode(';',$filter_list));

			$output = $this->curl_get($url);

			$data = json_decode($output['body'], true);
			if (isset($output['headers']['x-tracing-id'][0])) $x_tracing_ids[] = $output['headers']['x-tracing-id'][0]; else $x_tracing_ids[] = '?';

			if (isset($data['errors'])) {
				$this->toLog('shipping_apiship_data '.$cmd.' error1', ['url' => $url, 'output' => $output], true);
				return [];
			}

			if (!isset($data['rows'])) {
				$this->toLog('shipping_apiship_data '.$cmd.' error2', ['url' => $url, 'output' => $output], true);
				return ['message' => $data['message']];
			}

			$rows = array_merge($rows,$data['rows']);

			$rows_count = count($data['rows']);
			$offset = $offset + $rows_count;


		} while($rows_count > 0); 
		
		$this->toLog('shipping_apiship_data '.$cmd, ['url' => $url,	'$x_tracing_id' => $x_tracing_ids, 'output' => $rows]);

		return $rows;
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

		$params = [		
			'orderIds' => $order_id,
			'format' => 'pdf'
		];

		$output = $this->curl_post($url, $params);
		if (isset($output['headers']['x-tracing-id'][0])) $x_tracing_id = $output['headers']['x-tracing-id'][0]; else $x_tracing_id = '?';

		$data = [
			'body' => json_decode($output['body'], true),
			'x-tracing-id' => $x_tracing_id
		];

		$this->toLog('shipping_apiship_labels', ['url' => $url, 'params' => $params, 'output' => $data], isset($data['body']['errors']));
		return $data;


	}

 	public function apiship_waybills($order_id) {

		$url = $this->apiship_params['shipping_apiship_url'] . 'orders/waybills';

		$params = [		
			'orderIds' => $order_id,
		];

		$output = $this->curl_post($url, $params);
		if (isset($output['headers']['x-tracing-id'][0])) $x_tracing_id = $output['headers']['x-tracing-id'][0]; else $x_tracing_id = '?';

		$data = [
			'body' => json_decode($output['body'], true),
			'x-tracing-id' => $x_tracing_id
		];

		$this->toLog('shipping_apiship_waybills', ['url' => $url, 'params' => $params, 'output' => $data], isset($data['body']['errors']));
		return $data;


	}

 	public function apiship_points($points) {
		$all_points = [];
		$limit = 1000;
		$offset = 0;
		while($offset*$limit < count($points)) {
			$part_points = array_slice($points, $offset*$limit, $limit);
			$url = $this->apiship_params['shipping_apiship_url'] . 'lists/points?limit=10000&offset=0&filter=' . urlencode('id=['. implode(',' , $part_points) .']');
			$output = $this->curl_get($url);
			$data = json_decode($output['body'], true);

			if (isset($data['errors'])) {
				$this->toLog('shipping_apiship points error ', ['url' => $url, 'output' => $output], true);
				return [];
			}

			if (!isset($data['rows'])) {
				$this->toLog('shipping_apiship points error2 ', ['url' => $url, 'output' => $output], true);
				return [];
			}

			if (isset($data['rows'])) $data_points = $data['rows']; else $data_points = [];
			$all_points = array_merge($all_points, $data_points);

			$this->toLog('shipping_apiship points ', ['url' => $url, 'output' => $data]);

			$offset++;
		}

		return $all_points;
	}

 	public function apiship_orders_status($date) {

		$url = $this->apiship_params['shipping_apiship_url'] . 'orders/statuses/date/' . $date;

		$output = $this->curl_get($url);
		$data = json_decode($output['body'], true);

		if (isset($output['headers']['x-tracing-id'][0])) $x_tracing_id = $output['headers']['x-tracing-id'][0]; else $x_tracing_id = '?';

		$this->toLog('shipping_apiship orders_status', ['url' => $url, 'x_tracing_id' => $x_tracing_id, 'output' => $data], isset($data['body']['errors']));

		return $data;
	}

 	public function apiship_order_status($order_id) {

		$url = $this->apiship_params['shipping_apiship_url'] . 'orders/'. $order_id . '/status';

		$output = $this->curl_get($url);
		$data = json_decode($output['body'], true);

		if (isset($output['headers']['x-tracing-id'][0])) $x_tracing_id = $output['headers']['x-tracing-id'][0]; else $x_tracing_id = '?';

		$this->toLog('shipping_apiship order_status', ['url' => $url, 'x_tracing_id' => $x_tracing_id, 'output' => $data], isset($data['body']['errors']));

		return $data;
	}

 	public function apiship_oc_order_status($order_id) {

		$url = $this->apiship_params['shipping_apiship_url'] . 'orders/status?clientNumber='.$order_id;

		$output = $this->curl_get($url);
		$data = json_decode($output['body'], true);

		if (isset($output['headers']['x-tracing-id'][0])) $x_tracing_id = $output['headers']['x-tracing-id'][0]; else $x_tracing_id = '?';

		$this->toLog('shipping_apiship order_status', ['url' => $url, 'x_tracing_id' => $x_tracing_id, 'output' => $data], isset($data['body']['errors']));

		return $data;
	}

 	public function apiship_calculator($region, $city, $postcode, $ext_address, $providers, $products, $place_params = []) {

		if (trim($city) == '') {		
			$output['message'] = $this->apiship_params['shipping_apiship_error_select_city']; 
				
			return [
				'body' => $output,
				'x-tracing-id' => ''
			];
		}

		$calculate_data = $this->calculate_places($products);
		$items = $calculate_data['items'];
		$cart_length = (empty($place_params['length'])) ? $calculate_data['total_length'] : $place_params['length'];
		$cart_width = (empty($place_params['width'])) ? $calculate_data['total_width'] : $place_params['width'];
		$cart_height = (empty($place_params['height'])) ? $calculate_data['total_height'] : $place_params['height'];
		$cart_weight = (empty($place_params['weight'])) ? $calculate_data['total_weight'] : $place_params['weight'];
		$cart_cost = $calculate_data['total_cost'];
		
		$url = $this->apiship_params['shipping_apiship_url'] . 'calculator';

		$places = [];

		if (false) {
			foreach($items as $item) {
				$places[] = [
				      'height' => $item['height'],
				      'length' => $item['length'],
				      'width' => $item['width'],
				      'weight' => $item['weight']
				];
			}
		} else {
		 	$places[] = [
		      	'height' => $cart_height,
		      	'length' => $cart_length,
		      	'width' => $cart_width,
		      	'weight' => $cart_weight
			];
		}


		$params = [		
			'from' => [
			   	'countryCode' => $this->apiship_params['shipping_apiship_sending_country_code'],
			    	'region' => $this->apiship_params['shipping_apiship_sending_region'],
			    	'city' => $this->apiship_params['shipping_apiship_sending_city'],
			],
			'to' => [
			   	'region' => $region,
			    	'city' => $city,
			],
			'places' => $places,
			'customCode' => $this->apiship_params['shipping_apiship_custom_code'],
		  	'assessedCost' => $cart_cost,
			'includeFees' => $this->apiship_params['shipping_apiship_include_fees']
		  	
		];

		if ($providers!=[]) $params['providerKeys'] = $providers;
		if ($postcode!='') $params['to']['index'] = $postcode;
		if ($ext_address!='') $params['to']['addressString'] = $ext_address;


		$output = $this->curl_post($url, $params);
		if (isset($output['headers']['x-tracing-id'][0])) $x_tracing_id = $output['headers']['x-tracing-id'][0]; else $x_tracing_id = '?';

		$data = [
			'body' => json_decode($output['body'], true),
			'x-tracing-id' => $x_tracing_id
		];

		$this->toLog('shipping_apiship_calculator', ['url' => $url,	'params' => $params, 'output' => $data], isset($data['body']['errors']));

		if (isset($data['body']['errors'])) {		
			$output['message'] = $this->apiship_params['shipping_apiship_error_calculator']; 
				
			return [
				'body' => $output,
				'x-tracing-id' => ''
			];
		}

		return $data;

	}

 	public function apiship_order($order_params) {

		$url = $this->apiship_params['shipping_apiship_url'] . 'orders/sync';

		$params = [
			'order' => [
		    		'clientNumber' => $order_params['orderId'], // Номер заказа в системе клиента. Может быть переиспользован, если отменить или удалить заказ.
		    		'weight' => $order_params['orderWeight'], // Вес всего заказа в граммах
		    		'providerKey' => $order_params['orderProviderKey'], // Код службы доставки
		    		'pickupType' => $order_params['orderPickupType'], // Тип забора груза 1 - от двери клиента 2 – клиент привозит заказ на склад СД
		    		'deliveryType' => $order_params['orderDeliveryType'], // Тип доставки 1 - до двери 2 – до ПВЗ 
		    		'tariffId' => $order_params['orderTariffId'], // Тариф службы доставки по которому осуществляется доставка
				'pointOutId' => $order_params['orderPointOutId'], // ID пункта выдачи заказов из метода /lists/points. Обязательно если доставка до ПВЗ
				'pickupDate' => date("Y-m-d")
		  	],
		  	'cost' => [
		    		'assessedCost' => $order_params['sub_total_cost'], // Оценочная стоимость / сумма страховки (в рублях)
		    		'codCost' => $order_params['costCodCost'], // Сумма наложенного платежа с учетом НДС (в рублях)
				'deliveryCost' => $order_params['costDeliveryCost']
		  	],
			'sender' => [
				'email' => $this->apiship_params['shipping_apiship_contact_email'],// Контактный email адрес
			    	'phone' => $this->apiship_params['shipping_apiship_contact_phone'], // Контактный телефон
				'companyName' => $this->apiship_params['shipping_apiship_contact_organization'],// Название компании
			    	'contactName' => $this->apiship_params['shipping_apiship_contact_name'], // ФИО контактного лица
			    	'countryCode' => $this->apiship_params['shipping_apiship_sending_country_code'], // Код страны в соответствии с ISO 3166-1 alpha-2
			    	'region' => $this->apiship_params['shipping_apiship_sending_region'], // Область или республика или край
			    	'city' => $this->apiship_params['shipping_apiship_sending_city'], // Город или населенный пункт
				'street' => $this->apiship_params['shipping_apiship_sending_street'], // Улица
				'house' => $this->apiship_params['shipping_apiship_sending_house'], // Дом
				'block' => $this->apiship_params['shipping_apiship_sending_block'], // Строение/Корпус
				'office' => $this->apiship_params['shipping_apiship_sending_office'] // Офис/Квартира
			],
			'recipient' => [
			  	'phone' => $order_params['recipientPhone'], // Контактный телефон 
			    	'contactName' => $order_params['recipientContactName'], // ФИО контактного лица
				'addressString' => $order_params['recipientAddressString'], // Адрес одной строкой
				/*
				'countryCode' => $order_params['recipientCountryCode'], // Код страны в соответствии с ISO 3166-1 alpha-2 
			    	'region' => $order_params['recipientRegion'], // Область или республика или край
			    	'city' => $order_params['recipientCity'], // Город или населенный пункт
				'street' => $order_params['recipientStreet'], // Улица
				'house' => $order_params['recipientHouse'], // Дом
				*/
				'comment' => $order_params['recipientComment'] // Комментарий
			],
			'returnAddress' => [
				'email' => $this->apiship_params['shipping_apiship_contact_email'],// Контактный email адрес
			    	'phone' => $this->apiship_params['shipping_apiship_contact_phone'], // Контактный телефон
				'companyName' => $this->apiship_params['shipping_apiship_contact_organization'],// Название компании
			    	'contactName' => $this->apiship_params['shipping_apiship_contact_name'], // ФИО контактного лица
			    	'countryCode' => $this->apiship_params['shipping_apiship_sending_country_code'], // Код страны в соответствии с ISO 3166-1 alpha-2
			    	'region' => $this->apiship_params['shipping_apiship_sending_region'], // Область или республика или край
			    	'city' => $this->apiship_params['shipping_apiship_sending_city'], // Город или населенный пункт
				'street' => $this->apiship_params['shipping_apiship_sending_street'], // Улица
				'house' => $this->apiship_params['shipping_apiship_sending_house'], // Дом
				'block' => $this->apiship_params['shipping_apiship_sending_block'], // Строение/Корпус
				'office' => $this->apiship_params['shipping_apiship_sending_office'] // Офис/Квартира
			],
			'places' => [
			    	[
			      	'height' => $order_params['placeHeight'],
			      	'length' => $order_params['placeLength'],
			      	'width' => $order_params['placeWidth'],
			      	'weight' => $order_params['placeWeight'],
			    	]
			]
		];

		$pointInId = $this->get_pickup_id($order_params['orderProviderKey']);
		if ($pointInId != '') $params['order']['pointInId'] = $pointInId;

		$koef = $order_params['costAssessedCost']/$order_params['sub_total_cost'];
		$total_cost = 0;

		$koef_weight = $order_params['placeWeight']/$order_params['placeCalculateWeight'];
		$total_weight = 0;

		foreach($order_params['items'] as $item) {
			
			$cost = $this->format_cost(($order_params['costCodCost']==0)?0:$item['cost']*$koef);
			$assessed_cost = $item['cost'];
			$total_cost = $total_cost + $cost*$item['quantity'];
			
			$weight = $this->format_weight($item['weight']*$koef_weight);
			$total_weight = $total_weight + $weight*$item['quantity'];
			
			$params['places'][0]['items'][] = [
				'articul' => $item['articul'],
				'description' => $item['description'],
				'quantity' => $item['quantity'],
				'weight' => $weight,
				'cost' => $cost,
				'assessedCost' => $assessed_cost
			];

		}

		$cost_dif = $total_cost - $order_params['costCodCost'] + $order_params['costDeliveryCost']; 
		$weight_dif = $total_weight - $order_params['placeWeight'];

		$this->toLog('shipping_apiship_order', [
			'cost_dif' => $cost_dif,
			'total_cost' => $total_cost,
			'koef' => $koef,

			'weight_dif' => $weight_dif,
			'total_weight' => $total_weight,
			'koef_weight' => $koef_weight
		]);


		if ($cost_dif != 0) {
			if ($params['places'][0]['items'][0]['quantity'] == 1) {
				$params['places'][0]['items'][0]['cost'] -= $cost_dif;
			} else {

				$params['places'][0]['items'][0]['quantity']--;
				$params['places'][0]['items'][] = [
					'articul' => $params['places'][0]['items'][0]['articul'],
					'description' => $params['places'][0]['items'][0]['description'],
					'quantity' => 1,
					'weight' => $params['places'][0]['items'][0]['weight'],
					'cost' => $params['places'][0]['items'][0]['cost'] - $cost_dif,
					'assessedCost' => $params['places'][0]['items'][0]['assessedCost']
				];


			}
		}

		if ($weight_dif != 0) {
			$params['places'][0]['items'][0]['weight'] -= $weight_dif;
		}

		$output = $this->curl_post($url, $params);

		if (isset($output['headers']['x-tracing-id'][0])) $x_tracing_id = $output['headers']['x-tracing-id'][0]; else $x_tracing_id = '?';

		$data = [
			'body' => json_decode($output['body'], true),
			'x-tracing-id' => $x_tracing_id
		];

		$this->toLog('shipping_apiship_order', ['url' => $url, 'params' => $params, 'output' => $data], isset($data['body']['errors']));
		
		if (isset($data['body']['errors'][0]['field'])) {
			if ($data['body']['errors'][0]['field'] == 'clientNumber') {
				$order_data = $this->apiship_oc_order_status($order_params['orderId']);
				if (isset($order_data['orderInfo']['orderId'])) return ['body' => $order_data['orderInfo']];
			}
		}

		return $data;


	}

 	public function apiship_cancel_order($apiship_order_id) {
				
		$url = $this->apiship_params['shipping_apiship_url'] . 'orders/' . $apiship_order_id . '/cancel';

		$output = $this->curl_get($url, '');

		if (isset($output['headers']['x-tracing-id'][0])) $x_tracing_id = $output['headers']['x-tracing-id'][0]; else $x_tracing_id = '?';

		$data = [
			'body' => json_decode($output['body'], true),
			'x-tracing-id' => $x_tracing_id
		];

		$this->toLog('shipping_apiship_cancel_order', ['url' => $url, 'output' => $data], isset($data['body']['errors']));
		
		if (isset($data['body']['code'])) {
			if ($data['body']['code'] == '040081') {
				$order_data = $this->apiship_order_status($apiship_order_id);
				if (isset($order_data['orderInfo'])) {
					return ['body' => $order_data['orderInfo']];
				}
			}
		}
		return $data;
	}

 	public function apiship_order_info($apiship_order_id) {


		$url = $this->apiship_params['shipping_apiship_url'] . 'orders/' . $apiship_order_id;

		$output = $this->curl_get($url, '');

		if (isset($output['headers']['x-tracing-id'][0])) $x_tracing_id = $output['headers']['x-tracing-id'][0]; else $x_tracing_id = '?';

		$data = [
			'body' => json_decode($output['body'], true),
			'x-tracing-id' => $x_tracing_id
		];


		$this->toLog('shipping_apiship_order_info', ['url' => $url, 'output' => $data], isset($data['body']['errors']));
		return $data;
	}


	public function get_address($params) {

		if ($params['regionType'] == 'г') {
			$address = $params['region'];
			if ($params['city']!=$params['region']) $address = $address. ', ' . $params['cityType'] . ' ' . $params['city'];
			if (!empty($params['area'])) $address = $address. ', ' . $params['area'] . ' р-н';
		}
		else {
			$address = $params['region'] . ' '. $params['regionType'];
			if (!empty($params['area'])) $address = $address. ', ' . $params['area'] . ' р-н';
			$address = $address. ', ' . $params['cityType'] . ' ' . $params['city'];
		}

		if (!empty($params['street'])) $full_street = $params['streetType'] . ' ' . $params['street']; else $full_street = '';
		if (!empty($params['community'])) $full_community = $params['communityType'] . ' ' . $params['community']; else $full_community = '';

		if (!empty($full_community)) {
			$address = $address. ', ' .$full_community;
		}

		if (!empty($full_street)) {
			$address = $address. ', ' .$full_street;
		}

		if (!empty($params['house'])) {
			if (mb_strpos($params['house'], 'д')===false)
				$address = $address. ', д.' . $params['house'];	
			else
				$address = $address. ' ' . $params['house'];	

		}
		if (!empty($params['block'])) $address = $address. ' корпус ' . $params['block'];
		if (!empty($params['office'])) $address = $address. ', офис ' . $params['office'];
		

		return $address;
	}

 	public function get_providers() {

		$provider_keys = [];
		
		$connections = $this->apiship_connections();
		if(isset($connections['message'])) return ['message' => $connections['message'], 'providers' => []];

		foreach($connections as $connection) {
			$provider_keys[] = $connection['providerKey'];
		}
		
		$providers = [];
		foreach($this->apiship_providers() as $provider) {
			if (in_array($provider['key'], $provider_keys)) $providers[] = $provider;
		}
		
		return ['message' => '', 'providers' => $providers];
	}

 	public function get_providers_points() {
		$points = [];
		if (is_array($this->apiship_params['shipping_apiship_provider']))
		foreach($this->apiship_params['shipping_apiship_provider'] as $provider => $data) {
			if (!empty($data['id'])) $points[] = $data['id'];
		}

		$data = $this->apiship_point_by_params(['id=['.implode(',',$points).']']);

		$points_data = [];
		if (isset($data['message'])) return $points_data;

		foreach($data as $point) {
			$points_data[$point['providerKey']] = ['id' => $point['id'], 'address' => $point['code']. ', ' . $this->get_address($point)];
		}
		return $points_data;
	}

 	public function get_integrator_statuses() {

		$data = $this->apiship_statuses();		
		$statuses_data = [];
		if (isset($data['message'])) return $statuses_data;

		foreach($data as $status) {
			$statuses_data[] = ['key' => $status['key'], 'name' => $status['name']];
		}
		return $statuses_data;
	}

	public function parce_code($code) {

		$code_parts = explode('.',$code);
		if (isset($code_parts[1])) $tariff_parts = explode('_',$code_parts[1]);

		if (isset($tariff_parts[0])) $delivery_type = $tariff_parts[0]; else $delivery_type = '';
		if (isset($tariff_parts[1])) $provider = $tariff_parts[1]; else $provider = ''; 
		if (isset($tariff_parts[2])) $tariff_id = $tariff_parts[2]; else $tariff_id = '';
		if (isset($tariff_parts[3])) $point_id = $tariff_parts[3]; else $point_id = '';

		return [
			'delivery_type' => $delivery_type,
			'provider' => $provider,
			'tariff_id' => $tariff_id,
			'point_id' => $point_id,
			'short_code' => $code_parts[1]
		];
	}

	public function calculate_places($products) {

		$total_weight = 0;
		$total_length = 0;
		$total_width = 0;
		$total_height = 0;
		$total_cost = 0;

		$items = [];

		$this->load->model('catalog/product'); 

		foreach ($products as $product) {

			$product_info = $this->model_catalog_product->getProduct($product['product_id']);

			$length = intval($this->length->convert($product_info['length'], $product_info['length_class_id'], $this->apiship_params['shipping_apiship_cm_select'])); 
			$width = intval($this->length->convert($product_info['width'], $product_info['length_class_id'], $this->apiship_params['shipping_apiship_cm_select'])); 
			$height = intval($this->length->convert($product_info['height'], $product_info['length_class_id'], $this->apiship_params['shipping_apiship_cm_select'])); 
			$weight = intval($this->weight->convert($product_info['weight'], $product_info['weight_class_id'], $this->apiship_params['shipping_apiship_gr_select']));

			$cost = round($this->currency->convert($product['price'], $this->apiship_params['shipping_apiship_rub_select'], $this->config->get('config_currency')), 2); 

			if ($length==0) $length = round($this->apiship_params['shipping_apiship_parcel_length']);
			if ($width==0) $width = round($this->apiship_params['shipping_apiship_parcel_width']);
			if ($height==0) $height = round($this->apiship_params['shipping_apiship_parcel_height']);
			if ($weight==0) $weight = round($this->apiship_params['shipping_apiship_parcel_weight']);
			
			if ($length==0) $length = 1;
			if ($width==0) $width = 1;
			if ($height==0) $height = 1;
			if ($weight==0) $weight = 1;


			$items[] = [
				'articul' => $product['model'],
				'description' => $product['name'],
				'quantity' => intval($product['quantity']),
				'weight' => $weight,
				'height' => $height,
				'length' => $length,
				'width' => $width,
				'cost' => $cost				
			];

			for($i=0;$i<$product['quantity'];$i++) {
				$total_weight = $total_weight + $weight;
				$total_cost = $total_cost + $cost;

				$item_ar = [$length, $width, $height];
				rsort($item_ar);

				if ($item_ar[0] > $total_length) $total_length = $item_ar[0];
				if ($item_ar[1] > $total_width) $total_width = $item_ar[1];
				
				$total_height = $total_height + $item_ar[2];
			}
		}

		return [
			'items' => $items,
			'total_length' => $total_length,
			'total_width' => $total_width,
			'total_height' => $total_height,
			'total_weight' => $total_weight,
			'total_cost' => $total_cost
		];
	}

	private function get_pickup_id($provider) {
		if (isset($this->apiship_params['shipping_apiship_provider'][$provider]['id'])) return $this->apiship_params['shipping_apiship_provider'][$provider]['id'];
		return '';
	}

	public function format_cost($cost) {	
		return round($cost, 2);
	}

	public function format_weight($weight) {	
		return round($weight);
	}

}