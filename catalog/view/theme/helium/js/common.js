// Time for ninja coding! :)
// After everything is tested maybe replace standart methods with this functions prior minification
// to save 3-4 kb of payload as standart minifyers don't replace common methods
const d = document;
const w = window;
Node.prototype.on = Node.prototype.addEventListener;
// Node.prototype.css = Node.prototype.style.cssText;
var byId = (i) => document.getElementById(i);
var qs = (q) => document.querySelector(q);
var qsAll = (q) => document.querySelectorAll(q);

// // Scroll horizontal scrolling containers by mouse wheel
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
// let horizontal_scrolling_containers = d.getElementsByClassName('scroll-x');
// for (var i = 0; i < horizontal_scrolling_containers.length; i++) {
// 	horizontalScroll(horizontal_scrolling_containers[i]);
// }

// TODO :)
let filter_button = document.getElementById('button-filter');
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
	fetch('index.php?route=common/cart/fetchProductCount').then(r => {return r.text()}).then(r => {
		if (r !=='0') {setIcon(r)}
	})
	mobileMenu(); 				// Mobile menu buttons at the bottom of page
	mainMenu(); 				// Main menu - render buttons, aria attributes and titles
	countdown(document); 		// Countdown to the end date of discounts
	stickyHeader(); 			// Sticky header
	scrollslider(); 			// Sliders everywhere
	anchorNav(); 				// Focus on hastag navigation element
	hoverImage();				// Change product image
	imageGallery();				// modal image gallery
	toggleZonesAndPostcode();	// Fetch country zones and toggle postcodes, if applicable

	// Regular checkout
	if (!!document.getElementById('checkout')) {
		fetch('index.php?route=checkout/checkout/fetchCustomerIsLogged').then(r=>{return r.text()}).then(r=>{
			if (!!r) {
				// If user is logged in
				fetchPaymentAddress();
			} else {
				// If user is not logged in
				fetchLoginForm()
			}
		})
	}



	// Set product count on favicon on page load 
	

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



	const search_input = document.getElementById('search-input'),
	      inner_search = document.getElementById('search-results'),
	      search_input_group = document.getElementById('search');
	// Add event listeners for search input and result
	// If click or focus outside search results or search input - close search
	['click', 'focusin'].forEach(h => {
		document.addEventListener(h, (e) => {
			if ((search_input_group.contains(e.target) == false) && (inner_search.contains(e.target) == false)) {
				inner_search.classList.remove('some-results');
				inner_search.inert = true;
			}
		});
	});
	// Show previous search results if present
	search_input.addEventListener('focusin', () => {
		if (inner_search.childElementCount > 0) {
			inner_search.classList.add('some-results');
			inner_search.inert = false;
		}
	});
	// 	localStorage.setItem('display', 'list');
});




var voucher = {
	'add': function() {

	},
	'remove': function(key) {

		var url = 'index.php?route=checkout/cart/remove';
		var data = 'key=' + key;
		// ajax(url, data, function(r) {
		// 	ajax('index.php?route=common/cart/info', null, function(n) {
		// 		document.getElementById('cart').innerHTML = n;
		// 	},null,null,null,"GET","text",true);
		// });
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
				fetch(agree_link.href).then(r => {return r.text()}).then(r => {dialog.create(r, e)});
			}
		}
	}
	let main_menu_btn = document.getElementById('main-menu-trigger');
	if (main_menu_btn.contains(e.target)) {
		main_menu_btn.getElementsByClassName('menu-icon')[0].classList.toggle('open');
	}


	// Аякс загрузка отзывов на странице товара
	// DONE исправить, добавить нужные классы к HTML
	if (e.target.tagName === 'A' && e.target.href.indexOf('review&product') != '-1') {
		e.preventDefault();
		fetch(e.target.href).then(r => {return r.text()}).then(r => {document.getElementById('js_reviews').innerHTML = r});
	}

	// if (e.target.id == 'button-login') {
	// 	login();
	// }

});




// Маска номера телефона
// ID инпутов, куда может вводиться номер телефона
// TODO Объединить все в одну функцию
let masked_inputs = ['tel'];
['paste', 'input'].forEach(ev=>{
	document.addEventListener(ev, e =>{
		if (masked_inputs.indexOf(e.target.type) != -1) {
			e.target.value = phoneMask(e.target.value, '38');
		}
	})
})


