<!-- requires $tagFile, $readOnly, $db -->
<?php
$tags = $tagFile->fetchTags($db);
if (count($tags) == 0) {
	if (!$readOnly) {
?>
<ul class='tags' id='tags<?php echo $tagFile->getId(); ?>'>
	<li class='noTags'>There are no tags assigned</li>
<?php
	}
} else {
?>
<ul class='tags' id='tags<?php echo $tagFile->getId(); ?>'>
<?php
	$i = 0;
	foreach ($tags as $tag) {
?>
	<li id='tag<?php echo $tagFile->getId(); ?>tag<?php echo $i; ?>'>
<?php
		h($tag->getName());
		if ($tag->getUserId() == $_SESSION['user']->getId() && !$readOnly) {
?>
		<form id='removeTagForm<?php echo $tagFile->getId(); ?>tag<?php echo $i; ?>'
			class='tagForm removeTagForm' method='post'
			action='<?php u('processTags.php'); ?>'>
			<input type="hidden" name='action' value='remove'>
			<input type="hidden" name='root' value='<?php echo $_GET['root']; ?>'>
			<input type="hidden" name='fileId'
				value='<?php echo $tagFile->getId(); ?>'>
			<input type="hidden" name='name' value='<?php echo h(
					$tag->getName());
													?>'>
			<input type="submit" value="x" class='removeButton'>
		</form>
<?php
															}
?>
	</li>
<?php
		$i++;
	}
}
if (!$readOnly) {
?>
	<li class='addNewTag' id='newTag<?php echo $tagFile->getId(); ?>'>
		<form id='newTagForm<?php echo $tagFile->getId(); ?>'
			class='tagForm' method='post'
			action='<?php u('processTags.php'); ?>'>
			<input type="hidden" name='action' value='add'>
			<input type="hidden" name='root' value='<?php echo $_GET['root']; ?>'>
			<input type="hidden" name='fileId'
				value='<?php echo $tagFile->getId(); ?>'>
			<input type="text" name='name' class='newTagName'>
			<input type="submit" value="Add">
		</form>
	</li>
<?php
}
if (count($tags) != 0 || !$readOnly) {
?>
</ul>
<?php
}
?>