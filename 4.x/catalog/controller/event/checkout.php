<?php
namespace Opencart\Catalog\Controller\Extension\Apiship\Event;
/**
 * Class Checkout
 *
 * Обработчики событий витрины (регистрируются при установке модуля):
 * - catalog/view/checkout/shipping_method/after            → shippingMethod  (скрипт карты ПВЗ в чекауте)
 * - catalog/controller/checkout/shipping_method.save/after → shippingMethodSave (нельзя продолжить без выбранного ПВЗ)
 *
 * @package Opencart\Catalog\Controller\Extension\Apiship\Event
 */
class Checkout extends \Opencart\System\Engine\Controller {
	/**
	 * @param string               $route
	 * @param array<string, mixed> $data
	 * @param string               $output
	 *
	 * @return void
	 */
	public function shippingMethod(string &$route, array &$data, string &$output): void {
		if (!$this->config->get('shipping_apiship_status')) {
			return;
		}

		$this->load->language('extension/apiship/shipping/apiship');

		$language = 'language=' . $this->config->get('config_language');

		$script_data = [
			'get_points_url'     => $this->url->link('extension/apiship/shipping/apiship.get_points', $language, true),
			'set_point_url'      => $this->url->link('extension/apiship/shipping/apiship.set_point', $language, true),
			'tracing_url'        => $this->url->link('extension/apiship/shipping/apiship.get_last_tracing_id', $language, true),
			'selected_url'       => $this->url->link('extension/apiship/shipping/apiship.get_selected', $language, true),
			'confirm_url'        => $this->url->link('checkout/confirm.confirm', $language, true),
			'yandex_api_key'     => (string)$this->config->get('shipping_apiship_yandex_api_key'),
			// Адрес скрипта и стилей меняется и при обновлении модуля, и при пересохранении настроек
			'version'            => \Opencart\System\Library\Extension\Apiship\Apiship::VERSION . '-' . (string)($this->config->get('shipping_apiship_version_js_mod') ?: '0'),
			'image_path'         => 'extension/apiship/catalog/view/image/',
			// Подписи карты одним json: набор общий для всех пакетов модуля и собирается в библиотеке
			'texts_json'         => json_encode(
				\Opencart\System\Library\Extension\Apiship\Apiship::map_texts($this->language),
				JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
			)
		];

		$output .= $this->load->view('extension/apiship/event/checkout_script', $script_data);
	}

	/**
	 * После выбора способа оплаты пересчитывает выбранный вариант ApiShip: наложенный платёж меняет стоимость,
	 * а в чекауте OC4 оплата выбирается после доставки
	 *
	 * @param string       $route
	 * @param array<mixed> $args
	 * @param mixed        $output
	 *
	 * @return void
	 */
	public function paymentMethodSave(string &$route, array &$args, &$output): void {
		if (!$this->config->get('shipping_apiship_status') || !isset($this->session->data['payment_method'])) {
			return;
		}

		$code = (string)($this->session->data['shipping_method']['code'] ?? '');

		if (!str_starts_with($code, 'apiship.')) {
			return;
		}

		$this->load->model('extension/apiship/shipping/apiship');

		$quote = $this->model_extension_apiship_shipping_apiship->refresh_quote($code);

		// Пересчёт не удался (калькулятор недоступен, тариф больше не предлагается): старую цену оставлять нельзя —
		// сбрасываем способ доставки, покупатель выбирает его заново; ядро без shipping_method заказ не оформит
		if (!$quote) {
			unset($this->session->data['shipping_method']);

			$this->load->language('extension/apiship/shipping/apiship');

			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode(['error' => $this->language->get('shipping_apiship_error_recalculate')]));
		}
	}

	/**
	 * @param string       $route
	 * @param array<mixed> $args
	 * @param mixed        $output
	 *
	 * @return void
	 */
	public function shippingMethodSave(string &$route, array &$args, &$output): void {
		$code = (string)($this->session->data['shipping_method']['code'] ?? '');

		if ($code != '' && str_contains($code, 'apiship') && str_contains($code, 'error')) {
			unset($this->session->data['shipping_method']);

			$this->load->language('extension/apiship/shipping/apiship');

			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode(['error' => $this->language->get('shipping_apiship_error_select_point')]));
		}
	}
}
