<?php

require_once 'utils/initPage.php';
require_once 'utils/file.php';
require_once 'utils/search.php';

if (isset($_GET['root'])) {
	$root = $_GET['root'];
} else {
	saveMessage(new Message("Missing root id", Message::SEVERITY_ERROR));
	header("Location: " . url("index.php", NULL));
	exit();
}

$rootFile = getRootFile($root, $db);
if (isset($_GET['ancestor'])) {
	$file = getFile($_GET['ancestor'], $rootFile, $root, $db);
} else {
	$file = $rootFile;
}

if (isset($_SESSION['search'])) {
	list($nameLike, $historyNames, $nameCaseSensitive, $isDirectory, $isDeleted, $lengthLess, $lengthGreater, $versionLess, $versionGreater, $modifiedBefore, $modifiedAfter, $mimeLike, $firstMentionBefore, $firstMentionAfter, $tag, $comment, $historyComments, $ancestor, $historyAncestors, $routine) = $_SESSION['search']
			+ Array(null, null, null, null, null, null, null, null, null, null,
					null, null, null, null, null, null, null, null, null, null);
	$ids = search($nameLike, $historyNames, $nameCaseSensitive, $isDirectory,
			$isDeleted, $lengthLess, $lengthGreater, $versionLess,
			$versionGreater, $modifiedBefore, $modifiedAfter, $mimeLike,
			$firstMentionBefore, $firstMentionAfter, $tag, $comment,
			$historyComments, $ancestor, $historyAncestors, $routine, $db);
} else {
	unset($_SESSION['search']);
	list($nameLike, $historyNames, $nameCaseSensitive, $isDirectory, $isDeleted, $lengthLess, $lengthGreater, $versionLess, $versionGreater, $modifiedBefore, $modifiedAfter, $mimeLike, $firstMentionBefore, $firstMentionAfter, $tag, $comment, $historyComments, $ancestor, $historyAncestors, $routine) = Array(
			null, null, null, null, null, null, null, null, null, null, null,
			null, null, null, null, null, null, null, null, null);
	$isDeleted = false;
	$ids = array();
}
$ancestor = $file->getId();

if (!isset($_GET['resultsOnly'])) {
	$title = 'Browse files in WebFS';
	include 'pieces/page-start.php';
?>
<h1>Search directory <?php h($file->getName()); ?></h1>
<div id='criteria'>
	<form id='criteriaForm' action='<?php u('processSearch.php'); ?>' method="post">
		<input type="hidden" name='root' value='<?php echo $root; ?>'>
		<input type="hidden" name='ancestor' value='<?php echo $ancestor; ?>'>
<!-- 		<input type="hidden" name='resultsOnly' value='resultsOnly'> -->
		<fieldset>
			<legend>Name and attributes</legend>
			<label for='nameLike' class='leftLabel'>Name is like:</label>
				<input type="text" id='nameLike' name='nameLike'
					value='<?php h($nameLike); ?>'>
			<label for='historyNames'>History names:</label>
				<input type="checkbox" id='historyNames'
					name='historyNames' value='1'
					<?php h($historyNames == NULL ? '' : 'checked=\'checked\'') ?>>
			<label for='nameCaseSensitive'>CaSe sensitive:</label>
				<input type="checkbox" id='nameCaseSensitive'
					name='nameCaseSensitive' value='1'
					<?php h(
			$nameCaseSensitive == NULL ? '' : 'checked=\'checked\'')
					?>>
				<br>
			<label for='mimeLike' class='leftLabel'>Mime type is:</label>
				<input type="text" id='mimeLike' name='mimeLike'
					value='<?php h($mimeLike); ?>'>
				<br>
			<label for='isDirectory' class='leftLabel'>Directory:</label>
				<select name="isDirectory" id='isDirectory'>
					<option value='-1'
						<?php h(
								$isDirectory === NULL ? 'selected=\'selected\''
										: '')
						?>>Don't care</option>
					<option value='1'
						<?php h(
									$isDirectory === true ? 'selected=\'selected\''
											: '')
						?>>Yes</option>
					<option value='0'
						<?php h(
									$isDirectory === false ? 'selected=\'selected\''
											: '')
						?>>No</option>
				</select>
				<br>
			<label for='isDeleted' class='leftLabel'>Deleted:</label>
				<select name="isDeleted" id='isDeleted'>
					<option value='-1'
						<?php h(
									$isDeleted === NULL ? 'selected=\'selected\''
											: '')
						?>>Don't care</option>
					<option value='1'
						<?php h(
									$isDeleted === true ? 'selected=\'selected\''
											: '')
						?>>Yes</option>
					<option value='0'
						<?php h(
									$isDeleted === false ? 'selected=\'selected\''
											: '')
						?>>No</option>
				</select>
				<br>
			<label for='lengthGreater' class='leftLabel'>Length is between</label>
				<input type="text" id='lengthGreater' name='lengthGreater'
					value='<?php h($lengthGreater); ?>'>
			<label for='lengthLess'>and</label>
				<input type="text" id='lengthLess' name='lengthLess'
					value='<?php h($lengthLess); ?>'>
				<br>
			<label for='versionGreater' class='leftLabel'>Version is between</label>
				<input type="text" id='versionGreater' name='versionGreater'
					value='<?php h($versionGreater); ?>'>
			<label for='versionLess'>and</label>
				<input type="text" id='versionLess' name='versionLess'
					value='<?php h($versionLess); ?>'>
				<br>
			<label for='modifiedAfter' class='leftLabel'>Modified between</label>
				<input type="text" id='modifiedAfter' name='modifiedAfter'
					value='<?php h(formatDate($modifiedAfter)); ?>'>
			<label for='modifiedBefore'>and</label>
				<input type="text" id='modifiedBefore' name='modifiedBefore'
					value='<?php h(formatDate($modifiedBefore)); ?>'>
				<br>
			<label for='firstMentionAfter' class='leftLabel'>First mention between</label>
				<input type="text" id='firstMentionAfter' name='firstMentionAfter'
					value='<?php h(formatDate($firstMentionAfter)); ?>'>
			<label for='firstMentionBefore'>and</label>
				<input type="text" id='firstMentionBefore' name='firstMentionBefore'
					value='<?php h(formatDate($firstMentionBefore)); ?>'>
				<br>
		</fieldset>

		<fieldset>
			<legend>Tags, comments, routines</legend>
			<label for='tag' class='leftLabel'>Tag:</label>
				<input type="text" id='tag' name='tag'  class='newTagName'
					value='<?php h($tag); ?>'>
				<br>
			<label for='comment' class='leftLabel'>Comment:</label>
				<input type="text" id='comment' name='comment'
					value='<?php h($comment); ?>'>
			<label for='historyComments'>History comments:</label>
				<input type="checkbox" id='historyComments'
					name='historyComments' value='1'
					<?php h(
									$historyComments == NULL ? ''
											: 'checked=\'checked\'')
					?>>
				<br>
			<label for='routine' class='leftLabel'>Routine:</label>
				<select name="routine" id='routine'>
					<option value='-1'
						<?php h(
								$routine === NULL ? 'selected=\'selected\'' : '')
						?>>Don't care</option>
<?php
							foreach (Routine::getAllRoutines($db) as $r) {
?>
					<option value='<?php echo $r->getId(); ?>'
						<?php h(
				$routine == $r->getId() ? 'selected=\'selected\'' : '')
						?>>
						<?php h($r->getComment()); ?></option>
<?php
							}
?>
				</select>
		</fieldset>
		<input type="submit" value='Search' name='search'>
		<a onclick="return confirm('Reset the entire form?')"
			href='<?php u('searchSession.php',
			array('root' => $root, 'ancestor' => $ancestor))
				  ?>'>Reset search form</a>
	</form>
