<?php

namespace Vendidero\Germanized\Shipments\Admin;
use Vendidero\Germanized\Shipments\Shipment;
use WP_List_Table;
use Vendidero\Germanized\Shipments\ShipmentQuery;

defined( 'ABSPATH' ) || exit;

/**
 * Class Table
 * @package Vendidero\Germanized\Shipments\Admin
 */
class Table extends WP_List_Table {

    protected $query = null;

    protected $stati = array();

    protected $counts = array();

    protected $notice = array();

    /**
     * Constructor.
     *
     * @since 3.1.0
     *
     * @see WP_List_Table::__construct() for more information on default arguments.
     *
     * @global WP_Post_Type $post_type_object
     * @global wpdb         $wpdb
     *
     * @param array $args An associative array of arguments.
     */
    public function __construct( $args = array() ) {
        add_action( 'admin_notices', array( $this, 'bulk_admin_notices' ) );
        add_filter( 'removable_query_args', array( $this, 'enable_query_removing' ) );

        parent::__construct(
            array(
                'plural' => 'shipments',
                'screen' => isset( $args['screen'] ) ? $args['screen'] : null,
            )
        );
    }

    public function enable_query_removing( $args ) {
        $args = array_merge( $args, array(
            'changed',
            'bulk_action'
        ) );

        return $args;
    }

    /**
     * Handle bulk actions.
     *
     * @param  string $redirect_to URL to redirect to.
     * @param  string $action      Action name.
     * @param  array  $ids         List of ids.
     * @return string
     */
    public function handle_bulk_actions( $action, $ids, $redirect_to ) {
        $ids         = array_reverse( array_map( 'absint', $ids ) );
        $changed     = 0;

        if ( false !== strpos( $action, 'mark_' ) ) {

            $shipment_statuses = wc_gzd_get_shipment_statuses();
            $new_status        = substr( $action, 5 ); // Get the status name from action.

            // Sanity check: bail out if this is actually not a status, or is not a registered status.
            if ( isset( $shipment_statuses[ 'gzd-' . $new_status ] ) ) {

                foreach ( $ids as $id ) {

                    if ( $shipment = wc_gzd_get_shipment( $id ) ) {
                        $shipment->update_status( $new_status, true );

                        do_action( 'woocommerce_gzd_shipment_edit_status', $id, $new_status );
                        $changed++;
                    }
                }
            }
        } elseif( 'delete' === $action ) {
            foreach ( $ids as $id ) {
                if ( $shipment = wc_gzd_get_shipment( $id ) ) {
                    $shipment->delete( true );
                    $changed++;
                }
            }
        }

        $changed = apply_filters( 'woocommerce_gzd_shipments_bulk_action', $changed, $action, $ids, $redirect_to, $this );

        if ( $changed ) {
            $redirect_to = add_query_arg(
                array(
                    'changed'     => $changed,
                    'ids'         => join( ',', $ids ),
                    'bulk_action' => $action
                ),
                $redirect_to
            );
        }

        return esc_url_raw( $redirect_to );
    }

    public function output_notice() {

        if ( ! empty( $this->notice ) ) {
            $type = isset( $this->notice['type'] ) ? $this->notice['type'] : 'success';

            echo '<div id="message" class="' . ( 'success' === $type ? 'updated' : $type ) . ' notice is-dismissible">' . wpautop( $this->notice['message'] ) . ' <button type="button" class="notice-dismiss"></button></div>';
        }

        $this->notice = array();
    }

    /**
     * Show confirmation message that order status changed for number of orders.
     */
    public function set_bulk_notice() {

        $number            = isset( $_REQUEST['changed'] ) ? absint( $_REQUEST['changed'] ) : 0; // WPCS: input var ok, CSRF ok.
        $bulk_action       = isset( $_REQUEST['bulk_action'] ) ? wc_clean( wp_unslash( $_REQUEST['bulk_action'] ) ) : ''; // WPCS: input var ok, CSRF ok.

        if ( 'delete' === $bulk_action ) {

            $this->set_notice( sprintf( _nx( '%d shipment deleted.', '%d shipments deleted.', $number, 'shipments', 'woocommerce-germanized-shipments' ), number_format_i18n( $number ) ) );

        } elseif( strpos( $bulk_action, 'mark_' ) !== false ) {

            $shipment_statuses = wc_gzd_get_shipment_statuses();

            // Check if any status changes happened.
            foreach ( $shipment_statuses as $slug => $name ) {

                if ( 'mark_' . str_replace( 'gzd-', '', $slug ) === $bulk_action ) { // WPCS: input var ok, CSRF ok.
                    $this->set_notice( sprintf( _nx( '%d shipment status changed.', '%d shipment statuses changed.', $number, 'shipments', 'woocommerce-germanized-shipments' ), number_format_i18n( $number ) ) );
                    break;
                }
            }
        }

        do_action( 'woocommerce_gzd_shipments_bulk_notice', $bulk_action, $this );
    }

