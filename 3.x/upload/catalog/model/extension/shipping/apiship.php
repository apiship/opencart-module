<?php 
require_once DIR_SYSTEM . 'library/apiship/apiship.php';

class ModelExtensionShippingApiship extends Model {    

	private $apiship;
	private $apiship_params;


	public function __construct($params) {
		parent::__construct($params);
		$this->load->language('extension/shipping/apiship');

		$this->apiship_params = [
			'shipping_apiship_rub_select' => $this->config->get('shipping_apiship_rub_select'),
			'shipping_apiship_gr_select' => $this->config->get('shipping_apiship_gr_select'),
			'shipping_apiship_cm_select' => $this->config->get('shipping_apiship_cm_select'),
			'shipping_apiship_token' => $this->config->get('shipping_apiship_token'),
	
			'shipping_apiship_contact_organization' => $this->config->get('shipping_apiship_contact_organization'),
			'shipping_apiship_contact_name' => $this->config->get('shipping_apiship_contact_name'),
			'shipping_apiship_contact_phone' => $this->config->get('shipping_apiship_contact_phone'),
			'shipping_apiship_contact_email' => $this->config->get('shipping_apiship_contact_email'),
	
			'shipping_apiship_sending_country_code' => $this->config->get('shipping_apiship_sending_country_code'),
			'shipping_apiship_sending_region' => $this->config->get('shipping_apiship_sending_region'),
			'shipping_apiship_sending_city' => $this->config->get('shipping_apiship_sending_city'),
			'shipping_apiship_sending_street' => $this->config->get('shipping_apiship_sending_street'),
			'shipping_apiship_sending_house' => $this->config->get('shipping_apiship_sending_house'),
			'shipping_apiship_sending_block' => $this->config->get('shipping_apiship_sending_block'),
			'shipping_apiship_sending_office' => $this->config->get('shipping_apiship_sending_office'),
		
			'shipping_apiship_parcel_length' => $this->config->get('shipping_apiship_parcel_length'),
			'shipping_apiship_parcel_width' => $this->config->get('shipping_apiship_parcel_width'),
			'shipping_apiship_parcel_height' => $this->config->get('shipping_apiship_parcel_height'),
			'shipping_apiship_parcel_weight' => $this->config->get('shipping_apiship_parcel_weight'),

			'shipping_apiship_place_length' => $this->config->get('shipping_apiship_place_length'),
			'shipping_apiship_place_width' => $this->config->get('shipping_apiship_place_width'),
			'shipping_apiship_place_height' => $this->config->get('shipping_apiship_place_height'),
			'shipping_apiship_place_weight' => $this->config->get('shipping_apiship_place_weight'),
	
			'shipping_apiship_provider' => $this->config->get('shipping_apiship_provider'),
			'shipping_apiship_mapping_status' => $this->config->get('shipping_apiship_mapping_status'),
	
			'shipping_apiship_paid_orders' => $this->config->get('shipping_apiship_paid_orders'),
	
			'shipping_apiship_sort_order' => $this->config->get('shipping_apiship_sort_order'),
	
			'shipping_apiship_tax_class_id' => $this->config->get('shipping_apiship_tax_class_id'),
	
			'shipping_apiship_export_status' => $this->config->get('shipping_apiship_export_status'),
			'shipping_apiship_cancel_export_status' => $this->config->get('shipping_apiship_cancel_export_status'),
	
			'shipping_apiship_mode' => $this->config->get('shipping_apiship_mode'),
			
			'shipping_apiship_prefix' => $this->config->get('shipping_apiship_prefix'),
	
			'shipping_apiship_title' => $this->config->get('shipping_apiship_title'),
			'shipping_apiship_template' => html_entity_decode($this->config->get('shipping_apiship_template')),
			'shipping_apiship_custom_code' => $this->config->get('shipping_apiship_custom_code'),
			'shipping_apiship_include_fees' => $this->config->get('shipping_apiship_include_fees') ? 'true' : 'false',
			'shipping_apiship_group_points' => $this->config->get('shipping_apiship_group_points'),
	
			'shipping_apiship_title_days' => $this->language->get('shipping_apiship_title_days'),
			'shipping_apiship_error_timeout' => $this->language->get('shipping_apiship_error_timeout'),
			'shipping_apiship_success_export_message' => $this->language->get('shipping_apiship_success_export_message'),
			'shipping_apiship_success_cancel_message' => $this->language->get('shipping_apiship_success_cancel_message'),
			'shipping_apiship_error_params' => $this->language->get('shipping_apiship_error_params'),
			'shipping_apiship_error_calculator' => $this->language->get('shipping_apiship_error_calculator'),
			'shipping_apiship_error_no_export_order' => $this->language->get('shipping_apiship_error_no_export_order'),
			'shipping_apiship_no_shipping' => $this->language->get('shipping_apiship_no_shipping'),
			'shipping_apiship_change_order_status_message' => $this->language->get('shipping_apiship_change_order_status_message'),	
			'shipping_apiship_error_select_city' => $this->language->get('shipping_apiship_error_select_city'),		
			'shipping_apiship_status' => $this->config->get('shipping_apiship_status'),
			
			'shipping_apiship_data' => [],
			'shipping_apiship_comment' => [],
			'shipping_apiship_providers' => []
		];


		if (!isset($this->apiship_params['shipping_apiship_paid_orders'])) $this->apiship_params['shipping_apiship_paid_orders'] = [];

		$this->apiship = new Apiship($this->registry, $this->apiship_params, $this->log);

	}

	private function setData($key, $value) {

		if(!isset($value)) return;

		if(session_id() == '') {
		    session_start();
		}		
		$_SESSION['shipping_apiship'][$key] = $value;

	}

	private function getData($key) {

		if(session_id() == '') {
		    session_start();
		}

		if (isset($_SESSION['shipping_apiship'][$key])) return $_SESSION['shipping_apiship'][$key];
		return null;
	}

