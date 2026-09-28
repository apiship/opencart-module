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

// Разведение считается от общего центра, поэтому расстояние между пинами не зависит от того,
// насколько близко стояли сами точки — иначе близкие пины можно сдвинуть друг к другу
const spread_first = ApishipMap.spreadCoordinates(37.6, 55.75, 21, 0, 2, 46);
const spread_second = ApishipMap.spreadCoordinates(37.6, 55.75, 21, 1, 2, 46);

check('points that zoom can no longer separate are spread apart', spread_first.lon !== spread_second.lon && Math.abs(spread_first.lon - 37.6) < 0.001 && Math.abs(spread_first.lat - 55.75) < 0.001, JSON.stringify([spread_first, spread_second]));
// Расстояние между соседними пинами круга в пикселях экрана
function spreadGap(total) {
	const first = ApishipMap.spreadCoordinates(37.6, 55.75, 21, 0, total, 46);
	const second = ApishipMap.spreadCoordinates(37.6, 55.75, 21, 1, total, 46);

	const a = ApishipMap.project(first.lon, first.lat, 21);
	const b = ApishipMap.project(second.lon, second.lat, 21);

	return Math.sqrt(Math.pow(a.x - b.x, 2) + Math.pow(a.y - b.y, 2));
}

check('the circle grows with the number of pins, so eight pins do not overlap', spreadGap(8) >= 60, String(Math.round(spreadGap(8))));
check('two spread pins stand at least a pin width apart', spreadGap(2) >= 80, String(Math.round(spreadGap(2))));
check('a single point is not moved', ApishipMap.spreadCoordinates(37.6, 55.75, 21, 0, 1, 46).lon === 37.6);

check('marker key changes when the filtered tariff set changes', ApishipMap.tariffsKey(all[0].tariffs) !== ApishipMap.tariffsKey(only_cdek[0].tariffs) && ApishipMap.tariffsKey([]) === '', ApishipMap.tariffsKey(only_cdek[0].tariffs));

const clusters_close = ApishipMap.clusterItems(all, 19, 96);

check('clustering: at a close zoom the neighbours split apart', clusters_close.length === 3, JSON.stringify(clusters_close.map((c) => c.items.length)));
check('cluster centre is inside its points', clusters_far[0].lat > 55.749 && clusters_far[0].lat < 55.751, String(clusters_far[0].lat));

// Точки у границы ячейки грида на предельном зуме: сетка разносит их по разным кластерам, хотя пины перекрываются.
// Угол ячейки берётся рядом с настоящей точкой, координаты соседей считаются от него в пикселях экрана
const corner = ApishipMap.project(37.6, 55.75, 21);

corner.x = Math.ceil(corner.x / 96) * 96;
corner.y = Math.ceil(corner.y / 96) * 96;

function itemAt(id, dx, dy) {
	const place = ApishipMap.unproject(corner.x + dx, corner.y + dy, 21);

	return {point: {id: id, lon: place.lon, lat: place.lat}, tariffs: []};
}

function ids(cluster) {
	return cluster.items.map((item) => item.point.id).join(',');
}

const across = [itemAt(1, -2, 10), itemAt(2, 2, 10)];
const across_grid = ApishipMap.clusterItems(across, 21, 96);
const across_merged = ApishipMap.mergeClose(across_grid, 21, 96);

check('grid alone leaves points on both sides of a cell border as two single pins', across_grid.length === 2);
check('close pins across a cell border merge into one group', across_merged.length === 1 && ids(across_merged[0]) === '1,2', JSON.stringify(across_merged.map(ids)));

const across_center = ApishipMap.project(across_merged[0].lon, across_merged[0].lat, 21);

check('merged group is centred between its points', Math.abs(across_center.x - corner.x) < 0.01 && Math.abs(across_center.y - (corner.y + 10)) < 0.01, JSON.stringify(across_center));

const diagonal = ApishipMap.mergeClose(ApishipMap.clusterItems([itemAt(1, -2, -2), itemAt(2, 2, 2)], 21, 96), 21, 96);

check('close pins across a cell corner merge too', diagonal.length === 1 && diagonal[0].items.length === 2);

const vertical = ApishipMap.mergeClose(ApishipMap.clusterItems([itemAt(1, 10, -2), itemAt(2, 10, 2)], 21, 96), 21, 96);

check('close pins across a horizontal cell border merge too', vertical.length === 1 && vertical[0].items.length === 2);

// Цепочка: крайние точки дальше ширины пина друг от друга, но каждая перекрывается со средней. Первые две стоят
// в одной ячейке, и центр их кластера от третьей дальше ширины пина — сравниваться должны точки, а не центры
const chain = ApishipMap.mergeClose(ApishipMap.clusterItems([itemAt(1, -70, 10), itemAt(2, -5, 10), itemAt(3, 60, 10)], 21, 96), 21, 96);

check('a chain of overlapping pins becomes one group', chain.length === 1 && chain[0].items.length === 3, JSON.stringify(chain.map(ids)));

const apart_grid = ApishipMap.clusterItems([itemAt(1, -50, 10), itemAt(2, 50, 10), itemAt(3, 400, 300)], 21, 96);
const apart = ApishipMap.mergeClose(apart_grid, 21, 96);

check('pins a pin width apart or farther stay on their own, untouched', apart.length === 3 && apart[0] === apart_grid[0] && apart[2] === apart_grid[2], JSON.stringify(apart.map(ids)));

// Кластер из двух точек одной ячейки и одиночная точка через границу: центр группы — среднее по точкам, а не по кластерам
const weighted_grid = ApishipMap.clusterItems([itemAt(1, 30, 10), itemAt(2, 30, 10), itemAt(3, -30, 10)], 21, 96);
const weighted = ApishipMap.mergeClose(weighted_grid, 21, 96);
const weighted_center = ApishipMap.project(weighted[0].lon, weighted[0].lat, 21);

check('merging keeps every point and weighs the centre by points', weighted_grid.length === 2 && weighted.length === 1 && ids(weighted[0]) === '1,2,3' && Math.abs(weighted_center.x - (corner.x + 10)) < 0.01, JSON.stringify(weighted_center));
check('merged group has its own key, different from the keys of its parts', weighted[0].key !== weighted_grid[0].key && weighted[0].key !== weighted_grid[1].key);
check('nothing to merge in an empty list', ApishipMap.mergeClose([], 21, 96).length === 0);

for (const copy of COPIES) {
	check('package script is identical to ' + MAIN + ': ' + copy, fs.readFileSync(path.join(root, copy), 'utf8') === source);
}

console.log('\n' + (failures ? 'FAILED: ' + failures + ', passed: ' + passed : 'All ' + passed + ' tests passed'));

process.exit(failures ? 1 : 0);
