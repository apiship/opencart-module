<?php
/**
 * Юнит-тесты библиотеки пакета 4.x без ядра OpenCart.
 * Запуск: make test (docker php:8.3-cli) или php dev/oc4/tests/run.php
 */
declare(strict_types=0);

namespace Opencart\System\Engine {
	// Минимальный реестр вместо ядра OpenCart
	class Registry {
		private array $data = [];

		public function get(string $key) {
			return $this->data[$key] ?? null;
		}

		public function set(string $key, $value): void {
			$this->data[$key] = $value;
		}

		public function has(string $key): bool {
			return isset($this->data[$key]);
		}
	}
}

namespace ApishipTests {
	require_once __DIR__ . '/../../../4.x/system/library/apiship.php';

	use Opencart\System\Library\Extension\Apiship\Apiship;

	class StubSession {
		public array $data = [];
	}

	class StubLog {
		public array $lines = [];

		public function write($message): void {
			$this->lines[] = (string)$message;
		}
	}

	class StubLoader {
		public function model(string $route): void {
		}
	}

	class StubProductModel {
		public array $products = [];

		public function getProduct(int $product_id): array {
			return $this->products[$product_id] ?? [];
		}
	}

	class StubConverter {
		public function convert(float $value, $from, $to): float {
			return $value;
		}
	}

	class StubCurrency {
		public function convert(float $value, string $from, string $to): float {
			return $value;
		}
	}

	class StubConfig {
		public function get(string $key) {
			return $key == 'config_currency' ? 'RUB' : null;
		}
	}

	function registry(): \Opencart\System\Engine\Registry {
		$registry = new \Opencart\System\Engine\Registry();

		$registry->set('session', new StubSession());
		$registry->set('log', new StubLog());
		$registry->set('load', new StubLoader());
		$registry->set('length', new StubConverter());
		$registry->set('weight', new StubConverter());
		$registry->set('currency', new StubCurrency());
		$registry->set('config', new StubConfig());

		$products = new StubProductModel();
		$products->products = [
			10 => ['sku' => 'SKU-10', 'upc' => '', 'weight_class_id' => 1, 'length_class_id' => 1],
			20 => ['sku' => '', 'upc' => 'UPC-20', 'weight_class_id' => 1, 'length_class_id' => 1]
		];

		$registry->set('model_catalog_product', $products);

		return $registry;
	}

	function library(array $params = []): Apiship {
		$registry = registry();

		$defaults = [
			'shipping_apiship_rub_select'   => 'RUB',
			'shipping_apiship_gr_select'    => 1,
			'shipping_apiship_cm_select'    => 1,
			'shipping_apiship_token'        => 'test',
			'shipping_apiship_mode'         => 'shipping_apiship_mode_normal',
			'shipping_apiship_provider'     => [],
			'shipping_apiship_articul_mode' => 'sku',
			'shipping_apiship_parcel_length' => 10,
			'shipping_apiship_parcel_width'  => 10,
			'shipping_apiship_parcel_height' => 10,
			'shipping_apiship_parcel_weight' => 500
		];

		return new Apiship($registry, $params + $defaults, $registry->get('log'));
	}

	$failures = 0;
	$passed = 0;

	function check(string $name, bool $condition, string $details = ''): void {
		global $failures, $passed;

		if ($condition) {
			$passed++;

			echo "  ok   $name\n";
		} else {
			$failures++;

			echo "  FAIL $name" . ($details ? " — $details" : '') . "\n";
		}
	}

	echo "parce_code\n";

	$lib = library();

	$point = $lib->parce_code('apiship.point_cdek_136_3_2');

	check('point: delivery_type', $point['delivery_type'] == 'point');
	check('point: provider', $point['provider'] == 'cdek');
	check('point: tariff_id', $point['tariff_id'] == '136');
	check('point: point_id', $point['point_id'] == '3');
	check('point: pickup_type', $point['pickup_type'] == '2');
	check('point: short_code', $point['short_code'] == 'point_cdek_136_3_2');

	$error = $lib->parce_code('apiship.point_boxberry_1_error_1');

	check('point error: point_id = error', $error['point_id'] == 'error');
	check('point error: pickup_type', $error['pickup_type'] == '1');

	$door = $lib->parce_code('apiship.door_cdek_137_1');

