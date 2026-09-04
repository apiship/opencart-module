/**
 * ApiShip: общие помощники для вкладки заказа и списка заказов в админке OpenCart 4.
 */
function apiship_alert(type, text) {
	$('#alert').prepend('<div class="alert alert-' + type + ' alert-dismissible"><i class="fa-solid fa-circle-' + (type == 'danger' ? 'exclamation' : 'check') + '"></i> ' + text + ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
}

// Ответ get_label / get_waybill: ссылки на файлы в alert об успехе, текст ошибки — в alert об ошибке
function apiship_files(json, key, title) {
	var html = [];

	for (var index = 0; index < (json[key] || []).length; ++index) {
		html.push('<a target="_blank" href="' + json[key][index]['url'] + '">' + json[key][index]['name'] + '</a>');
	}

	if (json['error']) {
		apiship_alert('danger', json['error']);
	}

	if (html.length) {
		apiship_alert('success', title + ': ' + html.join(', '));
	}
}
