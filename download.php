<?php

require_once 'utils/initPage.php';
require_once 'utils/file.php';

$rootFile = getRootFile($_GET['root'], $db);
$file = getFile($_GET['fileId'], $rootFile, $_GET['root'], $db);

function download($fileName, $name, $type, $image) {
	if (file_exists($fileName)) {
		header('Content-Description: File Transfer');
		header('Content-Type: ' . $type);
		if (!$image) {
			header('Content-Disposition: attachment; filename=' . $name);
		}
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($fileName));
		ob_clean();
		flush();
		readfile($fileName);
	} else {
		notFound();
	}
}

function notFound() {
	header("HTTP/1.0 404 Not Found");
}

if (isset($_GET['file'])) {
	download($file->getRealPath(), $file->getName(), $file->getMime(), false);
} else if (isset($_GET['data'])) {
	try {
		$data = new FileData($_GET['data'], $db);
		switch ($data->getMetadata()->getType()) {
		case 'image':
			$type = 'image/jpeg';
			$image = true;
			break;
		case 'file':
			$type = $file->getMime();
			$image = false;
			break;
		default:
			$type = 'application/octet-stream';
			$image = false;
			break;
		}
		download($data->getFileName(),
				$data->getFileName() . "-" . $file->getName(), $type, $image);
	} catch (Exception $e) {
		notFound();
	}
} else {
	notFound();
}

exit;
?>