<?php
class ControllerShippingApiship extends Controller { 
	private $error = array();
	
	public function index() {  
		$this->load->language('shipping/apiship');

		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('setting/setting');
		$this->load->model('shipping/apiship');

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			if ($this->validate() == true) {
				$this->model_setting_setting->editSetting('shipping_apiship', $this->request->post);
	
	        		$this->model_setting_setting->editSetting('apiship', ['apiship_status' => $this->request->post['shipping_apiship_status']]);
	    
				$this->session->data['success'] = $this->language->get('text_success');
										
				$this->response->redirect($this->url->link('shipping/apiship', 'token=' . $this->session->data['token'], 'SSL'));
			} else $this->error['warning'] = $this->language->get('error_shipping_apiship_fields_filled');
		}

  		$data['breadcrumbs'] = array();

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
   		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('extension/shipping', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('shipping/apiship', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_all_zones'] = $this->language->get('text_all_zones');
		$data['text_none'] = $this->language->get('text_none');
		$data['text_shipping_apiship_cron_url_copy'] = $this->language->get('text_shipping_apiship_cron_url_copy');

		$data['shipping_apiship_version'] = '1.1 (OpenCart 2.0 - 2.2)';
		$data['shipping_apiship_version_js_mod'] = rand();

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		$data['entry_shipping_apiship_token'] = $this->language->get('entry_shipping_apiship_token');
		$data['entry_shipping_apiship_title'] = $this->language->get('entry_shipping_apiship_title');
		$data['entry_shipping_apiship_template'] = $this->language->get('entry_shipping_apiship_template');
		$data['entry_shipping_apiship_template_desc'] = $this->language->get('entry_shipping_apiship_template_desc');
		$data['entry_shipping_apiship_title_point_template'] = $this->language->get('entry_shipping_apiship_title_point_template');
		$data['entry_shipping_apiship_description_point_template'] = $this->language->get('entry_shipping_apiship_description_point_template');
		$data['entry_shipping_apiship_title_door_template'] = $this->language->get('entry_shipping_apiship_title_door_template');
		$data['entry_shipping_apiship_description_door_template'] = $this->language->get('entry_shipping_apiship_description_door_template');
		$data['entry_shipping_apiship_custom_code'] = $this->language->get('entry_shipping_apiship_custom_code');
		$data['entry_shipping_apiship_sending_country_code'] = $this->language->get('entry_shipping_apiship_sending_country_code');
		$data['entry_shipping_apiship_sending_region'] = $this->language->get('entry_shipping_apiship_sending_region');
		$data['entry_shipping_apiship_sending_city'] = $this->language->get('entry_shipping_apiship_sending_city');
		$data['entry_shipping_apiship_sending_street'] = $this->language->get('entry_shipping_apiship_sending_street');
		$data['entry_shipping_apiship_sending_house'] = $this->language->get('entry_shipping_apiship_sending_house');
		$data['entry_shipping_apiship_sending_block'] = $this->language->get('entry_shipping_apiship_sending_block');
		$data['entry_shipping_apiship_sending_office'] = $this->language->get('entry_shipping_apiship_sending_office');

		$data['entry_shipping_apiship_contact_organization'] = $this->language->get('entry_shipping_apiship_contact_organization');
		$data['entry_shipping_apiship_contact_inn'] = $this->language->get('entry_shipping_apiship_contact_inn');
		$data['entry_shipping_apiship_contact_name'] = $this->language->get('entry_shipping_apiship_contact_name');
		$data['entry_shipping_apiship_contact_phone'] = $this->language->get('entry_shipping_apiship_contact_phone');
		$data['entry_shipping_apiship_contact_email'] = $this->language->get('entry_shipping_apiship_contact_email');

		$data['entry_shipping_apiship_parcel_length'] = $this->language->get('entry_shipping_apiship_parcel_length');
		$data['entry_shipping_apiship_parcel_width'] = $this->language->get('entry_shipping_apiship_parcel_width');
		$data['entry_shipping_apiship_parcel_height'] = $this->language->get('entry_shipping_apiship_parcel_height');
		$data['entry_shipping_apiship_parcel_weight'] = $this->language->get('entry_shipping_apiship_parcel_weight');

		$data['entry_shipping_apiship_place_length'] = $this->language->get('entry_shipping_apiship_place_length');
		$data['entry_shipping_apiship_place_width'] = $this->language->get('entry_shipping_apiship_place_width');
		$data['entry_shipping_apiship_place_height'] = $this->language->get('entry_shipping_apiship_place_height');
		$data['entry_shipping_apiship_place_weight'] = $this->language->get('entry_shipping_apiship_place_weight');
		$data['entry_shipping_apiship_package_weight'] = $this->language->get('entry_shipping_apiship_package_weight');

		$data['entry_shipping_apiship_sort_order'] = $this->language->get('entry_shipping_apiship_sort_order');
		$data['entry_shipping_apiship_yandex_api_key'] = $this->language->get('entry_shipping_apiship_yandex_api_key');
		$data['entry_shipping_apiship_status'] = $this->language->get('entry_shipping_apiship_status');
		$data['entry_shipping_apiship_rub_select'] = $this->language->get('entry_shipping_apiship_rub_select');
		$data['entry_shipping_apiship_gr_select'] = $this->language->get('entry_shipping_apiship_gr_select');
		$data['entry_shipping_apiship_cm_select'] = $this->language->get('entry_shipping_apiship_cm_select');
		$data['entry_shipping_apiship_tax_class'] = $this->language->get('entry_shipping_apiship_tax_class');
		$data['entry_shipping_apiship_articul_mode'] = $this->language->get('entry_shipping_apiship_articul_mode');
		$data['entry_shipping_apiship_geo_zone'] = $this->language->get('entry_shipping_apiship_geo_zone');
		$data['entry_shipping_apiship_icon_show'] = $this->language->get('entry_shipping_apiship_icon_show');
		$data['entry_shipping_apiship_error_stub_show'] = $this->language->get('entry_shipping_apiship_error_stub_show');
		$data['entry_shipping_apiship_group_points'] = $this->language->get('entry_shipping_apiship_group_points');
		$data['entry_shipping_apiship_prefix'] = $this->language->get('entry_shipping_apiship_prefix');
		$data['entry_shipping_apiship_use_fix_product_assessed_cost'] = $this->language->get('entry_shipping_apiship_use_fix_product_assessed_cost');
		$data['entry_shipping_apiship_add_pickup_date'] = $this->language->get('entry_shipping_apiship_add_pickup_date');
		$data['entry_shipping_apiship_export_status'] = $this->language->get('entry_shipping_apiship_export_status');
		$data['entry_shipping_apiship_group_export_status_ready'] = $this->language->get('entry_shipping_apiship_group_export_status_ready');
		$data['entry_shipping_apiship_group_export_status_ok'] = $this->language->get('entry_shipping_apiship_group_export_status_ok');
		$data['entry_shipping_apiship_group_export_status_error'] = $this->language->get('entry_shipping_apiship_group_export_status_error');
		$data['entry_shipping_apiship_cancel_export_status'] = $this->language->get('entry_shipping_apiship_cancel_export_status');
		$data['entry_shipping_apiship_mode'] = $this->language->get('entry_shipping_apiship_mode');
		$data['entry_shipping_apiship_include_fees'] = $this->language->get('entry_shipping_apiship_include_fees');

		$data['entry_shipping_apiship_paid_orders'] = $this->language->get('entry_shipping_apiship_paid_orders');
		$data['entry_shipping_apiship_cash_on_delivery_payment_methods'] = $this->language->get('entry_shipping_apiship_cash_on_delivery_payment_methods');
		$data['entry_cash_on_delivery'] = $this->language->get('entry_cash_on_delivery');

		$data['entry_shipping_apiship_import_cron_url'] = $this->language->get('entry_shipping_apiship_import_cron_url');
		$data['entry_shipping_apiship_export_cron_url'] = $this->language->get('entry_shipping_apiship_export_cron_url');
		$data['entry_shipping_apiship_cron_key'] = $this->language->get('entry_shipping_apiship_cron_key');

		$data['entry_main_settings'] = $this->language->get('entry_main_settings');
		$data['entry_sending_address'] = $this->language->get('entry_sending_address');
		$data['entry_contact_details'] = $this->language->get('entry_contact_details');
		$data['entry_parcel_defaults'] = $this->language->get('entry_parcel_defaults');
		$data['entry_place_defaults'] = $this->language->get('entry_place_defaults');
		$data['entry_extra_settings'] = $this->language->get('entry_extra_settings');
		$data['entry_providers_points'] = $this->language->get('entry_providers_points');
		$data['entry_providers_point'] = $this->language->get('entry_providers_point');
		$data['entry_providers_courier'] = $this->language->get('entry_providers_courier');

		$data['entry_events_mapping'] = $this->language->get('entry_events_mapping');
		$data['entry_events_mapping_notify'] = $this->language->get('entry_events_mapping_notify');

		$data['text_url_callback'] = $this->language->get('text_url_callback');
		$server = isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1')) ? HTTPS_CATALOG : HTTP_CATALOG;
		$data['url_callback'] = $server . 'index.php?route=shipping/apiship/callback';
		$data['shipping_apiship_search_point_url'] = $server . 'index.php?route=shipping/apiship/get_point';
		
		$data['shipping_apiship_modes'] = [
			['code'=>'shipping_apiship_mode_normal', 'code_text'=>$this->language->get('entry_shipping_apiship_mode_normal')],
			['code'=>'shipping_apiship_mode_debug', 'code_text'=>$this->language->get('entry_shipping_apiship_mode_debug')]
		];

		$data['shipping_apiship_countries'] = [
			['code'=>'RU', 'code_text'=>$this->language->get('entry_country_ru')],
			['code'=>'KZ', 'code_text'=>$this->language->get('entry_country_kz')],
			['code'=>'BY', 'code_text'=>$this->language->get('entry_country_by')],
			['code'=>'KG', 'code_text'=>$this->language->get('entry_country_kg')],
			['code'=>'AM', 'code_text'=>$this->language->get('entry_country_am')]
		];

		$data['entry_shipping_apiship_pickup_type'] = $this->language->get('entry_shipping_apiship_pickup_type');
		$data['shipping_apiship_pickup_types'] = [
			['code'=>1, 'code_text'=>$this->language->get('entry_shipping_apiship_pickup_type1')],
			['code'=>2, 'code_text'=>$this->language->get('entry_shipping_apiship_pickup_type2')],
		];                

		$data['shipping_apiship_pickup_dates'] = [
			['code'=>0, 'code_text'=>$this->language->get('entry_shipping_apiship_add_pickup_date0')],
			['code'=>1, 'code_text'=>$this->language->get('entry_shipping_apiship_add_pickup_date1')]
		];                

		$this->load->language('catalog/product');
		$data['shipping_apiship_articul_modes'] = [
			['code'=>'model', 'code_text'=>$this->language->get('entry_model')],
			['code'=>'sku', 'code_text'=>$this->language->get('entry_sku')],
			['code'=>'upc', 'code_text'=>$this->language->get('entry_upc')],
			['code'=>'ean', 'code_text'=>$this->language->get('entry_ean')],
			['code'=>'jan', 'code_text'=>$this->language->get('entry_jan')],
			['code'=>'isbn', 'code_text'=>$this->language->get('entry_isbn')],
			['code'=>'mpn', 'code_text'=>$this->language->get('entry_mpn')]
		];                

            $this->load->model('localisation/currency');
		$data['currencies'] = ['' => ['code' => '']] + $this->model_localisation_currency->getCurrencies();

		$this->load->model('localisation/geo_zone');
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		$this->load->model('localisation/tax_class');
		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

		$this->load->model('localisation/weight_class');
		$data['weight_classes'] = ['' => ['weight_class_id' => '', 'title' => '']] + $this->model_localisation_weight_class->getWeightClasses();

		$this->load->model('localisation/length_class');
		$data['length_classes'] = ['' => ['length_class_id' => '', 'title' => '']] + $this->model_localisation_length_class->getLengthClasses(); 

		$this->load->model('localisation/order_status');	
		$data['order_statuses'] = ['' => ['order_status_id' => '', 'name' => '']] + $this->model_localisation_order_status->getOrderStatuses();

		$data['payment_methods'] = $this->model_shipping_apiship->get_payment_methods();

		if (isset($this->request->post['shipping_apiship_rub_select'])) {
			$data['shipping_apiship_rub_select'] = $this->request->post['shipping_apiship_rub_select'];
		} else {
			$data['shipping_apiship_rub_select'] = $this->config->get('shipping_apiship_rub_select');
		}

            if (isset($this->request->post['shipping_apiship_gr_select'])) {
			$data['shipping_apiship_gr_select'] = $this->request->post['shipping_apiship_gr_select'];
		} else {
			$data['shipping_apiship_gr_select'] = $this->config->get('shipping_apiship_gr_select');
		}

            if (isset($this->request->post['shipping_apiship_cm_select'])) {
			$data['shipping_apiship_cm_select'] = $this->request->post['shipping_apiship_cm_select'];
		} else {
			$data['shipping_apiship_cm_select'] = $this->config->get('shipping_apiship_cm_select');
		}
                            
		if (isset($this->request->post['shipping_apiship_token'])) {
			$data['shipping_apiship_token'] = $this->request->post['shipping_apiship_token'];
		} else {
			$data['shipping_apiship_token'] = $this->config->get('shipping_apiship_token');
		} 

		$data['shipping_apiship_providers'] = [];
		$data['shipping_apiship_providers_points'] = [];
		$data['shipping_apiship_integrator_statuses'] = [];

		if ($data['shipping_apiship_token'] != '') {
			$apiship_providers = $this->model_shipping_apiship->get_providers();
			if (!empty($apiship_providers['message'])) $this->error['warning'] = $apiship_providers['message'];		
			$data['shipping_apiship_providers'] = $apiship_providers['providers'];
			$data['shipping_apiship_providers_points'] = $this->model_shipping_apiship->get_providers_points();
			$data['shipping_apiship_integrator_statuses'] = $this->model_shipping_apiship->get_integrator_statuses();
		}

		if (isset($this->request->post['shipping_apiship_title'])) {
			$data['shipping_apiship_title'] = $this->request->post['shipping_apiship_title'];
		} else {
			if ($this->config->get('shipping_apiship_title') !== null) 
				$data['shipping_apiship_title'] = $this->config->get('shipping_apiship_title');
			else
				$data['shipping_apiship_title'] = 'ApiShip';
		} 

		if (isset($this->request->post['shipping_apiship_title_point_template'])) {
			$data['shipping_apiship_title_point_template'] = $this->request->post['shipping_apiship_title_point_template'];
		} else {
			$data['shipping_apiship_title_point_template'] = $this->config->get('shipping_apiship_title_point_template');
		} 

		if ($data['shipping_apiship_title_point_template'] == '') $data['shipping_apiship_title_point_template'] = $this->language->get('text_shipping_apiship_title_point_template');

		if (isset($this->request->post['shipping_apiship_description_point_template'])) {
			$data['shipping_apiship_description_point_template'] = $this->request->post['shipping_apiship_description_point_template'];
		} else {
			$data['shipping_apiship_description_point_template'] = $this->config->get('shipping_apiship_description_point_template');
		} 


		if (isset($this->request->post['shipping_apiship_title_door_template'])) {
			$data['shipping_apiship_title_door_template'] = $this->request->post['shipping_apiship_title_door_template'];
		} else {
			$data['shipping_apiship_title_door_template'] = $this->config->get('shipping_apiship_title_door_template');
		} 

		if ($data['shipping_apiship_title_door_template'] == '') $data['shipping_apiship_title_door_template'] = $this->language->get('text_shipping_apiship_title_door_template');

		if (isset($this->request->post['shipping_apiship_description_door_template'])) {
			$data['shipping_apiship_description_door_template'] = $this->request->post['shipping_apiship_description_door_template'];
		} else {
			$data['shipping_apiship_description_door_template'] = $this->config->get('shipping_apiship_description_door_template');
		} 


		if (isset($this->request->post['shipping_apiship_custom_code'])) {
			$data['shipping_apiship_custom_code'] = $this->request->post['shipping_apiship_custom_code'];
		} else {
			$data['shipping_apiship_custom_code'] = $this->config->get('shipping_apiship_custom_code');
		} 

		if (isset($this->request->post['shipping_apiship_include_fees'])) {
			$data['shipping_apiship_include_fees'] = $this->request->post['shipping_apiship_include_fees'];
		} else {
			$data['shipping_apiship_include_fees'] = $this->config->get('shipping_apiship_include_fees');
		} 

		if (isset($this->request->post['shipping_apiship_group_points'])) {
			$data['shipping_apiship_group_points'] = $this->request->post['shipping_apiship_group_points'];
		} else {
			$data['shipping_apiship_group_points'] = $this->config->get('shipping_apiship_group_points');
		} 


		if (isset($this->request->post['shipping_apiship_sending_country_code'])) {
			$data['shipping_apiship_sending_country_code'] = $this->request->post['shipping_apiship_sending_country_code'];
		} else {
			$data['shipping_apiship_sending_country_code'] = $this->config->get('shipping_apiship_sending_country_code');
		} 

		if (isset($this->request->post['shipping_apiship_sending_region'])) {
			$data['shipping_apiship_sending_region'] = $this->request->post['shipping_apiship_sending_region'];
		} else {
			$data['shipping_apiship_sending_region'] = $this->config->get('shipping_apiship_sending_region');
		} 

		if (isset($this->request->post['shipping_apiship_sending_city'])) {
			$data['shipping_apiship_sending_city'] = $this->request->post['shipping_apiship_sending_city'];
		} else {
			$data['shipping_apiship_sending_city'] = $this->config->get('shipping_apiship_sending_city');
		} 

		if (isset($this->request->post['shipping_apiship_sending_street'])) {
			$data['shipping_apiship_sending_street'] = $this->request->post['shipping_apiship_sending_street'];
		} else {
			$data['shipping_apiship_sending_street'] = $this->config->get('shipping_apiship_sending_street');
		} 

		if (isset($this->request->post['shipping_apiship_sending_house'])) {
			$data['shipping_apiship_sending_house'] = $this->request->post['shipping_apiship_sending_house'];
		} else {
			$data['shipping_apiship_sending_house'] = $this->config->get('shipping_apiship_sending_house');
		} 

		if (isset($this->request->post['shipping_apiship_sending_block'])) {
			$data['shipping_apiship_sending_block'] = $this->request->post['shipping_apiship_sending_block'];
		} else {
			$data['shipping_apiship_sending_block'] = $this->config->get('shipping_apiship_sending_block');
		} 

		if (isset($this->request->post['shipping_apiship_sending_office'])) {
			$data['shipping_apiship_sending_office'] = $this->request->post['shipping_apiship_sending_office'];
		} else {
			$data['shipping_apiship_sending_office'] = $this->config->get('shipping_apiship_sending_office');
		} 

		if (isset($this->request->post['shipping_apiship_contact_organization'])) {
			$data['shipping_apiship_contact_organization'] = $this->request->post['shipping_apiship_contact_organization'];
		} else {
			$data['shipping_apiship_contact_organization'] = $this->config->get('shipping_apiship_contact_organization');
		} 

		if (isset($this->request->post['shipping_apiship_contact_inn'])) {
			$data['shipping_apiship_contact_inn'] = $this->request->post['shipping_apiship_contact_inn'];
		} else {
			$data['shipping_apiship_contact_inn'] = $this->config->get('shipping_apiship_contact_inn');
		} 

		if (isset($this->request->post['shipping_apiship_contact_name'])) {
			$data['shipping_apiship_contact_name'] = $this->request->post['shipping_apiship_contact_name'];
		} else {
			$data['shipping_apiship_contact_name'] = $this->config->get('shipping_apiship_contact_name');
		} 

		if (isset($this->request->post['shipping_apiship_contact_phone'])) {
			$data['shipping_apiship_contact_phone'] = $this->request->post['shipping_apiship_contact_phone'];
		} else {
			$data['shipping_apiship_contact_phone'] = $this->config->get('shipping_apiship_contact_phone');
		} 

		if (isset($this->request->post['shipping_apiship_contact_email'])) {
			$data['shipping_apiship_contact_email'] = $this->request->post['shipping_apiship_contact_email'];
		} else {
			$data['shipping_apiship_contact_email'] = $this->config->get('shipping_apiship_contact_email');
		} 

		if (isset($this->request->post['shipping_apiship_parcel_length'])) {
			$data['shipping_apiship_parcel_length'] = $this->request->post['shipping_apiship_parcel_length'];
		} elseif ($this->config->get('shipping_apiship_parcel_length')) { 
			$data['shipping_apiship_parcel_length'] = $this->config->get('shipping_apiship_parcel_length');
		} else {
			$data['shipping_apiship_parcel_length'] = 10;
		} 

		if (isset($this->request->post['shipping_apiship_parcel_width'])) {
			$data['shipping_apiship_parcel_width'] = $this->request->post['shipping_apiship_parcel_width'];
		} elseif ($this->config->get('shipping_apiship_parcel_width')) { 
			$data['shipping_apiship_parcel_width'] = $this->config->get('shipping_apiship_parcel_width');
		} else {
			$data['shipping_apiship_parcel_width'] = 10;
		} 

		if (isset($this->request->post['shipping_apiship_parcel_height'])) {
			$data['shipping_apiship_parcel_height'] = $this->request->post['shipping_apiship_parcel_height'];
		} elseif ($this->config->get('shipping_apiship_parcel_height')) { 
			$data['shipping_apiship_parcel_height'] = $this->config->get('shipping_apiship_parcel_height');
		} else {
			$data['shipping_apiship_parcel_height'] = 10;
		} 

		if (isset($this->request->post['shipping_apiship_parcel_weight'])) {
			$data['shipping_apiship_parcel_weight'] = $this->request->post['shipping_apiship_parcel_weight'];
		} elseif ($this->config->get('shipping_apiship_parcel_weight')) { 
			$data['shipping_apiship_parcel_weight'] = $this->config->get('shipping_apiship_parcel_weight');
		} else {
			$data['shipping_apiship_parcel_weight'] = 500;
		} 

		if (isset($this->request->post['shipping_apiship_place_length'])) {
			$data['shipping_apiship_place_length'] = $this->request->post['shipping_apiship_place_length'];
		} else { 
			$data['shipping_apiship_place_length'] = $this->config->get('shipping_apiship_place_length');
		}

		if (isset($this->request->post['shipping_apiship_place_width'])) {
			$data['shipping_apiship_place_width'] = $this->request->post['shipping_apiship_place_width'];
		} else { 
			$data['shipping_apiship_place_width'] = $this->config->get('shipping_apiship_place_width');
		}  

		if (isset($this->request->post['shipping_apiship_place_height'])) {
			$data['shipping_apiship_place_height'] = $this->request->post['shipping_apiship_place_height'];
		} else { 
			$data['shipping_apiship_place_height'] = $this->config->get('shipping_apiship_place_height');
		}  

		if (isset($this->request->post['shipping_apiship_place_weight'])) {
			$data['shipping_apiship_place_weight'] = $this->request->post['shipping_apiship_place_weight'];
		} else { 
			$data['shipping_apiship_place_weight'] = $this->config->get('shipping_apiship_place_weight');
		} 

		if (isset($this->request->post['shipping_apiship_package_weight'])) {
			$data['shipping_apiship_package_weight'] = $this->request->post['shipping_apiship_package_weight'];
		} else { 
			$data['shipping_apiship_package_weight'] = $this->config->get('shipping_apiship_package_weight');
		} 

		if (isset($this->request->post['shipping_apiship_provider'])) {
			$data['shipping_apiship_provider'] = $this->request->post['shipping_apiship_provider'];
		} else {
			$data['shipping_apiship_provider'] = $this->config->get('shipping_apiship_provider');
		}          
      
		if (isset($this->request->post['shipping_apiship_mapping_status'])) {
			$data['shipping_apiship_mapping_status'] = $this->request->post['shipping_apiship_mapping_status'];
		} else {
			$data['shipping_apiship_mapping_status'] = $this->config->get('shipping_apiship_mapping_status');
		}      

		if (isset($this->request->post['shipping_apiship_articul_mode'])) {
			$data['shipping_apiship_articul_mode'] = $this->request->post['shipping_apiship_articul_mode'];
		} else {
			$data['shipping_apiship_articul_mode'] = $this->config->get('shipping_apiship_articul_mode');
		}                

		if (isset($this->request->post['shipping_apiship_tax_class_id'])) {
			$data['shipping_apiship_tax_class_id'] = $this->request->post['shipping_apiship_tax_class_id'];
		} else {
			$data['shipping_apiship_tax_class_id'] = $this->config->get('shipping_apiship_tax_class_id');
		}                
                
		if (isset($this->request->post['shipping_apiship_geo_zone_id'])) {
			$data['shipping_apiship_geo_zone_id'] = $this->request->post['shipping_apiship_geo_zone_id'];
		} else {
			$data['shipping_apiship_geo_zone_id'] = $this->config->get('shipping_apiship_geo_zone_id');
		}

		if (isset($this->request->post['shipping_apiship_sort_order'])) {
			$data['shipping_apiship_sort_order'] = $this->request->post['shipping_apiship_sort_order'];
		} else {
			$data['shipping_apiship_sort_order'] = $this->config->get('shipping_apiship_sort_order');
		}                

		if (isset($this->request->post['shipping_apiship_yandex_api_key'])) {
			$data['shipping_apiship_yandex_api_key'] = $this->request->post['shipping_apiship_yandex_api_key'];
		} else {
			$data['shipping_apiship_yandex_api_key'] = $this->config->get('shipping_apiship_yandex_api_key');
		}                

		if (isset($this->request->post['shipping_apiship_export_status'])) {
			$data['shipping_apiship_export_status'] = $this->request->post['shipping_apiship_export_status'];
		} else {
			$data['shipping_apiship_export_status'] = $this->config->get('shipping_apiship_export_status');
		} 
                
		if (isset($this->request->post['shipping_apiship_cancel_export_status'])) {
			$data['shipping_apiship_cancel_export_status'] = $this->request->post['shipping_apiship_cancel_export_status'];
		} else {
			$data['shipping_apiship_cancel_export_status'] = $this->config->get('shipping_apiship_cancel_export_status');
		} 
                
		if (isset($this->request->post['shipping_apiship_group_export_status_ready'])) {
			$data['shipping_apiship_group_export_status_ready'] = $this->request->post['shipping_apiship_group_export_status_ready'];
		} else {
			$data['shipping_apiship_group_export_status_ready'] = $this->config->get('shipping_apiship_group_export_status_ready');
		} 

		if (isset($this->request->post['shipping_apiship_group_export_status_ok'])) {
			$data['shipping_apiship_group_export_status_ok'] = $this->request->post['shipping_apiship_group_export_status_ok'];
		} else {
			$data['shipping_apiship_group_export_status_ok'] = $this->config->get('shipping_apiship_group_export_status_ok');
		} 

		if (isset($this->request->post['shipping_apiship_group_export_status_error'])) {
			$data['shipping_apiship_group_export_status_error'] = $this->request->post['shipping_apiship_group_export_status_error'];
		} else {
			$data['shipping_apiship_group_export_status_error'] = $this->config->get('shipping_apiship_group_export_status_error');
		} 

		if (isset($this->request->post['shipping_apiship_status'])) {
			$data['shipping_apiship_status'] = $this->request->post['shipping_apiship_status'];
		} else {
			$data['shipping_apiship_status'] = $this->config->get('shipping_apiship_status');
		}

		if (isset($this->request->post['shipping_apiship_icon_show'])) {
			$data['shipping_apiship_icon_show'] = $this->request->post['shipping_apiship_icon_show'];
		} else {
			$data['shipping_apiship_icon_show'] = $this->config->get('shipping_apiship_icon_show');
		} 

		if (isset($this->request->post['shipping_apiship_prefix'])) {
			$data['shipping_apiship_prefix'] = $this->request->post['shipping_apiship_prefix'];
		} else {
			$data['shipping_apiship_prefix'] = $this->config->get('shipping_apiship_prefix');
		} 
       
		if (isset($this->request->post['shipping_apiship_error_stub_show'])) {
			$data['shipping_apiship_error_stub_show'] = $this->request->post['shipping_apiship_error_stub_show'];
		} else {
			$data['shipping_apiship_error_stub_show'] = $this->config->get('shipping_apiship_error_stub_show');
		} 
       
		if (isset($this->request->post['shipping_apiship_mode'])) {
			$data['shipping_apiship_mode'] = $this->request->post['shipping_apiship_mode'];
		} else {
			$data['shipping_apiship_mode'] = $this->config->get('shipping_apiship_mode');
		}  

		if (isset($this->request->post['shipping_apiship_pickup_type'])) {
			$data['shipping_apiship_pickup_type'] = $this->request->post['shipping_apiship_pickup_type'];
		} else {
			$data['shipping_apiship_pickup_type'] = $this->config->get('shipping_apiship_pickup_type');
		}  

		if (isset($this->request->post['shipping_apiship_use_fix_product_assessed_cost'])) {
			$data['shipping_apiship_use_fix_product_assessed_cost'] = $this->request->post['shipping_apiship_use_fix_product_assessed_cost'];
		} else {
			$data['shipping_apiship_use_fix_product_assessed_cost'] = $this->config->get('shipping_apiship_use_fix_product_assessed_cost');
		}  

		if (isset($this->request->post['shipping_apiship_fix_product_assessed_cost'])) {
			$data['shipping_apiship_fix_product_assessed_cost'] = $this->request->post['shipping_apiship_fix_product_assessed_cost'];
		} else {
			$data['shipping_apiship_fix_product_assessed_cost'] = $this->config->get('shipping_apiship_fix_product_assessed_cost');
		}  

		if (isset($this->request->post['shipping_apiship_add_pickup_date'])) {
			$data['shipping_apiship_add_pickup_date'] = $this->request->post['shipping_apiship_add_pickup_date'];
		} elseif (null !== $this->config->get('shipping_apiship_add_pickup_date')) {
			$data['shipping_apiship_add_pickup_date'] = $this->config->get('shipping_apiship_add_pickup_date');
		} else {
			$data['shipping_apiship_add_pickup_date'] = 1;
		}  


		if (isset($this->request->post['shipping_apiship_paid_orders'])) {
			$data['shipping_apiship_paid_orders'] = $this->request->post['shipping_apiship_paid_orders'];
		} elseif ($this->config->get('shipping_apiship_paid_orders')) {
			$data['shipping_apiship_paid_orders'] = $this->config->get('shipping_apiship_paid_orders');
		} else
			$data['shipping_apiship_paid_orders'] = [];

		if (isset($this->request->post['shipping_apiship_cash_on_delivery_payment_methods'])) {
			$data['shipping_apiship_cash_on_delivery_payment_methods'] = $this->request->post['shipping_apiship_cash_on_delivery_payment_methods'];
		} elseif ($this->config->get('shipping_apiship_cash_on_delivery_payment_methods')) {
			$data['shipping_apiship_cash_on_delivery_payment_methods'] = $this->config->get('shipping_apiship_cash_on_delivery_payment_methods');
		} else
			$data['shipping_apiship_cash_on_delivery_payment_methods'] = [];

		if (isset($this->request->post['shipping_apiship_cron_key'])) {
			$data['shipping_apiship_cron_key'] = $this->request->post['shipping_apiship_cron_key'];
		} elseif ( $this->config->get('shipping_apiship_cron_key') ) {
			$data['shipping_apiship_cron_key'] = $this->config->get('shipping_apiship_cron_key');
		} else {
			$data['shipping_apiship_cron_key'] = $this->generateRandomString();
		} 

            // errors
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['error_shipping_apiship_rub_select'])) {
			$data['error_shipping_apiship_rub_select'] = $this->error['error_shipping_apiship_rub_select'];
		} else {
			$data['error_shipping_apiship_rub_select'] = '';
		}

