const d = document;
// Time for ninja coding! :)
// After everything is tested maybe replace standart methods with this functions prior minification
// to save 3-4 kb of payload as standart minifyers don't replace common methods
Node.prototype.listen = Node.prototype.addEventListener;
var byId = function(i) {return document.getElementById(i)};
var qs = function(q) {return document.querySelector(q)};
var qsAll = function(q) {return document.querySelectorAll(q)};

let filter_button = document.getElementById('button-filter');




// function horizontalScroll(element) {
// 	element.addEventListener("wheel", (event) => {
// 		event.preventDefault();

// 		let [x, y] = [event.deltaX, event.deltaY];
// 		let magnitude;

// 		if (x === 0) {
// 			magnitude = y > 0 ? -30 : 30;
// 		} else {
// 			magnitude = x;
// 		}

// 		console.log({ x, y, magnitude });
// 		element.scrollBy({
// 			left: -magnitude
// 		});
// 	});
// 	// requestAnimationFrame(horizontalScroll);
// }
// let ololo = d.getElementsByClassName('module-product-list');
// for (var i = 0; i < ololo.length; i++) {
// 	horizontalScroll(ololo[i]);
// }






// TODO :)
document.addEventListener('click', function(e) {
	let filter = [];
	if(!!filter_button && filter_button.contains(e.target)) {
		var filter_inputs = document.querySelectorAll('input[name^=\'filter\']:checked');
		for (let index = 0; index < filter_inputs.length; index++) {
			const f = filter_inputs[index].value;
			if (f !== "0") {
				filter.push(f);
			}
		}
		if (filter.length > 0) {
			filter.sort();
			// TODO Add condition if window.location has question mark
			// Then replace '?filter=' with '&filter='
			window.location = filter_action+'?filter='+ filter.join(',');
		} else {
			window.location = filter_action;
		}
	}
});

// function getURLVar(key) {
// 	var value = [];

// 	var query = String(document.location).split('?');

// 	if (query[1]) {
// 		var part = query[1].split('&');

// 		for (i = 0; i < part.length; i++) {
// 			var data = part[i].split('=');

// 			if (data[0] && data[1]) {
// 				value[data[0]] = data[1];
// 			}
// 		}

// 		if (value[key]) {
// 			return value[key];
// 		} else {
// 			return '';
// 		}
// 	} else { 			// Изменения для seo_url от Русской сборки OpenCart 3x
// 		var query = String(document.location.pathname).split('/');
// 		if (query[query.length - 1] == 'cart') value['route'] = 'checkout/cart';
// 		if (query[query.length - 1] == 'checkout') value['route'] = 'checkout/checkout';

// 		if (value[key]) {
// 			return value[key];
// 		} else {
// 			return '';
// 		}
// 	}
// }

document.addEventListener('DOMContentLoaded', function() {
	mobileMenu(); // Buttons at the bottom of page
	mainMenu(); // render buttons, aria attributes and titles for main menu
	countdown(d); // Countdown to the ent of discounts
	stickyHeader(); // Sticky header
	scrollslider(); // Sliders everywhere
	anchorNav(); // Focus on hastag navigation element

	// Set product count on favicon on page load 
	fetch('index.php?route=common/cart/fetchProductCount').then(r => {return r.text()}).then(resp => { setIcon(resp)})


	// TODO Move this to it's function
	let pagination = document.querySelector('main ul.pagination');
	if (!!pagination) {
		let load_more_btn = createElm({
			type: 'button',
			attrs: {'id':'load-more-btn','class': 'primary', 'aria-label': js_lang.load_more},
			props: {'innerHTML': '<i class="icon-reload"></i>'+js_lang.load_more},
			events: {'click': function(){loadMore()}}
		});
		pagination.insertAdjacentElement('afterend', load_more_btn);
	}




	// Highlight any found errors
	var error_inputs = document.getElementsByClassName('.text-danger');
    for (let i = 0; i < error_inputs.length; i++) {
        const e = error_inputs[i];
        var p = e.parentElement.parentElement;
        if (!!p && p.classList.contains('form-group')) {
            p.classList.add('has-error');
        }
    }


	// Currency
	currency_selector = document.querySelectorAll('#form-currency .currency-select');
    if (currency_selector.length > 0) {
        currency_selector.forEach(function (elm) {
            elm.addEventListener('click', function(evt){
                evt.preventDefault();
                input_currency = document.querySelector('#form-currency input[name=\'code\']');
                input_currency.value = elm.name;
                document.getElementById('form-currency').submit();
            });
        });
    }

	// Language
	lang_selector = document.querySelectorAll('#form-language .language-select');
    if (lang_selector.length > 0) {
        lang_selector.forEach(function (elm) {
            elm.addEventListener('click', function(evt){
                evt.preventDefault();
                input_lang = document.querySelector('#form-language input[name=\'code\']');
                input_lang.value = elm.name;
                document.getElementById('form-language').submit();
            });
        });
	}




	/* Search */
	// $('#search input[name=\'search\']').parent().find('button').on('click', function() {
	// 	var url = $('base').attr('href') + 'index.php?route=product/search';

	// 	var value = $('header #search input[name=\'search\']').val();

	// 	if (value) {
	// 		url += '&search=' + encodeURIComponent(value);
	// 	}

	// 	location = url;
	// });

	// $('#search input[name=\'search\']').on('keydown', function(e) {
	// 	if (e.keyCode == 13) {
	// 		$('header #search input[name=\'search\']').parent().find('button').trigger('click');
	// 	}
	// });



	// // Product List
	// $('#list-view').click(function() {
	// 	$('#content .product-grid > .clearfix').remove();

	// 	$('#content .row > .product-grid').attr('class', 'product-layout product-list col-xs-12');
	// 	$('#grid-view').removeClass('active');
	// 	$('#list-view').addClass('active');

	// 	localStorage.setItem('display', 'list');
	// });

	// // Product Grid
	// $('#grid-view').click(function() {
	// 	// What a shame bootstrap does not take into account dynamically loaded columns
	// 	var cols = $('#column-right, #column-left').length;

	// 	if (cols == 2) {
	// 		$('#content .product-list').attr('class', 'product-layout product-grid col-lg-6 col-md-6 col-sm-12 col-xs-12');
	// 	} else if (cols == 1) {
	// 		$('#content .product-list').attr('class', 'product-layout product-grid col-lg-4 col-md-4 col-sm-6 col-xs-12');
	// 	} else {
	// 		$('#content .product-list').attr('class', 'product-layout product-grid col-lg-3 col-md-3 col-sm-6 col-xs-12');
	// 	}

	// 	$('#list-view').removeClass('active');
	// 	$('#grid-view').addClass('active');

	// 	localStorage.setItem('display', 'grid');
	// });

	// if (localStorage.getItem('display') == 'list') {
	// 	$('#list-view').trigger('click');
	// 	$('#list-view').addClass('active');
	// } else {
	// 	$('#grid-view').trigger('click');
	// 	$('#grid-view').addClass('active');
	// }

	// // Checkout
	// $(document).on('keydown', '#collapse-checkout-option input[name=\'email\'], #collapse-checkout-option input[name=\'password\']', function(e) {
	// 	if (e.keyCode == 13) {
	// 		$('#collapse-checkout-option #button-login').trigger('click');
	// 	}
	// });

});




