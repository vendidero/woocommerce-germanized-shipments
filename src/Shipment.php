<?php
/**
 * Regular shipment
 *
 * @package Vendidero\Germanized\Shipments
 * @version 1.0.0
 */
namespace Vendidero\Germanized\Shipments;
use WC_Data;
use WC_Data_Store;
use Exception;
use WC_DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment Class.
 */
abstract class Shipment extends WC_Data {

    /**
     * Stores data about status changes so relevant hooks can be fired.
     *
     * @var bool|array
     */
    protected $status_transition = false;

    /**
     * This is the name of this object type.
     *
     * @since 1.0.0
     * @var string
     */
    protected $object_type = 'shipment';

    /**
     * Contains a reference to the data store for this class.
     *
     * @since 1.0.0
     * @var object
     */
    protected $data_store = 'shipment';

    /**
     * Stores meta in cache for future reads.
     * A group must be set to to enable caching.
     *
     * @since 1.0.0
     * @var string
     */
    protected $cache_group = 'shipment';

	/**
	 * The contained ShipmentItems.
	 *
	 * @var null|Shipment
	 */
	protected $items = null;

	/**
	 * List of items to be deleted on save.
	 *
	 * @var Shipment[]
	 */
	protected $items_to_delete = array();

	/**
	 * Item weights.
	 *
	 * @var null|float[]
	 */
	protected $weights = null;

	/**
	 * Item lengths.
	 *
	 * @var null|float[]
	 */
	protected $lengths = null;

	/**
	 * Item widths.
	 *
	 * @var null|integer[]
	 */
	protected $widths = null;

	/**
	 * Item heights.
	 *
	 * @var null|integer[]
	 */
	protected $heights = null;

    /**
     * Stores shipment data.
     *
     * @var array
     */
    protected $data = array(
        'date_created'          => null,
        'date_sent'             => null,
        'status'                => '',
        'weight'                => '',
        'width'                 => '',
        'height'                => '',
        'length'                => '',
        'country'               => '',
        'address'               => array(),
        'tracking_id'           => '',
        'shipping_provider'     => '',
        'shipping_method'       => '',
        'total'                 => 0,
    );

	/**
	 * Get the shipment if ID is passed, otherwise the shipment is new and empty.
	 * This class should NOT be instantiated, but the `wc_gzd_get_shipment` function should be used.
	 *
	 * @param int|object|Shipment $shipment Shipment to read.
	 */
    public function __construct( $data = 0 ) {
        parent::__construct( $data );

        if ( $data instanceof Shipment ) {
            $this->set_id( absint( $data->get_id() ) );
        } elseif ( is_numeric( $data ) ) {
            $this->set_id( $data );
        }

        $this->data_store = WC_Data_Store::load( 'shipment' );

        // If we have an ID, load the user from the DB.
        if ( $this->get_id() ) {
            try {
                $this->data_store->read( $this );
            } catch ( Exception $e ) {
                $this->set_id( 0 );
                $this->set_object_read( true );
            }
        } else {
            $this->set_object_read( true );
        }
    }

    public function get_type() {
    	return '';
    }

    /**
     * Merge changes with data and clear.
     * Overrides WC_Data::apply_changes.
     *
     * @since 3.2.0
     */
    public function apply_changes() {
        if ( function_exists( 'array_replace' ) ) {
            $this->data = array_replace( $this->data, $this->changes ); // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.array_replaceFound
        } else { // PHP 5.2 compatibility.
            foreach ( $this->changes as $key => $change ) {
                $this->data[ $key ] = $change;
            }
        }
        $this->changes = array();
    }

	/**
	 * Return item count (quantities summed up).
	 *
	 * @return int
	 */
    public function get_item_count() {
        $items    = $this->get_items();
        $quantity = 0;

        foreach( $items as $item ) {
            $quantity += $item->get_quantity();
        }

        return $quantity;
    }

    /**
     * Prefix for action and filter hooks on data.
     *
     * @return string
     */
    protected function get_hook_prefix() {
        return 'woocommerce_gzd_shipment_get_';
    }

