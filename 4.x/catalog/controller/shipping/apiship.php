<?php
namespace Opencart\Catalog\Controller\Extension\Apiship\Shipping;
/**
 * Class Apiship
 *
 * Эндпоинты витрины: ajax чекаута (сессия покупателя) и cron (ключ из настроек).
 * Действия с заказами из админки идут через admin-контроллер extension/apiship/shipping/order.
 * Маршруты: index.php?route=extension/apiship/shipping/apiship.<method>
 *
 * @package Opencart\Catalog\Controller\Extension\Apiship\Shipping
 */
class Apiship extends \Opencart\System\Engine\Controller {
	/**
	 * Ключ cron: заголовок X-Apiship-Key либо POST-поле key. В query string ключ не принимается,
	 * чтобы он не оседал в логах веб-сервера и прокси
	 *
	 * @return bool
	 */
	private function check_key(): bool {
		$key = (string)($this->request->server['HTTP_X_APISHIP_KEY'] ?? ($this->request->post['key'] ?? ''));

		$config_key = (string)$this->config->get('shipping_apiship_cron_key');

		return $config_key != '' && $key != '' && hash_equals($config_key, $key);
	}

	/**
	 * Изменяющие действия только POST
	 *
	 * @return bool
	 */
	private function is_post(): bool {
		return strtoupper((string)($this->request->server['REQUEST_METHOD'] ?? '')) == 'POST';
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
	 * Cron-эндпоинт: POST + ключ
	 *
	 * @return array<string, mixed>|null ошибка либо null если доступ есть
	 */
	private function cron_guard(): ?array {
		if (!$this->is_post()) {
			$this->load->language('extension/apiship/shipping/apiship');

			return ['status' => 'error', 'error' => $this->language->get('shipping_apiship_error_method')];
		}

		if (!$this->check_key()) {
			return $this->error_key();
		}

		return null;
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
	 * Cron: импорт статусов (POST, ключ)
	 *
	 * @return void
	 */
	public function import_orders(): void {
		$error = $this->cron_guard();

		if ($error) {
			$this->json($error);

			return;
		}

		$this->load->model('extension/apiship/shipping/apiship');

		$this->json($this->model_extension_apiship_shipping_apiship->import_orders());
	}

	/**
	 * Cron: групповой экспорт (POST, ключ)
	 *
	 * @return void
	 */
	public function export_orders(): void {
		$error = $this->cron_guard();

		if ($error) {
			$this->json($error);

			return;
		}

		$this->load->model('extension/apiship/shipping/apiship');

		$this->json($this->model_extension_apiship_shipping_apiship->export_orders());
	}

	/**
	 * Стоимость доставки без учёта правил (POST order_id, ключ)
	 *
	 * Модуль сам этот эндпоинт не вызывает: публичный URL для внешних интеграций и модов,
	 * сохранён для паритета с пакетом 3.x.
	 *
	 * @return void
	 */
	public function get_delivery_cost_original(): void {
		$error = $this->cron_guard();

		if ($error) {
			$this->json($error);

			return;
		}

		$this->load->model('extension/apiship/shipping/apiship');

		$order_id = (int)($this->request->post['order_id'] ?? ($this->request->post['id'] ?? 0));

		$this->json($this->model_extension_apiship_shipping_apiship->get_delivery_cost_original($order_id));
	}

	/**
	 * Выбранный в сессии способ доставки (обновление текста в чекауте после пересчёта)
	 *
	 * @return void
	 */
	public function get_selected(): void {
		$selected = $this->session->data['shipping_method'] ?? [];

		$json = [];

		if (isset($selected['code']) && str_starts_with((string)$selected['code'], 'apiship.')) {
			$json = [
				'code' => $selected['code'],
				'name' => $selected['name'] ?? '',
				'text' => $selected['text'] ?? ''
			];
		}

		$this->json($json);
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