 	private function apiship_point($id) {		
		$data = $this->apiship->apiship_point_by_params(['id=' . $id]);
		if (isset($data[0])) return $data[0]; else return [];
	}
	
	public function get_providers() {
		if ($this->apiship_params['shipping_apiship_status'] != 1) return [];

		$providers_name = [];
		$providers = $this->apiship->apiship_providers();
		if (isset($providers['message'])) return $providers_name;

		foreach($providers as $provider) {
			$providers_name[$provider['key']] = $provider['name'];
		}

		return $providers_name;
	}

	private function get_provider_name($provider_key) {

		if (count($this->apiship_params['shipping_apiship_providers'])==0)
			$this->apiship_params['shipping_apiship_providers'] = $this->apiship->apiship_providers();

		$providers = $this->apiship_params['shipping_apiship_providers'];

		foreach($providers as $provider) {
			if ($provider['key'] == $provider_key) return $provider['name'];
		}
		return '';
	}

	public function get_last_tracing_id() {
		return $this->getData('shipping_apiship_last_tracing_id');
	}

  	public function get_quote_list($address, $full_list = false) {

		$this->load->language('extension/shipping/apiship');
		if ($this->apiship_params['shipping_apiship_status'] == 1) {
			$status = true;
		} else {
			$status = false;
		}

		$quote_data = [];
		
		$start_points = [];
		$select_points = [];

		$select_point = [];
		if (isset($this->session->data['shipping_apiship'])) $select_point = $this->session->data['shipping_apiship'];

		$region = $address['zone'];
		$city = trim($address['city']);
		$postcode = trim($address['postcode']);
		$ext_address = trim($address['address_1']);

		$place_params['length'] = $this->apiship_params['shipping_apiship_place_length'];
		$place_params['width'] = $this->apiship_params['shipping_apiship_place_width'];
		$place_params['height'] = $this->apiship_params['shipping_apiship_place_height'];
		$place_params['weight'] = $this->apiship_params['shipping_apiship_place_weight'];

		$apiship_calculator_data = $this->apiship->apiship_calculator($region,$city,$postcode,$ext_address,[],$this->cart->getProducts(), $place_params);
		$data = $apiship_calculator_data['body'];

		$this->setData('shipping_apiship_last_tracing_id',$apiship_calculator_data['x-tracing-id']);
		$this->setData('shipping_apiship_region',$region);
		$this->setData('shipping_apiship_city',$city);
		$this->setData('shipping_apiship_postcode',$postcode);
		$this->setData('shipping_apiship_ext_address',$ext_address);

		if ($full_list == false)
		{
			if (isset($data['deliveryToPoint'])) $providers = $data['deliveryToPoint']; else $providers = [];
			foreach($providers as $provider) {	
				if (isset($provider['tariffs'])) $tariffs = $provider['tariffs']; else $tariffs = [];			
				foreach($tariffs as $tariff) {
					foreach($tariff['pointIds'] as $point_id) {
						if (!in_array($this->get_pickup_type($provider['providerKey']), $tariff['pickupTypes'])) continue;
						
						$key = 'point_' . $provider['providerKey'] . '_' . $tariff['tariffId'] . '_' . 'error';
						if (empty($start_points[$provider['providerKey']])) { 
							$start_points[$provider['providerKey']] = ['key' => $key, 'tariffName' => $tariff['tariffName'], 'daysMin' => $tariff['daysMin'], 'daysMax' => $tariff['daysMax'], 'deliveryCost' => $tariff['deliveryCost']];
						} elseif ($tariff['deliveryCost'] < $start_points[$provider['providerKey']]['deliveryCost']) {
							if (strpos($start_points[$provider['providerKey']]['key'],'error')!==false)
								$start_points[$provider['providerKey']] = ['key' => $key, 'tariffName' => $tariff['tariffName'], 'daysMin' => $tariff['daysMin'], 'daysMax' => $tariff['daysMax'], 'deliveryCost' => $tariff['deliveryCost']];
						}

						$key = 'point_' . $provider['providerKey'] . '_' . $tariff['tariffId'] . '_' . $point_id;
						if (isset($select_point[$provider['providerKey']]))
						if ('apiship.' . $key == $select_point[$provider['providerKey']]['code'])  {
							$start_points[$provider['providerKey']] = ['key' => $key, 'tariffName' => $tariff['tariffName'], 'daysMin' => $tariff['daysMin'], 'daysMax' => $tariff['daysMax'], 'deliveryCost' => $tariff['deliveryCost']];															
						}

					}

				}				
	
			}


			$this->apiship->toLog('get_quote_list debug', [
				'start_points' => $start_points,
				'session' => $this->session->data,
				'select_point' => $select_point
			]);

			if ($this->apiship_params['shipping_apiship_group_points']) {
				// все ПВЗ на одной карте

				usort($start_points, function($a, $b) {
				    return $a['deliveryCost'] > $b['deliveryCost'];
				});

				foreach($start_points as $provider_key => $element) {
					
					$shipping_apiship_last_select_code = $this->getData('shipping_apiship_last_select_code');
					if (isset($shipping_apiship_last_select_code))
					if ('apiship.' . $element['key'] == $shipping_apiship_last_select_code)  {

						$parce_code = $this->apiship->parce_code('apiship.' . $element['key']);
						
						if ($parce_code['point_id'] != 'error') {
							$point = $this->apiship_point($parce_code['point_id']);
							$title = $this->get_title('point', $point['providerKey'], $element['tariffName'], $point['name'], $this->apiship->get_address($point), $element['daysMin'], $element['daysMax']);
						}
						else
							$title = $this->language->get('shipping_apiship_point');
									
						$quote_data[$element['key']] = [
							'code'         => 'apiship.' . $element['key'],
							'title'        => $title, 
							'cost'         => $element['deliveryCost'],
							'tax_class_id' => $this->apiship_params['shipping_apiship_tax_class_id'],
							'text'         => $this->currency->format($this->tax->calculate($element['deliveryCost'], $this->apiship_params['shipping_apiship_tax_class_id'], $this->config->get('config_tax')), $this->apiship_params['shipping_apiship_rub_select'])
						];

						break;
					}
				}


				if (empty($quote_data)) {
					foreach($start_points as $provider_key => $element) {
						
						$parce_code = $this->apiship->parce_code('apiship.' . $element['key']);
						
						if ($parce_code['point_id'] != 'error') {
							$point = $this->apiship_point($parce_code['point_id']);
							$title = $this->get_title('point', $point['providerKey'], $element['tariffName'], $point['name'], $this->apiship->get_address($point), $element['daysMin'], $element['daysMax']);
						}
						else
							$title = $this->language->get('shipping_apiship_point');
									
						$quote_data[$element['key']] = [
							'code'         => 'apiship.' . $element['key'],
							'title'        => $title, 
							'cost'         => $element['deliveryCost'],
							'tax_class_id' => $this->apiship_params['shipping_apiship_tax_class_id'],
							'text'         => $this->currency->format($this->tax->calculate($element['deliveryCost'], $this->apiship_params['shipping_apiship_tax_class_id'], $this->config->get('config_tax')), $this->apiship_params['shipping_apiship_rub_select'])
						];

						break;
						
					}
				}
			 
			}
			else {

				// Для каждой СД своя карта
				foreach($start_points as $provider_key => $element) {
	
					$parce_code = $this->apiship->parce_code('apiship.' . $element['key']);
					
					if ($parce_code['point_id'] != 'error') {
						$point = $this->apiship_point($parce_code['point_id']);
						$title = $this->get_title('point', $point['providerKey'], $element['tariffName'], $point['name'], $this->apiship->get_address($point), $element['daysMin'], $element['daysMax']);
					}
					else
						$title = $this->language->get('shipping_apiship_point');
								
					$quote_data[$element['key']] = [
						'code'         => 'apiship.' . $element['key'],
						'title'        => $title, 
						'cost'         => $element['deliveryCost'],
						'tax_class_id' => $this->apiship_params['shipping_apiship_tax_class_id'],
						'text'         => $this->currency->format($this->tax->calculate($element['deliveryCost'], $this->apiship_params['shipping_apiship_tax_class_id'], $this->config->get('config_tax')), $this->apiship_params['shipping_apiship_rub_select'])
					];
	
				}
			}			


		}
		else
		{
			// список ПВЗ в админку
			$points_data = $this->get_points_array($region,$city,$postcode,$ext_address);
			if ($points_data['error'] == 'no_error') {
				usort($points_data['points'], function($a, $b) {
				    return $a['title'] > $b['title'];
				});
				foreach($points_data['points'] as $point) {					
					$parce_code = $this->apiship->parce_code($point['code']);
					$quote_data[$parce_code['short_code']] = [
						'code'         => $point['code'],
						'title'        => $point['title'], 
						'cost'         => $point['cost'],
						'tax_class_id' => $this->apiship_params['shipping_apiship_tax_class_id'],
						'text'         => $point['text']
					];
				}
			}
				
		}

		$method_data = array();

		if (isset($data['deliveryToDoor'])) $providers = $data['deliveryToDoor']; else $providers = [];
		foreach($providers as $provider) {
			if (isset($provider['tariffs'])) $tariffs = $provider['tariffs']; else $tariffs = [];
			foreach($tariffs as $tariff) {
				if (!in_array($this->get_pickup_type($provider['providerKey']), $tariff['pickupTypes'])) continue;

				$key = 'door_' . $provider['providerKey'] . '_' . $tariff['tariffId'];
				$quote_data[$key] = [
					'code'         => 'apiship.' . $key,
					'title'        => $this->get_title('door', $provider['providerKey'], $tariff['tariffName'], '', '', $tariff['daysMin'], $tariff['daysMax']), 
					'cost'         => $tariff['deliveryCost'],
					'tax_class_id' => $this->apiship_params['shipping_apiship_tax_class_id'],
					'text'         => $this->currency->format($this->tax->calculate($tariff['deliveryCost'], $this->apiship_params['shipping_apiship_tax_class_id'], $this->config->get('config_tax')), $this->apiship_params['shipping_apiship_rub_select'])
				];

			}

		}

		if(empty($quote_data)) {
			// нет данных, потому что таймаут
			$title = $this->apiship_params['shipping_apiship_error_timeout'];

			// нет данных, потому что ошибка
			if (isset($data['message'])) {
				//$title = $this->apiship_params['shipping_apiship_error_calculator'];
				$title = $data['message'];
				if (isset($data['errors'])) $errors = $data['errors']; else $errors = [];
				foreach($errors as $error) {
					$title = $title . ", " . $error['message'];
				}
			}
			
			// нет данных, потому что поиск не нашел
			if (isset($data['deliveryToPoint']) || isset($data['deliveryToDoor'])) $title = sprintf($this->apiship_params['shipping_apiship_no_shipping'], $city .', '.$region); 

			$quote_data['error'] = array(
				'code'         => 'apiship.error',
				'title'        => $title,
				'cost'         => 0,
				'tax_class_id' => $this->apiship_params['shipping_apiship_tax_class_id'],
				'text'         => $this->currency->format(0, $this->apiship_params['shipping_apiship_rub_select'])
			);

		}

      	$method_data = array(
	      	'code'       => 'apiship',
	      	'title'      => $this->apiship_params['shipping_apiship_title'], 
	      	'quote'      => $quote_data,
			'sort_order' => $this->apiship_params['shipping_apiship_sort_order'],
        		'error'      => false
      	);

		return $method_data;


  	}