		if (isset($this->error['error_shipping_apiship_gr_select'])) {
			$data['error_shipping_apiship_gr_select'] = $this->error['error_shipping_apiship_gr_select'];
		} else {
			$data['error_shipping_apiship_gr_select'] = '';
		}

		if (isset($this->error['error_shipping_apiship_cm_select'])) {
			$data['error_shipping_apiship_cm_select'] = $this->error['error_shipping_apiship_cm_select'];
		} else {
			$data['error_shipping_apiship_cm_select'] = '';
		}

		if (isset($this->error['error_shipping_apiship_token'])) {
			$data['error_shipping_apiship_token'] = $this->error['error_shipping_apiship_token'];
		} else {
			$data['error_shipping_apiship_token'] = '';
		}

		if (isset($this->error['error_shipping_apiship_sending_region'])) {
			$data['error_shipping_apiship_sending_region'] = $this->error['error_shipping_apiship_sending_region'];
		} else {
			$data['error_shipping_apiship_sending_region'] = '';
		}

		if (isset($this->error['error_shipping_apiship_sending_city'])) {
			$data['error_shipping_apiship_sending_city'] = $this->error['error_shipping_apiship_sending_city'];
		} else {
			$data['error_shipping_apiship_sending_city'] = '';
		}

		if (isset($this->error['error_shipping_apiship_sending_street'])) {
			$data['error_shipping_apiship_sending_street'] = $this->error['error_shipping_apiship_sending_street'];
		} else {
			$data['error_shipping_apiship_sending_street'] = '';
		}