    /**
     * Return the shipment statuses without gzd- internal prefix.
     *
     * @param  string $context View or edit context.
     * @return string
     */
    public function get_status( $context = 'view' ) {
        $status = $this->get_prop( 'status', $context );

        if ( empty( $status ) && 'view' === $context ) {

	        /**
	         * Filters the default Shipment status used as fallback.
	         *
	         * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
	         * unique hook for a shipment type.
	         *
	         * Example hook name: woocommerce_gzd_shipment_get_default_shipment_status
	         *
	         * @param string $status Default fallback status.
	         *
	         * @since 3.0.0
	         */
            $status = apply_filters( "{$this->get_hook_prefix()}}default_shipment_status", 'draft' );
        }

        return $status;
    }

	/**
	 * Checks whether the shipment has a specific status or not.
	 *
	 * @param  string $status The status to be checked against.
	 * @return boolean
	 */
    public function has_status( $status ) {
	    /**
	     * Filter to decide whether a Shipment has a certain status or not.
	     *
	     * @param boolean                                  $has_status Whether the Shipment has a status or not.
	     * @param Shipment $this The shipment object.
	     * @param string                                   $status The status to be checked against.
	     *
	     * @since 3.0.0
	     */
        return apply_filters( 'woocommerce_gzd_shipment_has_status', ( is_array( $status ) && in_array( $this->get_status(), $status, true ) ) || $this->get_status() === $status, $this, $status );
    }

    /**
     * Return the date this shipment was created.
     *
     * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
     * @return WC_DateTime|null object if the date is set or null if there is no date.
     */
    public function get_date_created( $context = 'view' ) {
        return $this->get_prop( 'date_created', $context );
    }

    /**
     * Return the date this shipment was sent.
     *
     * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
     * @return WC_DateTime|null object if the date is set or null if there is no date.
     */
    public function get_date_sent( $context = 'view' ) {
        return $this->get_prop( 'date_sent', $context );
    }

	/**
	 * Returns the shipment method.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_shipping_method( $context = 'view' ) {
		return $this->get_prop( 'shipping_method', $context );
	}

	/**
	 * Returns the shipment weight. In case view context was chosen and weight is not yet set, returns the content weight.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
    public function get_weight( $context = 'view' ) {
        $weight = $this->get_prop( 'weight', $context );

        if ( 'view' === $context && '' === $weight ) {
            return $this->get_content_weight();
        }

        return $weight;
    }

	/**
	 * Returns the shipment length. In case view context was chosen and length is not yet set, returns the content length.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
    public function get_length( $context = 'view' ) {
        $length = $this->get_prop( 'length', $context );

        if ( 'view' === $context && '' === $length ) {
            return $this->get_content_length();
        }

        return $length;
    }

	/**
	 * Returns the shipment width. In case view context was chosen and width is not yet set, returns the content width.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
    public function get_width( $context = 'view' ) {
        $width = $this->get_prop( 'width', $context );

        if ( 'view' === $context && '' === $width ) {
            return $this->get_content_width();
        }

        return $width;
    }

	/**
	 * Returns the shipment height. In case view context was chosen and height is not yet set, returns the content height.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
    public function get_height( $context = 'view' ) {
        $height = $this->get_prop( 'height', $context );

        if ( 'view' === $context && '' === $height ) {
            return $this->get_content_height();
        }

        return $height;
    }

	/**
	 * Returns the calculated weights for included items.
	 *
	 * @return float[]
	 */
    public function get_item_weights() {
        if ( is_null( $this->weights ) ) {
            $this->weights = array();

            foreach( $this->get_items() as $item ) {
                $this->weights[ $item->get_id() ] = ( ( $item->get_weight() === '' ? 0 : $item->get_weight() ) * $item->get_quantity() );
            }

            if ( empty( $this->weights ) ) {
                $this->weights = array( 0 );
            }
        }

        return $this->weights;
    }

