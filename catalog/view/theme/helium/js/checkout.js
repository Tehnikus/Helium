fetch('index.php?route=checkout/checkout/fetchCustomerIsLogged').then(r=>{return r.text()}).then(r=>{
    (!r) ? getForm('login') : getForm('payment_address');
})


document.addEventListener('click', async (e) => {
    // List all dynamically added buttons
    let button_account = document.getElementById('button-account');
    let button_guest = document.getElementById('button-guest');
    let button_register = document.getElementById('button-register');
    let button_shipping_method = document.getElementById('button-shipping-method');
    let button_payment_method = document.getElementById('button-payment-method');
    let button_payment_address = document.getElementById('button-payment-address');
    let button_shipping_address = document.getElementById('button-shipping-address');
    let button_confirm = document.getElementById('js_button_confirm');

    if (!!button_confirm) {
        if (button_confirm.contains(e.target)) {
            // console.log(button_confirm.dataset);

            let response = await fetch(button_confirm.dataset.confirm, { method: 'POST' });
            let result = await response.json();
            if ('redirect' in result) {
                window.location = result.redirect;
            } else {
                return result;
            }
        }
    }

    if (!!button_payment_address) {
        if (button_payment_address.contains(e.target)) {
            e.preventDefault()
            let response = await saveForm('payment_address');
            if (typeof (response) !== 'undefined' && 'shipping_required' in response.js_defs) {
                console.log('Shipping required');
                getForm('shipping_address');
            } else {
                getForm('shipping_method');
            }
        }
    }

    if (!!button_payment_method) {
        if (button_payment_method.contains(e.target)) {
            e.preventDefault();
            chainFunctions(saveForm, 'payment_method', getForm, 'confirm');
        }
    }
    // If create account and checkout
    if (!!button_account) {
        if (button_account.contains(e.target)) {
            // radio box that contains name of the form to be loaded
            var guest_or_register = document.querySelector('input[name="account"]:checked').value;
            getForm(guest_or_register);
        }
    }

    if (!!button_register) {
        if (button_register.contains(e.target)) {
            chainFunctions(saveForm, 'checkout_register', getForm, 'shipping_method');
        }
    }

    if (!!button_guest) {
        if (button_guest.contains(e.target)) {
            e.preventDefault();
            let response = await saveForm('guest_checkout');
            // console.log(response);
            // If shipping is required for this order
            if (typeof (response) !== 'undefined' && 'shipping_required' in response.js_defs) {
                console.log('Shipping required');
                var shipping_address_same_as_order = document.querySelector('input[name=\'shipping_address\']:checked');
                if (shipping_address_same_as_order) {
                    // Shipping address is the same as delivery
                    getForm('shipping_method');
                } else {
                    getForm('payment_address');
                }
            } else {
                console.log('No shipping needed');
                getForm('payment_method');
            }
        }
    }

    if (!!button_shipping_method) {
        if (button_shipping_method.contains(e.target)) {
            e.preventDefault();
            chainFunctions(saveForm, 'shipping_method', getForm, 'payment_method');
        }
    }
    if (!!button_shipping_address) {
        if (button_shipping_address.contains(e.target)) {
            e.preventDefault();
            chainFunctions(saveForm, 'shipping_address', getForm, 'shipping_method');
        }
    }
});

// Universal save method
// @save_form_id - form id to be saved
// Form itself must have correct action parameter
async function saveForm(saved_form_id) {
    let saved_form = document.getElementById(saved_form_id);
    let data = new FormData(saved_form);
    // for (var pair of data.entries()) {
    //   console.log(pair[0]+ ', ' + pair[1]); 
    // }
    let response = await fetch(saved_form.action, { method: 'POST', body: data });
    let result = await response.json();
    if ('error' in result) {
        handleErrors(result, saved_form);
    } else if ('redirect' in result) {
        window.location = result.redirect;
    } else {
        return result;
    }
}

// Universal request form method
// @method_name is the name of php file to be requested
// parent element containing collapse must have class matching ('collapse-'+method_name)
function getForm(method_name) {
    fetch('index.php?route=checkout/' + method_name, { method: "POST" })
    .then((r) => { return r.text(); })
    .then((body) => {
        let step = document.querySelector('.collapse-' + method_name)
        step.innerHTML = body;
        let details = step.closest('details');
        if (details.Accordion) {
            // Open Accordion by class method
            details.Accordion.open();
            console.log('true');
        } else {
            // Open details by native api
            details.open;
        }
        // TODO, use global getZones
        // TODO, add Event listener on country change to load zones dynamically 
        let country_select = step.querySelector('[name="country_id"]');
        let zone_select = step.querySelector('[name="zone_id"]');
        if (!!country_select && !!zone_select) {
            getZones2(country_select.value, zone_select.id);
        }
    });
}

// Chain xhr request functions
// Waits until first function returns no errors, then calls second function
// i.e. saveForm('guest_delivery') then getForm('shipping_method')
async function chainFunctions(function1, argument1, function2, argument2) {
    let result = await function1(argument1);
    if (result) {
        function2(argument2);
    }
}



// Create zones options
function getZones2(country_id, zone_input_id) {
    fetch('index.php?route=checkout/checkout/country&country_id=' + country_id, { method: "POST" })
    .then((r) => { return r.text(); })
    .then((body) => {
        let country = JSON.parse(body);
        if ('zone' in country) {
            let payment_zone_select = document.getElementById(zone_input_id);
            // remove child elements
            while (payment_zone_select.firstChild) {
                payment_zone_select.removeChild(payment_zone_select.lastChild);
            }
            // Add new select options
            for (const zone in country.zone) {
                if (Object.hasOwnProperty.call(country.zone, zone)) {
                    const element = country.zone[zone];
                    let option = document.createElement('option');
                    option.value = element.zone_id;
                    option.innerText = element.name;
                    payment_zone_select.appendChild(option);
                }
            }
        }
    });
}