		if (isset($this->error['error_shipping_apiship_sending_house'])) {
			$data['error_shipping_apiship_sending_house'] = $this->error['error_shipping_apiship_sending_house'];
		} else {
			$data['error_shipping_apiship_sending_house'] = '';
		}

		if (isset($this->error['error_shipping_apiship_contact_organization'])) {
			$data['error_shipping_apiship_contact_organization'] = $this->error['error_shipping_apiship_contact_organization'];
		} else {
			$data['error_shipping_apiship_contact_organization'] = '';
		}

		if (isset($this->error['error_shipping_apiship_contact_inn'])) {
			$data['error_shipping_apiship_contact_inn'] = $this->error['error_shipping_apiship_contact_inn'];
		} else {
			$data['error_shipping_apiship_contact_inn'] = '';
		}

		if (isset($this->error['error_shipping_apiship_contact_name'])) {
			$data['error_shipping_apiship_contact_name'] = $this->error['error_shipping_apiship_contact_name'];
		} else {
			$data['error_shipping_apiship_contact_name'] = '';
		}

		if (isset($this->error['error_shipping_apiship_contact_phone'])) {
			$data['error_shipping_apiship_contact_phone'] = $this->error['error_shipping_apiship_contact_phone'];
		} else {
			$data['error_shipping_apiship_contact_phone'] = '';
		}