    public function set_notice( $message, $type = 'success' ) {
        $this->notice = array(
            'message' => $message,
            'type'    => $type,
        );
    }

    protected function get_stati() {
        return $this->stati;
    }

    /**
     * @return bool
     */
    public function ajax_user_can() {
        return current_user_can( 'edit_shop_orders' );
    }

    /**
     * @global array    $avail_post_stati
     * @global WP_Query $wp_query
     * @global int      $per_page
     * @global string   $mode
     */
    public function prepare_items() {
        global $per_page;

        $per_page        = $this->get_items_per_page( 'woocommerce_page_wc_gzd_shipments_per_page', 10 );

        $per_page        = apply_filters( 'woocommerce_gzd_shipments_edit_per_page', $per_page, 'shipment' );
        $this->stati     = wc_gzd_get_shipment_statuses();
        $this->counts    = wc_gzd_get_shipment_counts();
        $paged           = $this->get_pagenum();

        $args = array(
            'limit'       => $per_page,
            'paginate'    => true,
            'offset'      => ( $paged - 1 ) * $per_page,
            'count_total' => true,
        );

        if ( isset( $_REQUEST['shipment_status'] ) && in_array( $_REQUEST['shipment_status'], array_keys( $this->stati ) ) ) {
            $args['status'] = wc_clean( wp_unslash( $_REQUEST['shipment_status'] ) );
        }

        if ( isset( $_REQUEST['orderby'] ) ) {
            $args['orderby'] = wc_clean( wp_unslash( $_REQUEST['orderby'] ) );
        }

        if ( isset( $_REQUEST['order'] ) ) {
            $args['order'] = 'asc' === $_REQUEST['order'] ? 'ASC' : 'DESC';
        }

        if ( isset( $_REQUEST['order_id'] ) && ! empty( $_REQUEST['order_id'] ) ) {
            $args['order_id'] = absint( $_REQUEST['order_id'] );
        }

        if ( isset( $_REQUEST['m'] ) ) {
            $m          = wc_clean( wp_unslash( $_REQUEST['m'] ) );
            $year       = substr( $m, 0, 4 );

            if ( ! empty( $year ) ) {
                $month      = '';
                $day        = '';

                if ( strlen( $m ) > 5 ) {
                    $month = substr( $m, 4, 2 );
                }

                if ( strlen( $m ) > 7 ) {
                    $day = substr( $m, 6, 2 );
                }

                $datetime = new WC_DateTime();
                $datetime->setDate( $year, 1, 1 );

                if ( ! empty( $month ) ) {
                    $datetime->setDate( $year, $month, 1 );
                }

                if ( ! empty( $day ) ) {
                    $datetime->setDate( $year, $month, $day );
                }

                $next_month = clone $datetime;
                $next_month->modify( '+ 1 month' );

                $args['date_created'] = $datetime->format( 'Y-m-d' ) . '...' . $next_month->format( 'Y-m-d' );
            }
        }

        if ( isset( $_REQUEST['s'] ) ) {
            $args['search'] = wc_clean( wp_unslash( $_REQUEST['s'] ) );
        }

        // Query the user IDs for this page
        $this->query = new ShipmentQuery( $args );
        $this->items = $this->query->get_shipments();

        $this->set_pagination_args(
            array(
                'total_items' => $this->query->get_total(),
                'per_page'    => $per_page,
            )
        );
    }

    /**
     */
    public function no_items() {
        echo _x( 'No shipments found', 'shipments', 'woocommerce-germanized-shipments' );
    }

    /**
     * Determine if the current view is the "All" view.
     *
     * @since 4.2.0
     *
     * @return bool Whether the current view is the "All" view.
     */
    protected function is_base_request() {
        $vars = $_GET;
        unset( $vars['paged'] );

        if ( empty( $vars ) ) {
            return true;
        }

        return 1 === count( $vars );
    }

