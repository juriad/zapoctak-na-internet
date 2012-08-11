<?php

$requireRoles = array('roots');
require_once 'utils/initPage.php';

if (isset($_POST['action']) && $_POST['action'] == 'addRoot') {
	if (isset($_POST['name']) && isset($_POST['path'])
			&& isset($_POST['interval'])) {
		$interval = intval($_POST['interval']);
		if ($interval <= 0 || !isNumberOnly($_POST['interval'])) {
			saveMessage(
					new Message("Interval must be a positive number",
							Message::SEVERITY_ERROR));
		} else if (isBlank($_POST['name']) || isBlank($_POST['path'])) {
			saveMessage(
					new Message("Name and path must not be blank",
							Message::SEVERITY_ERROR));
		} else {
			$active = false;
			if (isset($_POST['active']) && $_POST['active'] == 1) {
				$active = true;
			}
			$name = trim($_POST['name']);
			$path = trim($_POST['path']);
			$id = Root::createNewRoot($name, $path, $interval, $active, $db);
			if (!$id) {
				saveMessage(
						new Message("Root was not created",
								Message::SEVERITY_ERROR));
			} else {
				saveMessage(
						new Message("Root has been created",
								Message::SEVERITY_SUCCESS));
			}
		}
	} else {
		saveMessage(
				new Message("Incomplete root information",
						Message::SEVERITY_ERROR));
	}
} else if (isset($_POST['action']) && $_POST['action'] == 'changeInterval') {
	if (isset($_POST['rootId']) && isset($_POST['interval'])) {
		try {
			$root = new Root($_POST['rootId'], $db);
			$interval = intval($_POST['interval']);
			if ($interval <= 0 || !isNumberOnly($_POST['interval'])) {
				saveMessage(
						new Message("Interval must be a positive number",
								Message::SEVERITY_ERROR));
			} else if ($interval == $root->getScanInterval()) {
				saveMessage(
						new Message("Interval has been changed, same value",
								Message::SEVERITY_INFO));
			} else {
				$root->setScanInterval($interval, $db);
				saveMessage(
						new Message("Interval has been changed",
								Message::SEVERITY_SUCCESS));
			}
		} catch (Exeption $e) {
			saveMessage(
					new Message("No root with such id", Message::SEVERITY_ERROR));
		}
	} else {
		saveMessage(
				new Message("Incomplete information", Message::SEVERITY_ERROR));
	}
} else if (isset($_GET['action']) && $_GET['action'] == 'setState') {
	if (isset($_GET['rootId']) && isset($_GET['state'])) {
		try {
			$root = new Root($_GET['rootId'], $db);
			if ($root->getState() >= 0) {
				$root->setState($_GET['state'] == 1 ? 1 : 0, $db);
				saveMessage(
						new Message(
								"Root has been "
										. ($root->getState() > 0 ? "activated"
												: "deactivated"),
								Message::SEVERITY_SUCCESS));
			} else {
				saveMessage(
						new Message("Cannot change state of invalid root",
								Message::SEVERITY_ERROR));
			}
		} catch (Exception $e) {
			saveMessage(
					new Message("No root with such id", Message::SEVERITY_ERROR));
		}
	} else {
		saveMessage(new Message("Unknown root id", Message::SEVERITY_ERROR));
	}
} else {
	saveMessage(new Message("Missing action", Message::SEVERITY_ERROR));
}
header("Location: " . url('roots.php', NULL));
exit();
?>