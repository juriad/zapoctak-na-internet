<?php

require_once 'utils/initPage.php';

$title = 'Manage users in WebFS';
include 'pieces/page-start.php';

?>

<h1>User settings</h1>

<table id='user' class='tablesorter'>
	<thead>
		<tr>
			<th>Name</th>
			<th>Added</th>
			<th>Last logged</th>
			<th>Change password</th>
		</tr>
	</thead>
	<tbody>
<?php
$user = $_SESSION['user'];
?>
		<tr>
			<td><?php h($user->getName()); ?></td>
			<td><span data-time='<?php echo $user->getAdded(); ?>' class='timebr'>
				<?php echo $user->getAdded(); ?></span></td>
			<td>
<?php
if (is_null($user->getLastLogged())) {
?>
				never
<?php
} else {
?>
				<span data-time='<?php echo $user->getLastLogged(); ?>'
					class='timebr'><?php echo $user->getLastLogged(); ?></span>
<?php
}
?>
			</td>
			<td>
				<form id='passwd-form<?php echo $user->getId(); ?>'
					action='<?php u('processUser.php'); ?>' method='post'>
					<input type='password' name='password'>
					<input type='submit' value='Submit'>
					<input type='hidden' name='action' value='changePass'>
				</form>
			</td>
		</tr>
	</tbody>
</table>
<?php
include 'pieces/page-end.php';
?>