// TODO
// The real time phone masking while typing
// Pass format as variable
// Example format: '+38(XXX)XXX-XX-XX'
// 1. All digits and plus sign at the beginning considered as country code
// 2. Count digits inside brackets: (XXX) = {3}, (XX) {2} and so on
// 3. Count digits after brackets: XXX-XXXX. First group is XXX = {3}
// 4. Count digits after brackets: XXX-XXXX. Second group is XXXX = {4}

function phoneMask (phone, format) {

  	return phone.replace(/\D/g, '')
	.replace(/^(38)/, '') // 1. Pass country code here as variable
	.replace(/^(\d)/, '($1') 
	.replace(/^(\(\d{3})(\d)/, '$1) $2') 	// 2. Pass number of digits in brackets here. Like this {number_of_digits_in_brackets} instead of {3}
	.replace(/(\d{3})(\d{1,5})/, '$1-$2') 	// 3. Pass first group here XXX = {3}
	.replace(/(-\d{4})\d+?$/, '$1'); 		// 4. Pass first group here XXX = {4}
}







// ///////////////////////////// //
// Works perfectly - don't touch //
// ///////////////////////////// //


// Mobile menu render
// Find all blcoks "div.mobile_menu" on page,
// create corresponding button in fixed bottom panel visible on mobile devices,
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
			// Create button for each block found
			let b = mb[k]; // Block itself
			// New button for mobile menu
			let btn = {
				type: 'button',
				attrs: {'class':'mobile_button button', 'aria-label':b.dataset.blockName},
				props:{'innerHTML':'<i class="'+ b.dataset.icon +'"></i><span>'+ b.dataset.blockName+ '</span>'},
				events:{
					'click': function(e) {
						// close other blocks when current is opened
						for (a of mb) {
							if (a !== b) {
								a.classList.remove('open');
							}
						}
						// Toggle .active class on button
						for (c of document.getElementsByClassName('mobile_button')) {
							if (c.contains(e.target)) {
								c.classList.toggle('active');
							} else {
								c.classList.remove('active');
							}
						}
						// Open current block
						b.classList.toggle('open');
					}
				},
			};
			// Put button in array according to dataset.order
			btns[mb[k].dataset.order] = btn;
		}
		// Catalog button
		// Shows main menu with categories etc.
		let catalog_btn  = {
			type: 'button',
			attrs: {'class':'mobile_button button', 'aria-label':js_lang.text_menu_button, 'data-action':'toggleMainMenu'},
			props: {
				'innerHTML':'<i class="icon-cart"></i><span>'+js_lang.text_menu_button+'</span>',
				'dataset' : {'accordionTarget':'main-menu'}
			},
		};
		// Cart button
		// Shows cart dialog window
		let cart_btn  = {
			type: 'button',
			attrs: {'class':'mobile_button button', 'aria-label':js_lang.text_cart_button, 'data-action':'cartShowModal'},
			props: {'innerHTML':'<i class="icon-cart"></i><span>'+js_lang.text_cart_button+'</span>'},
			nest: {
				0:{
					type: 'span',
					attrs: {'class': 'c_count product-count'},
					
				}
			}
		};
		// Insert most important buttons the last, so they are always at the right side of mobile menu 
		btns[9] = catalog_btn;
		btns[10] = cart_btn;
		let menu = {
			attrs: {'class': 'mobile_buttons scroll-x'},
			nest: btns
		};
		// Insert mobile menu to the page. createElm(menu) creates elements from object
		document.body.insertAdjacentElement('beforeend', createElm(menu));
	}
}


