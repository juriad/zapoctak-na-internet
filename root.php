<?php

$requireRoles = array('roots');
require_once 'utils/initPage.php';

if (isset($_GET['rootId'])) {
	try {
		$root = new Root($_GET['rootId'], $db);
		if ($root->getState() < 0) {
			saveMessage(
					new Message("Cannot edit invalid root",
							Message::SEVERITY_ERROR));
			header("Location: " . url('roots.php', NULL));
			exit();
		}
	} catch (Exception $e) {
		saveMessage(new Message("Unknown root id", Message::SEVERITY_ERROR));
		header("Location: " . url('roots.php', NULL));
		exit();
	}
} else {
	saveMessage(new Message("Unknown root id", Message::SEVERITY_ERROR));
	header("Location: " . url('roots.php', NULL));
	exit();
}

$title = 'Manage allowed users for root in WebFS';
include 'pieces/page-start.php';

?>

<h1>Allowed users for root <?php h($root->getName()); ?></h1>

<form action="<?php u('processRoot.php'); ?>" method="post">
	<input type="hidden" name="action" value="modifyUsers">
	<input type="hidden" name="rootId" value="<?php echo $root->getId() ?>">

	<table id='root' class='tablesorter'>
		<thead>
			<tr>
				<th>Allowed</th>
				<th>Id</th>
				<th>User</th>
				<th>Active</th>
				<th>Added</th>
			</tr>
		</thead>
		<tbody>
<?php
$userRoots = $root->getUserRoots(array(), $db);
$allowedUserIds = array();
$row = 0;
foreach ($userRoots as $ur) {
	$user = $ur->getUser();
?>
			<tr class='<?php echo ($row % 2 == 0 ? 'even' : 'odd'); ?> allowed<?php echo ($ur
			->isActive() ? "" : " inactive");
																			  ?>'>
				<td>
					<input type='checkbox' id='ch<?php echo $user->getId(); ?>'
						name='users[]' value='<?php echo $user->getId(); ?>' checked='checked'>
				</td>
				<td><?php echo $ur->getId(); ?></td>
				<td>
					<label for='ch<?php echo $user->getId(); ?>'>
						<?php h($user->getName()); ?></label>
				</td>
				<td><?php echo ($ur->isActive() ? "active" : "inactive"); ?>
					<br>
					<a href='<?php u('processRoot.php',
			array('action' => 'setActive', 'userRootId' => $ur->getId(),
					'state' => ($ur->isActive() ? '0' : '1')));
							 ?>'>
						<?php echo ($ur->isActive() ? "Deactivate" : "Activate"); ?></a>
				</td>
				<td><span data-time='<?php echo $ur->getAdded(); ?>' class='timebr'>
				<?php echo $ur->getAdded(); ?></span></td>
			</tr>
<?php
								 array_push($allowedUserIds, $user->getId());
								 $row++;
							 }

							 foreach (User::getAllUserIds($db) as $userId) {
								 if (!in_array($userId, $allowedUserIds)) {
									 if ($userId == $_SESSION['user']->getId()) {
										 $user = $_SESSION['user'];
									 } else {
										 $user = new User($userId, $db);
									 }
?>
			<tr class='<?php echo ($row % 2 == 0 ? 'even' : 'odd'); ?> not-allowed'>
				<td>
					<input type='checkbox' id='ch<?php echo $user->getId(); ?>'
						name='users[]' value='<?php echo $user->getId(); ?>'>
				</td>
				<td></td>
				<td>
					<label for='ch<?php echo $user->getId(); ?>'>
						<?php h($user->getName()); ?></label>
				</td>
				<td></td>
				<td></td>
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

<a href='<?php u('roots.php'); ?>'>Back to list of roots</a>
<?php
include 'pieces/page-end.php';
?>