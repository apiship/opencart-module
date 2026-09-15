/**
 * Тест хелпера скрипта карты ApishipMap.pointTariffs без DOM и Яндекс.Карт: тарифы точки из ответа get_points
 * ({points, tariffs}) с кодом варианта по code_template. Проверяет скрипт пакета 4.x и общий скрипт пакетов 2.1/2.3/3.x.
 * Запуск: make test-js или node dev/oc4/tests/map.js
 */
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const SCRIPTS = [
	'4.x/catalog/view/javascript/apiship.js',
	'3.x/upload/catalog/view/javascript/apiship.js',
	'2.3/upload/catalog/view/javascript/apiship.js',
	'2.1/upload/catalog/view/javascript/apiship.js'
];

let passed = 0;
let failures = 0;

function check(name, condition, details) {
	if (condition) {
		passed++;
		console.log('  ok   ' + name);
	} else {
		failures++;
		console.log('  FAIL ' + name + (details ? ' — ' + details : ''));
	}
}

for (const file of SCRIPTS) {
	console.log(file);

	const source = fs.readFileSync(path.join(__dirname, '..', '..', '..', file), 'utf8');

	// Класс объявлен на верхнем уровне скрипта: последнее выражение возвращает его без DOM, jQuery и ymaps
	const ApishipMap = vm.runInNewContext(source + '\nApishipMap;', {});

	const tariffs = {
		cdek_136_1: {code_template: 'apiship.point_cdek_136_{point_id}_1', tariff: 'A', text: '350 р.', cost: 350, provider: 'СДЭК', provider_key: 'cdek'},
		cdek_137_1: {code_template: 'apiship.point_cdek_137_{point_id}_1', tariff: 'B', text: '500 р.', cost: '500', provider: 'СДЭК', provider_key: 'cdek'},
		broken: {tariff: 'no template'}
	};

	const list = ApishipMap.pointTariffs({id: 3, tariffs: ['cdek_137_1', 'cdek_136_1', 'missing', 'broken']}, tariffs);

	check('tariffs of a point sorted by cost, unknown and broken keys skipped', list.length === 2 && list[0].code === 'apiship.point_cdek_136_3_1' && list[1].code === 'apiship.point_cdek_137_3_1', JSON.stringify(list.map((t) => t.code)));
	check('cost is a number even when the server sent a string', list[1].cost === 500 && list[0].tariff === 'A');
	check('source dictionary is not mutated', tariffs.cdek_136_1.code === undefined && tariffs.cdek_137_1.cost === '500');
	check('point without tariffs or without dictionary gives an empty list', ApishipMap.pointTariffs({id: 1}, tariffs).length === 0 && ApishipMap.pointTariffs({id: 1, tariffs: ['cdek_136_1']}, null).length === 0);
	check('inherited object keys are not tariffs', ApishipMap.pointTariffs({id: 1, tariffs: ['toString']}, tariffs).length === 0);
	check('escapeHtml', ApishipMap.escapeHtml('<b>"x"</b>') === '&lt;b&gt;&quot;x&quot;&lt;/b&gt;');
}

console.log('\n' + (failures ? 'FAILED: ' + failures + ', passed: ' + passed : 'All ' + passed + ' tests passed'));

process.exit(failures ? 1 : 0);