// Cart add remove functions
var cart = {
	'add': function(product_id, quantity = null) {

		if (quantity == null) {
			qty_input = document.getElementById('input-quantity');
			if (!!qty_input) {
				quantity = qty_input.value;
			}
		}
		var data = 'product_id=' + product_id + '&quantity=' + (typeof(quantity) != 'undefined' ? quantity : 1);

		// Product options
		let options_inputs = document.querySelectorAll('[name^="option"]:checked');
		let options_selects = document.querySelectorAll('select[name^="option"]');
		let options_string = '';
		// Options inputs values
		for (let i = 0; i < options_inputs.length; i++) {
			options_string += '&' + options_inputs[i].name + '=' + options_inputs[i].value;
		}
		// Options selects values
		for (let i = 0; i < options_selects.length; i++) {
			options_string += '&' + options_selects[i].name + '=' + options_selects[i][options_selects[i].selectedIndex].value;
		}
		data += options_string;

		// var data = new FormData();
		// data.append( "json", JSON.stringify( p ) );

		// fetch(url,
		// {
		// 	method: "POST",
		// 	body: data
		// })
		// .then(function(res){ return res.json(); })
		// .then(function(data){ alert( JSON.stringify( data ) ) })

		// Add to cart request
		var url = 'index.php?route=checkout/cart/add';
		ajax(url, data,
			function(r) {
				if (r.success) {
					document.getElementById('total_cart').innerHTML = r.total_cart;
					document.getElementById('product_count').innerText = r.product_count;
					setIcon(r.product_count);
					// Запрос на обновление внешнего вида корзины
					ajax('index.php?route=common/cart/modal',null,function(c) {
						dialog.create(c);
					}, null,null,null,"GET","text",true);

				}
				// Display additional dialog for required options
				if (r.error && r.error.option) {
					let options = r.error.option;
					var url = 'index.php?route=common/cart/displayAdditionalModal';
					var data = 'product_id=' + product_id;
					ajax(url, data, function(response) {
						dialog.create('<span class="h3">'+options+'</span>'+response.data);
					},null,null,null,'POST','json',true);
				}
			},
			null,null,null,'POST','JSON',true
		);
	},
	'update': function(key, quantity) {
		// Эта функция нигде не используется
		var url = 'index.php?route=checkout/cart/edit';
		var data = 'key=' + key + '&quantity=' + (typeof(quantity) != 'undefined' ? quantity : 1);
		ajax(url, data, function(r){
			console.log(r);
		},null,null,null,"POST","JSON",true);
		// 	$.ajax({
		// 		url: 'index.php?route=checkout/cart/edit',
		// 		type: 'post',
		// 		data: 'key=' + key + '&quantity=' + (typeof(quantity) != 'undefined' ? quantity : 1),
		// 		dataType: 'json',
		// 		beforeSend: function() {
		// 			$('#cart > button').button('loading');
		// 		},
		// 		complete: function() {
		// 			$('#cart > button').button('reset');
		// 		},
		// 		success: function(json) {
		// 			// Need to set timeout otherwise it wont update the total
		// 			setTimeout(function () {
		// 				$('#cart > button').html('<span id="cart-total"><i class="fa fa-shopping-cart"></i> ' + json['total'] + '</span>');
		// 			}, 100);

		// 			if (getURLVar('route') == 'checkout/cart' || getURLVar('route') == 'checkout/checkout') {
		// 				location = 'index.php?route=checkout/cart';
		// 			} else {
		// 				$('#cart > ul').load('index.php?route=common/cart/info ul li');
		// 			}
		// 		},
		// 		error: function(xhr, ajaxOptions, thrownError) {
		// 			alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
		// 		}
		// 	});
	},
	'remove': function(key) {
		var url = 'index.php?route=checkout/cart/remove';
		var data = 'key=' + key;
		ajax(url, data, function(r) {
			ajax('index.php?route=common/cart/info', null, function(n) {
				document.getElementById('cart').innerHTML = n;
			},null,null,null,"GET","text",true);
		})
		// $.ajax({
		// 	url: 'index.php?route=checkout/cart/remove',
		// 	type: 'post',
		// 	data: 'key=' + key,
		// 	dataType: 'json',
		// 	beforeSend: function() {
		// 		$('#cart > button').button('loading');
		// 	},
		// 	complete: function() {
		// 		$('#cart > button').button('reset');
		// 	},
		// 	success: function(json) {
		// 		// Need to set timeout otherwise it wont update the total
		// 		setTimeout(function () {
		// 			$('#cart > button').html('<span id="cart-total"><i class="fa fa-shopping-cart"></i> ' + json['total'] + '</span>');
		// 		}, 100);

		// 		if (getURLVar('route') == 'checkout/cart' || getURLVar('route') == 'checkout/checkout') {
		// 			location = 'index.php?route=checkout/cart';
		// 		} else {
		// 			$('#cart > ul').load('index.php?route=common/cart/info ul li');
		// 		}
		// 	},
		// 	error: function(xhr, ajaxOptions, thrownError) {
		// 		alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
		// 	}
		// });
	},
	'showModal': function(){
		ajax('index.php?route=common/cart/modal',null,function(c) {
			dialog.create(c);
		}, null,null,null,"GET","text",true);
	}
}



var voucher = {
	'add': function() {

	},
	'remove': function(key) {

		var url = 'index.php?route=checkout/cart/remove';
		var data = 'key=' + key;
		ajax(url, data, function(r) {
			ajax('index.php?route=common/cart/info', null, function(n) {
				document.getElementById('cart').innerHTML = n;
			},null,null,null,"GET","text",true);
		});


		// $.ajax({
		// 	url: 'index.php?route=checkout/cart/remove',
		// 	type: 'post',
		// 	data: 'key=' + key,
		// 	dataType: 'json',
		// 	beforeSend: function() {
		// 		$('#cart > button').button('loading');
		// 	},
		// 	complete: function() {
		// 		$('#cart > button').button('reset');
		// 	},
		// 	success: function(json) {
		// 		// Need to set timeout otherwise it wont update the total
		// 		setTimeout(function () {
		// 			$('#cart > button').html('<span id="cart-total"><i class="fa fa-shopping-cart"></i> ' + json['total'] + '</span>');
		// 		}, 100);

		// 		if (getURLVar('route') == 'checkout/cart' || getURLVar('route') == 'checkout/checkout') {
		// 			location = 'index.php?route=checkout/cart';
		// 		} else {
		// 			$('#cart > ul').load('index.php?route=common/cart/info ul li');
		// 		}
		// 	},
		// 	error: function(xhr, ajaxOptions, thrownError) {
		// 		alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
		// 	}
		// });
	}
}

var wishlist = {
	// DONE Переделать на отображение окошка со списком желаний
	'add': function(product_id) {
		var url = 'index.php?route=account/wishlist/add';
		var data = 'product_id=' + product_id;
		ajax(url, data, function(r) {
			dialog.create(r['table']);
			document.querySelector('#wishlist-total').innerHTML = r['total'];
		},null,null,null,"POST","json",true);
	},
	'remove': function(product_id) {
		var url = 'index.php?route=account/wishlist/remove';
		var data = 'product_id=' + product_id;
		ajax(url, data, function(r) {
			mwindow.create('toast', r['remove'], 'success');
			document.querySelector('#wishlist-total').innerHTML = r['total'];
		},null,null,null,"POST","json",true);
	},
}

