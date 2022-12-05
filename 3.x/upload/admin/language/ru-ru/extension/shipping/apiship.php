<?php
// Heading
$_['heading_title']					= 'Расчет доставки ApiShip';

// Text
$_['text_extension']   					= 'Расширения';
$_['text_shipping']      				= 'Доставка';
$_['text_success']       				= 'Настройки модуля обновлены!';
$_['text_url_callback']       			= 'Скрипт обработки заказа';
$_['text_edit']          				= 'Изменить настройки модуля ApiShip';
$_['text_shipping_apiship_cron_url_copy']       = 'Копировать';
$_['text_shipping_apiship_template']    		= '%type %company %name %address (тариф: %tariff) %time';
$_['entry_shipping_apiship_group_points']    	= 'Все ПВЗ на одной карте';

// Entry
$_['entry_shipping_apiship_token']    		= 'Токен';
$_['entry_shipping_apiship_title']    		= 'Название модуля доставки';
$_['entry_shipping_apiship_template']    		= 'Шаблон пунктов модуля доставки<br>
<div style=\'font-weight:normal\'>
<b>%type</b> - тип доставки<br>
<b>%company</b> - транспортная компания<br>
<b>%name</b> - название ПВЗ<br>
<b>%address</b> - адрес ПВЗ<br>
<b>%tariff</b> - название тарифа<br>
<b>%time</b> - сроки доставки<br>
</div>';

$_['entry_shipping_apiship_custom_code']    	= 'customCode для калькулятора тарифов';
$_['entry_shipping_apiship_include_fees']    	= 'Добавить в доставку комиссию ТК';

$_['entry_shipping_apiship_sending_country_code']    = 'Страна';
$_['entry_shipping_apiship_sending_region']    	= 'Регион';
$_['entry_shipping_apiship_sending_city']    	= 'Город';
$_['entry_shipping_apiship_sending_street']    	= 'Улица';
$_['entry_shipping_apiship_sending_house']    	= 'Дом';
$_['entry_shipping_apiship_sending_block']   	= 'Строение/Корпус';
$_['entry_shipping_apiship_sending_office']   	= 'Офис/Квартира';

$_['entry_country_ru']   				= 'Россия';

$_['entry_shipping_apiship_contact_organization']   = 'Организация';
$_['entry_shipping_apiship_contact_name']   	= 'ФИО отвественного лица';
$_['entry_shipping_apiship_contact_phone']   	= 'Телефон';
$_['entry_shipping_apiship_contact_email']   	= 'Email';

$_['entry_shipping_apiship_parcel_length']   	= 'Длина (см)';
$_['entry_shipping_apiship_parcel_width']   	= 'Ширина (см)';
$_['entry_shipping_apiship_parcel_height']   	= 'Высота (см)';
$_['entry_shipping_apiship_parcel_weight']   	= 'Вес (гр)';

$_['entry_shipping_apiship_place_length']   	= 'Длина (см)';
$_['entry_shipping_apiship_place_width']   	= 'Ширина (см)';
$_['entry_shipping_apiship_place_height']   	= 'Высота (см)';
$_['entry_shipping_apiship_place_weight']   	= 'Вес (гр)';

$_['entry_shipping_apiship_sort_order']   	= 'Порядок сортировки';
$_['entry_shipping_apiship_status']   		= 'Статус';
$_['entry_shipping_apiship_export_status'] 	= 'Статус после экспорта';
$_['entry_shipping_apiship_cancel_export_status'] 	= 'Статус после отмены экспорта';
$_['entry_shipping_apiship_export_title']		= 'Создание заказа в Apiship';
$_['entry_shipping_apiship_cancel_export_title']= 'Отмена заказа в Apiship';
$_['entry_shipping_apiship_label_title']		= 'Ярлык';
$_['entry_shipping_apiship_waybill_title']	= 'Акт приема-передачи';

