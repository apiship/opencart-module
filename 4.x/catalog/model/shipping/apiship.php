<?php
namespace Opencart\Catalog\Model\Extension\Apiship\Shipping;
/**
 * Class Apiship
 *
 * Can be called from $this->load->model('extension/apiship/shipping/apiship');
 *
 * @package Opencart\Catalog\Model\Extension\Apiship\Shipping
 */
class Apiship extends \Opencart\System\Engine\Model {
	/**
	 * @var \Opencart\System\Library\Extension\Apiship\Apiship
	 */
	private \Opencart\System\Library\Extension\Apiship\Apiship $apiship;
	/**
	 * @var array<string, mixed>
	 */
	private array $apiship_params;

	/**
	 * @param \Opencart\System\Engine\Registry $registry
	 */
	public function __construct(\Opencart\System\Engine\Registry $registry) {
		parent::__construct($registry);

		$this->load->language('extension/apiship/shipping/apiship');

		$config_keys = [
			'shipping_apiship_rub_select',
			'shipping_apiship_gr_select',
			'shipping_apiship_cm_select',
			'shipping_apiship_token',
			'shipping_apiship_contact_organization',
			'shipping_apiship_contact_inn',
			'shipping_apiship_contact_name',
			'shipping_apiship_contact_phone',
			'shipping_apiship_contact_email',
			'shipping_apiship_sending_country_code',
			'shipping_apiship_sending_region',
			'shipping_apiship_sending_city',
			'shipping_apiship_sending_street',
			'shipping_apiship_sending_house',
			'shipping_apiship_sending_block',
			'shipping_apiship_sending_office',
			'shipping_apiship_parcel_length',
			'shipping_apiship_parcel_width',
			'shipping_apiship_parcel_height',
			'shipping_apiship_parcel_weight',
			'shipping_apiship_place_length',
			'shipping_apiship_place_width',
			'shipping_apiship_place_height',
			'shipping_apiship_place_weight',
			'shipping_apiship_package_weight',
			'shipping_apiship_provider',
			'shipping_apiship_mapping_status',
			'shipping_apiship_paid_orders',
			'shipping_apiship_cash_on_delivery_payment_methods',
			'shipping_apiship_sort_order',
			'shipping_apiship_articul_mode',
			'shipping_apiship_tax_class_id',
			'shipping_apiship_export_status',
			'shipping_apiship_cancel_export_status',
			'shipping_apiship_group_export_status_ready',
			'shipping_apiship_group_export_status_ok',
			'shipping_apiship_group_export_status_error',
			'shipping_apiship_mode',
			'shipping_apiship_prefix',
			'shipping_apiship_title',
			'shipping_apiship_custom_code',
			'shipping_apiship_group_points',
			'shipping_apiship_status',
			'shipping_apiship_add_pickup_date',
			'shipping_apiship_use_fix_product_assessed_cost',
			'shipping_apiship_fix_product_assessed_cost',
			'shipping_apiship_geo_zone_id',
			'shipping_apiship_yandex_api_key'
		];

		$this->apiship_params = [];

		foreach ($config_keys as $key) {
			$this->apiship_params[$key] = $this->config->get($key);
		}

		$this->apiship_params['shipping_apiship_error_stub_show'] = (bool)$this->config->get('shipping_apiship_error_stub_show');
		$this->apiship_params['shipping_apiship_icon_show'] = (bool)$this->config->get('shipping_apiship_icon_show');
		$this->apiship_params['shipping_apiship_include_fees'] = $this->config->get('shipping_apiship_include_fees') ? 'true' : 'false';

		$this->apiship_params['shipping_apiship_title_point_template'] = html_entity_decode((string)$this->config->get('shipping_apiship_title_point_template'), ENT_QUOTES, 'UTF-8');
		$this->apiship_params['shipping_apiship_description_point_template'] = html_entity_decode((string)$this->config->get('shipping_apiship_description_point_template'), ENT_QUOTES, 'UTF-8');
		$this->apiship_params['shipping_apiship_title_door_template'] = html_entity_decode((string)$this->config->get('shipping_apiship_title_door_template'), ENT_QUOTES, 'UTF-8');
		$this->apiship_params['shipping_apiship_description_door_template'] = html_entity_decode((string)$this->config->get('shipping_apiship_description_door_template'), ENT_QUOTES, 'UTF-8');

		$language_keys = [
			'shipping_apiship_title_days',
			'shipping_apiship_error_timeout',
			'shipping_apiship_success_export_message',
			'shipping_apiship_success_cancel_message',
			'shipping_apiship_error_params',
			'shipping_apiship_error_calculator',
			'shipping_apiship_error_no_export_order',
			'shipping_apiship_no_shipping',
			'shipping_apiship_change_order_status_message',
			'shipping_apiship_error_select_city'
		];

		foreach ($language_keys as $key) {
			$this->apiship_params[$key] = $this->language->get($key);
		}

		if (!is_array($this->apiship_params['shipping_apiship_paid_orders'])) {
			$this->apiship_params['shipping_apiship_paid_orders'] = [];
		}

		if (!is_array($this->apiship_params['shipping_apiship_cash_on_delivery_payment_methods'])) {
			$this->apiship_params['shipping_apiship_cash_on_delivery_payment_methods'] = [];
		}

		$this->apiship_params['shipping_apiship_data'] = [];
		$this->apiship_params['shipping_apiship_comment'] = [];
		$this->apiship_params['shipping_apiship_providers'] = [];

		if (!class_exists('\Opencart\System\Library\Extension\Apiship\Apiship')) {
			require_once(DIR_EXTENSION . 'apiship/system/library/apiship.php');
		}

		$this->apiship = new \Opencart\System\Library\Extension\Apiship\Apiship($this->registry, $this->apiship_params, $this->log);
	}

	/**
	 * Точка по id
	 *
	 * @param string $id
	 *
	 * @return array<string, mixed>
	 */
	private function apiship_point(string $id): array {
		$data = $this->apiship->apiship_point_by_params(['id=' . $id]);

		return $data[0] ?? [];
	}

	/**
	 * Службы доставки: key => name
	 *
	 * @return array<string, string>
	 */
	public function get_providers(): array {
		if ($this->apiship_params['shipping_apiship_status'] != 1) {
			return [];
		}

		$providers_name = [];

		$providers = $this->apiship->apiship_providers();

		if (isset($providers['message'])) {
			return $providers_name;
		}

		foreach ($providers as $provider) {
			$providers_name[$provider['key']] = $provider['name'];
		}

		return $providers_name;
	}

	/**
	 * @param string $provider_key
	 *
	 * @return string
	 */
	private function get_provider_name(string $provider_key): string {
		if (count($this->apiship_params['shipping_apiship_providers']) == 0) {
			$providers = $this->apiship->apiship_providers();

			$this->apiship_params['shipping_apiship_providers'] = isset($providers['message']) ? [] : $providers;
		}

		foreach ($this->apiship_params['shipping_apiship_providers'] as $provider) {
			if ($provider['key'] == $provider_key) {
				return $provider['name'];
			}
		}

		return '';
	}

	/**
	 * @return string
	 */
	public function get_last_tracing_id(): string {
		return (string)$this->apiship->getData('shipping_apiship_last_tracing_id');
	}

	/**
	 * Сумма заказа для калькулятора (без доставки), в валюте настроек
	 *
	 * @return float
	 */
	private function getCartTotal(): float {
		$totals = [];
		$taxes = $this->cart->getTaxes();
		$total = 0;

		if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
			$this->load->model('checkout/cart');

			($this->model_checkout_cart->getTotals)($totals, $taxes, $total);
		}

		$end_total = 0;
		$shipping_cost = 0;

		foreach ($totals as $total_item) {
			if ($total_item['code'] != 'total' && $total_item['code'] != 'shipping') {
				$end_total += $total_item['value'];
			}

			if ($total_item['code'] == 'shipping') {
				$shipping_cost = $total_item['value'];
			}
		}

		if ($end_total < 0) {
			$end_total += $shipping_cost;
		}

