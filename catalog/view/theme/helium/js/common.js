const d = document;
const w = document.window;
const favicon = document.querySelector("link[rel~='icon']");
let filter_button = document.getElementById('button-filter');
let review_button = d.getElementById('write-review');


if (!!review_button) {
	review_button.addEventListener('click', () => {
		ajax(
			'index.php?route='+review_button.dataset.type+'/displayReviewModal',
			'entity_id='+review_button.dataset.id,
			function(c) {
			mwindow.create('dialog', c);
		}, null,null,null,"POST","text",true);
	});
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
				mwindow.create('toast', r.error, 'error');
			}
			if (r.success) {
				mwindow.create('toast', r.success, 'success');
				// Убираем форму
				review_form.parentElement.removeChild(review_form);
			}
		}, 
		null,null,null,'POST','JSON',true
	);
}

function quickorder(form) {

}

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




function showCompareModal() {
	ajax('index.php?route=product/compare/showCompareModal',null,function(c) {
		mwindow.create('dialog', c);
	}, null,null,null,"GET","text",true);
}
function showWhishlistModal() {
	ajax('index.php?route=account/wishlist/showWishlistModal',null,function(c) {
		console.log(c);
		mwindow.create('dialog', c);
	}, null,null,null,"GET","text",true);
}

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
	mobileMenu();
	mainMenu();
	countdown(d);
	
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
						dialog.create('<span class="h3">'+options+'</span>'+response);
					},null,null,null,'POST','text',true);
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
			// console.log(r);
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
			mwindow.create('dialog', c);
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
	// TODO Переделать на отображение окошка со списком желаний
	'add': function(product_id) {
		var url = 'index.php?route=account/wishlist/add';
		var data = 'product_id=' + product_id;
		ajax(url, data, function(r) {
			console.dir(r);
			mwindow.create('dialog', r['table']);

			document.querySelector('#wishlist-total').innerHTML = r['total'];
		},null,null,null,"POST","json",true);
	},
	'remove': function(product_id) {
		var url = 'index.php?route=account/wishlist/remove';
		var data = 'product_id=' + product_id;
		ajax(url, data, function(r) {
			console.dir(r);
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
			console.dir(r);
			mwindow.create('dialog', r['table']);
			document.querySelector('#compare-total').innerHTML = r['total'];
		},null,null,null,"POST","json",true);
	},
	'remove': function() {

	}
}



