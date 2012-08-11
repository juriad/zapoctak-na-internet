<?php

function getRootFile($rootId, PDO $db) {
	if (isset($rootId)) {
		$a1 = array();
		$a2 = array();
		try {
			$ur = new UserRoot($rootId, $a1, $a2, $db);
		} catch (Exception $e) {
			$ur = false;
		}
		if (!$ur || $ur->getUser()->getId() != $_SESSION['user']->getId()
				|| !$ur->isActive() || !$ur->getRoot()->getRootFile()
				|| $ur->getRoot()->getState() < 0) {
			saveMessage(new Message("Unknown root id", Message::SEVERITY_ERROR));
			header("Location: " . url("index.php", NULL));
			exit();
		}
		try {
			$rootFile = new File($ur->getRoot()->getRootFile(), $db);
		} catch (Exception $e) {
			saveMessage(new Message("Root file error", Message::SEVERITY_ERROR));
			header("Location: " . url("index.php", NULL));
			exit();
		}
	} else {
		saveMessage(new Message("Missing root id", Message::SEVERITY_ERROR));
		header("Location: " . url("index.php", NULL));
		exit();
	}
	return $rootFile;
}

function getFile($fileId, File $rootFile, $root, PDO $db) {
	if (isset($fileId)) {
		try {
			$file = new File($fileId, $db);
		} catch (Exception $e) {
			saveMessage(new Message("Unknown file id", Message::SEVERITY_ERROR));
			header("Location: " . url("files.php", array('root' => $root)));
			exit();
		}
		if (!$file->isDescendantOf($rootFile->getId())) {
			saveMessage(new Message("Unknown file id", Message::SEVERITY_ERROR));
			header("Location: " . url("files.php", array('root' => $root)));
			exit();
		}
	} else {
		saveMessage(new Message("Missing file id", Message::SEVERITY_ERROR));
		header("Location: " . url("files.php", array('root' => $root)));
		exit();
	}
	return $file;
}
?>