    /**
     * @global array $locked_post_status This seems to be deprecated.
     * @global array $avail_post_stati
     * @return array
     */
    protected function get_views() {

        $status_links     = array();
        $num_shipments    = $this->counts;
        $total_shipments  = array_sum( (array) $num_shipments );
        $class            = '';
        $all_args         = array();

        if ( empty( $class ) && ( $this->is_base_request() || isset( $_REQUEST['all_shipments'] ) ) ) {
            $class = 'current';
        }

        $all_inner_html = sprintf(
            _nx(
                'All <span class="count">(%s)</span>',
                'All <span class="count">(%s)</span>',
                $total_shipments,
                'shipments',
                'woocommerce-germanized-shipments'
            ),
            number_format_i18n( $total_shipments )
        );

        $status_links['all'] = $this->get_edit_link( $all_args, $all_inner_html, $class );

        foreach ( wc_gzd_get_shipment_statuses() as $status => $title ) {
            $class = '';

            if ( ! in_array( $status, array_keys( $this->stati ) ) || empty( $num_shipments[ $status ] ) ) {
                continue;
            }

            if ( isset( $_REQUEST['shipment_status'] ) && $status === $_REQUEST['shipment_status'] ) {
                $class = 'current';
            }

            $status_args = array(
                'shipment_status' => $status,
            );

            $status_label = sprintf(
                translate_nooped_plural( _nx_noop( $title . ' <span class="count">(%s)</span>', $title . ' <span class="count">(%s)</span>', 'shipments', 'woocommerce-germanized-shipments' ), $num_shipments[ $status ] ),
                number_format_i18n( $num_shipments[ $status ] )
            );

            $status_links[ $status ] = $this->get_edit_link( $status_args, $status_label, $class );
        }

        return $status_links;
    }

    /**
     * Helper to create links to edit.php with params.
     *
     * @since 4.4.0
     *
     * @param string[] $args  Associative array of URL parameters for the link.
     * @param string   $label Link text.
     * @param string   $class Optional. Class attribute. Default empty string.
     * @return string The formatted link string.
     */
    protected function get_edit_link( $args, $label, $class = '' ) {
        $url = add_query_arg( $args, 'admin.php?page=wc-gzd-shipments' );

        $class_html = $aria_current = '';
        if ( ! empty( $class ) ) {
            $class_html = sprintf(
                ' class="%s"',
                esc_attr( $class )
            );

            if ( 'current' === $class ) {
                $aria_current = ' aria-current="page"';
            }
        }

        return sprintf(
            '<a href="%s"%s%s>%s</a>',
            esc_url( $url ),
            $class_html,
            $aria_current,
            $label
        );
    }

    /**
     * @return string
     */
    public function current_action() {
        if ( isset( $_REQUEST['delete_all'] ) || isset( $_REQUEST['delete_all2'] ) ) {
            return 'delete_all';
        }

        return parent::current_action();
    }

    /**
     * Display a monthly dropdown for filtering items
     *
     * @since 3.1.0
     *
     * @global wpdb      $wpdb
     * @global WP_Locale $wp_locale
     *
     * @param string $post_type
     */
    protected function months_dropdown( $type ) {
        global $wpdb, $wp_locale;

        $extra_checks = "AND shipment_status != 'auto-draft'";

        if ( isset( $_GET['shipment_status'] ) && 'all' !== $_GET['shipment_status'] ) {
            $extra_checks = $wpdb->prepare( ' AND shipment_status = %s', $_GET['shipment_status'] );
        }

        $months = $wpdb->get_results("
            SELECT DISTINCT YEAR( shipment_date_created ) AS year, MONTH( shipment_date_created ) AS month
            FROM $wpdb->gzd_shipments
            WHERE 1=1
            $extra_checks
            ORDER BY shipment_date_created DESC
		" );

        $month_count = count( $months );

        if ( ! $month_count || ( 1 == $month_count && 0 == $months[0]->month ) ) {
            return;
        }

        $m = isset( $_GET['m'] ) ? (int) $_GET['m'] : 0;
        ?>
        <label for="filter-by-date" class="screen-reader-text"><?php echo _x( 'Filter by date', 'shipments', 'woocommerce-germanized-shipments'); ?></label>
        <select name="m" id="filter-by-date">
            <option<?php selected( $m, 0 ); ?> value="0"><?php echo _x( 'All dates', 'shipments', 'woocommerce-germanized-shipments' ); ?></option>
            <?php
            foreach ( $months as $arc_row ) {
                if ( 0 == $arc_row->year ) {
                    continue;
                }

                $month = zeroise( $arc_row->month, 2 );
                $year  = $arc_row->year;

                printf(
                    "<option %s value='%s'>%s</option>\n",
                    selected( $m, $year . $month, false ),
                    esc_attr( $arc_row->year . $month ),
                    /* translators: 1: month name, 2: 4-digit year */
                    sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year )
                );
            }
            ?>
        </select>
        <?php
    }

