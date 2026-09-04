<?php
namespace Opencart\Admin\Controller\Extension\Apiship\Event;
/**
 * Class Order
 *
 * Обработчики событий админки (регистрируются при установке модуля):
 * - admin/view/sale/order_info/before → info      (вкладка «ApiShip» на странице заказа)
 * - admin/view/sale/order_info/after  → infoAfter (поиск ПВЗ в модалке выбора способа доставки)
 * - admin/view/sale/order/after       → list      (кнопки «Ярлык» и «Акт» в списке заказов)
 *
 * @package Opencart\Admin\Controller\Extension\Apiship\Event
 */
class Order extends \Opencart\System\Engine\Controller {
	/**
	 * @param string               $route
	 * @param array<string, mixed> $data
	 * @param string               $code
	 * @param string               $output
	 *
	 * @return void
	 */
	public function info(string &$route, array &$data, string &$code, string &$output): void {
		// Вкладка содержит cron-ключ, которым выполняются экспорт и отмена: только для пользователей с правом изменять заказы
		if (!$this->config->get('shipping_apiship_status') || !$this->user->hasPermission('modify', 'sale/order')) {
			return;
		}

		$order_id = (int)($data['order_id'] ?? ($this->request->get['order_id'] ?? 0));

		if (!$order_id) {
			return;
		}

		$this->load->model('sale/order');

		$order_info = $this->model_sale_order->getOrder($order_id);

		if (!$order_info) {
			return;
		}

		$shipping_code = (string)($order_info['shipping_method']['code'] ?? '');

		if (strpos($shipping_code, 'apiship') === false) {
			return;
		}

		$this->load->language('extension/apiship/shipping/apiship');

		$tab_data = $this->language->all();

		$catalog = HTTP_CATALOG . 'index.php?route=extension/apiship/shipping/apiship.';
		$key = '&key=' . urlencode((string)$this->config->get('shipping_apiship_cron_key'));

		$tab_data['order_id'] = $order_id;
		$tab_data['user_token'] = $this->session->data['user_token'];
		$tab_data['export_url'] = $catalog . 'export_order&id=' . $order_id . $key;
		$tab_data['export_cancel_url'] = $catalog . 'cancel_order&id=' . $order_id . $key;
		$tab_data['get_order_params_url'] = $catalog . 'get_order_params&id=' . $order_id . $key;
		$tab_data['label_url'] = $catalog . 'get_label' . $key;
		$tab_data['waybill_url'] = $catalog . 'get_waybill' . $key;
		$tab_data['history_url'] = $this->url->link('sale/order.history', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id, true);

		if (!isset($data['tabs']) || !is_array($data['tabs'])) {
			$data['tabs'] = [];
		}

		$data['tabs'][] = [
			'code'    => 'apiship',
			'title'   => $this->language->get('text_apiship'),
			'content' => $this->load->view('extension/apiship/event/order_info', $tab_data)
		];
	}

	/**
	 * @param string               $route
	 * @param array<string, mixed> $data
	 * @param string               $output
	 *
	 * @return void
	 */
	public function infoAfter(string &$route, array &$data, string &$output): void {
		if (!$this->config->get('shipping_apiship_status') || !$this->user->hasPermission('modify', 'sale/order')) {
			return;
		}

		$this->load->language('extension/apiship/shipping/apiship');

		$script_data = $this->language->all();

		$script_data['user_token'] = $this->session->data['user_token'];

		$output = $this->inject($output, $this->load->view('extension/apiship/event/order_shipping_search', $script_data));
	}

	/**
	 * @param string               $route
	 * @param array<string, mixed> $data
	 * @param string               $output
	 *
	 * @return void
	 */
	public function list(string &$route, array &$data, string &$output): void {
		// Кнопки ярлыков и актов содержат cron-ключ: только для пользователей с правом изменять заказы
		if (!$this->config->get('shipping_apiship_status') || !$this->user->hasPermission('modify', 'sale/order')) {
			return;
		}

		$this->load->language('extension/apiship/shipping/apiship');

		$list_data = $this->language->all();

		$catalog = HTTP_CATALOG . 'index.php?route=extension/apiship/shipping/apiship.';
		$key = '&key=' . urlencode((string)$this->config->get('shipping_apiship_cron_key'));

		$list_data['label_url'] = $catalog . 'get_label' . $key;
		$list_data['waybill_url'] = $catalog . 'get_waybill' . $key;

		$output = $this->inject($output, $this->load->view('extension/apiship/event/order_list', $list_data));
	}

	/**
	 * Вставка html перед закрывающим body
	 *
	 * @param string $output
	 * @param string $html
	 *
	 * @return string
	 */
	private function inject(string $output, string $html): string {
		$pos = strripos($output, '</body>');

		if ($pos === false) {
			return $output . $html;
		}

		return substr($output, 0, $pos) . $html . substr($output, $pos);
	}
}