</div>
<hr style='background-color:black;height:10px' >
<?php
				  }
?>
<div id='results'>
<?php
if (count($ids) == 0) {
?>
	<div id='resultsHeader' class='noResult'>No file matched your criteria</div>
<?php
} else {
?>
	<div id='resultsHeader'><?php echo count($ids) . ' files has been found'; ?></div>
<?php
}

require_once 'utils/pagination.php';
?>
<table id='resultTable' class='tablesorter'>
	<thead>
		<tr>
			<?php paginationFilesSortLink(0, 'Type') ?>
			<?php paginationFilesSortLink(1, 'Name') ?>
			<?php paginationFilesSortLink(2, 'Size') ?>
			<?php paginationFilesSortLink(3, 'Modified') ?>
			<?php paginationFilesSortLink(4, 'Version') ?>
			<?php paginationFilesSortLink(5, 'First mention') ?>
			<?php paginationFilesSortLink(6, 'Parent') ?>
			<th>Info</th>
		</tr>
	</thead>
	<tbody>
<?php

require_once 'libs/class.paging.phps';

if (!isset($_SESSION['paginationPerPage'])) {
	$_SESSION['paginationPerPage'] = 10;
}
$paginationPerPage = $_SESSION['paginationPerPage'];

$paging = new Paging(count($ids), NULL, $paginationPerPage, '&page=%s');
$paging->set_title_format('Page %s');
$paging->set_around(5);
$paging->set_paging_mode(1);
$paging->set_output_mode(1);
$paging->set_paging(1);

$row = 0;
for ($i = $paging->get_start(); $i
		<= $paging->get_limit() + $paging->get_start() - 1 && $i < count($ids); $i++) {
	$id = $ids[$i];
	$child = new File($id, $db);
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
<?php
	if ($child->getParent() != NULL) {
		$parent = new File($child->getParent(), $db);
?>
				<a href='<?php u('files.php',
				array('root' => $_GET['root'], 'parent' => $parent->getId()));
						 ?>'>
					<?php h($parent->getName()); ?></a>
<?php
							 } else {
?>
				This file is root
<?php
	}
?>
			</td>
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
	Showing files <?php echo (count($ids) > 0 ? $paging->get_start() + 1 : 0); ?> -
	<?php echo $paging->get_start() + $row; ?>
	of <?php echo count($ids); ?>
	</div>
</div>
</div>
<?php
if (!isset($_GET['resultsOnly'])) {
	include 'pieces/page-end.php';
} else {
?>
<div id="messages">
	<ul id='messagesList'>
	<?php
	foreach ($_SESSION['messages'] as $message) {
	?>
		<li class='<?php echo $message->getClass(); ?>'>
			<?php h($message->getMessage()); ?></li>
	<?php
	}
	$_SESSION['messages'] = array();
	?>
	</ul>
</div>
<?php
}
?>