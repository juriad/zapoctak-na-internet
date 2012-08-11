<?php

$requireRoles = array('users');
require_once 'utils/initPage.php';

if (isset($_GET['userId'])) {
	if ($_GET['userId'] == $_SESSION['user']->getId()) {
		$user = $_SESSION['user'];
	} else {
		$user = new User($_GET['userId'], $db);
	}
} else {
	saveMessage(new Message("Unknown user id", Message::SEVERITY_ERROR));
	header("Location: " . url('users.php', NULL));
	exit();
}

$title = 'Manage users in WebFS';
include 'pieces/page-start.php';

?>

<h1>Roles of user <?php h($user->getName()); ?></h1>

<form action="<? u('processRoles.php'); ?>" method="post">
	<input type="hidden" name="action" value="modifyRoles">
	<input type="hidden" name="userId" value="<?php echo $user->getId() ?>">
<table id='roles' class='tablesorter'>
	<thead>
		<tr>
			<th>Role</th>
		</tr>
	</thead>
	<tbody>
<?php
$row = 0;
foreach ($user->getRoles() as $role) {
?>
		<tr class='<?php echo ($row % 2 == 0 ? 'even' : 'odd'); ?>'>
			<td>
				<label for='<?php h($role); ?>'><?php h($role); ?></label>
					<input type='checkbox' id='<?php h($role); ?>' name='roles[]'
						value='<?php h($role); ?>' checked='checked'>
					<br>
			</td>
		</tr>
<?php
	$row++;
}
foreach (User::$ALL_ROLES as $role) {
	if (!$user->hasRole($role)) {
?>
		<tr class='<?php echo ($row % 2 == 0 ? 'even' : 'odd'); ?>'>
			<td>
				<label for='<?php h($role); ?>'><?php h($role); ?></label>
					<input type='checkbox' id='<?php h($role); ?>' name='roles[]'
						value='<?php h($role); ?>'>
					<br>
			</td>
		</tr>
<?php
		$row++;
	}
}
?>
	</tbody>
</table>
	<input type="submit" value="Submit">
</form>
<a href='<?php u('users.php'); ?>'>Back to list of users</a>
<?php
include 'pieces/page-end.php';
?>