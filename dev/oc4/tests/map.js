/**
 * Тесты чистых хелперов скрипта карты ApishipMap без DOM и Яндекс.Карт: тарифы точки, подписи карточки,
 * фильтры служб и типов, сеточная кластеризация. Скрипт одинаков во всех пакетах модуля — это тоже проверяется.
 * Запуск: make test-js или node dev/oc4/tests/map.js
 */
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const MAIN = '4.x/catalog/view/javascript/apiship.js';

const COPIES = [
	'3.x/upload/catalog/view/javascript/apiship.js',
	'2.3/upload/catalog/view/javascript/apiship.js',
	'2.1/upload/catalog/view/javascript/apiship.js'
];

const root = path.join(__dirname, '..', '..', '..');

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

const source = fs.readFileSync(path.join(root, MAIN), 'utf8');

// Класс объявлен на верхнем уровне скрипта: последнее выражение возвращает его без DOM, jQuery и ymaps3
const ApishipMap = vm.runInNewContext(source + '\nApishipMap;', {});

const texts = {
	from: 'от',
	map_days: ['день', 'дня', 'дней'],
	map_points: ['пункт', 'пункта', 'пунктов'],
	map_ways: ['способ доставки', 'способа доставки', 'способов доставки'],
	map_more_ways: 'Ещё {n} {ways}',
	map_faster: 'Быстрее — {days} за {price}'
};

const tariffs = {
	bb_1_1: {code_template: 'apiship.point_boxberry_1_{point_id}_1', tariff: 'Стандарт', text: '123 ₽', cost: 123, provider: 'Boxberry', provider_key: 'boxberry', days_min: 2, days_max: 3},
	cdek_2_1: {code_template: 'apiship.point_cdek_2_{point_id}_1', tariff: 'Экономичная посылка', text: '130 ₽', cost: '130', provider: 'СДЭК', provider_key: 'cdek', days_min: 4, days_max: 5},
	bb_3_1: {code_template: 'apiship.point_boxberry_3_{point_id}_1', tariff: 'Экспресс', text: '260 ₽', cost: 260, provider: 'Boxberry', provider_key: 'boxberry', days_min: 1, days_max: 1},
	broken: {tariff: 'no template'}
};

const points = [
	{id: 3, lon: 37.6, lat: 55.75, type: 'Пункт выдачи заказа', tariffs: ['bb_3_1', 'cdek_2_1', 'bb_1_1', 'missing', 'broken']},
	{id: 4, lon: 37.6005, lat: 55.7503, type: 'Пункт выдачи заказа', tariffs: ['cdek_2_1']},
	{id: 5, lon: 39.9, lat: 57.62, type: 'Постамат', tariffs: ['bb_1_1']}
];

console.log(MAIN);

const list = ApishipMap.pointTariffs(points[0], tariffs);

check('tariffs of a point sorted by cost, unknown and broken keys skipped', list.length === 3 && list[0].code === 'apiship.point_boxberry_1_3_1' && list[2].code === 'apiship.point_boxberry_3_3_1', JSON.stringify(list.map((t) => t.code)));
check('cost is a number even when the server sent a string', list[1].cost === 130 && list[0].tariff === 'Стандарт');
check('source dictionary is not mutated', tariffs.bb_1_1.code === undefined && tariffs.cdek_2_1.cost === '130');
check('point without tariffs or without dictionary gives an empty list', ApishipMap.pointTariffs({id: 1}, tariffs).length === 0 && ApishipMap.pointTariffs({id: 1, tariffs: ['bb_1_1']}, null).length === 0);
check('inherited object keys are not tariffs', ApishipMap.pointTariffs({id: 1, tariffs: ['toString']}, tariffs).length === 0);
check('escapeHtml', ApishipMap.escapeHtml('<b>"x"</b>') === '&lt;b&gt;&quot;x&quot;&lt;/b&gt;');

check('format substitutes every placeholder', ApishipMap.format('{n} из {total}', {n: 3, total: 9}) === '3 из 9');
check('plural picks the form by the number', ApishipMap.plural(1, texts.map_points) === 'пункт' && ApishipMap.plural(3, texts.map_points) === 'пункта' && ApishipMap.plural(12, texts.map_points) === 'пунктов' && ApishipMap.plural(21, texts.map_points) === 'пункт');
check('plural works with a single form (languages without declension)', ApishipMap.plural(5, 'points') === 'points');
check('a language without declension keeps the plural for counts ending in one', ApishipMap.plural(21, ['point', 'points', 'points']) === 'points' && ApishipMap.plural(1, ['point', 'points', 'points']) === 'point' && ApishipMap.plural(101, ['day', 'days', 'days']) === 'days');

check('days of a tariff as a range', ApishipMap.tariffDaysText(tariffs.bb_1_1, texts) === '2–3 дня', ApishipMap.tariffDaysText(tariffs.bb_1_1, texts));
check('days of a tariff as a single number', ApishipMap.tariffDaysText(tariffs.bb_3_1, texts) === '1 день', ApishipMap.tariffDaysText(tariffs.bb_3_1, texts));
check('tariff without days gives an empty string', ApishipMap.tariffDaysText({}, texts) === '' && ApishipMap.tariffDays({}) === null);