	/**
	 * Returns the calculated lengths for included items.
	 *
	 * @return float[]
	 */
    public function get_item_lengths() {
        if ( is_null( $this->lengths ) ) {
            $this->lengths = array();

            foreach( $this->get_items() as $item ) {
                $this->lengths[ $item->get_id() ] = $item->get_length() === '' ? 0 : $item->get_length();
            }

            if ( empty( $this->lengths ) ) {
                $this->lengths = array( 0 );
            }
        }

        return $this->lengths;
    }

	/**
	 * Returns the calculated widths for included items.
	 *
	 * @return float[]
	 */
    public function get_item_widths() {
        if ( is_null( $this->widths ) ) {
            $this->widths = array();

            foreach( $this->get_items() as $item ) {
                $this->widths[ $item->get_id() ] = $item->get_width() === '' ? 0 : $item->get_width();
            }

            if ( empty( $this->widths ) ) {
                $this->widths = array( 0 );
            }
        }

        return $this->widths;
    }

	/**
	 * Returns the calculated heights for included items.
	 *
	 * @return float[]
	 */
    public function get_item_heights() {
        if ( is_null( $this->heights ) ) {
            $this->heights = array();

            foreach( $this->get_items() as $item ) {
                $this->heights[ $item->get_id() ] = $item->get_height() === '' ? 0 : $item->get_height();
            }

            if ( empty( $this->heights ) ) {
                $this->heights = array( 0 );
            }
        }

        return $this->heights;
    }

	/**
	 * Returns the calculated weight for included items.
	 *
	 * @return float
	 */
    public function get_content_weight() {
        return wc_format_decimal( array_sum( $this->get_item_weights() ) );
    }

	/**
	 * Returns the calculated length for included items.
	 *
	 * @return float
	 */
    public function get_content_length() {
        return wc_format_decimal( max( $this->get_item_lengths() ) );
    }

	/**
	 * Returns the calculated width for included items.
	 *
	 * @return float
	 */
    public function get_content_width() {
        return wc_format_decimal( max( $this->get_item_widths() ) );
    }

	/**
	 * Returns the calculated height for included items.
	 *
	 * @return float
	 */
    public function get_content_height() {
        return wc_format_decimal( max( $this->get_item_heights() ) );
    }

	/**
	 * Returns the shipping address properties.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string[]
	 */
    public function get_address( $context = 'view' ) {
        return $this->get_prop( 'address', $context );
    }

	/**
	 * Returns the shipment total.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return float
	 */
    public function get_total( $context = 'view' ) {
        return $this->get_prop( 'total', $context );
    }

	/**
	 * Returns the shipment tracking id.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
    public function get_tracking_id( $context = 'view' ) {
        return $this->get_prop( 'tracking_id', $context );
    }

	/**
	 * Returns the shipment tracking URL.
	 *
	 * @return string
	 */
	public function get_tracking_url() {
		/**
		 * Filter to adjust a Shipment's tracking URL.
		 *
		 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
		 * unique hook for a shipment type.
		 *
		 * Example hook name: woocommerce_gzd_shipment_get_tracking_url
		 *
		 * @param string                                   $tracking_url The tracking URL.
		 * @param Shipment $this The shipment object.
		 *
		 * @since 3.0.0
		 */
		return apply_filters( "{$this->get_hook_prefix()}tracking_url", '', $this );
	}

	/**
	 * Returns the shipment tracking instruction.
	 *
	 * @return string
	 */
	public function get_tracking_instruction() {
		$instruction = '';

		if ( $this->get_shipping_provider() && $this->get_tracking_id() ) {
			$instruction = sprintf( __( 'Your shipment is being processed by %s. If you want to track the shipment, please use the following tracking number: %s. Depending on the chosen shipping method it is possible that the tracking data does not reflect the current status when receiving this email.', 'woocommerce-germanized-shipments' ), wc_gzd_get_shipping_provider_title( $this->get_shipping_provider() ), $this->get_tracking_id() );
		}

		/**
		 * Filter to adjust a Shipment's tracking instruction.
		 *
		 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
		 * unique hook for a shipment type.
		 *
		 * Example hook name: woocommerce_gzd_shipment_get_tracking_instruction
		 *
		 * @param string                                   $instruction The tracking instruction.
		 * @param Shipment $this The shipment object.
		 *
		 * @since 3.0.0
		 */
		return apply_filters( "{$this->get_hook_prefix()}tracking_instruction", $instruction, $this );
	}

