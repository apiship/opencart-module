/**
 * ApiShip: карта пунктов выдачи (Яндекс.Карты 2.1) в модальном окне Bootstrap 3.
 * Использование: new ApishipMap(config).open(points, callback, code)
 * config.texts — подписи, config.image_path — путь к иконкам модуля, config.yandex_api_key — ключ Яндекс API.
 * Без config берётся глобальный apiship_config, а ключ — из глобальной get_yandex_api_key() (так работают моды шаблонов).
 */
class ApishipMap {
	constructor(config) {
		this.ID_MODAL = 'apiship_yandex_map';
		this.YANDEX_MAP_CONTAINER_ID = 'apiship_yandex_map_container';

		this.config = config || (typeof apiship_config !== 'undefined' ? apiship_config : {});
		this.texts = Object.assign({
			from: 'от',
			map_title: 'Пункты самовывоза',
			map_cost: 'Стоимость',
			map_take_here: 'Забрать отсюда',
			map_type: 'Тип точки',
			map_provider: 'СД',
			map_cash: 'Оплата наличными',
			map_card: 'Оплата картой',
			map_no_points: 'Пункты выдачи не найдены',
			map_load: 'Не удалось загрузить карту. Обновите страницу'
		}, this.config.texts || {});
		this.image_path = this.config.image_path || './catalog/view/theme/default/image/shipping/';

		this.callback_function = null;
		this.callback_code = null;
		this.Mymap = null;
		this.checkYmaps = null;
		this.loadFailed = false;

		this.modal = {
			initLayout: {
				template: () => `
					<div class="modal fade apiship_modal" id="${this.ID_MODAL}" tabindex="-1" role="dialog">
						<div class="modal-dialog apiship_modal-dialog" role="document">
							<div class="modal-content apiship_modal-content">
								<div class="modal-header apiship_modal-header">
									<h4>${ApishipMap.escapeHtml(this.texts.map_title)}</h4>
									<button type="button" class="close" data-dismiss="modal" aria-label="Close">
										<span aria-hidden="true">&times;</span>
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
			},

			destroy: () => {
				const modal = document.getElementById(this.ID_MODAL);
				if (modal) modal.remove();
			}
		};

		this.yandexMaps = {
			points: [],
			initApi: () => {
				let yandex_api_key = this.getApiKey();
				let script_src;

				if (yandex_api_key === '') {
					script_src = 'https://api-maps.yandex.ru/2.1/?lang=ru_RU';
				} else {
					script_src = 'https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=' + encodeURIComponent(yandex_api_key);
				}

				if (typeof ymaps !== 'undefined') return;
				if (document.querySelector('script[data-apiship-ymaps]')) return;

				let script = document.createElement('script');
				script.setAttribute('src', script_src);
				script.setAttribute('defer', '');
				script.setAttribute('data-apiship-ymaps', '1');
				script.onerror = () => {
					this.loadFailed = true;
					script.remove();
				};
				document.head.appendChild(script);
			},
			createContainer: () => {
				let container = document.createElement('div');
				let modalBody = document.getElementById(this.ID_MODAL).querySelector('.modal-body');
				container.setAttribute('id', this.YANDEX_MAP_CONTAINER_ID);
				modalBody.appendChild(container);
			},
			initMap: () => {
				let apishipSearchControl = new ymaps.control.SearchControl({
					options: {
						provider: 'yandex#search',
						noPopup: 'true'
					}
				});

				this.Mymap = new ymaps.Map(this.YANDEX_MAP_CONTAINER_ID, {
					center: [this.yandexMaps.points[0]['lat'], this.yandexMaps.points[0]['lon']],
					zoom: 10,
					controls: (this.getApiKey() === '') ?
						['zoomControl'] :
						['zoomControl', 'geolocationControl', apishipSearchControl]
				}, {
					suppressMapOpenBlock: true
				});

				this.yandexMaps.createPlacemarks(this.yandexMaps.points, this.Mymap);
			},
			createPlacemarks: (points, map) => {
				const texts = this.texts;
				const esc = ApishipMap.escapeHtml;

				let objectManager = new ymaps.ObjectManager({
					clusterize: true,
					gridSize: 128,
					clusterIconLayout: ymaps.templateLayoutFactory.createClass(
						'<span class="apiship_cluster"></span>',
						{
							build: function () {
								this.constructor.superclass.build.call(this);
								let cost_min = 0;
								let text_min = '';
								let changeProviderKey = false;
								let providerKey = '';

								this.getData().properties.geoObjects.forEach((geoObject) => {
									if (providerKey === '') {
										providerKey = geoObject.properties.providerKey;
									} else if (providerKey !== geoObject.properties.providerKey) {
										changeProviderKey = true;
									}

									if (cost_min === 0) {
										cost_min = geoObject.properties.cost;
										text_min = geoObject.properties.text;
									} else if (geoObject.properties.cost < cost_min) {
										cost_min = geoObject.properties.cost;
										text_min = geoObject.properties.text;
									}
								});

								let el = this.getParentElement().getElementsByClassName('apiship_cluster')[0];
								if (changeProviderKey) {
									el.innerHTML = ' ' + esc(texts.from) + ' ' + esc(text_min);
								} else {
									el.innerHTML = '<img style="width:64px;vertical-align: middle;" src="https://storage.apiship.ru/icons/providers/svg/' + encodeURIComponent(providerKey) + '.svg">' + ' ' + esc(texts.from) + ' ' + esc(text_min);
								}
							}
						}
					),
					clusterIconShape: {
						type: 'Rectangle',
						coordinates: [[0, 0], [140, 40]]
					}
				});

				let iteration = 0;
				let point_types = [];
				let providers = [];

				for (const point of points) {
					if (!point_types.includes(point.type)) point_types.push(point.type);
					if (!providers.includes(point.provider)) providers.push(point.provider);

					// Адрес, тариф, стоимость приходят из API — в html только экранированными
					const balloonContentBody =
						'<h3 style="font-size: 1.3em;font-weight: bold;margin-bottom: 0.5em;">' + esc(point.address) + '</h3>' +
						'<b>' + esc(texts.map_cost) + ': </b>' + esc(point.text) + '<br>' +
						(point.paymentCash == 1 ? '<img title="' + esc(texts.map_cash) + '" src="' + esc(this.image_path) + 'apiship_cash.png">' : '') + ' ' +
						(point.paymentCard == 1 ? '<img title="' + esc(texts.map_card) + '" src="' + esc(this.image_path) + 'apiship_card.png">' : '');

					objectManager.add({
						type: 'Feature',
						id: iteration,
						geometry: {
							type: 'Point',
							coordinates: [point.lat, point.lon]
						},
						properties: {
							type: point.type,
							provider: point.provider,
							providerKey: point.provider_key,
							cost: parseFloat(point.cost),
							text: point.text,
							balloonContentHeader: esc(point.tariff),
							balloonContentBody: balloonContentBody,
							balloonContentFooter: '<a href=# data-placemarkid="' + esc(point.code) + '" class="list_item btn btn-success">' + esc(texts.map_take_here) + '</a>'
						},
						options: {
							iconLayout: 'default#imageWithContent',
							iconImageHref: '',
							iconContentLayout: ymaps.templateLayoutFactory.createClass(
								'<span class="apiship_cluster"><img style="width:64px;vertical-align: middle;" src="https://storage.apiship.ru/icons/providers/svg/' + encodeURIComponent(point.provider_key) + '.svg"> ' + esc(point.text) + '</span>'
							),
							iconImageSize: [140, 40],
							iconImageOffset: [0, 0],
							hideIconOnBalloonOpen: false
						}
					});

					iteration++;
				}

				map.geoObjects.add(objectManager);

				// Типы точек
				let pointTypesItems = point_types.map(function (title) {
					return new ymaps.control.ListBoxItem({
						data: {
							content: esc(title)
						},
						state: {
							selected: true
						}
					});
				});

				let listBoxControlTypes = new ymaps.control.ListBox({
					data: {
						content: esc(texts.map_type),
						title: texts.map_type
					},
					items: pointTypesItems,
					state: {
						filters: pointTypesItems.reduce((filters, filter) => {
							filters[filter.data.get('content')] = filter.isSelected();
							return filters;
						}, {})
					}
				});

				map.controls.add(listBoxControlTypes);

				// Службы доставки
				let pointProvidersItems = providers.map(function (title) {
					return new ymaps.control.ListBoxItem({
						data: {
							content: esc(title)
						},
						state: {
							selected: true
						}
					});
				});

				let listBoxControlProviders = new ymaps.control.ListBox({
					data: {
						content: esc(texts.map_provider),
						title: texts.map_provider
					},
					items: pointProvidersItems,
					state: {
						filters: pointProvidersItems.reduce((filters, filter) => {
							filters[filter.data.get('content')] = filter.isSelected();
							return filters;
						}, {})
					}
				});

				map.controls.add(listBoxControlProviders);

				// Обработчики событий для фильтров
				listBoxControlTypes.events.add(['select', 'deselect'], (e) => {
					let listBoxItem = e.get('target');
					let filters = ymaps.util.extend({}, listBoxControlTypes.state.get('filters'));
					filters[listBoxItem.data.get('content')] = listBoxItem.isSelected();
					listBoxControlTypes.state.set('filters', filters);
				});

				listBoxControlProviders.events.add(['select', 'deselect'], (e) => {
					let listBoxItem = e.get('target');
					let filters = ymaps.util.extend({}, listBoxControlProviders.state.get('filters'));
					filters[listBoxItem.data.get('content')] = listBoxItem.isSelected();
					listBoxControlProviders.state.set('filters', filters);
				});

				// Мониторинг фильтров: у ObjectManager одна функция фильтра, поэтому оба условия применяются вместе
				const applyFilters = () => {
					const types = listBoxControlTypes.state.get('filters');
					const providerFilters = listBoxControlProviders.state.get('filters');

					objectManager.setFilter((obj) => {
						return types[esc(obj.properties.type)] && providerFilters[esc(obj.properties.provider)];
					});
				};

				let filterMonitorTypes = new ymaps.Monitor(listBoxControlTypes.state);
				filterMonitorTypes.add('filters', applyFilters);

				let filterMonitorProviders = new ymaps.Monitor(listBoxControlProviders.state);
				filterMonitorProviders.add('filters', applyFilters);

				// Обработчик клика по кнопке выбора
				$(document).off('click', 'a.list_item');
				$(document).on('click', 'a.list_item', (event) => {
					$(document).off('click', 'a.list_item');
					event.preventDefault();
					this.callback_function($(event.currentTarget).data().placemarkid, this.callback_code);
					this.onCloseModal();
					this.modal.close();
				});
			},
			destroyMap: () => {
				let el = document.getElementById(this.YANDEX_MAP_CONTAINER_ID);
				if (el !== null) el.remove();
			}
		};
	}

	// Экранирование строки для вставки в html (данные точек приходят из API)
	static escapeHtml(value) {
		return String(value == null ? '' : value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#39;');
	}

	// Ключ Яндекс API из конфига; глобальная get_yandex_api_key() — для скриптов модов, определяющих её сами
	getApiKey() {
		if (typeof this.config.yandex_api_key !== 'undefined') {
			return String(this.config.yandex_api_key);
		}

		return (typeof get_yandex_api_key === 'function') ? String(get_yandex_api_key()) : '';
	}

	onCloseModal() {
		this.stopWaiting();
		this.yandexMaps.destroyMap();
	}

	stopWaiting() {
		if (this.checkYmaps !== null) {
			clearInterval(this.checkYmaps);
			this.checkYmaps = null;
		}
	}

	init() {
		this.modal.createModalBootstrap();
		this.yandexMaps.initApi();
		$('#' + this.ID_MODAL).on('hide.bs.modal', () => this.onCloseModal());
		return this;
	}

	open(points, callback, code) {
		if (!Array.isArray(points) || points.length === 0) {
			alert(this.texts.map_no_points);
			return;
		}

		// Удаляем предыдущую модалку если есть
		let existingModal = document.getElementById(this.ID_MODAL);
		if (existingModal) {
			existingModal.remove();
		}

		this.init();

		this.callback_function = callback;
		this.callback_code = code;

		this.yandexMaps.createContainer();
		this.yandexMaps.points = points;

		// Ожидаем загрузку Яндекс.Карт: не дольше 15 секунд, иначе закрываем модалку с сообщением
		if (typeof ymaps !== 'undefined') {
			ymaps.ready(() => this.yandexMaps.initMap());
		} else {
			let attempts = 0;

			this.checkYmaps = setInterval(() => {
				attempts++;

				if (typeof ymaps !== 'undefined') {
					this.stopWaiting();
					ymaps.ready(() => this.yandexMaps.initMap());
				} else if (this.loadFailed || attempts >= 150) {
					this.stopWaiting();
					this.modal.close();
					alert(this.texts.map_load);
				}
			}, 100);
		}

		this.modal.open();
	}
}
