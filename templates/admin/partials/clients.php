<?php
?>
<style>
	.form-table {width:700px !important;}
	.form-table tr {width:700px !important; display:inline-block;}
	.form-table th {width:155px !important; padding-left:20px;}
	.widefat th { font-weight:700; }
</style>
<div class="wrap">
	<h1 class="wp-heading-inline">Client<?php if (!isset($_GET['client'])){ echo "s"; }else{ echo " Details"; } ?></h1>

	<hr />
	<h2>Client</h2>
	<form method="get" action="admin-post.php">
		<table class="form-table">
			<tr>
				<td>
					<select name="client" onchange="this.form.submit();">
						<option value="">Select Client</option>
						<?php
						$vendors = get_users( array( 'role__in' => array( 'client' ) ) );
						foreach ( $vendors as $user ) {

							$first = get_user_meta( $user->ID, 'first_name', true );
							$last = get_user_meta( $user->ID, 'last_name', true );

							echo "<option value=\"".$user->ID."\"";
							if(isset($_GET['client']) && $user->ID==$_GET['client']){ echo " selected";}
							echo ">".$first." ".$last."</option>";

						}
						?>
					</select>
				</td>
			</tr>
		</table>
		<input name="action" type="hidden" value="client_select_form_handler">
	</form>

	<?php
	if(!isset($_GET['client']) || $_GET['client']==""){
		exit;
	}
	?>

	<hr />
	<h2>Partner</h2>
	<?php
	// get current partner from metadata
	$partner = get_user_meta($_GET['client'], 'sb_partner');
	if(!empty($partner)) {
		$partner_uid = (int)$partner[0];
		$partner = get_userdata($partner_uid);
	}else{
		$partner_uid="";
	}
	?>

	<form method="post" action="admin-post.php">
		<table class="form-table">
			<tr>
				<td>
					<select name="partner">
						<option value="">Select Partner</option>
						<?php
						$clients = get_users( array( 'role__in' => array( 'client' ) ) );
						foreach ( $clients as $user ) {
							if($user->ID != $_GET['client']) {

								$first = get_user_meta($user->ID, 'first_name', true);
								$last = get_user_meta($user->ID, 'last_name', true);

								echo "<option value=\"" . $user->ID . "\"";
								if (isset($partner_uid) && $user->ID == $partner_uid) {
									echo " selected";
								}
								echo ">" . $first . " " . $last . "</option>";
							}
						}
						?>
					</select>
				</td>
				<td>
					<input name="client" type="hidden" value="<?php echo $_GET['client']; ?>" />
					<input name="action" type="hidden" value="client_select_partner_form_handler">
					<input type="submit" class="button-primary" value="Save" />
				</td>
			</tr>
		</table>
	</form>
	<hr />
	<h2>Therapist</h2>
	<?php
	// get current provider from metadata
	$provider_uid = get_user_meta($_GET['client'], 'sb_provider',true);

	/*
	if(!empty($provider)) {
		$provider_uid = (int)$provider[0];
		$provider = get_userdata($provider_uid);
	}else{
		$provider_uid="";
	}
	*/
	?>

	<form method="post" action="admin-post.php">
		<table class="form-table">
			<tr>
				<td>
					<select name="provider">
						<option value="">Select Therapist</option>
						<?php
						$vendors = get_users( array( 'role__in' => array( 'employee_therapist', 'legacy_associate_therapist', 'legacy_licensed_therapist', 'legacy_senior_therapist', 'associate_therapist', 'licensed_therapist', 'senior_therapist' ) ) );
						foreach ( $vendors as $user ) {

							$first = get_user_meta( $user->ID, 'first_name', true );
							$last = get_user_meta( $user->ID, 'last_name', true );

							echo "<option value=\"".$user->ID."\"";
                            // TODO change to meta_data sb_provider value
							if($user->ID==$provider_uid){ echo " selected"; }
							echo ">".$first." ".$last."</option>";

						}
						?>
					</select>
				</td>
				<td>
					<input name="client" type="hidden" value="<?php echo $_GET['client']; ?>" />
					<input name="action" type="hidden" value="client_select_provider_form_handler">
					<input type="submit" class="button-primary" value="Save" />
				</td>
			</tr>
		</table>
	</form>
	<hr />
	<h2>Transactions</h2>

	<?php // https://webkul.com/blog/create-admin-tables-using-wp_list_table-class/ ?>

	<table class="widefat" style="width:400px">
		<tr>
			<th>Date</th>
			<th>Amount</th>
		</tr>

		<?php
		$uid = 2;
		global $wpdb;
		$table_name = $wpdb->prefix . "sb_payments";
		$query = "SELECT * FROM `$table_name` WHERE `payee_uid` = '$uid'";
		preintq($query);
		$results = $wpdb->get_results($query);

		foreach($results as $result){

			$amt = number_format(($result->amt /100), 2, '.', ' ');
			$date = gmdate('m-d-y',$result->date);

			echo "<tr><td>".$date."</td><td>".$amt."</td></tr>";

		}

		?>
	</table>
</div>