// Clicks handling
document.addEventListener('click', function(e){
	/* Agree to Terms */
	// Кто блядь завернул ссылку с классом в отдельный тег <b>? Рукожопы ебаные.
	agree_links = document.querySelectorAll('.agree');
	if (!!agree_links) {
		for (let i = 0; i < agree_links.length; i++) {
			const agree_link = agree_links[i];
			if (agree_link.contains(e.target) && agree_link.href !== null) {
				e.preventDefault();
				ajax(agree_link.href, null, function(r){
					mwindow.create('dialog', r, 'warning');
				},null,null,null,'GET','html',true);
			}
		}
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

	// Dropdowns
	// Очень-очень сильное колдунство
	[].forEach.call(document.getElementsByClassName('dropdown-toggle'), function(el) {
		if (el.contains(e.target) || el == e.target) {
			if (el.classList.contains('main-menu-trigger')) {
				accordion(el, false);
			} else {
				accordion(el, true);
			}
			animated_icon = el.querySelector('.nav-icon-1');
			if(!!animated_icon) {
				animated_icon.classList.toggle('open');
			}
		}
	})
});



function accordion(toggler = null, close_all = false) {
	let target = toggler.parentElement.querySelector('.dropdown-menu') || document.querySelector('[data-openedby="'+toggler.dataset.target+'"]') || toggler.href.split('#')[1];
	if (close_all == true || toggler == null) {
		[].forEach.call(document.getElementsByClassName('collapse-in'), function(e) {
			if(e !== target) {
				toggle_menu(e);
			}
		})
	}
	if (!!target) {
		toggle_menu(target);
	} else {
		console.log('Target menu not found in element', toggler);
	}
	function toggle_menu(el) {
		['collapse', 'collapse-in'].forEach(function(c) {
			el.classList.toggle(c)
		});
	}
}

// Main menu function
// Appends open and close buttons to list of categories 
function mainMenu() {
	let main_menu = document.getElementById('main-menu');
	let ul = main_menu.getElementsByClassName('top-level');

	[].forEach.call(ul, function(e) {
		renderMenuButtons(e);
	});

	function renderMenuButtons(ul) {
		let nested_uls = ul.querySelectorAll('li > ul');
		for (const nested_ul in nested_uls) {
			if (nested_uls.hasOwnProperty(nested_ul)) {
				const el = nested_uls[nested_ul];
				// render toggle buttons
				let inner_text = el.parentElement.querySelector('a').innerText;
				let open_button = createElm({
					type: 'button',
					attrs: {'class': 'menu-toggle', 'aria-label':js_lang.openlist}
				});
				let back_button = createElm({
					type: 'button',
					attrs: {'class': 'menu-back', 'aria-label':js_lang.back_to+' '+inner_text},
					props: {'innerText': inner_text}
				});


				// Toggle visibility and scrolling classes
				[open_button, back_button].forEach(function(button) {
					button.addEventListener('click', function() {
						// remove scrolling ability for other elements than visible one so they do not overlap
						for (const other_uls in nested_uls) {
							if (nested_uls.hasOwnProperty(other_uls)) {
								const element = nested_uls[other_uls];
								// element.scrollTop = 0;
								element.classList.remove('scrollable');
							}
						}
						// Then slide into view target ul and add scrolling ability to it
						['open', 'scrollable'].map(each_class => el.classList.toggle(each_class));
						// Toggle scrolling ability on back action on parent list element
						if (!el.classList.contains('open')) {
							console.log(el.parentElement.parentElement);
							el.parentElement.parentElement.classList.add('scrollable');
						}
					})
				});
				el.insertAdjacentElement('beforeBegin', open_button);
				el.insertAdjacentElement('afterBegin', back_button);
			}
		}
	}
}

// Load more
// DONE Fix so it works on every pagination, not only product list
function loadMore() {
	let pagination = document.querySelector('main ul.pagination');
	if (!!pagination) {
		active_pages = pagination.querySelectorAll('li.active');
		var last = active_pages[active_pages.length - 1];
		next_page = last.nextElementSibling;
		if (!next_page.nextElementSibling.classList.contains('page')) {
			d.getElementById('load-more-btn').disabled = true;
		}
		if (!!next_page) {
			next_href = next_page.getElementsByTagName('a')[0];
			if (!!next_href && typeof(next_href.href) !== 'undefined' && (typeof(next_href.attributes.rel) == 'undefined' || next_href.attributes.rel.value !== ('next' || 'last'))) {
				ajax(next_href.href, null, function(resp){
					var div = document.createElement('div');
					div.innerHTML = resp;
					var blocks = div.querySelectorAll('main .miniature, .js-product-review');
					for (let index = 0; index < blocks.length; index++) {
						const block = blocks[index];
						document.querySelector('main .product.grid, main .article.grid, #js-reviews').insertAdjacentElement('beforeend', block);
						countdown(block)
					}
					var next_text = next_href.innerText;
					next_page.innerHTML = '<span>'+next_text+'</span>';
					next_page.classList.add('active');
				}, null, null, null, 'GET', 'text', true);
			} else {
				document.querySelectorAll('[rel="next"], [rel="last"]').forEach(function (e) {
					e.classList.add('hidden');
				});
			}
		}
	}
}



// TODO Исправить так, чтобы количество товаров задавалось при загрузке страницы
function setIcon(productCount) {

	// var favicon = document.getElementById('favicon');
	// let favicon = document.querySelector("link[rel~='icon']");
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
	};
};








