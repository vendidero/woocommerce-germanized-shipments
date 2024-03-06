<?php

namespace Vendidero\Germanized\Shipments\PickPack;

use Vendidero\Germanized\Shipments\Package;

defined( 'ABSPATH' ) || exit;

class Helper {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ), 25 );
		add_action( 'admin_head', array( __CLASS__, 'hide_page_from_menu' ) );

		add_action( 'woocommerce_gzd_shipment_pick_pack_order_setup', array( __CLASS__, 'setup_order' ), 10, 1 );
	}

	public static function setup_order( $order_id ) {
		Package::log( 'Queue setup: ' . $order_id, 'info', 'pick-pack-orders' );

		if ( $order = self::get_pick_pack_order( $order_id ) ) {
			if ( $order->needs_setup() ) {
				$order->setup();
			}
		}
	}

	public static function add_page() {
		add_submenu_page( 'woocommerce', _x( 'Pick & Pack', 'shipments', 'woocommerce-germanized-shipments' ), _x( 'Pick & Pack', 'shipments', 'woocommerce-germanized-shipments' ), 'manage_woocommerce', 'shipments-pick-pack', array( __CLASS__, 'render_pick_pack' ) );
	}

	public static function hide_page_from_menu() {
		remove_submenu_page( 'woocommerce', 'shipments-pick-pack' );
	}

	public static function render_pick_pack() {
		if ( isset( $_GET['pick-pack-order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$pick_pack_order = isset( $_GET['pick-pack-order'] ) ? wc_clean( wp_unslash( $_GET['pick-pack-order'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( ! $pick_pack_order ) {
				return;
			}

			if ( ! $pick_pack_order = self::get_pick_pack_order( $pick_pack_order ) ) {
				return;
			}

			self::render_pick_pack_order( $pick_pack_order );
		} else {
			self::render_new_pick_pack_order();
		}
	}

	/**
	 * @param Order $pick_pack_order
	 *
	 * @return void
	 */
	protected static function render_pick_pack_order( $pick_pack_order ) {
		?>
		<div class="wrap woocommerce woocommerce-pick-pack-order woocommerce-pick-pack-order-<?php echo esc_attr( $pick_pack_order->get_type() ); ?>" data-id="<?php echo esc_attr( $pick_pack_order->get_id() ); ?>" data-type="<?php echo esc_attr( $pick_pack_order->get_type() ); ?>">
			<div class="pick-pack-order">
				<form class="pick-pack-order-form pick-pack-order-form">
					<header>
						<div class="progress-bar-wrapper">
							<progress max="100" value="<?php echo esc_attr( $pick_pack_order->get_percentage() ); ?>"></progress>
							<span class="progress-desc"><?php printf( _x( 'Processed %1$d of approximately %2$d orders', 'shipments', 'woocommerce-germanized-shipments' ), $pick_pack_order->get_offset(), $pick_pack_order->get_total() ); ?></span>
						</div>

						<h2><?php printf( _x( 'Order #%1$s: %2$s', 'shipments', 'woocommerce-germanized-shipments' ), esc_html( $pick_pack_order->get_current_order_number() ), $pick_pack_order->get_current_task_name() ); ?></h2>
					</header>
				</form>
			</div>
		</div>
		<?php
	}

	protected static function render_new_pick_pack_order() {
		?>
		<div class="wrap woocommerce woocommerce-pick-pack-order">
			<div class="pick-pack-order">
				<form class="pick-pack-order-form pick-pack-order-form-create">
					<header>
						<h2><?php echo esc_html_x( 'Pick & Pack', 'shipments', 'woocommerce-germanized-shipments' ); ?></h2>
						<p><?php echo esc_html_x( 'Start your pick & pack process and select which orders to include.', 'shipments', 'woocommerce-germanized-shipments' ); ?></p>
					</header>
					<section>
						<div class="notice-wrapper"></div>

						<fieldset class="form-row" id="pick-pack-order-type-select">
							<legend><?php echo esc_html_x( 'Your preferred way to export', 'shipments', 'woocommerce-germanized-shipments' ); ?></legend>

							<?php foreach ( self::get_available_types() as $type => $type_data ) : ?>
								<div>
									<input type="radio" id="order_type_<?php echo esc_attr( $type ); ?>" name="order_type" value="<?php echo esc_attr( $type ); ?>" <?php checked( 'manual', $type ); ?> />
									<label for="order_type_<?php echo esc_attr( $type ); ?>">
										<span class="title"><?php echo esc_html( $type_data['label'] ); ?></span>
										<span class="description"><?php echo esc_html( $type_data['description'] ); ?></span>
									</label>
								</div>
							<?php endforeach; ?>
						</fieldset>

						<fieldset class="form-row" id="pick-pack-order-date-select">
							<legend><?php echo esc_html_x( 'Date range', 'shipments', 'woocommerce-germanized-shipments' ); ?></legend>

							<div>
								<input type="text" size="11" placeholder="yyyy-mm-dd" value="<?php echo esc_attr( date_i18n( 'Y-m-d' ) ); ?>" name="date_from" class="range_datepicker from" autocomplete="off" /><?php //@codingStandardsIgnoreLine ?>
								<span>&ndash;</span>
								<input type="text" size="11" placeholder="yyyy-mm-dd" value="<?php echo esc_attr( date_i18n( 'Y-m-d' ) ); ?>" name="date_end" class="range_datepicker to" autocomplete="off" /><?php //@codingStandardsIgnoreLine ?>
							</div>
						</fieldset>

						<?php foreach ( array_keys( self::get_available_types() ) as $pick_pack_type ) : ?>
							<fieldset class="form-row show-hide-pick-pack-order form-row-pick-pack-order-<?php echo esc_attr( $pick_pack_type ); ?>" id="order-<?php echo esc_attr( $pick_pack_type ); ?>-tasks">
								<legend><?php echo esc_html_x( 'Tasks', 'shipments', 'woocommerce-germanized-shipments' ); ?></legend>

								<?php foreach ( self::get_available_tasks( $pick_pack_type ) as $task_name => $task ) : ?>
									<div>
										<input type="checkbox" id="order_<?php echo esc_attr( $pick_pack_type ); ?>_task_<?php echo esc_attr( $task['name'] ); ?>" name="order_<?php echo esc_attr( $pick_pack_type ); ?>_tasks[]" value="<?php echo esc_attr( $task['name'] ); ?>" />
										<label for="order_<?php echo esc_attr( $pick_pack_type ); ?>_task_<?php echo esc_attr( $task['name'] ); ?>">
											<?php echo esc_attr( $task['title'] ); ?>
											<?php if ( ! empty( $task['description'] ) ) : ?>
												<?php echo wc_help_tip( $task['description'] ); ?>
											<?php endif; ?>
										</label>
									</div>
								<?php endforeach; ?>
							</fieldset>
						<?php endforeach; ?>
					</section>
					<footer>
						<button type="submit" class="pick-pack-order-export-button button button-primary" value="<?php echo esc_attr_x( 'Start', 'shipments', 'woocommerce-germanized-shipments' ); ?>"><?php echo esc_attr_x( 'Start', 'shipments', 'woocommerce-germanized-shipments' ); ?></button>
					</footer>
				</form>
			</div>
		</div>
		<?php
	}

	public static function get_pick_pack_order( $order ) {
		return Factory::get_pick_pack_order( $order );
	}

	public static function create_pick_pack_order( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'tasks' => array(),
				'query' => array(),
				'limit' => 10,
			)
		);

		if ( $order = Factory::get_pick_pack_order( 0 ) ) {
			$order->set_tasks( $args['tasks'] );
			$order->set_query( $args['query'] );
			$order->set_limit( $args['limit'] );
			$order->save();

			return $order;
		}

		return false;
	}

	public static function get_available_statuses() {
		return array(
			'gzd-created'     => _x( 'Created', 'shipments-pick-pack-status', 'woocommerce-germanized-shipments' ),
			'gzd-running'     => _x( 'Running', 'shipments-pick-pack-status', 'woocommerce-germanized-shipments' ),
			'gzd-paused'      => _x( 'Paused', 'shipments-pick-pack-status', 'woocommerce-germanized-shipments' ),
			'gzd-idling'      => _x( 'Idling', 'shipments-pick-pack-status', 'woocommerce-germanized-shipments' ),
			'gzd-doing-setup' => _x( 'Doing setup', 'shipments-pick-pack-status', 'woocommerce-germanized-shipments' ),
			'gzd-completed'   => _x( 'Completed', 'shipments-pick-pack-status', 'woocommerce-germanized-shipments' ),
		);
	}

	public static function get_available_types() {
		return array(
			'loop' => array(
				'class_name'  => '\Vendidero\Germanized\Shipments\PickPack\Loop',
				'label'       => _x( 'Loop', 'shipments-pick-pack-type', 'shipments' ),
				'description' => _x( 'Process orders step by step. Manually go through the pick & pack steps.', 'shipments', 'woocommerce-germanized-shipments' ),
			),
		);
	}

	public static function get_type( $type ) {
		$available_types = self::get_available_types();

		return array_key_exists( $type, $available_types ) ? $available_types[ $type ] : false;
	}
}
