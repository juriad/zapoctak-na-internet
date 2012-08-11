<?php

// session_start() must have been called;
// array requireRoles may be present

if (!isset($_SESSION['user'])) {
	header("Location: " . url("login.php", NULL));
	exit();
}

if (isset($_GET['logout'])) {
	$_SESSION = array();
	if (isset($_COOKIE[session_name()])) {
		setcookie(session_name(), '', time() - 42000, '/');
	}
	session_unset();
	session_destroy();
	header("Location: " . url("login.php", NULL));
	exit();
}

if (isset($requireRoles)) {
	foreach ($requireRoles as $role) {
		if (!$_SESSION['user']->hasRole($role)) {
			header("Location: " . url("index.php", NULL));
			exit();
		}
	}
}
?>