		if (isset($this->error['error_shipping_apiship_contact_email'])) {
			$data['error_shipping_apiship_contact_email'] = $this->error['error_shipping_apiship_contact_email'];
		} else {
			$data['error_shipping_apiship_contact_email'] = '';
		}

		if (isset($this->error['error_shipping_apiship_parcel_length'])) {
			$data['error_shipping_apiship_parcel_length'] = $this->error['error_shipping_apiship_parcel_length'];
		} else {
			$data['error_shipping_apiship_parcel_length'] = '';
		}

		if (isset($this->error['error_shipping_apiship_parcel_width'])) {
			$data['error_shipping_apiship_parcel_width'] = $this->error['error_shipping_apiship_parcel_width'];
		} else {
			$data['error_shipping_apiship_parcel_width'] = '';
		}

		if (isset($this->error['error_shipping_apiship_parcel_height'])) {
			$data['error_shipping_apiship_parcel_height'] = $this->error['error_shipping_apiship_parcel_height'];
		} else {
			$data['error_shipping_apiship_parcel_height'] = '';
		}

		if (isset($this->error['error_shipping_apiship_parcel_weight'])) {
			$data['error_shipping_apiship_parcel_weight'] = $this->error['error_shipping_apiship_parcel_weight'];
		} else {
			$data['error_shipping_apiship_parcel_weight'] = '';
		}
		
