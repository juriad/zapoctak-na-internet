<menu>
	<li><a href="<?php u('index.php'); ?>">Roots</a></li>
	<li><a href="<?php u('user.php') ?>">User</a></li>
<?php
if ($_SESSION['user']->hasRole('roots') || $_SESSION['user']->hasRole('users')) {
?>
	<li><a>Administration</a>
	<menu>
<?php
	if ($_SESSION['user']->hasRole('roots')) {
?>
		<li><a href="<?php u('roots.php') ?>">Roots</a></li>
<?php
	}
	if ($_SESSION['user']->hasRole('users')) {
?>
		<li><a href="<?php u('users.php') ?>">Users</a></li>
<?php
	}
?>
	</menu>
	</li>
<?php
}
?>
	<li><a href="<?php u('index.php', array('logout' => 1)); ?>">Log out</a></li>
	<li style='float:right;'><a href="<?php u('help.php'); ?>">Help</a></li>
</menu>