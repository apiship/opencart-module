<?php
namespace Opencart\Catalog\Controller\Extension\Apiship\Shipping;
/**
 * Class Apiship
 *
 * Ajax-эндпоинты витрины и cron-эндпоинты модуля.
 * Маршруты: index.php?route=extension/apiship/shipping/apiship.<method>
 *
 * @package Opencart\Catalog\Controller\Extension\Apiship\Shipping
 */
class Apiship extends \Opencart\System\Engine\Controller {
	/**
	 * Проверка ключа для cron-эндпоинтов и вызовов из админки
	 *
	 * @return bool
	 */
	private function check_key(): bool {
		$key = $this->request->get['key'] ?? ($this->request->post['key'] ?? '');

		$config_key = (string)$this->config->get('shipping_apiship_cron_key');

		return $config_key != '' && hash_equals($config_key, (string)$key);
	}

	/**
	 * @param mixed $data
	 *
	 * @return void
	 */
	private function json($data): void {
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($data));
	}

	/**
	 * @return array<string, mixed>
	 */
	private function error_key(): array {
		$this->load->language('extension/apiship/shipping/apiship');

		return ['status' => 'error', 'error' => $this->language->get('shipping_apiship_error_key')];
	}

	/**
	 * Точки для карты (POST code)
	 *
	 * @return void
	 */
	public function get_points(): void {
		$this->load->model('extension/apiship/shipping/apiship');

		$code = (string)($this->request->post['code'] ?? '');

		$this->json($this->model_extension_apiship_shipping_apiship->get_points($code));
	}

	/**
	 * Выбор ПВЗ (POST shipping_apiship_point)
	 *
	 * @return void
	 */
	public function set_point(): void {
		$this->load->model('extension/apiship/shipping/apiship');

		$code = (string)($this->request->post['shipping_apiship_point'] ?? '');

		$this->json($this->model_extension_apiship_shipping_apiship->set_point($code));
	}

	/**
	 * Экспорт заказа (из карточки заказа в админке)
	 *
	 * @return void
	 */
	public function export_order(): void {
		if (!$this->check_key()) {
			$this->json($this->error_key());

			return;
		}

		$this->load->model('extension/apiship/shipping/apiship');

		$params = array_merge($this->request->get, $this->request->post);

		$order_id = (int)($params['id'] ?? 0);

		$this->json($this->model_extension_apiship_shipping_apiship->export_order($order_id, $params));
	}

	/**
	 * Отмена заказа в ApiShip
	 *
	 * @return void
	 */
	public function cancel_order(): void {
		if (!$this->check_key()) {
			$this->json($this->error_key());

			return;
		}

		$this->load->model('extension/apiship/shipping/apiship');

		$order_id = (int)($this->request->get['id'] ?? ($this->request->post['id'] ?? 0));

		$this->json($this->model_extension_apiship_shipping_apiship->cancel_order($order_id));
	}

	/**
	 * Cron: импорт статусов
	 *
	 * @return void
	 */
	public function import_orders(): void {
		if (!$this->check_key()) {
			$this->json($this->error_key());

			return;
		}

		$this->load->model('extension/apiship/shipping/apiship');

		$this->json($this->model_extension_apiship_shipping_apiship->import_orders());
	}

	/**
	 * Cron: групповой экспорт
	 *
	 * @return void
	 */
	public function export_orders(): void {
		if (!$this->check_key()) {
			$this->json($this->error_key());

			return;
		}

		$this->load->model('extension/apiship/shipping/apiship');

		$this->json($this->model_extension_apiship_shipping_apiship->export_orders());
	}

	/**
	 * Ярлыки (POST id — список order_id через запятую)
	 *
	 * @return void
	 */
	public function get_label(): void {
		if (!$this->check_key()) {
			$this->json(['labels' => []] + $this->error_key());

			return;
		}

		$this->load->model('extension/apiship/shipping/apiship');

		$this->json($this->model_extension_apiship_shipping_apiship->get_label($this->get_ids()));
	}

	/**
	 * Акты приёма-передачи (POST id — список order_id через запятую)
	 *
	 * @return void
	 */
	public function get_waybill(): void {
		if (!$this->check_key()) {
			$this->json(['waybills' => []] + $this->error_key());

			return;
		}

		$this->load->model('extension/apiship/shipping/apiship');

		$this->json($this->model_extension_apiship_shipping_apiship->get_waybill($this->get_ids()));
	}

	/**
	 * @return array<int, int>
	 */
	private function get_ids(): array {
		$id = (string)($this->request->post['id'] ?? ($this->request->get['id'] ?? ''));

		$ids = [];

		foreach (explode(',', $id) as $value) {
			if ((int)$value > 0) {
				$ids[] = (int)$value;
			}
		}

		return $ids;
	}

	/**
	 * Параметры экспорта для карточки заказа
	 *
	 * @return void
	 */
	public function get_order_params(): void {
		if (!$this->check_key()) {
			$this->json($this->error_key());

			return;
		}

		$this->load->model('extension/apiship/shipping/apiship');

		$order_id = (int)($this->request->get['id'] ?? 0);

		$this->json($this->model_extension_apiship_shipping_apiship->get_order_params($order_id));
	}

	/**
	 * Стоимость доставки без учёта правил
	 *
	 * @return void
	 */
	public function get_delivery_cost_original(): void {
		if (!$this->check_key()) {
			$this->json($this->error_key());

			return;
		}

		$this->load->model('extension/apiship/shipping/apiship');

		$order_id = (int)($this->request->get['id'] ?? 0);

		$this->json($this->model_extension_apiship_shipping_apiship->get_delivery_cost_original($order_id));
	}

	/**
	 * Последний x-tracing-id расчёта (для отладки)
	 *
	 * @return void
	 */
	public function get_last_tracing_id(): void {
		$this->load->model('extension/apiship/shipping/apiship');

		$this->json('x_tracing_id:' . $this->model_extension_apiship_shipping_apiship->get_last_tracing_id());
	}
}
