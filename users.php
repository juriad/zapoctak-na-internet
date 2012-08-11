<?php

$requireRoles = array('users');
require_once 'utils/initPage.php';

$title = 'Manage users in WebFS';
include 'pieces/page-start.php';

?>

<h1>Users settings</h1>

<table id='users' class='tablesorter'>
	<thead>
		<tr>
			<th>Id</th>
			<th>Name</th>
			<th>Active</th>
			<th>Added</th>
			<th>Last logged</th>
			<th>Roles</th>
			<th>Change password</th>
		</tr>
	</thead>
	<tbody>
<?php
$row = 0;
foreach (User::getAllUsers($db) as $user) {
?>
		<tr class='<?php echo ($row % 2 == 0 ? 'even' : 'odd'); ?>
		<?php echo ($user->isActive() ? "" : " inactive"); ?>'>
			<td><?php echo $user->getId(); ?></td>
			<td><?php h($user->getName()); ?></td>
			<td>
				<?php echo ($user->isActive() ? "active" : "inactive"); ?>
				<br>
				<a href='<?php u('processUsers.php',
			array('action' => 'setActive', 'userId' => $user->getId(),
					'state' => ($user->isActive() ? '0' : '1')));
						 ?>'>
					<?php echo ($user->isActive() ? "Deactivate" : "Activate"); ?></a>
			</td>
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
				<?php h(implode(', ', $user->getRoles())); ?>
				<br>
				<a href='<?php u('roles.php', array('userId' => $user->getId())); ?>'>Edit roles</a>
			</td>
			<td>
				<form id='passwd-form<?php echo $user->getId(); ?>'
					action='<?php u('processUsers.php'); ?>' method='post'>
					<input type='password' name='password'>
					<input type='submit' value='Submit'>
					<input type='hidden' name='action' value='changePass'>
					<input type='hidden' name='userId'
						value='<?php echo $user->getId(); ?>'>
				</form>
			</td>
		</tr>
<?php
	$row++;
}
?>
	</tbody>
</table>

<h2>Add new user</h2>
<form id='newUserForm' action="<?php u('processUsers.php'); ?>" method="post">
	<input type="hidden" name="action" value="addUser">
	<label for="username">Username:</label>
		<input type="text" id="username" name="username"> <br>
	<label for="password">Password:</label>
		<input type="password" id="password" name="password"> <br>
	<input type="submit" value="Submit">
</form>
<?php
include 'pieces/page-end.php';
?>