	/**
	 * Returns whether the current shipment has tracking instructions available or not.
	 *
	 * @return boolean
	 */
	public function has_tracking_instruction() {
    	$instruction = $this->get_tracking_instruction();

    	return ( ! empty( $instruction ) ) ? true : false;
	}

	/**
	 * Returns the shipment shipping provider.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
    public function get_shipping_provider( $context = 'view' ) {
	    return $this->get_prop( 'shipping_provider', $context );
    }

	/**
	 * Returns the formatted shipping address.
	 *
	 * @param  string $empty_content Content to show if no address is present.
	 * @return string
	 */
    public function get_formatted_address( $empty_content = '' ) {
        $address = WC()->countries->get_formatted_address( $this->get_address() );

        return $address ? $address : $empty_content;
    }

	/**
	 * Returns the shipment address phone number.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
    public function get_phone( $context = 'view' ) {
        return $this->get_address_prop( 'phone', $context );
    }

	/**
	 * Returns the shipment address email.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
    public function get_email( $context = 'view' ) {
        return $this->get_address_prop( 'email', $context );
    }

	/**
	 * Returns the shipment address first line.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
    public function get_address_1( $context = 'view' ) {
        return $this->get_address_prop( 'address_1', $context );
    }

	/**
	 * Returns the shipment address second line.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
    public function get_address_2( $context = 'view' ) {
        return $this->get_address_prop( 'address_2', $context );
    }

	/**
	 * Returns the shipment address street number by splitting the address.
	 *
	 * @param  string $type The address type e.g. address_1 or address_2.
	 *
	 * @return string
	 */
    public function get_address_street_number( $type = 'address_1' ) {
	    $split = wc_gzd_split_shipment_street( $this->{"get_$type"}() );

	    return $split['number'];
    }

	/**
	 * Returns the shipment address street without number by splitting the address.
	 *
	 * @param  string $type The address type e.g. address_1 or address_2.
	 *
	 * @return string
	 */
	public function get_address_street( $type = 'address_1' ) {
		$split = wc_gzd_split_shipment_street( $this->{"get_$type"}() );

		return $split['street'];
	}

	/**
	 * Returns the shipment address company.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
    public function get_company( $context = 'view' ) {
        return $this->get_address_prop( 'company', $context );
    }

	/**
	 * Returns the shipment address first name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
    public function get_first_name( $context = 'view' ) {
        return $this->get_address_prop( 'first_name', $context );
    }

	/**
	 * Returns the shipment address last name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
    public function get_last_name( $context = 'view' ) {
        return $this->get_address_prop( 'last_name', $context );
    }

	/**
	 * Returns the shipment address formatted full name.
	 *
	 * @return string
	 */
    public function get_formatted_full_name() {
	    return sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce-germanized-shipments' ), $this->get_first_name(), $this->get_last_name() );
    }

	/**
	 * Returns the shipment address postcode.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
    public function get_postcode( $context = 'view' ) {
        return $this->get_address_prop( 'postcode', $context );
    }

	/**
	 * Returns the shipment address city.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
    public function get_city( $context = 'view' ) {
        return $this->get_address_prop( 'city', $context );
    }

	/**
	 * Returns the shipment address state.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
    public function get_state( $context = 'view' ) {
        return $this->get_address_prop( 'state', $context );
    }

	/**
	 * Returns the shipment address country.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
    public function get_country( $context = 'view' ) {
        return $this->get_address_prop( 'country', $context ) ? $this->get_address_prop( 'country', $context ) : '';
    }

	/**
	 * Decides whether the shipment is sent to an external pickup or not.
	 *
	 * @param string[] $types
	 *
	 * @return boolean
	 */
    public function send_to_external_pickup( $types ) {
        $types = is_array( $types ) ? $types : array( $types );

	    /**
	     * Filter to decide whether a Shipment is to be sent to a external pickup location
	     * e.g. packstation.
	     *
	     * @param boolean                                  $external True if the Shipment goes to a pickup location.
	     * @param array                                    $types Array containing the types to be checked agains.
	     * @param Shipment $this The shipment object.
	     *
	     * @since 3.0.0
	     */
        return apply_filters( 'woocommerce_gzd_shipment_send_to_external_pickup', false, $types, $this );
    }