$_['entry_shipping_apiship_pickup_type']		= 'Тип забора груза';
$_['entry_shipping_apiship_pickup_type1']		= 'Курьер';
$_['entry_shipping_apiship_pickup_type2']		= 'Привоз на склад СД';

$_['entry_shipping_apiship_place_dimensions']	= 'Габариты места (см)';
$_['entry_shipping_apiship_place_weight']		= 'Вес места (гр)';
$_['entry_shipping_apiship_order_status']		= 'Статус заказа';

$_['entry_shipping_apiship_rub_select']   	= 'Рубль';
$_['entry_shipping_apiship_gr_select']    	= 'Грамм';
$_['entry_shipping_apiship_cm_select']    	= 'Сантиметр';

$_['entry_shipping_apiship_tax_class']    	= 'Налоговый класс';
$_['entry_shipping_apiship_geo_zone']     	= 'Географическая зона';

$_['entry_shipping_apiship_icon_show']    	= 'Показывать иконку';
$_['entry_shipping_apiship_prefix']    		= 'Префикс номера заказа';

$_['entry_shipping_apiship_mode']   		= 'Режим работы';
$_['entry_shipping_apiship_mode_normal']		= 'Нормальный';
$_['entry_shipping_apiship_mode_debug']		= 'Отладка';
$_['entry_shipping_apiship_mode_test']		= 'Тест';

$_['entry_main_settings']				= 'Основные настройки';
$_['entry_sending_address']				= 'Адрес отправления';
$_['entry_contact_details']				= 'Контактные данные';
$_['entry_parcel_defaults']				= 'Параметры товаров по умолчанию';
$_['entry_place_defaults']				= 'Параметры грузоместа (используются, если заполнены)';
$_['entry_extra_settings']				= 'Дополнительные параметры';
$_['entry_providers_points']				= 'Параметры отгрузки в службы доставки';
$_['entry_providers_point']				= 'Привоз на склад';

$_['entry_events_mapping']				= 'Параметры сопоставления статусов';
$_['entry_events_mapping_notify']			= 'Оповестить';


$_['entry_shipping_apiship_paid_orders']		= 'Статусы оплаченных заказов';
$_['entry_shipping_apiship_cron_url']   		= 'cron url:';
$_['entry_shipping_apiship_paid']			= ' (Оплачен) ';

// Error
$_['error_permission']  				= 'У Вас нет прав для управления этим модулем!';
$_['error_shipping_apiship_token'] 			= 'Необходимо заполнить поле!';
$_['error_shipping_apiship_sending_country'] 	= 'Необходимо заполнить поле!';
$_['error_shipping_apiship_sending_region'] 	= 'Необходимо заполнить поле!';
$_['error_shipping_apiship_sending_city'] 	= 'Необходимо заполнить поле!';
$_['error_shipping_apiship_sending_street'] 	= 'Необходимо заполнить поле!';
$_['error_shipping_apiship_sending_house'] 	= 'Необходимо заполнить поле!';
$_['error_shipping_apiship_sending_address2'] 	= 'Необходимо заполнить поле!';

$_['error_shipping_apiship_contact_organization'] 	= 'Необходимо заполнить поле!';
$_['error_shipping_apiship_contact_name'] 	= 'Необходимо заполнить поле!';
$_['error_shipping_apiship_contact_phone'] 	= 'Необходимо заполнить поле!';
$_['error_shipping_apiship_contact_email'] 	= 'Необходимо заполнить поле!';

$_['error_shipping_apiship_parcel_length'] 	= 'Необходимо заполнить поле!';
$_['error_shipping_apiship_parcel_width'] 	= 'Необходимо заполнить поле!';
$_['error_shipping_apiship_parcel_height'] 	= 'Необходимо заполнить поле!';
$_['error_shipping_apiship_parcel_weight'] 	= 'Необходимо заполнить поле!';
$_['error_shipping_apiship_select_orders'] 	= 'Необходимо выбрать заказы!';

