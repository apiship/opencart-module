<?php			
require_once DIR_SYSTEM . 'library/apiship/apiship.php';

class ModelExtensionShippingApiship extends Model {

	private $apiship;

	public function __construct($params) {
		parent::__construct($params);
		$this->apiship_params = [
			'shipping_apiship_rub_select' => $this->config->get('shipping_apiship_rub_select'),
			'shipping_apiship_gr_select' => $this->config->get('shipping_apiship_gr_select'),
			'shipping_apiship_cm_select' => $this->config->get('shipping_apiship_cm_select'),

			'shipping_apiship_token' => $this->config->get('shipping_apiship_token'),
			'shipping_apiship_mode' => $this->config->get('shipping_apiship_mode'),
			'shipping_apiship_provider' => $this->config->get('shipping_apiship_provider'),
			'shipping_apiship_prefix' => $this->config->get('shipping_apiship_prefix')
		];
		$this->apiship = new Apiship($this->registry, $this->apiship_params, $this->log);
	}

	public function install() {

		$sql  = "CREATE TABLE IF NOT EXISTS `".DB_PREFIX."apiship_order` ( ";
		$sql .= " `oc_order_id` int(11) NOT NULL,";
		$sql .= " `apiship_order_id` int(11) NOT NULL,";
 		$sql .= " `status` int(11) DEFAULT NULL,";
		$sql .= " UNIQUE KEY `oc_order_id` (`oc_order_id`) ";
		$sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;";
		$this->db->query($sql);

		$sql  = "CREATE TABLE IF NOT EXISTS `".DB_PREFIX."apiship_order_status` (";
		$sql .= " `id` int(11) NOT NULL AUTO_INCREMENT,";
		$sql .= " `key` varchar(64) COLLATE utf8_unicode_ci NOT NULL,";
		$sql .= " `name` text COLLATE utf8_unicode_ci NOT NULL,";
		$sql .= " PRIMARY KEY (`id`),";
		$sql .= " UNIQUE KEY `state_key` (`key`)";
		$sql .= " ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		$this->db->query($sql);


	}

 	public function get_providers() {
		return $this->apiship->get_providers();
	}

 	public function get_providers_points() {
		return $this->apiship->get_providers_points();
	}

 	public function get_integrator_statuses() {
		return $this->apiship->get_integrator_statuses();
	}


}