<?php
require_once 'utils/initPage.php';
require_once 'utils/file.php';

function addNewRule($userId, $name, $routineId, File $file, PDO $db) {
	// set criterion, value, subdirectories
	if ($file->isDirectory()) {
		if (isset($_POST['criterion'])) {
			switch ($_POST['criterion']) {
			case 'MIME':
				$criterion = 'MIME';
				break;
			case 'MASK':
				$criterion = 'MASK';
				break;
			case 'IMASK':
				$criterion = 'IMASK';
				break;
			default:
				saveMessage(
						new Message("Invalid criterion",
								Message::SEVERITY_ERROR));
				return false;
			}
		} else {
			saveMessage(
					new Message("Missing criterion", Message::SEVERITY_ERROR));
			return false;
		}
		if (isset($_POST['value']) && !isBlank($_POST['value'])) {
			$value = trim($_POST['value']);
		} else {
			saveMessage(
					new Message("Value must not be blank",
							Message::SEVERITY_ERROR));
			return false;
		}
		if (isset($_POST['subdirectories']) && $_POST['subdirectories']) {
			$subdirectories = true;
		} else {
			$subdirectories = false;
		}
	} else {
		$criterion = 'TRUE';
		$value = 'TRUE';
		$subdirectories = false;
	}
	if (Rule::createRule($userId, $name, $criterion, $value, $routineId,
			$file->getId(), $subdirectories, $db)) {
		return true;
	} else {
		saveMessage(
				new Message("Error while adding rule", Message::SEVERITY_ERROR));
		return false;
	}
}

if (isset($_POST['action'])
		&& ($_POST['action'] == 'add' || $_POST['action'] == 'remove')) {
	$rootFile = getRootFile($_POST['root'], $db);
	$file = getFile($_POST['fileId'], $rootFile, $_POST['root'], $db);

	if ($_POST['action'] == 'add') {
		if (isset($_POST['name']) && isset($_POST['routineId'])) {
			if (isBlank($_POST['name'])) {
				saveMessage(
						new Message("Name must not be blank",
								Message::SEVERITY_ERROR));
			} else {
				try {
					$routine = Routine::getRoutine($_POST['routineId'], $db);
					$name = trim($_POST['name']);
					if (addNewRule($_SESSION['user']->getId(), $name,
							$routine->getId(), $file, $db)) {
						saveMessage(
								new Message("Rule has been added",
										Message::SEVERITY_SUCCESS));
					}
				} catch (Exception $e) {
					saveMessage(
							new Message("Unknown routine",
									Message::SEVERITY_ERROR));
				}
			}
		} else {
			saveMessage(
					new Message("Missing name or routineId",
							Message::SEVERITY_ERROR));
		}
	} else if ($_POST['action'] == 'remove') {
		if (isset($_POST['ruleId'])) {
			try {
				$rule = new Rule($_POST['ruleId'], $db);
				if ($rule->getFileId() == $file->getId()
						&& $rule->getUserId() == $_SESSION['user']->getId()) {
					$rule->removeRule($db);
					saveMessage(
							new Message("Rule has been removed",
									Message::SEVERITY_SUCCESS));
				} else {
					saveMessage(
							new Message("Unknown rule id",
									Message::SEVERITY_ERROR));
				}
			} catch (Exception $e) {
				saveMessage(
						new Message("Unknown rule id", Message::SEVERITY_ERROR));
			}
		} else {
			saveMessage(new Message("Missing rule id", Message::SEVERITY_ERROR));
		}
	}
} else {
	saveMessage(new Message("Missing action", Message::SEVERITY_ERROR));
}
header(
		"Location: "
				. url('file.php',
						array('root' => $_POST['root'],
								'id' => $_POST['fileId'])));
exit();
?>