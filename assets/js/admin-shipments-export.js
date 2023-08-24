window.germanized = window.germanized || {};
window.germanized.admin = window.germanized.admin || {};

( function( $, admin ) {

    /**
     * Core
     */
    admin.shipments_export = {
        params: {},

        init: function () {
            var self = germanized.admin.shipments_export;

            self.params = wc_gzd_admin_shipments_export_params;

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

            $( document )
                .on( 'click', '.export-date-adjuster', self.onAdjustDate )
                .on( 'submit', '.wc-gzd-shipments-create-export', self.onCreateExport )
                .on( 'click', '.export-button-continue', self.continueExport );

            var $editForm = $( '.wc-gzd-shipments-export' );

            if ( $editForm.length > 0 && $editForm.data( 'autostart' ) ) {
                self.editExport( 'run' );
            }
        },

        continueExport: function() {
            var $form = $( '.wc-gzd-shipments-export' ),
                self = germanized.admin.shipments_export;

            $form.find( '.notice-wrapper' ).empty();
            $form.find( '.export-button-continue' ).hide();

            self.editExport( 'continue' );

            return false;
        },

        editExport: function( task ) {
            var $form = $( '.wc-gzd-shipments-export' ),
                self = germanized.admin.shipments_export,
                data = $form.serializeArray();

            data['export_action'] = task;

            $.ajax( {
                type: 'POST',
                url: self.params.ajax_url,
                data: data,
                dataType: 'json',
                success: function( response ) {
                    $form.find( '.notice-wrapper' ).empty();
                    $form.find( '.wc-gzd-shipments-export-progress' ).val( response.percentage );
                    $form.find( '.export-button-continue' ).hide();

                    $.each( response.error_messages, function( i, message ) {
                        $form.find( '.notice-wrapper' ).append( '<div class="notice notice-error"><p>' + message + '</p></div>' );
                    });

                    if ( 'completed' === response.status ) {
                        window.location.reload();
                    } else if ( 'halted' === response.status ) {
                        $form.find( '.export-button-continue' ).show();
                    } else {
                        self.editExport( 'run' );
                    }
                }
            } ).fail( function( response ) {
                window.console.log( response );
            } );

            return false;
        },

        onCreateExport: function() {
            var $form = $( this ),
                self = germanized.admin.shipments_export,
                data = {};

            $.each( $form.serializeArray(), function( index, item ) {
                if ( item.name.indexOf( '[]' ) !== -1 ) {
                    item.name = item.name.replace( '[]', '' );
                    data[ item.name ] = $.makeArray( data[ item.name ] );
                    data[ item.name ].push( item.value );
                } else {
                    data[ item.name ] = item.value;
                }
            });

            $( '.wc-gzd-shipments-export-wrapper' ).block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });

            $.ajax( {
                type: 'POST',
                url: self.params.ajax_url,
                data: data,
                dataType: 'json',
                success: function( response ) {
                    $form.find( '.notice-wrapper .notice' ).remove();

                    if ( response.success ) {
                        window.location = response.url;
                    } else {
                        if ( response.hasOwnProperty( 'messages' ) ) {
                            $.each( response.messages, function( i, message ) {
                                $( '.notice-wrapper' ).append( '<div class="notice is-dismissible notice-error"><p>' + message + '</p><button type="button" class="notice-dismiss"></button></div>' );
                            });
                        }

                        $( '.wc-gzd-shipments-export-wrapper' ).unblock();
                    }
                }
            } ).fail( function( response ) {
                window.console.log( response );
            } );

            return false;
        },

        onAdjustDate: function() {
            var $link = $( this ),
                self = germanized.admin.shipments_export,
                dates  = self.dates,
                today = new Date();

            if ( 'current_month' === $link.data( 'adjust' ) ) {
                $( dates[0] ).datepicker( "setDate", new Date( today.getFullYear(), today.getMonth(), 1 ) );
                $( dates[1] ).datepicker( "setDate", new Date( today.getFullYear(), today.getMonth() + 1, 0 ) );
            } else if ( 'last_month' === $link.data( 'adjust' ) ) {
                $( dates[0] ).datepicker( "setDate", new Date( today.getFullYear(), today.getMonth() - 1, 1 ) );
                $( dates[1] ).datepicker( "setDate", new Date( today.getFullYear(), today.getMonth(), 0 ) );
            }

            return false;
        }
    };

    $( document ).ready( function() {
        germanized.admin.shipments_export.init();
    });

})( jQuery, window.germanized.admin );
