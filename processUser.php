<?php

require_once 'utils/initPage.php';

if (isset($_POST['action']) && $_POST['action'] == 'changePass') {
	if (isset($_POST['password'])
			&& trim($_POST['password']) == $_POST['password']
			&& strlen($_POST['password']) > 0) {
		$user = $_SESSION['user'];
		$user->setPassword($_POST['password'], $db);
		saveMessage(
				new Message("Password has been changed",
						Message::SEVERITY_SUCCESS));
	} else if (isset($_POST['password'])
			&& trim($_POST['password']) != $_POST['password']) {
		saveMessage(
				new Message(
						"Password contains leading or trailing blanks, change it!",
						Message::SEVERITY_ERROR));
	} else {
		saveMessage(
				new Message("Missing new password", Message::SEVERITY_ERROR));
	}
} else {
	saveMessage(new Message("Missing action", Message::SEVERITY_ERROR));
}
header("Location: " . url('user.php', NULL));
exit();

?>