	private function get_title($type, $providerKey, $tariffName, $pointName = '', $pointAddress ='', $daysMin, $daysMax ) {
		$template = $this->apiship_params['shipping_apiship_template'];

		$type_name = '';
		if ($type == 'door') $type_name = $this->language->get('shipping_apiship_door');
		if ($type == 'point') $type_name = $this->language->get('shipping_apiship_point');
	
		$time = $daysMin . ' - ' . $daysMax . $this->apiship_params['shipping_apiship_title_days'];
		if ($daysMin == $daysMax) $time = $daysMin . $this->apiship_params['shipping_apiship_title_days'];
		if ($daysMin == 0) $time = '';

		$template_ar = [
			'%type' => $type_name,
			'%company' => $this->get_provider_name($providerKey),
			'%name' => $pointName,
			'%address' => $pointAddress,
			'%tariff' => $tariffName,
			'%time' => $time
		];

		foreach($template_ar as $teplate_key => $teplate_value) {
			$template = str_replace($teplate_key, $teplate_value, $template);
		}

		return $template;

	}

	private function get_points_array($region, $city, $postcode, $ext_address, $provider = []) {
		$this->load->language('extension/shipping/apiship');

		$this->apiship->toLog('get_points_array', [ 
				'region' => $region,
				'city' => $city,
				'postcode' => $postcode,
				'ext_address' => $ext_address, 
				'provider' => $provider
		]);

		$data_points = [];
		$all_points = [];
		$apiship_providers = [];

		$products = $this->cart->getProducts();
		if (empty($products)) return ['error' => 'no_products','points' => []];
		if (empty($city)) return ['error' => 'no_city','points' => []];


		$apiship_providers_data = $this->apiship->apiship_providers();
		foreach($apiship_providers_data as $apiship_provider) {
			$apiship_providers[$apiship_provider['key']] = $apiship_provider['name'];
		}

		$apiship_point_types = ['Пункт выдачи заказа', 'Постамат', 'Отделение Почты России', 'Терминал'];

		$apiship_calculator_data = $this->apiship->apiship_calculator($region,$city,$postcode,$ext_address,$provider,$products);
			$data = $apiship_calculator_data['body'];
			$points_ids = [];
			if (isset($data['deliveryToPoint'])) $providers = $data['deliveryToPoint']; else $providers = [];
			foreach($providers as $provider) {
				if (isset($provider['tariffs'])) $tariffs = $provider['tariffs']; else $tariffs = [];			
				foreach($tariffs as $tariff) {
					if (!in_array($this->get_pickup_type($provider['providerKey']), $tariff['pickupTypes'])) continue;
					foreach($tariff['pointIds'] as $point_id) {
						if (!in_array($point_id,$points_ids)) $points_ids[] = $point_id;
					}
				}
			}

			$points = $this->apiship->apiship_points($points_ids);
			foreach($points as $point)
			{

				$description = str_replace(array("\r\n", "\r", "\n"), '',  strip_tags($point['description']));

				$data_points[$point['id']] = [
					'address' => $this->apiship->get_address($point),
					'note' => $description,
					'lon' => $point['lng'],
					'lat' => $point['lat'],
					'name' => $point['name'],

					'city' => $point['city'],
					'tax_class_id' => $this->apiship_params['shipping_apiship_tax_class_id'],

					'type' => $point['type'],
					'phone' => $point['phone'],
					'workTime' => $point['timetable'],


				];
				

			}


			if (isset($data['deliveryToPoint'])) $providers = $data['deliveryToPoint']; else $providers = [];
			foreach($providers as $provider) {
				if (isset($provider['tariffs'])) $tariffs = $provider['tariffs']; else $tariffs = [];					
				foreach($tariffs as $tariff) {
					foreach($tariff['pointIds'] as $point_id) {
						if (!in_array($this->get_pickup_type($provider['providerKey']), $tariff['pickupTypes'])) continue;

						$code = 'point_' . $provider['providerKey'] . '_' . $tariff['tariffId'] . '_' . $point_id;
						if (!isset($data_points[$point_id])) continue; 
						$point = $data_points[$point_id];
						$cost = $tariff['deliveryCost'];

						$all_points[] = [
							'lon'	=> $point['lon'],
							'lat'	=> $point['lat'],
							'code' => 'apiship.' . $code,

							'tariff' => $tariff['tariffName'],
							'daysMin' => $tariff['daysMin'],							
							'daysMax' => $tariff['daysMax'],

							'text' => $this->currency->format($this->tax->calculate($cost, $this->apiship_params['shipping_apiship_tax_class_id'], $this->config->get('config_tax')), $this->apiship_params['shipping_apiship_rub_select']),
							'cost' => round($cost),						

							'title' => $this->get_title('point', $provider['providerKey'], $tariff['tariffName'], $point['name'], $point['address'], $tariff['daysMin'], $tariff['daysMax']),
				
							'type' => $apiship_point_types[$point['type']-1],
							'provider' => $apiship_providers[$provider['providerKey']],
							'provider_key' => $provider['providerKey'],

							//'phones'	=> $point['phone'],
							//'workTime'	=> $point['workTime'],
							
						];
					}
				}
			}


		usort($all_points, function($a, $b) {
			    return $a['cost'] > $b['cost'];
		});

		return ['error' => 'no_error','points' => $all_points];
		
	}

