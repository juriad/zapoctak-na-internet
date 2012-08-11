<!-- requires $targetId, $targetTable, $readOnly, $db -->
<?php
$comments = Comment::getAllCommentsFor($targetId, $targetTable, $db);
$target = "$targetTable$targetId";
if (count($comments) == 0) {
	if (!$readOnly) {
?>
<ul class='comments' id='comments<?php echo $target; ?>'>
	<li class='noComments'>There are no comments</li>
<?php
	}
} else {
?>
<ul class='comments' id='comments<?php echo $target; ?>'>
<?php
	$i = 0;
	$users = array($_SESSION['user']->getId() => $_SESSION['user']);
	foreach ($comments as $comment) {
?>
	<li id='comment<?php echo $target; ?>comment<?php echo $i; ?>'>
		<div class='commentHeader'>
<?php
		if (!isset($users[$comment->getUserId()])) {
			$users[$comment->getUserId()] = new User($comment->getUserId(), $db);
		}
?>
			<span class='author'>
				<?php h($users[$comment->getUserId()]->getName()); ?></span>
			<span data-time='<?php echo $comment->getAdded(); ?>' class='time'>
				<?php echo $comment->getAdded(); ?></span>
<?php
		if ($comment->getUserId() == $_SESSION['user']->getId() && !$readOnly) {
?>
			<form id='commentForm<?php echo $target; ?>comment<?php echo $i; ?>'
				class='commentForm removeCommentForm' method='post'
				action='<?php u('processComments.php') ?>'>
				<input type='hidden' name='referer' value='<?php h($_SERVER['REQUEST_URI']); ?>'>
				<input type="hidden" name='action' value='remove'>
				<input type="hidden" name='id'
					value='<?php echo $comment->getId(); ?>'>
				<input type="submit" value="x" class='removeButton'>
			</form>
<?php
		}
?>
		</div>
		<div class='commentBody'>
<pre>
<?php h($comment->getBody()); ?>
</pre>
		</div>
	</li>
<?php
		$i++;
	}
}
?>
<?php
if (!$readOnly) {
?>
	<li class='addNewComment' id='newComment<?php echo $target; ?>'>
		<form id='newCommentForm<?php echo $target; ?>'
			class='commentForm newCommentForm' method='post'
			action='<?php u('processComments.php'); ?>'>
			<input type="hidden" name='action' value='add'>
			<input type="hidden" name='targetId' value='<?php echo $targetId; ?>'>
			<input type="hidden" name='targetTable' value='<?php echo $targetTable; ?>'>
			<input type='hidden' name='referer' value='<?php echo $_SERVER['REQUEST_URI']; ?>'>
			<textarea rows="5" cols="60" name='body'></textarea>
			<input type="submit" value="Add">
		</form>
	</li>
<?php
}
if (count($comments) != 0 || !$readOnly) {
?>
</ul>
<?php
}
?>