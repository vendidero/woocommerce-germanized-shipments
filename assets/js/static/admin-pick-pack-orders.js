window.germanized = window.germanized || {};
window.germanized.admin = window.germanized.admin || {};

( function( $, admin ) {

    /**
     * Core
     */
    admin.pick_pack_orders = {
        params: {},
        dates: null,
        $wrapper: null,

        init: function () {
            var self = germanized.admin.pick_pack_orders;
            self.params = wc_gzd_admin_pick_pack_orders_params;

            if ( $( '.pick-pack-order-form-create' ).length > 0 ) {
                self.$wrapper = $( '.pick-pack-order-form-create' );
                self.createForm();
            }
        },

        createForm: function() {
            var self = germanized.admin.pick_pack_orders;

            self.dates = $( '.range_datepicker' ).datepicker({
                changeMonth: true,
                changeYear: true,
                defaultDate: '',
                dateFormat: 'yy-mm-dd',
                numberOfMonths: 1,
                minDate: '-20Y',
                maxDate: '+1D',
                showButtonPanel: true,
                showOn: 'focus',
                buttonImageOnly: true,
                onSelect: function() {
                    var option = $( this ).is( '.from' ) ? 'minDate' : 'maxDate',
                        date   = $( this ).datepicker( 'getDate' );

                    self.dates.not( this ).datepicker( 'option', option, date );
                },
            });

            self.$wrapper.find( 'input[name=order_type]' ).on( 'change', function() {
                 var currentType = $( this ).val();

                 $( '.show-hide-pick-pack-order' ).hide();
                 $( '.form-row-pick-pack-order-' + currentType ).show();
            });

            self.$wrapper.find( 'input[name=order_type]:checked' ).trigger( 'change' );

            self.$wrapper.on( 'submit', function() {
                self.ajaxSubmit( {
                    'security': self.params.create_pick_pack_order_nonce,
                    'action': 'create_pick_pack_order'
                } );

                return false;
            } );
        },

        getData: function( $wrapper, additionalData ) {
            var self = germanized.admin.pick_pack_orders,
                data = {};

            additionalData = additionalData || {};

            $.each( $wrapper.find( ':input[name]' ).serializeArray(), function( index, item ) {
                if ( item.name.indexOf( '[]' ) !== -1 ) {
                    item.name = item.name.replace( '[]', '' );
                    data[ item.name ] = $.makeArray( data[ item.name ] );
                    data[ item.name ].push( item.value );
                } else {
                    data[ item.name ] = item.value;
                }
            });

            $.extend( data, additionalData );

            return data;
        },

        block: function( $wrapper ) {
            $wrapper.block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        },

        unblock: function( $wrapper ) {
            $wrapper.unblock();
        },

        ajaxSubmit: function( params, cSuccess, cError ) {
            var self = germanized.admin.pick_pack_orders,
                url = self.params.ajax_url,
                $wrapper = self.$wrapper,
                $noticeWrapper = $wrapper.find( '.notice-wrapper' );

            $noticeWrapper.empty();

            self.block( $wrapper );

            cSuccess = cSuccess || self.onAjaxSuccess;
            cError   = cError || self.onAjaxError;

            if ( ! params.hasOwnProperty( 'security' ) ) {
                params['security'] = self.params.run_pick_pack_order_nonce;
            }

            if ( ! params['action'].startsWith( 'woocommerce_gzd_' ) ) {
                params['action'] = 'woocommerce_gzd_' + params['action'];
            }

            params = self.getData( $wrapper, params );

            $.ajax({
                type: "POST",
                url:  url,
                data: params,
                success: function( data ) {
                    if ( data.success ) {
                        if ( data.hasOwnProperty( 'fragments' ) ) {
                            $.each( data.fragments, function ( key, value ) {
                                $( key ).replaceWith( value );
                                $( key ).unblock();
                            } );
                        }

                        cSuccess.apply( $wrapper, [ data ] );
                        self.unblock( $wrapper );

                        if ( data.hasOwnProperty( 'redirect' ) ) {
                            window.location = data.redirect;
                        }
                    } else {
                        cError.apply( $wrapper, [ data ] );
                        self.unblock( $wrapper );

                       if( data.hasOwnProperty( 'messages' ) ) {
                            $.each( data.messages, function( i, message ) {
                                self.addNotice( $noticeWrapper, message, 'error' );
                            });
                        }
                    }
                },
                error: function( data ) {
                    cError.apply( $wrapper, [ data ] );

                    self.unblock( $wrapper );
                },
                dataType: 'json'
            });
        },

        onRemoveNotice: function() {
            $( this ).parents( '.notice' ).slideUp( 150, function() {
                $( this ).remove();
            });
        },

        addNotice: function( $noticeWrapper, message, noticeType ) {
            $noticeWrapper.find( '.notice-wrapper' ).append( '<div class="notice is-dismissible notice-' + noticeType +'"><p>' + message + '</p><button type="button" class="notice-dismiss"></button></div>' );
        },

        onAjaxError: function( data ) {

        },

        onAjaxSuccess: function( data ) {

        },
    };

    $( document ).ready( function() {
        germanized.admin.pick_pack_orders.init();
    });

})( jQuery, window.germanized.admin );