	public function get_point() {
		if (isset($this->request->get['search'])) 
		{
			$search = $this->request->get['search'];
		}
		else
			return [];

		if (isset($this->request->get['type'])) 
		{
			$type = $this->request->get['type'];
		}
		else
			return [];

		$search_list = [
			'code%',
			'street%',
			'city%',
			'community%',
			'region%',
			'area%',
			'house%',
			'block%'
		];

		$points_data = [];
		$point_data = [];
		$operations = '[1,3]';

		foreach($search_list as $search_item) {
			$points = $this->apiship->apiship_point_by_params([
				'providerKey='.$type,
				'availableOperation='. $operations,
				$search_item.$search
			]);
	
			foreach($points as $point) {
				if (!in_array($point['id'],$point_data)) {
					$point_data[] = $point['id'];
					$points_data[] = ['id' => $point['id'],'text' => $this->apiship->get_address($point), 'code' => $point['code']];
				}
			}

		}

		usort($points_data, function($a, $b) {
		    return $a['text'] > $b['text'];
		});
		
		return $points_data;
		
	}

	public function get_points() {

		if (isset($this->request->post['code'])) $code = $this->request->post['code']; else $code = '';

		$region = $this->getData('shipping_apiship_region');
		$city = $this->getData('shipping_apiship_city');
		$postcode = $this->getData('shipping_apiship_postcode');
		$ext_address = $this->getData('shipping_apiship_ext_address');

		$this->apiship->toLog('get_points', [
				'code' => $code,
				'region' => $region,
				'city' => $city,
				'postcode' => $postcode,
				'ext_address' => $ext_address
		]);

		$points = [];
		$parce_code = $this->apiship->parce_code($code);
		$provider = [$parce_code['provider']];
		if ($this->apiship_params['shipping_apiship_group_points']) $provider = [];
		
		$points = $this->get_points_array($region, $city, $postcode, $ext_address, $provider);

		echo json_encode($points);

	}