// Create multilevel DOM elements from Javascript Object
// Proudly present :)
function createElm({type, styles, attrs, props, events, nest}) {
	// Safely create function arguments
	let [eType, eStyle,eAttr, eProps, eHandlers] = [type || 'div', styles || {}, attrs || {}, props || {}, events || {}];
	// create initial element of type
	let el = document.createElement(eType);
	// Styles
	for (let k in eStyle) {el.style[k] = eStyle[k]}
	// Attributes
	for (let k in eAttr) {el.setAttribute(k, eAttr[k])}
	// Properties
	for (let k in eProps) {
		// Dataset
		if(k == 'dataset') {
			for (let d in eProps[k]) {
				el.dataset[d] = eProps[k][d];
			}
		}
		// Other properties
		el[k] = eProps[k]
	}
	// Events
	for (let k in eHandlers) {el.addEventListener(k,eHandlers[k])}
	// Nested elements
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

// Dialog component
// Creates accessible dialog
// Closed by ESC or by click outside
// Sets focus inside dialog
// Animates from click point
// usage:
// dialog.create(content, event)
// @content - html string or JSON encoded html string
// @event - optional, event to animate from click position 
let dialog = {
	create: (content, event) => {
		dialog.close();
		let a;
		a = createElm({
			type: 'dialog',
			attrs: {'class': '', 'role':'dialog'},
			events: {
				// 'close': (e) => {}, // Ability to add animation here
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
				props:{'innerHTML': (typeof(content) === 'object' ? content.outerHTML : content)}},
				// Close button
				{type: 'button', attrs: {'class':'close','aria-label':js_lang.close, 'title':js_lang.close}, events: {'click': (e) => {dialog.close(e)}}},
			]
		});
		// Append dialog to document
		document.body.insertAdjacentElement('beforeend', a);
		countdown(a);
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
			// Set transform origin to point where click happened
			a.style.transformOrigin = [
				(event.clientX - (window.innerWidth / 2)) + a.offsetWidth/2, 'px', ' ', (event.clientY - (window.innerHeight / 2)) + a.offsetHeight/2, 'px'
			].join('');
			// Set transform origin to scale(1,1,1)
			a.classList.toggle('visible');
			// And fly! :)
		} else {
			a.showModal();
		}
		
		return a;
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
// TODO Ommit links usage, move all logic to PHP
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
				fetch(next_href.href).then(r=>{return r.text()}).then(r=> {
					const div = document.createElement('div');
					div.innerHTML = r;
					const blocks = div.querySelectorAll('main .miniature, div[role="comment"]');
					for (let index = 0; index < blocks.length; index++) {
						const block = blocks[index];
						document.querySelector('main .product.grid, main .article.grid, #js_reviews').insertAdjacentElement('beforeend', block);
						countdown(block)
					}
					const next_text = next_href.innerText;
					next_page.innerHTML = '<span>'+next_text+'</span>';
					next_page.classList.add('active');
				})
			}
		}
	}
}

