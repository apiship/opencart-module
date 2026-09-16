/**
 * ApiShip: карта пунктов выдачи (Яндекс JS API 3.0) в модальном окне.
 * Использование: new ApishipMap(config).open(data, callback, code), data — ответ get_points ({points, tariffs}):
 * каждая точка один раз, тарифы (цена, название, срок, код варианта по шаблону) отдельным словарём, точка ссылается на них ключами.
 * config.texts — подписи, config.yandex_api_key — ключ Яндекс API (нужен доступ к JavaScript API 3.0).
 * Без config берётся глобальный apiship_config, а ключ — из глобальной get_yandex_api_key() (так работают моды шаблонов).
 * Файл одинаков во всех пакетах модуля (2.1, 2.3, 3.x, 4.x), это проверяет dev/oc4/tests/map.js.
 */
class ApishipMap {
	constructor(config) {
		this.ID_MODAL = 'apiship_yandex_map';
		this.YANDEX_MAP_CONTAINER_ID = 'apiship_yandex_map_container';

		// Сторона грида кластеризации в пикселях экрана: примерно ширина пина с ценой
		this.CLUSTER_GRID = 96;
		this.PROVIDER_ICONS = 'https://storage.apiship.ru/icons/providers/svg/';

		this.config = config || (typeof apiship_config !== 'undefined' ? apiship_config : {});
		this.texts = Object.assign({
			from: 'от',
			map_title: 'Пункты самовывоза',
			map_take_here: 'Забрать отсюда',
			map_type: 'Тип точки',
			map_provider: 'СД',
			map_no_points: 'Пункты выдачи не найдены',
			map_load: 'Не удалось загрузить карту. Обновите страницу',
			map_badge_card: 'Картой',
			map_badge_cash: 'Наличными',
			map_fitting_room: 'Примерочная',
			map_how_to_get: 'Как пройти',
			map_delivery_here: 'Доставка в этот пункт',
			map_cheapest: 'дешевле всех',
			map_faster: 'Быстрее — {days} за {price}',
			map_more_ways: 'Ещё {n} {ways}',
			map_ways: ['способ доставки', 'способа доставки', 'способов доставки'],
			map_days: ['день', 'дня', 'дней'],
			map_points: ['пункт', 'пункта', 'пунктов'],
			map_providers_all: 'Все службы',
			map_providers_title: 'Службы доставки',
			map_types_all: 'Все типы',
			map_filter_reset: 'Сбросить',
			map_filter_apply: 'Показать {n} {points}',
			map_filter_counter: '{n} {points} из {total}',
			map_search: 'Город, улица',
			map_search_empty: 'Ничего не найдено',
			map_zoom_in: 'Приблизить',
			map_zoom_out: 'Отдалить',
			map_geolocation: 'Моё местоположение',
			map_close: 'Закрыть'
		}, this.config.texts || {});

		this.callback_function = null;
		this.callback_code = null;

		this.points = [];
		this.tariffs = {};

		// Выбранные службы и типы точек: пустой набор — фильтр не применён, показываем всё
		this.provider_filter = [];
		this.type_filter = [];

		this.map = null;
		this.markers = {};
		this.ui = {};
		this.card = null;
		this.selected_point = null;
		this.selected_tariffs = [];
		this.selected_index = 0;
		this.selected_code = null;
		this.expanded = false;
		this.location = null;

		this.loadFailed = false;
		this.checkYmaps = null;

		this.modal = {
			initLayout: {
				template: () => `
					<div class="modal fade apiship_modal" id="${this.ID_MODAL}" tabindex="-1" role="dialog">
						<div class="modal-dialog apiship_modal-dialog" role="document">
							<div class="modal-content apiship_modal-content">
								<div class="modal-header apiship_modal-header">
									<h4>${ApishipMap.escapeHtml(this.texts.map_title)}</h4>
									<button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="${ApishipMap.escapeHtml(this.texts.map_close)}">
										<span class="apiship_modal-close-x" aria-hidden="true">&times;</span>
									</button>
								</div>
								<div class="modal-body apiship_modal-body"></div>
							</div>
						</div>
					</div>
				`,
				create: () => {
					if (document.getElementById(this.ID_MODAL)) return;

					const wrapper = document.createElement('div');
					wrapper.innerHTML = this.modal.initLayout.template().trim();

					document.body.appendChild(wrapper.firstChild);
				}
			},

			createModalBootstrap: () => {
				this.modal.initLayout.create();
			},

			open: () => {
				$('#' + this.ID_MODAL).modal('show');
			},

			close: () => {
				$('#' + this.ID_MODAL).modal('hide');
			}
		};
	}

	/* ------------------------------------------------------------------ *
	 * Чистые хелперы: считают данные карты без DOM и Яндекс.Карт,
	 * поэтому проверяются юнит-тестами (dev/oc4/tests/map.js)
	 * ------------------------------------------------------------------ */