	public function set_point() {
		if (!isset($this->request->post['shipping_apiship_point'])) {
			echo json_encode(['error' => $this->apiship_params['shipping_apiship_error_params']]); 
			exit;
		}

		$code = $this->request->post['shipping_apiship_point'];
		$parce_code = $this->apiship->parce_code($code);

		$delivery_type = $parce_code['delivery_type'];
		
		$region = $this->getData('shipping_apiship_region');
		$city = $this->getData('shipping_apiship_city');
		$postcode = $this->getData('shipping_apiship_postcode');
		$ext_address = $this->getData('shipping_apiship_ext_address');;

		$cost = -1;  
		$postcode = ''; 
  		$address1 = '';

		$apiship_calculator_data = $this->apiship->apiship_calculator($region,$city,$postcode,$ext_address,[],$this->cart->getProducts());
		$data = $apiship_calculator_data['body'];

		if (isset($data['deliveryToPoint'])) $providers = $data['deliveryToPoint']; else $providers = [];
		foreach($providers as $provider) {
			if (isset($provider['tariffs'])) $tariffs = $provider['tariffs']; else $tariffs = [];				
			foreach($tariffs as $tariff) {
				foreach($tariff['pointIds'] as $point_id) {
					if (!in_array($this->get_pickup_type($provider['providerKey']), $tariff['pickupTypes'])) continue;
					$key = $delivery_type. '_' . $provider['providerKey'] . '_' . $tariff['tariffId'] . '_' . $point_id;
					if ('apiship.' . $key == $code) {
						$cost = $tariff['deliveryCost'];
						$point = $this->apiship_point($point_id);

						$postcode = $point['postIndex'];
						$address1 = $this->apiship->get_address($point);
						$title = $this->get_title('point', $provider['providerKey'], $tariff['tariffName'], $point['name'], $point['address'], $tariff['daysMin'], $tariff['daysMax']);
					}
				}
			}
		}	

		if ($cost == -1) {
			echo json_encode(['error' => $this->apiship_params['shipping_apiship_error_calculator']]); 
			exit;
		}

		$shipping_apiship = [
				'code'    	   => $code,
				'title'        => $title,
				'cost'         => $cost,
				'tax_class_id' => $this->apiship_params['shipping_apiship_tax_class_id'],
				'text'         => $this->currency->format($this->tax->calculate($cost, $this->apiship_params['shipping_apiship_tax_class_id'], $this->config->get('config_tax')), $this->apiship_params['shipping_apiship_rub_select'])
		];

		$this->session->data['shipping_apiship'][$parce_code['provider']] = $shipping_apiship;
		$this->session->data['shipping_methods']['apiship']['quote'][$parce_code['short_code']] = $shipping_apiship;
		$this->setData('shipping_apiship_last_select_code', $code);

		$shipping_apiship['postcode'] = $postcode;
		$shipping_apiship['address1'] = $address1;


		$this->apiship->toLog('set_point', [
			'post' => $this->request->post['shipping_apiship_point'],
			'shipping_apiship' => $shipping_apiship
		]);

		echo json_encode($shipping_apiship); 
			
		exit;
		
	}

  	public function getQuote($address) {

		if (isset($this->session->data['api_id']))
		{
			 return $this->get_quote_list($address, true);
		}

		return $this->get_quote_list($address);

  	}

	public function get_label() {
		if (isset($this->request->post['id'])) $id = $this->request->post['id']; else return ['error' => $this->apiship_params['shipping_apiship_error_params']];

		$text_error = '';
		$apiship_orders = [];
		$oc_orders = explode(',',$id);
		foreach($oc_orders as $oc_order_id) {
			$apiship_order = $this->get_apiship_order_by_oc_number($oc_order_id);
			if (isset($apiship_order['apiship_order_id'])) 
				$apiship_orders[] = $apiship_order['apiship_order_id'];
			else
				$text_error = $text_error . sprintf($this->apiship_params['shipping_apiship_error_no_export_order'], $oc_order_id) . '; '; 
		}

		$labels = [];
		if (empty($apiship_orders)) return ['labels' => $labels];

		$data = $this->apiship->apiship_labels($apiship_orders);
		
		if (isset($data['body']['url'])) $labels[] = ['name' => 'label_'.$id.'.pdf', 'url' => $data['body']['url']];
		if (isset($data['body']['failedOrders']))
		foreach($data['body']['failedOrders'] as $orders) {
			$text_error = $text_error . $orders['orderId'] . ' - ' . $orders['message']. '; '; 
		}		

		if (empty($text_error))
			return ['labels' => $labels];
		else
			return ['labels' => $labels, 'error' => $text_error];

	}

