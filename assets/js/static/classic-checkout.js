
window.germanized = window.germanized || {};
window.germanized.shipments_classic_checkout = window.germanized.shipments_classic_checkout || {};

( function( $, germanized ) {

    /**
     * Core
     */
    germanized.shipments_classic_checkout = {

        params: {},
        pickupLocations: {},

        init: function () {
            var self  = germanized.shipments_classic_checkout;
            self.params  = wc_gzd_shipments_classic_checkout_params;

            var $pickupSelect = $( '#pickup_location' );

            if ( $pickupSelect.length > 0 ) {
                self.pickupLocations = $pickupSelect.length > 0 ? $pickupSelect.data( 'locations' ) : {};

                $( document.body ).on( 'updated_checkout', self.afterRefreshCheckout );
                $( document ).on( 'change', '#pickup_location_field #pickup_location', self.onSelectPickupLocation );

                self.afterRefreshCheckout();
            }
        },

        onSelectPickupLocation: function() {
            var self = germanized.shipments_classic_checkout,
                $pickupSelect  = self.getPickupLocationSelect(),
                $customerNumberField = $( '#pickup_location_customer_number_field' );

            if ( "-1" === $pickupSelect.val() ) {
                $customerNumberField.addClass( 'hidden' );
                $customerNumberField.hide();
            }

            $( document.body ).trigger( 'update_checkout' );
        },

        getPickupLocationSelect: function() {
            return $( '#pickup_location' );
        },

        afterRefreshCheckout: function( e, ajaxData ) {
            var self = germanized.shipments_classic_checkout,
                $pickupSelect = self.getPickupLocationSelect(),
                $pickupSelectField = $pickupSelect.parents( '#pickup_location_field' ),
                $customerNumberField = $( '#pickup_location_customer_number_field' ),
                current = $pickupSelect.val();

            ajaxData = ( typeof ajaxData === 'undefined' ) ? {
                'fragments': {
                    '.gzd-shipments-pickup-locations': JSON.stringify( self.pickupLocations ),
                }
            } : ajaxData;

            if ( ajaxData.hasOwnProperty( 'fragments' ) && ajaxData.fragments.hasOwnProperty( '.gzd-shipments-pickup-locations' ) ) {
                self.pickupLocations = JSON.parse( ajaxData.fragments['.gzd-shipments-pickup-locations'] );
            } else {
                self.pickupLocations = {};
            }

            $( '#pickup_location' ).attr('data-locations', self.pickupLocations );

            if ( Object.keys( self.pickupLocations ).length ) {
                $pickupSelectField.show();
                $pickupSelectField.removeClass( 'hidden' );

                $pickupSelect.find( 'option:gt(0)' ).remove();

                $.each( self.pickupLocations, function( code, pickupLocation ) {
                    $pickupSelect.append( $( "<option></option>" ).attr("value", code ).text( pickupLocation.label ) );
                });

                if ( self.pickupLocations.hasOwnProperty( current ) ) {
                    var currentLocation = self.pickupLocations[ current ];

                    if ( currentLocation ) {
                        $pickupSelect.find( 'option[value="' + currentLocation.code + '"' )[0].selected = true;

                        self.replaceShippingAddress( currentLocation.address_replacements );

                        if ( currentLocation.supports_customer_number ) {
                            if ( currentLocation.customer_number_is_mandatory ) {
                                if ( ! $customerNumberField.find( 'label abbr' ).length || ( ! $customerNumberField.find( 'label abbr' ).hasClass( 'required' ) ) ) {
                                    $customerNumberField.find( 'label' ).append( ' <abbr class="required">*</abbr>' );
                                }

                                $customerNumberField.find( 'label span.optional' ).hide();
                                $customerNumberField.addClass( 'validate-required' );
                            } else {
                                $customerNumberField.find( 'label abbr.required' ).remove();
                                $customerNumberField.find( 'label span.optional' ).show();

                                $customerNumberField.removeClass( 'validate-required woocommerce-invalid woocommerce-invalid-required-field' );
                            }

                            $customerNumberField.removeClass( 'hidden' );
                            $customerNumberField.show();
                        } else {
                            $customerNumberField.addClass( 'hidden' );
                            $customerNumberField.hide();
                        }
                    }
                } else {
                    $customerNumberField.addClass( 'hidden' );
                    $customerNumberField.hide();

                    $pickupSelect.val( "-1" );
                }
            } else {
                if ( "-1" !== current ) {
                    $( '#shipping_address_1' ).val( "" );

                    var $form = $( 'form.checkout' );

                    if ( $form.find( '.woocommerce-NoticeGroup-updateOrderReview' ).length <= 0 ) {
                        $form.prepend( '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-updateOrderReview"></div>' );
                    }

                    $form.find( '.woocommerce-NoticeGroup-updateOrderReview' ).prepend( '<div class="woocommerce-info">Your selected pickup location is not available any longer. Please review your shipping address.</div>' );

                    var scrollElement = $( '.woocommerce-NoticeGroup-updateOrderReview' );

                    $.scroll_to_notices( scrollElement );
                }

                $pickupSelectField.addClass( 'hidden' );
                $pickupSelectField.hide();

                $customerNumberField.addClass( 'hidden' );
                $customerNumberField.hide();

                $pickupSelect.val( "-1" );
            }
        },

        replaceShippingAddress: function( replacements ) {
            var self = germanized.shipments_classic_checkout,
                $shipToDifferent = $( '#ship-to-different-address input' ),
            hasChanged = [];

            Object.keys( replacements ).forEach( addressField => {
                var value = replacements[ addressField ];

                if ( value ) {
                    if ( $( '#shipping_' + addressField ).length > 0 ) {
                        if ( $( '#shipping_' + addressField ).val() !== value ) {
                            hasChanged.push( addressField );
                        }

                        $( '#shipping_' + addressField ).val( value );
                    }
                }
            });

            if ( ! $shipToDifferent.is( ':checked' ) ) {
                $shipToDifferent.prop( 'checked', true );
                $shipToDifferent.trigger( 'change' );
            }

            if ( hasChanged.length > 0 && $.inArray( "postcode", hasChanged ) !== -1 ) {
                $( '#shipping_postcode' ).trigger( 'change' );
            }
        }
    };

    $( document ).ready( function() {
        germanized.shipments_classic_checkout.init();
    });

})( jQuery, window.germanized );
