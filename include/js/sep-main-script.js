/**
 * Plugin Name: Super Easy Picklist for WooCommerce
 * File description: Various Javascript functions.
 * Author: Dan's Art
 * Author URI: http://dev.dans-art.ch
 */
let sep_scripts = {

    loaded_orders: [],
    templates : [], //Storage for the templates.

    construct() {

        jQuery(document).ready(function () {

            //Event listeners
            sep_scripts.set_event_listeners();
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
        jQuery('#order-section-content').on('click', '.sep-order-item', (e) => {
            let order_id = jQuery(e.currentTarget).data('order-id');
            sep_scripts.load_single_order(order_id);
        });
    },

    /**
     * 
     * @param {object} field The input field 
     */
    async find_orders() {
        jQuery('#order-section-content').html(__('Loading orders...', 'sep'));
        let value = jQuery('#sep-order-search-input').val();
        let send_data = new FormData();
        send_data.append('action', 'sep_ajax_orders');
        send_data.append('do', 'get_orders');
        send_data.append('search', value);
        const ajax_response = await this.load_ajax(send_data);
        const found_orders = this.get_ajax_success_answer(ajax_response);
        if (empty(found_orders)) {
            jQuery('#order-section-errors').append(__('Error while loading the Order', 'sep') + '<br/>' + this.get_ajax_error_answer(ajax_response));
            return;
        }
        //Add the current order to the object properties
        this.loaded_orders = found_orders;
        await sep_scripts.get_template("order-details.html", 'templates/backend/components/').then(function (content) {
            if (typeof found_orders === 'object') {
                var output = '<h2>' + __('Found Orders', 'sep') + '</h2>';
                jQuery.each(found_orders, (index, order) => {

                    output += sep_scripts.add_templage_args(content,
                        {
                            order_number: order.id,
                            name: order.billing.first_name + ' ' + order.billing.last_name,
                            amount: order.total + ' ' + order.currency,
                            date: order.date_created.date,
                        }
                    );
                });
            }
            jQuery('#order-section-content').html(output);
        });
        return;

    },

    async load_single_order(order_id) {
        //Find the order in the loaded orders
        this.loaded_orders.forEach(async function (order_obj) {
            if (order_obj.id === order_id) {
                await sep_scripts.get_template("single-order.html", 'templates/backend/components/').then(function (content) {
                    if (typeof order_obj === 'object') {
                        var output = sep_scripts.add_templage_args(content,
                            {
                                order_title: __('Order', 'sep'),
                                name: order_obj.billing.first_name + ' ' + order_obj.billing.last_name,
                            }
                        );
                    }
                    jQuery('#order-section-content').html(output);
                });
            }
        })
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
    get_ajax_error_answer(data, only_first = true) {
        return this.get_ajax_answer(data, 'system_error', only_first);
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

    /**
     * Loads a template file
     * @param {*} file 
     * @param {*} subfolder 
     */
    async get_template(file, subfolder = '') {

        return response = await jQuery.get(
            window.sep_plugin_dir_url + subfolder + file,
            function (data, textStatus, jqxhr) {
            }
        );
    },

    add_templage_args(template_string, args = {}) {
        jQuery.each(args, (index, value) => {
            template_string = template_string.replaceAll('{{' + index + '}}', value);
        });
        return template_string;
    },



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


