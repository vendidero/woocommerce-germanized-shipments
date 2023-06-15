<?php
namespace Vendidero\Germanized\Shipments\ShippingMethod;

use DVDoug\BoxPacker\BoxList;
use Vendidero\Germanized\Shipments\Admin\Settings;
use Vendidero\Germanized\Shipments\Interfaces\ShippingProvider;
use Vendidero\Germanized\Shipments\Packing\Helper;
use Vendidero\Germanized\Shipments\SimpleShipment;

defined( 'ABSPATH' ) || exit;

class ShippingMethod extends \WC_Shipping_Method {

	protected $shipping_provider = null;

	/**
	 * Constructor.
	 *
	 * @param int $instance_id Shipping method instance ID.
	 * @param ShippingProvider|null $shipping_provider
	 */
	public function __construct( $instance_id = 0, $shipping_provider = null ) {
		if ( is_null( $shipping_provider ) ) {
			if ( ! empty( $instance_id ) ) {
				$raw_method = \WC_Data_Store::load( 'shipping-zone' )->get_method( $instance_id );

				if ( ! empty( $raw_method ) ) {
					$method_id               = str_replace( 'shipping_provider_', '', $raw_method->method_id );
					$this->shipping_provider = wc_gzd_get_shipping_provider( $method_id );
				}
			}
		} else {
			$this->shipping_provider = is_a( $shipping_provider, 'Vendidero\Germanized\Shipments\Interfaces\ShippingProvider' ) ? $shipping_provider : wc_gzd_get_shipping_provider( $shipping_provider );
		}

		if ( ! is_a( $this->shipping_provider, 'Vendidero\Germanized\Shipments\Interfaces\ShippingProvider' ) ) {
			return;
		}

		$this->id                 = 'shipping_provider_' . $this->shipping_provider->get_name();
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = $this->shipping_provider->get_title();
		$this->title              = $this->method_title;
		$this->method_description = '';
		$this->supports           = array(
			'shipping-zones',
			'instance-settings',
		);

		$this->init();

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Init user set variables.
	 */
	public function init() {
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title      = $this->get_option( 'title' );

		// Actions.
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

    public function get_all_shipping_rules() {
        return array_merge( ...array_values( $this->get_option( 'shipping_rules', array() ) ) );
    }

    public function admin_options() {
	    wp_localize_script(
		    'wc-gzd-admin-shipping-rules',
		    'wc_gzd_admin_shipping_rules_params',
		    array(
			    'rules'                     => $this->get_all_shipping_rules(),
			    'default_shipping_rule'    => array(
				    'rule_id'     => 0,
				    'type'        => 'always',
				    'packaging'   => '',
                    'costs'       => '',
			    ),
			    'strings'                   => array(
				    'unload_confirmation_msg' => _x( 'Your changed data will be lost if you leave this page without saving.', 'shipments', 'woocommerce-germanized-shipments' ),
			    ),
		    )
	    );
	    wp_enqueue_script( 'wc-gzd-admin-shipping-rules' );

	    parent::admin_options();
    }

	public function init_form_fields() {
		$this->instance_form_fields = array(
			'title'      => array(
				'title'       => _x( 'Title', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'        => 'text',
				'description' => _x( 'This controls the title which the user sees during checkout.', 'shipments', 'woocommerce-germanized-shipments' ),
				'default'     => $this->method_title,
				'desc_tip'    => true,
			),
			'shipping_rules' => array(
				'title'       => _x( 'Rules', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'        => 'shipping_rules',
				'default'     => array(),
			),
			'cache' => array(
				'type'        => 'cache',
				'default'     => array(),
			)
		);
	}

    protected function generate_cache_html() {
        return '';
    }

    protected function validate_cache_field() {
        $rules = $this->get_option( 'shipping_rules' );
        $cache = array(
            'packaging_ids' => array_keys( $rules ),
        );

        return $cache;
    }

    public function get_rule_types() {
        return array(
	        'always' => array(
		        'label' => _x( 'Always', 'shipments', 'woocommerce-germanized-shipments' ),
		        'fields' => array(),
	        ),
            'weight' => array(
                'label' => _x( 'Weight', 'shipments', 'woocommerce-germanized-shipments' ),
                'fields' => array(
                    'weight_from' => array(
                        'type' => 'text',
                        'data_type' => 'decimal',
                        'label' => _x( 'Is from', 'shipments', 'woocommerce-germanized-shipments' )
                    ),
                    'weight_to' => array(
	                    'type' => 'text',
	                    'data_type' => 'decimal',
	                    'label' => _x( 'to', 'shipments', 'woocommerce-germanized-shipments' )
                    )
                ),
            ),
            'total' => array(
	            'label' => _x( 'Total', 'shipments', 'woocommerce-germanized-shipments' ),
	            'fields' => array(
		            'total_from' => array(
			            'type' => 'text',
			            'data_type' => 'decimal',
			            'label' => _x( 'Is from', 'shipments', 'woocommerce-germanized-shipments' )
		            ),
		            'total_to' => array(
			            'type' => 'text',
			            'data_type' => 'decimal',
			            'label' => _x( 'to', 'shipments', 'woocommerce-germanized-shipments' )
		            )
	            ),
            ),
        );
    }

    public function get_available_packaging() {

    }

    public function get_cache() {
	    $cache = wp_parse_args( $this->get_option( 'cache', array() ), array(
            'packaging_ids' => array()
        ) );

        return $cache;
    }

    public function calculate_shipping( $package = array() ) {
        $cache  = $this->get_cache();
        $items  = array();
        $boxes  = BoxList::fromArray( Helper::get_available_packaging( $cache['packaging_ids'] ) );

	    foreach ( $package['contents'] as $item_id => $values ) {
            if ( isset( $values['package'] ) ) {
	            $packer = new \DVDoug\BoxPacker\InfalliblePacker();
                $packer->setBoxes( $boxes );
                $packer->setItems( $values['package'] );

	            /**
	             * Make sure to not try to spread/balance weights. Instead try to pack
	             * the first box as full as possible to make sure a smaller box can be used for a second box.
	             */
	            $packer->setMaxBoxesToBalanceWeight( 0 );
	            $boxes = $packer->pack();

	            // Items that do not fit in any box
	            $items_too_large = $packer->getUnpackedItems();

                if ( 0 <= $items_too_large->count() ) {
	                foreach ( $boxes as $box ) {
		                $packaging = $box->getBox();
		                $items     = $box->getItems();

                        $total_weight = $items->getWeight();
                        $volume       = $items->getVolume();
                        $total        = 0;
                        $subtotal     = 0;

                        foreach( $items as $item ) {
                            $cart_item = $item->getItem();
                            $total    += $cart_item->getTotal();
	                        $subtotal += $cart_item->getSubtotal();
                        }

                        $total    = wc_remove_number_precision( $total );
		                $subtotal = wc_remove_number_precision( $subtotal );


	                }
                }
            }
	    }
    }

	protected function validate_shipping_rules_field( $option_name, $option_value ) {
        $option_value = stripslashes_deep( $option_value );
        $ids          = array_keys( $option_value['type'] );
        $rules        = array();
        $rule_types   = $this->get_rule_types();
        $index        = 0;

        foreach( $ids as $id ) {
            $rule_id   = $index++;
            $packaging = absint( $option_value['packaging'][ $id ] );
            $type      = wc_clean( $option_value['type'][ $id ] );
	        $costs     = wc_format_decimal( isset( $option_value['costs'][ $id ] ) ? wc_clean( $option_value['costs'][ $id ] ) : 0 );

            if ( ! array_key_exists( $type, $rule_types ) ) {
                continue;
            }

            $rule = array(
                'rule_id'   => $rule_id,
                'packaging' => $packaging,
                'type'      => $type,
                'costs'     => $costs,
                'meta'      => array(),
            );

            $rule_type = $rule_types[ $type ];

            foreach( $rule_type['fields'] as $field_name => $field ) {
                $field = wp_parse_args( $field, array(
                    'type' => '',
                    'data_type' => '',
                ) );

                $rule[ $field_name ] = isset( $field['default'] ) ? $field['default'] : '';

                if ( isset( $option_value[ $field_name ][ $id ] ) ) {
                    $value = wc_clean( $option_value[ $field_name ][ $id ] );

                    if ( 'decimal' === $field['data_type'] ) {
                        $value = wc_format_decimal( $value );
                    }

	                $rule[ $field_name ] = $value;
                }
            }

            if ( ! isset( $rules[ $packaging ] ) ) {
                $rules[ $packaging ] = array();
            }

	        $rules[ $packaging ][] = $rule;
        }

        return $rules;
    }

	protected function generate_shipping_rules_html( $option_name, $option ) {
        ob_start();
        $field_key = $this->get_field_key( 'shipping_rules' );
        $rule_types = $this->get_rule_types();
		?>
		<table class="widefat wc-gzd-shipments-shipping-rules">
			<thead>
				<tr>
					<th class="sort"></th>
					<th class="cb"></th>
					<th class="packaging">
						<?php echo esc_html_x( 'Packaging', 'shipments', 'woocommerce-germanized-shipments' ); ?>
					</th>
					<th class="conditions">
						<?php echo esc_html_x( 'Conditions', 'shipments', 'woocommerce-germanized-shipments' ); ?>
					</th>
					<th class="costs">
						<?php echo esc_html_x( 'Costs', 'shipments', 'woocommerce-germanized-shipments' ); ?>
					</th>
                    <th class="actions">
						<?php echo esc_html_x( 'Actions', 'shipments', 'woocommerce-germanized-shipments' ); ?>
                    </th>
				</tr>
			</thead>
			<tbody class="wc-gzd-shipments-shipping-rules-rows">
			</tbody>
			<tfoot>
				<tr>
					<th colspan="7">
                        <button class="button button-primary wc-gzd-shipments-shipping-rule-add"><?php echo esc_html_x( 'Add new', 'shipments', 'woocommerce-germanized-shipments' ); ?></button>
					</th>
				</tr>
			</tfoot>
		</table>
        <script type="text/html" id="tmpl-wc-gzd-shipments-shipping-rules-row-blank">
            <tr>
                <td colspan="6" class="wc-gzd-shipments-shipping-rules-blank-state"><?php echo esc_html_x( 'No rules yet', 'shipments', 'woocommerce-germanized-shipments' ); ?></td>
            </tr>
        </script>
        <script type="text/html" id="tmpl-wc-gzd-shipments-shipping-rules-row">
            <tr data-id="{{ data.rule_id }}" class="packaging-{{data.packaging}}">
                <td class="sort ui-sortable-handle">
                    <div class="wc-item-reorder-nav wc-gzd-shipping-rules-reorder-nav">
                    </div>
                </td>
                <td class="cb">
                    <input name="<?php echo esc_attr( $field_key ); ?>[cb][{{ data.rule_id }}]" type="checkbox" value="{{ data.rule_id }}" data-attribute="cb" />
                </td>
                <td class="packaging">
                    <select class="wc-enhanced-select shipping-packaging" name="<?php echo esc_attr( $field_key ); ?>[packaging][{{ data.rule_id }}]" data-attribute="packaging">
                        <?php foreach( wc_gzd_get_packaging_select() as $option => $value ) : ?>
                            <option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $value ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td class="conditions">
                    <div class="conditions-columns">
                        <div class="conditions-column conditions-when">
                            <p class="form-field">
                                <label>When</label>
                                <select name="<?php echo esc_attr( $field_key ); ?>[type][{{ data.rule_id }}]" class="shipping-rules-type" data-attribute="type">
		                            <?php foreach( $rule_types as $rule_type => $rule_type_data ) : ?>
                                        <option value="<?php echo esc_attr( $rule_type ); ?>"><?php echo esc_html( $rule_type_data['label'] ); ?></option>
		                            <?php endforeach; ?>
                                </select>
                            </p>
                        </div>
	                    <?php
	                    $columns = array();

	                    foreach( $rule_types as $rule_type => $rule_type_data ) {
		                    $index = 0;

		                    foreach( $rule_type_data['fields'] as $field_name => $field ) {
			                    $column_key = ++$index;
			                    $column_key = isset( $field['column'] ) ? $field['column'] : $column_key;

			                    if ( ! isset( $columns[ $column_key ] ) ) {
				                    $columns[ $column_key ] = array();
			                    }

			                    if ( ! isset( $columns[ $column_key ][ $rule_type ] ) ) {
				                    $columns[ $column_key ][ $rule_type ] = array();
			                    }

			                    $columns[ $column_key ][ $rule_type ][ $field_name ] = $field;
		                    }
	                    }
	                    ?>
	                    <?php foreach( $columns as $column ) : ?>
                            <div class="conditions-column">
			                    <?php foreach( $column as $column_rule_type => $fields ) : ?>
				                    <?php foreach( $fields as $field_name => $field ) :
					                    $field = wp_parse_args( $field, array(
						                    'name' => $field_key . "[$field_name][{{ data.rule_id }}]",
						                    'id' => $field_key . '-' . $field_name . '-{{ data.rule_id }}',
						                    'custom_attributes' => array(),
						                    'type' => 'text',
						                    'class' => isset( $field['data_type'] ) ? $field['data_type'] : '',
						                    'value' => '{{data.' . $field_name . '}}',
					                    ) );
					                    $field['data_type'] = '';
					                    $field['custom_attributes'] = array_merge( $field['custom_attributes'], array( 'data-attribute' => $field_name ) );
					                    ?>
                                        <div class="shipping-rules-type-container shipping-rules-type-container-<?php echo esc_attr( $column_rule_type ); ?>" data-rule-type="<?php echo esc_attr( $column_rule_type ); ?>">
						                    <?php woocommerce_wp_text_input( $field ); ?>
                                        </div>
				                    <?php endforeach; ?>
			                    <?php endforeach; ?>
                            </div>
	                    <?php endforeach; ?>
                    </div>
                </td>
                <td class="costs">
                    <p class="form-field">
                        <label>Rule cost is</label>
                        <input type="text" class="short wc_input_price" name="<?php echo esc_attr( $field_key ); ?>[costs][{{ data.rule_id }}]" value="{{ data.costs }}" data-attribute="costs">
                    </p>
                </td>
                <td class="actions">
                    <a class="button button-secondary" href="#"></a>
                </td>
            </tr>
        </script>
        <script type="text/html" id="tmpl-wc-gzd-shipments-shipping-rules-row-label-modal">
            <div class="wc-backbone-modal wc-gzd-admin-shipment-modal wc-gzd-modal-shipping-rule-label">
                <div class="wc-backbone-modal-content">
                    <section class="wc-backbone-modal-main" role="main">
                        <header class="wc-backbone-modal-header">
                            <h1><?php echo esc_html_x( 'Adjust label configuration', 'shipments', 'woocommerce-germanized-shipments' ); ?></h1>
                            <button class="modal-close modal-close-link dashicons dashicons-no-alt">
                                <span class="screen-reader-text">Close modal panel</span>
                            </button>
                        </header>
                        <article>

                        </article>
                        <footer>
                            <div class="inner">
                                <button id="btn-ok" class="button button-primary button-large"><?php echo esc_html_x( 'Done', 'shipments', 'woocommerce-germanized-shipments' ); ?></button>
                            </div>
                        </footer>
                    </section>
                </div>
            </div>
            <div class="wc-backbone-modal-backdrop modal-close"></div>
        </script>
		<?php
        $html = ob_get_clean();

        return $html;
	}
}