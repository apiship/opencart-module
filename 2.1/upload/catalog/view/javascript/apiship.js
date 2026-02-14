class ApishipMap {
	constructor() {
		this.ID_MODAL = 'apiship_yandex_map';
		this.YANDEX_MAP_CONTAINER_ID = 'apiship_yandex_map_container';
		
		this.callback_function = null;
		this.callback_code = null;
		this.Mymap = null;
		this.instance = null;
		
		this.modal = {
			initLayout: {
				template: () => `
					<div class="modal fade apiship_modal" id="${this.ID_MODAL}" tabindex="-1" role="dialog">
						<div class="modal-dialog apiship_modal-dialog" role="document">
							<div class="modal-content apiship_modal-content">
								<div class="modal-header apiship_modal-header">
									<h4>Пункты самовывоза</h4>
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
		
			checkOnInit: () => {
				return !!document.getElementById(this.ID_MODAL);
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
			settings: {},
			initApi: () => {
				let yandex_api_key = get_yandex_api_key();
				let script_src;
				
				if (yandex_api_key === '') {
					script_src = 'https://api-maps.yandex.ru/2.1/?lang=ru_RU';
				} else {
					script_src = 'https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=' + yandex_api_key;
				}
				
				if (typeof ymaps !== 'undefined') return;
				
				let script = document.createElement('script');
				script.setAttribute('src', script_src);
				script.setAttribute('defer', '');
				script.setAttribute('data', 'yandex-map');
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
					controls: (get_yandex_api_key() === '') ? 
						['zoomControl'] : 
						['zoomControl', 'geolocationControl', apishipSearchControl]
				}, {
					suppressMapOpenBlock: true
				});
				
				this.yandexMaps.createPlacemarks(this.yandexMaps.points, this.Mymap);
			},
			createPlacemarks: (points, map) => {
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
									el.innerHTML = ' от ' + text_min;
								} else {
									el.innerHTML = '<img style="width:64px;vertical-align: middle;" src="https://storage.apiship.ru/icons/providers/svg/' + providerKey + '.svg">' + ' от ' + text_min;
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
					
					const balloonContentBody =						
						'<h3 style="font-size: 1.3em;font-weight: bold;margin-bottom: 0.5em;">' + point.address + '</h3>' +
						'<b>Стоимость: </b>' + point.text + '<br>' +
						(point.paymentCash == 1 ? '<img title="Оплата наличными" src="./catalog/view/theme/default/image/shipping/apiship_cash.png">' : '') + ' ' +
						(point.paymentCard == 1 ? '<img title="Оплата картой" src="./catalog/view/theme/default/image/shipping/apiship_card.png">' : '');
					
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
							cost: point.cost,
							text: point.text,
							balloonContentHeader: point.tariff,
							balloonContentBody: balloonContentBody,
							balloonContentFooter: '<a href=# data-placemarkid="' + point.code + '" class="list_item btn btn-success">Забрать отсюда</a>'
						},
						options: {
							iconLayout: 'default#imageWithContent',
							iconImageHref: '',
							iconContentLayout: ymaps.templateLayoutFactory.createClass(
								'<span class="apiship_cluster"><img style="width:64px;vertical-align: middle;" src="https://storage.apiship.ru/icons/providers/svg/' + point.provider_key + '.svg"> ' + point.text + '</span>'
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
							content: title
						},
						state: {
							selected: true
						}
					});
				});
				
				let listBoxControlTypes = new ymaps.control.ListBox({
					data: {
						content: 'Тип точки',
						title: 'Тип точки'
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
							content: title
						},
						state: {
							selected: true
						}
					});
				});
				
				let listBoxControlProviders = new ymaps.control.ListBox({
					data: {
						content: 'СД',
						title: 'СД'
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
				
				// Мониторинг фильтров
				let filterMonitorTypes = new ymaps.Monitor(listBoxControlTypes.state);
				filterMonitorTypes.add('filters', (filters) => {
					objectManager.setFilter((obj) => {
						let content = obj.properties.type;
						return filters[content];
					});
				});
				
				let filterMonitorProviders = new ymaps.Monitor(listBoxControlProviders.state);
				filterMonitorProviders.add('filters', (filters) => {
					objectManager.setFilter((obj) => {
						let content = obj.properties.provider;
						return filters[content];
					});
				});
				
				// Обработчик клика по кнопке выбора
				$(document).on("click", "a.list_item", (event) => {
					$(document).off("click", "a.list_item");
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
	
	onCloseModal() {
		this.yandexMaps.destroyMap();
	}
	
	init() {
		console.log('apiship init...');
		this.modal.createModalBootstrap();
		this.yandexMaps.initApi();
		$('#' + this.ID_MODAL).on('hide.bs.modal', () => this.onCloseModal());
		return this;
	}
	
	open(points, callback, code) {
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
		
		// Ожидаем загрузку Яндекс.Карт
		if (typeof ymaps !== 'undefined') {
			ymaps.ready(() => this.yandexMaps.initMap());
		} else {
			// Если карты еще не загружены, ждем их загрузку
			let checkYmaps = setInterval(() => {
				if (typeof ymaps !== 'undefined') {
					clearInterval(checkYmaps);
					ymaps.ready(() => this.yandexMaps.initMap());
				}
			}, 100);
		}
		
		this.modal.open();
	}
}
