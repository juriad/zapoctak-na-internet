<?php

require_once 'utils/initPage.php';

if (isset($_GET['action']) && $_GET['action'] == 'setActive') {
	if (isset($_GET['userRootId']) && isset($_GET['state'])) {
		try {
			$a1 = array();
			$a2 = array();
			$ur = new UserRoot($_GET['userRootId'], $a1, $a2, $db);
			if ($ur->getUser()->getId() != $_SESSION['user']->getId()) {
				saveMessage(
						new Message("No root with such id",
								Message::SEVERITY_ERROR));
			} else if ($ur->getRoot()->getState() >= 0) {
				$ur->setActive($_GET['state'] == 1 ? true : false, $db);
				saveMessage(
						new Message(
								"Root has been "
										. ($ur->isActive() ? "activated"
												: "deactivated"),
								Message::SEVERITY_SUCCESS));
			} else {
				saveMessage(
						new Message("Cannot edit invalid root",
								Message::SEVERITY_ERROR));
			}
		} catch (Exception $e) {
			saveMessage(
					new Message("No root with such id", Message::SEVERITY_ERROR));
		}
	}
} else {
	saveMessage(new Message("Missing action", Message::SEVERITY_ERROR));
}

header("Location: " . url("index.php", NULL));
exit();

?>