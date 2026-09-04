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
			'version'            => (string)($this->config->get('shipping_apiship_version_js_mod') ?: '1.3'),
			'image_path'         => 'extension/apiship/catalog/view/image/',
			'text_from'          => $this->language->get('shipping_apiship_title_from'),
			'text_select_point'  => $this->language->get('shipping_apiship_select_point'),
			'text_change_point'  => $this->language->get('shipping_apiship_change_point'),
			'text_map_title'     => $this->language->get('shipping_apiship_map_title'),
			'text_map_cost'      => $this->language->get('shipping_apiship_map_cost'),
			'text_map_take_here' => $this->language->get('shipping_apiship_map_take_here'),
			'text_map_type'      => $this->language->get('shipping_apiship_map_point_type'),
			'text_map_provider'  => $this->language->get('shipping_apiship_map_provider'),
			'text_map_cash'      => $this->language->get('shipping_apiship_map_payment_cash'),
			'text_map_card'      => $this->language->get('shipping_apiship_map_payment_card'),
			'text_map_no_points' => $this->language->get('shipping_apiship_error_no_points'),
			'text_map_load'      => $this->language->get('shipping_apiship_error_map_load'),
			'text_recalculate'   => $this->language->get('shipping_apiship_error_recalculate')
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
