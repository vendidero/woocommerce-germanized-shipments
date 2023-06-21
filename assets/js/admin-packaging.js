window.germanized = window.germanized || {};
window.germanized.admin = window.germanized.admin || {};

( function( $, admin ) {
    admin.packaging = {
        params: {},

        init: function() {
            var self = germanized.admin.packaging;

            $( document )
                .on( 'change', 'input.gzd-override-toggle', self.onChangeOverride )
                .on( 'change', 'select.default-product', self.onChangeDefaultProduct );

            $( '.form-table select.default-product' ).trigger( 'change' );
        },

        onChangeDefaultProduct: function() {
            var $select = $( this ),
                $wrapper = $select.parents( '.form-table' ),
                val      = $select.val(),
                services = $select.data( 'services-' + val.toLowerCase() );

            if ( services ) {
                $wrapper.find( '.service-check' ).prop( 'disabled', true );
                $wrapper.find( '.service-check' ).parents( 'tr' ).hide();

                $.each( services.split(","), function( i, service ) {
                    var $service = $wrapper.find( '.service-check[data-service="' + service + '"]' );

                    if ( $service.length > 0 ) {
                        $service.parents( 'tr' ).show();
                        $service.prop( 'disabled', false );
                    }
                } );
            }
        },

        onChangeOverride: function() {
            var $checkbox = $( this ),
                $wrapper = $checkbox.parents( '.wc-gzd-packaging-zone-wrapper' );

            $wrapper.removeClass( 'zone-wrapper-has-override' );

            if ( $checkbox.is( ':checked' ) ) {
                $wrapper.addClass( 'zone-wrapper-has-override' );
            }
        },
    };

    $( document ).ready( function() {
        germanized.admin.packaging.init();
    });

})( jQuery, window.germanized.admin );