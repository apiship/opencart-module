<?php
namespace Opencart\Admin\Controller\Extension\Apiship\Event;
/**
 * Class Order
 *
 * Обработчики событий админки (регистрируются при установке модуля). Действия с заказом выполняет
 * admin-контроллер extension/apiship/sale/order: ядро OC4 пускает на него только с правом access,
 * сам контроллер требует modify на этот маршрут и на sale/order. При установке права выдаются группе
 * установившего; другим группам их выдают в Группах пользователей (см. README), иначе вкладка показывает
 * предупреждение, а кнопок в списке заказов нет.
 * - admin/view/sale/order_info/before → info      (вкладка «ApiShip» на странице заказа)
 * - admin/view/sale/order_info/after  → infoAfter (поиск ПВЗ в модалке выбора способа доставки)
 * - admin/view/sale/order/after       → list      (кнопки «Ярлык» и «Акт» в списке заказов)
 *
 * @package Opencart\Admin\Controller\Extension\Apiship\Event
 */
class Order extends \Opencart\System\Engine\Controller {
	private const ROUTE = 'extension/apiship/sale/order';

	/**
	 * Может ли пользователь выполнять действия ApiShip: те же права, что проверяют ядро (access на маршрут)
	 * и контроллер действий (modify на маршрут и на sale/order)
	 *
	 * @return bool
	 */
	private function can_act(): bool {
		return $this->user->hasPermission('access', self::ROUTE) && $this->user->hasPermission('modify', self::ROUTE) && $this->user->hasPermission('modify', 'sale/order');
	}

	/**
	 * @param string               $route
	 * @param array<string, mixed> $data
	 * @param string               $code
	 * @param string               $output
	 *
	 * @return void
	 */
	public function info(string &$route, array &$data, string &$code, string &$output): void {
		// Экспорт и отмена меняют заказ: вкладка только для пользователей с правом изменять заказы
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

		// Все действия — через admin-контроллер с user_token; cron-ключ в html не попадает
		$token = 'user_token=' . $this->session->data['user_token'];

		$tab_data['order_id'] = $order_id;
		$tab_data['user_token'] = $this->session->data['user_token'];
		// Нет прав на маршрут действий — вкладка с подсказкой, какие права выдать, вместо молчащих кнопок
		$tab_data['apiship_permission_error'] = $this->can_act() ? '' : sprintf($this->language->get('error_shipping_apiship_order_permission'), self::ROUTE);
		$tab_data['export_url'] = $this->url->link('extension/apiship/sale/order.export', $token, true);
		$tab_data['export_cancel_url'] = $this->url->link('extension/apiship/sale/order.cancel', $token, true);
		$tab_data['get_order_params_url'] = $this->url->link('extension/apiship/sale/order.params', $token . '&order_id=' . $order_id, true);
		$tab_data['label_url'] = $this->url->link('extension/apiship/sale/order.label', $token, true);
		$tab_data['waybill_url'] = $this->url->link('extension/apiship/sale/order.waybill', $token, true);
		$tab_data['history_url'] = $this->url->link('sale/order.history', $token . '&order_id=' . $order_id, true);

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
		// Ярлыки и акты идут через маршрут действий: кнопки только тем, кого он пустит
		if (!$this->config->get('shipping_apiship_status') || !$this->can_act()) {
			return;
		}

		$this->load->language('extension/apiship/shipping/apiship');

		$list_data = $this->language->all();

		$token = 'user_token=' . $this->session->data['user_token'];

		$list_data['label_url'] = $this->url->link('extension/apiship/sale/order.label', $token, true);
		$list_data['waybill_url'] = $this->url->link('extension/apiship/sale/order.waybill', $token, true);

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
