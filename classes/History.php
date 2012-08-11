<?php

class History {

	private $items, $file;

	function __construct(File $file, PDO $db) {
		$this->file = $file;
		$this->items = array();
		$selectFile = $db
				->prepare(
						'SELECT h.id AS id, h.fileId AS fileId, h.version AS version, h.name AS name,
						datetime(h.modified, \'unixepoch\', \'localtime\') AS modified, h.length AS length, h.mime AS mime,
						datetime(s.time, \'localtime\') AS scan, h.parent AS parent, h.path AS path, h.realPath AS realPath
						FROM history h
						LEFT JOIN scans s ON s.id=h.scanId
						WHERE h.fileId=?
						ORDER BY h.version DESC');
		execute($selectFile, $file->getId(), PDO::PARAM_INT);
		$rows = $selectFile->fetchAll(PDO::FETCH_ASSOC);

		foreach ($rows as $row) {
			array_push($this->items, new HistoryItem($row));
		}
	}

	function getItems() {
		return $this->items;
	}

	function getFile() {
		return $this->file;
	}
}

?>