		return $this->currency->convert((float)$end_total, (string)$this->config->get('config_currency'), (string)$this->apiship_params['shipping_apiship_rub_select']);
	}

	/**
	 * @param array<string, mixed> $address
	 *
	 * @return bool
	 */
	private function check_geo_zone(array $address): bool {
		if (empty($this->apiship_params['shipping_apiship_geo_zone_id'])) {
			return true;
		}

		$this->load->model('localisation/geo_zone');

		$results = $this->model_localisation_geo_zone->getGeoZone((int)$this->apiship_params['shipping_apiship_geo_zone_id'], (int)($address['country_id'] ?? 0), (int)($address['zone_id'] ?? 0));

		return (bool)$results;
	}

	/**
	 * @param string $provider
	 *
	 * @return string
	 */
	private function get_image_ref(string $provider): string {
		if ($provider == '') {
			return '<img class="apiship_img_providers" src="' . $this->get_image_url('apiship_map.png') . '">';
		}

		return '<img class="apiship_img_providers" src="https://storage.apiship.ru/icons/providers/svg/' . $provider . '.svg"> ';
	}

	/**
	 * @param string $file
	 *
	 * @return string
	 */
	private function get_image_url(string $file): string {
		return HTTP_SERVER . 'extension/apiship/catalog/view/image/' . $file;
	}

	/**
	 * @param string $code
	 *
	 * @return string
	 */
	private function get_link_text(string $code): string {
		$parce_code = $this->apiship->parce_code($code);

		if ($parce_code['point_id'] == 'error') {
			return $this->language->get('shipping_apiship_select_point');
		}

		return $this->language->get('shipping_apiship_change_point');
	}

	/**
	 * @param string $short_code
	 * @param string $name
	 * @param float  $delivery_cost
	 * @param string $description
	 * @param string $image
	 * @param bool   $with_link
	 *
	 * @return array<string, mixed>
	 */
	private function build_quote(string $short_code, string $name, float $delivery_cost, string $description, string $image, bool $with_link): array {
		$code = 'apiship.' . $short_code;

		$cost = $this->currency->convert($delivery_cost, (string)$this->apiship_params['shipping_apiship_rub_select'], (string)$this->config->get('config_currency'));

		return [
			'code'                => $code,
			'name'                => $name,
			'title'               => $name,
			'cost'                => $cost,
			'tax_class_id'        => (int)$this->apiship_params['shipping_apiship_tax_class_id'],
			'text'                => $this->currency->format($this->tax->calculate($cost, (int)$this->apiship_params['shipping_apiship_tax_class_id'], (bool)$this->config->get('config_tax')), $this->session->data['currency']),
			'description'         => $description,
			'apiship_image'       => $this->apiship_params['shipping_apiship_icon_show'] ? $image : '',
			'apiship_image_class' => $this->apiship_params['shipping_apiship_icon_show'] ? 'apiship_img_providers' : '',
			'apiship_link'        => $with_link ? 'apiship_open(\'' . $code . '\');return false;' : '',
			'apiship_link_class'  => $with_link ? 'apiship_points' : '',
			'apiship_link_text'   => $with_link ? $this->get_link_text($code) : ''
		];
	}

	/**
	 * Заголовок и описание пункта выдачи по элементу расчёта
	 *
	 * @param array<string, mixed> $element   key, tariffName, daysMin, daysMax, deliveryCost, tariffDescription
	 * @param array<string, mixed> $fallback  providerKey, daysMin, daysMax для варианта «ПВЗ не выбран»
	 *
	 * @return array<string, string>
	 */
	private function point_texts(array $element, array $fallback): array {
		$code = 'apiship.' . $element['key'];

		$parce_code = $this->apiship->parce_code($code);

		if ($parce_code['point_id'] != 'error') {
			$point = $this->apiship_point($parce_code['point_id']);

			$params = [
				'type'              => 'point',
				'sub_type'          => $point['type'] ?? '',
				'providerKey'       => $point['providerKey'] ?? $parce_code['provider'],
				'tariffName'        => $element['tariffName'],
				'pointName'         => $point['name'] ?? '',
				'pointAddress'      => $point ? $this->apiship->get_address($point) : '',
				'daysMin'           => $element['daysMin'],
				'daysMax'           => $element['daysMax'],
				'tariffDescription' => $element['tariffDescription'],
				'code'              => $code
			];
		} else {
			$params = [
				'type'    => 'point',
				'daysMin' => $fallback['daysMin'],
				'daysMax' => $fallback['daysMax'],
				'code'    => $code
			];

			if (!empty($fallback['providerKey'])) {
				$params['providerKey'] = $fallback['providerKey'];
			}
		}

		return [
			'name'        => $this->fill_template($params + ['template' => $this->apiship_params['shipping_apiship_title_point_template']]),
			'description' => $this->fill_template($params + ['template' => $this->apiship_params['shipping_apiship_description_point_template']])
		];
	}

	/**
	 * Варианты доставки для чекаута или полный список для API (админка)
	 *
	 * @param array<string, mixed> $address
	 * @param bool                 $full_list
	 * @param string               $search
	 * @param string               $current_code
	 *
	 * @return array<string, mixed>
	 */
	public function get_quote_list(array $address, bool $full_list = false, string $search = '', string $current_code = ''): array {
		if ($this->apiship_params['shipping_apiship_status'] != 1) {
			return [];
		}

		if (!$this->check_geo_zone($address)) {
			return [];
		}

		$quote_data = [];

		$start_points = [];
		$select_points = $this->apiship->getData('shipping_apiship_select_points');

		if (!is_array($select_points)) {
			$select_points = [];
		}

		$region = isset($address['zone']) ? (string)$address['zone'] : '';
		$city = isset($address['city']) ? trim((string)$address['city']) : '';
		$postcode = isset($address['postcode']) ? trim((string)$address['postcode']) : '';
		$ext_address = isset($address['address_1']) ? trim((string)$address['address_1']) : '';
		$country = isset($address['iso_code_2']) ? trim((string)$address['iso_code_2']) : '';

		$cash_on_delivery = $this->is_cash_on_delivery();

		$apiship_calculator_data = $this->apiship->apiship_calculator($country, $region, $city, $postcode, $ext_address, [], $this->cart->getProducts(), $this->getCartTotal(), $cash_on_delivery);

		$data = $apiship_calculator_data['body'];

		$this->apiship->setData('shipping_apiship_last_tracing_id', $apiship_calculator_data['x-tracing-id']);
		$this->apiship->setData('shipping_apiship_region', $region);
		$this->apiship->setData('shipping_apiship_city', $city);
		$this->apiship->setData('shipping_apiship_postcode', $postcode);
		$this->apiship->setData('shipping_apiship_ext_address', $ext_address);
		$this->apiship->setData('shipping_apiship_country', $country);

		if (!$full_list) {
			$daysMin = [];
			$daysMax = [];
			$daysMinAllPoints = 0;
			$daysMaxAllPoints = 0;

			$providers = $data['deliveryToPoint'] ?? [];

			foreach ($providers as $provider) {
				$tariffs = $provider['tariffs'] ?? [];

				foreach ($tariffs as $tariff) {
					foreach ($tariff['pointIds'] as $point_id) {
						foreach ($this->tariff_pickup_types($tariff, $provider['providerKey']) as $pickup_type) {

							if (!isset($tariff['tariffDescription'])) {
								$tariff['tariffDescription'] = '';
							}

							if (empty($daysMin[$provider['providerKey']]) || $tariff['daysMin'] < $daysMin[$provider['providerKey']]) {
								$daysMin[$provider['providerKey']] = $tariff['daysMin'];
							}

							if (empty($daysMax[$provider['providerKey']]) || $tariff['daysMax'] > $daysMax[$provider['providerKey']]) {
								$daysMax[$provider['providerKey']] = $tariff['daysMax'];
							}

							if (empty($daysMinAllPoints) || $tariff['daysMin'] < $daysMinAllPoints) {
								$daysMinAllPoints = $tariff['daysMin'];
							}

							if (empty($daysMaxAllPoints) || $tariff['daysMax'] > $daysMaxAllPoints) {
								$daysMaxAllPoints = $tariff['daysMax'];
							}

							$element = [
								'tariffName'        => $tariff['tariffName'],
								'daysMin'           => $tariff['daysMin'],
								'daysMax'           => $tariff['daysMax'],
								'deliveryCost'      => $tariff['deliveryCost'],
								'tariffDescription' => $tariff['tariffDescription']
							];

							// Самый дешёвый тариф службы доставки без выбранной точки
							$key = 'point_' . $provider['providerKey'] . '_' . $tariff['tariffId'] . '_error_' . $pickup_type;

							if (empty($start_points[$provider['providerKey']])) {
								$start_points[$provider['providerKey']] = ['key' => $key] + $element;
							} elseif ($tariff['deliveryCost'] < $start_points[$provider['providerKey']]['deliveryCost'] && strpos($start_points[$provider['providerKey']]['key'], 'error') !== false) {
								$start_points[$provider['providerKey']] = ['key' => $key] + $element;
							}

							// Ранее выбранная точка
							$key = 'point_' . $provider['providerKey'] . '_' . $tariff['tariffId'] . '_' . $point_id . '_' . $pickup_type;

							if (isset($select_points[$provider['providerKey']]['code']) && 'apiship.' . $key == $select_points[$provider['providerKey']]['code']) {
								$start_points[$provider['providerKey']] = ['key' => $key] + $element;
							}
						}
					}
				}
			}

			if ($this->apiship_params['shipping_apiship_group_points']) {
				// Все ПВЗ на одной карте: один вариант доставки
				usort($start_points, function($a, $b) {
					return $a['deliveryCost'] <=> $b['deliveryCost'];
				});

				$fallback = ['daysMin' => $daysMinAllPoints, 'daysMax' => $daysMaxAllPoints];

				$shipping_apiship_last_select_code = $this->apiship->getData('shipping_apiship_last_select_code');

				foreach ($start_points as $element) {
					if (isset($shipping_apiship_last_select_code) && 'apiship.' . $element['key'] == $shipping_apiship_last_select_code) {
						$parce_code = $this->apiship->parce_code('apiship.' . $element['key']);

						$texts = $this->point_texts($element, $fallback);

						$image = 'https://storage.apiship.ru/icons/providers/svg/' . $parce_code['provider'] . '.svg';

						$quote_data[$element['key']] = $this->build_quote($element['key'], $texts['name'], (float)$element['deliveryCost'], $texts['description'], $image, true);

						break;
					}
				}

				if (empty($quote_data)) {
					foreach ($start_points as $element) {
						$texts = $this->point_texts($element, $fallback);

						$image = $this->get_image_url('apiship_map.png');

						$quote_data[$element['key']] = $this->build_quote($element['key'], $texts['name'], (float)$element['deliveryCost'], $texts['description'], $image, true);

						break;
					}
				}
			} else {
				// Для каждой службы доставки своя карта
				foreach ($start_points as $provider_key => $element) {
					$fallback = [
						'providerKey' => $provider_key,
						'daysMin'     => $daysMin[$provider_key] ?? 0,
						'daysMax'     => $daysMax[$provider_key] ?? 0
					];

					$texts = $this->point_texts($element, $fallback);

					$image = 'https://storage.apiship.ru/icons/providers/svg/' . $provider_key . '.svg';

					$quote_data[$element['key']] = $this->build_quote($element['key'], $texts['name'], (float)$element['deliveryCost'], $texts['description'], $image, true);
				}
			}
		} else {
			// Полный список ПВЗ для редактирования заказа в админке
			$points_data = $this->get_points_array($country, $region, $city, $postcode, $ext_address);

			if ($points_data['error'] == 'no_error') {
				usort($points_data['points'], function($a, $b) {
					return strcmp($a['name'], $b['name']);
				});

				$limit = 10;
				$count = 0;

				foreach ($points_data['points'] as $point) {
					$parce_code = $this->apiship->parce_code($point['code']);

					$is_current = ($current_code != '' && $point['code'] == $current_code);

					if (!$is_current) {
						if ($search != '' && mb_stripos($point['name'], $search) === false) {
							continue;
						}

						if ($count >= $limit) {
							continue;
						}

						$count++;
					}

					$quote_data[$parce_code['short_code']] = $this->build_quote($parce_code['short_code'], $point['name'], (float)$point['cost_value'], '', '', false);
				}
			}
		}

		$providers = $data['deliveryToDoor'] ?? [];

		foreach ($providers as $provider) {
			$tariffs = $provider['tariffs'] ?? [];

			foreach ($tariffs as $tariff) {
				foreach ($this->tariff_pickup_types($tariff, $provider['providerKey']) as $pickup_type) {

					if (!isset($tariff['tariffDescription'])) {
						$tariff['tariffDescription'] = '';
					}

					$key = 'door_' . $provider['providerKey'] . '_' . $tariff['tariffId'] . '_' . $pickup_type;

					$code = 'apiship.' . $key;

					$params = [
						'type'              => 'door',
						'providerKey'       => $provider['providerKey'],
						'tariffName'        => $tariff['tariffName'],
						'daysMin'           => $tariff['daysMin'],
						'daysMax'           => $tariff['daysMax'],
						'tariffDescription' => $tariff['tariffDescription'],
						'code'              => $code
					];

					$name = $this->fill_template($params + ['template' => $this->apiship_params['shipping_apiship_title_door_template']]);
					$description = $this->fill_template($params + ['template' => $this->apiship_params['shipping_apiship_description_door_template']]);

					$image = 'https://storage.apiship.ru/icons/providers/svg/' . $provider['providerKey'] . '.svg';

					$quote_data[$key] = $this->build_quote($key, $name, (float)$tariff['deliveryCost'], $description, $image, false);
				}
			}
		}

		if ($this->apiship_params['shipping_apiship_error_stub_show'] && empty($quote_data)) {
			// нет данных, потому что таймаут
			$title = $this->apiship_params['shipping_apiship_error_timeout'];

			// нет данных, потому что ошибка
			if (isset($data['message'])) {
				$title = $data['message'];

				foreach ($data['errors'] ?? [] as $error) {
					$title .= ', ' . $error['message'];
				}
			}

			// нет данных, потому что поиск не нашел
			if (isset($data['deliveryToPoint']) || isset($data['deliveryToDoor'])) {
				$title = sprintf($this->apiship_params['shipping_apiship_no_shipping'], $city . ', ' . $region);
			}

			$quote_data['error'] = [
				'code'         => 'apiship.error',
				'name'         => $title,
				'title'        => $title,
				'cost'         => 0,
				'tax_class_id' => (int)$this->apiship_params['shipping_apiship_tax_class_id'],
				'text'         => $this->currency->format(0, $this->session->data['currency'])
			];
		}

		$method_data = [];

		if ($quote_data) {
			$method_data = [
				'code'       => 'apiship',
				'name'       => $this->apiship_params['shipping_apiship_title'] ?: $this->language->get('shipping_apiship_title'),
				'title'      => $this->apiship_params['shipping_apiship_title'] ?: $this->language->get('shipping_apiship_title'),
				'quote'      => $quote_data,
				'sort_order' => (int)$this->apiship_params['shipping_apiship_sort_order'],
				'error'      => false
			];
		}

		return $method_data;
	}

	/**
	 * Подстановка переменных шаблона заголовка/описания
	 *
	 * @param array<string, mixed> $params template, type, sub_type, providerKey, tariffName, pointName, pointAddress, daysMin, daysMax, tariffDescription, code
	 *
	 * @return string
	 */
	private function fill_template(array $params): string {
		$type = $params['type'] ?? '';
		$sub_type = $params['sub_type'] ?? '';
		$providerKey = (string)($params['providerKey'] ?? '');
		$tariffName = isset($params['tariffName']) ? sprintf($this->language->get('shipping_apiship_tariff_template'), $params['tariffName']) : '';
		$pointName = $params['pointName'] ?? '';
		$pointAddress = $params['pointAddress'] ?? '';
		$daysMin = $params['daysMin'] ?? '';
		$daysMax = $params['daysMax'] ?? '';
		$tariffDescription = $params['tariffDescription'] ?? '';
		$code = $params['code'] ?? '';

		$template = (string)($params['template'] ?? '');

		$type_name = '';

		if ($type == 'door') {
			$type_name = $this->language->get('shipping_apiship_door');
		}

		if ($type == 'point') {
			$type_name = $this->language->get('shipping_apiship_point');
		}

		if ($sub_type == 1) {
			$type_name .= $this->language->get('shipping_apiship_point_1');
		}

		if ($sub_type == 2) {
			$type_name .= $this->language->get('shipping_apiship_point_2');
		}

		if ($sub_type == 3) {
			$type_name .= $this->language->get('shipping_apiship_point_3');
		}

		if ($sub_type == 4) {
			$type_name .= $this->language->get('shipping_apiship_point_4');
		}

		$time = $daysMin . '-' . $daysMax . $this->apiship_params['shipping_apiship_title_days'];

		if ($daysMin == $daysMax) {
			$time = $daysMin . $this->apiship_params['shipping_apiship_title_days'];
		}

		if ($daysMin == 0) {
			$time = '';
		}

		$loading_html = '<div id="apiship_loading_' . $code . '" class="apiship_loading" style="visibility:hidden;"></div>';

		$template_ar = [
			'%type'        => $type_name,
			'%company'     => $this->get_provider_name($providerKey),
			'%name'        => $pointName,
			'%address'     => $pointAddress,
			'%tariff'      => $tariffName,
			'%time'        => $time,
			'%description' => $tariffDescription,
			'%logo'        => $this->get_image_ref($providerKey),
			'%link'        => '<a class="apiship_points" href="#" onclick="apiship_open(\'' . $code . '\');return false;">' . $this->get_link_text($code) . $loading_html . '</a>'
		];

		return str_replace(array_keys($template_ar), array_values($template_ar), $template);
	}

	/**
	 * Все ПВЗ по адресу с тарифами (для карты и полного списка)
	 *
	 * @param string             $country
	 * @param string             $region
	 * @param string             $city
	 * @param string             $postcode
	 * @param string             $ext_address
	 * @param array<int, string> $provider
	 *
	 * @return array<string, mixed>
	 */
	private function get_points_array(string $country, string $region, string $city, string $postcode, string $ext_address, array $provider = []): array {
		$this->apiship->toLog('get_points_array', [
			'country'     => $country,
			'region'      => $region,
			'city'        => $city,
			'postcode'    => $postcode,
			'ext_address' => $ext_address,
			'provider'    => $provider
		]);

		$data_points = [];
		$all_points = [];
		$apiship_providers = [];

		$products = $this->cart->getProducts();

		if (empty($products)) {
			return ['error' => 'no_products', 'points' => []];
		}

		if (empty($city)) {
			return ['error' => 'no_city', 'points' => []];
		}

		$apiship_providers_data = $this->apiship->apiship_providers();

		if (!isset($apiship_providers_data['message'])) {
			foreach ($apiship_providers_data as $apiship_provider) {
				$apiship_providers[$apiship_provider['key']] = $apiship_provider['name'];
			}
		}

		$apiship_point_types = [
			1 => $this->language->get('shipping_apiship_map_type_1'),
			2 => $this->language->get('shipping_apiship_map_type_2'),
			3 => $this->language->get('shipping_apiship_map_type_3'),
			4 => $this->language->get('shipping_apiship_map_type_4')
		];

		$cash_on_delivery = $this->is_cash_on_delivery();

		$apiship_calculator_data = $this->apiship->apiship_calculator($country, $region, $city, $postcode, $ext_address, $provider, $products, $this->getCartTotal(), $cash_on_delivery);

		$data = $apiship_calculator_data['body'];

		$points_ids = [];

		$providers = $data['deliveryToPoint'] ?? [];

		foreach ($providers as $provider) {
			$tariffs = $provider['tariffs'] ?? [];

			foreach ($tariffs as $tariff) {
				foreach ($this->tariff_pickup_types($tariff, $provider['providerKey']) as $pickup_type) {

					foreach ($tariff['pointIds'] as $point_id) {
						if (!in_array($point_id, $points_ids)) {
							$points_ids[] = $point_id;
						}
					}
				}
			}
		}

		$points = $this->apiship->apiship_points($points_ids);

		// Калькулятор вернул ПВЗ, а справочник точек — нет (ошибка или таймаут lists/points): это ошибка, а не пустая карта
		if ($points_ids && !$points) {
			return ['error' => $this->language->get('shipping_apiship_error_no_points'), 'points' => []];
		}

		foreach ($points as $point) {
			$description = str_replace(["\r\n", "\r", "\n"], '', strip_tags((string)($point['description'] ?? '')));

			$data_points[$point['id']] = [
				'address'      => $this->apiship->get_address($point),
				'note'         => $description,
				'lon'          => $point['lng'],
				'lat'          => $point['lat'],
				'name'         => $point['name'],
				'city'         => $point['city'],
				'tax_class_id' => (int)$this->apiship_params['shipping_apiship_tax_class_id'],
				'type'         => $point['type'],
				'phone'        => $point['phone'] ?? '',
				'workTime'     => $point['timetable'] ?? '',
				'paymentCash'  => $point['paymentCash'] ?? 0,
				'paymentCard'  => $point['paymentCard'] ?? 0
			];
		}

		foreach ($providers as $provider) {
			$tariffs = $provider['tariffs'] ?? [];

			foreach ($tariffs as $tariff) {
				if (!isset($tariff['tariffDescription'])) {
					$tariff['tariffDescription'] = '';
				}

				foreach ($tariff['pointIds'] as $point_id) {
					foreach ($this->tariff_pickup_types($tariff, $provider['providerKey']) as $pickup_type) {

						$code = 'point_' . $provider['providerKey'] . '_' . $tariff['tariffId'] . '_' . $point_id . '_' . $pickup_type;

						if (!isset($data_points[$point_id])) {
							continue;
						}

						$point = $data_points[$point_id];

						$cost = $this->currency->convert((float)$tariff['deliveryCost'], (string)$this->apiship_params['shipping_apiship_rub_select'], (string)$this->config->get('config_currency'));
						$cost_with_tax = $this->tax->calculate($cost, (int)$this->apiship_params['shipping_apiship_tax_class_id'], (bool)$this->config->get('config_tax'));

						$all_points[] = [
							'lon'          => $point['lon'],
							'lat'          => $point['lat'],
							'code'         => 'apiship.' . $code,
							'tariff'       => $tariff['tariffName'],
							'daysMin'      => $tariff['daysMin'],
							'daysMax'      => $tariff['daysMax'],
							'text'         => $this->currency->format($cost_with_tax, $this->session->data['currency']),
							'cost'         => $this->currency->format($cost_with_tax, $this->session->data['currency'], 0, false),
							'cost_value'   => (float)$tariff['deliveryCost'],
							'name'         => $this->fill_template([
								'template'          => $this->apiship_params['shipping_apiship_title_point_template'],
								'type'              => 'point',
								'sub_type'          => $point['type'],
								'providerKey'       => $provider['providerKey'],
								'tariffName'        => $tariff['tariffName'],
								'pointName'         => $point['name'],
								'pointAddress'      => $point['address'],
								'daysMin'           => $tariff['daysMin'],
								'daysMax'           => $tariff['daysMax'],
								'tariffDescription' => $tariff['tariffDescription'],
								'code'              => 'apiship.' . $code
							]),
							'type'         => $apiship_point_types[(int)$point['type']] ?? (string)$point['type'],
							'provider'     => $apiship_providers[$provider['providerKey']] ?? $provider['providerKey'],
							'provider_key' => $provider['providerKey'],
							'address'      => $point['address'],
							'paymentCash'  => $point['paymentCash'],
							'paymentCard'  => $point['paymentCard']
						];
					}
				}
			}
		}

		usort($all_points, function($a, $b) {
			return $a['cost_value'] <=> $b['cost_value'];
		});

		return ['error' => 'no_error', 'points' => $all_points];
	}

	/**
	 * Точки для карты по коду варианта доставки
	 *
	 * @param string $code
	 *
	 * @return array<string, mixed>
	 */
	public function get_points(string $code): array {
		$region = (string)$this->apiship->getData('shipping_apiship_region');
		$city = (string)$this->apiship->getData('shipping_apiship_city');
		$postcode = (string)$this->apiship->getData('shipping_apiship_postcode');
		$ext_address = (string)$this->apiship->getData('shipping_apiship_ext_address');
		$country = (string)$this->apiship->getData('shipping_apiship_country');

		$this->apiship->toLog('get_points', [
			'country'     => $country,
			'code'        => $code,
			'region'      => $region,
			'city'        => $city,
			'postcode'    => $postcode,
			'ext_address' => $ext_address
		]);

		$parce_code = $this->apiship->parce_code($code);

		$data = $this->get_points_array($country, $region, $city, $postcode, $ext_address);

		$points = [];

		foreach ($data['points'] as $point) {
			if ($this->apiship_params['shipping_apiship_group_points'] || $point['provider_key'] == $parce_code['provider']) {
				$points[] = $point;
			}
		}

		if ($data['error'] == 'no_error' && !$points) {
			return ['error' => $this->language->get('shipping_apiship_error_no_points'), 'points' => []];
		}

		return ['error' => $data['error'], 'points' => $points];
	}

	/**
	 * Выбор ПВЗ: сохраняет вариант в сессии и возвращает обновлённый quote
	 *
	 * @param string $code
	 *
	 * @return array<string, mixed>
	 */
	public function set_point(string $code): array {
		if ($code == '') {
			return ['error' => $this->apiship_params['shipping_apiship_error_params']];
		}

		$parce_code = $this->apiship->parce_code($code);

		$delivery_type = $parce_code['delivery_type'];

		$region = (string)$this->apiship->getData('shipping_apiship_region');
		$city = (string)$this->apiship->getData('shipping_apiship_city');
		$postcode = (string)$this->apiship->getData('shipping_apiship_postcode');
		$ext_address = (string)$this->apiship->getData('shipping_apiship_ext_address');
		$country = (string)$this->apiship->getData('shipping_apiship_country');

		$cost = -1;
		$address1 = '';
		$name = '';
		$image = '';

		$cash_on_delivery = $this->is_cash_on_delivery();

		$apiship_calculator_data = $this->apiship->apiship_calculator($country, $region, $city, $postcode, $ext_address, [], $this->cart->getProducts(), $this->getCartTotal(), $cash_on_delivery);

		$data = $apiship_calculator_data['body'];

		$providers = $data['deliveryToPoint'] ?? [];

		foreach ($providers as $provider) {
			$tariffs = $provider['tariffs'] ?? [];

			foreach ($tariffs as $tariff) {
				if (!isset($tariff['tariffDescription'])) {
					$tariff['tariffDescription'] = '';
				}

				foreach ($tariff['pointIds'] as $point_id) {
					foreach ($this->tariff_pickup_types($tariff, $provider['providerKey']) as $pickup_type) {

						$key = $delivery_type . '_' . $provider['providerKey'] . '_' . $tariff['tariffId'] . '_' . $point_id . '_' . $pickup_type;

						if ('apiship.' . $key == $code) {
							$cost = (float)$tariff['deliveryCost'];

							$point = $this->apiship_point((string)$point_id);

							$postcode = (string)($point['postIndex'] ?? '');
							$address1 = $point ? $this->apiship->get_address($point) : '';

							$name = $this->fill_template([
								'template'          => $this->apiship_params['shipping_apiship_title_point_template'],
								'type'              => 'point',
								'sub_type'          => $point['type'] ?? '',
								'providerKey'       => $provider['providerKey'],
								'tariffName'        => $tariff['tariffName'],
								'pointName'         => $point['name'] ?? '',
								'pointAddress'      => $address1,
								'daysMin'           => $tariff['daysMin'],
								'daysMax'           => $tariff['daysMax'],
								'tariffDescription' => $tariff['tariffDescription'],
								'code'              => $code
							]);

							$image = 'https://storage.apiship.ru/icons/providers/svg/' . $provider['providerKey'] . '.svg';
						}
					}
				}
			}
		}

		if ($cost == -1) {
			return ['error' => $this->apiship_params['shipping_apiship_error_calculator']];
		}

		$shipping_apiship = $this->build_quote($parce_code['short_code'], $name, $cost, '', $image, true);

		$select_points = $this->apiship->getData('shipping_apiship_select_points');

		if (!is_array($select_points)) {
			$select_points = [];
		}

		$select_points[$parce_code['provider']] = $shipping_apiship;

		$this->apiship->setData('shipping_apiship_select_points', $select_points);

		// Вариант должен быть в сессии, иначе checkout/shipping_method.save его не примет
		if (!isset($this->session->data['shipping_methods']['apiship'])) {
			$this->session->data['shipping_methods']['apiship'] = [
				'code'       => 'apiship',
				'name'       => $this->apiship_params['shipping_apiship_title'] ?: $this->language->get('shipping_apiship_title'),
				'quote'      => [],
				'sort_order' => (int)$this->apiship_params['shipping_apiship_sort_order'],
				'error'      => false
			];
		}

		$this->session->data['shipping_methods']['apiship']['quote'][$parce_code['short_code']] = $shipping_apiship;

		$this->apiship->setData('shipping_apiship_last_select_code', $code);

		$shipping_apiship['postcode'] = $postcode;
		$shipping_apiship['address1'] = $address1;

		$this->apiship->toLog('set_point', [
			'post'            => $code,
			'shipping_apiship' => $shipping_apiship
		]);

		return $shipping_apiship;
	}

	/**
	 * Get Quote
	 *
	 * @param array<string, mixed> $address
	 *
	 * @return array<string, mixed>
	 */
	public function getQuote(array $address): array {
		// Вызов из API (редактирование заказа в админке): полный список ПВЗ с поиском
		if (isset($this->request->get['route']) && str_starts_with((string)$this->request->get['route'], 'api/')) {
			$search = isset($this->request->get['term']) ? trim((string)$this->request->get['term']) : '';
			$current_code = (string)($this->request->post['shipping_method']['code'] ?? '');

			return $this->get_quote_list($address, true, $search, $current_code);
		}

		return $this->get_quote_list($address);
	}

	/**
	 * @param array<int, int> $oc_orders
	 *
	 * @return array<string, mixed>
	 */
	public function get_label(array $oc_orders): array {
		$text_error = '';
		$apiship_orders = [];

		foreach ($oc_orders as $oc_order_id) {
			$apiship_order = $this->get_apiship_order_by_oc_number((int)$oc_order_id);

			if (isset($apiship_order['apiship_order_id'])) {
				$apiship_orders[] = (int)$apiship_order['apiship_order_id'];
			} else {
				$text_error .= sprintf($this->apiship_params['shipping_apiship_error_no_export_order'], $oc_order_id) . '; ';
			}
		}

		$labels = [];

		if (empty($apiship_orders)) {
			return ['labels' => $labels, 'error' => $text_error];
		}

		$data = $this->apiship->apiship_labels($apiship_orders);

		if (isset($data['body']['url'])) {
			$labels[] = ['name' => 'label_' . implode('_', $oc_orders) . '.pdf', 'url' => $data['body']['url']];
		}

		foreach ($data['body']['failedOrders'] ?? [] as $orders) {
			$text_error .= $orders['orderId'] . ' - ' . $orders['message'] . '; ';
		}

		if (empty($labels) && empty($text_error) && isset($data['body']['message'])) {
			$text_error = $data['body']['message'];
		}

		if (empty($text_error)) {
			return ['labels' => $labels];
		}

		return ['labels' => $labels, 'error' => $text_error];
	}

	/**
	 * @param array<int, int> $oc_orders
	 *
	 * @return array<string, mixed>
	 */
	public function get_waybill(array $oc_orders): array {
		$text_error = '';
		$apiship_orders = [];

		foreach ($oc_orders as $oc_order_id) {
			$apiship_order = $this->get_apiship_order_by_oc_number((int)$oc_order_id);

			if (isset($apiship_order['apiship_order_id'])) {
				$apiship_orders[] = (int)$apiship_order['apiship_order_id'];
			} else {
				$text_error .= sprintf($this->apiship_params['shipping_apiship_error_no_export_order'], $oc_order_id) . '; ';
			}
		}

		$waybills = [];

		if (empty($apiship_orders)) {
			return ['waybills' => $waybills, 'error' => $text_error];
		}

		$data = $this->apiship->apiship_waybills($apiship_orders);

		foreach ($data['body']['waybillItems'] ?? [] as $waybill) {
			$waybills[] = ['name' => $waybill['providerKey'] . '_' . implode('_', $oc_orders) . '.pdf', 'url' => $waybill['file']];
		}

		foreach ($data['body']['failedOrders'] ?? [] as $orders) {
			$text_error .= $orders['orderId'] . ' - ' . $orders['message'] . '; ';
		}

		if (empty($waybills) && empty($text_error) && isset($data['body']['message'])) {
			$text_error = $data['body']['message'];
		}

		if (empty($text_error)) {
			return ['waybills' => $waybills];
		}

		return ['waybills' => $waybills, 'error' => $text_error];
	}

	/**
	 * Одиночный экспорт заказа из карточки заказа
	 *
	 * @param int                  $order_id
	 * @param array<string, mixed> $params shipping_apiship_pickup_type, place_length, place_width, place_height, place_weight, comment, pickup_date
	 *
	 * @return array<string, mixed>
	 */
	public function export_order(int $order_id, array $params): array {
		$required = [
			'shipping_apiship_pickup_type',
			'shipping_apiship_place_length',
			'shipping_apiship_place_width',
			'shipping_apiship_place_height',
			'shipping_apiship_place_weight',
			'shipping_apiship_comment',
			'shipping_apiship_pickup_date'
		];

		foreach ($required as $key) {
			if (!isset($params[$key])) {
				return ['error' => $this->apiship_params['shipping_apiship_error_params']];
			}
		}

		return $this->export_order_value(
			$order_id,
			(int)$params['shipping_apiship_pickup_type'],
			(float)$params['shipping_apiship_place_length'],
			(float)$params['shipping_apiship_place_width'],
			(float)$params['shipping_apiship_place_height'],
			(float)$params['shipping_apiship_place_weight'],
			(string)$params['shipping_apiship_comment'],
			(string)$params['shipping_apiship_pickup_date'],
			(int)$this->apiship_params['shipping_apiship_export_status'],
			0
		);
	}

	/**
	 * @param int    $order_id
	 * @param int    $shipping_apiship_pickup_type
	 * @param float  $shipping_apiship_place_length
	 * @param float  $shipping_apiship_place_width
	 * @param float  $shipping_apiship_place_height
	 * @param float  $shipping_apiship_place_weight
	 * @param string $shipping_apiship_comment
	 * @param string $shipping_apiship_pickup_date
	 * @param int    $shipping_apiship_export_status_ok
	 * @param int    $shipping_apiship_export_status_error
	 *
	 * @return array<string, mixed>
	 */
	private function export_order_value(int $order_id, int $shipping_apiship_pickup_type, float $shipping_apiship_place_length, float $shipping_apiship_place_width, float $shipping_apiship_place_height, float $shipping_apiship_place_weight, string $shipping_apiship_comment, string $shipping_apiship_pickup_date, int $shipping_apiship_export_status_ok, int $shipping_apiship_export_status_error): array {
		$this->load->model('checkout/order');

		$order = $this->model_checkout_order->getOrder($order_id);

		if (!$order) {
			return ['error' => $this->apiship_params['shipping_apiship_error_params']];
		}

		$shipping_code = (string)($order['shipping_method']['code'] ?? '');

		if (strpos($shipping_code, 'apiship') === false) {
			return ['error' => $this->apiship_params['shipping_apiship_error_params']];
		}

		$order_products = $this->getOrderProducts($order_id);

		$order_totals_items = $this->get_order_totals($order_id);
		$total_sum = $order_totals_items['total_sum'];
		$order_totals = $order_totals_items['order_totals'];

		$calculate_data = $this->apiship->calculate_places($order_products, (float)$total_sum);

		$this->apiship->toLog('export_order debug', ['calculate_data' => $calculate_data]);

		$items = $calculate_data['items'];
		$total_cost = $calculate_data['total_cost'];
		$assessed_cost = $calculate_data['assessed_cost'];

		$parce_code = $this->apiship->parce_code($shipping_code);

		$delivery_type = $parce_code['delivery_type'];
		$provider = $parce_code['provider'];
		$tariff_id = $parce_code['tariff_id'];
		$point_id = $parce_code['point_id'];

		if ($delivery_type == 'point') {
			$point = $this->apiship_point($point_id);

			$recipientAddressString = $point ? $this->apiship->get_address($point) : '';
		} else {
			$recipientAddressString = $this->apiship->get_address([
				'postIndex'  => $order['shipping_postcode'],
				'area'       => '',
				'region'     => $order['shipping_zone'],
				'regionType' => '',
				'city'       => $order['shipping_city'],
				'cityType'   => '',
				'street'     => $order['shipping_address_1'],
				'streetType' => ''
			], true);
		}

		$shipping_total = (float)($order_totals['shipping'] ?? 0);

		// Сумма заказа в валюте ApiShip (get_order_totals уже конвертирует итоги)
		$order_total = (float)($order_totals['total'] ?? $this->currency->convert((float)$order['total'], (string)$this->config->get('config_currency'), (string)$this->apiship_params['shipping_apiship_rub_select']));

		$paid_orders = in_array($order['order_status_id'], $this->apiship_params['shipping_apiship_paid_orders']);

		$order_params = [];

		$order_params['orderId'] = $this->apiship_params['shipping_apiship_prefix'] . $order['order_id'];
		$order_params['orderWeight'] = $shipping_apiship_place_weight;
		$order_params['orderProviderKey'] = $provider;
		$order_params['orderPickupType'] = $shipping_apiship_pickup_type;
		$order_params['orderPickupDate'] = $shipping_apiship_pickup_date;
		$order_params['orderDeliveryType'] = ($delivery_type == 'point') ? 2 : 1;
		$order_params['orderTariffId'] = $tariff_id;
		$order_params['orderPointOutId'] = $point_id;

		$order_params['costAssessedCost'] = $this->apiship->format_cost($order_total - $shipping_total);
		$order_params['costCodCost'] = !$paid_orders ? $this->apiship->format_cost($order_total) : 0;
		$order_params['costDeliveryCost'] = !$paid_orders ? $this->apiship->format_cost($shipping_total) : 0;
		$order_params['sub_total_cost'] = $this->apiship->format_cost($total_cost);
		$order_params['assessed_cost'] = $this->apiship->format_cost($assessed_cost);

		$order_params['recipientPhone'] = $order['telephone'];
		$order_params['recipientEmail'] = $order['email'];
		$order_params['recipientContactName'] = $order['firstname'] . ' ' . $order['lastname'];
		$order_params['recipientCountryCode'] = $order['shipping_iso_code_2'];
		$order_params['recipientAddressString'] = $recipientAddressString;
		$order_params['recipientComment'] = $shipping_apiship_comment;

		$order_params['placeHeight'] = $shipping_apiship_place_height;
		$order_params['placeLength'] = $shipping_apiship_place_length;
		$order_params['placeWidth'] = $shipping_apiship_place_width;
		$order_params['placeWeight'] = $shipping_apiship_place_weight;
		$order_params['placeCalculateWeight'] = $calculate_data['total_weight'];

		$order_params['items'] = $items;

		$output_data = $this->apiship->apiship_order($order_params)['body'];

		if (isset($output_data['orderId'])) {
			$apiship_order_status = $this->apiship->apiship_order_status((int)$output_data['orderId']);

			$status = $this->get_status($apiship_order_status);
			$status_name = $status['name'] ?? '';

			$track_number = (string)($output_data['providerNumber'] ?? '');

			$text = sprintf($this->apiship_params['shipping_apiship_success_export_message'], $output_data['orderId'], $track_number, $status_name);

			$this->model_checkout_order->addHistory($order_id, $shipping_apiship_export_status_ok ?: (int)$order['order_status_id'], $text, false);

			$this->bind_apiship_order($order_id, (int)$output_data['orderId']);
			$this->change_order_str_field($order_id, 'tracking', $track_number);

			return ['success' => $text];
		}

		if (isset($output_data['message'])) {
			$text = $output_data['message'] . PHP_EOL;

			foreach ($output_data['errors'] ?? [] as $error) {
				$text .= $error['message'] . PHP_EOL;
			}
		} else {
			$text = $this->apiship_params['shipping_apiship_error_timeout'];
		}

		$this->log->write('shipping_apiship export error ' . print_r($output_data, true));

		if ($shipping_apiship_export_status_error) {
			$this->model_checkout_order->addHistory($order_id, $shipping_apiship_export_status_error, $text, false);
		}

		return ['error' => $text];
	}

	/**
	 * @param int $order_id
	 *
	 * @return array<string, mixed>
	 */
	public function cancel_order(int $order_id): array {
		$apiship_order = $this->get_apiship_order_by_oc_number($order_id);

		if (!isset($apiship_order['apiship_order_id'])) {
			return ['error' => sprintf($this->apiship_params['shipping_apiship_error_no_export_order'], $order_id)];
		}

		$output_data = $this->apiship->apiship_cancel_order((int)$apiship_order['apiship_order_id'])['body'];

		$this->load->model('checkout/order');

		if (isset($output_data['orderId'])) {
			$text = sprintf($this->apiship_params['shipping_apiship_success_cancel_message'], $output_data['orderId']);

			$order = $this->model_checkout_order->getOrder($order_id);

			$this->model_checkout_order->addHistory($order_id, (int)$this->apiship_params['shipping_apiship_cancel_export_status'] ?: (int)($order['order_status_id'] ?? 0), $text, false);

			$this->delete_apiship_order($order_id);
			$this->change_order_str_field($order_id, 'tracking', '');

			return ['success' => $text];
		}

		if (isset($output_data['message'])) {
			$text = $output_data['message'] . PHP_EOL;

			foreach ($output_data['errors'] ?? [] as $error) {
				$text .= $error['message'] . PHP_EOL;
			}
		} else {
			$text = $this->apiship_params['shipping_apiship_error_timeout'];
		}

		$this->log->write('shipping_apiship cancel error ' . print_r($output_data, true));

		return ['error' => $text];
	}

	/**
	 * Групповой экспорт заказов в статусе «готов к экспорту» (cron)
	 *
	 * @return array<string, mixed>
	 */
	public function export_orders(): array {
		$orders = $this->get_apiship_orders();

		$export_result = [];

		foreach ($orders as $order) {
			$order_id = (int)$order['order_id'];

			$order_params = $this->get_order_params_value($order_id);

			$export_result[] = $this->export_order_value(
				$order_id,
				(int)$order_params['pickup_type'],
				(float)$order_params['place_length'],
				(float)$order_params['place_width'],
				(float)$order_params['place_height'],
				(float)$order_params['place_weight'],
				(string)$order_params['comment'],
				(string)$order_params['pickup_date'],
				(int)$this->apiship_params['shipping_apiship_group_export_status_ok'],
				(int)$this->apiship_params['shipping_apiship_group_export_status_error']
			);
		}

		return ['status' => 'ok', 'export_result' => $export_result];
	}

	/**
	 * Импорт статусов заказов из ApiShip (cron)
	 *
	 * @return array<string, mixed>
	 */
	public function import_orders(): array {
		$ret_text = '';

		$file_name = DIR_DOWNLOAD . 'apiship.obj';

		$data = null;

		$exists = file_exists($file_name) && filesize($file_name) > 0;

		$fp = @fopen($file_name, $exists ? 'r+' : 'w');

		if ($fp === false) {
			$this->log->write('shipping_apiship import_orders: cannot open ' . $file_name);

			return ['status' => 'error', 'import_result' => 'cannot open ' . $file_name];
		}

		if (!flock($fp, LOCK_EX)) {
			fclose($fp);

			$this->log->write('shipping_apiship import_orders: cannot lock ' . $file_name);

			return ['status' => 'error', 'import_result' => 'cannot lock ' . $file_name];
		}

		if ($exists) {
			$data = @unserialize((string)fread($fp, filesize($file_name)));
		}

		if (!is_array($data)) {
			$data = [
				'last_import_date'        => date('Y-m-d'),
				'last_status_change_date' => date("Y-m-d\T00:00:00+03:00", strtotime('-2 day'))
			];
		}

		date_default_timezone_set('Europe/Moscow');

		$dif_last_status_change_date = abs(strtotime(date('Y-m-d H:i:s')) - strtotime($data['last_status_change_date']));

		if ($dif_last_status_change_date / 3600 > 48) {
			$data['last_status_change_date'] = date("Y-m-d\T00:00:00+03:00", strtotime('-2 day'));
		}

		$time = strtotime(date('Y-m-d H:i:s')) - strtotime($data['last_import_date']);

		if ($time > 60) {
			$apiship_orders_status = $this->apiship->apiship_orders_status($data['last_status_change_date']);

			foreach ($apiship_orders_status as $apiship_order_status) {
				if (!isset($apiship_order_status['status']['created'])) {
					$this->apiship->toLog('shipping_apiship import_orders ', $apiship_orders_status, true);

					break;
				}

				if ($apiship_order_status['status']['created'] > $data['last_status_change_date']) {
					$data['last_status_change_date'] = $apiship_order_status['status']['created'];
				}

				// если заказа нет в opencart (не выгружен через модуль)
				$apiship_order = $this->get_apiship_order_by_apiship_number((int)$apiship_order_status['orderInfo']['orderId']);

				if (empty($apiship_order)) {
					continue;
				}

				$current_status_id = $apiship_order['status'];
				$status = $this->get_status($apiship_order_status);

				$status_id = $status['id'];
				$status_name = $status['name'];
				$order_id = (int)$apiship_order['oc_order_id'];
				$key = $status['key'];

				if ($current_status_id != $status_id) {
					$text = sprintf($this->apiship_params['shipping_apiship_change_order_status_message'], $apiship_order['apiship_order_id'], $status_name);

					$this->load->model('checkout/order');

					$order = $this->model_checkout_order->getOrder($order_id);

					if (!$order) {
						continue;
					}

					$order_new_status = (int)$order['order_status_id'];
					$order_notify = false;
					$order_text = $text;

					$mapping = $this->apiship_params['shipping_apiship_mapping_status'][$key] ?? null;

					if (is_array($mapping) && !empty($mapping['use'])) {
						$order_new_status = (int)$mapping['order_status_id'];
						$order_notify = !empty($mapping['notify']);
						$order_text = '';
					}

					// Сначала история заказа OpenCart, затем фиксация статуса ApiShip: при сбое истории статус останется
					// прежним и запись будет обработана повторно на следующем запуске
					$this->model_checkout_order->addHistory($order_id, $order_new_status, $order_text, $order_notify);

					$this->set_apiship_order_status((int)$apiship_order['apiship_order_id'], (int)$status_id);

					$this->log->write($text);

					$ret_text .= $text . PHP_EOL . '<br>';
				}
			}

			$data['last_import_date'] = date('Y-m-d H:i:s');
		} else {
			$ret_text = 'time_out ' . $time;

			$this->log->write('import time_out');
		}

		ftruncate($fp, 0);
		rewind($fp);
		fwrite($fp, serialize($data));
		fflush($fp);
		flock($fp, LOCK_UN);
		fclose($fp);

		return ['status' => 'ok', 'import_result' => $ret_text];
	}

	/**
	 * @param int $oc_order_id
	 * @param int $apiship_order_id
	 *
	 * @return void
	 */
	private function bind_apiship_order(int $oc_order_id, int $apiship_order_id): void {
		$this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "apiship_order` (`oc_order_id`, `apiship_order_id`) VALUES ('" . (int)$oc_order_id . "', '" . (int)$apiship_order_id . "')");
	}

	/**
	 * @param int $oc_order_id
	 *
	 * @return void
	 */
	private function delete_apiship_order(int $oc_order_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "apiship_order` WHERE `oc_order_id` = '" . (int)$oc_order_id . "'");
	}

	/**
	 * @param int $oc_order_id
	 *
	 * @return array<string, mixed>
	 */
	private function get_apiship_order_by_oc_number(int $oc_order_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "apiship_order` WHERE `oc_order_id` = '" . (int)$oc_order_id . "'");

		return $query->row;
	}

	/**
	 * @param int $apiship_order_id
	 *
	 * @return array<string, mixed>
	 */
	private function get_apiship_order_by_apiship_number(int $apiship_order_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "apiship_order` WHERE `apiship_order_id` = '" . (int)$apiship_order_id . "'");

		return $query->row;
	}

	/**
	 * @param string $key
	 *
	 * @return array<string, mixed>
	 */
	private function get_apiship_order_status_by_key(string $key): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "apiship_order_status` WHERE `key` = '" . $this->db->escape($key) . "'");

		return $query->row;
	}

	/**
	 * @param string $key
	 * @param string $name
	 *
	 * @return array<string, mixed>
	 */
	private function insert_apiship_order_status(string $key, string $name): array {
		$this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "apiship_order_status` (`key`, `name`) VALUES ('" . $this->db->escape($key) . "', '" . $this->db->escape($name) . "')");

		return $this->get_apiship_order_status_by_key($key);
	}

	/**
	 * @param int    $order_id
	 * @param string $field
	 * @param string $param
	 *
	 * @return void
	 */
	private function change_order_str_field(int $order_id, string $field, string $param): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "order` SET `" . $this->db->escape($field) . "` = '" . $this->db->escape($param) . "', `date_modified` = NOW() WHERE `order_id` = '" . (int)$order_id . "'");
	}

	/**
	 * @param int $apiship_order_id
	 * @param int $status_id
	 *
	 * @return void
	 */
	private function set_apiship_order_status(int $apiship_order_id, int $status_id): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "apiship_order` SET `status` = '" . (int)$status_id . "' WHERE `apiship_order_id` = '" . (int)$apiship_order_id . "'");
	}

	/**
	 * Типы забора, доступные и по тарифу, и по настройкам службы доставки
	 *
	 * @param array<string, mixed> $tariff
	 * @param string               $provider_key
	 *
	 * @return array<int, int>
	 */
	private function tariff_pickup_types(array $tariff, string $provider_key): array {
		$tariff_types = is_array($tariff['pickupTypes'] ?? null) ? $tariff['pickupTypes'] : [];

		$result = [];

		foreach ([1, 2] as $pickup_type) {
			if (in_array($pickup_type, $tariff_types) && in_array($pickup_type, $this->get_pickup_types($provider_key))) {
				$result[] = $pickup_type;
			}
		}

		return $result;
	}

	/**
	 * Наложенный платёж по текущему (или переданному) способу оплаты
	 *
	 * @param string|null $payment_code null — код из сессии чекаута
	 *
	 * @return bool
	 */
	private function is_cash_on_delivery(?string $payment_code = null): bool {
		if ($payment_code === null) {
			$payment_code = (string)($this->session->data['payment_method']['code'] ?? '');
		}

		return $this->apiship->is_cash_on_delivery($payment_code, $this->apiship_params['shipping_apiship_cash_on_delivery_payment_methods']);
	}

	/**
	 * Пересчёт выбранного варианта доставки (после выбора способа оплаты меняется наложенный платёж)
	 *
	 * @param string $code apiship.point_… или apiship.door_…
	 *
	 * @return array<string, mixed> обновлённый quote либо [] если вариант больше недоступен
	 */
	public function refresh_quote(string $code): array {
		$parce_code = $this->apiship->parce_code($code);

		if ($parce_code['delivery_type'] == 'point') {
			if ($parce_code['point_id'] == 'error' || $parce_code['point_id'] == '') {
				return [];
			}

			$quote = $this->set_point($code);

			if (isset($quote['error'])) {
				return [];
			}

			unset($quote['postcode'], $quote['address1']);
		} elseif ($parce_code['delivery_type'] == 'door') {
			$region = (string)$this->apiship->getData('shipping_apiship_region');
			$city = (string)$this->apiship->getData('shipping_apiship_city');
			$postcode = (string)$this->apiship->getData('shipping_apiship_postcode');
			$ext_address = (string)$this->apiship->getData('shipping_apiship_ext_address');
			$country = (string)$this->apiship->getData('shipping_apiship_country');

			$apiship_calculator_data = $this->apiship->apiship_calculator($country, $region, $city, $postcode, $ext_address, [], $this->cart->getProducts(), $this->getCartTotal(), $this->is_cash_on_delivery());

			$quote = [];

			foreach ($apiship_calculator_data['body']['deliveryToDoor'] ?? [] as $provider) {
				if ($provider['providerKey'] != $parce_code['provider']) {
					continue;
				}

				foreach ($provider['tariffs'] ?? [] as $tariff) {
					if ((string)$tariff['tariffId'] != $parce_code['tariff_id'] || !in_array((int)$parce_code['pickup_type'], $this->tariff_pickup_types($tariff, $provider['providerKey']))) {
						continue;
					}

					$params = [
						'type'              => 'door',
						'providerKey'       => $provider['providerKey'],
						'tariffName'        => $tariff['tariffName'],
						'daysMin'           => $tariff['daysMin'],
						'daysMax'           => $tariff['daysMax'],
						'tariffDescription' => $tariff['tariffDescription'] ?? '',
						'code'              => $code
					];

					$name = $this->fill_template($params + ['template' => $this->apiship_params['shipping_apiship_title_door_template']]);
					$description = $this->fill_template($params + ['template' => $this->apiship_params['shipping_apiship_description_door_template']]);

					$quote = $this->build_quote($parce_code['short_code'], $name, (float)$tariff['deliveryCost'], $description, 'https://storage.apiship.ru/icons/providers/svg/' . $provider['providerKey'] . '.svg', false);

					break 2;
				}
			}

			if (!$quote) {
				return [];
			}

			$this->session->data['shipping_methods']['apiship']['quote'][$parce_code['short_code']] = $quote;
		} else {
			return [];
		}

		$this->session->data['shipping_method'] = $quote;

		return $quote;
	}

	/**
	 * Разрешённые типы забора для службы доставки (1 — курьер, 2 — привоз на склад)
	 *
	 * @param string $provider
	 *
	 * @return array<int, int>
	 */
	private function get_pickup_types(string $provider): array {
		$pickup_types = [];

		$settings = $this->apiship_params['shipping_apiship_provider'][$provider] ?? [];

		if (!empty($settings['pickup_type'])) {
			$pickup_types[] = 2;
		}

		if (!empty($settings['courier_type'])) {
			$pickup_types[] = 1;
		}

		return $pickup_types;
	}

	/**
	 * @param array<string, mixed> $apiship_order_status
	 *
	 * @return array<string, mixed>
	 */
	private function get_status(array $apiship_order_status): array {
		$key = (string)($apiship_order_status['status']['key'] ?? '');
		$name = (string)($apiship_order_status['status']['name'] ?? '');

		if ($key == '') {
			return ['id' => 0, 'key' => '', 'name' => $name];
		}

		$status = $this->get_apiship_order_status_by_key($key);

		if (!empty($status)) {
			return $status;
		}

		return $this->insert_apiship_order_status($key, $name);
	}

	/**
	 * @param int $product_id
	 * @param int $product_option_value_id
	 *
	 * @return array<string, mixed>
	 */
	private function getProductOptionValue(int $product_id, int $product_option_value_id): array {
		$query = $this->db->query("SELECT `pov`.`option_value_id`, `ovd`.`name`, `pov`.`quantity`, `pov`.`subtract`, `pov`.`price`, `pov`.`price_prefix`, `pov`.`points`, `pov`.`points_prefix`, `pov`.`weight`, `pov`.`weight_prefix` FROM `" . DB_PREFIX . "product_option_value` `pov` LEFT JOIN `" . DB_PREFIX . "option_value` `ov` ON (`pov`.`option_value_id` = `ov`.`option_value_id`) LEFT JOIN `" . DB_PREFIX . "option_value_description` `ovd` ON (`ov`.`option_value_id` = `ovd`.`option_value_id`) WHERE `pov`.`product_id` = '" . (int)$product_id . "' AND `pov`.`product_option_value_id` = '" . (int)$product_option_value_id . "' AND `ovd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}

	/**
	 * Товары заказа с габаритами и весом (с учётом опций)
	 *
	 * @param int $order_id
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function getOrderProducts(int $order_id): array {
		$this->load->model('checkout/order');
		$this->load->model('catalog/product');

		$order_products = $this->model_checkout_order->getProducts($order_id);

		foreach ($order_products as &$order_product) {
			$order_options = $this->model_checkout_order->getOptions($order_id, (int)$order_product['order_product_id']);

			$product_info = $this->model_catalog_product->getProduct((int)$order_product['product_id']);

			$order_product['length'] = $product_info['length'] ?? 0;
			$order_product['width'] = $product_info['width'] ?? 0;
			$order_product['height'] = $product_info['height'] ?? 0;
			$order_product['length_class_id'] = (int)($product_info['length_class_id'] ?? 0);

			$weight = (float)($product_info['weight'] ?? 0);

			foreach ($order_options as $order_option) {
				$product_option_value_info = $this->getProductOptionValue((int)$order_product['product_id'], (int)($order_option['product_option_value_id'] ?? 0));

				if (!empty($product_option_value_info['weight'])) {
					if ($product_option_value_info['weight_prefix'] == '+') {
						$weight += (float)$product_option_value_info['weight'];
					} elseif ($product_option_value_info['weight_prefix'] == '-') {
						$weight -= (float)$product_option_value_info['weight'];
					} elseif ($product_option_value_info['weight_prefix'] == '=') {
						$weight = (float)$product_option_value_info['weight'];
					}
				}
			}

			$order_product['weight'] = $weight * (int)$order_product['quantity'];
			$order_product['weight_class_id'] = (int)($product_info['weight_class_id'] ?? 0);
		}

		unset($order_product);

		return $order_products;
	}

	/**
	 * Параметры экспорта для карточки заказа
	 *
	 * @param int $order_id
	 *
	 * @return array<string, mixed>
	 */
	public function get_order_params(int $order_id): array {
		return $this->get_order_params_value($order_id);
	}

	/**
	 * @param int $order_id
	 *
	 * @return array<string, mixed>
	 */
	private function get_order_params_value(int $order_id): array {
		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($order_id);

		if (!$order_info) {
			return ['error' => $this->apiship_params['shipping_apiship_error_params']];
		}

		$apiship_paid = in_array($order_info['order_status_id'], $this->apiship_params['shipping_apiship_paid_orders']);

		$apiship_export = $this->is_order_export($order_id);

		$parce_code = $this->apiship->parce_code((string)($order_info['shipping_method']['code'] ?? ''));

		$provider = $parce_code['provider'];

		if (in_array(2, $this->get_pickup_types($provider))) {
			$apiship_pickup_type = 2;
		} else {
			$apiship_pickup_type = 1;
		}

		if (!empty($parce_code['pickup_type'])) {
			$apiship_pickup_type = (int)$parce_code['pickup_type'];
		}

		$order_products = $this->getOrderProducts($order_id);

		$total_sum = $this->get_order_totals($order_id)['total_sum'];

		$calculate_data = $this->apiship->calculate_places($order_products, (float)$total_sum);

		$apiship_place_length = $calculate_data['total_length'];
		$apiship_place_width = $calculate_data['total_width'];
		$apiship_place_height = $calculate_data['total_height'];
		$apiship_place_weight = $calculate_data['total_weight'];

		$apiship_order_status = '';
		$apiship_comment = (string)$order_info['comment'];
		$apiship_tracking_url = '';

		$add_pickup_date = 1;

		if ((string)$this->apiship_params['shipping_apiship_add_pickup_date'] === '0') {
			$add_pickup_date = 0;
		}

		$pickup_date = date('Y-m-d', strtotime('+' . $add_pickup_date . ' day'));

		if ($apiship_export) {
			$apiship_order = $this->get_apiship_order_by_oc_number($order_id);

			$order_status = [];

			if (isset($apiship_order['apiship_order_id'])) {
				$order_status = $this->apiship->apiship_order_status((int)$apiship_order['apiship_order_id']);
			}

			if (isset($order_status['status']['key'])) {
				$apiship_order_status = (string)($order_status['status']['name'] ?? '');

				if (isset($order_status['orderInfo']['trackingUrl'])) {
					$apiship_tracking_url = (string)$order_status['orderInfo']['trackingUrl'];
				}

				if (isset($order_status['orderInfo']['orderId'])) {
					$apiship_order_info = $this->apiship->apiship_order_info((int)$order_status['orderInfo']['orderId']);

					$body = $apiship_order_info['body'] ?? [];

					if (isset($body['order']['pickupType'])) {
						$apiship_pickup_type = (int)$body['order']['pickupType'];
					}

					if (isset($body['order']['pickupDate'])) {
						$pickup_date = date('Y-m-d', strtotime($body['order']['pickupDate']));
					}

					if (isset($body['places'][0]['height'])) {
						$apiship_place_height = $body['places'][0]['height'];
					}

					if (isset($body['places'][0]['length'])) {
						$apiship_place_length = $body['places'][0]['length'];
					}

					if (isset($body['places'][0]['width'])) {
						$apiship_place_width = $body['places'][0]['width'];
					}

					if (isset($body['places'][0]['weight'])) {
						$apiship_place_weight = $body['places'][0]['weight'];
					}

					if (isset($body['recipient']['comment'])) {
						$apiship_comment = (string)$body['recipient']['comment'];
					}
				}
			}
		}

		return [
			'export'       => $apiship_export,
			'paid'         => $apiship_paid,
			'pickup_type'  => $apiship_pickup_type,
			'place_length' => $apiship_place_length,
			'place_width'  => $apiship_place_width,
			'place_height' => $apiship_place_height,
			'place_weight' => $apiship_place_weight,
			'order_status' => $apiship_order_status,
			'comment'      => $apiship_comment,
			'pickup_date'  => $pickup_date,
			'tracking_url' => $apiship_tracking_url
		];
	}

	/**
	 * @param int $order_id
	 *
	 * @return array<string, mixed>
	 */
	private function get_order_totals(int $order_id): array {
		$this->load->model('checkout/order');

		$totals = $this->model_checkout_order->getTotals($order_id);

		$total_sum = 0;
		$shipping_cost = 0;
		$order_totals = [];

		// Итоги заказа хранятся в базовой валюте магазина, ApiShip ждёт суммы в валюте «рубль» из настроек
		foreach ($totals as $total_item) {
			$value = $this->currency->convert((float)$total_item['value'], (string)$this->config->get('config_currency'), (string)$this->apiship_params['shipping_apiship_rub_select']);

			if ($total_item['code'] != 'total' && $total_item['code'] != 'shipping') {
				$total_sum += $value;
			}

			if ($total_item['code'] == 'shipping') {
				$shipping_cost = $value;
			}

			$order_totals[$total_item['code']] = $value;
		}

		if ($total_sum < 0) {
			$total_sum += $shipping_cost;

			if (isset($order_totals['shipping'])) {
				$order_totals['shipping'] = 0;
			}
		}

		return [
			'total_sum'    => $total_sum,
			'order_totals' => $order_totals
		];
	}

	/**
	 * @param int $order_id
	 *
	 * @return bool
	 */
	private function is_order_export(int $order_id): bool {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "apiship_order` WHERE `oc_order_id` = '" . (int)$order_id . "'");

		return $query->row['total'] > 0;
	}

	/**
	 * Стоимость доставки заказа без учёта правил (deliveryCostOriginal)
	 *
	 * @param int $order_id
	 *
	 * @return array<string, mixed>
	 */
	public function get_delivery_cost_original(int $order_id): array {
		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($order_id);

		if (!$order_info) {
			return ['error' => $this->apiship_params['shipping_apiship_error_params']];
		}

		$order_products = $this->getOrderProducts($order_id);

		$total_sum = $this->get_order_totals($order_id)['total_sum'];

		$cash_on_delivery = $this->is_cash_on_delivery((string)($order_info['payment_method']['code'] ?? ''));

		$apiship_calculator_data = $this->apiship->apiship_calculator((string)$order_info['shipping_iso_code_2'], (string)$order_info['shipping_zone'], (string)$order_info['shipping_city'], (string)$order_info['shipping_postcode'], (string)$order_info['shipping_address_1'], [], $order_products, (float)$total_sum, $cash_on_delivery);

		$data = $apiship_calculator_data['body'];

		$parce_code = $this->apiship->parce_code((string)($order_info['shipping_method']['code'] ?? ''));

		$delivery_cost_original = '-';

		$section = ($parce_code['delivery_type'] == 'point') ? 'deliveryToPoint' : 'deliveryToDoor';

		foreach ($data[$section] ?? [] as $provider) {
			if ($provider['providerKey'] != $parce_code['provider']) {
				continue;
			}

			foreach ($provider['tariffs'] ?? [] as $tariff) {
				if ((string)$tariff['tariffId'] != $parce_code['tariff_id']) {
					continue;
				}

				if ($parce_code['delivery_type'] == 'point' && !in_array($parce_code['point_id'], $tariff['pointIds'] ?? [])) {
					continue;
				}

				$delivery_cost_original = $tariff['deliveryCostOriginal'] ?? $tariff['deliveryCost'];

				break 2;
			}
		}

		$this->log->write('delivery_cost_original ' . print_r($delivery_cost_original, true));

		return ['delivery_cost_original' => $delivery_cost_original];
	}

	/**
	 * Заказы ApiShip в статусе группового экспорта
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_apiship_orders(): array {
		if (empty($this->apiship_params['shipping_apiship_group_export_status_ready'])) {
			return [];
		}

		$query = $this->db->query("SELECT `order_id`, `shipping_method` FROM `" . DB_PREFIX . "order` WHERE `shipping_method` LIKE '%apiship.%' AND `order_status_id` = '" . (int)$this->apiship_params['shipping_apiship_group_export_status_ready'] . "'");

		return $query->rows;
	}
}