	public function get_waybill() {
		if (isset($this->request->post['id'])) $id = $this->request->post['id']; else return ['error' => $this->apiship_params['shipping_apiship_error_params']];

		$text_error = '';
		$apiship_orders = [];
		$oc_orders = explode(',',$id);
		foreach($oc_orders as $oc_order_id) {
			$apiship_order = $this->get_apiship_order_by_oc_number($oc_order_id);
			if (isset($apiship_order['apiship_order_id'])) 
				$apiship_orders[] = $apiship_order['apiship_order_id'];
			else
				$text_error = $text_error . sprintf($this->apiship_params['shipping_apiship_error_no_export_order'], $oc_order_id) . '; '; 
		}

		$waybills = [];
		if (empty($apiship_orders)) return ['waybills' => $waybills];

		$data = $this->apiship->apiship_waybills($apiship_orders);			

		if (isset($data['body']['waybillItems'])) {
			foreach($data['body']['waybillItems'] as $waybill) {
				$waybills[] = ['name' => $waybill['providerKey'].'_'.$id.'.pdf', 'url' => $waybill['file']];
			}
		}

		if (isset($data['body']['failedOrders']))
		foreach($data['body']['failedOrders'] as $orders) {
			$text_error = $text_error . $orders['orderId'] . ' - ' . $orders['message']. '; '; 
		}		

		if (empty($text_error))
			return ['waybills' => $waybills];
		else
			return ['waybills' => $waybills, 'error' => $text_error];

	}

	public function export_order() {

		if (!isset($this->session->data['user_id'])) return array('error' => $this->apiship_params['shipping_apiship_error_params']);

		if (isset($this->request->get['id'])) $order_id = $this->request->get['id']; else return array('error' => $this->apiship_params['shipping_apiship_error_params']);
		if (isset($this->request->get['shipping_apiship_pickup_type'])) $shipping_apiship_pickup_type = $this->request->get['shipping_apiship_pickup_type']; else return array('error' => $this->apiship_params['shipping_apiship_error_params']);
		if (isset($this->request->get['shipping_apiship_place_length'])) $shipping_apiship_place_length = $this->request->get['shipping_apiship_place_length']; else return array('error' => $this->apiship_params['shipping_apiship_error_params']);
		if (isset($this->request->get['shipping_apiship_place_width'])) $shipping_apiship_place_width = $this->request->get['shipping_apiship_place_width']; else return array('error' => $this->apiship_params['shipping_apiship_error_params']);
		if (isset($this->request->get['shipping_apiship_place_height'])) $shipping_apiship_place_height = $this->request->get['shipping_apiship_place_height']; else return array('error' => $this->apiship_params['shipping_apiship_error_params']);		
		if (isset($this->request->get['shipping_apiship_place_weight'])) $shipping_apiship_place_weight = $this->request->get['shipping_apiship_place_weight']; else return array('error' => $this->apiship_params['shipping_apiship_error_params']);		

		$text = '';
	
		$this->load->model('checkout/order');
		$order_products = $this->model_checkout_order->getOrderProducts($order_id);

		$calculate_data = $this->apiship->calculate_places($order_products);
		$items = $calculate_data['items']; 
		$total_length = $shipping_apiship_place_length; //$calculate_data['total_length'];
		$total_width = $shipping_apiship_place_width; 	//$calculate_data['total_width'];
		$total_height = $shipping_apiship_place_height; //$calculate_data['total_height'];
		$total_weight = $shipping_apiship_place_weight; //$calculate_data['total_weight'];
		$total_cost = $calculate_data['total_cost'];

		$order = $this->model_checkout_order->getOrder($order_id);

		$shipping_code = $order['shipping_code'];
		if (strpos($shipping_code, 'apiship')===false) {
			$text = $this->apiship_params['shipping_apiship_error_params'];
			return array('text' => $text);
		}

		$parce_code = $this->apiship->parce_code($shipping_code);
		$delivery_type = $parce_code['delivery_type'];
		$provider = $parce_code['provider'];
		$tariff_id = $parce_code['tariff_id'];
		$point_id = $parce_code['point_id'];

		if ($delivery_type=='point')
		{
			$point = $this->apiship_point($point_id);
			$recipientAddressString = $this->apiship->get_address($point);
		} else
			$recipientAddressString = implode(', ', [$order['shipping_postcode'],$order['shipping_country'],$order['shipping_zone'],$order['shipping_city'],$order['shipping_address_1']]);

		$order_params['orderId'] = $this->apiship_params['shipping_apiship_prefix']. $order['order_id'];
		$order_params['orderWeight'] = $total_weight;
		$order_params['orderProviderKey'] = $provider;
		$order_params['orderPickupType'] = $shipping_apiship_pickup_type; 
		$order_params['orderDeliveryType'] = ($delivery_type=='point')?2:1;
		$order_params['orderTariffId'] = $tariff_id;
		$order_params['orderPointOutId'] = $point_id;

		$this->load->model('account/order');
		$totals = $this->model_account_order->getOrderTotals($order_id);

		foreach ($totals as $total) {
			$order_totals[$total['code']] = $total['value'];
		} 

		$order_params['costAssessedCost'] = $this->apiship->format_cost($order_totals['total'] - $order_totals['shipping']);

		$order_params['costCodCost'] = in_array($order['order_status_id'], $this->apiship_params['shipping_apiship_paid_orders']) ? 0 : $this->apiship->format_cost($order_totals['total']);
		$order_params['costDeliveryCost'] = in_array($order['order_status_id'], $this->apiship_params['shipping_apiship_paid_orders']) ? 0 : $this->apiship->format_cost($order_totals['shipping']);
		$order_params['sub_total_cost'] = $total_cost;


		$order_params['recipientPhone'] =  $order['telephone'];  
		$order_params['recipientContactName'] = $order['firstname'] . ' ' . $order['lastname'];
		//$order_params['recipientCountryCode'] = $order['shipping_iso_code_2'];
		$order_params['recipientAddressString'] = $recipientAddressString;
		$order_params['recipientComment'] = $order['comment'];

		$order_params['placeHeight'] = $total_height;
		$order_params['placeLength'] = $total_length;
		$order_params['placeWidth'] = $total_width;
		$order_params['placeWeight'] = $total_weight;
		$order_params['placeCalculateWeight'] = $calculate_data['total_weight'];

		$order_params['items'] = $items;
		
		$output_data = $this->apiship->apiship_order($order_params)['body'];

		if (isset($output_data['orderId'])) {
	
			$apiship_order_status = $this->apiship->apiship_order_status($output_data['orderId']);	
			$status = $this->get_status($apiship_order_status);
			$status_name = $status['name'];

			if (isset($output_data['providerNumber'])) $track_number = $output_data['providerNumber']; else $track_number = '';

			$text = sprintf($this->apiship_params['shipping_apiship_success_export_message'], $output_data['orderId'], $track_number, $status_name);
			$this->model_checkout_order->addOrderHistory($order['order_id'], $this->apiship_params['shipping_apiship_export_status'], $text, FALSE);
			
			$this->bind_apiship_order($order['order_id'], $output_data['orderId']);
			$this->change_order_str_field($order['order_id'], 'tracking', $track_number);

			return array('success' => $text);
		} else {

			if (isset($output_data['message'])) {
				$text = $output_data['message'] . PHP_EOL;
				if (isset($output_data['errors'])) $errors = $output_data['errors']; else $errors = [];
				foreach($errors as $error) {
					$text = $text . $error['message'] . PHP_EOL;
				}
			}				
			else
				$text = $this->apiship_params['shipping_apiship_error_timeout'];

			$this->log->write('shipping_apiship export error ' . print_r($output_data,1));
			return array('error' => $text);
		}	
			

		

	}

