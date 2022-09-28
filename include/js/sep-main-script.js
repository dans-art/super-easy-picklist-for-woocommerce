/**
 * Plugin Name: Super Easy Picklist for WooCommerce
 * File description: Various Javascript functions.
 * Author: Dan's Art
 * Author URI: http://dev.dans-art.ch
 */
let sep_scripts = {

    loaded_orders: [],
    templates: [], //Storage for the templates.

    construct() {

        jQuery(document).ready(function () {

            //Event listeners
            sep_scripts.set_event_listeners();

            //Preload the templates
            sep_scripts.load_template('order_details', 'order-details.html', 'templates/backend/components/');
            sep_scripts.load_template('single_order', 'single-order.html', 'templates/backend/components/');

            const order_from_url = sep_scripts.get_order_from_url();
            if (!empty(order_from_url)) {
                sep_scripts.find_orders(order_from_url).then(() => {
                    sep_scripts.load_single_order(parseInt(order_from_url));
                });
            }
        });
    },
    /**
     * Registers the event listeners
     */
    set_event_listeners() {
        //Order search
        jQuery('#sep-order-search').on('click', (e) => {
            e.preventDefault();
            sep_scripts.find_orders();
        });
        //On order selection
        jQuery('#order-section-content').on('click', '.sep-order-item button', (e) => {
            let order_id = jQuery(e.currentTarget).data('order-id');
            sep_scripts.load_single_order(order_id);
        });
        //On barcode input
        jQuery('#order-section-content').on('keydown', '#picklist_code_input', (e) => {
            if (e.which !== 13) {
                return;
            }
            let barcode = jQuery(e.currentTarget).val();
            let line_id = jQuery(`[data-product-sku="${barcode}"]`).data('line-id');
            sep_scripts.picklist_modify_quantity(line_id, 'increase');
            jQuery(e.currentTarget).val('');
        });
        //Button actions
        //Select another order
        jQuery('#order-section-content').on('click', '#select-another-order', (e) => {
            jQuery('#order-section-content').html('');
            jQuery('#sep-order-search-input-container').show();
        });
        //Add Tracking code
        jQuery('#order-section-content').on('click', '#add-tracking-code-button', (e) => {
            sep_scripts.add_tracking_code();
        });
        //Increase or decrease the quantity
        jQuery('#order-section-content').on('click', '.modify-product-quantity', (e) => {
            let line_id = jQuery(e.currentTarget).data('line-id');
            let action = jQuery(e.currentTarget).data('action');
            sep_scripts.picklist_modify_quantity(line_id, action);
        });

        /** Settings */
        jQuery('#sep-save-shipping-provider').on('click', (e) => {
            e.preventDefault();
            sep_scripts.add_shipping_provider();
        });

    },

    /**
     * Loads a template to the class. Is also called by the get_template method.
     * @param {string} template_id - The ID of the template. Must be unique, otherwise one template can override the other
     * @param {string} file_name - The File name of the template with extension
     * @param {string} path - The path the template is saved in
     * @returns bool
     */
    async load_template(template_id, file_name, path) {
        await sep_scripts.get_template(template_id, file_name, path).then(function (content) {
            sep_scripts.templates[template_id] = content;
            return true;
        });
        return false;
    },

    /**
     * Loads a template file. If the template is already loaded, it will use the one from the get sep_scripts.templates object, if loaded
     * @param {string} template_id - The ID of the template. Must be unique, otherwise one template can override the other
     * @param {string} file_name - The File name of the template with extension
     * @param {string} path - The path the template is saved in
     * @returns bool
     */
    async get_template(template_id, file, subfolder = '') {
        if (!empty(sep_scripts.templates[template_id])) {
            return sep_scripts.templates[template_id];
        }
        return response = await jQuery.get(
            window.sep_plugin_dir_url + subfolder + file,
            function (data, textStatus, jqxhr) {
                sep_scripts.templates[template_id] = data;
            }
        );
    },
    /**
     * Finds the orders. Sends an ajax request to get the matching orders.
     * Displays the items in the #order-section-content container
     * 
     * @param {object} field The input field 
     */
    async find_orders(order_id) {
        let loading_order_text = __('Loading orders...', 'sep');
        let send_data = new FormData();
        send_data.append('action', 'sep_ajax_orders');
        if (empty(order_id)) {
            let value = jQuery('#sep-order-search-input').val();
            send_data.append('do', 'get_orders');
            send_data.append('search', value);
        }
        else {
            send_data.append('do', 'get_single_order');
            send_data.append('order_id', order_id);
            loading_order_text = __('Loading order %s', 'sep').replace('%s', order_id);
        }
        jQuery('#order-section-content').html(loading_order_text);
        const ajax_response = await this.load_ajax(send_data);
        const found_orders = this.get_ajax_success_answer(ajax_response);
        if (empty(found_orders)) {
            sep_scripts.display_error(sep_scripts.get_ajax_error_answer(ajax_response), '#order-section-errors');
            return;
        }
        //Add the current order to the object properties
        this.loaded_orders = found_orders;
        return await sep_scripts.get_template('order_details', 'order-details.html', 'templates/backend/components/').then(function (content) {
            if (typeof found_orders === 'object') {
                var output = '<h2>' + __('Found Orders', 'sep') + '</h2>';
                jQuery.each(found_orders, (index, order) => {
                    var date_obj = new Date(order.date_created.date);
                    output += sep_scripts.add_templage_args(content,
                        {
                            order_number: order.id,
                            name: order.billing.first_name + ' ' + order.billing.last_name,
                            amount: order.total + ' ' + order.currency,
                            date: date_obj.toDateString()
                        }
                    );
                });
            }
            return jQuery('#order-section-content').html(output);
        });
        return;

    },

    async load_single_order(order_id) {
        //Find the order in the loaded orders
        this.loaded_orders.forEach(async function (order_obj) {
            if (order_obj.id === order_id) {
                var order_items = await sep_scripts.display_products_items_box(order_obj.line_items); //Format the products
                var tracking_data = await sep_scripts.display_tracking_box(order_obj.tracking); //Format the tracking codes
                var status = await sep_scripts.display_status_box(order_obj); //Returns the status box

                await sep_scripts.get_template('single_order', 'single-order.html', 'templates/backend/components/').then(function (content) {
                    if (typeof order_obj === 'object') {

                        var output = sep_scripts.add_templage_args(content,
                            {
                                order_id: order_obj.id,
                                billing_address: sep_scripts.get_order_address(order_obj),
                                shipping_address: sep_scripts.get_order_address(order_obj, 'shipping'),
                                picklist_content: order_items,
                                tracking_content: tracking_data,
                                status_content: status,
                                total_items: Object.keys(order_obj.line_items).length,
                                //Titles
                                order_title: __('Order', 'sep'),
                                edit_order_text: __('Edit order', 'sep'),
                                select_another_order_text: __('Select another order', 'sep'),
                                billing_address_text: __('Billing address', 'sep'),
                                shipping_address_text: __('Shipping address', 'sep'),
                                picklist_title: __('Picklist', 'sep'),
                                tracking_title: __('Shipping tracking', 'sep'),
                                status_title: __('Order status', 'sep'),
                                picklist_input_placeholder: __('Scan a product to add it to the picklist', 'sep'),
                                items_packed_text: __('Items packed: ', 'sep'),
                                items_packed_of_text: __('out of', 'sep'),
                            }
                        );
                        //Fill the picklist
                        jQuery('#order-section-content #picklist').html();
                    }
                    //Hide the order search bar
                    jQuery('#sep-order-search-input-container').hide();
                    //Displays the content
                    jQuery('#order-section-content').html(output);
                });
            }
        })
    },

    /**
     * Renders the tracking data box
     * 
     * @param {object} tracking_data The tracking data as an object e.g. ([barcode:1231564513, provider: swisspost])
     * @returns string The Tracking info as html code
     */
    async display_tracking_box(tracking_data) {
        var template = await sep_scripts.get_template('tracking', 'tracking.html', 'templates/backend/components/');
        var list_item_template = await sep_scripts.get_template('tracking_item', 'tracking-item.html', 'templates/backend/components/');
        var output = '';
        if (typeof tracking_data !== 'object') {
            return __('Error while loading tracking data. Wrong format.', 'sep');
        }
        if (!empty(tracking_data)) {
            jQuery.each(tracking_data, (index, item) => {
                output = output + sep_scripts.add_templage_args(list_item_template, {
                    barcode: item.barcode,
                    provider: item.provider,
                });
            });
        }

        return sep_scripts.add_templage_args(template, {
            tracking_btn_text: __('Order tracking', 'sep'),
            current_tracking_codes: output,
            tracking_providers: sep_scripts.get_tracking_providers(),
        });
    },

    /**
     * The tracking providers set by the user in the settings
     * @returns The parcel delivery providers.
     */
    get_tracking_providers() {
        return '<option value="swiss_post">Die Post</option>';//Temp
    },

    async add_tracking_code() {
        var barcode = jQuery('#tracking-code-input').val();
        var provider = jQuery('#tracking-providers').val();
        var order_id = jQuery('#order-information').data('order-id');
        if (empty(barcode)) {
            sep_scripts.display_error(__('No barcode given', 'sep'), '#tracking-codes-errors');
            return false;
        }
        var list_item_template = await sep_scripts.get_template('tracking_item', 'tracking-item.html', 'templates/backend/components/');
        let item = sep_scripts.add_templage_args(list_item_template, {
            barcode: barcode,
            provider: provider,
        });
        jQuery('#current-tracking-codes-container').append(item);

        //Update the order via ajax
        let send_data = new FormData();
        send_data.append('action', 'sep_ajax_orders');
        send_data.append('do', 'add_tracking_code');
        send_data.append('order_id', order_id);
        send_data.append('barcode', barcode);
        send_data.append('service_provider', provider);
        const ajax_response = await this.load_ajax(send_data);
        const has_errors = this.get_ajax_error_answer(ajax_response);
        if (!empty(has_errors)) {
            sep_scripts.display_error(__('Updating the order failed: %s', 'sep').replace('%s', has_errors),
                '#tracking-codes-errors'
            );
            return;
        }
        return;
    },

    /**
     * Adds a new shipping provider 
     * 
     * @returns 
     */
    async add_shipping_provider() {
        let send_data = new FormData();
        send_data.append('action', 'sep_ajax_settings');

        send_data.append('do', 'add_shippting_provider');
        send_data.append('name', jQuery('#sep-add-shipping-provider-name').val());
        send_data.append('link', jQuery('#sep-add-shipping-provider-link').val());


        const ajax_response = await this.load_ajax(send_data);
        const added = this.get_ajax_success_answer(ajax_response);
        if (empty(added)) {
            jQuery('#sep-add-sp-errors').append(this.get_ajax_error_answer(ajax_response));
            return;
        }
        //Check for errors in the response
        if (typeof added !== 'object') {
            jQuery('#sep-add-sp-errors').append(__('Error: Invalid shipping provider data received','sep'));
            return;
        }
        //Add the item to the list
        jQuery('#sep-add-sp-errors').html(''); //Remove all errors
        debugger;

    },

    async display_products_items_box(products) {
        var output = '';
        if (typeof products !== 'object') {
            return __('Error while loading products. Wrong format.', 'sep');
        }
        var template = await sep_scripts.get_template('list_products', 'list-products.html', 'templates/backend/components/');
        jQuery.each(products, (index, item) => {
            output = output + sep_scripts.add_templage_args(template, {
                line_id: item.id,
                sku: item.sku,
                product_id: item.product_id,
                name: item.name,
                quantity: item.quantity,
                product_meta: sep_scripts.get_product_meta(item.meta_data),
            });
        });
        return output;
    },

    async display_status_box(order_obj) {
        var template = await sep_scripts.get_template('status_box', 'status_box.html', 'templates/backend/components/');
        return sep_scripts.add_templage_args(template, {
            order_id: order_obj.id,
        });
    },

    /**
     * Formats the product meta
     * @param {object} meta 
     * @returns {string} The Meta
     */
    get_product_meta(meta) {
        if (typeof meta !== 'object') {
            return __('Error while loading product meta. Wrong format.', 'sep');
        }
        var output = '';
        jQuery.each(meta, (index, item) => {
            output = output + `${item.key} : ${item.value}`;
        })
        return output;
    },

    /**
     * 
     * @param {object} order The order object from the ajax request 
     * @param {string} type The type to get. billing or shipping are supported 
     * @returns {string} The formated order address 
     */
    get_order_address(order, type = 'billing') {
        if (typeof order !== 'object' || empty(order[type])) {
            return __('Error while loading address. Wrong format or wrong type.', 'sep')
        }
        let address = [];
        if (!empty(order[type].first_name)) address.push(`${order[type].first_name} ${order[type].last_name}`);
        if (!empty(order[type].company)) address.push(order[type].company);
        if (!empty(order[type].address_1)) address.push(order[type].address_1);
        if (!empty(order[type].address_2)) address.push(order[type].address_2);
        if (!empty(order[type].city)) address.push(`${order[type].postcode} ${order[type].city}`);
        if (!empty(order[type].email)) address.push(order[type].email);
        if (!empty(order[type].phone)) address.push(order[type].phone);
        return address.join('<br/>');
    },

    /**
     * Returns the success message from the ajax response
     * @param {string} data The data from the ajax request as JSON string
     * @param {bool} only_first If only the first element should be returned
     * @returns void|array|string
     */
    get_ajax_success_answer(data, only_first = true) {
        return this.get_ajax_answer(data, 'success', only_first);
    },
    /**
     * Returns the error message from the ajax response
     * @param {string} data The data from the ajax request as JSON string
     * @param {bool} only_first If only the first element should be returned
     * @returns void|array|string
     */
    get_ajax_error_answer(data, only_first = true) {
        return this.get_ajax_answer(data, 'error', only_first);
    },
    /**
     * Returns the system error message from the ajax response
     * @param {string} data The data from the ajax request as JSON string
     * @param {bool} only_first If only the first element should be returned
     * @returns void|array|string
     */
    get_ajax_system_error_answer(data, only_first = true) {
        return this.get_ajax_answer(data, 'system_error', only_first);
    },

    /**
     * Adds an error message to given field
     * @param {string} message The error message
     * @param {string} field The field as css selector
     */
    display_error(message, field) {
        jQuery(field).append(message);
    },
    /**
     * Returns the message from the ajax response
     * @param {string} data The data from the ajax request as JSON string
     * @param {string} type The type to get. Can be success, error and system_error
     * @param {bool} only_first If only the first element should be returned
     * @returns void|array|string
     */
    get_ajax_answer(data, type, only_first = true) {
        try {
            var data_obj = JSON.parse(data);
        } catch (error) {
            console.error(error);
            return;
        }
        if (typeof data_obj[type] !== undefined) {
            return (only_first) ? data_obj[type][0] : data_obj[type];
        }
    },

    async load_ajax(send_data) {

        return await jQuery.ajax({
            url: window.ajaxurl,
            type: 'POST',
            cache: false,
            processData: false,
            contentType: false,
            data: send_data,
            success: function success(data) {
                return;
            },
            error: function error(data) {
                console.error(__('Failed to get a proper response from the ajax request.', 'sep'), data);
            }
        });
    },


    add_templage_args(template_string, args = {}) {
        jQuery.each(args, (index, value) => {
            template_string = template_string.replaceAll('{{' + index + '}}', value);
        });
        return template_string;
    },

    /**
     * Increase or decreases the amount to pick.
     * @param {int} line_id - The ID of the product line
     * @param {string} action The action to take. Only increase or decrease are supported 
     */
    picklist_modify_quantity(line_id, action = 'increase') {
        let current_quant = jQuery(`[data-line-id=${line_id}] .current-quantity`).text();
        current_quant = parseInt(current_quant);
        var new_quant = (action === 'increase') ? current_quant + 1 : current_quant - 1;
        //Only allow positive numbers
        if (new_quant >= 0) {
            jQuery(`[data-line-id=${line_id}] .current-quantity`).text(new_quant.toString());
        }
        sep_scripts.picklist_check_quantity(line_id);
        sep_scripts.update_picked_count();
        return;
    },

    /**
     * Checks if the quantities. If the amount matches, it will get the valid class, error otherwise. 
     * @param {*} line_id 
     */
    picklist_check_quantity(line_id) {
        let current_quant = parseInt(jQuery(`[data-line-id=${line_id}] .current-quantity`).text());
        let total_quant = parseInt(jQuery(`[data-line-id=${line_id}] .total-quantity`).text());
        //Reset the status
        jQuery(`[data-line-id=${line_id}] .quantity`).removeClass('sep-valid');
        jQuery(`[data-line-id=${line_id}] .quantity`).removeClass('sep-error');
        //Amount matches
        if (current_quant === total_quant) {
            jQuery(`[data-line-id=${line_id}] .quantity`).addClass('sep-valid');
        }
        //Amount is bigger than allowed 
        if (current_quant > total_quant) {
            jQuery(`[data-line-id=${line_id}] .quantity`).addClass('sep-error');
        }
        return;
    },

    /**
     * Updates the picked count by counting all the line items with the sep-valid class
     */
    update_picked_count() {
        var packed = jQuery('.sep-order-product-item .quantity.sep-valid').length;
        jQuery('#pick-info .items-packed').text(packed);
    },

    /**
     * Tries to get the order parameter from the url
     * 
     * @returns Empty string or the id of the order
     */
    get_order_from_url() {
        const query = window.location.search;
        const param = new URLSearchParams(query);
        return param.get('order');
    }


};

const { __, _x, _n, _nx } = wp.i18n; //Map the functions to the wp translation script 

//Helper functions
/**
 * Checks if the given value is empty or null
 * @param {mixed} value 
 * @returns 
 */
function empty(value) {
    if (value === null) {
        return true;
    }
    if (typeof value === 'undefined') {
        return true;
    }
    if (value.length === 0) {
        return true;
    }
    return false;
}
sep_scripts.construct();


