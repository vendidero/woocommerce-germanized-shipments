/* global wc_gzd_admin_shipping_rules_params, ajaxurl */
( function( $, data, wp, ajaxurl ) {
    $( function() {
        var $tbody            = $( '.wc-gzd-shipments-shipping-rules-rows' ),
            $table            = $( '.wc-gzd-shipments-shipping-rules' ),
            $row_template     = wp.template( 'wc-gzd-shipments-shipping-rules-row' ),
            $packaging_info   = wp.template( 'wc-gzd-shipments-shipping-rules-packaging-info' ),
            shippingRuleViews = {},

            // Backbone model
            ShippingRule = Backbone.Model.extend({
                updateRules: function( rules, packaging ) {
                    if ( 0 === Object.keys( rules ).length ) {
                        rules = {};
                    }

                    var currentRules = { ...this.get( 'rules' ) };
                    currentRules[ String( packaging ) ] = rules;

                    this.set( 'rules', currentRules );
                },
                getRulesByPackaging: function( packaging ) {
                    var rules = { ...this.get( 'rules' ) };

                    return rules.hasOwnProperty( String( packaging ) ) ? rules[ String( packaging ) ] : {};
                }
            } ),

            // Backbone view
            ShippingRuleView = Backbone.View.extend({
                rowTemplate: $row_template,
                packaging: '',
                initialize: function() {
                    this.packaging = String( $( this.el ).data( 'packaging' ) );
                    $( this.el ).on( 'change', { view: this }, this.updateModelOnChange );
                },
                block: function() {
                    $( this.el ).block({
                        message: null,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                },
                unblock: function() {
                    $( this.el ).unblock();
                },
                getRules: function() {
                    return this.model.getRulesByPackaging( this.packaging );
                },
                render: function() {
                    var rules = this.getRules(),
                        view  = this;

                    this.$el.empty();
                    this.unblock();

                    if ( _.size( rules ) ) {
                        // Populate $tbody with the current classes
                        $.each( rules, function( id, rowData ) {
                            view.renderRow( rowData );
                        } );
                    }
                },
                renderRow: function( rowData ) {
                    var view = this;
                    view.$el.append( view.rowTemplate( rowData ) );
                    view.initRow( rowData );
                },
                initRow: function( rowData ) {
                    var view = this,
                        $tr = view.$el.find( 'tr[data-id="' + rowData.rule_id + '"]'),
                        $tbody = $tr.parents( 'tbody' );

                    // Support select boxes
                    $tr.find( 'select' ).each( function() {
                        var attribute = $( this ).data( 'attribute' );
                        $( this ).find( 'option[value="' + rowData[ attribute ] + '"]' ).prop( 'selected', true );
                    } );

                    // Make the rows function
                    $tr.find( '.shipping-rules-type-container' ).hide();
                    $tr.find( '.conditions-column:not(.conditions-when)' ).hide();

                    $tr.find( '.shipping-rules-type' ).on( 'change', { view: this }, this.onChangeRuleType );
                    $tr.find( '.shipping-rules-type' ).trigger( 'change' );

                    $tr.find( 'input.decimal' ).on( 'change', { view: this }, this.onChangeDecimal );
                    $tr.find( 'input.wc_input_price' ).on( 'change', { view: this }, this.onChangePrice );
                    $tr.find( 'input.decimal, input.wc_input_price' ).trigger( 'change' );

                    $tr.find( '.shipping-rule-remove' ).on( 'click', { view: this }, this.onDeleteRow );

                    if ( $tbody.find( 'tr.wc-gzd-shipments-shipping-rules-packaging-info' ).length <= 0 ) {
                        $tbody.prepend( $packaging_info );
                        var $packagingTr = $tbody.find( 'tr.wc-gzd-shipments-shipping-rules-packaging-info' );

                        $packagingTr.find( '.packaging-title' ).html( $tbody.data( 'title' ) );

                        if ( $tbody.data( 'help-tip' ) ) {
                            $packagingTr.find( '.woocommerce-help-tip' ).attr( 'data-tip', $tbody.data( 'help-tip' ) );
                        } else {
                            $packagingTr.find( '.woocommerce-help-tip' ).remove();
                        }
                    }

                    $( document.body ).trigger( 'wc-enhanced-select-init' );
                    $( document.body ).trigger( 'init_tooltips' );
                },
                onChangeDecimal: function( event ) {
                    var $tr     = $( this ).closest( 'tr' ),
                        $input  = $( this );

                    $input.val( $input.val().replace( '.', data.decimal_separator ) );
                },
                onChangePrice: function( event ) {
                    var $tr     = $( this ).closest( 'tr' ),
                        $input  = $( this );

                    $input.val( $input.val().replace( '.', data.price_decimal_separator ) );
                },
                onChangeRuleType: function( event ) {
                    var $tr     = $( this ).closest( 'tr' ),
                        rule    = $( this ).val();

                    $tr.find( '.shipping-rules-type-container' ).hide();
                    $tr.find( '.conditions-column:not(.conditions-when)' ).hide();
                    $tr.find( '.shipping-rules-type-container-' + rule ).show();
                    $tr.find( '.shipping-rules-type-container-' + rule ).parents( '.conditions-column' ).show();
                },
                onDeleteRow: function( event ) {
                    var view       = event.data.view,
                        model      = view.model,
                        rules      = view.getRules(),
                        rule_id    = $( this ).closest( 'tr' ).data( 'id' ),
                        rule_index = "rule_" + rule_id;

                    event.preventDefault();

                    if ( rules[ rule_index ] ) {
                        delete rules[ rule_index ];

                        model.updateRules( rules, view.packaging );
                    }

                    view.render();
                },
                updateModelOnChange: function( event ) {
                    var view       = event.data.view,
                        model      = view.model,
                        $target    = $( event.target ),
                        rule_id    = $target.closest( 'tr' ).data( 'id' ),
                        rule_index = "rule_" + rule_id,
                        attribute  = $target.data( 'attribute' ),
                        value      = $target.val(),
                        rules      = view.getRules();

                    if ( ! $target.is( ':visible' ) ) {
                        return false;
                    }

                    if ( $target.is( ':checkbox' ) ) {
                        value = $target.is( ':checked' ) ? value : '';
                    } else if ( value && $target.hasClass( 'decimal' ) ) {
                        value = parseFloat( value.replace( data.decimal_separator , '.' ) );
                    } else if ( value && $target.hasClass( 'wc_input_price' ) ) {
                        value = parseFloat( value.replace( data.price_decimal_separator, '.' ) );
                    }

                    if ( ! rules[ rule_index ] || String( rules[ rule_index ][ attribute ] ) !== String( value ) ) {
                        rules[ rule_index ][ attribute ] = value;

                        if ( String( rules[ rule_index ]['packaging'] ) !== String( view.packaging ) ) {
                            var newPackaging = rules[ rule_index ]['packaging'],
                                newView = shippingRuleViews[ newPackaging ],
                                newRules = newView.getRules();

                            newRules[ rule_index ] = { ...rules[ rule_index ] };
                            delete rules[ rule_index ];

                            model.updateRules( rules, view.packaging );
                            newView.model.updateRules( newRules, newPackaging );

                            view.render();
                            newView.render();
                        } else {
                            model.updateRules( rules, view.packaging );
                        }
                    }
                }
            } ),
            shippingRule = new ShippingRule({
                rules: data.rules
            } );

        $tbody.each( function() {
            var view = new ShippingRuleView({
                model: shippingRule,
                el: $( this )
            } );

            view.render();
            shippingRuleViews[ $( this ).data( 'packaging' ) ] = view;

            // Sorting
            $( this ).sortable({
                items: 'tr',
                cursor: 'move',
                axis: 'y',
                handle: 'td.sort',
                scrollSensitivity: 40,
                helper: function ( event, ui ) {
                    ui.children().each( function () {
                        $( this ).width( $( this ).width() );
                    } );
                    ui.css( 'left', '0' );
                    return ui;
                },
                start: function ( event, ui ) {
                    ui.item.css( 'background-color', '#f6f6f6' );
                },
                stop: function ( event, ui ) {
                    ui.item.removeAttr( 'style' );
                    ui.item.trigger( 'updateMoveButtons' );
                },
            } );
        } );

        $( document ).on( 'click', '.wc-gzd-shipments-shipping-rule-add', function() {
            var packagingId = $( '.new-shipping-packaging' ).val(),
                view = shippingRuleViews[ packagingId ],
                rules = view.model.getRulesByPackaging( packagingId ),
                newRow  = _.extend( {}, data.default_shipping_rule, {
                    rule_id: 'new-' + _.size( rules ) + '-' + Date.now(),
                    packaging: packagingId,
                    newRow : true
                } );

            rules[ 'rule_' + newRow.rule_id ] = newRow;
            view.model.updateRules( rules, packagingId );

            shippingRuleViews[ packagingId ].renderRow( newRow );

            return false;
        } );

        $( document).on( 'change', '.wc-gzd-shipments-shipping-rules-rows input.cb', function() {
             $selected = $( this ).parents( 'table' ).find( 'input.cb:checked' );

             if ( $selected.length > 0 ) {
                 $table.find( '.wc-gzd-shipments-shipping-rule-remove' ).removeClass( 'disabled' );
             } else {
                 $table.find( '.wc-gzd-shipments-shipping-rule-remove' ).addClass( 'disabled' );
             }
        });

        $( document ).on( 'click', '.wc-gzd-shipments-shipping-rule-remove', function() {
            var rules = shippingRule.get( 'rules' ),
                $button = $( this ),
                $table = $button.parents( 'table' ),
                packagingIds = [];

            $table.find( 'input.cb:checked' ).each( function() {
                var rule_id = $( this ).val(),
                    rule_index = 'rule_' + rule_id,
                    $tr = $( this ).parents( 'tr' ),
                    packagingId = $tr.find( '.shipping-packaging' ).val();

                if ( ! packagingIds.includes( packagingId ) ) {
                    packagingIds.push( packagingId );
                }

                delete rules[ packagingId ][ rule_index ];
            });

            shippingRule.set( 'rules', rules );

            packagingIds.forEach( function ( packagingId, index) {
                shippingRuleViews[ packagingId ].render();
            });

            $button.addClass( 'disabled' );

            return false;
        } );
    });
})( jQuery, wc_gzd_admin_shipping_rules_params, wp, ajaxurl );