	// Экранирование строки для вставки в html (данные точек приходят из API)
	static escapeHtml(value) {
		return String(value == null ? '' : value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#39;');
	}

	// Подстановка {name} в строку подписи: значения подставляются как есть, экранирует вызывающий код
	static format(template, values) {
		let result = String(template == null ? '' : template);

		for (const key of Object.keys(values || {})) {
			result = result.split('{' + key + '}').join(String(values[key]));
		}

		return result;
	}

	// Форма слова для числа: [1 пункт, 2 пункта, 5 пунктов]; в языках без склонения все три формы совпадают
	static plural(count, forms) {
		const list = Array.isArray(forms) ? forms : [forms, forms, forms];
		const n = Math.abs(Math.floor(Number(count) || 0)) % 100;
		const n1 = n % 10;

		if (n > 10 && n < 20) return list[2];
		if (n1 > 1 && n1 < 5) return list[1];
		if (n1 === 1) return list[0];

		return list[2];
	}

	// Тарифы точки из словаря ответа сервера: с кодом варианта для этой точки, по возрастанию цены.
	// Код собирается по code_template тарифа — подстановка id точки вместо {point_id} (Apiship::POINT_ID_PLACEHOLDER)
	static pointTariffs(point, tariffs) {
		const keys = (point && Array.isArray(point.tariffs)) ? point.tariffs : [];
		const result = [];

		for (const key of keys) {
			const tariff = (tariffs && Object.prototype.hasOwnProperty.call(tariffs, key)) ? tariffs[key] : null;

			if (!tariff || typeof tariff.code_template !== 'string') continue;

			result.push(Object.assign({}, tariff, {
				code: tariff.code_template.split('{point_id}').join(String(point.id)),
				cost: parseFloat(tariff.cost)
			}));
		}

		result.sort((a, b) => a.cost - b.cost);

		return result;
	}

	// Срок тарифа в днях числом: больший из daysMin и daysMax, null — если срока нет
	static tariffDays(tariff) {
		const min = parseInt(tariff && tariff.days_min, 10);
		const max = parseInt(tariff && tariff.days_max, 10);
		const days = [];

		if (!isNaN(min) && min > 0) days.push(min);
		if (!isNaN(max) && max > 0) days.push(max);

		return days.length ? Math.max.apply(null, days) : null;
	}

	// Срок тарифа подписью: «2–3 дня», «1 день», пустая строка — если срока нет
	static tariffDaysText(tariff, texts) {
		const min = parseInt(tariff && tariff.days_min, 10);
		const max = parseInt(tariff && tariff.days_max, 10);
		const forms = (texts && texts.map_days) || ['день', 'дня', 'дней'];

		const has_min = !isNaN(min) && min > 0;
		const has_max = !isNaN(max) && max > 0;

		if (has_min && has_max && min !== max) {
			return min + '–' + max + ' ' + ApishipMap.plural(max, forms);
		}

		const days = has_min ? min : (has_max ? max : null);

		return days === null ? '' : days + ' ' + ApishipMap.plural(days, forms);
	}

	// Службы точки по её тарифам, в порядке возрастания цены тарифа
	static pointProviders(point_tariffs) {
		const providers = [];

		for (const tariff of point_tariffs) {
			if (!providers.some((provider) => provider.key === tariff.provider_key)) {
				providers.push({key: tariff.provider_key, name: tariff.provider});
			}
		}

		return providers;
	}

	// Строка раскрытия «Ещё N способов доставки» и подсказка о самом быстром из скрытых.
	// point_tariffs отсортированы по цене, первый уже показан отдельно; null — скрывать нечего
	static moreWays(point_tariffs, texts) {
		const rest = point_tariffs.slice(1);

		if (!rest.length) return null;

		const title = ApishipMap.format(texts.map_more_ways, {
			n: rest.length,
			ways: ApishipMap.plural(rest.length, texts.map_ways)
		});

		const providers = ApishipMap.pointProviders(point_tariffs).map((provider) => provider.name).join(', ');

		// Подсказка нужна, только когда среди скрытых есть заметно более быстрый тариф
		const cheapest_days = ApishipMap.tariffDays(point_tariffs[0]);
		let fastest = null;

		for (const tariff of rest) {
			const days = ApishipMap.tariffDays(tariff);

			if (days === null) continue;
			if (cheapest_days !== null && days >= cheapest_days) continue;
			if (fastest === null || days < ApishipMap.tariffDays(fastest)) fastest = tariff;
		}

		const hint = fastest ? ApishipMap.format(texts.map_faster, {
			days: ApishipMap.tariffDaysText(fastest, texts),
			price: fastest.text
		}) : '';

		return {title: title, providers: providers, hint: hint};
	}

	// Службы всех точек со счётчиком точек, по алфавиту: строки фильтра служб
	static providerCounts(points, tariffs) {
		const counts = {};

		for (const point of points) {
			const point_providers = ApishipMap.pointProviders(ApishipMap.pointTariffs(point, tariffs));

			for (const provider of point_providers) {
				if (!counts[provider.key]) counts[provider.key] = {key: provider.key, name: provider.name, count: 0};

				counts[provider.key].count++;
			}
		}

		return Object.keys(counts)
			.map((key) => counts[key])
			.sort((a, b) => String(a.name).localeCompare(String(b.name)));
	}

	// Типы точек со счётчиком точек, по алфавиту: строки фильтра типов
	static typeCounts(points) {
		const counts = {};

		for (const point of points) {
			const type = String(point.type == null ? '' : point.type);

			if (!counts[type]) counts[type] = {key: type, name: type, count: 0, logo: false};

			counts[type].count++;
		}

		return Object.keys(counts)
			.map((key) => counts[key])
			.sort((a, b) => String(a.name).localeCompare(String(b.name)));
	}

	// Точки, подходящие под выбранные службы и типы: пустой фильтр — ограничения нет.
	// У каждой точки остаются только тарифы выбранных служб — по ним считается цена на пине
	static filterPoints(points, tariffs, provider_filter, type_filter) {
		const providers = Array.isArray(provider_filter) ? provider_filter : [];
		const types = Array.isArray(type_filter) ? type_filter : [];
		const result = [];

		for (const point of points) {
			if (types.length && types.indexOf(String(point.type == null ? '' : point.type)) === -1) continue;

			const point_tariffs = ApishipMap.pointTariffs(point, tariffs)
				.filter((tariff) => !providers.length || providers.indexOf(tariff.provider_key) !== -1);

			if (!point_tariffs.length) continue;

			result.push({point: point, tariffs: point_tariffs});
		}

		return result;
	}

	// Точка в пиксели сферического Меркатора для зума: кластеризация группирует точки по экранной сетке
	static project(lon, lat, zoom) {
		const size = 256 * Math.pow(2, zoom);
		const limited = Math.max(-85.05112878, Math.min(85.05112878, Number(lat)));
		const sin = Math.sin(limited * Math.PI / 180);

		return {
			x: (Number(lon) + 180) / 360 * size,
			y: (0.5 - Math.log((1 + sin) / (1 - sin)) / (4 * Math.PI)) * size
		};
	}

	// Обратное преобразование project()
	static unproject(x, y, zoom) {
		const size = 256 * Math.pow(2, zoom);
		const n = Math.PI - 2 * Math.PI * y / size;

		return {
			lon: x / size * 360 - 180,
			lat: 180 / Math.PI * Math.atan(0.5 * (Math.exp(n) - Math.exp(-n)))
		};
	}

	// Группировка точек по экранной сетке: список кластеров, у каждого — свои точки и координаты центра.
	// Кластер из одной точки рисуется обычным пином, поэтому отдельного случая для него нет
	static clusterItems(items, zoom, grid) {
		const size = Math.max(1, Number(grid) || 1);
		const buckets = {};
		const order = [];

		for (const item of items) {
			const pixels = ApishipMap.project(item.point.lon, item.point.lat, zoom);
			const key = Math.floor(pixels.x / size) + ':' + Math.floor(pixels.y / size);

			if (!buckets[key]) {
				buckets[key] = {key: key, items: [], x: 0, y: 0};
				order.push(key);
			}

			buckets[key].items.push(item);
			buckets[key].x += pixels.x;
			buckets[key].y += pixels.y;
		}

		return order.map((key) => {
			const bucket = buckets[key];
			const center = ApishipMap.unproject(bucket.x / bucket.items.length, bucket.y / bucket.items.length, zoom);

			return {key: key, items: bucket.items, lon: center.lon, lat: center.lat};
		});
	}

	// Самый дешёвый тариф группы точек и признак разброса цен: подпись пина «от N ₽» или «N ₽»
	static groupPrice(items) {
		let cheapest = null;
		let range = false;

		for (const item of items) {
			for (const tariff of item.tariffs) {
				if (cheapest === null) {
					cheapest = tariff;
				} else if (tariff.cost < cheapest.cost) {
					cheapest = tariff;
					range = true;
				} else if (tariff.cost !== cheapest.cost) {
					range = true;
				}
			}
		}

		return cheapest === null ? null : {tariff: cheapest, range: range};
	}

	// Иконки карточки и панелей: svg внутри скрипта, чтобы карта не зависела от картинок темы магазина
	static icon(name) {
		const paths = {
			box: '<path d="M12 2 3 6.5v11L12 22l9-4.5v-11L12 2Zm0 2.2 6.6 3.3L12 10.8 5.4 7.5 12 4.2ZM5 9.2l6 3v7.3l-6-3V9.2Zm8 10.3v-7.3l6-3v7.3l-6 3Z"/>',
			clock: '<path d="M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Zm0 2a7 7 0 1 1 0 14 7 7 0 0 1 0-14Zm-1 2v5.4l4 2.4.9-1.5-3.3-2V7h-1.6Z"/>',
			card: '<path d="M3 6.5A1.5 1.5 0 0 1 4.5 5h15A1.5 1.5 0 0 1 21 6.5v11a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 17.5v-11ZM5 9v8h14V9H5Zm0-2h14V7H5Zm2 7h5v2H7v-2Z"/>',
			cash: '<path d="M3 6h18v12H3V6Zm2 2v8h14V8H5Zm7 1.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5Z"/>',
			shirt: '<path d="M9 3 4 5.5 5.5 10 8 9.2V21h8V9.2l2.5.8L20 5.5 15 3a3 3 0 0 1-6 0Z"/>',
			route: '<path d="M6 3a3 3 0 0 0-1 5.8V11h6a2 2 0 0 1 0 4H9.8A3 3 0 1 0 8 18.9V17h4a4 4 0 0 0 0-8H7V8.8A3 3 0 0 0 6 3Z"/>',
			search: '<path d="M10.5 3a7.5 7.5 0 1 0 4.6 13.4l4.2 4.3 1.5-1.5-4.3-4.2A7.5 7.5 0 0 0 10.5 3Zm0 2a5.5 5.5 0 1 1 0 11 5.5 5.5 0 0 1 0-11Z"/>',
			truck: '<path d="M3 6h11v9H3V6Zm12 3h3.5L21 12v3h-6V9ZM7 16.5a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm10 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/>',
			filter: '<path d="M3 5h18l-7 8v6l-4 2v-8L3 5Z"/>',
			plus: '<path d="M11 5h2v6h6v2h-6v6h-2v-6H5v-2h6V5Z"/>',
			minus: '<path d="M5 11h14v2H5v-2Z"/>',
			target: '<path d="M11 2h2v3.1a7 7 0 0 1 5.9 5.9H22v2h-3.1a7 7 0 0 1-5.9 5.9V22h-2v-3.1A7 7 0 0 1 5.1 13H2v-2h3.1A7 7 0 0 1 11 5.1V2Zm1 5a5 5 0 1 0 0 10 5 5 0 0 0 0-10Zm0 3a2 2 0 1 1 0 4 2 2 0 0 1 0-4Z"/>',
			chevron: '<path d="M7.4 9.6 12 14.2l4.6-4.6L18 11l-6 6-6-6 1.4-1.4Z"/>',
			close: '<path d="m6.4 5 5.6 5.6L17.6 5 19 6.4 13.4 12 19 17.6 17.6 19 12 13.4 6.4 19 5 17.6 10.6 12 5 6.4 6.4 5Z"/>',
			check: '<path d="M9.6 16.2 5.4 12l-1.4 1.4 5.6 5.6L20.4 8.2 19 6.8l-9.4 9.4Z"/>'
		};

		return '<svg class="apiship_icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' + (paths[name] || '') + '</svg>';
	}

	// Логотип службы доставки из справочника ApiShip
	providerLogo(provider_key, provider_name) {
		return '<img class="apiship_logo" alt="' + ApishipMap.escapeHtml(provider_name) + '" src="' +
			ApishipMap.escapeHtml(this.PROVIDER_ICONS + encodeURIComponent(String(provider_key)) + '.svg') + '">';
	}

	/* ------------------------------------------------------------------ *
	 * Загрузка API и жизненный цикл модалки
	 * ------------------------------------------------------------------ */

	// Ключ Яндекс API из конфига; глобальная get_yandex_api_key() — для сторонних скриптов, определяющих её сами
	getApiKey() {
		if (typeof this.config.yandex_api_key !== 'undefined') {
			return String(this.config.yandex_api_key);
		}

		return (typeof get_yandex_api_key === 'function') ? String(get_yandex_api_key()) : '';
	}

	// JS API 3.0 без ключа не работает: при пустом ключе скрипт всё равно грузится, карта покажет ошибку загрузки
	initApi() {
		if (typeof ymaps3 !== 'undefined') return;
		if (document.querySelector('script[data-apiship-ymaps]')) return;

		const key = this.getApiKey();

		const script = document.createElement('script');
		script.setAttribute('src', 'https://api-maps.yandex.ru/v3/?lang=ru_RU&apikey=' + encodeURIComponent(key));
		script.setAttribute('defer', '');
		script.setAttribute('data-apiship-ymaps', '1');
		script.onerror = () => {
			this.loadFailed = true;
			script.remove();
		};

		document.head.appendChild(script);
	}

	init() {
		this.modal.createModalBootstrap();
		this.initApi();
		$('#' + this.ID_MODAL).on('hide.bs.modal', () => this.onCloseModal());
		// Bootstrap 5 не поддерживает вложенные модалки: возвращаем состояние страницы, если осталась открытая модалка
		$('#' + this.ID_MODAL).on('hidden.bs.modal', () => {
			if ($('.modal.show').length) {
				$('body').addClass('modal-open');
			}
		});
		return this;
	}

	open(data, callback, code) {
		const points = (data && Array.isArray(data.points)) ? data.points : [];
		const tariffs = (data && data.tariffs && typeof data.tariffs === 'object') ? data.tariffs : {};

		if (points.length === 0) {
			alert(this.texts.map_no_points);
			return;
		}

		// Удаляем предыдущую модалку если есть
		const existingModal = document.getElementById(this.ID_MODAL);
		if (existingModal) {
			existingModal.remove();
		}

		this.init();

		this.callback_function = callback;
		this.callback_code = code;

		this.points = points;
		this.tariffs = tariffs;
		this.provider_filter = [];
		this.type_filter = [];

		this.createContainer();

		// Ожидаем загрузку Яндекс.Карт: не дольше 15 секунд, иначе закрываем модалку с сообщением
		if (typeof ymaps3 !== 'undefined') {
			this.initMap();
		} else {
			let attempts = 0;

			this.checkYmaps = setInterval(() => {
				attempts++;

				if (typeof ymaps3 !== 'undefined') {
					this.stopWaiting();
					this.initMap();
				} else if (this.loadFailed || attempts >= 150) {
					this.stopWaiting();
					this.modal.close();
					alert(this.texts.map_load);
				}
			}, 100);
		}

		this.modal.open();
	}

	createContainer() {
		const container = document.createElement('div');
		const modalBody = document.getElementById(this.ID_MODAL).querySelector('.modal-body');

		container.setAttribute('id', this.YANDEX_MAP_CONTAINER_ID);
		modalBody.appendChild(container);
	}

	onCloseModal() {
		this.stopWaiting();
		this.closeCard();

		if (this.map) {
			this.map.destroy();
			this.map = null;
		}

		this.markers = {};

		const el = document.getElementById(this.YANDEX_MAP_CONTAINER_ID);
		if (el !== null) el.remove();
	}

	stopWaiting() {
		if (this.checkYmaps !== null) {
			clearInterval(this.checkYmaps);
			this.checkYmaps = null;
		}
	}

	/* ------------------------------------------------------------------ *
	 * Карта
	 * ------------------------------------------------------------------ */

	initMap() {
		const container = document.getElementById(this.YANDEX_MAP_CONTAINER_ID);

		if (!container) return;

		ymaps3.ready.then(() => {
			// Модалку успели закрыть, пока грузился API
			if (!document.getElementById(this.YANDEX_MAP_CONTAINER_ID)) return;

			const first = this.points[0];

			this.location = {center: [Number(first.lon), Number(first.lat)], zoom: 12, bounds: null};

			this.map = new ymaps3.YMap(container, {
				location: {center: this.location.center, zoom: this.location.zoom},
				behaviors: ['drag', 'scrollZoom', 'pinchZoom', 'dblClick']
			}, [
				new ymaps3.YMapDefaultSchemeLayer({}),
				new ymaps3.YMapDefaultFeaturesLayer({})
			]);

			this.map.addChild(new ymaps3.YMapListener({
				layer: 'any',
				onUpdate: (event) => this.onMapUpdate(event)
			}));

			// Ключ поиска — тот же, что у карты: подключение Search API к ключу проверяется первым запросом
			try {
				ymaps3.getDefaultConfig().setApikeys({search: this.getApiKey()});
			} catch (e) {
				// Поиск по адресу недоступен — строка поиска ищет по адресам точек
			}

			this.buildControls(container);
			this.renderMarkers();
		}).catch(() => {
			this.modal.close();
			alert(this.texts.map_load);
		});
	}

	onMapUpdate(event) {
		const location = event && event.location;

		if (!location) return;

		const zoom_changed = !this.location || this.location.zoom !== location.zoom;

		this.location = {
			center: location.center || (this.location && this.location.center),
			zoom: location.zoom,
			bounds: location.bounds || null
		};

		// Во время жеста метки едут вместе с картой; пересобираем их, когда карта остановилась
		if (!event.mapInAction || zoom_changed) {
			this.renderMarkers();
		}
	}

	// Точки в видимой области с запасом: bounds приходят от API, без них показываем всё
	inBounds(items) {
		const bounds = this.location && this.location.bounds;

		if (!bounds || !bounds[0] || !bounds[1]) return items;

		const west = Math.min(bounds[0][0], bounds[1][0]);
		const east = Math.max(bounds[0][0], bounds[1][0]);
		const south = Math.min(bounds[0][1], bounds[1][1]);
		const north = Math.max(bounds[0][1], bounds[1][1]);

		const pad_lon = (east - west) * 0.3;
		const pad_lat = (north - south) * 0.3;

		return items.filter((item) => {
			const lon = Number(item.point.lon);
			const lat = Number(item.point.lat);

			return lon >= west - pad_lon && lon <= east + pad_lon && lat >= south - pad_lat && lat <= north + pad_lat;
		});
	}

	renderMarkers() {
		if (!this.map) return;

		const zoom = this.location ? this.location.zoom : 12;
		const items = ApishipMap.filterPoints(this.points, this.tariffs, this.provider_filter, this.type_filter);

		this.updateCounter(items.length);

		const clusters = ApishipMap.clusterItems(this.inBounds(items), zoom, this.CLUSTER_GRID);
		const wanted = {};

		for (const cluster of clusters) {
			const price = ApishipMap.groupPrice(cluster.items);

			if (!price) continue;

			const single = cluster.items.length === 1 ? cluster.items[0] : null;
			const active = single && this.selected_point && single.point.id === this.selected_point.id;

			const logo = this.provider_filter.length ? ':' + price.tariff.provider_key : '';

			const key = single
				? 'point:' + single.point.id + ':' + price.tariff.cost + logo + (active ? ':active' : '')
				: 'cluster:' + zoom + ':' + cluster.key + ':' + cluster.items.length + ':' + price.tariff.cost;

			wanted[key] = true;

			if (this.markers[key]) continue;

			const element = this.buildPin(cluster, price, single, active);

			const marker = new ymaps3.YMapMarker({
				coordinates: single ? [Number(single.point.lon), Number(single.point.lat)] : [cluster.lon, cluster.lat],
				zIndex: active ? 900 : 700
			}, element);

			this.markers[key] = marker;
			this.map.addChild(marker);
		}

		for (const key of Object.keys(this.markers)) {
			if (wanted[key]) continue;

			this.map.removeChild(this.markers[key]);

			delete this.markers[key];
		}
	}

	// Пин: иконка, цена «от N ₽» и логотип службы, когда фильтр служб включён. Кликается вся площадь (OCM-76)
	buildPin(cluster, price, single, active) {
		const element = document.createElement('div');
		const prefix = price.range ? ApishipMap.escapeHtml(this.texts.from) + ' ' : '';

		element.className = 'apiship_pin' + (active ? ' apiship_pin-active' : '') + (single ? '' : ' apiship_pin-cluster');

		const logo = (single && this.provider_filter.length)
			? this.providerLogo(price.tariff.provider_key, price.tariff.provider)
			: ApishipMap.icon('box');

		element.innerHTML = logo +
			'<span class="apiship_pin_price">' + prefix + ApishipMap.escapeHtml(price.tariff.text) + '</span>' +
			(single ? '' : '<span class="apiship_pin_count">' + cluster.items.length + '</span>') +
			'<span class="apiship_pin_tail"></span>';

		element.addEventListener('click', (event) => {
			event.preventDefault();
			event.stopPropagation();

			if (single) {
				this.openCard(single);
			} else {
				this.map.setLocation({center: [cluster.lon, cluster.lat], zoom: Math.min(21, (this.location.zoom || 12) + 2), duration: 250});
			}
		});

		return element;
	}

	/* ------------------------------------------------------------------ *
	 * Карточка пункта выдачи
	 * ------------------------------------------------------------------ */

	openCard(item) {
		this.selected_point = item.point;
		this.selected_tariffs = item.tariffs;
		this.selected_index = 0;
		this.expanded = false;

		this.showCard();
		this.moveToCard();
		this.renderMarkers();
	}

	closeCard() {
		if (this.card && this.map) {
			this.map.removeChild(this.card);
		}

		this.card = null;
		this.selected_point = null;
		this.selected_tariffs = [];
	}

	// Карточка привязана к точке отдельной меткой: при перетаскивании карты она едет вместе с пином
	showCard() {
		if (this.card && this.map) {
			this.map.removeChild(this.card);
			this.card = null;
		}

		const point = this.selected_point;

		if (!point || !this.map) return;

		const element = document.createElement('div');

		element.className = 'apiship_card' + (this.cardBelow() ? ' apiship_card-below' : '');
		element.innerHTML = this.cardHtml();

		element.addEventListener('click', (event) => this.onCardClick(event));

		this.card = new ymaps3.YMapMarker({
			coordinates: [Number(point.lon), Number(point.lat)],
			zIndex: 1000
		}, element);

		this.map.addChild(this.card);
	}

	// Точка в верхней половине экрана — места над ней нет, карточка раскрывается под пином
	cardBelow() {
		const point = this.selected_point;
		const center = this.location && this.location.center;

		if (!point || !center) return false;

		return Number(point.lat) > Number(center[1]);
	}

	// Сдвигаем карту, а не карточку: точка уходит ниже или выше центра, чтобы карточка целиком попала в окно
	moveToCard() {
		const point = this.selected_point;

		if (!point || !this.map || !this.location) return;

		const bounds = this.location.bounds;
		const span = (bounds && bounds[0] && bounds[1]) ? Math.abs(bounds[0][1] - bounds[1][1]) : 0;
		const shift = this.cardBelow() ? -span * 0.22 : span * 0.22;

		this.map.setLocation({
			center: [Number(point.lon), Number(point.lat) + shift],
			zoom: this.location.zoom,
			duration: 250
		});
	}

	cardHtml() {
		const point = this.selected_point;
		const texts = this.texts;
		const esc = ApishipMap.escapeHtml;

		const badges = [];

		if (Number(point.paymentCard) === 1) badges.push(ApishipMap.icon('card') + esc(texts.map_badge_card));
		if (Number(point.paymentCash) === 1) badges.push(ApishipMap.icon('cash') + esc(texts.map_badge_cash));
		if (Number(point.fittingRoom) === 1) badges.push(ApishipMap.icon('shirt') + esc(texts.map_fitting_room));

		let html =
			'<div class="apiship_card_head">' +
				'<div class="apiship_card_kicker">' + esc(point.type) + '</div>' +
				'<button type="button" class="apiship_card_close" data-apiship-close="1" aria-label="' + esc(texts.map_close) + '">' + ApishipMap.icon('close') + '</button>' +
				'<div class="apiship_card_title">' + esc(point.title || point.address) + '</div>' +
				(point.subtitle ? '<div class="apiship_card_subtitle">' + esc(point.subtitle) + '</div>' : '') +
				(point.timetable ? '<div class="apiship_card_line">' + ApishipMap.icon('clock') + esc(point.timetable) + '</div>' : '') +
				(badges.length ? '<div class="apiship_card_badges"><span class="apiship_badge">' + badges.join('</span><span class="apiship_badge">') + '</span></div>' : '') +
			'</div>';

		if (point.description) {
			html +=
				'<div class="apiship_card_section">' +
					'<div class="apiship_card_section_title">' + ApishipMap.icon('route') + esc(texts.map_how_to_get) + '</div>' +
					'<div class="apiship_card_note">' + esc(point.description) + '</div>' +
				'</div>';
		}

		html += '<div class="apiship_card_section">' +
			'<div class="apiship_card_section_title">' + esc(texts.map_delivery_here) + '</div>' +
			this.tariffRowHtml(this.selected_tariffs[this.selected_index], this.selected_index);

		const more = ApishipMap.moreWays(this.selected_tariffs, texts);

		if (more) {
			const logos = ApishipMap.pointProviders(this.selected_tariffs)
				.map((provider) => this.providerLogo(provider.key, provider.name)).join('');

			html +=
				'<button type="button" class="apiship_card_more' + (this.expanded ? ' apiship_card_more-open' : '') + '" data-apiship-expand="1">' +
					'<span class="apiship_card_more_logos">' + logos + '</span>' +
					'<span class="apiship_card_more_text">' +
						'<span class="apiship_card_more_title">' + esc(more.title) + (more.providers ? ' — ' + esc(more.providers) : '') + '</span>' +
						(more.hint ? '<span class="apiship_card_more_hint">' + esc(more.hint) + '</span>' : '') +
					'</span>' +
					'<span class="apiship_card_more_chevron">' + ApishipMap.icon('chevron') + '</span>' +
				'</button>';

			if (this.expanded) {
				html += '<div class="apiship_card_rest">';

				for (let index = 0; index < this.selected_tariffs.length; index++) {
					if (index === this.selected_index) continue;

					html += this.tariffRowHtml(this.selected_tariffs[index], index);
				}

				html += '</div>';
			}
		}

		const selected = this.selected_tariffs[this.selected_index];

		html +=
				'<button type="button" class="apiship_card_submit" data-apiship-take="1">' +
					esc(texts.map_take_here) + ' — ' + esc(selected.text) +
				'</button>' +
			'</div>';

		return html;
	}

	// Строка тарифа: переключатель, логотип службы, название тарифа, служба со сроком и цена
	tariffRowHtml(tariff, index) {
		const esc = ApishipMap.escapeHtml;
		const checked = index === this.selected_index;
		const days = ApishipMap.tariffDaysText(tariff, this.texts);
		const cheapest = index === 0 && this.selected_tariffs.length > 1 && this.selected_tariffs[this.selected_tariffs.length - 1].cost > tariff.cost;

		return '<label class="apiship_tariff' + (checked ? ' apiship_tariff-checked' : '') + '" data-apiship-tariff="' + index + '">' +
			'<span class="apiship_tariff_radio">' + (checked ? ApishipMap.icon('check') : '') + '</span>' +
			this.providerLogo(tariff.provider_key, tariff.provider) +
			'<span class="apiship_tariff_text">' +
				'<span class="apiship_tariff_name">' + esc(tariff.tariff) + '</span>' +
				'<span class="apiship_tariff_note">' + esc(tariff.provider) + (days ? ' — ' + esc(days) : '') + '</span>' +
			'</span>' +
			'<span class="apiship_tariff_price">' + esc(tariff.text) +
				(cheapest ? '<span class="apiship_tariff_cheapest">' + esc(this.texts.map_cheapest) + '</span>' : '') +
			'</span>' +
		'</label>';
	}

	onCardClick(event) {
		const target = event.target;

		if (!target || !target.closest) return;

		if (target.closest('[data-apiship-close]')) {
			event.preventDefault();
			this.closeCard();
			this.renderMarkers();

			return;
		}

		if (target.closest('[data-apiship-expand]')) {
			event.preventDefault();
			this.expanded = !this.expanded;
			this.showCard();

			return;
		}

		const row = target.closest('[data-apiship-tariff]');

		if (row) {
			event.preventDefault();
			this.selected_index = parseInt(row.getAttribute('data-apiship-tariff'), 10) || 0;
			this.showCard();

			return;
		}

		if (target.closest('[data-apiship-take]')) {
			event.preventDefault();
			this.choose(this.selected_tariffs[this.selected_index]);
		}
	}

	// Выбор точки и тарифа уходит в чекаут тем же кодом варианта, что и раньше
	choose(tariff) {
		if (!tariff || typeof this.callback_function !== 'function') return;

		this.callback_function(tariff.code, this.callback_code);
		this.modal.close();
	}

	/* ------------------------------------------------------------------ *
	 * Поиск, фильтры, зум и счётчик поверх карты
	 * ------------------------------------------------------------------ */

	buildControls(container) {
		const esc = ApishipMap.escapeHtml;

		this.ui = {menus: []};

		const panel = document.createElement('div');

		panel.className = 'apiship_panel';
		panel.innerHTML =
			'<div class="apiship_search">' + ApishipMap.icon('search') +
				'<input type="text" class="apiship_search_input" placeholder="' + esc(this.texts.map_search) + '">' +
			'</div>';

		this.ui.search = panel.querySelector('.apiship_search_input');
		this.ui.search.addEventListener('keydown', (event) => {
			if (event.key === 'Enter' || event.keyCode === 13) {
				event.preventDefault();
				this.search(this.ui.search.value);
			}
		});

		this.ui.providers = this.buildFilter(
			panel,
			ApishipMap.providerCounts(this.points, this.tariffs),
			this.texts.map_providers_all,
			this.texts.map_providers_title,
			'truck',
			(selected) => {
				this.provider_filter = selected;
				this.onFilterChange();
			}
		);

		const types = ApishipMap.typeCounts(this.points);

		// Фильтр типов нужен, только когда типов больше одного (OCM-5)
		if (types.length > 1) {
			this.ui.types = this.buildFilter(
				panel,
				types,
				this.texts.map_types_all,
				this.texts.map_type,
				'filter',
				(selected) => {
					this.type_filter = selected;
					this.onFilterChange();
				}
			);
		}

		// Клик мимо панели закрывает открытый фильтр, по самой панели — нет
		panel.addEventListener('click', (event) => event.stopPropagation());
		container.addEventListener('click', () => this.closeMenus());

		container.appendChild(panel);

		const zoom = document.createElement('div');

		zoom.className = 'apiship_zoom';
		zoom.innerHTML =
			'<button type="button" class="apiship_zoom_button" data-apiship-zoom="1" title="' + esc(this.texts.map_zoom_in) + '">' + ApishipMap.icon('plus') + '</button>' +
			'<button type="button" class="apiship_zoom_button" data-apiship-zoom="-1" title="' + esc(this.texts.map_zoom_out) + '">' + ApishipMap.icon('minus') + '</button>' +
			'<button type="button" class="apiship_zoom_button" data-apiship-geolocation="1" title="' + esc(this.texts.map_geolocation) + '">' + ApishipMap.icon('target') + '</button>';

		zoom.addEventListener('click', (event) => {
			const step = event.target.closest ? event.target.closest('[data-apiship-zoom]') : null;

			if (step) {
				const delta = parseInt(step.getAttribute('data-apiship-zoom'), 10);

				this.map.setLocation({zoom: Math.max(2, Math.min(21, (this.location.zoom || 12) + delta)), duration: 200});

				return;
			}

			if (event.target.closest && event.target.closest('[data-apiship-geolocation]')) {
				this.geolocate();
			}
		});

		container.appendChild(zoom);

		const counter = document.createElement('div');

		counter.className = 'apiship_counter';
		counter.style.display = 'none';

		this.ui.counter = counter;

		container.appendChild(counter);
	}

	// Общий выпадающий фильтр: строки с логотипом (у служб), названием и числом пунктов
	buildFilter(panel, options, all_text, title_text, icon, onChange) {
		const esc = ApishipMap.escapeHtml;

		const filter = {selected: [], options: options};

		const wrapper = document.createElement('div');

		wrapper.className = 'apiship_filter';
		wrapper.innerHTML =
			'<button type="button" class="apiship_filter_button">' + ApishipMap.icon(icon) +
				'<span class="apiship_filter_label"></span>' +
				'<span class="apiship_filter_count"></span>' +
				'<span class="apiship_filter_chevron">' + ApishipMap.icon('chevron') + '</span>' +
			'</button>' +
			'<div class="apiship_filter_menu" style="display:none">' +
				'<div class="apiship_filter_head">' + esc(title_text) +
					'<button type="button" class="apiship_filter_reset">' + esc(this.texts.map_filter_reset) + '</button>' +
				'</div>' +
				'<div class="apiship_filter_list"></div>' +
				'<button type="button" class="apiship_filter_apply"></button>' +
			'</div>';

		const button = wrapper.querySelector('.apiship_filter_button');
		const label = wrapper.querySelector('.apiship_filter_label');
		const count = wrapper.querySelector('.apiship_filter_count');
		const menu = wrapper.querySelector('.apiship_filter_menu');
		const list = wrapper.querySelector('.apiship_filter_list');
		const apply = wrapper.querySelector('.apiship_filter_apply');

		const redraw = () => {
			const names = options.filter((option) => filter.selected.indexOf(option.key) !== -1).map((option) => option.name);

			button.className = 'apiship_filter_button' + (names.length ? ' apiship_filter_button-active' : '');
			label.textContent = names.length ? names.join(', ') : all_text;
			count.textContent = names.length ? String(names.length) : '';

			let rows = '';

			for (const option of options) {
				const checked = filter.selected.indexOf(option.key) !== -1;

				rows +=
					'<label class="apiship_filter_row" data-apiship-option="' + esc(option.key) + '">' +
						'<span class="apiship_filter_check' + (checked ? ' apiship_filter_check-on' : '') + '">' + (checked ? ApishipMap.icon('check') : '') + '</span>' +
						(option.logo === false ? '' : this.providerLogo(option.key, option.name)) +
						'<span class="apiship_filter_row_text">' +
							'<span class="apiship_filter_row_name">' + esc(option.name) + '</span>' +
							'<span class="apiship_filter_row_count">' + option.count + ' ' + esc(ApishipMap.plural(option.count, this.texts.map_points)) + '</span>' +
						'</span>' +
					'</label>';
			}

			list.innerHTML = rows;

			const visible = ApishipMap.filterPoints(this.points, this.tariffs, this.provider_filter, this.type_filter).length;

			apply.textContent = ApishipMap.format(this.texts.map_filter_apply, {
				n: visible,
				points: ApishipMap.plural(visible, this.texts.map_points)
			});
		};

		this.ui.menus.push(menu);

		button.addEventListener('click', (event) => {
			event.preventDefault();

			const open = menu.style.display === 'none';

			this.closeMenus();

			menu.style.display = open ? 'block' : 'none';
		});

		list.addEventListener('click', (event) => {
			const row = event.target.closest ? event.target.closest('[data-apiship-option]') : null;

			if (!row) return;

			event.preventDefault();

			const key = row.getAttribute('data-apiship-option');
			const index = filter.selected.indexOf(key);

			if (index === -1) {
				filter.selected.push(key);
			} else {
				filter.selected.splice(index, 1);
			}

			onChange(filter.selected.slice());
			redraw();
		});

		wrapper.querySelector('.apiship_filter_reset').addEventListener('click', (event) => {
			event.preventDefault();
			filter.selected = [];
			onChange([]);
			redraw();
		});

		apply.addEventListener('click', (event) => {
			event.preventDefault();
			menu.style.display = 'none';
		});

		panel.appendChild(wrapper);

		filter.redraw = redraw;

		redraw();

		return filter;
	}

	closeMenus() {
		for (const menu of (this.ui.menus || [])) {
			menu.style.display = 'none';
		}
	}

	// Фильтр сменился: карточка прежней точки больше не отражает доступные тарифы, закрываем её
	onFilterChange() {
		this.closeCard();
		this.renderMarkers();

		if (this.ui.providers) this.ui.providers.redraw();
		if (this.ui.types) this.ui.types.redraw();
	}

	updateCounter(visible) {
		if (!this.ui || !this.ui.counter) return;

		const filtered = this.provider_filter.length || this.type_filter.length;

		this.ui.counter.style.display = filtered ? 'flex' : 'none';

		if (!filtered) return;

		this.ui.counter.innerHTML = ApishipMap.icon('filter') + ApishipMap.escapeHtml(ApishipMap.format(this.texts.map_filter_counter, {
			n: visible,
			points: ApishipMap.plural(visible, this.texts.map_points),
			total: this.points.length
		}));
	}

	geolocate() {
		if (!navigator.geolocation) return;

		navigator.geolocation.getCurrentPosition((position) => {
			if (!this.map) return;

			this.map.setLocation({
				center: [position.coords.longitude, position.coords.latitude],
				zoom: Math.max(this.location.zoom || 12, 13),
				duration: 300
			});
		}, () => {});
	}

	// Поиск по адресу через Search API; если он не подключён к ключу, ищем среди адресов точек
	search(text) {
		const query = String(text || '').trim();

		if (query === '' || !this.map) return;

		const local = () => this.searchPoints(query);

		if (!ymaps3.search) {
			local();

			return;
		}

		ymaps3.search({text: query, bounds: this.location ? this.location.bounds : null}).then((result) => {
			const found = (result || []).filter((feature) => feature && feature.geometry && Array.isArray(feature.geometry.coordinates))[0];

			if (!found) {
				local();

				return;
			}

			this.map.setLocation({center: found.geometry.coordinates, zoom: Math.max(this.location.zoom || 12, 13), duration: 300});
		}).catch(() => local());
	}

	searchPoints(query) {
		const needle = query.toLowerCase();

		const found = this.points.filter((point) => {
			return String(point.title || point.address || '').toLowerCase().indexOf(needle) !== -1 ||
				String(point.subtitle || '').toLowerCase().indexOf(needle) !== -1;
		})[0];

		if (!found) {
			this.showSearchEmpty();

			return;
		}

		this.map.setLocation({center: [Number(found.lon), Number(found.lat)], zoom: Math.max(this.location.zoom || 12, 14), duration: 300});
	}

	showSearchEmpty() {
		if (!this.ui || !this.ui.search) return;

		const input = this.ui.search;
		const previous = input.placeholder;

		input.value = '';
		input.placeholder = this.texts.map_search_empty;

		setTimeout(() => {
			input.placeholder = previous;
		}, 2000);
	}
}
