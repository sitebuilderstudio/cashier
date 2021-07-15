<?php

ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );
error_reporting( E_ALL );

// reference -  https://developer.wordpress.org/reference/classes/wp_list_table/
// https://www.smashingmagazine.com/2011/11/native-admin-tables-wordpress/

// - https://wpengineer.com/2426/wp_list_table-a-step-by-step-guide/

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Payments_List_Table extends WP_List_Table {

	/**
	 * Constructor, we override the parent to pass our own arguments
	 * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
	 */
	function __construct() {
		parent::__construct( array(
			'singular' => 'wp_list_text_link', //Singular label
			'plural'   => 'wp_list_test_links', //plural label, also this well be one of the table css class
			'ajax'     => false //We won't support Ajax for this table
		) );
	}

	/**
	 * Define the columns that are going to be used in the table
	 * @return array $columns, the array of columns to use with the table
	 */

	function get_columns() {
		return $columns = array(
			'id'               => __( 'ID' ),
			'payer_uid'        => __( 'Client' ),
			'payee_uid'        => __( 'Therapist' ),
			'amt'              => __( 'Amount' ),
			'commission_amt'   => __( 'Commission Amt' ),
			'date'             => __( 'Date' ),
			'stripe_charge_id' => __( 'Stripe Charge ID' )
		);
	}

	/**
	 * Decide which columns to activate the sorting functionality on
	 * @return array $sortable, the array of columns that can be sorted by the user
	 */

	public function get_sortable_columns() {
		return $sortable = array(
			'id'             => array( 'id', false ),
			'payer_uid'      => array( 'payer_uid', false ),
			'payee_uid'      => array( 'payee_uid', false ),
			'amt'            => array( 'amt', false ),
			'commission_amt' => array( 'commission_amt', false ),
			'date'           => array( 'date', false ),
			// 'stripe_charge_id' => array( 'stripe_charge_id', false ) // might not be good to sort with this
		);
	}

	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
			case 'payer_uid':
			case 'payee_uid':
			case 'amt':
			case 'commission_amt':
			case 'stripe_charge_id':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 */

	function prepare_items() {

		global $wpdb, $_wp_column_headers;
		$screen = get_current_screen();

		// Preparing your query
		$tbl_left  = $wpdb->prefix . "cash_payments";
		$tbl_right = $wpdb->prefix . "users";

		$query = "SELECT a.*, b.user_login as payee from
		(SELECT $tbl_left.*, $tbl_right.user_login as payer FROM $tbl_left LEFT JOIN $tbl_right ON $tbl_left.payer_uid = $tbl_right.ID ) a
		LEFT JOIN wp_users b
		ON a.payee_uid = b.ID";

		// echo $query;

		// Search parameters
		if ( isset( $_GET['sphs'] ) ) {

			$query .= ' WHERE 1=1';

			if ( isset( $_GET['client'] ) && $_GET['client'] != "" ) {
				$query .= ' AND a.payer_uid = ' . (int) sanitize_text_field( $_GET['client'] );
			}

			if ( isset( $_GET['therapist'] ) && $_GET['therapist'] != "" ) {
				$query .= ' AND a.payee_uid = ' . (int) sanitize_text_field( $_GET['therapist'] );
			}

			if ( isset( $_GET['datefrom'] ) && $_GET['datefrom'] != "" ) {
				$df    = strtotime( $_GET['datefrom'] );
				$dt    = isset( $_GET['dateto'] ) && $_GET['dateto'] != "" ? strtotime( $_GET['dateto'] ) : strtotime( "now" );
				$query .= ' AND ( a.date BETWEEN ' . $df . ' AND ' . $dt . ')';
			}
		}

		// echo "<br/>" . $query;

		// Ordering parameters
		// Parameters that are going to be used to order the result

		if ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'payer_uid' ) {
			$orderby = 'payer';
		} elseif ( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'payee_uid' ) {
			$orderby = 'payee';
		} else {
			$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'id';
		}

		$order = isset( $_GET["order"] ) ? sanitize_text_field( $_GET["order"] ) : 'DESC';

		if ( ! empty( $orderby ) & ! empty( $order ) ) {
			$query .= ' ORDER BY ' . $orderby . ' ' . $order;
		}

		//echo '<span style="background: #fff; padding: 15px; display: block; box-shadow: 0 1px 1px 0 rgb(0 0 0 / 10%); border-left: 4px solid #e69955;">' . $query . '</span>';

		// Pagination parameters
		//Number of elements in your table?
		$totalitems = $wpdb->query( $query ); //return the total number of affected rows
		//How many to display per page?
		$perpage = 20;
		//Which page is this?
		$paged = ! empty( $_GET["paged"] ) ? $_GET["paged"] : 1;
		//Page Number
		if ( empty( $paged ) || ! is_numeric( $paged ) || $paged <= 0 ) {
			$paged = 1;
		} //How many pages do we have in total?
		$totalpages = ceil( $totalitems / $perpage ); //adjust the query to take pagination into account

		if ( ! empty( $paged ) && ! empty( $perpage ) ) {
			$offset = ( $paged - 1 ) * $perpage;
			$query  .= ' LIMIT ' . (int) $offset . ',' . (int) $perpage;
		}

		/* -- Register the pagination -- */
		$this->set_pagination_args( array(
			"total_items" => $totalitems,
			"total_pages" => $totalpages,
			"per_page"    => $perpage,
		) );
		//The pagination links are automatically built according to those parameters

		/* -- Register the Columns -- */
		$columns                           = $this->get_columns();
		$_wp_column_headers[ $screen->id ] = $columns;

		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		/* -- Fetch the items -- */
		$this->items = $wpdb->get_results( $query );
	}

	/**
	 * To add the filter Options: Cleint, Therapist and Date Range
	 *
	 */
	function extra_tablenav( $which ) {

		global $wpdb;

		switch ( $which ) {
			case 'top':
				// your html code
				ob_start();

				// get all clients or in this case payer
				$tbl_left  = $wpdb->prefix . "cash_payments";
				$tbl_right = $wpdb->prefix . "users";

				$payer_query = "SELECT $tbl_left.payer_uid, $tbl_right.user_login as payer FROM $tbl_left LEFT JOIN $tbl_right ON $tbl_left.payer_uid = $tbl_right.ID GROUP BY $tbl_left.payer_uid";
				$payers      = $wpdb->get_results( $payer_query ); //returns users as payers

				$payee_query = "SELECT $tbl_left.payee_uid, $tbl_right.user_login as payee FROM $tbl_left LEFT JOIN $tbl_right ON $tbl_left.payee_uid = $tbl_right.ID GROUP BY $tbl_left.payee_uid";
				$payees      = $wpdb->get_results( $payee_query ); //returns users as payee

				?>
				<div class="container-payments-history-search" style="display: inline-block">

					<form name="form-payments-history-search" method="get"
					      action="<?php echo $_SERVER['PHP_SELF']; ?>?page=payments-history">

						<input type="hidden" name="page" value="payments-history">
						<!-- this is important to submit form using GET method -->

						<select name="client">
							<?php $ip_client = isset( $_GET['client'] ) ? $_GET['client'] : 0; ?>
							<option value=""><?php _e( 'Select Client', 'stripe-builder' ); ?> </option>
							<?php foreach ( $payers as $key => $payer ): ?>
								<?php if ( $payer->payer != null ): ?>
									<option value="<?php echo $payer->payer_uid; ?>" <?php selected( $ip_client, $payer->payer_uid ); ?>>
										<?php echo $payer->payer; ?>
									</option>
								<?php endif; ?>
							<?php endforeach; ?>
						</select>

						<select name="therapist">
							<?php $ip_therapist = isset( $_GET['therapist'] ) ? $_GET['therapist'] : 0; ?>
							<option value=""><?php _e( 'Select Therapist', 'stripe-builder' ); ?></option>
							<?php foreach ( $payees as $key => $payee ): ?>
								<?php if ( $payee->payee != null ): ?>
									<option value="<?php echo $payee->payee_uid; ?>" <?php selected( $ip_therapist, $payee->payee_uid ); ?>>
										<?php echo $payee->payee; ?>
									</option>
								<?php endif; ?>
							<?php endforeach; ?>
						</select>

						<label for="datefrom"> &nbsp; Date Range: From </label>
						<input type="date" name="datefrom" id="datefrom"
						       value="<?php echo isset( $_GET['datefrom'] ) ? $_GET['datefrom'] : ''; ?>">
						<label for="dateto">To </label>
						<input type="date" name="dateto" id="dateto"
						       value="<?php echo isset( $_GET['dateto'] ) ? $_GET['dateto'] : ''; ?>">

						<input type="submit" name="sphs" value="Filter" class="button">
						<!-- sphs : submit-payment-history-search-->

					</form>

				</div>
				<?php
				echo ob_get_clean();
				break;

			case 'bottom':
				break;

		}
	}

	/**
	 * Display the rows of records in the table
	 * @return string, echo the markup of the rows
	 */
	function display_rows() {

		//Get the records registered in the prepare_items method
		$records = $this->items;

		//Get the columns registered in the get_columns and get_sortable_columns methods
		//list( $columns, $hidden ) = $this->get_column_info();

		//$columns = $this->get_column_info();
		$columns = $this->get_columns();

		//Loop for each record
		if ( ! empty( $records ) ) {
			foreach ( $records as $rec ) {

				//Open the line
				echo '<tr id="record_' . $rec->id . '">';
				foreach ( $columns as $column_name => $column_display_name ) {

					//Style attributes for each col
					$class = "class='$column_name column-$column_name'";
					$style = "";
					//if ( in_array( $column_name, $hidden ) ) $style = ' style="display:none;"';
					$attributes = $class . $style;

					//edit link
					//$editlink  = '/wp-admin/link.php?action=edit&link_id='.(int)$rec->id;

					//Display the cell
					switch ( $column_name ) {
						case "id":
							echo '<td ' . $attributes . '>' . stripslashes( $rec->id ) . '</td>';
							break;
						case "payer_uid":
//							echo '<td ' . $attributes . '>' . stripslashes( $rec->payer_uid ) . '</td>';
							echo '<td ' . $attributes . '>' . stripslashes( $rec->payer ) . '</td>';
							break;
						case "payee_uid":
//							echo '<td ' . $attributes . '>' . stripslashes( $rec->payee_uid ) . '</td>';
							echo '<td ' . $attributes . '>' . stripslashes( $rec->payee ) . '</td>';
							break;
						case "amt":
							echo '<td ' . $attributes . '>' . substr( $rec->amt, 0,-2 ) . '</td>';
							break;
						case "date":
							echo '<td ' . $attributes . '>' . stripslashes( date( 'm/d/Y', $rec->date ) ) . '</td>';
							break;
						case "stripe_charge_id":
							echo '<td ' . $attributes . '>' . $rec->stripe_charge_id . '</td>';
							break;
						case "commission_amt":
							echo '<td ' . $attributes . '>' . substr($rec->commission_amt,0,-2) . '</td>';
							break;
					}
				}

				//Close the line
				echo '</tr>';
			}
		}
	}

}


//Prepare Table of elements
$wp_list_table = new Payments_List_Table();

echo '<div class="wrap"><h2>Payments</h2>';

$wp_list_table->prepare_items();

//Table of elements
$wp_list_table->display();