    /**
     * @param string $which
     */
    protected function extra_tablenav( $which ) {
        ?>
        <div class="alignleft actions">
            <?php
            if ( 'top' === $which && ! is_singular() ) {
                ob_start();

                $this->months_dropdown( 'shipment' );
                $this->order_filter();

                /**
                 * Fires before the Filter button on the Posts and Pages list tables.
                 *
                 * The Filter button allows sorting by date and/or category on the
                 * Posts list table, and sorting by date on the Pages list table.
                 *
                 * @since 2.1.0
                 * @since 4.4.0 The `$post_type` parameter was added.
                 * @since 4.6.0 The `$which` parameter was added.
                 *
                 * @param string $post_type The post type slug.
                 * @param string $which     The location of the extra table nav markup:
                 *                          'top' or 'bottom' for WP_Posts_List_Table,
                 *                          'bar' for WP_Media_List_Table.
                 */
                do_action( 'woocommerce_gzd_shipments_filters', $which );

                $output = ob_get_clean();

                if ( ! empty( $output ) ) {
                    echo $output;

                    submit_button( _x( 'Filter', 'shipments', 'woocommerce-germanized-shipments' ), '', 'filter_action', false, array( 'id' => 'shipment-query-submit' ) );
                }
            }
            ?>
        </div>
        <?php
        /**
         * Fires immediately following the closing "actions" div in the tablenav for the posts
         * list table.
         *
         * @since 4.4.0
         *
         * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
         */
        do_action( 'manage_posts_extra_tablenav', $which );
    }

    protected function order_filter() {
        $order_id     = '';
        $order_string = '';

        if ( ! empty( $_GET['order_id'] ) ) { // phpcs:disable  WordPress.Security.NonceVerification.NoNonceVerification
            $order_id     = absint( $_GET['order_id'] ); // WPCS: input var ok, sanitization ok.
            $order_string = sprintf(
                esc_html_x( 'Order #%s', 'shipments', 'woocommerce-germanized-shipments' ),
                $order_id
            );
        }
        ?>
        <select class="wc-gzd-order-search" name="order_id" data-placeholder="<?php echo esc_attr_x( 'Filter by order', 'shipments', 'woocommerce-germanized-shipments' ); ?>" data-allow_clear="true">
            <option value="<?php echo esc_attr( $order_id ); ?>" selected="selected"><?php echo htmlspecialchars( wp_kses_post( $order_string ) ); // htmlspecialchars to prevent XSS when rendered by selectWoo. ?><option>
        </select>
        <?php
    }

    /**
     * @return array
     */
    protected function get_table_classes() {
        return array( 'widefat', 'fixed', 'striped', 'posts', 'shipments' );
    }

    /**
     * @return array
     */
    public function get_columns() {

        $columns               = array();
        $columns['cb']         = '<input type="checkbox" />';
        $columns['title']      = _x( 'Title', 'shipments', 'woocommerce-germanized-shipments' );
        $columns['date']       = _x( 'Date', 'shipments', 'woocommerce-germanized-shipments' );
        $columns['status']     = _x( 'Status', 'shipments', 'woocommerce-germanized-shipments' );
        $columns['items']      = _x( 'Items', 'shipments', 'woocommerce-germanized-shipments' );
        $columns['address']    = _x( 'Address', 'shipments', 'woocommerce-germanized-shipments' );
        // $columns['weight']     = _x( 'Weight', 'shipments', 'woocommerce-germanized-shipments' );
        // $columns['dimensions'] = _x( 'Dimensions', 'shipments', 'woocommerce-germanized-shipments' );
        $columns['order']      = _x( 'Order', 'shipments', 'woocommerce-germanized-shipments' );
        $columns['actions']    = _x( 'Actions', 'shipments', 'woocommerce-germanized-shipments' );

        /**
         * Filters the columns displayed in the Posts list table.
         *
         * @since 1.5.0
         *
         * @param string[] $post_columns An associative array of column headings.
         * @param string   $post_type    The post type slug.
         */
        $columns = apply_filters( 'woocommerce_gzd_manage_shipments_columns', $columns );

        return $columns;
    }

    /**
     * @return array
     */
    protected function get_sortable_columns() {
        return array(
            'date'     => array( 'date_created', false ),
            'weight'   => 'weight',
        );
    }

