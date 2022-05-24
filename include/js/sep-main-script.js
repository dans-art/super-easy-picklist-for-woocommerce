/**
 * Plugin Name: Super Easy Picklist for WooCommerce
 * File description: Various Javascript functions.
 * Author: Dan's Art
 * Author URI: http://dev.dans-art.ch
 */
let sep_scripts = {

    construct(){
        sep_scripts.add_event();
    },

    /**
     * Adds the eventlistener
     */
     add_event(){
        //On Enter press
        jQuery(document).on('keypress', function(event){
            if(event.which == 13){
                event.preventDefault();
                sep_scripts.on_enter();
            }
        });
    },

    /**
     * Runs if the Enter key has ben pressed
     * Depending on the position of the cursor / field in focus, it will load an order or adds a product
     */
    async on_enter(){
        let focus = jQuery(':focus');
        if(focus.length = 0){
            return false;
        }
        let name = focus[0].name;
        let value = focus[0].value;
        switch (name) {
            case 'sep_order_search':
                let data =  await this.load_order_ajax(value);
                console.log(data);
                break;
        
            default:
                break;
        }
    },

    /**
     * Loads the order details via ajax
     */
    async load_order_ajax(order_input){
        let args = new FormData();
        args.append('action', 'sep_order_functions');
        args.append('do', 'get_order');
        args.append('order', order_input);

        const result =  await jQuery.ajax({
            url: document.ajax_url,
            type: 'POST',
            data: args,
            cache: false,
            processData: false,
            contentType: false,
        });

        return result;
    },

};

jQuery(document).ready(function(){
    sep_scripts.construct();
});
