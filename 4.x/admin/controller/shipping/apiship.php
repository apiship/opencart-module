<?php
namespace Opencart\Admin\Controller\Extension\Apiship\Shipping;
/**
 * Class Apiship
 *
 * Настройки модуля доставки ApiShip.
 *
 * @package Opencart\Admin\Controller\Extension\Apiship\Shipping
 */
class Apiship extends \Opencart\System\Engine\Controller {
	public const VERSION = '1.3';

	/**
	 * @var array<string, string>
	 */
	private array $error = [];

	/**
	 * События модуля (trigger → action); code у всех одинаковый, чтобы удалять одним запросом
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function getEvents(): array {
		return [
			[
				'description' => 'ApiShip: вкладка на странице заказа',
				'trigger'     => 'admin/view/sale/order_info/before',
				'action'      => 'extension/apiship/event/order.info'
			],
			[
				'description' => 'ApiShip: поиск ПВЗ при выборе способа доставки в заказе',
				'trigger'     => 'admin/view/sale/order_info/after',
				'action'      => 'extension/apiship/event/order.infoAfter'
			],
			[
				'description' => 'ApiShip: ярлыки и акты в списке заказов',
				'trigger'     => 'admin/view/sale/order/after',
				'action'      => 'extension/apiship/event/order.list'
			],
			[
				'description' => 'ApiShip: карта ПВЗ в чекауте',
				'trigger'     => 'catalog/view/checkout/shipping_method/after',
				'action'      => 'extension/apiship/event/checkout.shippingMethod'
			],
			[
				'description' => 'ApiShip: проверка выбора ПВЗ',
				'trigger'     => 'catalog/controller/checkout/shipping_method.save/after',
				'action'      => 'extension/apiship/event/checkout.shippingMethodSave'
			],
			[
				'description' => 'ApiShip: пересчёт доставки после выбора оплаты (наложенный платёж)',
				'trigger'     => 'catalog/controller/checkout/payment_method.save/after',
				'action'      => 'extension/apiship/event/checkout.paymentMethodSave'
			]
		];
	}

	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/apiship/shipping/apiship');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/apiship/shipping/apiship');

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping')
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/apiship/shipping/apiship', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/apiship/shipping/apiship.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping');
		$data['point_search_url'] = $this->url->link('extension/apiship/shipping/apiship.point', 'user_token=' . $this->session->data['user_token'], true);

		$data['shipping_apiship_version'] = self::VERSION . ' (OpenCart 4.1.x)';
		$data['shipping_apiship_version_js_mod'] = rand();

		$data['shipping_apiship_modes'] = [
			['code' => 'shipping_apiship_mode_normal', 'code_text' => $this->language->get('entry_shipping_apiship_mode_normal')],
			['code' => 'shipping_apiship_mode_debug', 'code_text' => $this->language->get('entry_shipping_apiship_mode_debug')]
		];

		$data['shipping_apiship_countries'] = [
			['code' => 'RU', 'code_text' => $this->language->get('entry_country_ru')],
			['code' => 'KZ', 'code_text' => $this->language->get('entry_country_kz')],
			['code' => 'BY', 'code_text' => $this->language->get('entry_country_by')],
			['code' => 'KG', 'code_text' => $this->language->get('entry_country_kg')],
			['code' => 'AM', 'code_text' => $this->language->get('entry_country_am')]
		];

		$data['shipping_apiship_pickup_types'] = [
			['code' => 1, 'code_text' => $this->language->get('entry_shipping_apiship_pickup_type1')],
			['code' => 2, 'code_text' => $this->language->get('entry_shipping_apiship_pickup_type2')]
		];

		$data['shipping_apiship_pickup_dates'] = [
			['code' => 0, 'code_text' => $this->language->get('entry_shipping_apiship_add_pickup_date0')],
			['code' => 1, 'code_text' => $this->language->get('entry_shipping_apiship_add_pickup_date1')]
		];

		$this->load->language('catalog/product', 'product');

		$data['shipping_apiship_articul_modes'] = [
			['code' => 'model', 'code_text' => $this->language->get('product_entry_model')],
			['code' => 'sku', 'code_text' => $this->language->get('product_entry_sku')],
			['code' => 'upc', 'code_text' => $this->language->get('product_entry_upc')],
			['code' => 'ean', 'code_text' => $this->language->get('product_entry_ean')],
			['code' => 'jan', 'code_text' => $this->language->get('product_entry_jan')],
			['code' => 'isbn', 'code_text' => $this->language->get('product_entry_isbn')],
			['code' => 'mpn', 'code_text' => $this->language->get('product_entry_mpn')]
		];

		$this->load->model('localisation/currency');

		$data['currencies'] = $this->model_localisation_currency->getCurrencies();

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		$this->load->model('localisation/tax_class');

		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

		$this->load->model('localisation/weight_class');

		$data['weight_classes'] = $this->model_localisation_weight_class->getWeightClasses();

		$this->load->model('localisation/length_class');

		$data['length_classes'] = $this->model_localisation_length_class->getLengthClasses();

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$data['payment_methods'] = $this->model_extension_apiship_shipping_apiship->get_payment_methods();

		// Настройки: значение из конфига либо значение по умолчанию
		$defaults = [
			'shipping_apiship_rub_select'                       => '',
			'shipping_apiship_gr_select'                        => '',
			'shipping_apiship_cm_select'                        => '',
			'shipping_apiship_token'                            => '',
			'shipping_apiship_title'                            => 'ApiShip',
			'shipping_apiship_title_point_template'             => $this->language->get('text_shipping_apiship_title_point_template'),
			'shipping_apiship_description_point_template'       => '',
			'shipping_apiship_title_door_template'              => $this->language->get('text_shipping_apiship_title_door_template'),
			'shipping_apiship_description_door_template'        => '',
			'shipping_apiship_custom_code'                      => '',
			'shipping_apiship_include_fees'                     => 0,
			'shipping_apiship_group_points'                     => 0,
			'shipping_apiship_icon_show'                        => 0,
			'shipping_apiship_sending_country_code'             => 'RU',
			'shipping_apiship_sending_region'                   => '',
			'shipping_apiship_sending_city'                     => '',
			'shipping_apiship_sending_street'                   => '',
			'shipping_apiship_sending_house'                    => '',
			'shipping_apiship_sending_block'                    => '',
			'shipping_apiship_sending_office'                   => '',
			'shipping_apiship_contact_organization'             => '',
			'shipping_apiship_contact_inn'                      => '',
			'shipping_apiship_contact_name'                     => '',
			'shipping_apiship_contact_phone'                    => '',
			'shipping_apiship_contact_email'                    => '',
			'shipping_apiship_parcel_length'                    => 10,
			'shipping_apiship_parcel_width'                     => 10,
			'shipping_apiship_parcel_height'                    => 10,
			'shipping_apiship_parcel_weight'                    => 500,
			'shipping_apiship_place_length'                     => '',
			'shipping_apiship_place_width'                      => '',
			'shipping_apiship_place_height'                     => '',
			'shipping_apiship_place_weight'                     => '',
			'shipping_apiship_package_weight'                   => '',
			'shipping_apiship_articul_mode'                     => '',
			'shipping_apiship_tax_class_id'                     => 0,
			'shipping_apiship_geo_zone_id'                      => 0,
			'shipping_apiship_prefix'                           => '',
			'shipping_apiship_error_stub_show'                  => 0,
			'shipping_apiship_use_fix_product_assessed_cost'    => 0,
			'shipping_apiship_fix_product_assessed_cost'        => '',
			'shipping_apiship_add_pickup_date'                  => 1,
			'shipping_apiship_export_status'                    => '',
			'shipping_apiship_cancel_export_status'             => '',
			'shipping_apiship_group_export_status_ready'        => '',
			'shipping_apiship_group_export_status_ok'           => '',
			'shipping_apiship_group_export_status_error'        => '',
			'shipping_apiship_cron_key'                         => '',
			'shipping_apiship_yandex_api_key'                   => '',
			'shipping_apiship_sort_order'                       => '',
			'shipping_apiship_mode'                             => 'shipping_apiship_mode_normal',
			'shipping_apiship_status'                           => 0,
			'shipping_apiship_provider'                         => [],
			'shipping_apiship_mapping_status'                   => [],
			'shipping_apiship_paid_orders'                      => [],
			'shipping_apiship_cash_on_delivery_payment_methods' => []
		];

		foreach ($defaults as $key => $default) {
			$value = $this->config->get($key);

			if ($value === null || (is_array($default) && !is_array($value))) {
				$value = $default;
			}

			$data[$key] = $value;
		}

		// Пустые значения из старых сохранений
		foreach (['shipping_apiship_parcel_length', 'shipping_apiship_parcel_width', 'shipping_apiship_parcel_height', 'shipping_apiship_parcel_weight'] as $key) {
			if (!$data[$key]) {
				$data[$key] = $defaults[$key];
			}
		}

		foreach (['shipping_apiship_title_point_template', 'shipping_apiship_title_door_template'] as $key) {
			if ($data[$key] == '') {
				$data[$key] = $defaults[$key];
			}
		}

		if ($data['shipping_apiship_cron_key'] == '') {
			$data['shipping_apiship_cron_key'] = $this->generateRandomString();
		}

		$data['shipping_apiship_providers'] = [];
		$data['shipping_apiship_providers_points'] = [];
		$data['shipping_apiship_integrator_statuses'] = [];
		$data['error_warning'] = '';

		if ($data['shipping_apiship_token'] != '') {
			$apiship_providers = $this->model_extension_apiship_shipping_apiship->get_providers();

			if (!empty($apiship_providers['message'])) {
				$data['error_warning'] = $apiship_providers['message'];
			}

			$data['shipping_apiship_providers'] = $apiship_providers['providers'];
			$data['shipping_apiship_providers_points'] = $this->model_extension_apiship_shipping_apiship->get_providers_points();
			$data['shipping_apiship_integrator_statuses'] = $this->model_extension_apiship_shipping_apiship->get_integrator_statuses();
		}

		$data['shipping_apiship_import_cron_url'] = HTTP_CATALOG . 'index.php?route=extension/apiship/shipping/apiship.import_orders';
		$data['shipping_apiship_export_cron_url'] = HTTP_CATALOG . 'index.php?route=extension/apiship/shipping/apiship.export_orders';

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/apiship/shipping/apiship', $data));
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/apiship/shipping/apiship');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/apiship/shipping/apiship')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$required = [
			'rub_select',
			'gr_select',
			'cm_select',
			'token',
			'sending_region',
			'sending_city',
			'sending_street',
			'sending_house',
			'contact_organization',
			'contact_name',
			'contact_phone',
			'contact_email',
			'parcel_length',
			'parcel_width',
			'parcel_height',
			'parcel_weight',
			'export_status',
			'cancel_export_status',
			'group_export_status_ready',
			'group_export_status_ok',
			'group_export_status_error'
		];

		foreach ($required as $field) {
			if (empty($this->request->post['shipping_apiship_' . $field])) {
				$json['error'][$field] = $this->language->get('error_shipping_apiship_' . $field);
			}
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_shipping_apiship_fields_filled');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('shipping_apiship', $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Поиск точек привоза (select2)
	 *
	 * @return void
	 */
	public function point(): void {
		$json = [];

		if ($this->user->hasPermission('access', 'extension/apiship/shipping/apiship')) {
			$this->load->model('extension/apiship/shipping/apiship');

			$search = (string)($this->request->get['search'] ?? '');
			$type = (string)($this->request->get['type'] ?? '');

			$json = $this->model_extension_apiship_shipping_apiship->get_point($search, $type);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Install
	 *
	 * @return void
	 */
	public function install(): void {
		if ($this->user->hasPermission('modify', 'extension/shipping')) {
			$this->load->model('extension/apiship/shipping/apiship');

			$this->model_extension_apiship_shipping_apiship->install();

			// Действия с заказом (экспорт, отмена, ярлыки) — отдельный admin-контроллер, право на него выдаём группе установившего
			$this->load->model('user/user_group');

			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/apiship/shipping/order');
			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/apiship/shipping/order');

			// События вместо OCMOD-патчей ядра
			$this->load->model('setting/event');

			$this->model_setting_event->deleteEventByCode('apiship');

			foreach ($this->getEvents() as $event) {
				$this->model_setting_event->addEvent([
					'code'        => 'apiship',
					'description' => $event['description'],
					'trigger'     => $event['trigger'],
					'action'      => $event['action'],
					'status'      => 1,
					'sort_order'  => 1
				]);
			}
		}
	}

	/**
	 * Uninstall
	 *
	 * Удаляются только события модуля. Настройки расширения удаляет ядро OpenCart при uninstall,
	 * таблицы apiship_order и apiship_order_status сохраняются (связки заказов переживают переустановку).
	 *
	 * @return void
	 */
	public function uninstall(): void {
		if ($this->user->hasPermission('modify', 'extension/shipping')) {
			$this->load->model('setting/event');

			$this->model_setting_event->deleteEventByCode('apiship');
		}
	}

	/**
	 * @param int $length
	 *
	 * @return string
	 */
	private function generateRandomString(int $length = 10): string {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';

		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[random_int(0, $charactersLength - 1)];
		}

		return $randomString;
	}
}
