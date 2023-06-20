window.germanized = window.germanized || {};
window.germanized.admin = window.germanized.admin || {};

( function( $, admin ) {
    admin.shipment_settings = {
        params: {},

        init: function() {
            $( document ).on( 'click', 'a.woocommerce-gzd-input-toggle-trigger', this.onInputToggleClick );
        },

        onInputToggleClick: function() {
            var $toggle   = $( this ).find( 'span.woocommerce-gzd-input-toggle' ),
                $row      = $toggle.parents( 'fieldset' ),
                $checkbox = $row.find( 'input[type=checkbox]' ),
                $enabled  = $toggle.hasClass( 'woocommerce-input-toggle--enabled' );

            $toggle.removeClass( 'woocommerce-input-toggle--enabled' );
            $toggle.removeClass( 'woocommerce-input-toggle--disabled' );

            if ( $enabled ) {
                $checkbox.prop( 'checked', false );
                $toggle.addClass( 'woocommerce-input-toggle--disabled' );
            } else {
                $checkbox.prop( 'checked', true );
                $toggle.addClass( 'woocommerce-input-toggle--enabled' );
            }

            $checkbox.trigger( 'change' );

            return false;
        }
    };

    $( document ).ready( function() {
        germanized.admin.shipment_settings.init();
    });

})( jQuery, window.germanized.admin );