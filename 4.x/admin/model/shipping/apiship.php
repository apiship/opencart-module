<?php
namespace Opencart\Admin\Model\Extension\Apiship\Shipping;
/**
 * Class Apiship
 *
 * Can be called from $this->load->model('extension/apiship/shipping/apiship');
 *
 * @package Opencart\Admin\Model\Extension\Apiship\Shipping
 */
class Apiship extends \Opencart\System\Engine\Model {
	/**
	 * @var \Opencart\System\Library\Extension\Apiship\Apiship
	 */
	private \Opencart\System\Library\Extension\Apiship\Apiship $apiship;

	/**
	 * @param \Opencart\System\Engine\Registry $registry
	 */
	public function __construct(\Opencart\System\Engine\Registry $registry) {
		parent::__construct($registry);

		$apiship_params = [
			'shipping_apiship_rub_select' => $this->config->get('shipping_apiship_rub_select'),
			'shipping_apiship_gr_select'  => $this->config->get('shipping_apiship_gr_select'),
			'shipping_apiship_cm_select'  => $this->config->get('shipping_apiship_cm_select'),
			'shipping_apiship_token'      => $this->config->get('shipping_apiship_token'),
			'shipping_apiship_mode'       => $this->config->get('shipping_apiship_mode'),
			'shipping_apiship_provider'   => $this->config->get('shipping_apiship_provider'),
			'shipping_apiship_prefix'     => $this->config->get('shipping_apiship_prefix')
		];

		if (!class_exists('\Opencart\System\Library\Extension\Apiship\Apiship')) {
			require_once(DIR_EXTENSION . 'apiship/system/library/apiship.php');
		}

		$this->apiship = new \Opencart\System\Library\Extension\Apiship\Apiship($this->registry, $apiship_params, $this->log);
	}

	/**
	 * Таблицы модуля
	 *
	 * @return void
	 */
	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "apiship_order` (
			`oc_order_id` int(11) NOT NULL,
			`apiship_order_id` int(11) NOT NULL,
			`status` int(11) DEFAULT NULL,
			UNIQUE KEY `oc_order_id` (`oc_order_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "apiship_order_status` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`key` varchar(64) NOT NULL,
			`name` text NOT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `state_key` (`key`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_providers(): array {
		return $this->apiship->get_providers();
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public function get_providers_points(): array {
		return $this->apiship->get_providers_points();
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	public function get_integrator_statuses(): array {
		return $this->apiship->get_integrator_statuses();
	}

	/**
	 * Поиск точек привоза службы доставки (select2 в настройках)
	 *
	 * @param string $search
	 * @param string $type providerKey
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_point(string $search, string $type): array {
		if ($type == '') {
			return [];
		}

		$operations = '[1,3]';

		$filter = [
			'providerKey=' . $type,
			'availableOperation=' . $operations
		];

		if (trim($search) != '') {
			$filter[] = 'code%' . trim($search);
		}

		$points = $this->apiship->apiship_point_by_params($filter);

		$points_data = [];

		if (isset($points['message'])) {
			return $points_data;
		}

		foreach ($points as $point) {
			$points_data[] = ['id' => $point['id'], 'text' => $this->apiship->get_address($point), 'code' => $point['code']];
		}

		usort($points_data, function($a, $b) {
			return strcmp($a['text'], $b['text']);
		});

		return $points_data;
	}

	/**
	 * Установленные способы оплаты (для настройки наложенного платежа)
	 *
	 * @return array<int, array<string, string>>
	 */
	public function get_payment_methods(): array {
		$payment_methods = [];

		$this->load->model('setting/extension');

		$results = $this->model_setting_extension->getExtensionsByType('payment');

		foreach ($results as $result) {
			$this->load->language('extension/' . $result['extension'] . '/payment/' . $result['code'], $result['code']);

			$name = $this->language->get($result['code'] . '_heading_title');

			$payment_methods[] = ['code' => $result['code'], 'name' => $name];
		}

		// Модуль оплаты filterit хранит свои способы оплаты в настройках
		$filterit_payment = $this->config->get('filterit_payment');

		if (is_array($filterit_payment) && !empty($filterit_payment['created'])) {
			$language = $this->config->get('config_admin_language');

			foreach ($filterit_payment['created'] as $code => $info) {
				$payment_methods[] = [
					'code' => $code,
					'name' => !empty($info['title'][$language]) ? '[' . $code . '] ' . $info['title'][$language] : '[' . $code . ']'
				];
			}
		}

		return $payment_methods;
	}
}