	/**
	 * Returns an address prop.
	 *
	 * @param string $prop
	 * @param string $context
	 *
	 * @return null|string
	 */
    protected function get_address_prop( $prop, $context = 'view' ) {
        $value = null;

        if ( isset( $this->changes['address'][ $prop ] ) || isset( $this->data['address'][ $prop ] ) ) {
            $value = isset( $this->changes['address'][ $prop ] ) ? $this->changes['address'][ $prop ] : $this->data['address'][ $prop ];

            if ( 'view' === $context ) {
	            /**
	             * Filter to adjust a Shipment's shipping address property e.g. first_name.
	             *
	             * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
	             * unique hook for a shipment type. `$prop` refers to the actual address property e.g. first_name.
	             *
	             * Example hook name: woocommerce_gzd_shipment_get_address_first_name
	             *
	             * @param string                                   $value The address property value.
	             * @param Shipment $this The shipment object.
	             *
	             * @since 3.0.0
	             */
                $value = apply_filters( "{$this->get_hook_prefix()}address_{$prop}", $value, $this );
            }
        }

        return $value;
    }

    /**
     * Returns dimensions.
     *
     * @return string|array
     */
    public function get_dimensions() {
        return array(
            'length' => $this->get_length(),
            'width'  => $this->get_width(),
            'height' => $this->get_height(),
        );
    }

	/**
	 * Returns whether the shipment is editable or not.
	 *
	 * @return boolean
	 */
    public function is_editable() {
	    /**
	     * Filter to dedice whether the current Shipment is still editable or not.
	     *
	     * @param boolean                                  $is_editable Whether the Shipment is editable or not.
	     * @param Shipment $this The shipment object.
	     *
	     * @since 3.0.0
	     */
        return apply_filters( 'woocommerce_gzd_shipment_is_editable', $this->has_status( wc_gzd_get_shipment_editable_statuses() ), $this );
    }

	/**
	 * Returns the shipment number.
	 *
	 * @return string
	 */
    public function get_shipment_number() {
	    /**
	     * Filter to adjust a Shipment's number.
	     *
	     * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
	     * unique hook for a shipment type.
	     *
	     * Example hook name: woocommerce_gzd_shipment_get_shipment_number
	     *
	     * @param string                                   $number The shipment number.
	     * @param Shipment $this The shipment object.
	     *
	     * @since 3.0.0
	     */
        return (string) apply_filters( "{$this->get_hook_prefix()}shipment_number", $this->get_id(), $this );
    }

    /*
    |--------------------------------------------------------------------------
    | Setters
    |--------------------------------------------------------------------------
    */

    /**
     * Set shipment status.
     *
     * @param string  $new_status Status to change the shipment to. No internal gzd- prefix is required.
     * @param boolean $manual_update Whether it is a manual status update or not.
     * @return array  details of change
     */
    public function set_status( $new_status, $manual_update = false ) {
        $old_status = $this->get_status();
        $new_status = 'gzd-' === substr( $new_status, 0, 4 ) ? substr( $new_status, 4 ) : $new_status;

        $this->set_prop( 'status', $new_status );

        $result = array(
            'from' => $old_status,
            'to'   => $new_status,
        );

        if ( true === $this->object_read && ! empty( $result['from'] ) && $result['from'] !== $result['to'] ) {
            $this->status_transition = array(
                'from'   => ! empty( $this->status_transition['from'] ) ? $this->status_transition['from'] : $result['from'],
                'to'     => $result['to'],
                'manual' => (bool) $manual_update,
            );

            if ( $manual_update ) {
	            /**
	             * Action that fires after a shipment status has been updated manually.
	             *
	             * @param integer $shipment_id The shipment id.
	             * @param string  $status The new shipment status.
	             *
	             * @since 3.0.0
	             */
                do_action( 'woocommerce_gzd_shipment_edit_status', $this->get_id(), $result['to'] );
            }

            $this->maybe_set_date_sent();
        }

        return $result;
    }