var compare = {
	'add': function(product_id) {
		var url = 'index.php?route=product/compare/add';
		var data = 'product_id=' + product_id;
		ajax(url, data, function(r) {
			dialog.create(r['table']);
			document.querySelector('#compare-total').innerHTML = r['total'];
		},null,null,null,"POST","json",true);
	},
	'remove': function() {

	}
}




// Clicks handling
document.addEventListener('click', function(e) {
	/* Agree to Terms */
	// Кто блядь завернул ссылку с классом в отдельный тег <b>? Рукожопы ебаные.
	agree_links = document.querySelectorAll('.agree');
	if (!!agree_links) {
		for (let i = 0; i < agree_links.length; i++) {
			const agree_link = agree_links[i];
			if (agree_link.contains(e.target) && agree_link.href !== null) {
				e.preventDefault();
				ajax(agree_link.href, null, function(r){
					dialog.create(r);
				},null,null,null,'GET','html',true);
			}
		}
	}
	let main_menu_btn = document.getElementById('main-menu-trigger');
	if (main_menu_btn.contains(e.target)) {
		main_menu_btn.getElementsByClassName('menu-icon')[0].classList.toggle('open');
	}


	// Аякс загрузка отзывов на странице товара
	// DONE исправить, добавить нужные классы к HTML
	if (e.target.tagName.toLowerCase() === 'a' && e.target.href.indexOf('review&product') != '-1') {
		e.preventDefault();
		ajax(e.target.href, null, function(resp){
			document.getElementById('reviews').innerHTML = resp;
		}, null, null, null, 'GET', 'text', true);
	}

	// Добавление товара в корзину
	// if (e.target.id == 'button-cart') {
	// 	var array = [];
	// 	var checkboxes = document.querySelectorAll('input:checked');

	// 	for (var i = 0; i < checkboxes.length; i++) {
	// 		array.push(checkboxes[i].value);
	// 	}
	// 	// console.log(array);
	// }

	// Отзывы
	if (e.target.id == 'button-review') {
		e.preventDefault();
		sendReview(e.target);
	}

	if (e.target.id == 'button-login') {
		login();
	}

});











// Маска номера телефона
// ID инпутов, куда может вводиться номер телефона
// TODO Объединить все в одну функцию
let masked_inputs = ['tel'];
document.addEventListener('input', function(e) {
    if (masked_inputs.indexOf(e.target.type) != -1) {
        e.target.addEventListener('input', handleInput, false);
    }
});


function handleInput (e) {
  e.target.value = phoneMask(e.target.value)
}

