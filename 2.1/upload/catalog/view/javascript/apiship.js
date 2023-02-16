var apiship = apiship || (function() {

    var ID_MODAL = 'apiship_yandex_map';
    var YANDEX_MAP_CONTAINER_ID = 'apiship_yandex_map_container';
    var YANDEX_MAP_CONTAINER_ID_FOR_MAP = 'apiship-modal-yandex-map-wrap';
    var YANDEX_MAP_CONTAINER_ID_FOR_ADDRESS = 'apiship-modal-yandex-map-address';


    var modal = {
        initLayout: {
          createRoot: function (){
              let root = document.createElement('div')
              root.classList.add('modal','fade')
              root.setAttribute('tabindex','-1')
              root.setAttribute('role','dialog')
              root.setAttribute('id', ID_MODAL)
              document.body.appendChild(root)
              return root;
          },
          createDialog: function (dom){
              let dialog = document.createElement('div')
              //dialog.classList.add('modal-dialog','modal-lg')
              if(document.documentElement.clientWidth < 768){
                 // dialog.setAttribute('style','width: 100%')
              }
              dom.appendChild(dialog)
              return dialog;
          },
          createContent: function (dom){
              let content = document.createElement('div')
              content.classList.add('modal-content')
		  content.setAttribute('style','margin: 7px')
              dom.appendChild(content)
              return content;
          },
          createHeader: function (dom){
              let header = document.createElement('div')
              header.classList.add('modal-header')
              header.innerHTML = '<h4>Пункты самовывоза</h4>'
              dom.appendChild(header)
              return header;
          },
          createButtonClose: function (dom){
              let buttonClose = document.createElement('button')
              buttonClose.setAttribute('type','button')
              buttonClose.setAttribute('data-dismiss','modal')
              buttonClose.setAttribute('aria-label','Close')
              buttonClose.classList.add('close')
              dom.appendChild(buttonClose)
              return buttonClose;
          },
          createIconClose: function (dom){
              let iconClose = document.createElement('span')
              iconClose.setAttribute('aria-hidden','true')
              iconClose.innerHTML= '&times;'
              dom.appendChild(iconClose)
              return iconClose;
          },
          createBody: function (dom){
              let body = document.createElement('div')
              body.classList.add('modal-body')
              dom.appendChild(body)
              return body;
          },
          createRow: function (dom){
              let row = document.createElement('div')
              row.classList.add('class','row')
              dom.appendChild(row)
              return row;
          },
          createColForMap: function (dom){
              let col = document.createElement('div')
              col.classList.add('col-lg-12','col-md-12')
              col.setAttribute('id',YANDEX_MAP_CONTAINER_ID_FOR_MAP)
              dom.appendChild(col)
              return
          },

        },
        createModalBootstrap: function (){
            if(this.checkOnInit()) return;
            let root = this.initLayout.createRoot(),
                dialog = this.initLayout.createDialog(root),
                content = this.initLayout.createContent(dialog),
                header = this.initLayout.createHeader(content),
                buttonClose = this.initLayout.createButtonClose(header),
                iconClose = this.initLayout.createIconClose(buttonClose),
                body = this.initLayout.createBody(content),
                row = this.initLayout.createRow(body),
                colMap = this.initLayout.createColForMap(row);
        },
        checkOnInit: function (){
            if(document.getElementById(this.idModal)){
                return true
            }
            return false
        },
        open: function (){
            $('#'+ID_MODAL).modal('show')
        },
        close: function (){
            $('#'+ID_MODAL).modal('hide')
        },
        destroy: function (){
            if(this.checkOnInit()){
                document.getElementById(this.idModal).remove()
            }
        }
    };



	var yandexMaps = {
		points: [],
        	settings: {},
        	initApi: function (){
			const script_src = 'https://api-maps.yandex.ru/2.1/?lang=ru_RU';

			if (typeof ymaps !== 'undefined') return;
	
	            let script = document.createElement('script')
	            script.setAttribute('src', script_src)
	            script.setAttribute('defer','')
			script.setAttribute('data','yandex-map')
	            document.head.appendChild(script)
		},
	      createContainer: function (){
	            let container = document.createElement('div'),
	                modalBody = document.getElementById(ID_MODAL).querySelector('.modal-body  #'+YANDEX_MAP_CONTAINER_ID_FOR_MAP);
	            container.setAttribute('id',YANDEX_MAP_CONTAINER_ID)
	            container.setAttribute('style','width: 100%')
	            modalBody.appendChild(container)
		},
		initMap: function (event){
	            Mymap = new ymaps.Map(YANDEX_MAP_CONTAINER_ID, {
	                center: [yandexMaps.points[0]['lat'],yandexMaps.points[0]['lon']],
	                zoom: 10,
	                controls: ['zoomControl']
	            }, {
	                	suppressMapOpenBlock: true
	            })
	            yandexMaps.createPlacemarks(yandexMaps.points, Mymap)
		},
		createPlacemarks: function (points, map){

			objectManager = new ymaps.ObjectManager({
		            // Чтобы метки начали кластеризоваться, выставляем опцию.
		            clusterize: true,
		            gridSize: 128,
		            // Макет метки кластера
		            clusterIconLayout: ymaps.templateLayoutFactory.createClass(
					'<span class="apiship_cluster"></span>',
					{
					    build: function() {
					     		this.constructor.superclass.build.call(this);
							cost_min = 0
							//cost_max = 0
							changeProviderKey = false
							providerKey = ''
							this.getData().properties.geoObjects.forEach((geoObject) => {
								if (providerKey == '')
									providerKey = geoObject.properties.providerKey
								else
									if (providerKey!=geoObject.properties.providerKey) changeProviderKey = true
	
	
								//if (geoObject.properties.cost > cost_max) cost_max = geoObject.properties.cost
								if (cost_min == 0) 
									cost_min = geoObject.properties.cost 
								else 
									if (geoObject.properties.cost < cost_min) cost_min = geoObject.properties.cost
							})
							
							el = this.getParentElement().getElementsByClassName('apiship_cluster')[0];
							if (changeProviderKey == true) 
								el.innerHTML = ' от ' + cost_min + 'р.'
							else { 
								el.innerHTML = '<img style="width:64px;vertical-align: middle;" src="https://storage.apiship.ru/icons/providers/svg/'+providerKey+'.svg">' + ' от ' + cost_min + 'р.'
		      				}

					    }
					}
				),
				clusterIconShape: {
					type: 'Rectangle',
	        			coordinates: [[0, 0], [140, 40]]
				}
	
			});

			iteration = 0;

			var point_types = [];
			var providers = [];

			for(const point of points){
				if (!point_types.includes(point.type)) point_types.push(point.type);
				if (!providers.includes(point.provider)) providers.push(point.provider);

                		const balloonContentBody = 
	                    	'<h3 style="font-size: 1.3em;font-weight: bold;margin-bottom: 0.5em;">' + point.title + '</h3>' +
					'<b>Стоимость: </b>' + point.text;

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
							balloonContentHeader : point.tariff,
							balloonContentBody : balloonContentBody,
							balloonContentFooter: '<a href=# data-placemarkid="' +point.code+ '" class="list_item btn btn-success">Забрать отсюда</a>',
						},
						options: {
							iconLayout: 'default#imageWithContent',
							iconImageHref: '',
							iconContentLayout: ymaps.templateLayoutFactory.createClass(
            						'<span class="apiship_cluster"><img style="width:64px;vertical-align: middle;" src="https://storage.apiship.ru/icons/providers/svg/' + point.provider_key + '.svg"> '+point.cost + 'р.' +'</span>'
        						),
							iconImageSize: [140, 40],
							iconImageOffset: [0, 0],
							hideIconOnBalloonOpen: false
						}
		
					});
					iteration++;
			}

			map.geoObjects.add(objectManager);
		

	    		var pointTypesItems = point_types
		            .map(function (title) {
		                return new ymaps.control.ListBoxItem({
		                    data: {
		                        content: title
		                    },
		                    state: {
		                        selected: true
		                    }
		                })
		            }),
		        reducer = function (filters, filter) {
		            filters[filter.data.get('content')] = filter.isSelected();
		            return filters;
		        },
		        
		        listBoxControlTypes = new ymaps.control.ListBox({
		            data: {
		                content: 'Тип точки',
		                title: 'Тип точки'
		            },
		            items: pointTypesItems,
		            state: {
		                filters: pointTypesItems.reduce(reducer, {})
		            }
		        });
		    	map.controls.add(listBoxControlTypes);
			
			// Providers
		    	var pointProvidersItems = providers
		            .map(function (title) {
		                return new ymaps.control.ListBoxItem({
		                    data: {
		                        content: title
		                    },
		                    state: {
		                        selected: true
		                    }
		                })
		            }),
		        reducer = function (filters, filter) {
		            filters[filter.data.get('content')] = filter.isSelected();
		            return filters;
		        },
		        
		        listBoxControlProviders = new ymaps.control.ListBox({
		            data: {
		                content: 'СД',
		                title: 'СД'
		            },
		            items: pointProvidersItems,
		            state: {
		                filters: pointProvidersItems.reduce(reducer, {})
		            }
		        });
	
		    	map.controls.add(listBoxControlProviders);
	
			listBoxControlTypes.events.add(['select', 'deselect'], function (e) {
		        	var listBoxItem = e.get('target');
		        	var filters = ymaps.util.extend({}, listBoxControlTypes.state.get('filters'));
		        	filters[listBoxItem.data.get('content')] = listBoxItem.isSelected();
		        	listBoxControlTypes.state.set('filters', filters);
		    	});
	
		    	listBoxControlProviders.events.add(['select', 'deselect'], function (e) {
		        	var listBoxItem = e.get('target');
		        	var filters = ymaps.util.extend({}, listBoxControlProviders.state.get('filters'));
		        	filters[listBoxItem.data.get('content')] = listBoxItem.isSelected();
		        	listBoxControlProviders.state.set('filters', filters);
		    	});
	
		
		    	var filterMonitorTypes = new ymaps.Monitor(listBoxControlTypes.state);
		    		filterMonitorTypes.add('filters', function (filters) {
		        	objectManager.setFilter(getFilterFunctionTypes(filters));
		    	});
	
		    	var filterMonitorProviders = new ymaps.Monitor(listBoxControlProviders.state);
		    		filterMonitorProviders.add('filters', function (filters) {
		        	objectManager.setFilter(getFilterFunctionProviders(filters));
		    	});
	
		    	function getFilterFunctionTypes(categories) {
		        	return function (obj) {
		            	var content = obj.properties.type;
		            	return categories[content]
		        	}
		    	}
	
		    	function getFilterFunctionProviders(categories) {
		        	return function (obj) {
		            	var content = obj.properties.provider;
		            	return categories[content]
		        	}
		    	}
	
			$(document).on( "click", "a.list_item", function(event) {	
				$(document).off( "click", "a.list_item");				
				event.preventDefault();
				callback_function($(this).data().placemarkid, callback_code)
				onCloseModal()				
				modal.close();				
	        	});
	
				
	
	        	},
	        	destroyMap: function (){
	            	var el = document.getElementById(YANDEX_MAP_CONTAINER_ID);
				if (el!==null) el.remove();
	        	}
    	};

      function onCloseModal(){
           yandexMaps.destroyMap()
      };

	var callback_function = null
	var callback_code = null
	var Mymap = null
	
	var instance;
	
	function init() {				
		console.log('apiship init...')
		modal.createModalBootstrap()
		yandexMaps.initApi()
		$('#'+ID_MODAL).on('hide.bs.modal', onCloseModal);
		return this;
	};

	return {
	 
	    	getInstance: function () {
	 
	      	if ( !instance ) {
	        		instance = init();
	      	}
	 
	      	return instance;
	    	},

	      open: function(callback, points, code){
			callback_function = callback
			callback_code = code
	
	            yandexMaps.createContainer()
				
			height = window.innerHeight*0.8;
			document.getElementById('apiship_yandex_map_container').style.height = height + "px";

	            yandexMaps.points = points
	            ymaps.ready(yandexMaps.initMap)
	            modal.open()

	      }

	 
	  };


})();

apiship.getInstance()