		if (isset($this->error['error_shipping_apiship_export_status'])) {
			$data['error_shipping_apiship_export_status'] = $this->error['error_shipping_apiship_export_status'];
		} else {
			$data['error_shipping_apiship_export_status'] = '';
		}

		if (isset($this->error['error_shipping_apiship_cancel_export_status'])) {
			$data['error_shipping_apiship_cancel_export_status'] = $this->error['error_shipping_apiship_cancel_export_status'];
		} else {
			$data['error_shipping_apiship_cancel_export_status'] = '';
		}

		if (isset($this->error['error_shipping_apiship_group_export_status_ready'])) {
			$data['error_shipping_apiship_group_export_status_ready'] = $this->error['error_shipping_apiship_group_export_status_ready'];
		} else {
			$data['error_shipping_apiship_group_export_status_ready'] = '';
		}

		if (isset($this->error['error_shipping_apiship_group_export_status_ok'])) {
			$data['error_shipping_apiship_group_export_status_ok'] = $this->error['error_shipping_apiship_group_export_status_ok'];
		} else {
			$data['error_shipping_apiship_group_export_status_ok'] = '';
		}

		if (isset($this->error['error_shipping_apiship_group_export_status_error'])) {
			$data['error_shipping_apiship_group_export_status_error'] = $this->error['error_shipping_apiship_group_export_status_error'];
		} else {
			$data['error_shipping_apiship_group_export_status_error'] = '';
		}

