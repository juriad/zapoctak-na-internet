<?php

$requireRoles = array('users');
require_once 'utils/initPage.php';

if (isset($_POST['action']) && $_POST['action'] == 'addUser') {
	if (isset($_POST['username']) && isset($_POST['password'])) {
		if (!isAlnumOnly(trim($_POST['username']))) {
			saveMessage(
					new Message(
							"Username is invalid, use only letters and numbers, change it!",
							Message::SEVERITY_ERROR));
		} else if (trim($_POST['password']) != $_POST['password']) {
			saveMessage(
					new Message("Missing password", Message::SEVERITY_ERROR));
		} else if (strlen($_POST['password']) == 0) {
			saveMessage(
					new Message(
							"Password contains leading or trailing blanks, change it!",
							Message::SEVERITY_ERROR));
		} else {
			$active = false;
			if (isset($_POST['active']) && $_POST['active'] == 1) {
				$active = true;
			}
			$id = User::createNewUser($_POST['username'], $_POST['password'],
					$active, $db);
			if (!$id) {
				saveMessage(
						new Message("User was not be created",
								Message::SEVERITY_ERROR));
			} else {
				saveMessage(
						new Message("User has been created",
								Message::SEVERITY_SUCCESS));
			}
		}
	} else {
		saveMessage(
				new Message("Missing username or password",
						Message::SEVERITY_ERROR));
	}
} else if (isset($_POST['action']) && $_POST['action'] == 'changePass') {
	if (isset($_POST['userId']) && isset($_POST['password'])) {
		if (trim($_POST['password']) == $_POST['password']
				&& strlen($_POST['password']) > 0) {
			try {
				if ($_POST['userId'] == $_SESSION['user']->getId()) {
					$user = $_SESSION['user'];
				} else {
					$user = new User($_POST['userId'], $db);
				}
				$user->setPassword($_POST['password'], $db);
				saveMessage(
						new Message("Password has been changed",
								Message::SEVERITY_SUCCESS));
			} catch (Exeption $e) {
				saveMessage(
						new Message("No user with such id",
								Message::SEVERITY_ERROR));
			}
		} else if (trim($_POST['password']) != $_POST['password']) {
			saveMessage(
					new Message(
							"Password contains leading or trailing blanks, change it!",
							Message::SEVERITY_ERROR));
		} else {
			saveMessage(
					new Message("Missing new password", Message::SEVERITY_ERROR));
		}
	} else {
		saveMessage(
				new Message("Missing user id or password",
						Message::SEVERITY_ERROR));
	}
} else if (isset($_GET['action']) && $_GET['action'] == 'setActive') {
	if (isset($_GET['userId']) && isset($_GET['state'])) {
		try {
			if ($_GET['userId'] == $_SESSION['user']->getId()) {
				$user = $_SESSION['user'];
			} else {
				$user = new User($_GET['userId'], $db);
			}
			$user->setActive($_GET['state'] == 1 ? true : false, $db);
			saveMessage(
					new Message(
							"User has been "
									. ($user->isActive() ? "activated"
											: "deactivated"),
							Message::SEVERITY_SUCCESS));
		} catch (Exception $e) {
			saveMessage(
					new Message("No user with such id", Message::SEVERITY_ERROR));
		}
	} else {
		saveMessage(
				new Message("Missing user id or state", Message::SEVERITY_ERROR));
	}
} else {
	saveMessage(new Message("Missing action", Message::SEVERITY_ERROR));
}
header("Location: " . url('users.php', NULL));
exit();

?>