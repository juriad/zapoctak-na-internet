<?php

require_once 'utils/initPage.php';
require_once 'utils/file.php';

$rootFile = getRootFile($_GET['root'], $db);

if (isset($_GET['parent'])) {
	$parent = getFile($_GET['parent'], $rootFile, $_GET['root'], $db);
} else {
	$parent = $rootFile;
}

if (!$parent->isDirectory()) {
	header(
			"Location: "
					. url('file.php',
							array('id' => $parent->getId(),
									'root' => $_GET['root'])));
	exit();
}
// parent is valid now
if ($parent->isDeleted()) {
	saveMessage(new Message("This content is deleted", Message::SEVERITY_INFO));
}

if (isset($_GET['deleted'])) {
	if ($_GET['deleted']) {
		$_SESSION['deleted'] = true;
		$deleted = true;
	} else {
		$_SESSION['deleted'] = false;
		$deleted = false;
	}
	unset($_GET['page']);
} else {
	if (isset($_SESSION['deleted'])) {
		$deleted = $_SESSION['deleted'];
	} else {
		$_SESSION['deleted'] = false;
		$deleted = false;
	}
}

if ($parent->isDeleted()) {
	$deleted = true;
}

$title = 'Browse files in WebFS';
include 'pieces/page-start.php';
?>

<div id='navigation'>
<?php
$nav = $parent->getAncestors($rootFile, $db);
for ($i = 0; $i < count($nav); $i += 2) {
?>
 / <a href='<?php u('files.php',
			array('root' => $_GET['root'], 'parent' => $nav[$i]));
			?>'>
<?php h($nav[$i + 1]); ?></a>
<?php
			}
?>
</div>

<div id='settings'>
	<a href='<?php u('files.php',
		array('root' => $_GET['root'], 'parent' => $parent->getId(),
				'deleted' => ($_SESSION['deleted'] ? '0' : '1')));
			 ?>'>
		<?php echo ($_SESSION['deleted'] ? 'Hide ' : 'Show'); ?> deleted files</a>
	<a id='searchThis' href='<?php u('searchSession.php',
					 array('root' => $_GET['root'],
							 'ancestor' => $parent->getId()));
							 ?>'>Search this directory</a>
</div>
<?php
							 require_once 'utils/pagination.php';
?>
<table id='files' class='tablesorter'>
	<thead>
		<tr>
			<?php paginationFilesSortLink(0, 'Type') ?>
			<?php paginationFilesSortLink(1, 'Name') ?>
			<?php paginationFilesSortLink(2, 'Size') ?>
			<?php paginationFilesSortLink(3, 'Modified') ?>
			<?php paginationFilesSortLink(4, 'Version') ?>
			<?php paginationFilesSortLink(5, 'First mention') ?>
			<th>Info</th>
		</tr>
	</thead>
	<tbody>
		<tr class='even'>
			<td>
				<a href='<?php u('files.php',
		array('root' => $_GET['root'], 'parent' => $parent->getId()));
						 ?>'>
<?php
						 $fileForType = $parent;
						 $outputType = 2;
						 include 'pieces/fileType.php';
?>
					</a>
			</td>
			<td>
				<a href='<?php u('files.php',
		array('root' => $_GET['root'], 'parent' => $parent->getId()));
						 ?>'>.</a>
<?php
						 $tagFile = $parent;
						 $readOnly = true;
						 include 'pieces/tags.php';
?>
			</td>
			<td>
				<span class='filesize'
					data-filesize='<?php echo $parent->getLength(); ?>'>
					<?php echo $parent->getHumanLength(); ?></span>
			</td>
			<td><span data-time='<?php echo $parent->getModified(); ?>'
					class='timebr'><?php echo $parent->getModified(); ?></span></td>
			<td><?php echo $parent->getVersion(); ?></td>
			<td><span data-time='<?php echo $parent->getFirstMention(); ?>'
			class='timebr'><?php echo $parent->getFirstMention(); ?></span></td>
			<td>
				<a href='<?php u('file.php',
		array('id' => $parent->getId(), 'root' => $_GET['root']));
						 ?>'>Info</a>
			</td>
		</tr>
<?php
						 if ($parent->getParent()) {
							 $pparent = new File($parent->getParent(), $db);
?>
		<tr class='odd'>
			<td>
				<a href='<?php u('files.php',
			array('root' => $_GET['root'], 'parent' => $pparent->getId()));
						 ?>'>
<?php
							 $fileForType = $pparent;
							 $outputType = 2;
							 include 'pieces/fileType.php';
?>
						 </a>
			</td>
			<td>
				<a href='<?php u('files.php',
			array('root' => $_GET['root'], 'parent' => $pparent->getId()));
						 ?>'>..</a>
<?php
							 $tagFile = $pparent;
							 $readOnly = true;
							 include 'pieces/tags.php';
