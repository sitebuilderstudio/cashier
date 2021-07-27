<?php

//https://onextrapixel.com/how-to-design-and-style-your-wordpress-plugin-admin-panel/

// get tiers data for this tier
if ( isset( $_GET['tier_group'] ) ) {
	$tier_group = (int) $_GET['tier_group'];
} else {
	// default
	$tier_group = 1;
}

// set tier group name
switch ( $tier_group ) {
	case 1:
		$tier_group_name = "Legacy Associate Therapists";
		break;
	case 2:
		$tier_group_name = "Legacy Licensed Therapists";
		break;
	case 3:
		$tier_group_name = "Legacy Senior Therapists";
		break;
	case 4:
		$tier_group_name = "Associate Therapists";
		break;
	case 5:
		$tier_group_name = "Licensed Therapists";
		break;
	case 6:
		$tier_group_name = "Senior Therapists";
		break;
}


global $wpdb;
$table = $wpdb->prefix . "cash_tiers";
$query = "SELECT * FROM $table WHERE tier_group = '$tier_group' ORDER BY level DESC"; preintq( $query );

$results = $wpdb->get_results( $query );

?>
<style>
    .form-table {
        width: 700px !important;
    }

    .form-table tr {
        width: 700px !important;
        display: inline-block;
    }

    .form-table th {
        width: 155px !important;
        padding-left: 20px;
    }
</style>
<div class="wrap">
    <h1 class="wp-heading-inline">Multi-Tier Commissions - Set Commission Levels Per Tier</h1>

    <hr/>

    <form method="get" action="admin-post.php">
        <input name="action" type="hidden" value="tiers_select_form_submit">
        <select name="tier_group" onchange="this.form.submit();">
            <option value="1" <?php if ( $tier_group === 1 ) {
				echo "selected";
			} ?>>Legacy Associate
            </option>
            <option value="2" <?php if ( $tier_group === 2 ) {
				echo "selected";
			} ?>>Legacy Licensed
            </option>
            <option value="3" <?php if ( $tier_group === 3 ) {
				echo "selected";
			} ?>>Legacy Senior
            </option>
            <option value="4" <?php if ( $tier_group === 4 ) {
				echo "selected";
			} ?>>Associate
            </option>
            <option value="5" <?php if ( $tier_group === 5 ) {
				echo "selected";
			} ?>>Licensed
            </option>
            <option value="6" <?php if ( $tier_group === 6 ) {
				echo "selected";
			} ?>>Senior
            </option>
        </select>
    </form>

    <form method="post" action="admin-post.php">

        <table class="form-table">
            <tr>
                <th>Level</th>
                <th>Monthly Earnings</th>
                <th>Commission</th>
            </tr>

			<?php
			// get all the tiers for this group from the database

			$i = 0;

			foreach ( $results as $result ) {

				$earnings = (int) $result->earnings;
				$percent  = (int) $result->percent;
				$level    = (int) $result->level;
				if ( $level === 0 ) {
					$level = "Base";
				}

				echo "<tr valign=\"top\"><td>" . $level . "</td>";
				echo "<td>";
				if ( $result->level == 0 ) {
					echo "<td><input type=\"hidden\" name=\"earnings[" . $result->id . "]\" value=\"0\" /></td>";
				} else {
					echo "<td><input type=\"number\" name=\"earnings[" . $result->id . "]\" value=\"" . $earnings . "\" /></td>";
				}

				echo "<td><input type=\"number\" name=\"percent[" . $result->id . "]\" value=\"" . $percent . "\" /></td>";

				echo "</tr>";

				$i ++;

			}

			?>
        </table>
        <p class="submit">
            <input name="tier_group" type="hidden" value="<?php echo $tier_group; ?>"/>
            <input name="action" type="hidden" value="tiers_form_submit">
            <input type="submit" class="button-primary" value="Save Changes"/>
        </p>
    </form>
</div>
