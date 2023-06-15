/* global wc_gzd_admin_shipping_rules_params, ajaxurl */
( function( $, data, wp, ajaxurl ) {
    $( function() {
        var $tbody          = $( '.wc-gzd-shipments-shipping-rules-rows' ),
            $save_button    = $( '.wc-gzd-shipments-shipping-rules-save' ),
            $row_template   = wp.template( 'wc-gzd-shipments-shipping-rules-row' ),
            $blank_template = wp.template( 'wc-gzd-shipments-shipping-rules-row-blank' ),

            // Backbone model
            ShippingRule       = Backbone.Model.extend({
                changes: {},
                logChanges: function( changedRows ) {
                    var changes = this.changes || {};

                    _.each( changedRows, function( row, id ) {
                        changes[ id ] = _.extend( changes[ id ] || { rule_id : id }, row );
                    } );

                    this.changes = changes;

                    this.trigger( 'change:rules' );
                },
                discardChanges: function( id ) {
                    var changes      = this.changes || {};

                    // Delete all changes
                    delete changes[ id ];

                    // No changes? Disable save button.
                    if ( 0 === _.size( this.changes ) ) {
                        shippingRuleView.clearUnloadConfirmation();
                    }
                }
            } ),

            // Backbone view
            ShippingRuleView = Backbone.View.extend({
                rowTemplate: $row_template,
                initialize: function() {
                    this.listenTo( this.model, 'change:rules', this.setUnloadConfirmation );
                    $tbody.on( 'change', { view: this }, this.updateModelOnChange );
                    $( window ).on( 'beforeunload', { view: this }, this.unloadConfirmation );
                    $save_button.on( 'click', { view: this }, this.onSubmit );
                    $( document.body ).on( 'click', '.wc-gzd-shipments-shipping-rule-add', { view: this }, this.onAddNewRow );
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
                render: function() {
                    var rules       = _.indexBy( this.model.get( 'rules' ), 'rule_id' ),
                        view        = this;

                    this.$el.empty();
                    this.unblock();

                    if ( _.size( rules ) ) {
                        // Sort classes
                        rules = _.sortBy( rules, function( rule ) {
                            return rule.packaging;
                        } );

                        // Populate $tbody with the current classes
                        $.each( rules, function( id, rowData ) {
                            view.renderRow( rowData );
                        } );
                    } else {
                        view.$el.append( $blank_template );
                    }
                },
                renderRow: function( rowData ) {
                    var view = this;
                    view.$el.append( view.rowTemplate( rowData ) );
                    view.initRow( rowData );
                },
                initRow: function( rowData ) {
                    var view = this;
                    var $tr = view.$el.find( 'tr[data-id="' + rowData.rule_id + '"]');

                    // Support select boxes
                    $tr.find( 'select' ).each( function() {
                        var attribute = $( this ).data( 'attribute' );
                        $( this ).find( 'option[value="' + rowData[ attribute ] + '"]' ).prop( 'selected', true );
                    } );

                    // Make the rows function
                    $tr.find( '.shipping-rules-type-container' ).hide();
                    $tr.find( '.conditions-column:not(.conditions-when)' ).hide();

                    $tr.find( '.shipping-rules-type' ).on( 'change', { view: this }, this.onChangeRuleType );
                    $tr.find( '.shipping-rules-delete' ).on( 'click', { view: this }, this.onDeleteRow );
                    $tr.find( '.shipping-packaging' ).on( 'change', { view: this }, this.onChangePackaging );

                    $tr.find( '.shipping-rules-type' ).trigger( 'change' );
                },
                onChangePackaging: function( event ) {
                    var view = event.data.view,
                        model = view.model,
                        $tr   = $( this ).closest('tr'),
                        packaging = $(this).val();

                    $lastTr = $tbody.find( '.shipping-packaging:has(option[value="' + packaging + '"]:selected)' ).not( this ).last().parents( 'tr ');

                    if ( $lastTr.length > 0 ) {
                        $tr.insertAfter( $lastTr );
                        $tr.find( '.shipping-packaging' ).focus();
                    } else {
                        $tr.insertAfter($tbody.find('tr:last'));
                    }

                    $tr.addClass('packaging-' + packaging);
                },
                onChangeRuleType: function( event ) {
                    var view    = event.data.view,
                        model   = view.model,
                        $tr     = $( this ).closest('tr'),
                        rule    = $(this).val();

                    $tr.find( '.shipping-rules-type-container' ).hide();
                    $tr.find( '.conditions-column:not(.conditions-when)' ).hide();
                    $tr.find('.shipping-rules-type-container-' + rule).show();
                    $tr.find('.shipping-rules-type-container-' + rule).parents('.conditions-column').show();
                },
                onAddNewRow: function( event ) {
                    event.preventDefault();

                    var view    = event.data.view,
                        model   = view.model,
                        rules   = _.indexBy( model.get( 'rules' ), 'rule_id' ),
                        changes = {},
                        size    = _.size( rules ),
                        newRow  = _.extend( {}, data.default_shipping_rule, {
                            rule_id: 'new-' + size + '-' + Date.now(),
                            newRow : true
                        } );

                    changes[ newRow.rule_id ] = newRow;

                    model.logChanges( changes );
                    view.renderRow( newRow );
                    $( '.wc-gzd-shipments-shipping-rules-blank-state' ).remove();
                },
                onDeleteRow: function( event ) {
                    var view    = event.data.view,
                        model   = view.model,
                        rules   = _.indexBy( model.get( 'rules' ), 'rule_id' ),
                        changes = {},
                        rule_id = $( this ).closest('tr').data('id');

                    event.preventDefault();

                    if ( rules[ rule_id ] ) {
                        delete rules[ rule_id ];
                        changes[ rule_id ] = _.extend( changes[ rule_id ] || {}, { deleted : 'deleted' } );
                        model.set( 'rules', rules );
                        model.logChanges( changes );
                    }

                    view.render();
                },
                setUnloadConfirmation: function() {
                    this.needsUnloadConfirm = true;
                    $save_button.prop( 'disabled', false );
                },
                clearUnloadConfirmation: function() {
                    this.needsUnloadConfirm = false;
                    $save_button.attr( 'disabled', 'disabled' );
                },
                unloadConfirmation: function( event ) {
                    if ( event.data.view.needsUnloadConfirm ) {
                        event.returnValue = data.strings.unload_confirmation_msg;
                        window.event.returnValue = data.strings.unload_confirmation_msg;
                        return data.strings.unload_confirmation_msg;
                    }
                },
                updateModelOnChange: function( event ) {
                    var model     = event.data.view.model,
                        $target   = $( event.target ),
                        rule_id   = $target.closest( 'tr' ).data( 'id' ),
                        attribute = $target.data( 'attribute' ),
                        value     = $target.val(),
                        rules     = _.indexBy( model.get( 'rules' ), 'rule_id' ),
                        changes   = {};

                    if ( $target.is(':checkbox') ) {
                        value = $target.is(':checked') ? value : '';
                    }

                    if ( ! rules[ rule_id ] || rules[ rule_id ][ attribute ] !== value ) {
                        changes[ rule_id ] = {};
                        changes[ rule_id ][ attribute ] = value;
                    }

                    model.logChanges( changes );
                }
            } ),
            shippingRule = new ShippingRule({
                rules: data.rules
            } ),
            shippingRuleView = new ShippingRuleView({
                model:    shippingRule,
                el:       $tbody
            } );

        shippingRuleView.render();

        // Sorting
        $tbody.sortable({
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

        var controlled = false;
        var shifted = false;
        var hasFocus = false;

        $( document.body ).on( 'keyup keydown', function ( e ) {
            shifted = e.shiftKey;
            controlled = e.ctrlKey || e.metaKey;
        } );

        $tbody
            .on( 'focus click', ':input:visible', function ( e ) {
                var $this_table = $( this ).closest( 'table, tbody' );
                var $this_row = $( this ).closest( 'tr' );

                if (
                    ( e.type === 'focus' && hasFocus !== $this_row.index() ) ||
                    ( e.type === 'click' && $( this ).is( ':focus' ) )
                ) {
                    hasFocus = $this_row.index();

                    if ( ! shifted && ! controlled ) {
                        $( 'tr', $this_table )
                            .removeClass( 'current' )
                            .removeClass( 'last_selected' );
                        $this_row
                            .addClass( 'current' )
                            .addClass( 'last_selected' );
                    } else if ( shifted ) {
                        $( 'tr', $this_table ).removeClass( 'current' );
                        $this_row
                            .addClass( 'selected_now' )
                            .addClass( 'current' );

                        if ( $( 'tr.last_selected', $this_table ).length > 0 ) {
                            if (
                                $this_row.index() >
                                $( 'tr.last_selected', $this_table ).index()
                            ) {
                                $( 'tr', $this_table )
                                    .slice(
                                        $(
                                            'tr.last_selected',
                                            $this_table
                                        ).index(),
                                        $this_row.index()
                                    )
                                    .addClass( 'current' );
                            } else {
                                $( 'tr', $this_table )
                                    .slice(
                                        $this_row.index(),
                                        $(
                                            'tr.last_selected',
                                            $this_table
                                        ).index() + 1
                                    )
                                    .addClass( 'current' );
                            }
                        }

                        $( 'tr', $this_table ).removeClass( 'last_selected' );
                        $this_row.addClass( 'last_selected' );
                    } else {
                        $( 'tr', $this_table ).removeClass( 'last_selected' );
                        if (
                            controlled &&
                            $( this ).closest( 'tr' ).is( '.current' )
                        ) {
                            $this_row.removeClass( 'current' );
                        } else {
                            $this_row
                                .addClass( 'current' )
                                .addClass( 'last_selected' );
                        }
                    }

                    $( 'tr', $this_table ).removeClass( 'selected_now' );
                }
            } )
            .on( 'blur', ':input:visible', function () {
                hasFocus = false;
            } );
    });
})( jQuery, wc_gzd_admin_shipping_rules_params, wp, ajaxurl );