	public function cancel_order() {

		if (!isset($this->session->data['user_id'])) return array('error' => $this->apiship_params['shipping_apiship_error_params']);
		if (isset($this->request->get['id'])) $order_id = $this->request->get['id']; else return array('error' => $this->apiship_params['shipping_apiship_error_params']);
	
		$apiship_order = $this->get_apiship_order_by_oc_number($order_id);
		$output_data = $this->apiship->apiship_cancel_order($apiship_order['apiship_order_id'])['body'];

		$this->load->model('checkout/order');

		if (isset($output_data['orderId'])) {
			$text = sprintf($this->apiship_params['shipping_apiship_success_cancel_message'], $output_data['orderId']);
									
			$this->model_checkout_order->addOrderHistory($order_id, $this->apiship_params['shipping_apiship_cancel_export_status'], $text, FALSE);
			$this->delete_apiship_order($order_id);
			$this->change_order_str_field($order_id, 'tracking', '');

			return array('success' => $text);
		} else {

			if (isset($output_data['message'])) {
				$text = $output_data['message'] . PHP_EOL;
				if (isset($output_data['errors'])) $errors = $output_data['errors']; else $errors = [];
				foreach($errors as $error) {
					$text = $text . $error['message'] . PHP_EOL;
				}
			}				
			else 
				$text = $this->apiship_params['shipping_apiship_error_timeout'];

			$this->log->write('shipping_apiship cancel error ' . print_r($output_data,1));
			return array('error' => $text);
		}	
		
	}

	public function import_orders() {
		
		$ret_text = '';
		
		$file_name = DIR_DOWNLOAD . 'apiship.obj';

		if (file_exists($file_name) && filesize($file_name)>0) {

			$fp = fopen($file_name, "r+");
			if (flock($fp, LOCK_EX)) {
				$text = fread($fp, filesize($file_name));
				$data = unserialize($text);
			}
			
		}
		else {
			$fp = fopen($file_name, "w");
			flock($fp, LOCK_EX);

		}

		if (!isset($data)) {
			$data = [
				'last_import_date' => date("Y-m-d"),
				'last_status_change_date' => date("Y-m-d\T00:00:00+03:00", strtotime("-5 day")),
			];
		}


		$time = strtotime(date("Y-m-d H:i:s")) - strtotime($data['last_import_date']);

		if ($time > 60) {
		
			$apiship_orders_status = $this->apiship->apiship_orders_status($data['last_status_change_date']);
			foreach($apiship_orders_status as $apiship_order_status) {
	
				if (!isset($apiship_order_status['status']['created'])) {
					$this->apiship->toLog('shipping_apiship import_orders ', $apiship_orders_status, true);
					break;
				}

				if ($apiship_order_status['status']['created'] > $data['last_status_change_date']) $data['last_status_change_date'] = $apiship_order_status['status']['created'];


				// если заказа нет в opencart(не выгружен в apiship)
				$apiship_order = $this->get_apiship_order_by_apiship_number($apiship_order_status['orderInfo']['orderId']);				
				if (empty($apiship_order)) continue;
		
				$current_status_id = $apiship_order['status'];		
				$status = $this->get_status($apiship_order_status);
		
				$status_id = $status['id'];
				$status_name = $status['name'];
				$order_id = $apiship_order['oc_order_id'];
				$key = $status['key'];

				if ($current_status_id != $status_id) {


					$this->set_apiship_order_status($apiship_order['apiship_order_id'], $status_id);
					$text = sprintf($this->apiship_params['shipping_apiship_change_order_status_message'], $apiship_order['apiship_order_id'], $status_name);
		
					$this->load->model('checkout/order');
					$order = $this->model_checkout_order->getOrder($order_id);
		
					$order_new_status = $order['order_status_id'];
					$order_notify = false;
					$order_text = $text;

					if (isset($this->apiship_params['shipping_apiship_mapping_status'][$key])) {
						if ($this->apiship_params['shipping_apiship_mapping_status'][$key]['use']) {
							$order_new_status = $this->apiship_params['shipping_apiship_mapping_status'][$key]['order_status_id'];
							if (isset($this->apiship_params['shipping_apiship_mapping_status'][$key]['notify'])) $order_notify = true; else $order_notify = false;
							$order_text = '';
						}
					}
					
					$this->model_checkout_order->addOrderHistory($order_id, $order_new_status, $order_text, $order_notify);
					
					$this->log->write($text);

					$ret_text = $ret_text . $text . PHP_EOL . '<br>';
				}

				
			}

			$data['last_import_date'] = date("Y-m-d H:i:s");
		}
		else {
			echo "time_out ". $time;
			$this->log->write('import time_out');
			return;
		}

		// save_data

		ftruncate($fp, 0); 
 		rewind($fp);
    		fwrite($fp,serialize($data));
		fflush($fp);
		flock($fp, LOCK_UN);
		fclose($fp);

		echo $ret_text;
		die;
	}

