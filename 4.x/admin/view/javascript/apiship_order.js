/**
 * ApiShip: общие помощники для вкладки заказа и списка заказов в админке OpenCart 4.
 */
function apiship_escape(value) {
	return String(value == null ? '' : value)
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#39;');
}

// Тексты ошибок и успеха приходят от API ApiShip — вставляем экранированными
function apiship_alert(type, text) {
	$('#alert').prepend('<div class="alert alert-' + type + ' alert-dismissible"><i class="fa-solid fa-circle-' + (type == 'danger' ? 'exclamation' : 'check') + '"></i> ' + apiship_escape(text) + ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
}

// Ответ ярлыков / актов: ссылки на файлы в alert об успехе, текст ошибки — в alert об ошибке
function apiship_files(json, key, title) {
	var links = [];

	for (var index = 0; index < (json[key] || []).length; ++index) {
		var url = String(json[key][index]['url'] || '');

		// Ссылки на файлы только http(s): никаких javascript: и data:
		if (!/^https?:\/\//i.test(url)) {
			continue;
		}

		links.push('<a target="_blank" rel="noopener noreferrer" href="' + apiship_escape(url) + '">' + apiship_escape(json[key][index]['name']) + '</a>');
	}

	if (json['error']) {
		apiship_alert('danger', json['error']);
	}

	if (links.length) {
		$('#alert').prepend('<div class="alert alert-success alert-dismissible"><i class="fa-solid fa-circle-check"></i> ' + apiship_escape(title) + ': ' + links.join(', ') + ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
	}
}
