<?php
namespace Opencart\Admin\Controller\Extension\Apiship\Sale;
/**
 * Class Order
 *
 * Действия с заказом из админки: экспорт в ApiShip, отмена, ярлыки, акты, параметры экспорта.
 * Авторизация — сессия админки (user_token): ядро OC4 пускает на маршрут только с правом access на него,
 * сами действия требуют modify на этот маршрут и на sale/order. Права выдаются при установке группе
 * установившего; другим группам — вручную (README). Cron-ключ сюда не попадает.
 * Модель витрины вызывается через экземпляр магазина (как sale/order.call в ядре OC4).
 *
 * Маршруты: index.php?route=extension/apiship/sale/order.<method>&user_token=…
 *
 * @package Opencart\Admin\Controller\Extension\Apiship\Sale
 */
class Order extends \Opencart\System\Engine\Controller {
	private const ROUTE = 'extension/apiship/sale/order';

	/**
	 * Проверка прав: любое действие с заказом требует modify на этот маршрут и на sale/order
	 * (access на маршрут ядро проверяет до вызова контроллера)
	 *
	 * @return string текст ошибки или '' если доступ есть
	 */
	private function permission_error(): string {
		$this->load->language('extension/apiship/shipping/apiship');

		if (!$this->user->hasPermission('modify', self::ROUTE) || !$this->user->hasPermission('modify', 'sale/order')) {
			return $this->language->get('error_permission');
		}

		return '';
	}

	/**
	 * Вызов модели витрины в контексте магазина заказа (как sale/order.call в ядре OC4): валюта заказа кладётся
	 * в сессию экземпляра, её читают форматтеры стоимости модели; после вызова сессия экземпляра уничтожается
	 *
	 * @param int      $order_id
	 * @param callable $action функция от модели extension/apiship/shipping/apiship витрины
	 *
	 * @return mixed результат $action
	 */
	private function call(int $order_id, callable $action) {
		$this->load->model('sale/order');

		$order_info = $order_id ? $this->model_sale_order->getOrder($order_id) : [];

		$store_id = (int)($order_info['store_id'] ?? 0);
		$language = (string)($order_info['language_code'] ?? $this->config->get('config_language'));
		$currency = (string)($order_info['currency_code'] ?? $this->config->get('config_currency'));

		$this->load->model('setting/store');

		$store = $this->model_setting_store->createStoreInstance($store_id, $language, $currency);

		$store->session->data['currency'] = $currency;

		$store->load->model('extension/apiship/shipping/apiship');

		$result = $action($store->model_extension_apiship_shipping_apiship);

		// Сессия экземпляра одноразовая: уничтожаем после вызова, как ядро в sale/order.call
		$store->session->destroy();

		return $result;
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
	 * @return array<int, int>
	 */
	private function ids(): array {
		// Список заказов и вкладка заказа шлют строку «1,2,3»; массив id[] тоже принимаем
		$id = $this->request->post['id'] ?? '';

		$values = is_array($id) ? $id : explode(',', (string)$id);

		$ids = [];

		foreach ($values as $value) {
			if (is_scalar($value) && (int)$value > 0) {
				$ids[] = (int)$value;
			}
		}

		return array_values(array_unique($ids));
	}

	/**
	 * Параметры экспорта для вкладки заказа (GET order_id)
	 *
	 * @return void
	 */
	public function params(): void {
		$error = $this->permission_error();

		if ($error) {
			$this->json(['error' => $error]);

			return;
		}

		$order_id = (int)($this->request->get['order_id'] ?? 0);

		$this->json($this->call($order_id, fn($model) => $model->get_order_params($order_id)));
	}

	/**
	 * Экспорт заказа в ApiShip (POST order_id + параметры места)
	 *
	 * @return void
	 */
	public function export(): void {
		$error = $this->permission_error();

		if ($error) {
			$this->json(['error' => $error]);

			return;
		}

		$order_id = (int)($this->request->post['order_id'] ?? 0);

		$this->json($this->call($order_id, fn($model) => $model->export_order($order_id, $this->request->post)));
	}

	/**
	 * Отмена заказа в ApiShip (POST order_id)
	 *
	 * @return void
	 */
	public function cancel(): void {
		$error = $this->permission_error();

		if ($error) {
			$this->json(['error' => $error]);

			return;
		}

		$order_id = (int)($this->request->post['order_id'] ?? 0);

		$this->json($this->call($order_id, fn($model) => $model->cancel_order($order_id)));
	}

	/**
	 * Ярлыки (POST id — список order_id через запятую)
	 *
	 * @return void
	 */
	public function label(): void {
		$error = $this->permission_error();

		if ($error) {
			$this->json(['labels' => [], 'error' => $error]);

			return;
		}

		$ids = $this->ids();

		$this->json($this->call($ids[0] ?? 0, fn($model) => $model->get_label($ids)));
	}

	/**
	 * Акты приёма-передачи (POST id — список order_id через запятую)
	 *
	 * @return void
	 */
	public function waybill(): void {
		$error = $this->permission_error();

		if ($error) {
			$this->json(['waybills' => [], 'error' => $error]);

			return;
		}

		$ids = $this->ids();

		$this->json($this->call($ids[0] ?? 0, fn($model) => $model->get_waybill($ids)));
	}
}
