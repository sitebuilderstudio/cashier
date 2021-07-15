<?php

//echo "tiers view file, build out tiers form and function";

//https://onextrapixel.com/how-to-design-and-style-your-wordpress-plugin-admin-panel/

?>
<style>
    .form-table {width:450px !important;}
    .form-table th {
        padding-left:8px !important;
        margin-bottom:10px !important;
        border-bottom:1px solid #7C7C7C3D !important;
    }
    /*.form-table {width:700px !important;}
    .form-table tr {width:700px !important; display:inline-block;}
    .form-table th {width:155px !important; padding-left:20px;}*/
</style>
<div class="wrap">
    <h1 class="wp-heading-inline">Specific Commissions - Set Commission Levels Per Therapist</h1>

    <hr />

    <form method="get" action="admin-post.php">
        <input name="action" type="hidden" value="custom_tiers_select_form_handler">
        <select name="user" onchange="this.form.submit();">
            <option value="">Select Therapist</option>
			<?php
			$vendors = get_users( array( 'role__in' => array( 'associate_therapist', 'licensed_therapist', 'senior_therapist', 'legacy_associate_therapist', 'legacy_licensed_therapist', 'legacy_senior_therapist' ) ) );
			foreach ( $vendors as $user ) {

				$first = get_user_meta( $user->ID, 'first_name', true );
				$last = get_user_meta( $user->ID, 'last_name', true );

				echo "<option value=\"".$user->ID."\"";
				if(isset($_GET['user']) && $user->ID==$_GET['user']){ echo " selected";}
				echo ">".$first." ".$last."</option>";

			}

			?>
        </select>
    </form>

	<?php
	if(!isset($_GET['user']) || $_GET['user']==""){
		exit;
	}
	?>

    <form method="post" action="admin-post.php">

        <table class="form-table">
            <tr>
                <th>Level</th>
                <th>Monthly Earnings</th>
                <th>Commission</th>
            </tr>

			<?php

			// get custom tiers data for this user
			$uid = $_GET['user'];

			global $wpdb;
			$table = $wpdb->prefix . "sb_tiers";
			$query = "SELECT * FROM $table WHERE user_id = '$uid' ORDER BY level DESC";
			$results = $wpdb->get_results($query);

			//echo "<pre>";
			//var_dump($results);
			//echo "</pre>";

			$i=5;

			foreach ($results as $result){

				$earnings = $result->earnings;
				$percent = $result->percent;
				$level = (int) $result->level;

				echo "<tr valign=\"top\"><td>".$level."</td>";

				echo "<td><input type=\"number\" name=\"earnings[".$result->id."]\" value=\"".$earnings."\" /></td>";

				echo "<td><input type=\"number\" name=\"percent[".$result->id."]\" value=\"".$percent."\" /></td>";

				echo "</tr>";

				$i--;

			}

			// if needed, echo some empty rows so we have a total of four levels for custom tiers

			while($i>0){

				echo "<tr valign=\"top\"><td>".$i."</td>";

				echo "<input type=\"hidden\" name=\"level[new][".$i."]\" value=\"".$i."\" />";

				echo "<td><input type=\"number\" name=\"earnings[new][".$i."]\" value=\"\" /></td>";

				echo "<td><input type=\"number\" name=\"percent[new][".$i."]\" value=\"\" /></td>";

				echo "</tr>";

				$i--;
			}

			?>
        </table>
        <p class="submit">
            <input name="user" type="hidden" value="<?= $uid ?>" />
            <input name="action" type="hidden" value="custom_tiers_form_submit">
            <input type="submit" class="button-primary" value="Save Changes" />
        </p>
    </form>
</div>