// Маска номера телефона
// ID инпутов, куда может вводиться номер телефона
// TODO Объединить все в одну функцию
let masked_inputs = ['InputPhone', 'one_click_input', 'user_telephone', 'input-payment-telephone'];
document.addEventListener('input', function(e) {
    if (masked_inputs.indexOf(e.target.id) != -1) {
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
// create corresponding button, 
// order buttons by data-order, 
// add icon from data-icon
// button name from data-block-name
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
		let menu = {
			attrs: {'class': 'mobile_buttons'},
			nest: btns
		};
		document.body.insertAdjacentElement('beforeend', createElm(menu));
	}
}


// Create multilevel DOM elements from Javascript Object
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
	create: (content) => {
		dialog.close();
		let a;
		a = createElm({
			type: 'dialog',
			attrs: {'class': ''},
			events: {
				'close': (e) => {console.log('closed', e)},
				'click': (e) => {
					if (e.target.contains(a)) {
						dialog.close()
					}
				}
			},
			nest: [
				{attrs: {'class':'modal_content'},
				props:{'innerHTML': (typeof(content) == 'object' ? content.outerHTML : content)}},
				{type: 'button', attrs: {'class':'close_modal','aria-label':js_lang.close}, events: {'click': (e) => {dialog.close(e)}}},
			]
		});
		document.body.insertAdjacentElement('beforeend', a);
		a.showModal();
		return;
	},
	close: () => {
		let b = document.getElementsByTagName('dialog');
		for (let i = 0; i < b.length; i++) {
			console.log(b[i]);
			b[i].close();
			document.body.removeChild(b[i]);
		}
		// [].forEach.call(document.getElementsByTagName('dialog'), function(f) {
		// 	f.cancel();
		// 	document.body.removeChild(f);
		// })
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
				1: {type: 'button', attrs: {'id':'focus','class':'close_modal','aria-label':js_lang.close}, events: {'click': function(e) {mwindow.close(e)}}},
				2: {attrs: {'class':'modal_content'},props:{'innerHTML': (typeof(content) == 'object' ? content.outerHTML : content)}},
			}
		});
		if (t == 'dialog') {
			let backdrop = createElm({attrs:{'class': 'modal_backdrop', 'aria-modal':'true'}, events: {'click': function(e) {mwindow.close(e)}}});
			d.body.insertAdjacentElement('beforeend', backdrop);
		}
		if (t == 'toast') {
			modal.style.cssText = 'position:fixed;top:20px;right:20px;z-index:100';
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
				Array.prototype.forEach.call(alltoasts, function(el, key) {
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


function countdown(element) {
	// TODO add data-discount-date-end here
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
						class:'time ' + key
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

function searchFunction () {
	search_input = document.getElementById('search-input');
	let data = search_input.value;
	let url = 'index.php?route=product/search/find';
	let inner_search = document.getElementById('search-results');
	let response;
	ajax(url, 'search='+data, 
		function(r) {
			if (!!r && Object.keys(r).length !== 0) {
				response = r;
				let search_results = createElm(r);
				countdown(search_results);
				
				inner_search.innerHTML = '';
				inner_search.appendChild(search_results);
				inner_search.classList.add('some-results');
			}

		},
		null,null,null,'POST','JSON',true
	);
	if (data.length < 1) {
		inner_search.classList.remove('some-results');
	}
	document.addEventListener('click', function(e){
		if ((e.target !== search_input) && (inner_search.contains(e.target) == false)) {
			inner_search.classList.remove('some-results');
		}
	});
	search_input.addEventListener('focusin', function(){
		if (typeof(response) == 'object' && Object.keys(response).length !== 0) {
			inner_search.classList.add('some-results');
		}
	});
}


// ----------------- Не нужно ------------------- //


	// let category_description = document.querySelector('.category-description');
	// if (!!category_description && category_description.scrollHeight > 200) {
	// 	let expand_btn = createElm({type:'button', attrs: {'class':'expand_button'}, props: {'innerHTML': js_lang.expand}});
	// 	expand_btn.addEventListener('click', function() {
	// 		let h = category_description.style.maxHeight;
	// 		if ( h == '') {
	// 			category_description.style.maxHeight = category_description.scrollHeight+'px';
	// 			expand_btn.innerText = js_lang.collapse;
	// 		} else {
	// 			if(h !== '200px') {
	// 				category_description.style.maxHeight = '200px';
	// 				expand_btn.innerText = js_lang.expand;
	// 			} else {
	// 				category_description.style.maxHeight = category_description.scrollHeight+'px';
	// 				expand_btn.innerText = js_lang.collapse;
	// 			}
	// 		}			
	// 	});
	// 	category_description.insertAdjacentElement('afterend', expand_btn);
	// }

	// ** FADE OUT FUNCTION **
// function fadeOut(el, callback) {
//     el.style.opacity = 1;
//     (function fade() {
//         if ((el.style.opacity -= .1) < 0) {
// 			el.style.display = "none";
// 			document.body.removeChild(el);
// 			callback;
//         } else {
// 			requestAnimationFrame(fade);
//         }
// 	})();
// };

// // ** FADE IN FUNCTION **
// function fadeIn(el, display) {
//     el.style.opacity = 0;
//     el.style.display = display || "block";
//     (function fade() {
//         var val = parseFloat(el.style.opacity);
//         if (!((val += .1) > 1)) {
//             el.style.opacity = val;
//             requestAnimationFrame(fade);
//         }
// 	})();
// };



// // var transitionEnd = whichTransitionEvent();
// // element.addEventListener(transitionEnd, theFunctionToInvoke, false);

// // function theFunctionToInvoke(){
// // // set margin of div here
// // }
// // Wait until transition ends
// function whichTransitionEvent(){
// 	var t;
// 	var el = document.createElement('fakeelement');
// 	var transitions = {
// 	  'transition':'transitionend',
// 	  'OTransition':'oTransitionEnd',
// 	  'MozTransition':'transitionend',
// 	  'WebkitTransition':'webkitTransitionEnd'
// 	}

// 	for(t in transitions){
// 		if( el.style[t] !== undefined ){
// 			return transitions[t];
// 		}
// 	}
// }


// Сериализация форм для отправки аяксом
// function serialize (data) {
// 	let obj = {};
// 	for (let [key, value] of data) {
// 		if (obj[key] !== undefined) {
// 			if (!Array.isArray(obj[key])) {
// 				obj[key] = [obj[key]];
// 			}
// 			obj[key].push(value);
// 		} else {
// 			obj[key] = value;
// 		}
// 	}
// 	return obj;
// }



function animatePrice(element) {
	console.log(element);
}

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
// TODO add price animation when product has quantity discount
let product_options = document.getElementsByClassName('product-option');
if (product_options.length > 0) {
	for (var i = 0; i < product_options.length; i++) {
		element = product_options[i];
		product_options[i].addEventListener('input', function(e) {
			let prices = e.target[e.target.selectedIndex] ? e.target[e.target.selectedIndex].dataset : e.target.dataset;
			if (prices.optionPrice) {
				let price_block = document.querySelector('.prices.h1 .price-value');
				let start = parseFloat(prices.basePrice);
				let end = start + parseFloat(prices.optionPrice);
				animateValue(price_block, start, end, 300);
			}
		})
	}
}







// Find all elements by ID, class or queryselector and bind event listeners on them
let elements_list = [
	{name: '#cart-header-button',  	e:'click',	func:cart.showModal},
	{name: '#compare-total',        e:'click',	func:showCompareModal},
	{name: '#wishlist-total',       e:'click',	func:showWhishlistModal},
	{name: '#search-input',         e:'input',	func:searchFunction},
]
elements_list.forEach((a) => {
	let element = false;
	let q = a.name.slice(0, 1);
	if (q == '#') {
		element = document.getElementById(a.name.substring(1));
	} else if (q == '.') {
		element = document.getElementsByClassName(a.name.substring(1));
	} else {
		element = document.querySelectorAll(a.name);
	}

	if (!!element) {
		if (element.length === undefined) {
			element.addEventListener(a.e, function(ev) {
				a.func(ev)
			})
		} else {
			for (var i = 0; i < element.length; i++) {
				element[i].addEventListener(a.e, function(ev) {
					a.func(ev);
				})
			}
		}
	}
})