	check('door: delivery_type', $door['delivery_type'] == 'door');
	check('door: pickup_type taken from 4th part', $door['pickup_type'] == '1');
	check('door: point_id empty', $door['point_id'] === '');

	$empty = $lib->parce_code('flat.flat');

	check('foreign code: no crash, empty provider', $empty['provider'] === '' && $empty['short_code'] == 'flat');

	echo "get_address\n";

	check('no regionType → empty string', $lib->get_address(['region' => 'Москва']) === '');

	$address = $lib->get_address([
		'regionType' => 'г',
		'region'     => 'Москва',
		'city'       => 'Москва',
		'cityType'   => 'г',
		'street'     => 'Тверская',
		'streetType' => 'ул',
		'house'      => '12',
		'block'      => '',
		'office'     => '5'
	]);

	check('city-region address', $address === 'Москва, ул Тверская, д.12, офис 5', $address);

	$address = $lib->get_address([
		'regionType' => 'обл',
		'region'     => 'Московская',
		'city'       => 'Химки',
		'cityType'   => 'г',
		'street'     => 'Ленина',
		'streetType' => 'ул',
		'house'      => 'д. 7',
		'block'      => '2',
		'postIndex'  => '141400'
	], true);

	check('region address with postcode and block', $address === '141400, Московская обл, г Химки, ул Ленина д. 7 корпус 2', $address);

	echo "calculate_places\n";

	$products = [
		['product_id' => 10, 'name' => 'Товар A', 'model' => 'A', 'quantity' => 2, 'price' => 100, 'length' => 20, 'width' => 10, 'height' => 5, 'length_class_id' => 1, 'weight' => 800, 'weight_class_id' => 1],
		['product_id' => 20, 'name' => 'Товар B', 'model' => 'B', 'quantity' => 1, 'price' => 50, 'length' => 0, 'width' => 0, 'height' => 0, 'length_class_id' => 1, 'weight' => 0, 'weight_class_id' => 1]
	];

	$result = $lib->calculate_places($products, 200.0);

	$items_total = 0;

	foreach ($result['items'] as $item) {
		$items_total += $item['cost'] * $item['quantity'];
	}

	check('items cost sum equals order total after discount', abs($items_total - 200.0) < 0.01, (string)$items_total);
	check('articul from sku', in_array('SKU-10', array_column($result['items'], 'articul')));
	check('fallback to parcel defaults for product without dimensions', $result['total_weight'] == 800 + 500, (string)$result['total_weight']);
	check('place length = max side', $result['total_length'] == 20, (string)$result['total_length']);
	check('place height = sum of smallest sides', $result['total_height'] == 5 + 5 + 10, (string)$result['total_height']);
	check('assessed cost equals items cost by default', abs($result['assessed_cost'] - 200.0) < 0.01, (string)$result['assessed_cost']);

	$fixed = library(['shipping_apiship_use_fix_product_assessed_cost' => 1, 'shipping_apiship_fix_product_assessed_cost' => 10])->calculate_places($products, 200.0);

	check('fixed assessed cost per item', abs($fixed['assessed_cost'] - 30.0) < 0.01, (string)$fixed['assessed_cost']);

	$override = library(['shipping_apiship_place_weight' => 1200, 'shipping_apiship_package_weight' => 100])->calculate_places($products, 200.0);

	check('place weight override + package weight', $override['total_weight'] == 1300, (string)$override['total_weight']);

	echo "session cache\n";

	$lib->setData('k', ['a' => 1], 10);

	check('getData returns stored value', $lib->getData('k') == ['a' => 1]);
	check('getData null for missing key', $lib->getData('missing') === null);

	$lib->setData('expired', 'x', -1);

	check('expired value is dropped', $lib->getData('expired') === null);

	echo "calculator\n";

	$calc = $lib->apiship_calculator('RU', 'Москва', '', '', '', [], $products, 200.0, false);

	check('empty city → message without network call', isset($calc['body']['message']));

	echo "format\n";

	check('format_cost rounds to 2 digits', $lib->format_cost(10.005) == 10.01);
	check('format_weight floors', $lib->format_weight(10.9) == 10.0);
	check('format_dimension rounds', $lib->format_dimension(10.5) == 11.0);

	echo "\n" . ($failures ? "FAILED: $failures, passed: $passed" : "All $passed tests passed") . "\n";

	exit($failures ? 1 : 0);
}