		$data['action'] = $this->url->link('shipping/apiship', 'token=' . $this->session->data['token'], 'SSL');
		
		$data['cancel'] = $this->url->link('extension/shipping', 'token=' . $this->session->data['token'], 'SSL');

		$data['shipping_apiship_import_cron_url'] = (($this->request->server['HTTPS'])?HTTPS_CATALOG:HTTP_CATALOG) . "index.php?route=shipping/apiship/import_orders";
		$data['shipping_apiship_export_cron_url'] = (($this->request->server['HTTPS'])?HTTPS_CATALOG:HTTP_CATALOG) . "index.php?route=shipping/apiship/export_orders";


		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('shipping/apiship.tpl', $data));

	}
		
	private function validate() {
		if (!$this->user->hasPermission('modify', 'shipping/apiship')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if (empty($this->request->post['shipping_apiship_rub_select'])) {
			$this->error['error_shipping_apiship_rub_select'] = $this->language->get('error_shipping_apiship_rub_select');
		}
		
		if (empty($this->request->post['shipping_apiship_gr_select'])) {
			$this->error['error_shipping_apiship_gr_select'] = $this->language->get('error_shipping_apiship_gr_select');
		}

		if (empty($this->request->post['shipping_apiship_cm_select'])) {
			$this->error['error_shipping_apiship_cm_select'] = $this->language->get('error_shipping_apiship_cm_select');
		}

		if (!$this->request->post['shipping_apiship_token']) {
			$this->error['error_shipping_apiship_token'] = $this->language->get('error_shipping_apiship_token');
		}

		if (!$this->request->post['shipping_apiship_sending_region']) {
			$this->error['error_shipping_apiship_sending_region'] = $this->language->get('error_shipping_apiship_sending_region');
		}

		if (!$this->request->post['shipping_apiship_sending_city']) {
			$this->error['error_shipping_apiship_sending_city'] = $this->language->get('error_shipping_apiship_sending_city');
		}

		if (!$this->request->post['shipping_apiship_sending_street']) {
			$this->error['error_shipping_apiship_sending_street'] = $this->language->get('error_shipping_apiship_sending_street');
		}

		if (!$this->request->post['shipping_apiship_sending_house']) {
			$this->error['error_shipping_apiship_sending_house'] = $this->language->get('error_shipping_apiship_sending_house');
		}

		if (!$this->request->post['shipping_apiship_contact_organization']) {
			$this->error['error_shipping_apiship_contact_organization'] = $this->language->get('error_shipping_apiship_contact_organization');
		}

		//if (!$this->request->post['shipping_apiship_contact_inn']) {
		//	$this->error['error_shipping_apiship_contact_inn'] = $this->language->get('error_shipping_apiship_contact_inn');
		//}

		if (!$this->request->post['shipping_apiship_contact_name']) {
			$this->error['error_shipping_apiship_contact_name'] = $this->language->get('error_shipping_apiship_contact_name');
		}

		if (!$this->request->post['shipping_apiship_contact_phone']) {
			$this->error['error_shipping_apiship_contact_phone'] = $this->language->get('error_shipping_apiship_contact_phone');
		}

		if (!$this->request->post['shipping_apiship_contact_email']) {
			$this->error['error_shipping_apiship_contact_email'] = $this->language->get('error_shipping_apiship_contact_email');
		}

		if (!$this->request->post['shipping_apiship_parcel_length']) {
			$this->error['error_shipping_apiship_parcel_length'] = $this->language->get('error_shipping_apiship_parcel_length');
		}

		if (!$this->request->post['shipping_apiship_parcel_width']) {
			$this->error['error_shipping_apiship_parcel_width'] = $this->language->get('error_shipping_apiship_parcel_width');
		}

		if (!$this->request->post['shipping_apiship_parcel_height']) {
			$this->error['error_shipping_apiship_parcel_height'] = $this->language->get('error_shipping_apiship_parcel_height');
		}

		if (!$this->request->post['shipping_apiship_parcel_weight']) {
			$this->error['error_shipping_apiship_parcel_weight'] = $this->language->get('error_shipping_apiship_parcel_weight');
		}

		if (empty($this->request->post['shipping_apiship_export_status'])) {
			$this->error['error_shipping_apiship_export_status'] = $this->language->get('error_shipping_apiship_export_status');
		}

		if (empty($this->request->post['shipping_apiship_cancel_export_status'])) {
			$this->error['error_shipping_apiship_cancel_export_status'] = $this->language->get('error_shipping_apiship_cancel_export_status');
		}

		if (empty($this->request->post['shipping_apiship_group_export_status_ready'])) {
			$this->error['error_shipping_apiship_group_export_status_ready'] = $this->language->get('error_shipping_apiship_group_export_status_ready');
		}

		if (empty($this->request->post['shipping_apiship_group_export_status_ok'])) {
			$this->error['error_shipping_apiship_group_export_status_ok'] = $this->language->get('error_shipping_apiship_group_export_status_ok');
		}

		if (empty($this->request->post['shipping_apiship_group_export_status_error'])) {
			$this->error['error_shipping_apiship_group_export_status_error'] = $this->language->get('error_shipping_apiship_group_export_status_error');
		}

		if (!$this->error) {
			return true;
		} else {

			foreach($this->error as $error) {
				$this->error['warning'] = $error;
				break;
			}
			return false;
		}	
	}

	public function install() {
		$this->load->model('shipping/apiship');
		$this->model_shipping_apiship->install();
	}

	private function generateRandomString($length = 10) {
	    	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    	$charactersLength = strlen($characters);
	    	$randomString = '';
	    	for ($i = 0; $i < $length; $i++) {
	        	$randomString .= $characters[rand(0, $charactersLength - 1)];
	    	}
	    	return $randomString;
	}
}