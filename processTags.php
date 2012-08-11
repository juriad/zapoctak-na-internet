<?php
require_once 'utils/initPage.php';
require_once 'utils/file.php';

if (isset($_POST['action'])
		&& ($_POST['action'] == 'add' || $_POST['action'] == 'remove')) {
	if (isset($_POST['name']) && !isBlank($_POST['name'])) {
		$name = trim($_POST['name']);
		$rootFile = getRootFile($_POST['root'], $db);
		$file = getFile($_POST['fileId'], $rootFile, $_POST['root'], $db);
		if ($_POST['action'] == 'add') {
			$file->addTag($name, $_SESSION['user']->getId(), $db);
		} else if ($_POST['action'] == 'remove') {
			$file->removeTag($name, $_SESSION['user']->getId(), $db);
		}
	} else {
		saveMessage(
				new Message("Missing tag name or tag is blank",
						Message::SEVERITY_ERROR));
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