    /**
     * Maybe set date sent.
     *
     * Sets the date sent variable when transitioning to the shipped shipment status.
     * Date sent is set once in this manner - only when it is not already set.
     */
    public function maybe_set_date_sent() {
        // This logic only runs if the date_sent prop has not been set yet.
        if ( ! $this->get_date_sent( 'edit' ) ) {
            $sent_stati = wc_gzd_get_shipment_sent_stati();

            if ( $this->has_status( $sent_stati ) ) {

                // If payment complete status is reached, set paid now.
                $this->set_date_sent( current_time( 'timestamp', true ) );
            }
        }
    }

    /**
     * Updates status of shipment immediately.
     *
     * @uses Shipment::set_status()
     *
     * @param string $new_status    Status to change the shipment to. No internal gzd- prefix is required.
     * @param bool   $manual        Is this a manual order status change?
     * @return bool
     */
    public function update_status( $new_status, $manual = false ) {
        if ( ! $this->get_id() ) {
            return false;
        }

        try {
            $this->set_status( $new_status, $manual );
            $this->save();
        } catch ( Exception $e ) {
            $logger = wc_get_logger();
            $logger->error(
                sprintf( 'Error updating status for shipment #%d', $this->get_id() ), array(
                    'shipment' => $this,
                    'error'    => $e,
                )
            );
            return false;
        }
        return true;
    }

    /**
     * Set the date this shipment was created.
     *
     * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
     */
    public function set_date_created( $date = null ) {
        $this->set_date_prop( 'date_created', $date );
    }

    /**
     * Set the date this shipment was sent.
     *
     * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
     */
    public function set_date_sent( $date = null ) {
        $this->set_date_prop( 'date_sent', $date );
    }

	/**
	 * Set the date this shipment will be delivered.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_est_delivery_date( $date = null ) {
		$this->set_date_prop( 'est_delivery_date', $date );
	}

	/**
	 * Set shipment weight.
	 *
	 * @param string $weight The weight.
	 */
    public function set_weight( $weight ) {
        $this->set_prop( 'weight', '' === $weight ? '' : wc_format_decimal( $weight ) );
    }

	/**
	 * Set shipment width.
	 *
	 * @param string $width The width.
	 */
    public function set_width( $width ) {
        $this->set_prop( 'width', '' === $width ? '' : wc_format_decimal( $width ) );
    }

	/**
	 * Set shipment length.
	 *
	 * @param string $length The length.
	 */
    public function set_length( $length ) {
        $this->set_prop( 'length', '' === $length ? '' : wc_format_decimal( $length ) );
    }

	/**
	 * Set shipment height.
	 *
	 * @param string $height The height.
	 */
    public function set_height( $height ) {
        $this->set_prop( 'height', '' === $height ? '' : wc_format_decimal( $height ) );
    }

	/**
	 * Set shipment address.
	 *
	 * @param string[] $address The address props.
	 */
    public function set_address( $address ) {
        $this->set_prop( 'address', empty( $address ) ? array() : (array) $address );
    }

	/**
	 * Set shipment shipping method.
	 *
	 * @param string $method The shipping method.
	 */
	public function set_shipping_method( $method ) {
		$this->set_prop( 'shipping_method', $method );
	}

	/**
	 * Set shipment total.
	 *
	 * @param float|string $value The shipment total.
	 */
    public function set_total( $value ) {
        $value = wc_format_decimal( $value );

        if ( ! is_numeric( $value ) ) {
            $value = 0;
        }

        $this->set_prop( 'total', $value );
    }