	private function bind_apiship_order($oc_order_id, $apiship_order_id) {
		$this->db->query("INSERT ignore into `" . DB_PREFIX . "apiship_order` (oc_order_id, apiship_order_id) values (" . $oc_order_id . "," . $apiship_order_id . ")");
	}

	private function delete_apiship_order($oc_order_id) {
		$this->db->query("delete from `" . DB_PREFIX . "apiship_order` where oc_order_id=" . $oc_order_id);
	}

	private function get_apiship_order_by_oc_number($oc_order_id) {
		$query = $this->db->query("SELECT * from `" . DB_PREFIX . "apiship_order` where oc_order_id =" . $oc_order_id);
		return $query->row;
	}

	private function get_apiship_order_by_apiship_number($apiship_order_id) {
		$query = $this->db->query("SELECT * from `" . DB_PREFIX . "apiship_order` where apiship_order_id =" . $apiship_order_id);
		return $query->row;
	}

	private function get_apiship_order_status_by_key($key) {
		$query = $this->db->query("SELECT * from `" . DB_PREFIX . "apiship_order_status` WHERE `key` = '" . $key . "'");
		return $query->row;
	}

	private function insert_apiship_order_status($key, $name) {
		$query = $this->db->query("INSERT ignore into `" . DB_PREFIX . "apiship_order_status` (`key`,`name`) values ('". $key . "','" . $name . "')");
		return $this->get_apiship_order_status_by_key($key);
	}

	private function change_order_str_field($order_id, $field, $param) {
		$this->db->query("UPDATE `" . DB_PREFIX . "order` SET " .$field. " = '" . $this->db->escape($param) . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");
	}

	private function set_apiship_order_status($apiship_order_id, $status_id) {
		$this->db->query("UPDATE `" . DB_PREFIX . "apiship_order` SET status = " . $status_id . " WHERE apiship_order_id = '" . (int)$apiship_order_id . "'");
	}

	private function get_pickup_type($provider) {
		if (isset($this->apiship_params['shipping_apiship_provider'][$provider]['pickup_type'])) return 2;
		return 1;
	}

	private function get_status($apiship_order_status) {
		
    		$key = $apiship_order_status['status']['key'];
		$name = $apiship_order_status['status']['name'];

		$status = $this->get_apiship_order_status_by_key($key);
		if (!empty($status)) return $status;
		return $this->insert_apiship_order_status($key, $name);
	}


	public function get_order_params() {

		if (isset($this->request->get['id'])) $order_id = $this->request->get['id']; else return array('text' => $this->apiship_params['shipping_apiship_error_params']);

		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($order_id);
		if (in_array($order_info['order_status_id'], $this->apiship_params['shipping_apiship_paid_orders'])) $apiship_paid = true; else $apiship_paid = false;

		$query = $this->db->query("SELECT count(*) as total from `" . DB_PREFIX . "apiship_order` where oc_order_id =" . $order_id);
		if ($query->row['total']>0) $apiship_export = true; else $apiship_export = false;
		
		$apiship_pickup_type = $this->get_pickup_type($order_info['shipping_code']);
		
		$order_products = $this->model_checkout_order->getOrderProducts($order_id);
		
		$calculate_data = $this->apiship->calculate_places($order_products);
				 
		$apiship_place_length = $calculate_data['total_length'];
		$apiship_place_width = $calculate_data['total_width'];
		$apiship_place_height = $calculate_data['total_height'];
		$apiship_place_weight = $calculate_data['total_weight'];

		if (!empty($this->apiship_params['shipping_apiship_place_length'])) $apiship_place_length = $this->apiship_params['shipping_apiship_place_length'];
		if (!empty($this->apiship_params['shipping_apiship_place_width'])) $apiship_place_width = $this->apiship_params['shipping_apiship_place_width'];
		if (!empty($this->apiship_params['shipping_apiship_place_height'])) $apiship_place_height = $this->apiship_params['shipping_apiship_place_height'];
		if (!empty($this->apiship_params['shipping_apiship_place_weight'])) $apiship_place_weight = $this->apiship_params['shipping_apiship_place_weight'];

		$apiship_order_status = '-';


		$apiship_order = $this->get_apiship_order_by_oc_number($order_id);
		if (isset($apiship_order['apiship_order_id'])) $order_status = $this->apiship->apiship_order_status($apiship_order['apiship_order_id']);

		if (isset($order_status['status']['key'])) {

			if (isset($order_status['status']['name'])) $apiship_order_status = $order_status['status']['name'];
		
			if (isset($order_status['orderInfo']['orderId'])) {
				$order_info = $this->apiship->apiship_order_info($order_status['orderInfo']['orderId']);

				if (isset($order_info['body']['order']['pickupType'])) $apiship_pickup_type = $order_info['body']['order']['pickupType'];
				
				if (isset($order_info['body']['places'][0]['height'])) $apiship_place_height = $order_info['body']['places'][0]['height'];
				if (isset($order_info['body']['places'][0]['length'])) $apiship_place_length = $order_info['body']['places'][0]['length'];
				if (isset($order_info['body']['places'][0]['width'])) $apiship_place_width = $order_info['body']['places'][0]['width'];	
				if (isset($order_info['body']['places'][0]['weight'])) $apiship_place_weight = $order_info['body']['places'][0]['weight'];

			}
		}

		return [
			'export' => $apiship_export,
			'paid' => $apiship_paid, 
			'pickup_type' => $apiship_pickup_type,
			'place_length' => $apiship_place_length,
			'place_width' => $apiship_place_width,
			'place_height' => $apiship_place_height,
			'place_weight' => $apiship_place_weight,
			'order_status' => $apiship_order_status
		];
	}


}
