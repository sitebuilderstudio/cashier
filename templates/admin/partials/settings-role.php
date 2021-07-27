<div class="wrap">
    <h2>Role Settings</h2>
    <hr/>

    <!--        <form method="post" action="options.php">-->
    <form method="post" action="admin-post.php">

        <table class="form-table">

            <tr>
                <th><h3>Create Role</h3></th>
            </tr>

            <tr valign="top">
                <th scope="row">Roles</th>
                <td><input type="text" name="role" id="role" required></td>
            </tr>

        </table>

        <p class="submit">
			<?php wp_nonce_field( 'nonce-add-role' ); ?>
            <input type="hidden" name="action" value="add_role">
            <input type="submit" class="button-primary" value="Save Role"/>
        </p>

    </form>


    <h2>Created Roles</h2>
	<?php
	$cashier_roles = get_option( 'cashier_roles' );

	if( $cashier_roles == '' ) { // make sure the $cashier_roles is not an empty string but an empty array
	    $cashier_roles = [];
    }

	?>

    <table class="roles">
        <thead>
        <th class="sn">S.N</th>
        <th>Roles</th>
        <th>By</th>
        </thead>

        <tbody>
		<?php
		$count = 1;

		foreach ( $cashier_roles as $key => $value ) { ?>
            <tr>
                <td><?php echo $count; ?></td>
                <td><?php echo $value[1]; ?></td>
                <td>
					<?php
					$user_obj = get_user_by( 'ID', $value[0] );
					//					var_dump( $user_obj );
					echo $user_obj->user_login;
					?>
                </td>
            </tr>
			<?php $count ++;
		} ?>
        </tbody>
    </table>

</div>

<style>
    table.roles {
        border: 1px solid #ccc;
        border-collapse: collapse;
        margin: 0;
        padding: 0;
        width: 45%;
        table-layout: fixed;
    }

    table.roles th.sn {
        width: 10%;
    }

    table.roles tr {
        background-color: #f8f8f8;
        border: 1px solid #ddd;
        padding: .35em;
    }

    table.roles th,
    table.roles td {
        padding: .625em;
        text-align: left;
    }

    table.roles th {
        font-size: .85em;
        letter-spacing: .1em;
        text-transform: uppercase;
    }
</style>