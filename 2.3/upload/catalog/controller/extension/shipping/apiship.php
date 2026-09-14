<?php
/**
 * Эндпоинты витрины ApiShip.
 *
 * - чекаут (сессия покупателя): get_points, set_point, get_last_tracing_id;
 * - cron (POST + ключ в заголовке X-Apiship-Key или POST-поле key): import_orders, export_orders, get_delivery_cost_original;
 * - действия с заказом из админки (POST + token администратора + право modify на sale/order):
 *   export_order, cancel_order, get_label, get_waybill, get_order_params.
 *   Модель заказа использует cart, tax и модели витрины, поэтому эндпоинты остаются на витрине; сессия
 *   у админки и витрины в OpenCart 2/3 общая (одна cookie), cron-ключ в эти действия не участвует.
 */
class ControllerExtensionShippingApiship extends Controller {

	private function is_post() {
		return isset($this->request->server['REQUEST_METHOD']) && strtoupper($this->request->server['REQUEST_METHOD']) == 'POST';
	}

	private function json($data) {
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($data));
	}

	/**
	 * Cron-эндпоинт: POST + ключ. В query string ключ не принимается
	 *
	 * @return array|null ошибка либо null если доступ есть
	 */
	private function cron_guard() {
		$this->load->language('extension/shipping/apiship');

		if (!$this->is_post()) {
			return array('status' => 'error', 'error' => $this->language->get('shipping_apiship_error_method'));
		}

		require_once DIR_SYSTEM . 'library/apiship/apiship.php';

		if (!Apiship::check_cron_key($this->config->get('shipping_apiship_cron_key'), $this->request->server, $this->request->post)) {
			return array('status' => 'error', 'error' => $this->language->get('shipping_apiship_error_key'));
		}

		return null;
	}

	/**
	 * Действие с заказом из админки: POST, user_token из сессии администратора (защита от CSRF),
	 * авторизованный администратор с правом modify на sale/order
	 *
	 * @return array|null ошибка либо null если доступ есть
	 */
	private function admin_guard() {
		$this->load->language('extension/shipping/apiship');

		if (!$this->is_post()) {
			return array('error' => $this->language->get('shipping_apiship_error_method'));
		}

		$token = (isset($this->request->post['token']) && is_scalar($this->request->post['token'])) ? (string)$this->request->post['token'] : '';
		$session_token = isset($this->session->data['token']) ? (string)$this->session->data['token'] : '';

		if ($token == '' || $session_token == '' || !hash_equals($session_token, $token) || empty($this->session->data['user_id'])) {
			return array('error' => $this->language->get('shipping_apiship_error_permission'));
		}

		// Класс прав администратора из ядра: обычно подхватывается автозагрузчиком, иначе подключаем файл ядра
		if (!class_exists('Cart\\User')) {
			require_once DIR_SYSTEM . 'library/cart/user.php';
		}

		$user = new Cart\User($this->registry);

		if (!$user->isLogged() || !$user->hasPermission('modify', 'sale/order')) {
			return array('error' => $this->language->get('shipping_apiship_error_permission'));
		}

		return null;
	}

	public function set_point() {
		$this->load->model('extension/shipping/apiship');
		return $this->model_extension_shipping_apiship->set_point();
	}

	public function get_points() {
		$this->load->model('extension/shipping/apiship');
		return $this->model_extension_shipping_apiship->get_points();
	}

	public function export_order() {
		$results = $this->admin_guard();
		if ($results === null)
		{
			$this->load->model('extension/shipping/apiship');
			$results = $this->model_extension_shipping_apiship->export_order();
		}

		$this->json($results);
	}

	public function cancel_order() {
		$results = $this->admin_guard();
		if ($results === null)
		{
			$this->load->model('extension/shipping/apiship');
			$results = $this->model_extension_shipping_apiship->cancel_order();
		}

		$this->json($results);
	}

	public function import_orders() {
		$results = $this->cron_guard();
		if ($results === null)
		{
			$this->load->model('extension/shipping/apiship');
			$results = $this->model_extension_shipping_apiship->import_orders();
		}

		$this->json($results);
	}

	public function export_orders() {
		$results = $this->cron_guard();
		if ($results === null)
		{
			$this->load->model('extension/shipping/apiship');
			$results = $this->model_extension_shipping_apiship->export_orders();
		}

		$this->json($results);
	}

	public function get_label() {
		$results = $this->admin_guard();
		if ($results === null)
		{
			$this->load->model('extension/shipping/apiship');
			$results = $this->model_extension_shipping_apiship->get_label();
		} else {
			$results['labels'] = array();
		}

		$this->json($results);
	}

	public function get_waybill() {
		$results = $this->admin_guard();
		if ($results === null)
		{
			$this->load->model('extension/shipping/apiship');
			$results = $this->model_extension_shipping_apiship->get_waybill();
		} else {
			$results['waybills'] = array();
		}

		$this->json($results);
	}

	public function get_order_params() {
		$results = $this->admin_guard();
		if ($results === null)
		{
			$this->load->model('extension/shipping/apiship');
			$results = $this->model_extension_shipping_apiship->get_order_params();
		}

		$this->json($results);
	}

	/**
	 * Стоимость доставки без учёта правил (POST id, ключ cron). Модуль сам этот эндпоинт не вызывает —
	 * публичный URL для внешних интеграций; раньше был открыт без проверки
	 */
	public function get_delivery_cost_original() {
		$results = $this->cron_guard();
		if ($results === null)
		{
			$this->load->model('extension/shipping/apiship');
			$results = $this->model_extension_shipping_apiship->get_delivery_cost_original();
		}

		$this->json($results);
	}

	public function get_last_tracing_id() {
		$this->load->model('extension/shipping/apiship');
		$results = $this->model_extension_shipping_apiship->get_last_tracing_id();
		$this->json('x_tracing_id:' . $results);
	}

	public function get_point() {
		$this->load->model('extension/shipping/apiship');
		$results = $this->model_extension_shipping_apiship->get_point();
		$this->json($results);
	}


}