// Creates accessible toast notifications
// Notifications stack one under another
// Announced by screen readers correctly
let toast = {
	'create': function(content, reason) {
		// Create new modal window
		let r = reason || 'success';
		let t = createElm({
			attrs: {'class': 'toast ' + r},
			nest: {
				1: {type: 'button', attrs: {'class':'close','aria-label':js_lang.close}, events: {'click': function(e) {toast.close(e)}}},
				2: {attrs: {'class':'modal_content'},props:{'innerHTML': (typeof(content) == 'object' ? content.outerHTML : content)}},
			}
		});
		// top:90px so toast doesn't overflow sticky header 
		t.style.cssText = 'position:fixed;top:90px;right:20px;z-index:100';
		// Set proper aria attribute to toast
		t.setAttribute('role', 'alertdialog');
		// Find last toast and set top position underneath previous
		let alltoasts = document.getElementsByClassName('toast');
		if (typeof(alltoasts[alltoasts.length - 1]) !== 'undefined') {
			t.style.top = alltoasts[alltoasts.length - 1].offsetHeight + alltoasts[alltoasts.length - 1].offsetTop + 10 + 'px';
		}
		let di = document.getElementsByTagName("DIALOG");
		if (!di.length > 0) {
			document.body.insertAdjacentElement('beforeend', t);
		} else {
			di[0].insertAdjacentElement('beforeend', t);
		}
		// Focus on close button
		t.firstElementChild.focus();
	},
	'close': function(e) {
		if (e.target === undefined) {return}
		let m = e.target.closest('.toast') || d.querySelector('.toast');
		if (!!m) {
			m.remove();
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

// Live search
// Shows product drid with pictures, highlights search query in product description
// Adds countdown() if such products present
let timeout = null;
const searchFunction = () => {
	clearTimeout(timeout);
	let search_input = document.getElementById('search-input'),
		search_word = search_input.value,
	    inner_search = document.getElementById('search-results');

	// Hide search results if input is empty
	if (search_word.length < 1) {
		inner_search.classList.remove('some-results');
		inner_search.inert = true;
		return;
	}
	timeout = setTimeout(function () {
		const b = new FormData;
		b.append('search', search_word)
		fetch('index.php?route=product/search/find', {method: "POST", body:b}).then(r=>{return r.json()})
		.then(r=>{
			if (!!r && Object.keys(r).length !== 0) {
				const search_results = createElm(r);
				// Add countdown timer to product discounts
				countdown(search_results);
				// Clear previous results
				inner_search.innerHTML = '';
				// Add new results
				inner_search.appendChild(search_results);
				// Show block
				inner_search.classList.add('some-results');
				inner_search.inert = false;
			}
		});
    }, 400);
}


// Country zones fetch
// Toggle postcode input
// DONE move to separate function
function toggleZonesAndPostcode() {
	const counrty_select 	= document.getElementsByName('country_id'),
		  zone_select 		= document.getElementsByName('zone_id'),
		  postcode_input 	= document.getElementsByName('postcode');
	Array.from(counrty_select, c => {
		Array.from(zone_select, z => {
			// Get zones on initial page load
			getZones(c,z);
			// Change zones on country select change
			c.addEventListener('change', ()=>{
				getZones(c,z);
				Array.from(postcode_input, p =>{
					togglePostcode(c, p)
				})
			})
		})
		Array.from(postcode_input, p =>{
			togglePostcode(c, p)
		})
	});
}

// Show postode input if country requires postcode
function togglePostcode(country_select, postcode_input) {
	if (!!country_select && !!postcode_input) {
		if (country_select.options[country_select.selectedIndex].dataset.postcodeRequired == '1') {
			postcode_input.parentElement.style.cssText = '';
			postcode_input.inert = false;
		} else {
			postcode_input.parentElement.style.cssText = 'display:none';
			postcode_input.inert = true;
		}
	}
}

const quickCheckout = () =>{
	// Quick checkout form elements
	const form 			 = document.querySelector('#js_quick_ckeckout'),
		  country_select = document.querySelector('[name="shipping_address[country_id]"]'),
		  zone_select 	 = document.querySelector('[name="shipping_address[zone_id]"]'),
		  postcode_input = document.querySelector('[name="postcode"]');


	if (!!country_select) {
		// Show postcode if required
		togglePostcode(country_select, postcode_input);
		// Get zones of selected country and add them to zones select
		getZones(country_select, zone_select);
	}
	toggleAddressForm();
	
	if (!!form) {
		// Save fields initially so payment and shipping can be displayed
		saveCheckoutfields(form).then(r =>{return r.ok}).then(r=>{
			if (r) {
				fetchShippingPayment();
			}
		});
		const formElements = Array.from(form.elements);
		formElements.forEach((input) => {
			if (input.name.includes('country') || input.name.includes('zone')) {
				// Observe country and zone select element immediate changes
				// So delivery and payment are updated instantly
				input.addEventListener('change', () => {
					saveCheckoutfields(form);
					fetchShippingPayment();
					getZones(country_select, zone_select);
					togglePostcode(country_select, postcode_input);
				});
			} else if (input.name.includes('shipping_method') || input.name.includes('payment_method')) {
				input.addEventListener('change', () => {
					saveShippingMethod(input);
				})
			} 
			// Text fields and other not involved in delivery and payment are updatetd on focusout
			// This fires anyway after user interaction
			input.addEventListener('focusout', ()=>{
				// Only save, do not fetchShippingPayment() here
				// because otherways if user clicks on delivery or payment after any field filled
				// this will cause reload delivery and payment inputs and will look like a glitch
				// forsing user to click one more time
				saveCheckoutfields(form);
			});
		});
	}
}

// Hide address form if customer has registered addresses and selected one of them
// Show address form otherwise
const toggleAddressForm = () => {
	// Registered customer fields, who has saved address
	const existing_address = document.querySelectorAll('[name="address_id"]');
	
	// Show or hide address form if there are existing addresses checked 
	if (existing_address.length > 0) {
		existing_address.forEach(ea => {
			const new_address_form = document.getElementById(ea.dataset.collapseTarget);
			// If checked "I want to add new address" option - when customer has no registered address
			if (ea.value && ea.checked) {
				collapseElement(new_address_form)
			}
			ea.addEventListener('change', ()=> {
				if (ea.value) {
					collapseElement(new_address_form);
					// Save existing address
					const b = new FormData;
					b.append('address_id', ea.value);
					fetch('index.php?route=common/cart/fetchSaveExistingAddress', {method: "POST", body:b})

					// Fetch shipping and payment as customer address may be in different zones, coutries with different shipping and payment methods
					fetchShippingPayment(); 
					// fillFetchedAddressFields(r);
				} else {
					uncollapseElement(new_address_form)
				}
			})
		})
	}
}

// const fillFetchedAddressFields = (r) => {
// 	for (const field_name in r) {
// 		// if (field_name == 'address_1') {field_name == 'address'}
// 		let field = document.getElementsByName(field_name)[0];
// 		if (!!field) {
// 			console.log(r[field_name]);
// 			field.value = r[field_name];
// 		}
// 	}
// }

const collapseElement = (e) => {e.style.cssText = 'height:0px; overflow:hidden; transition: height .5s;';e.inert = true;}
const uncollapseElement = (e) => {e.style.cssText = `height: ${e.scrollHeight}px; transition: height .5s; overflow:hidden;`;e.inert = false;}


const getZones = (country_select, zone_select) => {
	if (!!country_select && !! zone_select) {
		let zone_block = country_select.parentElement.nextElementSibling;
		fetch('index.php?route=checkout/checkout/country&country_id='+country_select.value,{method: "POST"})
		.then((r) => {return r.text();})
		.then((r) => {
			// Remove old zones
			while (zone_select.firstElementChild) {
				zone_select.removeChild(zone_select.lastElementChild)
			}
			country_data = JSON.parse(r);
			// If country needs zones
			if ('zone' in country_data) {
				zone_block.classList.remove('hidden');
				// Create new zones
				createZoneSelect(zone_select, country_data.zone);
				zone_block.style.cssText = '';
			} else {
				// Else hide corresponding block
				zone_block.style.cssText = 'display:none';
			}
		});
		function createZoneSelect(zone_select, zones) {
			for (z in zones) {
				// Set selected zone
				if ('selected' in zones[z]) {
					zone_select.value = zones[z].zone_id;
				}
				let o = createElm({
					type: 'option',
					attrs: {'value':zones[z].zone_id},
					props: {'innerText':zones[z].name}
				})
				zone_select.insertAdjacentElement('afterbegin', o);
			}
		}
	} else {
		// console.log('Country select or zone select not present');
	}
}


// Correct time in type="time" and type="datetimelocal" inputs to hours
// So 13:23 will be corrected to 13:00,
// And 13:49 to 14:00
const correctTime = (el) => {
	let date = '', time = '', hours = '', mins = '';
	let value = el.value;
	if (value.split('T')[1]) {
		date = value.split('T')[0] + 'T';
		time = value.split('T')[1];
	} else {
		time = value;
	}
	hours = time.split(':')[0];
	mins = time.split(':')[1];
	if (parseInt(mins) > 30) {
		hours = parseInt(hours) + 1;
		if (hours < 10) {
			hours = `0${hours}`;
		}
	}
	mins = '00';
	el.value = `${date}` + `${hours}:${mins}`;
}


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
				}, 250);
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
				}, 250);

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

		// Service data
		this.animation   = null;
		this.isClosing   = false;
		this.isExpanding = false;

		// Check if current element is details or something else
		// If details - use <summary> as inner content
		// Else document.querySelector('[data-accordion="'+el.dataset.accordionTarget+'"]');
		if (this.el.tagName == 'DETAILS') {
			this.external_content = false;
			this.toggler = el.querySelector('summary');
			this.content = this.toggler.nextElementSibling;
		} else {
			this.external_content = true;
			this.toggler = el;
			this.content = document.querySelector('[data-accordion="'+el.dataset.accordionTarget+'"]');
		}

		// Set aria attributes
		el.setAttribute('aria-haspopup', 'true');
		el.setAttribute('aria-controls', this.content.id);
		// Set content inert - for divs so it cannot be focused
		this.content.inert = true;
		
		this.toggler.addEventListener('click', (e) => this.onClick(e));
		// Close all accordions on escape key
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