?>
			</td>
			<td>
				<span class='filesize'
					data-filesize='<?php echo $pparent->getLength(); ?>'>
					<?php echo $pparent->getHumanLength(); ?></span>
			</td>
			<td><span data-time='<?php echo $pparent->getModified(); ?>'
					class='timebr'><?php echo $pparent->getModified(); ?></span></td>
			<td><?php echo $pparent->getVersion(); ?></td>
			<td><span data-time='<?php echo $pparent->getFirstMention(); ?>'
			class='timebr'><?php echo $pparent->getFirstMention(); ?></span></td>
			<td>
				<a href='<?php u('file.php',
			array('id' => $pparent->getId(), 'root' => $_GET['root']));
						 ?>'>Info</a>
			</td>
		</tr>
<?php } ?>

<?php
require_once 'libs/class.paging.phps';

if (!isset($_SESSION['paginationPerPage'])) {
	$_SESSION['paginationPerPage'] = 10;
}
$paginationPerPage = $_SESSION['paginationPerPage'];

$childrenIds = $parent->getChildrenIds($deleted, NULL, NULL, $db);
$paging = new Paging(count($childrenIds), NULL, $paginationPerPage, '&page=%s');
$paging->set_title_format('Page %s');
$paging->set_around(5);
$paging->set_paging_mode(1);
$paging->set_output_mode(1);
$paging->set_paging(1);

$children = $parent
		->getChildren($deleted, $paging->get_start(), $paging->get_limit(), $db);
$row = $parent->getParent() === NULL ? 1 : 2;
foreach ($children as $child) {
?>
		<tr class='<?php echo ($row % 2 == 0 ? 'even' : 'odd'); ?>'>
			<td>
<?php
	if ($child->isDirectory()) {
?>
				<a href='<?php u('files.php',
				array('root' => $_GET['root'], 'parent' => $child->getId()));
						 ?>'>
<?php
								 $fileForType = $child;
								 $outputType = 2;
								 include 'pieces/fileType.php';
?>
						 </a>
<?php
	} else {
?>
				<a href='<?php u('file.php',
				array('id' => $child->getId(), 'root' => $_GET['root']));
						 ?>'>
<?php
								 $fileForType = $child;
								 $outputType = 2;
								 include 'pieces/fileType.php';
?>
						 </a>
<?php
	}
?>
			</td>
			<td>
<?php
	if ($child->isDirectory()) {
?>
				<a href='<?php u('files.php',
				array('root' => $_GET['root'], 'parent' => $child->getId()));
						 ?>'>
					<?php h($child->getName()); ?></a>
<?php
							 } else {
?>
				<a href='<?php u('file.php',
				array('id' => $child->getId(), 'root' => $_GET['root']));
						 ?>'>
					<?php h($child->getName()); ?></a>
<?php
							 }
							 $tagFile = $child;
							 $readOnly = true;
							 include 'pieces/tags.php';
?>
			</td>
			<td>
				<span class='filesize'
					data-filesize='<?php echo $child->getLength(); ?>'>
					<?php echo $child->getHumanLength(); ?></span>
			</td>
			<td><span data-time='<?php echo $child->getModified(); ?>'
					class='timebr'><?php echo $child->getModified(); ?></span></td>
			<td><?php echo $child->getVersion(); ?></td>
			<td><span data-time='<?php echo $child->getFirstMention(); ?>'
			class='timebr'><?php echo $child->getFirstMention(); ?></span></td>
			<td>
				<a href='<?php u('file.php',
			array('id' => $child->getId(), 'root' => $_GET['root']));
						 ?>'>Info</a>
			</td>
		</tr>
<?php
							 $row++;
						 }
?>
	</tbody>
</table>
<div id='pagination'>
	<div id='paginationSettings'>
		<form id='paginationSettingsForm'
			method='post'
			action='<?php u('processPagination.php'); ?>'>
			<input type="hidden" name='action' value='setPerPage'>
			<input type='hidden' name='referer' value='<?php h($_SERVER['REQUEST_URI']); ?>'>
			<label for='paginationPerPageSelect'>Files per page:</label>
			<select name="paginationPerPage" id='paginationPerPageSelect'>
				<option value="10" <?php h(
		$paginationPerPage == 10 ? 'selected=\'selected\'' : '')
								   ?>>10</option>
				<option value="20" <?php h(
										   $paginationPerPage == 20 ? 'selected=\'selected\''
												   : '')
								   ?>>20</option>
				<option value="50" <?php h(
										   $paginationPerPage == 50 ? 'selected=\'selected\''
												   : '')
								   ?>>50</option>
				<option value="100" <?php h(
										   $paginationPerPage == 100 ? 'selected=\'selected\''
												   : '')
									?>>100</option>
				<option value="1000000000" <?php h(
											$paginationPerPage == 1000000000 ? 'selected=\'selected\''
													: '')
										   ?>>all</option>
			</select>
			<input id='paginationSettingsSubmit' type="submit" value="Set">
		</form>
	</div>
	<div id='paginationLinks'>
<?php
										   echo $paging->export_paging();
?>
	</div>
	<div id='paginationText'>
	Showing files <?php echo $paging->get_start() + 1; ?> -
	<?php echo $paging->get_start() + count($children); ?>
	of <?php echo count($childrenIds); ?>
	</div>
</div>
<?php
include 'pieces/page-end.php';
?>