	/**
	 * Set shipment shipping country.
	 *
	 * @param string $country The country in ISO format.
	 */
    public function set_country( $country ) {
        $address            = $this->get_address();
        $address['country'] = $country;

        $this->set_address( $address );
    }

	/**
	 * Set shipment tracking id.
	 *
	 * @param string $tracking_id The trakcing id.
	 */
    public function set_tracking_id( $tracking_id ) {
        $this->set_prop( 'tracking_id', $tracking_id );
    }

	/**
	 * Set shipment shipping provider.
	 *
	 * @param string $provider The shipping provider.
	 */
	public function set_shipping_provider( $provider ) {
		$this->set_prop( 'shipping_provider', wc_gzd_get_shipping_provider_slug( $provider ) );
	}

    /**
     * Return an array of items within this shipment.
     *
     * @return ShipmentItem[]
     */
    public function get_items() {
        $items = array();

        if ( is_null( $this->items ) ) {
            $this->items = array_filter( $this->data_store->read_items( $this ) );

            $items = (array) $this->items;
        } else {
            $items = (array) $this->items;
        }

	    /**
	     * Filter to adjust items belonging to a Shipment.
	     *
	     * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
	     * unique hook for a shipment type.
	     *
	     * Example hook name: woocommerce_gzd_shipment_get_items
	     *
	     * @param string                                   $number The shipment number.
	     * @param Shipment $this The shipment object.
	     *
	     * @since 3.0.0
	     */
        return apply_filters( "{$this->get_hook_prefix()}items", $items, $this );
    }

    /**
     * Get's the URL to edit the shipment in the backend.
     *
     * @return string
     */
    abstract public function get_edit_shipment_url();

    /**
     * Get an item object.
     *
     * @param  int  $item_id ID of item to get.
     *
     * @return ShipmentItem|false
     */
    public function get_item( $item_id ) {
        $items = $this->get_items();

        if ( isset( $items[ $item_id ] ) ) {
            return $items[ $item_id ];
        }

        return false;
    }

    /**
     * Remove item from the shipment.
     *
     * @param int $item_id Item ID to delete.
     *
     * @return false|void
     */
    public function remove_item( $item_id ) {
        $item = $this->get_item( $item_id );

        // Unset and remove later.
        $this->items_to_delete[] = $item;

        unset( $this->items[ $item->get_id() ] );

        $this->reset_content_data();
        $this->calculate_totals();
    }

    /**
     * Adds a shipment item to this shipment. The shipment item will not persist until save.
     *
     * @since 3.0.0
     * @param ShipmentItem $item Shipment item object.
     *
     * @return false|void
     */
    public function add_item( $item ) {
        // Make sure that items are loaded
        $items = $this->get_items();

        // Set parent.
        $item->set_shipment_id( $this->get_id() );

        // Append new row with generated temporary ID.
        $item_id = $item->get_id();

        if ( $item_id ) {
            $this->items[ $item_id ] = $item;
        } else {
            $this->items[ 'new:' . count( $this->items ) ] = $item;
        }

        $this->reset_content_data();
        $this->calculate_totals();
    }

	/**
	 * Reset item content data.
	 */
    protected function reset_content_data() {
        $this->weights = null;
        $this->lengths = null;
        $this->widths  = null;
        $this->heights = null;
    }