// Set product count notification on favicon
// Use CSS var in :root{} for backrgound
function setIcon(productCount) {
	const bg = getComputedStyle(document.body).getPropertyValue('--color-1') || '#ff0000';
	const favicon = document.querySelector("link[rel~='icon']");
	if (typeof(favicon) === 'undefined' || favicon == null || typeof(productCount) === 'undefined') {return}
	faviconSize = 16;
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
		context.arc(canvas.width - faviconSize / 3 , faviconSize / 3, faviconSize / 3, 0, 2*Math.PI);
		context.fillStyle = bg;
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
			if (qty >= parseInt(discount_qty)) {
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

// countdown to discout date end
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
		if (diff > 0) {
			return {
				days: Math.floor(diff / (864*10e4)),
				hours: Math.floor(diff % (864*10e4) / (1000*60*60)),
				mins: Math.floor(diff % (1000*60*60)/ (1000*60)),
				secs: Math.floor(diff % (1000*60) / 1000)
			};
		} else {
			return {days:0,hours:0,mins:0,secs:0}
		}
	}
}

// Handle errors on inputs
// Highlights faulty inputs, adds ARIA aria-errormessage to announce errors on screenreaders
// @result - the result of fetch request
// @form_with_errors - DOM element of form to be highlighted
function handleErrors(result, form_with_errors) {
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
			// DONE Test this with multiple warnings
			if (i == 'warning') {
				toast.create(errors_object['warning'], 'warning');
			} else {
				let error_inputs = form_with_errors.querySelectorAll('[name="' + i + '"]');
				if (error_inputs.length > 0) {
					error_inputs.forEach(error_input => {

						// Set ARIA ittributes to invalid fields
						error_input.setAttribute('aria-invalid', true);
						error_input.setAttribute('aria-errormessage', 'error_label_' + i);
						let error_input_group = error_input.closest('.form-group, .form-control');
						if (error_input_group.classList.contains('form-control')) {
							error_input_group = error_input_group.parentElement;
						}
						if (error_input_group) {
							error_input_group.classList.add('has-error');
							error_input_group.insertAdjacentHTML('beforeend','<span role="alert" id="error_label_' + i + '" class="text-danger">' + errors_object[i] + '</span>')
						} else {
							// If no input found, but error occured
							// Create toast notification with error
							toast.create(errors_object[i], 'success');
						}
					})
				} else {
					console.log('input not found:', 'input name=' + i);
				}
			}
		}
		// Return true if errors happened
		return true;
	}
	// Return false if no errors occured
	return false;
}

