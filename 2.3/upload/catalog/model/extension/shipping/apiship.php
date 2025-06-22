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
			'shipping_apiship_contact_inn' => $this->config->get('shipping_apiship_contact_inn'),
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
			'shipping_apiship_package_weight' => $this->config->get('shipping_apiship_package_weight'),
	
			'shipping_apiship_provider' => $this->config->get('shipping_apiship_provider'),
			'shipping_apiship_mapping_status' => $this->config->get('shipping_apiship_mapping_status'),
	
			'shipping_apiship_paid_orders' => $this->config->get('shipping_apiship_paid_orders'),
			'shipping_apiship_cash_on_delivery_payment_methods' => $this->config->get('shipping_apiship_cash_on_delivery_payment_methods'),

			'shipping_apiship_sort_order' => $this->config->get('shipping_apiship_sort_order'),
	
			'shipping_apiship_articul_mode' => $this->config->get('shipping_apiship_articul_mode'),
			'shipping_apiship_tax_class_id' => $this->config->get('shipping_apiship_tax_class_id'),
	
			'shipping_apiship_export_status' => $this->config->get('shipping_apiship_export_status'),
			'shipping_apiship_cancel_export_status' => $this->config->get('shipping_apiship_cancel_export_status'),
	
			'shipping_apiship_group_export_status_ready' => $this->config->get('shipping_apiship_group_export_status_ready'),
			'shipping_apiship_group_export_status_ok' => $this->config->get('shipping_apiship_group_export_status_ok'),
			'shipping_apiship_group_export_status_error' => $this->config->get('shipping_apiship_group_export_status_error'),


			'shipping_apiship_mode' => $this->config->get('shipping_apiship_mode'),
			
			'shipping_apiship_prefix' => $this->config->get('shipping_apiship_prefix'),
			'shipping_apiship_error_stub_show' => $this->config->get('shipping_apiship_error_stub_show') ? true : false,
	
			'shipping_apiship_title' => $this->config->get('shipping_apiship_title'),
			'shipping_apiship_title_point_template' => html_entity_decode($this->config->get('shipping_apiship_title_point_template')),
			'shipping_apiship_description_point_template' => html_entity_decode($this->config->get('shipping_apiship_description_point_template')),
			'shipping_apiship_title_door_template' => html_entity_decode($this->config->get('shipping_apiship_title_door_template')),
			'shipping_apiship_description_door_template' => html_entity_decode($this->config->get('shipping_apiship_description_door_template')),
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
			'shipping_apiship_add_pickup_date' => $this->config->get('shipping_apiship_add_pickup_date'),
			'shipping_apiship_use_fix_product_assessed_cost' => $this->config->get('shipping_apiship_use_fix_product_assessed_cost'),
			'shipping_apiship_fix_product_assessed_cost' => $this->config->get('shipping_apiship_fix_product_assessed_cost'),
			
			'shipping_apiship_geo_zone_id' => $this->config->get('shipping_apiship_geo_zone_id'),

			'shipping_apiship_data' => [],
			'shipping_apiship_comment' => [],
			'shipping_apiship_providers' => []
		];

		if (!isset($this->apiship_params['shipping_apiship_paid_orders'])) $this->apiship_params['shipping_apiship_paid_orders'] = [];
		if (!isset($this->apiship_params['shipping_apiship_cash_on_delivery_payment_methods'])) $this->apiship_params['shipping_apiship_cash_on_delivery_payment_methods'] = [];

		$this->apiship = new Apiship($this->registry, $this->apiship_params, $this->log);

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
		return $this->apiship->getData('shipping_apiship_last_tracing_id');
	}


	private function getCartTotal() {

		// Totals
		$this->load->model('extension/extension');

		$totals = array();
		$taxes = $this->cart->getTaxes();
		$total = 0;
		
		// Because __call can not keep var references so we put them into an array. 			
		$total_data = array(
			'totals' => &$totals,
			'taxes'  => &$taxes,
			'total'  => &$total
		);

		
		// Display prices
		if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
			$sort_order = array();

			$results = $this->model_extension_extension->getExtensions('total');

			foreach ($results as $key => $value) {
				$sort_order[$key] = $this->config->get($value['code'] . '_sort_order');
			}

			array_multisort($sort_order, SORT_ASC, $results);

			foreach ($results as $result) {
				if ($this->config->get($result['code'] . '_status')) {
					$this->load->model('extension/total/' . $result['code']);
					
					// We have to put the totals in an array so that they pass by reference.
					$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
				}
			}

			$sort_order = array();

			foreach ($totals as $key => $value) {
				$sort_order[$key] = $value['sort_order'];
			}

			array_multisort($sort_order, SORT_ASC, $totals);
		}


		$end_total = 0;
		$shipping_cost = 0;
		foreach ($totals as $total) {
			if (($total['code']!='total')&&($total['code']!='shipping')) $end_total = $end_total + $total['value'];
			if ($total['code']=='shipping') $shipping_cost = $total['value'];
		}
		if ($end_total < 0) $end_total = $end_total + $shipping_cost;

		return $this->currency->convert($end_total,$this->config->get('config_currency'),$this->apiship_params['shipping_apiship_rub_select']);
	}

	private function check_geo_zone($address) {
		if (empty($this->apiship_params['shipping_apiship_geo_zone_id'])) return true;
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->apiship_params['shipping_apiship_geo_zone_id'] . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");
		return $query->num_rows>0;
	}


	private function get_image_ref($provider) {
		if ($provider == '')
			return '<img class="apiship_img_providers" style="width:60px;" src="' . HTTP_SERVER . 'catalog/view/theme/default/image/shipping/apiship_map.png">';
		else
			return "<img class='apiship_img_providers' style='width:60px;' src='https://storage.apiship.ru/icons/providers/svg/" . $provider . ".svg'> ";
	}


  	public function get_quote_list($address, $full_list = false) {

		$this->load->language('extension/shipping/apiship');
		if ($this->apiship_params['shipping_apiship_status'] != 1) {
			return [];
		}

		if ($this->check_geo_zone($address)==false) {
			return [];
		}

		$quote_data = [];
		
		$start_points = [];
		$select_points = $this->apiship->getData('shipping_apiship_select_points');

		$region = (isset($address['zone']))?$address['zone']:'';
		$city = (isset($address['city']))?trim($address['city']):'';
		$postcode = (isset($address['postcode']))?trim($address['postcode']):'';
		$ext_address = (isset($address['address_1']))?trim($address['address_1']):'';
		$country = (isset($address['iso_code_2']))?trim($address['iso_code_2']):'';
		$payment_method_code = (isset($this->session->data['payment_method']['code']))?$this->session->data['payment_method']['code']:'';
		$cash_on_delivery = in_array($payment_method_code, $this->apiship_params['shipping_apiship_cash_on_delivery_payment_methods']);

		$apiship_calculator_data = $this->apiship->apiship_calculator($country,$region,$city,$postcode,$ext_address,[],$this->cart->getProducts(),$this->getCartTotal(),$cash_on_delivery);
		$data = $apiship_calculator_data['body'];

		$this->apiship->setData('shipping_apiship_last_tracing_id',$apiship_calculator_data['x-tracing-id']);
		$this->apiship->setData('shipping_apiship_region',$region);
		$this->apiship->setData('shipping_apiship_city',$city);
		$this->apiship->setData('shipping_apiship_postcode',$postcode);
		$this->apiship->setData('shipping_apiship_ext_address',$ext_address);
		$this->apiship->setData('shipping_apiship_country',$country);

		if ($full_list == false)
		{
			$daysMin = [];
			$daysMax = [];

			if (isset($data['deliveryToPoint'])) $providers = $data['deliveryToPoint']; else $providers = [];
			foreach($providers as $provider) {	
				if (isset($provider['tariffs'])) $tariffs = $provider['tariffs']; else $tariffs = [];			
				foreach($tariffs as $tariff) {
					foreach($tariff['pointIds'] as $point_id) {
						foreach(array(1,2) as $pickup_type) {

							if (!in_array($pickup_type, $tariff['pickupTypes'])) continue;
							if (!in_array($pickup_type, $this->get_pickup_types($provider['providerKey']))) continue;
						
							if (!isset($tariff['tariffDescription'])) $tariff['tariffDescription'] = '';

							if (empty($daysMin[$provider['providerKey']])) {
								$daysMin[$provider['providerKey']] = $tariff['daysMin'];
							} else {
								if ($tariff['daysMin'] < $daysMin[$provider['providerKey']]) $daysMin[$provider['providerKey']] = $tariff['daysMin'];
							}
						
							if (empty($daysMax[$provider['providerKey']])) {
								$daysMax[$provider['providerKey']] = $tariff['daysMax'];
							} else {
								if ($tariff['daysMax'] > $daysMax[$provider['providerKey']]) $daysMax[$provider['providerKey']] = $tariff['daysMax'];
							}

							if (empty($daysMinAllPoints)) {
								$daysMinAllPoints = $tariff['daysMin'];
							} else {
								if ($tariff['daysMin'] < $daysMinAllPoints) $daysMinAllPoints = $tariff['daysMin'];
							}

							if (empty($daysMaxAllPoints)) {
								$daysMaxAllPoints = $tariff['daysMax'];
							} else {
								if ($tariff['daysMax'] > $daysMaxAllPoints) $daysMaxAllPoints = $tariff['daysMax'];
							}

							$key = 'point_' . $provider['providerKey'] . '_' . $tariff['tariffId'] . '_' . 'error' . '_' . $pickup_type;
							if (empty($start_points[$provider['providerKey']])) { 
								$start_points[$provider['providerKey']] = ['key' => $key, 'tariffName' => $tariff['tariffName'], 'daysMin' => $tariff['daysMin'], 'daysMax' => $tariff['daysMax'], 'deliveryCost' => $tariff['deliveryCost'], 'tariffDescription' => $tariff['tariffDescription']];
							} elseif ($tariff['deliveryCost'] < $start_points[$provider['providerKey']]['deliveryCost']) {
								if (strpos($start_points[$provider['providerKey']]['key'],'error')!==false)
									$start_points[$provider['providerKey']] = ['key' => $key, 'tariffName' => $tariff['tariffName'], 'daysMin' => $tariff['daysMin'], 'daysMax' => $tariff['daysMax'], 'deliveryCost' => $tariff['deliveryCost'], 'tariffDescription' => $tariff['tariffDescription']];
							}

							$key = 'point_' . $provider['providerKey'] . '_' . $tariff['tariffId'] . '_' . $point_id . '_' . $pickup_type;
							if (isset($select_points[$provider['providerKey']]))
							if ('apiship.' . $key == $select_points[$provider['providerKey']]['code'])  {
							$start_points[$provider['providerKey']] = ['key' => $key, 'tariffName' => $tariff['tariffName'], 'daysMin' => $tariff['daysMin'], 'daysMax' => $tariff['daysMax'], 'deliveryCost' => $tariff['deliveryCost'], 'tariffDescription' => $tariff['tariffDescription']];															
						}
					}

				}

			}				
	
		}

/*
			$this->apiship->toLog('get_quote_list debug', [
				'template' => $this->apiship_params['shipping_apiship_title_point_template'],
				'address' => $address,
				'start_points' => $start_points,
				'session' => $this->session->data,
				'select_points' => $select_points,
				'weight' => $this->cart->getWeight()
			], true);

*/
			if ($this->apiship_params['shipping_apiship_group_points']) {
				// все ПВЗ на одной карте

				usort($start_points, function($a, $b) {
				    if ($a['deliveryCost'] == $b['deliveryCost']) return 0;
				    return ($a['deliveryCost'] < $b['deliveryCost']) ? -1 : 1;
				});

				foreach($start_points as $provider_key => $element) {
					
					$shipping_apiship_last_select_code = $this->apiship->getData('shipping_apiship_last_select_code');
					if (isset($shipping_apiship_last_select_code))
					if ('apiship.' . $element['key'] == $shipping_apiship_last_select_code)  {

						$parce_code = $this->apiship->parce_code('apiship.' . $element['key']);
						
						$code = 'apiship.' . $element['key'];

						if ($parce_code['point_id'] != 'error') {
							$point = $this->apiship_point($parce_code['point_id']);
							$title = $this->fill_template([
								'template' => $this->apiship_params['shipping_apiship_title_point_template'],
								'type' => 'point', 
								'sub_type' => $point['type'], 
								'providerKey' => $point['providerKey'], 
								'tariffName' => $element['tariffName'], 
								'pointName' => $point['name'], 
								'pointAddress' => $this->apiship->get_address($point), 
								'daysMin' => $element['daysMin'], 
								'daysMax' => $element['daysMax'], 
								'tariffDescription' => $element['tariffDescription'],
								'code' => $code
							]);

							$description = $this->fill_template([
								'template' => $this->apiship_params['shipping_apiship_description_point_template'],
								'type' => 'point', 
								'sub_type' => $point['type'], 
								'providerKey' => $point['providerKey'], 
								'tariffName' => $element['tariffName'], 
								'pointName' => $point['name'], 
								'pointAddress' => $this->apiship->get_address($point), 
								'daysMin' => $element['daysMin'], 
								'daysMax' => $element['daysMax'], 
								'tariffDescription' => $element['tariffDescription'],
								'code' => $code
							]);


						}
						else {
							$title = $this->fill_template([
								'template' => $this->apiship_params['shipping_apiship_title_point_template'], //''
								'type' => 'point',
								'daysMin' => $daysMinAllPoints,
								'daysMax' => $daysMaxAllPoints,
								'code' => $code
							]);

							$description = $this->fill_template([
								'template' => $this->apiship_params['shipping_apiship_description_point_template'], //''
								'type' => 'point',
								'daysMin' => $daysMinAllPoints,
								'daysMax' => $daysMaxAllPoints,
								'code' => $code
							]);

							//$image = '<img class="apiship_img_providers" style="width:60px;" src="' . HTTP_SERVER . 'catalog/view/theme/default/image/shipping/apiship_map.png">';
						}
						
						$image = "https://storage.apiship.ru/icons/providers/svg/" . $point['providerKey'] . ".svg"; //1

						$quote_data[$element['key']] = [
							'code'         => $code,
							'title'        => $title, 
							'cost'         => $this->currency->convert($element['deliveryCost'], $this->apiship_params['shipping_apiship_rub_select'], $this->config->get('config_currency')),
							'tax_class_id' => $this->apiship_params['shipping_apiship_tax_class_id'],
							'text'         => $this->currency->format($this->tax->calculate($this->currency->convert($element['deliveryCost'], $this->apiship_params['shipping_apiship_rub_select'], $this->config->get('config_currency')), $this->apiship_params['shipping_apiship_tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']),
							'description'  => $description,
							'image'        => $image
						]; 


						break;
					}
				}


				if (empty($quote_data)) {
					foreach($start_points as $provider_key => $element) {
						
						$parce_code = $this->apiship->parce_code('apiship.' . $element['key']);
						
						$code = 'apiship.' . $element['key'];

						if ($parce_code['point_id'] != 'error') {
							$point = $this->apiship_point($parce_code['point_id']);
							$title = $this->fill_template([
								'template' => $this->apiship_params['shipping_apiship_title_point_template'],
								'type' => 'point', 
								'sub_type' => $point['type'], 
								'providerKey' => $point['providerKey'], 
								'tariffName' => $element['tariffName'], 
								'pointName' => $point['name'], 
								'pointAddress' => $this->apiship->get_address($point), 
								'daysMin' => $element['daysMin'], 
								'daysMax' => $element['daysMax'], 
								'tariffDescription' => $element['tariffDescription'],
								'code' => $code
							]);

							$description = $this->fill_template([
								'template' => $this->apiship_params['shipping_apiship_description_point_template'],
								'type' => 'point', 
								'sub_type' => $point['type'], 
								'providerKey' => $point['providerKey'], 
								'tariffName' => $element['tariffName'], 
								'pointName' => $point['name'], 
								'pointAddress' => $this->apiship->get_address($point), 
								'daysMin' => $element['daysMin'], 
								'daysMax' => $element['daysMax'], 
								'tariffDescription' => $element['tariffDescription'],
								'code' => $code
							]);

							//$image = "https://storage.apiship.ru/icons/providers/svg/" . $point['providerKey'] . ".svg";


						}
						else {
							$title = $this->fill_template([
								'template' => $this->apiship_params['shipping_apiship_title_point_template'], //'',
								'type' => 'point',
								'daysMin' => $daysMinAllPoints,
								'daysMax' => $daysMaxAllPoints,
								'code' => $code
							]);

							$description = $this->fill_template([
								'template' => $this->apiship_params['shipping_apiship_description_point_template'], //''
								'type' => 'point',
								'daysMin' => $daysMinAllPoints,
								'daysMax' => $daysMaxAllPoints,
								'code' => $code
							]);

							//$image = '<img class="apiship_img_providers" style="width:60px;" src="' . HTTP_SERVER . 'catalog/view/theme/default/image/shipping/apiship_map.png">';


						}
						
						//$image = "https://storage.apiship.ru/icons/providers/svg/" . $point['providerKey'] . ".svg2";

						$image = HTTP_SERVER . "catalog/view/theme/default/image/shipping/apiship_map.png";

						$quote_data[$element['key']] = [
							'code'         => $code,
							'title'        => $title, 
							'cost'         => $this->currency->convert($element['deliveryCost'], $this->apiship_params['shipping_apiship_rub_select'], $this->config->get('config_currency')),
							'tax_class_id' => $this->apiship_params['shipping_apiship_tax_class_id'],
							'text'         => $this->currency->format($this->tax->calculate($this->currency->convert($element['deliveryCost'], $this->apiship_params['shipping_apiship_rub_select'], $this->config->get('config_currency')), $this->apiship_params['shipping_apiship_tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']),
							'description'  => $description,
							'image'        => $image
						];


						break;
						
					}
				}
			 
			}
			else {

				// Для каждой СД своя карта
				foreach($start_points as $provider_key => $element) {
	
					$parce_code = $this->apiship->parce_code('apiship.' . $element['key']);
					
					$code = 'apiship.' . $element['key'];

					if ($parce_code['point_id'] != 'error') {
						$point = $this->apiship_point($parce_code['point_id']);
						$title = $this->fill_template([
							'template' => $this->apiship_params['shipping_apiship_title_point_template'],
							'type' => 'point', 
							'sub_type' => $point['type'], 
							'providerKey' => $point['providerKey'], 
							'tariffName' => $element['tariffName'], 
							'pointName' => $point['name'], 
							'pointAddress' => $this->apiship->get_address($point), 
							'daysMin' => $element['daysMin'], 
							'daysMax' => $element['daysMax'], 
							'tariffDescription' => $element['tariffDescription'],
							'code' => $code
						]);

						$description = $this->fill_template([
							'template' => $this->apiship_params['shipping_apiship_description_point_template'],
							'type' => 'point', 
							'sub_type' => $point['type'], 
							'providerKey' => $point['providerKey'], 
							'tariffName' => $element['tariffName'], 
							'pointName' => $point['name'], 
							'pointAddress' => $this->apiship->get_address($point), 
							'daysMin' => $element['daysMin'], 
							'daysMax' => $element['daysMax'], 
							'tariffDescription' => $element['tariffDescription'],
							'code' => $code
						]);
							
						//$image = "https://storage.apiship.ru/icons/providers/svg/" . $point['providerKey'] . ".svg";


					}
					else {
						$title = $this->fill_template([
							'template' => $this->apiship_params['shipping_apiship_title_point_template'], //'',
							'type' => 'point', 
							'providerKey' => $provider_key, 
							'daysMin' => $daysMin[$provider_key], 
							'daysMax' => $daysMax[$provider_key],
							'code' => $code
						]);

						$description = $this->fill_template([
							'template' => $this->apiship_params['shipping_apiship_description_point_template'], //'',
							'type' => 'point', 
							'providerKey' => $provider_key, 
							'daysMin' => $daysMin[$provider_key], 
							'daysMax' => $daysMax[$provider_key],
							'code' => $code
						]);

						//$image = '<img class="apiship_img_providers" style="width:60px;" src="' . HTTP_SERVER . 'catalog/view/theme/default/image/shipping/apiship_map.png">';

					}

					$image = "https://storage.apiship.ru/icons/providers/svg/" . $provider_key . ".svg";

					$quote_data[$element['key']] = [
						'code'         => $code,
						'title'        => $title, 
						'cost'         => $this->currency->convert($element['deliveryCost'], $this->apiship_params['shipping_apiship_rub_select'], $this->config->get('config_currency')),
						'tax_class_id' => $this->apiship_params['shipping_apiship_tax_class_id'],
						'text'         => $this->currency->format($this->tax->calculate($this->currency->convert($element['deliveryCost'], $this->apiship_params['shipping_apiship_rub_select'], $this->config->get('config_currency')), $this->apiship_params['shipping_apiship_tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']),
						'description'  => $description,
						'image'        => $image
					];

	
				}
			}			


		}
		else
		{
			// список ПВЗ в админку
			$points_data = $this->get_points_array($country,$region,$city,$postcode,$ext_address);
			if ($points_data['error'] == 'no_error') {
				usort($points_data['points'], function($a, $b) {
				    if ($a['title'] == $b['title']) return 0;
				    return ($a['title'] < $b['title']) ? -1 : 1;
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
				foreach(array(1,2) as $pickup_type) {

					if (!in_array($pickup_type, $tariff['pickupTypes'])) continue;
					if (!in_array($pickup_type, $this->get_pickup_types($provider['providerKey']))) continue;
		
					if (!isset($tariff['tariffDescription'])) $tariff['tariffDescription'] = '';
		
					$key = 'door_' . $provider['providerKey'] . '_' . $tariff['tariffId'] . '_' . $pickup_type;

					$code = 'apiship.' . $key;

					$title = $this->fill_template([
						'template' => $this->apiship_params['shipping_apiship_title_door_template'],
										'type' => 'door',
										'providerKey' => $provider['providerKey'], 
										'tariffName' => $tariff['tariffName'], 
										'daysMin' => $tariff['daysMin'], 
										'daysMax' => $tariff['daysMax'], 
						'tariffDescription' => $tariff['tariffDescription'],
						'code' => $code
					]); 

					$description = $this->fill_template([
						'template' => $this->apiship_params['shipping_apiship_description_door_template'],
						'type' => 'door',
						'providerKey' => $provider['providerKey'], 
						'tariffName' => $tariff['tariffName'], 
						'daysMin' => $tariff['daysMin'], 
						'daysMax' => $tariff['daysMax'], 
						'tariffDescription' => $tariff['tariffDescription'],
						'code' => $code
					]); 

					$quote_data[$key] = [
						'code'         => $code,
						'title'        => $title, 
						'cost'         => $this->currency->convert($tariff['deliveryCost'], $this->apiship_params['shipping_apiship_rub_select'], $this->config->get('config_currency')),
						'tax_class_id' => $this->apiship_params['shipping_apiship_tax_class_id'],
						'text'         => $this->currency->format($this->tax->calculate($this->currency->convert($tariff['deliveryCost'], $this->apiship_params['shipping_apiship_rub_select'], $this->config->get('config_currency')), $this->apiship_params['shipping_apiship_tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']),
						'description'  => $description,
						'image'	   => "https://storage.apiship.ru/icons/providers/svg/" . $provider['providerKey'] . ".svg"

					];

				}

			}

		}

		if ($this->apiship_params['shipping_apiship_error_stub_show']) {
		if(empty($quote_data)) {
			// нет данных, потому что таймаут
			$title = $this->apiship_params['shipping_apiship_error_timeout'];

			// нет данных, потому что ошибка
			if (isset($data['message'])) {
					
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
					'text'         => $this->currency->format(0, $this->session->data['currency'])
			);

		    }
		}
		
    		$providers = $this->model_extension_shipping_apiship->get_providers();
		$shipping_apiship_providers_keys = json_encode(array_keys($providers));
		
			
		$shipping_apiship_version_js_mod = ($this->config->get('shipping_apiship_version_js_mod') !== null)?$this->config->get('shipping_apiship_version_js_mod'):'0.5';
		$shipping_apiship_yandex_api_key = $this->config->get('shipping_apiship_yandex_api_key');;
		$shipping_apiship_get_last_tracing_id_url = $this->url->link('extension/shipping/apiship/get_last_tracing_id', '', 'SSL');
		$shipping_apiship_select_point = $this->language->get('shipping_apiship_select_point');
		$shipping_apiship_change_point = $this->language->get('shipping_apiship_change_point');
		$shipping_apiship_get_points_url = $this->url->link('extension/shipping/apiship/get_points', '', 'SSL');
		$shipping_apiship_set_point_url = $this->url->link('extension/shipping/apiship/set_point', '', 'SSL');
		$shipping_apiship_group_points = $this->config->get('shipping_apiship_group_points') ? 'true' : 'false';
		$shipping_apiship_title_from = $this->language->get('shipping_apiship_title_from');
		$shipping_apiship_icon_show = $this->config->get('shipping_apiship_icon_show') ? 'true' : 'false'; 
		$shipping_apiship_hide_link = $this->config->get('shipping_apiship_hide_link') ? 'true' : 'false'; 
		$standart_checkout = (strpos($this->request->get['route'], 'shipping_method') !== false) ? 'true' : 'false';

		$ext = <<<EOT
				<link rel="stylesheet" href="catalog/view/javascript/apiship.css?v=$shipping_apiship_version_js_mod type="text/css" />

				<script type="text/javascript">
					function get_yandex_api_key() {
						return '$shipping_apiship_yandex_api_key';
					}		                
				</script>

				<script type="text/javascript" src="catalog/view/javascript/apiship.js?v=$shipping_apiship_version_js_mod"></script>

				<script type="text/javascript">

					function get_last_tracing_id() {
						$.ajax({
							url: '$shipping_apiship_get_last_tracing_id_url',
								success: function(id) {
									console.log(id)	
								},
						});
					}

					function get_image_ref(provider) {
						return "<img class='apiship_img_providers' style='width:60px;' src='https://storage.apiship.ru/icons/providers/svg/"+ provider +".svg'> ";
					}

					function apiship_prepare_ref(is_select) {
						if (is_select)
							return '<a class="apiship_points" href="#" onclick="apiship_open(\$(this).siblings(\'input\').val());return false;"> $shipping_apiship_select_point </a>';
						else
							return '<a class="apiship_points" href="#" onclick="apiship_open(\$(this).siblings(\'input\').val());return false;"> $shipping_apiship_change_point </a>';
					}

					function show_icons() {
						const providers = $shipping_apiship_providers_keys
						providers.forEach(provider => {
							var el = `input:not([type=hidden]):not([value*='error'])[value*="_\${provider}_"]`;
							for(var i=0; i<document.querySelectorAll(el).length; i++) {
								el2 = get_image_ref(provider);
								document.querySelectorAll(el)[i].insertAdjacentHTML('afterend',el2);
							}

							el = `input:not([type=hidden])[value*='error'][value*="_\${provider}_"]`;
							for(var i=0; i<document.querySelectorAll(el).length; i++) {
								if ($shipping_apiship_group_points)
									el2 = "<img class='apiship_img_providers' style='width:60px;' src='catalog/view/theme/default/image/shipping/apiship_map.png'>";
								else
									el2 = get_image_ref(provider);
								document.querySelectorAll(el)[i].insertAdjacentHTML('afterend',el2);
							}
						});	
					}


					$(document).ready(function(){

						el = 'input:not([type=hidden])[value^="apiship"][value*="point"][value*="error"]';
						for(var i=0; i<document.querySelectorAll(el).length; i++) {
							text = document.querySelectorAll(el)[i].nextSibling.textContent;	
							text = text.replace(' - ', ' $shipping_apiship_title_from ');
							document.querySelectorAll(el)[i].nextSibling.textContent = text;
						}


						if (!$shipping_apiship_hide_link) {				
							el_select = apiship_prepare_ref(true);
							el_change = apiship_prepare_ref(false);
	
							$('input:not([type=hidden])[value^="apiship"][value*="point"][value*="error"]').parent().append(el_select);
							$('input:not([type=hidden])[value^="apiship"][value*="point"]:not([value*="error"])').parent().append(el_change);
						}

						if ($shipping_apiship_icon_show)					
							show_icons();

						get_last_tracing_id();
					});


					function apiship_open(code) {
						$.ajax({
							url: '$shipping_apiship_get_points_url',
							type: 'post',
							dataType: 'json',
							data: {'code':code},
							beforeSend: function() {
								$('input:not([type=hidden])[value="'+ code +'"]').parent().append('<div class="apiship_loading"></div>');
							},
							complete: function() {
								$(".apiship_loading").remove();
							},
							success: function(data_points) {
								if (data_points['error']=='no_error') {
									apiship.open(apiship_function, data_points['points'], code)	
									return
								}

								if (data_points['error']=='no_products') {
									window.location.reload();
									return
								}

								if (data_points['error']=='no_city') {
									window.location.reload();
									return
								}

								alert(data_points['error']);
							},
							error: function(xhr, ajaxOptions, thrownError) {
								alert(thrownError + '\\r\\n' + xhr.statusText + '\\r\\n' + xhr.responseText);
							}
						});
					}
					function apiship_function(result, code){	
						$.ajax({
							url: '$shipping_apiship_set_point_url',
							type: 'post',
							data: {shipping_apiship_point:result},
							dataType: 'json',
							success: function(data) {								

								$(".apiship_loading").remove();
								if (typeof data.error === 'undefined')
								{	
									if ($standart_checkout)
									{
										provider_items = data.code.split('_');
										provider = provider_items[1]
										el_img = ($shipping_apiship_icon_show)?get_image_ref(provider):'';
										el = "<input type='radio' checked='checked' value='" + data.code + "' name='shipping_method'> "+ el_img + data.title + ' - ' + data.text + apiship_prepare_ref();
										$('input[value="'+ code +'"]').parent().html(el);
									}
									else
									{
										$("input[value='" + code + "']").val(data.code);
										if ($("input[value='" + data.code + "']").attr("checked") == "checked")
										{
		                                          		if (typeof simplecheckout_reload !== "undefined") {
		                                            			simplecheckout_reload("shipping");
		                                          		} else if (typeof reloadAll !== "undefined") {
		                                            			reloadAll();
		                                          		}
										}
										else
											$("input[value='" + data.code + "']").click();
									}
								}
								else
									alert(data.error);
								
							}
						});
					}

				</script>
		EOT;


		if(!empty($quote_data)) 
      	$method_data = array(
	      	'code'       => 'apiship',
	      	'title'      => $this->apiship_params['shipping_apiship_title'] . $ext, 
	      	'quote'      => $quote_data,
			'sort_order' => $this->apiship_params['shipping_apiship_sort_order'],
        		'error'      => false
      	);

		return $method_data;


  	}


	private function fill_template($params) {
		//template, type, sub_type, providerKey, tariffName, pointName, pointAddress, daysMin, daysMax, tariffDescription 

		if (isset($params['type'])) $type = $params['type']; else $type = '';
		if (isset($params['sub_type'])) $sub_type = $params['sub_type']; else $sub_type = '';
		if (isset($params['providerKey'])) $providerKey = $params['providerKey']; else $providerKey = '';
		if (isset($params['tariffName'])) $tariffName = sprintf($this->language->get('shipping_apiship_tariff_template'), $params['tariffName']); else $tariffName = '';
		if (isset($params['pointName'])) $pointName = $params['pointName']; else $pointName = '';
		if (isset($params['pointAddress'])) $pointAddress = $params['pointAddress']; else $pointAddress = '';
		if (isset($params['daysMin'])) $daysMin = $params['daysMin']; else $daysMin = '';
		if (isset($params['daysMax'])) $daysMax = $params['daysMax']; else $daysMax = '';
		if (isset($params['tariffDescription'])) $tariffDescription = $params['tariffDescription']; else $tariffDescription = '';
		if (isset($params['code'])) $code = $params['code']; else $code = '';


		$template = $params['template'];

		$type_name = '';
		if ($type == 'door') $type_name = $this->language->get('shipping_apiship_door');
		if ($type == 'point') $type_name = $this->language->get('shipping_apiship_point');
	
		if ($sub_type == 1) $type_name = $type_name . $this->language->get('shipping_apiship_point_1');
		if ($sub_type == 2) $type_name = $type_name . $this->language->get('shipping_apiship_point_2');
		if ($sub_type == 3) $type_name = $type_name . $this->language->get('shipping_apiship_point_3');
		if ($sub_type == 4) $type_name = $type_name . $this->language->get('shipping_apiship_point_4');

		$time = $daysMin . '-' . $daysMax . $this->apiship_params['shipping_apiship_title_days'];
		if ($daysMin == $daysMax) $time = $daysMin . $this->apiship_params['shipping_apiship_title_days'];
		if ($daysMin == 0) $time = '';

		$template_ar = [
			'%type' => $type_name,
			'%company' => $this->get_provider_name($providerKey),
			'%name' => $pointName,
			'%address' => $pointAddress,
			'%tariff' => $tariffName,
			'%time' => $time,
			'%description' => $tariffDescription,
			'%logo' => $this->get_image_ref($providerKey),
			'%link' => '<a class="apiship_points" href="#" onclick="apiship_open(\''.$code.'\');return false;"> выбрать ПВЗ</a>'
		];

		foreach($template_ar as $teplate_key => $teplate_value) {
			$template = str_replace($teplate_key, $teplate_value, $template);
		}




		return $template;

	}

	private function get_points_array($country, $region, $city, $postcode, $ext_address, $provider = []) {
		$this->load->language('extension/shipping/apiship');

		$this->apiship->toLog('get_points_array', [ 
				'country' => $country,
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
		$payment_method_code = (isset($this->session->data['payment_method']['code']))?$this->session->data['payment_method']['code']:'';
		$cash_on_delivery = in_array($payment_method_code, $this->apiship_params['shipping_apiship_cash_on_delivery_payment_methods']);
		$apiship_calculator_data = $this->apiship->apiship_calculator($country,$region,$city,$postcode,$ext_address,$provider,$products,$this->getCartTotal(),$cash_on_delivery);
		$data = $apiship_calculator_data['body'];
		$points_ids = [];
		if (isset($data['deliveryToPoint'])) $providers = $data['deliveryToPoint']; else $providers = [];
		foreach($providers as $provider) {
			if (isset($provider['tariffs'])) $tariffs = $provider['tariffs']; else $tariffs = [];			
			foreach($tariffs as $tariff) {
				foreach(array(1,2) as $pickup_type) {

					if (!in_array($pickup_type, $tariff['pickupTypes'])) continue;
					if (!in_array($pickup_type, $this->get_pickup_types($provider['providerKey']))) continue;

					foreach($tariff['pointIds'] as $point_id) {
						if (!in_array($point_id,$points_ids)) $points_ids[] = $point_id;
					}
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
				if (!isset($tariff['tariffDescription'])) $tariff['tariffDescription'] = '';
				foreach($tariff['pointIds'] as $point_id) {
					foreach(array(1,2) as $pickup_type) {

						if (!in_array($pickup_type, $tariff['pickupTypes'])) continue;
						if (!in_array($pickup_type, $this->get_pickup_types($provider['providerKey']))) continue;
		
						$code = 'point_' . $provider['providerKey'] . '_' . $tariff['tariffId'] . '_' . $point_id . '_' . $pickup_type;
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
	
							'text' => $this->currency->format($this->tax->calculate($this->currency->convert($cost, $this->apiship_params['shipping_apiship_rub_select'], $this->config->get('config_currency')), $this->apiship_params['shipping_apiship_tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']),
							'cost' => $this->currency->format($this->tax->calculate($this->currency->convert($cost, $this->apiship_params['shipping_apiship_rub_select'], $this->config->get('config_currency')), $this->apiship_params['shipping_apiship_tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'], '', false),						
	
							'title' => $this->fill_template([
								'template' => $this->apiship_params['shipping_apiship_title_point_template'],
								'type' => 'point',
								'sub_type' => $point['type'], 
								'providerKey' => $provider['providerKey'], 
								'tariffName' => $tariff['tariffName'], 
								'pointName' => $point['name'], 
								'pointAddress' => $point['address'], 
								'daysMin' => $tariff['daysMin'], 
								'daysMax' => $tariff['daysMax'], 
								'tariffDescription' => $tariff['tariffDescription'],
								'code' => 'apiship.' . $code
							]),
				
							'type' => $apiship_point_types[$point['type']-1],
							'provider' => $apiship_providers[$provider['providerKey']],
							'provider_key' => $provider['providerKey'],
	
							//'phones'	=> $point['phone'],
							//'workTime'	=> $point['workTime'],
							
						];
					}
				}
			}
		}


		usort($all_points, function($a, $b) {
			if ($a['cost'] == $b['cost']) return 0;
			return ($a['cost'] < $b['cost']) ? -1 : 1;
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

		$operations = '[1,3]';

		if (trim($search)=='')
			$points = $this->apiship->apiship_point_by_params([
				'providerKey='.$type,
				'availableOperation='. $operations
			]);
		else
			$points = $this->apiship->apiship_point_by_params([
				'providerKey='.$type,
				'availableOperation='. $operations,
				'code%'.$search
			]);
	
		$points_data = [];	
			foreach($points as $point) {
					$points_data[] = ['id' => $point['id'],'text' => $this->apiship->get_address($point), 'code' => $point['code']];
				}

		usort($points_data, function($a, $b) {
		    if ($a['text'] == $b['text']) return 0;
		    return ($a['text'] < $b['text']) ? -1 : 1;
		});
		
		return $points_data;
		
	}

	public function get_points() {

		if (isset($this->request->post['code'])) $code = $this->request->post['code']; else $code = '';

		$region = $this->apiship->getData('shipping_apiship_region');
		$city = $this->apiship->getData('shipping_apiship_city');
		$postcode = $this->apiship->getData('shipping_apiship_postcode');
		$ext_address = $this->apiship->getData('shipping_apiship_ext_address');
		$country = $this->apiship->getData('shipping_apiship_country');

		$this->apiship->toLog('get_points', [
				'country' => $country,
				'code' => $code,
				'region' => $region,
				'city' => $city,
				'postcode' => $postcode,
				'ext_address' => $ext_address
		]);

		$points = [];
		$parce_code = $this->apiship->parce_code($code);
		
		$data = $this->get_points_array($country, $region, $city, $postcode, $ext_address);

		$points = [];
		foreach($data['points'] as $point) {
			if (($this->apiship_params['shipping_apiship_group_points']) || ($point['provider_key']==$parce_code['provider'])) $points[] = $point;
		}

		echo json_encode(['error' => $data['error'],'points' => $points]);

	}

	public function set_point() {
		if (!isset($this->request->post['shipping_apiship_point'])) {
			echo json_encode(['error' => $this->apiship_params['shipping_apiship_error_params']]); 
			exit;
		}

		$code = $this->request->post['shipping_apiship_point'];
		$parce_code = $this->apiship->parce_code($code);

		$delivery_type = $parce_code['delivery_type'];
		
		$region = $this->apiship->getData('shipping_apiship_region');
		$city = $this->apiship->getData('shipping_apiship_city');
		$postcode = $this->apiship->getData('shipping_apiship_postcode');
		$ext_address = $this->apiship->getData('shipping_apiship_ext_address');
		$country = $this->apiship->getData('shipping_apiship_country');

		$cost = -1;  
  		$address1 = '';
		$payment_method_code = (isset($this->session->data['payment_method']['code']))?$this->session->data['payment_method']['code']:'';
		$cash_on_delivery = in_array($payment_method_code, $this->apiship_params['shipping_apiship_cash_on_delivery_payment_methods']);
		$apiship_calculator_data = $this->apiship->apiship_calculator($country,$region,$city,$postcode,$ext_address,[],$this->cart->getProducts(),$this->getCartTotal(),$cash_on_delivery);
		$data = $apiship_calculator_data['body'];

		if (isset($data['deliveryToPoint'])) $providers = $data['deliveryToPoint']; else $providers = [];
		foreach($providers as $provider) {
			if (isset($provider['tariffs'])) $tariffs = $provider['tariffs']; else $tariffs = [];				
			foreach($tariffs as $tariff) {
				if (!isset($tariff['tariffDescription'])) $tariff['tariffDescription'] = '';
				foreach($tariff['pointIds'] as $point_id) {
					foreach(array(1,2) as $pickup_type) {

						if (!in_array($pickup_type, $tariff['pickupTypes'])) continue;
						if (!in_array($pickup_type, $this->get_pickup_types($provider['providerKey']))) continue;

						$key = $delivery_type. '_' . $provider['providerKey'] . '_' . $tariff['tariffId'] . '_' . $point_id . '_' . $pickup_type;
						if ('apiship.' . $key == $code) {
							$cost = $tariff['deliveryCost'];
							$point = $this->apiship_point($point_id);
	
							$postcode = $point['postIndex'];
							$address1 = $this->apiship->get_address($point);
							$title = $this->fill_template([
								'template' => $this->apiship_params['shipping_apiship_title_point_template'],
								'type' => 'point', 
								'sub_type' => $point['type'], 
								'providerKey' => $provider['providerKey'], 
								'tariffName' => $tariff['tariffName'], 
								'pointName' => $point['name'], 
								'pointAddress' => $point['address'], 
								'daysMin' => $tariff['daysMin'], 
								'daysMax' => $tariff['daysMax'], 
								'tariffDescription' => $tariff['tariffDescription']
							]);
						}
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
				'cost'         => $this->currency->convert($cost, $this->apiship_params['shipping_apiship_rub_select'], $this->config->get('config_currency')),
				'tax_class_id' => $this->apiship_params['shipping_apiship_tax_class_id'],
				'text'         => $this->currency->format($this->tax->calculate($this->currency->convert($cost, $this->apiship_params['shipping_apiship_rub_select'], $this->config->get('config_currency')), $this->apiship_params['shipping_apiship_tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'])
		];

		$select_points = $this->apiship->getData('shipping_apiship_select_points');
 		$select_points[$parce_code['provider']] = $shipping_apiship;
		$this->apiship->setData('shipping_apiship_select_points', $select_points);

		$this->session->data['shipping_methods']['apiship']['quote'][$parce_code['short_code']] = $shipping_apiship;
		$this->apiship->setData('shipping_apiship_last_select_code', $code);

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
			if (isset($this->request->get['term'])) $search = $this->request->get['term']; else $search = "";
			$data = $this->get_quote_list($address, true);
			$result = [];
			foreach($data['quote'] as $code => $item)
			{
				if (($search=='')||(mb_stripos($item['title'], $search) !== false))
				{
					if (count($result) < 10) 
						$result[$code] = $item;
					else
						if (mb_stripos($code, 'door_') !== false) $result[$code] = $item;
				}
			
			}
			$data['quote'] = $result;
			return $data;
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
		if (isset($this->request->get['shipping_apiship_comment'])) $shipping_apiship_comment = $this->request->get['shipping_apiship_comment']; else return array('error' => $this->apiship_params['shipping_apiship_error_params']);		
		if (isset($this->request->get['shipping_apiship_pickup_date'])) $shipping_apiship_pickup_date = $this->request->get['shipping_apiship_pickup_date']; else return array('error' => $this->apiship_params['shipping_apiship_error_params']);		

		return $this->export_order_value($order_id, $shipping_apiship_pickup_type, $shipping_apiship_place_length, 
			$shipping_apiship_place_width, $shipping_apiship_place_height, $shipping_apiship_place_weight, $shipping_apiship_comment, $shipping_apiship_pickup_date,
			$this->apiship_params['shipping_apiship_export_status'], '');
	}

	private function export_order_value($order_id, $shipping_apiship_pickup_type, $shipping_apiship_place_length, $shipping_apiship_place_width, 
		$shipping_apiship_place_height, $shipping_apiship_place_weight, $shipping_apiship_comment, $shipping_apiship_pickup_date, $shipping_apiship_export_status_ok,
		$shipping_apiship_export_status_error) {

		$text = '';
	
		$order_products = $this->getOrderProducts($order_id);

		$order_totals_items = $this->get_order_totals($order_id);
		$total_sum = $order_totals_items['total_sum'];
		$order_totals = $order_totals_items['order_totals'];

		$calculate_data = $this->apiship->calculate_places($order_products, $total_sum);

			$this->apiship->toLog('export_order debug', [
				'calculate_data' => $calculate_data,
			]);

		$items = $calculate_data['items']; 
		$total_length = $shipping_apiship_place_length; 
		$total_width = $shipping_apiship_place_width; 	
		$total_height = $shipping_apiship_place_height; 
		$total_weight = $shipping_apiship_place_weight; 
		$total_cost = $calculate_data['total_cost'];
		$assessed_cost = $calculate_data['assessed_cost'];

		$this->load->model('checkout/order');
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
		} else {

			$recipientAddressString = $this->apiship->get_address([
					'postIndex' => $order['shipping_postcode'],
					'area' => '',
	
					'region' => $order['shipping_zone'],
					'regionType' => '',
					
					'city' => $order['shipping_city'],
					'cityType' => '',

					'street' => $order['shipping_address_1'],
					'streetType' => ''

			], true);
		}

		$order_params['orderId'] = $this->apiship_params['shipping_apiship_prefix']. $order['order_id'];
		$order_params['orderWeight'] = $total_weight;
		$order_params['orderProviderKey'] = $provider;
		$order_params['orderPickupType'] = $shipping_apiship_pickup_type; 
		$order_params['orderPickupDate'] = $shipping_apiship_pickup_date; 
		$order_params['orderDeliveryType'] = ($delivery_type=='point')?2:1;
		$order_params['orderTariffId'] = $tariff_id;
		$order_params['orderPointOutId'] = $point_id;


		$order_params['costAssessedCost'] = $this->apiship->format_cost($order['total'] - $order_totals['shipping']);
		$paid_orders = in_array($order['order_status_id'], $this->apiship_params['shipping_apiship_paid_orders']);

		$order_params['costCodCost'] = ($paid_orders==false) ? $this->apiship->format_cost($order['total']) : 0;
		$order_params['costDeliveryCost'] =  ($paid_orders==false) ? $this->apiship->format_cost($order_totals['shipping']) : 0;
		$order_params['sub_total_cost'] = $this->apiship->format_cost($total_cost);
		$order_params['assessed_cost'] = $this->apiship->format_cost($assessed_cost); 

		$order_params['recipientPhone'] =  $order['telephone'];  
		$order_params['recipientEmail'] =  $order['email'];  
		$order_params['recipientContactName'] = $order['firstname'] . ' ' . $order['lastname'];
		$order_params['recipientCountryCode'] = $order['shipping_iso_code_2'];
		$order_params['recipientAddressString'] = $recipientAddressString;
		$order_params['recipientComment'] = $shipping_apiship_comment;

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
			
			$this->model_checkout_order->addOrderHistory($order['order_id'], $shipping_apiship_export_status_ok, $text, FALSE);
			
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

			if ($shipping_apiship_export_status_error!='')
				$this->model_checkout_order->addOrderHistory($order['order_id'], $shipping_apiship_export_status_error, $text, FALSE);

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


	public function export_orders() {

		$orders = $this->get_apaiship_orders();
				
		$export_result = [];

		foreach ($orders as $order) {
			$order_id = $order['order_id'];
			$order_params = $this->get_order_params_value($order_id);
			$export_result[] = $this->export_order_value(
				$order_id, 
				$order_params['pickup_type'], 
				$order_params['place_length'], 
				$order_params['place_width'], 
				$order_params['place_height'], 
				$order_params['place_weight'], 
				$order_params['comment'], 
				$order_params['pickup_date'],
				$this->apiship_params['shipping_apiship_group_export_status_ok'],
				$this->apiship_params['shipping_apiship_group_export_status_error']
			);
		}
		
		return ['status' => 'ok', 'export_result'  => $export_result];
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
				'last_status_change_date' => date("Y-m-d\T00:00:00+03:00", strtotime("-2 day")),
			];
		}

		date_default_timezone_set('Europe/Moscow');
		$dif_last_status_change_date = abs(strtotime(date("Y-m-d H:i:s")) - strtotime($data['last_status_change_date']));
		if ($dif_last_status_change_date / 3600 > 48) $data['last_status_change_date'] = date("Y-m-d\T00:00:00+03:00", strtotime("-2 day"));

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
			$ret_text = "time_out ". $time;
			$this->log->write('import time_out');
			//return;
		}

		// save_data

		ftruncate($fp, 0); 
 		rewind($fp);
    		fwrite($fp,serialize($data));
		fflush($fp);
		flock($fp, LOCK_UN);
		fclose($fp);

		//echo $ret_text;

		return ['status' => 'ok', 'import_result'  => $ret_text];

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

	private function get_pickup_types($provider) {
		$pickup_types = [];
		if (isset($this->apiship_params['shipping_apiship_provider'][$provider]['pickup_type'])) $pickup_types[] = 2;
		if (isset($this->apiship_params['shipping_apiship_provider'][$provider]['courier_type'])) $pickup_types[] = 1;
		return $pickup_types;	
	}


	private function get_status($apiship_order_status) {
		
    		$key = $apiship_order_status['status']['key'];
		$name = $apiship_order_status['status']['name'];

		$status = $this->get_apiship_order_status_by_key($key);
		if (!empty($status)) return $status;
		return $this->insert_apiship_order_status($key, $name);
	}

	private function getProductOptionValue($product_id, $product_option_value_id) {
		$query = $this->db->query("SELECT pov.option_value_id, ovd.name, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_id = '" . (int)$product_id . "' AND pov.product_option_value_id = '" . (int)$product_option_value_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}

	private function getOrderProducts($order_id) {

		$this->load->model('account/order');
		$this->load->model('catalog/product');
		$order_products = $this->model_account_order->getOrderProducts($order_id);

		foreach($order_products as &$order_product) {
			$order_options = $this->model_account_order->getOrderOptions($order_id, $order_product['order_product_id']);
			$product_info = $this->model_catalog_product->getProduct($order_product['product_id']); 
				
			$order_product['length'] = (isset($product_info['length'])) ? $product_info['length'] : 0; 
			$order_product['width'] = (isset($product_info['width'])) ? $product_info['width'] : 0;
			$order_product['height'] = (isset($product_info['height'])) ? $product_info['height'] : 0;
			$order_product['length_class_id'] = $product_info['length_class_id'];

			$weight = (isset($product_info['weight'])) ? $product_info['weight'] : 0;

			$order_options_values = [];
			foreach($order_options as $order_option) {
				$product_option_value_info = $this->getProductOptionValue($order_product['product_id'], $order_option['product_option_value_id']);				
				$order_options_values[] = $product_option_value_info;

				if (!empty($product_option_value_info['weight'])) {
					if ($product_option_value_info['weight_prefix'] == '+') {
						$weight += $product_option_value_info['weight'];
					} elseif ($product_option_value_info['weight_prefix'] == '-') {
						$weight -= $product_option_value_info['weight'];
					} elseif ($product_option_value_info['weight_prefix'] == '=') {
						$weight = $product_option_value_info['weight'];
					}

				}

			}

			$order_product['weight'] = $weight * $order_product['quantity'];
			$order_product['weight_class_id'] = $product_info['weight_class_id'];
		}
	
		return $order_products;
	}


	public function get_order_params() {

		if (isset($this->request->get['id'])) $order_id = $this->request->get['id']; else return array('text' => $this->apiship_params['shipping_apiship_error_params']);
		return $this->get_order_params_value($order_id);
	}

	private function get_order_params_value($order_id) {

		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($order_id);
		$apiship_paid = in_array($order_info['order_status_id'], $this->apiship_params['shipping_apiship_paid_orders']);

		$apiship_export = $this->is_order_export($order_id);
		
		$parce_code = $this->apiship->parce_code($order_info['shipping_code']);
		if (isset($parce_code['provider'])) $provider = $parce_code['provider']; else $provider = '';
		if (in_array(2, $this->get_pickup_types($provider))) $apiship_pickup_type = 2; else $apiship_pickup_type = 1;

		if (!empty($parce_code['pickup_type'])) $apiship_pickup_type = $parce_code['pickup_type']; 
		
		$order_products = $this->getOrderProducts($order_id);

		$total_sum = $this->get_order_totals($order_id)['total_sum'];

		$calculate_data = $this->apiship->calculate_places($order_products, $total_sum);
				 
		$apiship_place_length = $calculate_data['total_length'];
		$apiship_place_width = $calculate_data['total_width'];
		$apiship_place_height = $calculate_data['total_height'];
		$apiship_place_weight = $calculate_data['total_weight'];


		$apiship_order_status = '';
		$apiship_comment = $order_info['comment'];

		$add_pickup_date = 1;
		if ($this->apiship_params['shipping_apiship_add_pickup_date'] === '0') $add_pickup_date = 0;
		$pickup_date = date("Y-m-d", strtotime("+". $add_pickup_date ." day"));

		if ($apiship_export == true) {
			$apiship_order = $this->get_apiship_order_by_oc_number($order_id);
			if (isset($apiship_order['apiship_order_id'])) $order_status = $this->apiship->apiship_order_status($apiship_order['apiship_order_id']);
	
			if (isset($order_status['status']['key'])) {
	
				if (isset($order_status['status']['name'])) $apiship_order_status = $order_status['status']['name'];
			
				if (isset($order_status['orderInfo']['orderId'])) {
					$order_info = $this->apiship->apiship_order_info($order_status['orderInfo']['orderId']);
	
					if (isset($order_info['body']['order']['pickupType'])) $apiship_pickup_type = $order_info['body']['order']['pickupType'];
					if (isset($order_info['body']['order']['pickupDate'])) $pickup_date = date("Y-m-d", strtotime($order_info['body']['order']['pickupDate']));
					
					if (isset($order_info['body']['places'][0]['height'])) $apiship_place_height = $order_info['body']['places'][0]['height'];
					if (isset($order_info['body']['places'][0]['length'])) $apiship_place_length = $order_info['body']['places'][0]['length'];
					if (isset($order_info['body']['places'][0]['width'])) $apiship_place_width = $order_info['body']['places'][0]['width'];	
					if (isset($order_info['body']['places'][0]['weight'])) $apiship_place_weight = $order_info['body']['places'][0]['weight'];
					
					if (isset($order_info['body']['recipient']['comment'])) $apiship_comment = $order_info['body']['recipient']['comment']; 
				}
			}
		}

		return [
			'export' => $apiship_export,
			'paid' => $apiship_paid,//!$cash_on_delivery, 
			'pickup_type' => $apiship_pickup_type,
			'place_length' => $apiship_place_length,
			'place_width' => $apiship_place_width,
			'place_height' => $apiship_place_height,
			'place_weight' => $apiship_place_weight,
			'order_status' => $apiship_order_status,
			'comment' => $apiship_comment,
			'pickup_date' => $pickup_date
		];
	}

	private function get_order_totals($order_id) {

		$this->load->model('account/order');
 		$totals = $this->model_account_order->getOrderTotals($order_id);
		$total_sum = 0;
		$shipping_cost = 0;
		foreach ($totals as $total_item) {
			if (($total_item['code']!='total')&&($total_item['code']!='shipping')) $total_sum = $total_sum + $total_item['value'];
			if ($total_item['code']=='shipping') $shipping_cost = $total_item['value'];
			$order_totals[$total_item['code']] = $total_item['value'];
		}

		if ($total_sum < 0) {
			$total_sum = $total_sum + $shipping_cost;
			if (isset($order_totals['shipping'])) $order_totals['shipping'] = 0;
		}

		return [
			'total_sum' => $total_sum,
			'order_totals' => $order_totals
		];

	}

	private function is_order_export($order_id) {
		$query = $this->db->query("SELECT count(*) as total from `" . DB_PREFIX . "apiship_order` where oc_order_id =" . $order_id);
		if ($query->row['total']>0) 
			return true;
		else 
			return false;
	}

	public function get_delivery_cost_original() {
		if (isset($this->request->get['id'])) $order_id = $this->request->get['id']; else return array('text' => $this->apiship_params['shipping_apiship_error_params']);

		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($order_id);

		$order_products = $this->model_checkout_order->getOrderProducts($order_id);
		$total_sum = $this->get_order_totals($order_id)['total_sum'];

		$cash_on_delivery = in_array($order_info['payment_code'], $this->apiship_params['shipping_apiship_cash_on_delivery_payment_methods']);
		$apiship_calculator_data = $this->apiship->apiship_calculator($order_info['shipping_iso_code_2'],$order_info['shipping_zone'],$order_info['shipping_city'],$order_info['shipping_postcode'],$order_info['shipping_address_1'],[],$order_products,$total_sum,$cash_on_delivery);
		$data = $apiship_calculator_data['body'];

		$delivery_cost_original = '-';

		if (isset($data['deliveryToPoint'])) $providers = $data['deliveryToPoint']; else $providers = [];
		foreach($providers as $provider) {	
			if (isset($provider['tariffs'])) $tariffs = $provider['tariffs']; else $tariffs = [];			
			foreach($tariffs as $tariff) {
				foreach($tariff['pointIds'] as $point_id) {
					if (!in_array($this->get_pickup_type($provider['providerKey']), $tariff['pickupTypes'])) continue;
					
					$key = 'apiship.point_' . $provider['providerKey'] . '_' . $tariff['tariffId'] . '_' . $point_id;
					if ($key == $order_info['shipping_code']) {
						$delivery_cost_original = $tariff['deliveryCostOriginal'];
						break;
					}
				}

			}				

		}

		if (isset($data['deliveryToDoor'])) $providers = $data['deliveryToDoor']; else $providers = [];
		foreach($providers as $provider) {
			if (isset($provider['tariffs'])) $tariffs = $provider['tariffs']; else $tariffs = [];
			foreach($tariffs as $tariff) {
				if (!in_array($this->get_pickup_type($provider['providerKey']), $tariff['pickupTypes'])) continue;

				$key = 'apiship.door_' . $provider['providerKey'] . '_' . $tariff['tariffId'];
				if ($key == $order_info['shipping_code']) {
					$delivery_cost_original = $tariff['deliveryCostOriginal'];
					break;
				}

			}

		}

		$this->log->write('delivery_cost_original ' . print_r($delivery_cost_original,1));

		return [
			'delivery_cost_original' => $delivery_cost_original
		];
	}

	public function get_apaiship_orders() {
		if (empty($this->apiship_params['shipping_apiship_group_export_status_ready'])) return [];
		$sql = "SELECT order_id, shipping_code FROM `" . DB_PREFIX . "order` where shipping_code like '%apiship%' and order_status_id = ". $this->apiship_params['shipping_apiship_group_export_status_ready'];

		$query = $this->db->query($sql);

		return $query->rows;
	}


}
