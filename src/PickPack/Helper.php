<?php

namespace Vendidero\Germanized\Shipments\PickPack;

defined( 'ABSPATH' ) || exit;

class Helper {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ), 25 );
		add_action( 'admin_head', array( __CLASS__, 'hide_page_from_menu' ) );
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
		} else {
			self::render_new_pick_pack_order();
		}
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

								<?php
								foreach ( self::get_available_tasks( $pick_pack_type, true ) as $task_name => $task ) :
									?>
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
				'type'  => 'manual',
				'tasks' => array(),
				'query' => array(),
				'limit' => 1,
			)
		);

		if ( $order = Factory::get_pick_pack_order( 0, $args['type'] ) ) {
			$order->set_tasks( $args['tasks'] );
			$order->set_query( $args['query'] );
			$order->set_limit( $args['limit'] );
			$order->save();

			return $order;
		}

		return false;
	}

	public static function get_available_types() {
		return array(
			'manual' => array(
				'class_name'  => '\Vendidero\Germanized\Shipments\PickPack\ManualOrder',
				'label'       => _x( 'Manual', 'shipments-pick-pack-type', 'shipments' ),
				'description' => _x( 'Process orders step by step. Manually go through the pick & pack steps.', 'shipments', 'woocommerce-germanized-shipments' ),
			),
			'auto'   => array(
				'class_name'  => '\Vendidero\Germanized\Shipments\PickPack\AutoOrder',
				'label'       => _x( 'Auto', 'shipments-pick-pack-type', 'shipments' ),
				'description' => _x( 'Automatically process orders and create export files, as chosen.', 'shipments', 'woocommerce-germanized-shipments' ),
			),
		);
	}

	public static function get_type( $type ) {
		$available_types = self::get_available_types();

		return array_key_exists( $type, $available_types ) ? $available_types[ $type ] : false;
	}

	public static function get_available_statuses() {
		return array(
			'gzd-created'   => _x( 'Created', 'shipments-pick-pack-status', 'woocommerce-germanized-shipments' ),
			'gzd-running'   => _x( 'Running', 'shipments-pick-pack-status', 'woocommerce-germanized-shipments' ),
			'gzd-paused'    => _x( 'Paused', 'shipments-pick-pack-status', 'woocommerce-germanized-shipments' ),
			'gzd-completed' => _x( 'Completed', 'shipments-pick-pack-status', 'woocommerce-germanized-shipments' ),
		);
	}

	public static function get_available_tasks( $for_type = 'all', $optional_only = false ) {
		$tasks = array(
			'create_shipments'   => array(
				'title'            => _x( 'Create shipments', 'shipments', 'woocommerce-germanized-shipments' ),
				'title_processing' => _x( 'Creating shipments', 'shipments', 'woocommerce-germanized-shipments' ),
				'priority'         => 10,
				'mandatory'        => true,
			),
			'pick_pack_items'    => array(
				'title'           => _x( 'Pick & pack items', 'shipments', 'woocommerce-germanized-shipments' ),
				'priority'        => 30,
				'supported_types' => array( 'manual' ),
			),
			'create_labels'      => array(
				'title'            => _x( 'Create labels', 'shipments', 'woocommerce-germanized-shipments' ),
				'title_processing' => _x( 'Creating labels', 'shipments', 'woocommerce-germanized-shipments' ),
				'priority'         => 20,
			),
			'update_status_sent' => array(
				'title'    => _x( 'Update status to sent', 'shipments', 'woocommerce-germanized-shipments' ),
				'priority' => 40,
			),
		);

		foreach ( $tasks as $task_name => $task_data ) {
			$tasks[ $task_name ] = wp_parse_args(
				$task_data,
				array(
					'title'            => '',
					'title_processing' => '',
					'priority'         => 50,
					'depends_on'       => '',
					'description'      => '',
					'mandatory'        => false,
					'supported_types'  => array_keys( self::get_available_types() ),
				)
			);

			if ( empty( $tasks[ $task_name ]['title_processing'] ) ) {
				$tasks[ $task_name ]['title_processing'] = $tasks[ $task_name ]['title'];
			}

			$tasks[ $task_name ]['supported_types'] = (array) $tasks[ $task_name ]['supported_types'];
			$tasks[ $task_name ]['name']            = $task_name;

			if ( 'all' !== $for_type && ! in_array( $for_type, $tasks[ $task_name ]['supported_types'], true ) ) {
				unset( $tasks[ $task_name ] );
				continue;
			}

			if ( $optional_only && $tasks[ $task_name ]['mandatory'] ) {
				unset( $tasks[ $task_name ] );
				continue;
			}
		}

		return $tasks;
	}

	public static function get_task( $task_name, $for_type = false ) {
		$tasks = self::get_available_tasks( $for_type );

		return array_key_exists( $task_name, $tasks ) ? $tasks[ $task_name ] : false;
	}
}