function removeElementsByClass(className) {
	const elements = document.getElementsByClassName(className);
	while(elements.length > 0) {
	  elements[0].parentNode.removeChild(elements[0]);
	}
}
const fetchShippingPayment  = () 	   => {ajax('common/cart/fetchDisplayShippingHtml');ajax('common/cart/fetchDisplayPaymentHtml')}
const validateQuickCheckout = (el, ev) => {ajax('common/cart/getConfirmOrder', {el, ev})}
const reviewModal 					= (el, ev) => {ajax(el.dataset.type+'/showReviewModal&entity_id=' + el.dataset.id, {ev})}
const sendReview 						= (el, ev) => {ajax(el.dataset.type+'/sendReview&entity_id=' + el.dataset.id, {el, ev})}
const wishlistModal 				= (el, ev) => {ajax('account/wishlist/showWishlistModal', {el,ev})}
const wishlistAdd 					= (el, ev) => {ajax('account/wishlist/add', {el,ev})}
const wishlistRemove 				= (el, ev) => {ajax('account/wishlist/remove', {el,ev})}
const compareModal 					= (el, ev) => {ajax('product/compare/showCompareModal', {el,ev})}
const compareAdd 						= (el, ev) => {ajax('product/compare/add', {el,ev})}
const compareRemove 				= (el, ev) => {ajax('product/compare/remove', {el,ev}).then(r=>{compareModal()})}
const contactsModal 				= (el, ev) => {ajax('information/contact/showContactsModal', {el,ev})}
const cartRemove 	    			= (el, ev) => {ajax('checkout/cart/remove', {el,ev}).then(r=>{cartShowModal();setIcon(r.cart_count)})}
const cartShowModal     		= (el, ev) => {ajax('common/cart/showCartModal', {el, ev}).then(r=>{quickCheckout()})}
const login 								= (el, ev) => {ajax('checkout/login/save', {el, ev})}
const guestOrRegister 			= (el, ev) => {ajax('checkout/'+ document.querySelector('input[name="account"]:checked').value, {el, ev}).then(r=>{toggleZonesAndPostcode()})}
// Save any form
const saveForm 				= (el, ev) => {
	ajax(el.dataset.url, {el, ev}).then(errors_present => {
		toggleZonesAndPostcode(); 
		if (!!errors_present && !!el.dataset.next) {
			ajax(el.dataset.next);
			toggleZonesAndPostcode();
		}
	})
}
// const getForm = (el, ev) => {

	// }
