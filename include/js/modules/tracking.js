/**
 * Module to handle the tracking actions
 * 
 */
class Tracking {
    /**
 * Checks if the tracking provider is set to none. If so, the inputs are hidden
 */
    maybe_display_tracking_input() {
        const provider = jQuery('#tracking-providers').val();
        if (provider === 'none') {
            jQuery('.tracking-input').hide();
        } else {
            jQuery('.tracking-input').show();
        }
    }
}
export { Tracking };