    /**
     * Gets the name of the default primary column.
     *
     * @since 4.3.0
     *
     * @return string Name of the default primary column, in this case, 'title'.
     */
    protected function get_default_primary_column_name() {
        return 'title';
    }

    /**
     * Handles the default column output.
     *
     * @since 4.3.0
     *
     * @param Shipment $shipment The current shipment object.
     * @param string          $column_name The current column name.
     */
    public function column_default( $shipment, $column_name ) {

        /**
         * Fires in each custom column in the Posts list table.
         *
         * This hook only fires if the current post type is non-hierarchical,
         * such as posts.
         *
         * @since 1.5.0
         *
         * @param string $column_name The name of the column to display.
         * @param int    $post_id     The current post ID.
         */
        do_action( 'woocommerce_gzd_manage_shipments_custom_column', $column_name, $shipment->get_id() );
    }

    /**
     * Handles the post author column output.
     *
     * @since 4.3.0
     *
     * @param Shipment $shipment The current shipment object.
     */
    public function column_title( $shipment ) {
        $title = sprintf( _x( 'Shipment #%s', 'shipments', 'woocommerce-germanized-shipments' ), $shipment->get_id() );

        if ( $order = $shipment->get_order() ) {
            echo '<a href="' . $shipment->get_edit_shipment_url() . '">' . $title . '</a>';
        } else {
            echo $title;
        }
    }

	/**
	 * Handles shipment actions.
	 *
	 * @since 0.0.1
	 *
	 * @param Shipment $shipment The current shipment object.
	 */
	protected function column_actions( $shipment ) {
		echo '<p>';

		do_action( 'woocommerce_gzd_shipments_table_actions_start', $shipment );

		$actions = array();

		if ( $shipment->has_status( array( 'draft' ) ) ) {
			$actions['processing'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_gzd_update_shipment_status&status=processing&shipment_id=' . $shipment->get_id() ), 'update-shipment-status' ),
				'name'   => __( 'Processing', 'woocommerce-germanized-shipments' ),
				'action' => 'processing',
			);
		}

		if ( $shipment->has_status( array( 'draft', 'processing' ) ) ) {
			$actions['shipped'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_gzd_update_shipment_status&status=shipped&shipment_id=' . $shipment->get_id() ), 'update-shipment-status' ),
				'name'   => __( 'Shipped', 'woocommerce-germanized-shipments' ),
				'action' => 'shipped',
			);
		}

		$actions = apply_filters( 'woocommerce_gzd_shipments_table_actions', $actions, $shipment );

		echo wc_gzd_render_shipment_action_buttons( $actions ); // WPCS: XSS ok.

		do_action( 'woocommerce_gzd_shipments_table_actions_end', $shipment );

		echo '</p>';
	}

    public function column_cb( $shipment ) {
        if ( current_user_can( 'edit_shop_orders' ) ) :
            ?>
            <label class="screen-reader-text" for="cb-select-<?php echo esc_attr( $shipment->get_id() ); ?>">
                <?php printf( _x( 'Select %s', 'shipments', 'woocommerce-germanized-shipments' ), $shipment->get_id() ); ?>
            </label>
            <input id="cb-select-<?php echo esc_attr( $shipment->get_id() ); ?>" type="checkbox" name="shipment[]" value="<?php echo esc_attr( $shipment->get_id() ); ?>" />
        <?php
        endif;
    }

