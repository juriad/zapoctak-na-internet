<?php

$requireRoles = array('users');
require_once 'utils/initPage.php';

if (isset($_POST['action']) && $_POST['action'] == 'modifyRoles') {
	if (!isset($_POST['userId'])) {
		saveMessage(new Message("Unknown user id", Message::SEVERITY_ERROR));
		header("Location: " . url("users.php", NULL));
	} else {
		try {
			if ($_POST['userId'] == $_SESSION['user']->getId()) {
				$user = $_SESSION['user'];
			} else {
				$user = new User($_POST['userId'], $db);
			}

			if (isset($_POST['roles'])) {
				$roles = $_POST['roles'];
			} else {
				$roles = array();
			}

			foreach (User::$ALL_ROLES as $role) {
				if (in_array($role, $roles)) {
					$user->addRole($role, $db);
				} else {
					$user->removeRole($role, $db);
				}
			}

			saveMessage(
					new Message("Roles has been updated",
							Message::SEVERITY_SUCCESS));
			header(
					"Location: "
							. url('roles.php',
									array('userId' => $_POST['userId'])));
		} catch (InvalidArgumentException $e) {
			saveMessage(
					new Message("No user with such id", Message::SEVERITY_ERROR));
			header("Location: " . url("users.php", NULL));
		}
	}
} else {
	saveMessage(new Message("Missing action", Message::SEVERITY_ERROR));
	if (isset($_POST['userId'])) {
		header(
				"Location: "
						. url('roles.php', array('userId' => $_POST['userId'])));
	} else {
		header("Location: " . url("users.php", NULL));
	}
}
exit();

?>