window.germanized = window.germanized || {};
window.germanized.admin = window.germanized.admin || {};

( function( $, admin ) {

    /**
     * Core
     */
    admin.shipments_table = {

        params: {},

        init: function() {
            var self = germanized.admin.shipments_table;

            self.initEnhanced();

            $( document.body ).on( 'init_tooltips', function() {
                self.initTipTip();
            });

            self.initTipTip();
        },

        initTipTip: function() {
            $( '.column-actions .wc-gzd-shipment-action-button' ).tipTip( {
                'fadeIn': 50,
                'fadeOut': 50,
                'delay': 200
            });
        },

        initEnhanced: function() {
            try {
                $( document.body )
                    .on( 'wc-enhanced-select-init', function() {

                        // Ajax customer search boxes
                        $( ':input.wc-gzd-order-search' ).filter( ':not(.enhanced)' ).each( function() {
                            var select2_args = {
                                allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
                                placeholder: $( this ).data( 'placeholder' ),
                                minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : '1',
                                escapeMarkup: function( m ) {
                                    return m;
                                },
                                ajax: {
                                    url:         wc_gzd_admin_shipments_table_params.ajax_url,
                                    dataType:    'json',
                                    delay:       1000,
                                    data:        function( params ) {
                                        return {
                                            term:     params.term,
                                            action:   'woocommerce_gzd_json_search_orders',
                                            security: wc_gzd_admin_shipments_table_params.search_orders_nonce,
                                            exclude:  $( this ).data( 'exclude' )
                                        };
                                    },
                                    processResults: function( data ) {
                                        var terms = [];
                                        if ( data ) {
                                            $.each( data, function( id, text ) {
                                                terms.push({
                                                    id: id,
                                                    text: text
                                                });
                                            });
                                        }
                                        return {
                                            results: terms
                                        };
                                    },
                                    cache: true
                                }
                            };

                            $( this ).selectWoo( select2_args ).addClass( 'enhanced' );
                        });

                    });

                $( 'html' ).on( 'click', function( event ) {
                    if ( this === event.target ) {
                        $( ':input.wc-gzd-order-search' ).filter( '.select2-hidden-accessible' ).selectWoo( 'close' );
                    }
                } );
            } catch( err ) {
                // If select2 failed (conflict?) log the error but don't stop other scripts breaking.
                window.console.log( err );
            }
        }
    };

    $( document ).ready( function() {
        germanized.admin.shipments_table.init();
    });

})( jQuery, window.germanized.admin );