    /**
     * Handles the post author column output.
     *
     * @since 4.3.0
     *
     * @param Shipment $shipment The current shipment object.
     */
    public function column_items( $shipment ) {
        ?>
        <table class="wc-gzd-shipments-preview">
            <tbody>
            <?php foreach( $shipment->get_items() as $item ) : ?>
                <tr class="wc-gzd-shipment-item-preview wc-gzd-shipment-item-preview-<?php echo esc_attr( $item->get_id() ); ?>">
                    <td class="wc-gzd-shipment-item-column-name">
                        <?php if ( $product = $item->get_product() ) : ?>
                            <a href="<?php echo get_edit_post_link( $product->get_id() ); ?>"><?php echo wp_kses_post( $item->get_name() ); ?></a>
                        <?php else: ?>
                            <?php echo wp_kses_post( $item->get_name() ); ?>
                        <?php endif; ?>

                        <?php echo ( $item->get_sku() ? '<br/><small>' . esc_html_x( 'SKU:', 'shipments', 'woocommerce-germanized-shipments' ) . ' ' . esc_html( $item->get_sku() ) . '</small>' : '' ); ?>
                    </td>
                    <td class="wc-gzd-shipment-item-column-quantity">
                        <?php echo $item->get_quantity(); ?>x
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Handles the post author column output.
     *
     * @since 4.3.0
     *
     * @param Shipment $shipment The current shipment object.
     */
    public function column_address( $shipment ) {
        echo '<address>' . $shipment->get_formatted_address() . '</address>';
    }

    /**
     * Handles the post author column output.
     *
     * @since 4.3.0
     *
     * @param Shipment $shipment The current shipment object.
     */
    public function column_status( $shipment ) {
        echo '<span class="shipment-status status-' . esc_attr( $shipment->get_status() ) . '">' . wc_gzd_get_shipment_status_name( $shipment->get_status() ) .'</span>';
    }

    /**
     * Handles the post author column output.
     *
     * @since 4.3.0
     *
     * @param Shipment $shipment The current shipment object.
     */
    public function column_weight( $shipment ) {
        echo wc_gzd_format_shipment_weight( $shipment->get_weight() );
    }

    /**
     * Handles the post author column output.
     *
     * @since 4.3.0
     *
     * @param Shipment $shipment The current shipment object.
     */
    public function column_dimensions( $shipment ) {
        echo wc_gzd_format_shipment_dimensions( $shipment->get_dimensions() );
    }

    /**
     * Handles the post author column output.
     *
     * @since 4.3.0
     *
     * @param Shipment $shipment The current shipment object.
     */
    public function column_date( $shipment ) {
        $shipment_timestamp = $shipment->get_date_created() ? $shipment->get_date_created()->getTimestamp() : '';

        if ( ! $shipment_timestamp ) {
            echo '&ndash;';
            return;
        }

        // Check if the order was created within the last 24 hours, and not in the future.
        if ( $shipment_timestamp > strtotime( '-1 day', current_time( 'timestamp', true ) ) && $shipment_timestamp <= current_time( 'timestamp', true ) ) {
            $show_date = sprintf(
            /* translators: %s: human-readable time difference */
                _x( '%s ago', '%s = human-readable time difference', 'woocommerce-germanized-shipments' ),
                human_time_diff( $shipment->get_date_created()->getTimestamp(), current_time( 'timestamp', true ) )
            );
        } else {
            $show_date = $shipment->get_date_created()->date_i18n( apply_filters( 'woocommerce_gzd_admin_shipment_date_format', _x( 'M j, Y', 'shipments', 'woocommerce-germanized-shipments' ) ) );
        }

        printf(
            '<time datetime="%1$s" title="%2$s">%3$s</time>',
            esc_attr( $shipment->get_date_created()->date( 'c' ) ),
            esc_html( $shipment->get_date_created()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
            esc_html( $show_date )
        );
    }

    /**
     * Handles the post author column output.
     *
     * @since 4.3.0
     *
     * @param Shipment $shipment The current shipment object.
     */
    public function column_order( $shipment ) {
        if ( ( $order = $shipment->get_order() ) && is_callable( array( $order, 'get_edit_order_url' ) ) ) {
            echo '<a href="' . $order->get_edit_order_url() . '">' . $order->get_order_number() . '</a>';
        } else {
            echo $shipment->get_order_id();
        }
    }

    /**
     *
     * @param int|WC_GZD_Shipment $shipment
     */
    public function single_row( $shipment ) {
        $GLOBALS['shipment'] = $shipment;
        $classes             = 'shipment shipment-status-' . $shipment->get_status();
        ?>
        <tr id="shipment-<?php echo $shipment->get_id(); ?>" class="<?php echo esc_attr( $classes ); ?>">
            <?php $this->single_row_columns( $shipment ); ?>
        </tr>
        <?php
    }

    /**
     * @return array
     */
    protected function get_bulk_actions() {
        $actions = array();

        if ( current_user_can( 'delete_shop_orders' ) ) {
            $actions['delete'] = _x( 'Delete Permanently', 'shipments', 'woocommerce-germanized-shipments' );
        }

        $actions['mark_processing'] = _x( 'Change status to processing', 'shipments', 'woocommerce' );
        $actions['mark_shipped']    = _x( 'Change status to shipped', 'shipments', 'woocommerce' );
        $actions['mark_delivered']  = _x( 'Change status to delivered', 'shipments', 'woocommerce-germanized-shipments' );

        return apply_filters( 'woocommerce_gzd_shipments_bulk_actions', $actions );
    }

}