// Checkout
function fetchLoginForm()				{ajax('checkout/login/fetchLoginForm')}
function fetchGuestForm() 			{ajax('checkout/guest').then(r => {toggleZonesAndPostcode()})}
function fetchRegisterForm() 		{ajax('checkout/register').then(r => {toggleZonesAndPostcode()})}
function fetchPaymentAddress() 	{ajax('checkout/payment_address/fetchPaymentAddress').then(r => {toggleZonesAndPostcode()})}
function fetchShippingAddress() {ajax('checkout/shipping_address/fetchShippingAddress').then(r=>{toggleZonesAndPostcode()})}
function fetchShippingMethods() {ajax('checkout/shipping_method/fetchShippingMethods')}
function fetchPaymentMethods()	{ajax('checkout/payment_method/fetchPaymentMethods')}
function fetchConfirmOrder() 		{ajax('checkout/confirm/fetchConfirmOrder')}
function completeOrder(el) 			{ajax(el.dataset.confirm)}


// try {
// 	let response = await fetch('index.php?route=common/cart/displayCartModal', { method: "POST" });
// 	let modalContent = await response.text();
// 	let modalDiv = document.createElement('div');
// 	modalDiv.innerHTML = modalContent;
// 	let cart_dialog = dialog.create(modalDiv, ev);
// 	quickCheckout(cart_dialog);
// } catch (error) {
// 	console.error(error);
// }
const cartAdd =  async (el, ev) => {
	let body = new FormData;
	body.append('product_id', el.dataset.product_id);
	body.append('quantity', (!!document.getElementById('input-quantity')) ? document.getElementById('input-quantity').value : el.dataset.minimum_qty || 1);
	// Product options
	const options_inputs = Array.from(document.querySelectorAll('input[name^="option"]:checked, select[name^="option"]'));
	options_inputs.map(element => {
		if (element.tagName === 'SELECT') {
			body.append(element.name, element.options[element.selectedIndex].value);
		} else {
			body.append(element.name, element.value);
		}
	})
	ajax('checkout/cart/add', {body, el, ev}).then(r=>{
		if ('success' in r) {
			cartShowModal(el, ev);
			setIcon(r.cart_count);
		}
	})
}
function saveShippingMethod(input) {
	const [m, v] = [input.name, input.value];
	let url = 'index.php?route=checkout/'+m+'/save';
	let data = new FormData;
	data.append(m, v);
	fetch(url, {method:"post", body: data});
}

const saveCheckoutfields = async (form) => {
	try {
		let data = new FormData(form);
		let response = await fetch('index.php?route=common/cart/fetchSaveQuickCheckoutfields', { method: "POST", body: data });
		return response;
	} catch (error) {
		console.error(error);
	}
}