const more = ApishipMap.moreWays(list, texts, 0);

check('more ways: carriers of the hidden tariffs only, not of the one already shown', more.title === 'Ещё 2 способа доставки' && more.providers === 'СДЭК, Boxberry', JSON.stringify(more));
check('more ways: the hint shows the fastest hidden tariff', more.hint === 'Быстрее — 1 день за 260 ₽', more.hint);
check('more ways: nothing to expand for a single tariff', ApishipMap.moreWays([list[0]], texts, 0) === null);
check('more ways: no hint when the shown tariff is also the fastest', ApishipMap.moreWays(ApishipMap.pointTariffs(points[0], {bb_1_1: tariffs.bb_1_1, cdek_2_1: tariffs.cdek_2_1}), texts, 0).hint === '');

// Покупатель выбрал не самый дешёвый тариф: скрыты все остальные, подсказка считается от выбранного
const more_selected = ApishipMap.moreWays(list, texts, 2);

check('more ways: choosing another tariff hides the rest, including the cheapest', more_selected.title === 'Ещё 2 способа доставки' && more_selected.providers === 'Boxberry, СДЭК', JSON.stringify(more_selected));
check('more ways: no hint when the chosen tariff is the fastest one', more_selected.hint === '', more_selected.hint);

const providers = ApishipMap.providerCounts(points, tariffs);

check('carrier filter: points counted per carrier, sorted by name', JSON.stringify(providers) === JSON.stringify([
	{key: 'boxberry', name: 'Boxberry', count: 2},
	{key: 'cdek', name: 'СДЭК', count: 2}
]), JSON.stringify(providers));

const types = ApishipMap.typeCounts(points);

check('point type filter: types counted and marked as logo-less', types.length === 2 && types[0].key === 'Постамат' && types[1].count === 2 && types[0].logo === false, JSON.stringify(types));

const all = ApishipMap.filterPoints(points, tariffs, [], []);

check('empty filter keeps every point with all its tariffs', all.length === 3 && all[0].tariffs.length === 3);

const only_cdek = ApishipMap.filterPoints(points, tariffs, ['cdek'], []);

check('carrier filter drops other carriers and the points left without tariffs', only_cdek.length === 2 && only_cdek[0].tariffs.length === 1 && only_cdek[0].tariffs[0].provider_key === 'cdek', JSON.stringify(only_cdek.map((item) => item.point.id)));

const only_lockers = ApishipMap.filterPoints(points, tariffs, [], ['Постамат']);

check('point type filter keeps only the chosen type', only_lockers.length === 1 && only_lockers[0].point.id === 5);

const price = ApishipMap.groupPrice(all);

check('pin price is the cheapest tariff of the group, «from» when prices differ', price.tariff.cost === 123 && price.range === true);
check('pin price without a range when there is a single tariff', ApishipMap.groupPrice([all[2]]).range === false);

const projected = ApishipMap.project(37.6, 55.75, 12);
const back = ApishipMap.unproject(projected.x, projected.y, 12);

check('projection round trip returns the same point', Math.abs(back.lon - 37.6) < 1e-9 && Math.abs(back.lat - 55.75) < 1e-9, JSON.stringify(back));

const clusters_far = ApishipMap.clusterItems(all, 12, 96);

check('clustering: neighbours in one cell, a point in another city on its own', clusters_far.length === 2 && clusters_far[0].items.length === 2, JSON.stringify(clusters_far.map((c) => c.items.length)));

const same_place = ApishipMap.spreadCoordinates(37.6, 55.75, 21, 0, 2, 46);
const same_place_second = ApishipMap.spreadCoordinates(37.6, 55.75, 21, 1, 2, 46);

check('points that zoom can no longer separate are spread apart', same_place.lon !== same_place_second.lon && Math.abs(same_place.lon - 37.6) < 0.001 && Math.abs(same_place.lat - 55.75) < 0.001, JSON.stringify([same_place, same_place_second]));
check('a single point is not moved', ApishipMap.spreadCoordinates(37.6, 55.75, 21, 0, 1, 46).lon === 37.6);

const clusters_close = ApishipMap.clusterItems(all, 19, 96);

check('clustering: at a close zoom the neighbours split apart', clusters_close.length === 3, JSON.stringify(clusters_close.map((c) => c.items.length)));
check('cluster centre is inside its points', clusters_far[0].lat > 55.749 && clusters_far[0].lat < 55.751, String(clusters_far[0].lat));

for (const copy of COPIES) {
	check('package script is identical to ' + MAIN + ': ' + copy, fs.readFileSync(path.join(root, copy), 'utf8') === source);
}

console.log('\n' + (failures ? 'FAILED: ' + failures + ', passed: ' + passed : 'All ' + passed + ' tests passed'));

process.exit(failures ? 1 : 0);
