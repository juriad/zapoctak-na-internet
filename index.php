<?php

require_once 'utils/initPage.php';

$title = 'Browse and manage roots in WebFS';
include 'pieces/page-start.php';

?>

<h1>Roots</h1>
<table id='roots' class='tablesorter'>
	<thead>
		<tr>
			<th>Action</th>
			<th>Root</th>
			<th>Active</th>
			<th>Added</th>
			<th>Comments</th>
		</tr>
	</thead>
	<tbody>
<?php
$userRoots = $_SESSION['user']->getUserRoots(array(), $db);
if (count($userRoots) == 0) {
?>
		<tr class='invalid'>
			<td colspan='5'>There are no available roots</td>
		</tr>
<?php
} else {
	$row = 0;
	foreach ($userRoots as $ur) {
		$root = $ur->getRoot();
		$state = $root->getState() > 0 ? ($ur->isActive() ? 1 : 0)
				: ($root->getState() < 0 ? -2 : -1);
?>
		<tr class='<?php echo ($row % 2 == 0 ? 'even' : 'odd'); ?>
		<?php echo ($state < 1 ? ($state < -1 ? ' invalid' : ' inactive') : "");
		?>'>
			<td>
<?php
				if ($state > 0) {
?>
					<a href='<?php u('files.php', array('root' => $ur->getId())); ?>'>Browse root</a>
					<br>
					<a href='<?php u('searchSession.php',
					array('root' => $ur->getId()));
							 ?>'>Search root</a>
<?php
									 } else {
?>
					Unavailable
<?php
		}
?>
			</td>
			<td>
<?php
		if ($state == 1) {
?>
				<a href='<?php u('files.php', array('root' => $ur->getId())); ?>'>
					<?php h($root->getName()); ?></a>
<?php
		} else {
?>
				<?php h($root->getName()); ?>
<?php
		}
?>
			</td>
			<td><?php echo ($state < 1 ? ($state < -1 ? 'invalid' : 'inactive')
				: 'active');
		if ($state >= 0) {
				?>
				<br>
				<a href='<?php u('processIndex.php',
					array('action' => 'setActive',
							'userRootId' => $ur->getId(),
							'state' => ($ur->isActive() ? '0' : '1')));
						 ?>'>
					<?php echo ($ur->isActive() ? "Deactivate" : "Activate") ?></a>
<?php
								 }
?>
			</td>
			<td><span data-time='<?php echo $root->getAdded(); ?>' class='timebr'>
				<?php echo $root->getAdded(); ?></span></td>
<?php
		if ($state == 1) {
?>
			<td class='fillCell'>
<?php
			$targetId = $root->getId();
			$targetTable = 'roots';
			$readOnly = false;
			include 'pieces/comments.php';
		} else {
?>
			<td>
				Available only for active root
<?php
		}
?>
			</td>
		</tr>
<?php
		$row++;
	}
}
?>
	</tbody>
</table>
<?php
include 'pieces/page-end.php';
?>