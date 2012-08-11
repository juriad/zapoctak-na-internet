<?php

$requireRoles = array('roots');
require_once 'utils/initPage.php';

$title = 'Roots in WebFS';
include 'pieces/page-start.php';

?>

<h1>Settings of root directories</h1>

<table id='admRoots' class='tablesorter'>
	<thead>
		<tr>
			<th>Id</th>
			<th>Name</th>
			<th>Path</th>
			<th>State</th>
			<th>Added</th>
			<th>Last scanned</th>
			<th>Scan interval</th>
			<th>User settings</th>
		</tr>
	</thead>
	<tbody>
<?php
$row = 0;
foreach (Root::getAllRoots($db) as $root) {
?>
		<tr class='<?php echo ($row % 2 == 0 ? 'even' : 'odd'); ?>
		<?php echo ($root->getState() < 1 ? ($root->getState() < 0 ? ' invalid'
					: ' inactive') : "");
		?>'>
			<td><?php echo $root->getId(); ?></td>
			<td><?php h($root->getName()); ?></td>
			<td><?php h($root->getPath()); ?></td>
			<td><?php echo ($root->getState() < 0 ? "invalid"
					: ($root->getState() < 1 ? "inactive" : "active"));
			if ($root->getState() >= 0) {
				?>
			<br>
			<a href='<?php u('processRoots.php',
				array('action' => 'setState', 'rootId' => $root->getId(),
						'state' => ($root->getState() > 0 ? '0' : '1')));
					 ?>'>
				<?php echo ($root->getState() > 0 ? "Deactivate" : "Activate"); ?></a>
<?php
						 }
?>
			</td>
			<td><span data-time='<?php echo $root->getAdded(); ?>' class='timebr'>
				<?php echo $root->getAdded(); ?></span></td>
			<td>
<?php
	if (is_null($root->getLastScan())) {
?>
				not yet
<?php
	} else {
?>
				<span data-time='<?php echo $root->getLastScan(); ?>'
					class='timebr'><?php echo $root->getLastScan(); ?></span>
<?php
	}
?>
			</td>
			<td>
				<form id='interval-form<?php echo $root->getId(); ?>'
					action='<?php u('processRoots.php'); ?>' method='post'>
					<input type='text' name='interval'
						value='<?php echo $root->getScanInterval(); ?>'>
					<input type='submit' value='Submit'>
					<input type='hidden' name='action' value='changeInterval'>
					<input type='hidden' name='rootId'
						value='<?php echo $root->getId(); ?>'>
				</form>
			</td>
			<td>
<?php
	if ($root->getState() >= 0) {
?>
				<a href='<?php u('root.php', array('rootId' => $root->getId())); ?>'>Edit users assigned</a>
<?php
	}
?>
			</td>
		</tr>
<?php
	$row++;
}
?>
	</tbody>
</table>

<h2>Add new root</h2>
<form action="<?php u('processRoots.php'); ?>" method="post" id='addRootForm'>
	<input type="hidden" name="action" value="addRoot">
	<label for="name">Name:</label>
		<input type="text" id="name" name="name">
		<br>
	<label for="path">Path:</label>
		<input type="text" id="path" name="path">
		<br>
	<label for="interval">Interval:</label>
		<input type="text" id="interval" name="interval">
		<br>
	<input type="submit" value="Submit">
</form>
<?php
include 'pieces/page-end.php';
?>