// TODO Add try-catch
// TODO replace const with function
const ajax = async (url, s) => {
	let method = "POST", body, headers, settings = s || {}, el, ev;

	if (!!settings.el && !!settings.el.dataset) {
		el = settings.el;
		url = el.dataset.url || el.action || url;
		if (Object.values(el.dataset).some(k => k !== 'action' || k !== 'form' || k !== 'url')) {
			body = new FormData;
			for (key in el.dataset) {
				if (key !=='form' || key !== 'action' || key !== 'url') {
					body.append(key, el.dataset[key])
				}
			}
		}
		if ('form' in el.dataset) {
			body = new FormData(document.getElementById(el.dataset.form))
		}
	}
	if ('body' in settings) {body = settings.body}
	if ('ev' in settings) {ev = settings.ev}

	return await fetch('index.php?route='+url, {method, body}).then(r=> {return r.json()}).then(r=> {
		/////////////////////////////
		// TODO Remove on production
		console.log({
			'url': 'index.php?route='+url,
			'body': !!body ? Object.fromEntries(body) : body,
			'response': r
		});
		/////////////////////////////
		/////////////////////////////


		if ('dialog' in r) {dialog.create(r.dialog, ev)}
		if ('toasts' in r) {for (c in r.toasts) {for (t in r.toasts[c]) {toast.create(r.toasts[c][t], c)}}}
		// if ('error' in r) {return handleErrors(r, document)}
		if (handleErrors(r, document)) {
			return false;
		}
		if ('redirect' in r) {window.location = r.redirect}
		if ('function' in r) {
			for (f in r.function) {
				let a = new Function(r.function[f]);
				a();
			}
		}
		if ('html' in r) {
			// Foreach action
			for (action in r.html) {
				// foreach selector in action
				for (selector in r.html[action]) {
					// Replace
					if (action === 'replace') {
						document.querySelectorAll(selector).forEach(
							element => {
								element.innerHTML = r.html[action][selector]
							}
						)
					}
					// Append
					if (action === 'append') {
						document.querySelectorAll(selector).forEach(
							element => {
								element.insertAdjacentHTML('afterend', r.html[action][selector])
							}
						)
					}
					// Prepend
					if (action === 'prepend') {
						document.querySelectorAll(selector).forEach(
							element => {
								element.insertAdjacentHTML('beforebegin', r.html[action][selector])
							}
						)
					}
					// Remove
					if (action == 'remove') {
						for (const element of document.querySelectorAll(selector)) {
							element.remove()
						}
					}
					// Check if parent of replaced contains '.collapse' class and fire Accordion.open()
					if (selector.indexOf('.collapse') !== -1) {
						let details = document.querySelector(selector).closest('details');
						if (details.Accordion) {
							Accordion.closeAllAccordions()
							// Open Accordion by class method
							details.Accordion.open()
						} else {
							Accordion.closeAllAccordions()
							// Open details by native browser api
							details.open;
						}
					}

				}
			}
		}
		return r
	})
}
// List of functions
// event: function
const actions = {
	click: {
		sendReview,
		reviewModal,
		cartAdd,
		cartRemove,
		cartShowModal,
		compareModal,
		compareAdd,
		compareRemove,
		wishlistModal,
		wishlistAdd,
		wishlistRemove,
		contactsModal,
		validateQuickCheckout,
		login,
		saveForm,
		guestOrRegister,
		completeOrder
	},
	input: {
		searchFunction,
		correctTime,
	},
	change: {
		saveShippingMethod
	}
};
// Add event listener to document
Object.keys(actions).forEach(key => document.addEventListener(key, handleEvents, {passive: true}));

// Data attribute driven event handler
// Returns element, event and fires function from action object
function handleEvents(evt) {
	const origin = evt.target.closest("[data-action]");
	return origin &&
		actions[evt.type] &&
		actions[evt.type][origin.dataset.action] &&
		actions[evt.type][origin.dataset.action](origin, evt) ||
		true;
}

// Change image on hover
const hoverImage = () => {
	[].forEach.call(document.querySelectorAll('.js_img_additional'), additional_img => {
		['click', 'mouseenter'].forEach(event => {
			additional_img.addEventListener(event, () => {
				[].forEach.call(document.querySelectorAll('.js_image_primary'), primary_img =>{
					primary_img.src = additional_img.dataset.largeImg;
				});
			}, {passive:true})
		})
	})
}
// Micro gallery
const imageGallery = () => {
	const src = [];
	const images = {};
	const primary_img = document.querySelector('.js_image_primary');
	const additional_img = document.querySelectorAll('.js_img_additional');
	if (!!primary_img) {
		src.push(primary_img.src);
		[].forEach.call(additional_img, img => {
			src.push(img.dataset.largeImg);
		});
		primary_img.addEventListener('click', (e)=> {
			e.preventDefault();
			src.forEach((s, k) =>{
				images[k] = {
					type: 'img',
					props: {'src': s}
				}
			})
			const gallery = createElm({
				type: 'div',
				attrs: {'class': 'scroll-x js_scroll'},
				nest: images
			})
			dialog.create(gallery, e);
			// TODO: Add scrollSlider()
		})
	}
}

