<?php

$requireRoles = array('roots');
require_once 'utils/initPage.php';

if (isset($_POST['action']) && $_POST['action'] == 'modifyUsers') {
	if (!isset($_POST['rootId'])) {
		saveMessage(new Message("Unknown root id", Message::SEVERITY_ERROR));
		header("Location: " . url('roots.php', NULL));
	} else {
		try {
			$root = new Root($_POST['rootId'], $db);

			if ($root->getState() < 0) {
				saveMessage(
						new Message("Cannot edit invalid root",
								Message::SEVERITY_ERROR));
				header("Location: " . url('roots.php', NULL));
				exit();
			}

			if (isset($_POST['users'])) {
				$users = $_POST['users'];
			} else {
				$users = array();
			}

			foreach (User::getAllUserIds($db) as $userId) {
				if (in_array($userId, $users)) {
					$root->addUser($userId, $db);
				} else {
					$root->removeUser($userId, $db);
				}
			}

			saveMessage(
					new Message("Users has been updated",
							Message::SEVERITY_SUCCESS));
			header(
					"Location: "
							. url('root.php',
									array('rootId' => $_POST['rootId'])));
		} catch (Exception $e) {
			saveMessage(
					new Message("No root with such id", Message::SEVERITY_ERROR));
			header("Location: " . url('roots.php', NULL));
		}
	}
} else if (isset($_GET['action']) && $_GET['action'] == 'setActive') {
	if (isset($_GET['userRootId']) && isset($_GET['state'])) {
		try {
			$a1 = array();
			$a2 = array();
			$ur = new UserRoot($_GET['userRootId'], $a1, $a2, $db);
			if ($ur->getRoot()->getState() >= 0) {
				$ur->setActive($_GET['state'] == 1 ? true : false, $db);
				saveMessage(
						new Message(
								"Root has been "
										. ($ur->isActive() ? "activated"
												: "deactivated"),
								Message::SEVERITY_SUCCESS));
				header(
						"Location: "
								. url('root.php',
										array(
												'rootId' => $ur->getRoot()
														->getId())));
			} else {
				saveMessage(
						new Message("Cannot edit invalid root",
								Message::SEVERITY_ERROR));
				header("Location: " . url('roots.php', NULL));
			}
		} catch (Exception $e) {
			saveMessage(
					new Message("No root with such id", Message::SEVERITY_ERROR));
			header("Location: " . url('roots.php', NULL));
		}
	} else {
		saveMessage(new Message("Unknown root id", Message::SEVERITY_ERROR));
		header("Location: " . url('roots.php', NULL));
	}
} else {
	header("Location: " . url('roots.php', NULL));
}
exit();
?>