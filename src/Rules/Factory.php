<?php
namespace Vendidero\Germanized\Shipments\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * factory class
 */
class Factory {

	/**
	 * Get group.
	 *
	 * @param  mixed $group_id (default: false) Group ID to get.
	 * @return Group|bool
	 */
	public static function get_group( $group_id = false ) {
		$group_id = self::get_group_id( $group_id );

		if ( ! $group_id ) {
			return false;
		}

		$classname = 'Vendidero\Germanized\Shipments\Rules\Group';

		// Filter classname so that the class can be overridden if extended.
		$classname = apply_filters( 'woocommerce_gzd_shipment_rule_group_classname', $classname, $group_id );

		if ( ! class_exists( $classname ) ) {
			return false;
		}

		try {
			$group = new $classname( $group_id );

			/**
			 * Return false in case the group id of the object doesnt match the
			 * expected group id (e.g. does not exist any longer).
			 */
			if ( $group->get_id() < $group_id ) {
				return false;
			}
		} catch ( \Exception $e ) {
			wc_caught_exception( $e, __FUNCTION__, array( $group_id ) );
			return false;
		}
	}

	/**
	 * Get rule.
	 *
	 * @param int $rule_id Rule id to get.
	 * @return Rule|false if not found
	 */
	public static function get_rule( $rule_id = 0 ) {
		$rule_id   = self::get_rule_id( $rule_id );
		$rule_type = self::get_rule_type( $rule_id );

		if ( $rule_id && $rule_type ) {
			$classname = self::get_rule_classname( $rule_type );
			$classname = apply_filters( 'woocommerce_gzd_shipment_rule_classname', $classname, $rule_type, $rule_id );

			if ( $classname && class_exists( $classname ) ) {
				try {
					return new $classname( $rule_id );
				} catch ( \Exception $e ) {
					return false;
				}
			}
		}
		return false;
	}

	public static function get_rule_classname( $rule_type ) {
		$rules_types = wc_gzd_get_shipment_rule_types();

		if ( array_key_exists( $rule_type, $rules_types ) ) {
			$classname = $rules_types[ $rule_type ];
		} else {
			$rule_type_class = str_replace( '_', '', 'Rule' . ucwords( str_replace( '-', '_', sanitize_title( $rule_type ) ), '_' ) );
			$classname       = 'Vendidero\Germanized\Shipments\Rules\\' . $rule_type_class;
		}

		return $classname;
	}

	/**
	 * Get the group ID depending on what was passed.
	 *
	 * @since 3.0.0
	 * @param  mixed $group Group data to convert to an ID.
	 * @return int|bool false on failure
	 */
	public static function get_group_id( $group ) {
		if ( is_numeric( $group ) ) {
			return $group;
		} elseif ( $group instanceof Group ) {
			return $group->get_id();
		} elseif ( ! empty( $group->shipment_rule_group_id ) ) {
			return $group->shipment_rule_group_id;
		} else {
			return false;
		}
	}

	/**
	 * Get the rule ID depending on what was passed.
	 *
	 * @since 3.0.0
	 * @param  mixed $rule Rule data to convert to an ID.
	 * @return int|bool false on failure
	 */
	public static function get_rule_id( $rule ) {
		if ( is_numeric( $rule ) ) {
			return $rule;
		} elseif ( $rule instanceof Rule ) {
			return $rule->get_id();
		} elseif ( ! empty( $rule->shipment_rule_id ) ) {
			return $rule->shipment_rule_id;
		} else {
			return false;
		}
	}

	/**
	 * Get the rule type depending on what was passed.
	 *
	 * @since 3.0.0
	 * @param  mixed $rule Rule data to convert to an ID.
	 * @return string|bool false on failure
	 */
	public static function get_rule_type( $rule ) {
		if ( ! empty( $rule->shipment_rule_type ) ) {
			return $rule->shipment_rule_type;
		}

		if ( $rule_id = self::get_rule_id( $rule ) ) {
			return \WC_Data_Store::load( 'shipment-rule' )->get_rule_type( $rule_id );
		}

		return false;
	}
}
