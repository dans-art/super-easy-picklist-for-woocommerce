/**
 * Plugin Name: Super Easy Picklist for WooCommerce
 * File description: Various Javascript functions.
 * Author: Dan's Art
 * Author URI: http://dev.dans-art.ch
 * @todo: Add function to delete the package form the order
 * @todo: use strict "use strict";
 * @todo: Split sep_scripts and import the modules tracking, order, template, helper.
 */


let sep_scripts = {

    loaded_orders: [],
    shipping_providers: [],
    templates: [], //Storage for the templates.
    tracking: null,
    order_status: [], //The available order statuses

    construct() {

        jQuery(document).ready(async function () {
            //Import the tracking handler
            await import('./modules/tracking.js').then((module) => {
                sep_scripts.tracking = new module.Tracking;
            });
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
            sep_scripts.update_url_to_single_order(order_id);
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
        //Redirect to edit order
        jQuery('#order-section-content').on('click', '#edit_order', (e) => {
            const order_id = sep_scripts.get_loaded_order_id();
            window.location = window.location.origin + window.location.pathname.replace('admin.php', 'post.php') + `?post=${order_id}&action=edit`;
        });
        //Select another order
        jQuery('#order-section-content').on('click', '#select-another-order', (e) => {
            jQuery('#order-section-content').html('');
            jQuery('#sep-order-search-input-container').show();
        });

        //Remove Tracking code
        jQuery('#order-section-content').on('click', '.sep-remove-sp', (e) => {
            const item_id = jQuery(e.currentTarget).parent().data('item-id');
            const order_id = sep_scripts.get_loaded_order_id();
            sep_scripts.remove_tracking_code(item_id, order_id);
        });


        /** Settings */
        jQuery('#sep-save-shipping-provider').on('click', (e) => {
            e.preventDefault();
            sep_scripts.add_shipping_provider();
        });
        jQuery('#sep_settings_page').on('click', '.sep-remove-sp', (e) => {
            e.preventDefault();
            let provider_id = jQuery(e.currentTarget).parent().data('item-id');
            sep_scripts.remove_shipping_provider(provider_id);
        });

        /** Meta Box */
        jQuery('#sep_order_meta_box').on('click', '#open-picklist-button', (e) => {
            e.preventDefault();
            const order_id = sep_scripts.get_order_from_url('post');
            window.location = window.location.origin + window.location.pathname.replace('post.php', 'admin.php') + `?page=super-easy-picklist&order=${order_id}`;
        });

    },

    /**
     * Registers the event listeners for single event actions
     */
    set_single_event_listeners() {
        //Increase or decrease the quantity
        jQuery('.modify-product-quantity').on('click', (e) => {
            let line_id = jQuery(e.currentTarget).data('line-id');
            let action = jQuery(e.currentTarget).data('action');
            sep_scripts.picklist_modify_quantity(line_id, action);
        });
        //Packs all form the current order
        jQuery('#pack-all').on('click', (e) => {
            sep_scripts.picklist_pack_all();
        });

        //Change of the shipping provider selector
        jQuery('#tracking-providers').on('change', (e) => {
            this.tracking.maybe_display_tracking_input();
        });

        //Change of the shipping provider selector
        jQuery('#finish-package').on('click', (e) => {
            const order_index = jQuery(e.currentTarget).data('order-index');
            this.pack_order(order_index);
        });
        //Updates the order status
        jQuery('#order-status').on('change', (e) => {
            this.update_status();
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
        if (empty(found_orders.orders)) {
            sep_scripts.display_error(sep_scripts.get_ajax_error_answer(ajax_response), '#order-section-errors');
            return;
        }
        //Add the current order to the object properties
        this.loaded_orders = found_orders.orders;
        this.shipping_providers = found_orders.shipping_providers;
        this.order_status = found_orders.order_status;
        return await sep_scripts.get_template('order_details', 'order-details.html', 'templates/backend/components/').then(function (content) {
            if (typeof found_orders.orders === 'object') {
                var output = '<h2>' + __('Found Orders', 'sep') + '</h2>';
                jQuery.each(found_orders.orders, (index, order) => {
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
    },

    async load_single_order(order_id) {
        //Find the order in the loaded orders
        for (const [index, order_obj] of Object.entries(this.loaded_orders)) {
            if (order_obj.id === order_id) {
                var order_items = await sep_scripts.display_products_items_box(order_obj.line_items, index); //Format the products
                var tracking_input = await sep_scripts.get_tracking_input(order_obj.tracking); //Format the tracking codes
                var packed_products = await sep_scripts.display_packed_products_box(order_obj.tracking, index); //Format the tracking codes
                var status = await sep_scripts.display_status_box(order_obj); //Returns the status box

                await sep_scripts.get_template('single_order', 'single-order.html', 'templates/backend/components/').then(function (content) {
                    if (typeof order_obj === 'object') {

                        var output = sep_scripts.add_templage_args(content,
                            {
                                order_id: order_obj.id,
                                billing_address: sep_scripts.get_order_address(order_obj),
                                shipping_address: sep_scripts.get_order_address(order_obj, 'shipping'),
                                picklist_content: order_items,
                                tracking_input: tracking_input,
                                status_content: status,
                                total_items: Object.keys(order_obj.line_items).length,
                                packed_content: packed_products,
                                order_index: index,
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
                                items_packed_of_text: __('out of', 'sep'),
                                items_packed_text: __('Items packed: ', 'sep'),
                                pack_all_text: __('Pack all', 'sep'),
                                finish_package_text: __('Finish package', 'sep'),
                                packed_title: __('Packed items', 'sep'),
                            }
                        );
                        //Fill the picklist
                        jQuery('#order-section-content #picklist').html();
                    }
                    //Hide the order search bar
                    jQuery('#sep-order-search-input-container').hide();
                    //Displays the content
                    jQuery('#order-section-content').html(output);
                    //Set the event listeners
                    sep_scripts.set_single_event_listeners();
                    //Hide the tracking inputs
                    sep_scripts.tracking.maybe_display_tracking_input();
                });
            }
        }
    },

    /**
     * Renders the tracking data box
     * 
     * @param {int} order_index The index of the order in the loaded_orders array
     * @param {object} tracking_data The tracking data as an object e.g. ([barcode:1231564513, provider: swisspost])
     * @returns string The Tracking info as html code
     */
    async display_packed_products_box(tracking_data, order_index) {
        const packed_container_template = await sep_scripts.get_template('packed_container', 'packed-container.html', 'templates/backend/components/');

        var output = '';
        if (typeof tracking_data !== 'object') {
            return __('Error while loading tracking data. Wrong format.', 'sep');
        }
        if (!empty(tracking_data)) {
            for (const item of tracking_data) {
                //Get the products as html
                const items_html = await sep_scripts.get_tracked_products(item.items_packed, order_index);
                //For each package
                output = output + sep_scripts.add_templage_args(packed_container_template, {
                    items: items_html,
                    tracking_code: item.sp_code,
                    service_provider: empty(item.sp_name) ? __('No shipping provider defined', 'sep') : item.sp_name,
                    tracking_link: sep_scripts.get_tracking_link(item.sp_id, item.sp_code),
                    date: !empty(item.date_packed) ? sep_scripts.convert_date(item.date_packed) : ''
                });
            }
        }
        return output;
    },

    /**
     * Links the items packed with the items form the order
     * 
     * @param {object} items_packed The items packed
     * @param {int} order_index The index of the order in the loaded_orders array
     * @returns {string} The products as html
     */
    async get_tracked_products(items_packed, order_index = 0) {
        const list_item_template = await sep_scripts.get_template('list_products', 'list-products.html', 'templates/backend/components/');

        if (empty(items_packed)) {
            return false;
        }
        var output = '';
        const order_items = sep_scripts.loaded_orders[order_index].line_items;
        jQuery.each(items_packed, (index, packed) => {
            output = output + sep_scripts.add_templage_args(list_item_template, {
                line_id: packed.id,
                sku: order_items[packed.id].sku,
                product_id: order_items[packed.id].product_id,
                name: order_items[packed.id].name,
                quantity: packed.quant,
                total_quantity: order_items[packed.id].quantity,
                product_meta: sep_scripts.get_product_meta(order_items[packed.id].meta_data),
            });
        });
        return output;
    },

    /**
     * Gets the current packed count.
     * If there are items in the #packed-container, it will take the quantity form there.
     * Otherwise the quantity will be taken form the ajax response
     * 
     * @param {int} line_id The line id of the order
     * @param {int} order_index The order index of the loaded order
     * @returns {int} The items packed
     */
    get_packed_count(line_id, order_index = 0) {
        var item_count = 0;
        var packed_items = jQuery(`#packed-container [data-line-id=${line_id}] .current-quantity`);
        if (!empty(packed_items)) {
            for (item of packed_items) {
                const quant = parseInt(jQuery(item).text());
                item_count += quant;
            }
            return item_count;
        }
        //No items packed so far
        const order_items = sep_scripts.loaded_orders[order_index].tracking;
        //Loop the packages
        for (item of order_items) {
            //Loop the package contents
            for (const [p_index, p_item] of Object.entries(item.items_packed)) {
                if (parseInt(line_id) === p_item.id) {
                    item_count += p_item.quant;
                }
            }
        }
        return item_count;
    },



    /**
     * Displays the tracking input fields
     * 
     * @param {object} tracking_data The Tracking data
     * @returns {string} 
     */
    async get_tracking_input(tracking_data) {
        var template = await sep_scripts.get_template('tracking_input', 'tracking-input.html', 'templates/backend/components/');
        if (typeof tracking_data !== 'object') {
            return __('Error while loading tracking data. Wrong format.', 'sep');
        }
        return sep_scripts.add_templage_args(template, {
            tracking_providers: sep_scripts.get_tracking_providers(),
        });
    },

    /**
     * The tracking providers set by the user in the settings
     * Make sure to load an order first with find_orders(), otherwise there will be no providers loaded
     * @returns The parcel delivery providers.
     */
    get_tracking_providers() {
        if (empty(sep_scripts.shipping_providers)) {
            console.error('No shipping providers loaded');
            return false;
        }
        var output = '<option value="none">' + __('None', 'sep') + '</option>';
        jQuery.each(sep_scripts.shipping_providers, (index, item) => {
            output = output + `<option value="${item.id}">${item.name}</option>`;
        });
        return output;
    },

    /**
     * Adds a tracking code to the current order
     * @returns void
     * @deprecated Old, remove
     */
    async add_tracking_code() {
        var barcode = jQuery('#tracking-code-input').val();
        var sp_id = jQuery('#tracking-providers').val();
        var provider = this.get_shipping_provider_by_id(sp_id);
        provider = (!empty(provider.name)) ? provider.name : provider;
        var order_id = this.get_loaded_order_id();
        if (empty(barcode)) {
            sep_scripts.display_error(__('No barcode given', 'sep'), '#tracking-codes-errors');
            return false;
        }
        var list_item_template = await sep_scripts.get_template('tracking_item', 'tracking-item.html', 'templates/backend/components/');
        let item = sep_scripts.add_templage_args(list_item_template, {
            link: barcode,
            name: provider,
            remove_text: '<i class="fa-solid fa-xmark"></i>',
        });
        jQuery('#current-tracking-codes-container').append(item);

        //Update the order via ajax
        let send_data = new FormData();
        send_data.append('action', 'sep_ajax_orders');
        send_data.append('do', 'add_tracking_code');
        send_data.append('order_id', order_id);
        send_data.append('barcode', barcode);
        send_data.append('service_provider', sp_id);
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
     * Removes the tracking code from the current order
     * 
     * @returns void
     */
    async remove_tracking_code(sp_id, order_id) {
        //Remove the line
        jQuery(`#current-tracking-codes-container[data-item-id="${sp_id}"]`);

        //Make ajax call to remove it from the order
        let send_data = new FormData();
        send_data.append('action', 'sep_ajax_orders');

        send_data.append('do', 'remove_tracking_code');
        send_data.append('order_id', order_id);
        send_data.append('sp_id', sp_id);

        const ajax_response = await this.load_ajax(send_data);
        const tracking = this.get_ajax_success_answer(ajax_response);
        if (empty(tracking)) {
            jQuery('#tracking-codes-errors').append(this.get_ajax_error_answer(ajax_response));
            return;
        }
    },


    /**
     * Adds a shipping provider to the database via ajax call
     * 
     * @returns void
     */
    async add_shipping_provider() {
        let send_data = new FormData();
        send_data.append('action', 'sep_ajax_settings');

        send_data.append('do', 'add_shipping_provider');
        send_data.append('name', jQuery('#sep-add-shipping-provider-name').val());
        send_data.append('link', jQuery('#sep-add-shipping-provider-link').val());


        const ajax_response = await this.load_ajax(send_data);
        const providers = this.get_ajax_success_answer(ajax_response);
        if (empty(providers)) {
            jQuery('#sep-add-sp-errors').append(this.get_ajax_error_answer(ajax_response));
            return;
        }
        //Check for errors in the response
        if (typeof providers !== 'object') {
            jQuery('#sep-add-sp-errors').append(__('Error: Invalid shipping provider data received', 'sep'));
            return;
        }
        //Add the item to the list
        jQuery('#sep-add-sp-errors').html(''); //Remove all errors

        //Refresh the providers
        const providers_html = await sep_scripts.list_all_shipping_providers(providers);
        jQuery('#sep-shipping-providers').html(providers_html);
        return;

    },

    /**
     * Removes a shipping provider from the database via ajax call
     * 
     * @param {string} provider_id The shipping provider ID
     * @returns void
     */
    async remove_shipping_provider(provider_id) {
        let send_data = new FormData();
        send_data.append('action', 'sep_ajax_settings');

        send_data.append('do', 'remove_shipping_provider');
        send_data.append('id', provider_id);

        //Remove the line
        jQuery('.sep-tracking-item[data-item-id="' + provider_id + '"]').remove();

        const ajax_response = await this.load_ajax(send_data);
        const removed = this.get_ajax_success_answer(ajax_response);
        if (empty(removed)) {
            jQuery('#sep-add-sp-errors').append(this.get_ajax_error_answer(ajax_response));
            return;
        }
        //Add the item to the list
        jQuery('#sep-add-sp-errors').html(''); //Remove all errors
        return;

    },


    /**
     * Searches in the shipping providers and the provider if found
     * 
     * @param {int} id The id of the shipping provider
     * @returns string|object String on error, object on success 
     */
    get_shipping_provider_by_id(id) {
        const providers = this.shipping_providers;
        id = parseInt(id);
        if (empty(providers)) {
            return __('Error; No providers loaded.', 'sep');
        }
        var output = false;
        jQuery.each(providers, (index, item) => {
            if (item.id === id) {
                output = item;
            }
        });
        return output;
    },

    /**
     * Converts the shipping provider code to an tracking url
     * 
     * @param {string} sp_id Shipping provider id
     * @param {string} sp_code The shipping provider code
     * @returns string|false false on error, string with the tracking url on success
     */
    get_tracking_link(sp_id, sp_code) {
        if (empty(sp_code)) {
            return '';
        }
        if (empty(sep_scripts.shipping_providers)) {
            console.error('No shipping providers loaded');
            return false;
        }
        var tracking_link = '';
        jQuery.each(sep_scripts.shipping_providers, (index, item) => {
            if (parseInt(sp_id) === item.id) {
                tracking_link = item.link;
                return;
            }
        });
        if (empty(tracking_link)) {
            console.error('No tracking link found');
            return false;
        }
        return tracking_link.replace('{tracking}', sp_code);
    },

    /**
     * Formats all the shipping providers
     * Loads the template tracking-item.html
     * 
     * @param {object} providers The shipping provider object
     * @returns {string} The providers formatted 
     */
    async list_all_shipping_providers(providers) {
        var output = '';
        if (typeof providers !== 'object') {
            return __('Error while loading providers. Wrong format.', 'sep');
        }
        var template = await sep_scripts.get_template('tracking_item', 'tracking-item.html', 'templates/backend/components/');
        jQuery.each(providers, (index, item) => {
            output = output + sep_scripts.add_templage_args(template, {
                id: item.id,
                link: item.link,
                name: item.name,
            });
        });
        return output;
    },

    /**
     * Loads the template list-products.html and formats the data
     * 
     * @param {object} products All the products as object
     * @param {object} order_index The index of the order.
     * @returns {string} Html string
     */
    async display_products_items_box(products, order_index = 0) {
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
                quantity: sep_scripts.get_packed_count(item.id, order_index),
                total_quantity: item.quantity,
                product_meta: sep_scripts.get_product_meta(item.meta_data),
            });
        });
        return output;
    },

    /**
     * Displays the order status box
     * 
     * @param {object} order_obj The order object of the current order
     * @returns string The HTML of the Order Dropdown box
     */
    async display_status_box(order_obj) {
        //Format the order statuses
        let status_options = '';
    
        for (const [key, item_name] of Object.entries(this.order_status)) {
            const selected = (order_obj.status === key ||'wc-' + order_obj.status === key) ? 'selected' : '';
            status_options += `<option value="${key}" ${selected}>${item_name}</option>`;
        }
        var template = await sep_scripts.get_template('status_box', 'status_box.html', 'templates/backend/components/');
        return sep_scripts.add_templage_args(template, {
            status_dropdown: status_options,
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
            if (empty(item.name)) {
                console.log('No Meta name found for:', item);
                return;
            }
            output = output + `${item.name} : ${item.value}` + '<br/>';
        })
        return output;
    },

    /**
     * 
     * @param {object} order The order object from the ajax request 
     * @param {string} type The type to get. billing or shipping are supported 
     * @returns {string} The formatted order address 
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
     * @param {bool} replace If true, the error will replace existing errors
     */
    display_error(message, field, replace = false) {
        if (replace) {
            jQuery(field).html(message);
            return;
        }
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
        let current_quant = jQuery(`#picklist [data-line-id=${line_id}] .current-quantity`).text();
        current_quant = parseInt(current_quant);
        var new_quant = (action === 'increase') ? current_quant + 1 : current_quant - 1;
        //Only allow positive numbers
        if (new_quant >= 0) {
            jQuery(`#picklist [data-line-id=${line_id}] .current-quantity`).text(new_quant.toString());
        }
        sep_scripts.picklist_check_quantity(line_id);
        sep_scripts.update_picked_count();

        //Set the item class to updated
        jQuery(`#picklist [data-line-id=${line_id}]`).addClass('sep-updated');
        return;
    },

    /**
     * Changes the current item total to the total items count
     */
    picklist_pack_all() {
        jQuery('#picklist .sep-order-product-item').each((index, item) => {
            const total = jQuery(item).find('.total-quantity').text();
            const current = jQuery(item).find('.current-quantity').text();
            const line_id = jQuery(item).data('line-id');
            //Don't change anything if the total amount is already picked
            if (total === current) {
                return;
            }
            jQuery(item).find('.current-quantity').text(total);
            this.picklist_check_quantity(line_id); //Adds the class of sep-vaild
            //Set the item class to updated
            jQuery(`#picklist [data-line-id=${line_id}]`).addClass('sep-updated');
        });
        this.update_picked_count();

    },

    /**
     * Checks if the quantities. If the amount matches, it will get the valid class, error otherwise. 
     * @param {*} line_id 
     */
    picklist_check_quantity(line_id) {
        let current_quant = parseInt(jQuery(`#picklist [data-line-id=${line_id}] .current-quantity`).text());
        let total_quant = parseInt(jQuery(`#picklist [data-line-id=${line_id}] .total-quantity`).text());
        //Reset the status
        jQuery(`#picklist [data-line-id=${line_id}] .quantity`).removeClass('sep-valid');
        jQuery(`#picklist [data-line-id=${line_id}] .quantity`).removeClass('sep-error');
        //Amount matches
        if (current_quant === total_quant) {
            jQuery(`#picklist [data-line-id=${line_id}] .quantity`).addClass('sep-valid');
        }
        //Amount is bigger than allowed 
        if (current_quant > total_quant) {
            jQuery(`#picklist [data-line-id=${line_id}] .quantity`).addClass('sep-error');
        }
        //Checks all the fields for errors
        this.picklist_check_errors();
        return;
    },
    /**
     * Checks all the items for quantity errors
     */
    picklist_check_errors() {
        var total_errors = 0;
        for (item of jQuery('#picklist .sep-order-product-item')) {
            const current = parseInt(jQuery(item).find('.current-quantity').text());
            const total = parseInt(jQuery(item).find('.total-quantity').text());
            if (current > total) {
                total_errors++;
            }
        }
        if (total_errors !== 0) {
            sep_scripts.display_error(__('You have picked on %s positions to many items', 'sep').replace('%s', total_errors), '#pick-error', true);
        }
        else {
            jQuery('#pick-error').html(''); //Reset the error field
        }
        return;
    },

    /**
     * Updates the picked count by counting all the line items with the sep-valid class
     */
    update_picked_count() {
        const packed = jQuery('#picklist .sep-order-product-item');
        var packed_count = 0;
        var total_packed = 0;
        for (item of packed) {
            packed_count += parseInt(jQuery(item).find('.current-quantity').text());
            total_packed += parseInt(jQuery(item).find('.total-quantity').text());
        }
        jQuery('#pick-info .items-packed').text(packed_count);
        jQuery('#pick-info .items-total').text(total_packed);
        return;
    },

    /**
     * Tries to get the order parameter from the url
     * 
     * @returns Empty string or the id of the order
     */
    get_order_from_url(param_name = 'order') {
        const query = window.location.search;
        const param = new URLSearchParams(query);
        return param.get(param_name);
    },

    /**
     * If a single order is loaded, this returns its id
     * 
     * @returns The loaded order ID
     */
    get_loaded_order_id(){
        return jQuery('#order-information').data('order-id');
    },

    /**
     * Updates the url with the current order id
     * @param {int} order_id The Id of the order
     */
    update_url_to_single_order(order_id) {
        let current_page = this.get_order_from_url('page');
        window.history.replaceState(null, null, `?page=${current_page}&order=${order_id}`)
    },

    /**
     * Adds the items packed to the order and assigns it to the barcode given.
     * 
     * @param {int} order_index Index of the order in the order_obj
     * 
     * @returns void
     */
    async pack_order(order_index = 0) {
        //Get all items
        const order_id = this.get_loaded_order_id();
        var items_send = {}; //The Items to send via ajax
        const sp_id = jQuery('#tracking-providers').val();
        const sp_code = jQuery('#tracking-code-input').val();
        const sp_name = sep_scripts.get_shipping_provider_by_id(sp_id).name;

        await jQuery('#picklist .sep-order-product-item').has('.sep-updated').each((index, item) => {
            const current_quant = parseInt(jQuery(item).find('.current-quantity').text());
            if (current_quant === 0) {
                return; //Skip if the quantity is 0
            }
            const total_quant = parseInt(jQuery(item).find('.total-quantity').text());
            const packed_class = (current_quant === total_quant) ? 'packed' : 'partly-packed';
            const line_id = jQuery(item).data('line-id');
            items_send[index] = {
                id: line_id,
                quant: sep_scripts.get_partly_packed_amount(current_quant, line_id),
            };
            //Give the packed class to avoid duple packing
            jQuery(item).addClass(packed_class);

        });

        //Validation
        if (sp_id !== 'none' && (empty(sp_id) || empty(sp_code))) {
            sep_scripts.display_error(__('No barcode or service provider given', 'sep'), '#tracking-codes-errors');
            return false;
        }
        if (empty(order_id)) {
            sep_scripts.display_error(__('No order ID found', 'sep'), '#tracking-codes-errors');
            return false;
        }
        if (empty(items_send)) {
            sep_scripts.display_error(__('No items found', 'sep'), '#tracking-codes-errors');
            return false;
        }

        //Update the order via ajax
        let send_data = new FormData();
        send_data.append('action', 'sep_ajax_orders');
        send_data.append('do', 'pack_order');
        send_data.append('order_id', order_id);
        send_data.append('sp_id', sp_id);
        send_data.append('sp_code', sp_code);
        send_data.append('items', JSON.stringify(items_send));
        const ajax_response = await this.load_ajax(send_data);
        const has_errors = this.get_ajax_error_answer(ajax_response);
        if (!empty(has_errors)) {
            sep_scripts.display_error(__('Updating the order failed: %s', 'sep').replace('%s', has_errors),
                '#tracking-codes-errors'
            );
            return false;
        }

        //Add the packed items to the order_obj
        tracking_obj = {
            items_packed: items_send,
            sp_code: sp_code,
            sp_id: sp_id,
            sp_name: sp_name,
            date_packed: 'now',
        }
        sep_scripts.loaded_orders[order_index].tracking.push(tracking_obj);

        //Reload the items in the packed section
        const reloaded_packed_products = await sep_scripts.display_packed_products_box(sep_scripts.loaded_orders[order_index].tracking, order_index);

        jQuery('#packed-container').html(reloaded_packed_products);
        return true;
    },

    /**
     * Updates the status of the order. Called when the status dropdown changes
     * @returns bool
     */
    async update_status(){
        const order_id = this.get_loaded_order_id();
        const new_status = jQuery('#order-status').val();

        let send_data = new FormData();
        send_data.append('action', 'sep_ajax_orders');
        send_data.append('do', 'update_status');
        send_data.append('order_id', order_id);
        send_data.append('status', new_status);

        const ajax_response = await this.load_ajax(send_data);
        const has_errors = this.get_ajax_error_answer(ajax_response);
        if (!empty(has_errors)) {
            sep_scripts.display_error(__('Updating the order failed: %s', 'sep').replace('%s', has_errors),
                '#status-errors'
            );
            return false;
        }
    },

    /**
     * Calculates the newly packed amount by subtract the picklist amount with packed amount  
     * @param {int} total_packed The total amount packed in the current packlist
     * @param {int} line_id The line ID
     * @returns {int}
     */
    get_partly_packed_amount(total_packed, line_id) {
        var packed_count = 0;
        let packed = jQuery(`#packed-container [data-line-id=${line_id}] .current-quantity`);
        for (item of packed) {
            packed_count += parseInt(jQuery(item).text());
        }
        return (packed_count > 0) ? total_packed - packed_count : total_packed;
    },

    /**
     * Converts a timestamp into a readable format
     * 
     * @param {int} timestamp Time in seconds
     */
    convert_date(timestamp) {
        const packed_date = new Date(timestamp * 1000); //Converting seconds to milliseconds
        //Get the format
        if (timestamp === 'now') {
            return __('Just now', 'sep');
        }
        return wp.date.date(window.sep_date_format, packed_date);
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