function phoneMask (phone) {
  return phone.replace(/\D/g, '')
    .replace(/^(38)/, '')
    .replace(/^(\d)/, '($1')
    .replace(/^(\(\d{3})(\d)/, '$1) $2')
    .replace(/(\d{3})(\d{1,5})/, '$1-$2')
    .replace(/(-\d{4})\d+?$/, '$1');
}

// function sajax({url, data, method, respType}) {
// 	if (typeof(url || data) == "undefined") {
// 		return console.log("data or url undefined");
// 	}
// 	if method
// 	var xhr = new XMLHttpRequest();
// 	xhr.responseType = respType.toLowerCase();
// 	if (method == "POST") {
//         xhr.open(method, url, async);
// 		xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
// 		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
//         xhr.send(data);
//     } else {
//         if(typeof data !== 'undefined' && data !== null) {
//             url = url+'?'+data;
//         }
//         xhr.open(method, url, async);
// 		xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
// 		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
//         xhr.send(null);
// 	}

// }


// AJAX function
function ajax(url, data, success=null, beforesend=null,  complete=null, error=null, method, respType="json", async = true) {
    method = typeof method !== "undefined" ? method : "POST";
    async = typeof async !== 'undefined' ? async : true;
    respType = typeof respType !== 'undefined' ? respType : "json";

    if (window.XMLHttpRequest) {
        var xhr = new XMLHttpRequest();
    } else {
        var xhr = new ActiveXObject("Microsoft.XMLHTTP");
	}
	xhr.responseType = respType.toLowerCase();
	// Function before sending request
	if (beforesend !== null) {
		beforesend();
	}
    if (method == "POST") {
        xhr.open(method, url, async);
		xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        xhr.send(data);
    } else {
        if(typeof data !== 'undefined' && data !== null) {
            url = url+'?'+data;
        }
        xhr.open(method, url, async);
		xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        xhr.send(null);
	}
	// Function on success
	// xhr.onload = function () {
		xhr.onreadystatechange = function() {
			if(xhr.readyState == 4 && xhr.status == 200) {
				if (success !== null) {
					success(xhr.response);
				}
			}
			if (xhr.readyState !== 4 && xhr.status !== 200) {
				if (error !== null) {
					error(xhr.response);
				}
			}
		}
	// }
	xhr.onerror = function() {
		// Ошибка сервера
	}
	xhr.ontimeout = function () {
		// Сервер не отвечает или нет сети
	}
	xhr.onabort = function () {

	}
	if (complete !== null) {
		complete();
	}

}

// // var restURL = "index.php?route=checkout/cart/add" + encodeURIComponent(document.getElementById('email').value)
// var url = "index.php?route=checkout/cart/add&" + 'product_id=' + product_id + '&quantity=' + (typeof(quantity) != 'undefined' ? quantity : 1)
// var opts = {
// 	method: 'POST',
// 	headers: {
// 		'Content-Type': 'application/json',
// 		'Accept': 'application/json'
// 	}
// };
// fetch(url, opts).then(function (response) {
// 	console.log(response);
// 	// return response.json();
// });
// // e.preventDefault();

function login() {
	email = document.querySelector('[name="email"]');
	password = document.querySelector('[name="password"]');
	ajax(
		'index.php?route=checkout/login/save',
		'password='+password+'&email='+email,
		// Если запрос отправлен успешно и получен какой-то ответ...
		(success) => {
			// Закрываем предыдущие сообщения об ошибках
			let error_windows = document.querySelectorAll('.modal_window');
			if (!!error_windows) {
				error_windows.forEach(e => {
					document.body.removeChild(e);
				});
				email.classList.remove('has-error');
				email.parentElement.classList.remove('has-error');
				password.classList.remove('has-error');
				password.parentElement.classList.remove('has-error');
			}
			// Выводим окошки с ошибками и предупреждениями
			if (success.error) {
				for (const error_type in success.error) {
					if (success.error.hasOwnProperty(error_type)) {
						mwindow.create('toast', success.error[error_type], error_type);
					}
				}
				email.classList.add('has-error');
				email.parentElement.classList.add('has-error');
				password.classList.add('has-error');
				password.parentElement.classList.add('has-error');
			}
			// Переадресация, если вход успешный
			if (success.redirect) {
				window.location = success.redirect;
			}

		},
		null,
		(comlpete) => {
			console.log(comlpete);
		},
		(error) => {
			console.log(error);
		},
		'POST',
		'JSON',
		true
	);
}




// ///////////////////////////// //
// Works perfectly - don't touch //
// ///////////////////////////// //


// Mobile menu render
// Find all div.mobile_menu,
// create corresponding button in fixed botton panel,
// order buttons by [data-order],
// add icon from data-icon,
// button name copied from elements [data-block-name]
// Maybe change .mobile-menu to [data-block-name] here and in CSS to keep code cleaner?
function mobileMenu() {
	// Blocks to be shown or hidden
	let mb = d.getElementsByClassName('mobile_menu');
	// Corresponding buttons
	let btns = {};
	if (!!mb && mb.length > 0) {
		for (var k = 0; k < mb.length; k++) {
			let b = mb[k];
			let btn = {
				type: 'button',
				attrs: {'class':'mobile_button button', 'aria-label':b.dataset.blockName},
				props:{'innerHTML':'<i class="'+ b.dataset.icon +'"></i><span>'+ b.dataset.blockName+ '</span>'},
				events:{
					'click': function(e) {
						for (a of mb) {
							if (a !== b) {
								a.classList.remove('open');
							}
						}
						for (c of document.getElementsByClassName('mobile_button')) {
							if (c.contains(e.target)) {
								c.classList.toggle('active');
							} else {
								c.classList.remove('active');
							}
						}
						b.classList.toggle('open');
					}
				},
			};
			btns[mb[k].dataset.order] = btn;
		}
		let catalog_btn  = {
			type: 'button',
			attrs: {'class':'mobile_button button', 'aria-label':js_lang.text_menu_button, 'data-action':'toggleMainMenu'},
			props: {
				'innerHTML':'<i class="icon-cart"></i><span>'+js_lang.text_menu_button+'</span>',
				'dataset' : {'accordionTarget':'main-menu'}
			},
		};
		let cart_btn  = {
			type: 'button',
			attrs: {'class':'mobile_button button', 'aria-label':js_lang.text_cart_button, 'data-action':'cartShowModal'},
			props: {
				'innerHTML':'<i class="icon-cart"></i><span>'+js_lang.text_cart_button+'</span>',
			},
		};
		btns[9] = catalog_btn;
		btns[10] = cart_btn;
		let menu = {
			attrs: {'class': 'mobile_buttons scroll-x'},
			nest: btns
		};
		document.body.insertAdjacentElement('beforeend', createElm(menu));
	}
}


// Create multilevel DOM elements from Javascript Object
// Proudly present :)
function createElm({type, styles, attrs, props, events, nest}) {
	let [eType, eStyle,eAttr, eProps, eHandlers] = [type || 'div', styles || {}, attrs || {}, props || {}, events || {}];
	let el = document.createElement(eType);
	for (let k in eStyle) {el.style[k] = eStyle[k]}
	for (let k in eAttr) {el.setAttribute(k, eAttr[k])}
	for (let k in eProps) {
		if(k == 'dataset') {
			for (let d in eProps[k]) {
				el.dataset[d] = eProps[k][d];
			}
		}
		el[k] = eProps[k]
	}
	for (let k in eHandlers) {el.addEventListener(k,eHandlers[k])}
	if(!!nest && Object.keys(nest).length !== 0) {
		for (const k in nest) {
			if (nest.hasOwnProperty(k)) {
				let e = createElm(nest[k]);
				el.appendChild(e);
			}
		}
	}
	return el;
}




// TODO Допилить это
// let dialog;
let dialog = {
	create: (content, event) => {
		dialog.close();
		let a;
		a = createElm({
			type: 'dialog',
			attrs: {'class': '', 'role':'dialog'},
			events: {
				'close': (e) => {console.log('closed', e)},
				'click': (e) => {
					// Click outside the dialog
					if (e.target.contains(a)) {
						dialog.close()
					}
				}
			},
			nest: [
				{attrs: {'class':'modal_content'},
				// Inner content. If typeof object (JSON or DOM) use outer HTML that parses it into HTML 
				props:{'innerHTML': (typeof(content) == 'object' ? content.outerHTML : content)}},
				// Close button
				{type: 'button', attrs: {'class':'close','aria-label':js_lang.close, 'title':js_lang.close}, events: {'click': (e) => {dialog.close(e)}}},
			]
		});
		// Append dialog to document
		document.body.insertAdjacentElement('beforeend', a);
		// Prevent body scrolling
		document.body.style.cssText = 'overflow: hidden;'
		// Open dialog
		// If click event is set, apply some nice animation
		// Else just open
		if (event) {
			// Class .animate sets transform: scale(0,0,0)
			a.classList.add('animate');
			// Show modal - yet zero width and height, thus invisible
			a.showModal();
			// Calculate transform origin
			var _wid = window.innerWidth,
			_hei = window.innerHeight,
			_mWid = a.offsetWidth,
			_mHei = a.offsetHeight,
				_x = event.clientX,
			_y = event.clientY,
			x,
			y;
			x = (_x - (_wid / 2)) + _mWid/2;
			y = (_y - (_hei / 2)) + _mHei/2;
			// Set transform origin to point where click happened
			a.style.transformOrigin = [
				x, 'px', ' ', y, 'px'
			].join('');
			// Set transform origin to scale(1,1,1)
			a.classList.toggle('visible');
			// And fly! :)
		} else {
			a.showModal();
		}

		return;
	},
	close: () => {
		let b = document.getElementsByTagName('dialog');
		for (let i = 0; i < b.length; i++) {
			// Here animations can be added
			// console.log(b[i]);
			// b[i].classList.remove('visible');
			// b[i].addEventListener('webkitAnimationEnd', function(){})
			b[i].close();
			document.body.removeChild(b[i]);
		}
		document.body.style.cssText = '';
		// [].forEach.call(document.getElementsByTagName('dialog'), function(f) {
		// 	f.cancel();
		// 	document.body.removeChild(f);
		// })
	}
}

// Load more
// Renders "Load more" button that requests next page on every pagination
// DONE Fix so it works on every pagination, not only product list
function loadMore() {
	let pagination = document.querySelector('main ul.pagination');
	if (!!pagination) {
		active_pages = pagination.querySelectorAll('li.active');
		var last = active_pages[active_pages.length - 1];
		next_page = last.nextElementSibling;
		// Disable "Load more" button and hide next and last buttons
		if (!next_page.nextElementSibling.classList.contains('page')) {
			d.getElementById('load-more-btn').disabled = true;
			document.querySelectorAll('[rel="next"], [rel="last"]').forEach(function (e) {
				e.style.cssText = 'display:none'
			});
		}
		if (!!next_page) {
			next_href = next_page.getElementsByTagName('a')[0];
			if (!!next_href && typeof(next_href.href) !== 'undefined' && (typeof(next_href.attributes.rel) == 'undefined' || next_href.attributes.rel.value !== ('next' || 'last'))) {
				ajax(next_href.href, null, function(resp){
					var div = document.createElement('div');
					div.innerHTML = resp;
					var blocks = div.querySelectorAll('main .miniature, div[role="comment"]');
					for (let index = 0; index < blocks.length; index++) {
						const block = blocks[index];
						document.querySelector('main .product.grid, main .article.grid, #js_reviews').insertAdjacentElement('beforeend', block);
						countdown(block)
					}
					var next_text = next_href.innerText;
					next_page.innerHTML = '<span>'+next_text+'</span>';
					next_page.classList.add('active');
				}, null, null, null, 'GET', 'text', true);
			}
		}
	}
}


let mwindow = {
	'create': function(type, content, reason) {

		// Remove previous dialog and backdrop so the dont stack
		let previous_dialog = d.querySelector('.modal_window.dialog');
		let backdrop = d.querySelector('.modal_backdrop');
		if (!!previous_dialog) {
			d.body.removeChild(d.querySelector('.modal_window.dialog'));
		}
		if (!!backdrop) {
			d.body.removeChild(d.querySelector('.modal_backdrop'));
		}

		// Create new modal window
		let [r, t] = [reason || '', type || 'dialog'];
		let modal = createElm({
			attrs: {'class': 'modal_window ' + t + ' '+ r, 'aria-modal':'true'},
			nest: {
				1: {type: 'button', attrs: {'id':'focus','class':'close','aria-label':js_lang.close}, events: {'click': function(e) {mwindow.close(e)}}},
				2: {attrs: {'class':'modal_content'},props:{'innerHTML': (typeof(content) == 'object' ? content.outerHTML : content)}},
			}
		});
		if (t == 'dialog') {
			let backdrop = createElm({attrs:{'class': 'modal_backdrop', 'aria-modal':'true'}, events: {'click': function(e) {mwindow.close(e)}}});
			d.body.insertAdjacentElement('beforeend', backdrop);
		}
		if (t == 'toast') {
			modal.style.cssText = 'position:fixed;top:20px;right:20px;z-index:100';
			modal.setAttribute('role', 'alertdialog');
			let alltoasts = document.getElementsByClassName('toast');
			// Find last toast and set top position underneath previous
			if (typeof(alltoasts[alltoasts.length - 1]) !== 'undefined') {
				modal.style.top = alltoasts[alltoasts.length - 1].offsetHeight + alltoasts[alltoasts.length - 1].offsetTop + 10 + 'px';
			}
		}
		let f = modal.querySelector('#focus');
		d.body.insertAdjacentElement('beforeend', modal);
		// DONE Focus on window
		if (!!f) {
			f.focus();
		}
	},
	'close': function(e) {
		if (e.target === undefined) {return}
		let m = e.target.closest('.modal_window') || d.querySelector('.modal_window');
		let b = d.querySelector('.modal_backdrop');
		if (!!m) {
			d.body.removeChild(d.querySelector('.modal_window'));
		}
		if (!!b) {
			d.body.removeChild(d.querySelector('.modal_backdrop'));
		}
		recalcPositions();
		function recalcPositions() {
			let alltoasts = document.getElementsByClassName('toast');
			if (typeof(alltoasts) !== 'undefined') {
				// DONE Change for []
				[].forEach.call(alltoasts, function(el, key) {
					if (key > 0) {
						el.style.top = alltoasts[key - 1].offsetHeight + alltoasts[key - 1].offsetTop + 10 + 'px';
					} else {
						el.style.top = '20px';
					}
				});
			}
		}
	}
}









// АЯКС отправка отзыва о товаре
function sendReview(t) {
	// Получаем форму, превращаем данные в строку вида:
	// &name='Вася'&review='ололо'
	let review_form = document.getElementById('form-review');
	let review = new URLSearchParams(new FormData(review_form)).toString();
	let review_url = 'index.php?route='+t.dataset.type+'/sendReview&entity_id=' + t.dataset.id;
	ajax(review_url, review,
		function(r) {
			if (r.error) {
				handleErrors(r, review_form);
				mwindow.create('toast', r.error, 'error');
			}
			if (r.success) {
				dialog.close();
				mwindow.create('toast', r.success, 'success');
				// Убираем форму
				// review_form.parentElement.removeChild(review_form);
			}
		},
		null,null,null,'POST','JSON',true
	);
}





// Find all elements by ID, class or queryselector and bind event listeners on them
// let elements_list = [
// 	// {name: '#cart-header-button',  	e:'click',	func:cart.showModal},
// 	// {name: '#cart-mobile-button',  	e:'click',	func:cart.showModal},
// 	{name: '#compare-total',        e:'click',	func:showCompareModal},
// 	{name: '#wishlist-total',       e:'click',	func:showWhishlistModal},
// 	// {name: '#search-input',         e:'input',	func:searchFunction},
// ]
// elements_list.forEach((a) => {
// 	let element = false;
// 	let q = a.name.slice(0, 1);
// 	if (q == '#') {
// 		element = document.getElementById(a.name.substring(1));
// 	} else if (q == '.') {
// 		element = document.getElementsByClassName(a.name.substring(1));
// 	} else {
// 		element = document.querySelectorAll(a.name);
// 	}

// 	if (!!element) {
// 		if (element.length === undefined) {
// 			element.addEventListener(a.e, function(ev) {
// 				a.func(ev)
// 			})
// 		} else {
// 			for (var i = 0; i < element.length; i++) {
// 				element[i].addEventListener(a.e, function(ev) {
// 					a.func(ev);
// 				})
// 			}
// 		}
// 	}
// })

// TODO Maybe make this as a separate class?
let timeout = null;
const searchFunction = (el) => {
	clearTimeout(timeout);
	let search_input = document.getElementById('search-input');
	let inner_search = document.getElementById('search-results');
	let search_input_group = document.getElementById('search');
	let data = search_input.value;
	let url = 'index.php?route=product/search/find';
	let response;
	timeout = setTimeout(function () {
		ajax(url, 'search='+data,
			function(r) {
				if (!!r && Object.keys(r).length !== 0) {
					response = r;
					let search_results = createElm(r);
					// Add special price countdown for search results
					countdown(search_results);
	
					inner_search.innerHTML = '';
					inner_search.appendChild(search_results);
					inner_search.classList.add('some-results');
				}
	
			},
			null,null,null,'POST','JSON',true
		);
    }, 400);
	if (data.length < 1) {
		inner_search.classList.remove('some-results');
	}
	// Add event listeners
	// If click or focus outside search results or search input
	// Then close search
	['click', 'focusin'].forEach(h => {
		document.addEventListener(h, function(e){
			if ((search_input_group.contains(e.target) == false) && (inner_search.contains(e.target) == false)) {
				inner_search.classList.remove('some-results');
			}
		});
	});

	search_input.addEventListener('focusin', function(){
		if (typeof(response) == 'object' && Object.keys(response).length !== 0) {
			inner_search.classList.add('some-results');
		}
	});
}



// Data driven event handler
function handle(evt) {
	const origin = evt.target.closest("[data-action]");
	return origin &&
		actions[evt.type] &&
		actions[evt.type][origin.dataset.action] &&
		actions[evt.type][origin.dataset.action](origin, evt) ||
		true;
}

// const firstElemHandler = (elem, evt) =>
// 	elem.textContent = `You ${evt.type === "click" ? "clicked" : "touched"}!`;
const reviewModal = (el, ev) => {
	fetch('index.php?route='+el.dataset.type+'/displayReviewModal&entity_id=' + el.dataset.id,
	{
		method: "POST",
	})
	.then(r => {return r.text();})
	.then(resp => {
		dialog.create(resp, ev);
	});
}

// Replace AJAX function
async function fetchFunction({url, m, h, b, callback, arg, ev}) {
	let [a, method, headers, body, e, f, event] = [url, m || 'POST', h || {"Content-type": "application/x-www-form-urlencoded; charset=UTF-8"}, b || '', callback || '', arg || '', ev || ''];
	return fetch(a, {method, headers, body})
	.then(r => {return r.text()})
	.then(resp => {
		if (e === '') {
			console.log('ololo');
			return resp;
		}
		if (arg !== '' || typeof(arg) !== 'undefined') {
			e[f](resp, event);
		} else {
			e(resp, event);
		}
	})
}
const cartRemove =  async (el, ev) => {
	let resp = await fetchFunction({url: 'index.php?route=checkout/cart/remove', b: 'key='+el.dataset.key });
	console.log(resp);
}

const compareModal = (el, ev) => {
	fetchFunction({url:'index.php?route=product/compare/showCompareModal', m: 'POST', callback: dialog, arg:'create',ev:ev})
}
const wishlistModal = (el, ev) => {
	fetchFunction({url:'index.php?route=account/wishlist/showWishlistModal', m: 'POST', callback: dialog, arg:'create', ev:ev})
}
const cartShowModal = (el, ev) => {
	fetchFunction({url:'index.php?route=common/cart/modal', callback: dialog, arg: 'create',ev:ev})
}
const contactsModal = (el, ev) => {
	fetchFunction({url:'index.php?route=information/contact/showContactsModal', callback: dialog, arg: 'create',ev:ev})
}



// const cartRemove =  async (el, ev) => {
// 	let resp = fetchFunction({url: 'index.php?route=checkout/cart/remove', b: 'key='+el.dataset.key }).then(r =>{return r.text()}).then(r=>{console.log(r);});
// }

// const cartRemove = (el, ev) => {
// 	fetch('index.php?route=checkout/cart/remove',
// 	{
// 		method:"POST", 
// 		headers: {"Content-type": "application/x-www-form-urlencoded; charset=UTF-8"}, 
// 		body: 'key='+el.dataset.key
// 	})
// 	.then(r => {return r.text()})
// 	.then(r => {
// 		let resp = JSON.parse(r);
// 		if ('success' in resp) {
// 			fetch('index.php?route=common/cart/modal')
// 			.then(r=>{return r.text()})
// 			.then(r=>{dialog.create(r)})
// 		}
// 	})
// }

const cartAdd = (el, ev) => {
	cart.add(el.dataset.productId);
}


// List of functions
// event: function
const actions = {
	click: {
		reviewModal,
		cartAdd,
		cartRemove,
		cartShowModal,
		compareModal,
		wishlistModal,
		contactsModal

	},
	input: {
		searchFunction,
	},
};
// Add event listener to document
Object.keys(actions).forEach(key => document.addEventListener(key, handle));






// Main menu
// Adds buttons in the main megamenu
// Next button shows child categories list
// Prev button closes child and shows parent list  
// DONE Add aria attributes to menu items
function mainMenu() {
	// DONE Set inert (unfocusable) for all elements except active
	let main_menu = document.getElementById('main-menu');
	let categories_with_children = main_menu.querySelectorAll('li[data-category-id]');
	[].forEach.call(categories_with_children, function(c) {
		let child_ul = main_menu.querySelector('ul[data-parent="'+ c.dataset.categoryId +'"]');
		child_ul.setAttribute('aria-expanded', false);
		let button_forward = createElm({
			type: 'button', 
			attrs: {'class':'menu-forward', 'role':'button','aria-label': js_lang.openlist, 'aria-haspopup': 'true' },
			events: {'click': () => {
				child_ul.classList.add('open');
				// Set opened child menu focusable
				child_ul.inert = false;
				// Set parent menu unfocusable
				c.parentElement.inert = true;
				// c.setAttribute('tabindex', '-1');
				child_ul.setAttribute('aria-expanded', true);
				
				setTimeout(() => {
					child_ul.firstChild.focus();
				}, 300);
			}}
		});
		c.appendChild(button_forward);
	});
	// Process all child menus
	[].forEach.call(main_menu.querySelectorAll('ul:not([data-parent="0"])'), p => {
		// Set child menus unfocusable on page load
		p.inert = true;
		let parent_menu = main_menu.querySelector('li[data-category-id="'+p.dataset.parent+'"]');
		let button_back = createElm({
			type: 'button', 
			attrs: {'class':'menu-back', 'role':'button', 'aria-label': parent_menu ? parent_menu.querySelector('a').innerText : js_lang.back_to, 'aria-haspopup': 'true'},
			props: {'innerText': parent_menu ? parent_menu.querySelector('a').innerText : js_lang.back_to},
			events: {'click': () => {
				p.classList.remove('open');
				p.inert = true;
				parent_menu.parentElement.inert = false;
				p.setAttribute('aria-expanded', false);
				setTimeout(() => {
					parent_menu.querySelector('a').focus();
				}, 400);

			}}
		});
		p.insertAdjacentElement('afterbegin', button_back);
	})
}

// DONE Add required querySelectors for the rest of accordions
// DONE Add ARIA attributes for accordions
// DONE Close all accordions
class Accordion {
	constructor(el) {
		this.el = el;
		// Add ARIA attributes
		// el.ariaExpanded = false;
		// el.ariaHasPopup = '';
		// This is needed for global listener to close all accordions on ESC key
		el.Accordion = this;

		this.animation = null;
		this.isClosing = false;
		this.isExpanding = false;


		// Check if current element is details or regular something else
		if (this.el.tagName == 'DETAILS') {
			this.external_content = false;
			this.toggler = el.querySelector('summary');
			this.content = this.toggler.nextElementSibling;
		} else {
			this.external_content = true;
			this.toggler = el;
			this.content = document.querySelector('[data-accordion="'+el.dataset.accordionTarget+'"]');
		}
		el.setAttribute('aria-haspopup', 'true');
		el.setAttribute('aria-controls', this.content.id);
		this.content.inert = true;
		
		this.toggler.addEventListener('click', (e) => this.onClick(e));
		document.onkeydown = function(evt) {
			evt = evt || window.event;
			var isEscape = false;
			if ("key" in evt) {
				isEscape = (evt.key === "Escape" || evt.key === "Esc");
			} else {
				isEscape = (evt.keyCode === 27);
			}
			if (isEscape) {
				Accordion.closeAllAccordions();
			}
		};
	}

	onClick(e) {
		e.preventDefault();
		this.content.style.overflow = 'hidden';
		// Join el and content
		// Where el represents details tag
		// And content - any external div, if accordion is not details
		if (this.isClosing || (!this.el.open && !this.content.open)) {
			this.open();

		} else if (this.isExpanding || (this.el.open || this.content.open)) {
			window.requestAnimationFrame(() => this.shrink());
		}
	}

	open() {
		if (this.external_content) {
			this.content.style.height = `${this.content.offsetHeight}px`;
			this.content.open = true;
		} else {
			this.el.style.height = `${this.el.offsetHeight}px`;
			this.el.open = true;
		}
		window.requestAnimationFrame(() => this.expand());
		this.content.inert = false;
	}

	expand() {
		let startHeight, endHeight;

		if (this.external_content) {
			this.content.ariaExpanded = true;
			startHeight = `${this.content.offsetHeight}px`; // Current content height (i.e. if button pressed on half-way closing)
			endHeight = `${this.content.firstElementChild.offsetHeight}px`; // Height of the inner content
		} else {
			this.el.ariaExpanded = true;
			startHeight = `${this.el.offsetHeight}px`;
			endHeight = `${this.toggler.offsetHeight + this.content.offsetHeight}px`;
		}
		let animation_style = {duration: 300, easing: 'cubic-bezier(0.87, 0, 0.13, 1)'};
		let animation_direction = {height: [startHeight, endHeight]};
		this.isExpanding = true;
		
		if (!this.content) {
			return;
		}

		if (this.animation) {
			this.animation.cancel();
		}

		// Different elements animation
		if (this.external_content) {
			this.animation = this.content.animate(animation_direction, animation_style);
		} else {
			this.animation = this.el.animate(animation_direction, animation_style);
		}

		this.animation.onfinish = () => this.onAnimationFinish(true);
		this.animation.oncancel = () => this.isExpanding = false;
	}

	shrink() {
		
		this.isClosing = true;
		// If content is external add aria-epanded to content, not to the element itself
		let startHeight, endHeight;
		if (this.external_content) {
			this.content.ariaExpanded = false;
			startHeight = `${this.content.offsetHeight}px`;
			endHeight = `0px`;
		} else {
			this.el.ariaExpanded = false;
			startHeight = `${this.el.offsetHeight}px`;
			endHeight = `${this.toggler.offsetHeight}px`;
		}
		let animation_style = {duration: 300, easing: 'cubic-bezier(0.87, 0, 0.13, 1)'};
		let animation_direction = {height: [startHeight, endHeight]};

		if (this.animation) {
			this.animation.cancel();
		}

		// Start a WAAPI animation
		if (this.external_content) {
			this.animation = this.content.animate(animation_direction, animation_style);
		} else {
			this.animation = this.el.animate(animation_direction, animation_style);
		}

		this.animation.onfinish = () => this.onAnimationFinish(false);
		this.animation.oncancel = () => this.isClosing = false;
		this.content.inert = true;
	}

	onAnimationFinish(open) {
		this.animation = null;
		this.isClosing = false;
		this.isExpanding = false;
		if (this.external_content) {
			this.content.open = open;
			if (open) {
				this.content.style.height = `${this.content.firstElementChild.offsetHeight}px`;
			} else {
				this.content.style.height = '0px';
			}
			this.content.style.overflow = '';
		} else {
			this.el.open = open;
			this.el.style.height = this.el.style.overflow = '';
		}
	}
	static closeAllAccordions() {
		const accordions = document.querySelectorAll('details, [data-accordion-target]');
		accordions.forEach((accordion) => {
			const instance = accordion.Accordion;
			if (instance && (instance.el.open || instance.content.open)) {
				instance.shrink();
			}
		});
	}
}
document.querySelectorAll('details, [data-accordion-target]').forEach((el) => {
	new Accordion(el);
});

// Simple and glitchless sticky header
// Adapts to logo dimensions
// No event listeners
function stickyHeader() {
	const h = document.getElementById('js_header'),
		oh = h.offsetHeight,
	    ih = document.getElementById('js_nav_main').offsetHeight;
	h.style.cssText = "position: sticky; z-index: 1; top:"+ -(oh - ih) + "px;";
}
// Focus on hash tag navigation element
function anchorNav() {
	let contents = document.querySelector('.contents');
	if (!!contents) {
		let links = contents.querySelectorAll('a');
		[].forEach.call(links, (a) => {
			a.addEventListener('click', (e)=>{
				const bodyRect = document.body.getBoundingClientRect(),
					elemRect = document.querySelector(a.hash).getBoundingClientRect(),
				    h = document.getElementById('js_nav_main').offsetHeight,
				    offset  = elemRect.top - bodyRect.top - h - 16; // minus 1rem
				e.preventDefault();
				window.location.hash = a.hash;
				window.scrollTo(0, offset);
				document.querySelector(a.hash).focus();
			})
		})
	}
}

// DONE Set icon on pare load
function setIcon(productCount) {
	const favicon = document.querySelector("link[rel~='icon']");
	if (productCount === '0' || (typeof(favicon) === 'undefined' && favicon == null)) {return}
	let faviconSize = 16;
	let canvas = document.createElement('canvas');
	canvas.width = faviconSize;
	canvas.height = faviconSize;
	let context = canvas.getContext('2d');
	let img = document.createElement('img');
	img.src = favicon.href;
	img.onload = () => {
		// Draw Original Favicon as Background
		context.drawImage(img, 0, 0, faviconSize, faviconSize);
		// Draw Notification Circle
		context.beginPath();
		context.arc( canvas.width - faviconSize / 3 , faviconSize / 3, faviconSize / 3, 0, 2*Math.PI);
		context.fillStyle = '#FF0000';
		context.fill();
		// Draw Notification Number
		context.font = '10px "helvetica", sans-serif';
		context.textAlign = "center";
		context.textBaseline = "middle";
		context.fillStyle = '#FFFFFF';
		context.fillText(productCount, canvas.width - faviconSize / 3, faviconSize / 3);
		// Replace favicon
		favicon.href = canvas.toDataURL('image/png');
	}
};

// Micro slider with smooth animations and native touch
function scrollslider() {
	const containers_class = 'js_scroll';
	[].forEach.call(document.getElementsByClassName(containers_class), c => {
		// Get timer from container dataset
		let time = c.dataset.time || 4000;
		let timer = setInterval(() => {
			scrollRight();
		}, time);
		// Observe slides visibility
		let observer = new IntersectionObserver(onIntersection, {
			root: c,      // Default is the viewport
			threshold: .9 // Percentage of target's visible area. Triggers "onIntersection". Not 1, because slide may be fractionally visible
		});

		// Set class to visible slide
		function onIntersection(slides, opts) {
			slides.forEach(entry => {
				entry.target.classList.toggle('visible', entry.isIntersecting)
			})
		}
		// Observe slides
		[].forEach.call(c.children, s => {
			observer.observe(s);
			
		});
		// Scroll left
		function scrollLeft() {
			// clearInterval(timer);
			// Select first visible slide if multilpe visibe
			let visible_slide = Array.from(c.querySelectorAll('.visible')).shift();
			if(visible_slide) {
				if (visible_slide.previousElementSibling) {
					// Calculate each time so dimensins change won't affect
					let scrollAmount = visible_slide.previousElementSibling.offsetWidth;
					// Scroll back if next slide present
					c.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
				} else {
					// Else scroll all the way to end
					let scrollAmount = c.scrollWidth;
					c.scrollBy({ left: scrollAmount, behavior: 'smooth' });
				}
			}
		}
		// Scroll right
		function scrollRight() {
			// clearInterval(timer);
			// Select last visible slide if multilpe visibe
			let visible_slide = Array.from(c.querySelectorAll('.visible')).pop();
			if(visible_slide) {
				if (visible_slide.nextElementSibling) {
					// Calculate each time so dimensins change won't affect
					let scrollAmount = visible_slide.nextElementSibling.offsetWidth;
					// Scroll forward if next slide present
					c.scrollBy({ left: scrollAmount, behavior: 'smooth' });
				} else {
					// Else scroll all the way to begin
					let scrollAmount = c.scrollWidth;
					c.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
				}
			}
		}

		// Add control buttons
		['left', 'right'].forEach(b => {
			const button = createElm({
				type: 'button',
				attrs: {'class': 'scroll_' + b, 'aria-label':js_lang[b], 'title':js_lang[b]},
				props: { innerHTML: '<i class="icon-chevron-' + b + '"></i>' },
				// Add some events
				events: {
					// Clicks
					'click': () => { b === 'left' ? scrollLeft() : scrollRight() },
					// Stop slider if controls are focused or hovered
					'mouseenter': () => { clearInterval(timer) },
					'focus': () => { clearInterval(timer) },
					// Else start slider again
					'mouseleave': () => { timer = setInterval(() => {scrollRight(); }, time); },
					// 'blur': () => { timer = setInterval(() => {scrollRight(); }, time); },
				}
			});
			c.insertAdjacentElement('beforebegin', button);
		});

		// If container hovered, touched or focused - stop animation
		['mouseenter', 'focus', 'touchstart'].forEach(e => {
			c.addEventListener(e, () => {
				clearInterval(timer);
			}, {passive: true});
		});
		[].forEach.call(c.children, (s) =>{
			s.addEventListener('focusin', () => {
				// Scroll into view
				clearInterval(timer);
				// s.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
			})
		});
		// If container is not hovered or focused - start animation
		// No 'touchend' listener, because if user interacts with block, it's expected that block stays in same condition that it was left
		['mouseleave', 'focusout'].forEach(f => {
			c.addEventListener(f, () => {
				// TODO: fix event listeners
				// Check if container does not have focus inside - like click on button or screen reader focus
				if (!c.matches(':focus-within')) {
					clearInterval(timer);
					timer = setInterval(() => {
						scrollRight();
					}, time);
				} else {
					clearInterval(timer);
				}
			});
		})
	})
}
// DONE add price animation when product has quantity discount
function animatePrice() {
	const js_prices = document.querySelector('[data-json-prices]').dataset.jsonPrices;
	price_list = JSON.parse(js_prices);
	const product = document.querySelector('[data-product-id]');
	const product_id = product.dataset.productId;
	const price_block = document.querySelector('#js_product_id_'+product_id+' .price-value');
	let base_price, start, end;

	// Start
	let options = product.querySelectorAll('input:checked, select');
	let qty = product.querySelector('input[name="quantity"]').value;
	start = parseFloat(price_block.innerText);
	base_price = price_list[product_id].base_price;
	end = base_price;
	// Quantity discounts
	if ('discounts' in price_list[product_id]) {
		for (discount_qty in price_list[product_id].discounts) {
			if (qty >= discount_qty) {
				end = parseFloat(price_list[product_id].discounts[discount_qty]);
			}
		}
	}
	// Option prices
	options.forEach(option => {
		let option_id = option.value;
		// Foreach option in product dataset
		for (let o in price_list[product_id].options) {
			if (option_id in price_list[product_id].options[o]) {
				end =  parseFloat(end) + parseFloat(price_list[product_id].options[o][option_id]);
			}
		}
	});
	animateValue(price_block, start, end, 300);
	// Animate quantity discount price list upon choosing different options with price values
	let discount_prices = document.querySelectorAll('[data-quantity]');
	discount_prices.forEach(dp => {
		let start, end;
		let price_value = dp.querySelector('.price-value');
		start = parseFloat(price_value.innerText);
		end = price_list[product_id].discounts[dp.dataset.quantity];
		options.forEach(option => {
			let option_id = option.value;
			for (let o in price_list[product_id].options) {
				if (option_id in price_list[product_id].options[o]) {
					end =  parseFloat(end) + parseFloat(price_list[product_id].options[o][option_id]);
				}
			}
		})
		animateValue(price_value, start, end, 300);
	})
	// Animate price change
	// const obj = document.getElementById("value");
	// animateValue(obj, 100, 4000, 300);
	function animateValue(obj, start, end, duration) {
		let startTimestamp = null;
		const step = (timestamp) => {
			  if (!startTimestamp) startTimestamp = timestamp;
			  const progress = Math.min((timestamp - startTimestamp) / duration, 1);
			  obj.innerHTML =  Math.round(((progress * (end - start) + start) + Number.EPSILON) * 100) / 100;
			  if (progress < 1) {
				window.requestAnimationFrame(step);
			  }
		};
		window.requestAnimationFrame(step);
	}
}

function countdown(element) {
	// DONE add data-discount-date-end here
	let products = element.querySelectorAll('[data-special-date-end], [data-discount-date-end]');
	for (let p = 0; p < products.length; p++) {
		let el = products[p];
		if (!!el.dataset && (!!el.dataset.specialDateEnd || !!el.dataset.discountDateEnd)) {
			let date_end = el.dataset.discountDateEnd || el.dataset.specialDateEnd;
			let finalDate = new Date(date_end + 'T00:00:00').getTime();
			let t = timer(finalDate);
			let div = createElm({attrs:{class: 'timer', 'aria-hidden':'true'}, nest:{1:{type:'span',props:{'innerText':js_lang.text_discount_ends_in}}}})

			for (const key in t) {
				let time = createElm({
					attrs: {
						class:'time ' + key,
						'role': 'timer',
						'aria-live': 'off'
					},
					nest: {
						1: {type:'span', attrs:{class:'span_'+key}, props:{'innerText': t[key]}},
						2: {type:'span', props:{'textContent': js_lang[key]}}
					}
				});
				div.appendChild(time);
			}

			el.insertAdjacentElement('beforeend', div);
			setInterval(function(){
				let tt = timer(finalDate);
				for (const key in tt) {
					div.getElementsByClassName('span_'+key)[0].innerText = tt[key];
				}
			},1000);
		}
	}
	function timer(finalDate) {
		let now = new Date().getTime();
		let diff = (finalDate - now);
		let t = {
			days: Math.floor(diff / (864*10e4)),
			hours: Math.floor(diff % (864*10e4) / (1000*60*60)),
			mins: Math.floor(diff % (1000*60*60)/ (1000*60)),
			secs: Math.floor(diff % (1000*60) / 1000)
		};
		return t
	}
}

// Handle errors on inputs
// Highlights feulty inputs, adds ARIA aria-errormessage to announce errors on screenreaders
// @result - the result of fetch request
// @form_with_errors - DOM element of form to be highlighted
function handleErrors(result, form_with_errors) {
	console.log(result);
	// Remove previous error messages
	removeElementsByClass('text-danger');
	[].forEach.call(document.querySelectorAll('.has-error'), function (el) {
		el.classList.remove('has-error');
	});
	// Remove ARIA attributes for previous errors
	[].forEach.call(document.querySelectorAll('[aria-invalid="true"]'), function (el) {
		el.removeAttribute('aria-invalid');
	});
	[].forEach.call(document.querySelectorAll('[aria-errormessage]'), function (el) {
		el.removeAttribute('aria-errormessage');
	});

	if ('error' in result) {
		let errors_object = result.error;
		for (i in errors_object) {
			// TODO Test this with multiple warnings
			if (i == 'warning') {
				mwindow.create('toast', errors_object['warning'], 'warning');
			} else {
				// console.log(i, errors_object[i]);
				let error_input = form_with_errors.querySelector('[name=' + i + ']');
				if (error_input) {
					// Set ARIA ittributes to invalid fields
					error_input.setAttribute('aria-invalid', true);
					error_input.setAttribute('aria-errormessage', 'error_label_' + i);
					let error_input_group = error_input.parentElement.classList.contains('form-group') ? error_input.parentElement : false;
					if (error_input_group) {
						error_input_group.classList.add('has-error');
					}
					error_input.insertAdjacentHTML('afterend', '<span role="alert" id="error_label_' + i + '" class="text-danger">' + errors_object[i] + '</span>')
				} else {
					console.log('input not found:', 'input name=[' + i + ']');
				}
			}
		}
	}
}

function removeElementsByClass(className) {
	const elements = document.getElementsByClassName(className);
	while(elements.length > 0) {
	  elements[0].parentNode.removeChild(elements[0]);
	}
}