    /**
     * Handle the status transition.
     */
    protected function status_transition() {
        $status_transition = $this->status_transition;

        // Reset status transition variable.
        $this->status_transition = false;

        if ( $status_transition ) {
            try {
	            /**
	             * Action that fires before a shipment status transition happens.
	             *
	             * @param integer                                  $shipment_id The shipment id.
	             * @param Shipment $shipment The shipment object.
	             *
	             * @since 3.0.0
	             */
	            do_action( 'woocommerce_gzd_shipment_before_status_change', $this->get_id(), $this );

                $status_to = $status_transition['to'];

	            /**
	             * Action that indicates shipment status change to a specific status.
	             *
	             * The dynamic portion of the hook name, `$status_to` refers to the new shipment status.
	             *
	             * Example hook name: `woocommerce_gzd_shipment_status_processing`
	             *
	             * @param integer                                  $shipment_id The shipment id.
	             * @param Shipment $shipment The shipment object.
	             *
	             * @see wc_gzd_get_shipment_statuses()
	             *
	             * @since 3.0.0
	             */
                do_action( 'woocommerce_gzd_shipment_status_' . $status_to, $this->get_id(), $this );

                if ( ! empty( $status_transition['from'] ) ) {
                    $status_from = $status_transition['from'];

	                /**
	                 * Action that indicates shipment status change from a specific status to a specific status.
	                 *
	                 * The dynamic portion of the hook name, `$status_from` refers to the old shipment status.
	                 * `$status_to` refers to the new status.
	                 *
	                 * Example hook name: `woocommerce_gzd_shipment_status_processing_to_shipped`
	                 *
	                 * @param integer                                  $shipment_id The shipment id.
	                 * @param Shipment $shipment The shipment object.
	                 *
	                 * @see wc_gzd_get_shipment_statuses()
	                 *
	                 * @since 3.0.0
	                 */
                    do_action( 'woocommerce_gzd_shipment_status_' . $status_from . '_to_' . $status_to, $this->get_id(), $this );

	                /**
	                 * Action that indicates shipment status change.
	                 *
	                 * @param integer                                  $shipment_id The shipment id.
	                 * @param string                                   $status_from The old shipment status.
	                 * @param string                                   $status_to The new shipment status.
	                 * @param Shipment $shipment The shipment object.
	                 *
	                 * @see wc_gzd_get_shipment_statuses()
	                 *
	                 * @since 3.0.0
	                 */
                    do_action( 'woocommerce_gzd_shipment_status_changed', $this->get_id(), $status_from, $status_to, $this );
                }
            } catch ( Exception $e ) {
                $logger = wc_get_logger();
                $logger->error(
                    sprintf( 'Status transition of shipment #%d errored!', $this->get_id() ), array(
                        'shipment' => $this,
                        'error'    => $e,
                    )
                );
            }
        }
    }

    /**
     * Remove all items from the shipment.
     */
    public function remove_items() {
        $this->data_store->delete_items( $this );
        $this->items = array();

        $this->reset_content_data();
        $this->calculate_totals();
    }

    /**
     * Save all items which are part of this shipment.
     */
    protected function save_items() {
        $items_changed = false;

        foreach ( $this->items_to_delete as $item ) {
            $item->delete();
            $items_changed = true;
        }

        $this->items_to_delete = array();

        foreach ( $this->get_items() as $item_key => $item ) {
            $item->set_shipment_id( $this->get_id() );

            $item_id = $item->save();

            // If ID changed (new item saved to DB)...
            if ( $item_id !== $item_key ) {
                $this->items[ $item_id ] = $item;

                unset( $this->items[ $item_key ] );

                $items_changed = true;
            }
        }
    }

	/**
	 * Calculate totals based on contained items.
	 */
    protected function calculate_totals() {
        $total = 0;

        foreach( $this->get_items() as $item ) {
            $total += round( $item->get_total(), wc_get_price_decimals() );
        }

        $this->set_total( $total );
    }

    /**
     * Save data to the database.
     *
     * @return integer shipment id
     */
    public function save() {
        try {
            $this->calculate_totals();

            if ( $this->data_store ) {
                // Trigger action before saving to the DB. Allows you to adjust object props before save.
                do_action( 'woocommerce_before_' . $this->object_type . '_object_save', $this, $this->data_store );

                if ( $this->get_id() ) {
                    $this->data_store->update( $this );
                } else {
                    $this->data_store->create( $this );
                }
            }

            $this->save_items();
            $this->status_transition();
            $this->reset_content_data();
        } catch ( Exception $e ) {
            $logger = wc_get_logger();
            $logger->error(
                sprintf( 'Error saving shipment #%d', $this->get_id() ), array(
                    'shipment' => $this,
                    'error'    => $e,
                )
            );
        }

        return $this->get_id();
    }
}
