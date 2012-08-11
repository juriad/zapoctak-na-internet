<?php

require_once 'utils/functions.php';
session_start();

if (isset($_SESSION['user'])) {
	header("Location: " . url("index.php", NULL));
	exit();
}

if (isset($_POST['username']) && isset($_POST['password'])) {
	require_once 'utils/requireDB.php';
	$selectId = $db
			->prepare(
					'SELECT id
					FROM users
					WHERE name=? AND password=?');
	execute($selectId, $_POST['username'], PDO::PARAM_STR,
			sha1($_POST['password']), PDO::PARAM_STR);
	$row = $selectId->fetch(PDO::FETCH_ASSOC);
	if ($row) {
		$id = $row['id'];
		$user = new User($id, $db);
		if (!$user->isActive()) {
			saveMessage(
					new Message('User has been blocked',
							Message::SEVERITY_ERROR));
			header("Location: " . url("login.php", NULL));
			exit();
		} else {
			$_SESSION['user'] = $user;
			$user->refreshLastLogged($db);
			header("Location: " . url("index.php", NULL));
			exit();
		}
	} else {
		saveMessage(
				new Message('Wrong name or password', Message::SEVERITY_ERROR));
		header("Location: " . url("login.php", NULL));
		exit();
	}
}

$title = 'Login to WebFS';
include 'pieces/page-start.php';

?>
<div id="login">
	<h1>Log in to WebFS</h1>
	<form action="<?php u('login.php') ?>" method="post" id="loginForm">
		<label for="username">Username:</label>
			<input type="text" id="username" name="username">
		<br>
		<label for="password">Password:</label>
			<input type="password" id="password" name="password">
		<br>
		<input type="submit" value="Submit">
	</form>
</div>
<?php
include 'pieces/page-end.php';
?>
