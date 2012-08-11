<?php

class HistoryItem {

	protected $id, $version, $name, $modified, $mime, $scan, $length, $parent, $path, $realPath;

	function __construct($result) {
		$this->id = $result['id'];
		$this->fileId = $result['fileId'];
		$this->version = $result['version'];
		$this->name = $result['name'];
		$this->modified = $result['modified'];
		$this->scan = $result['scan'];
		$this->length = $result['length'];
		$this->mime = $result['mime'];
		$this->parent = $result['parent'];
		$this->path = $result['path'];
		$this->realPath = $result['realPath'];
	}

	function getId() {
		return $this->id;
	}

	function getFileId() {
		return $this->fileId;
	}

	function getVersion() {
		return $this->version;
	}

	function getName() {
		return $this->name;
	}

	function getModified() {
		return $this->modified;
	}

	function getLength() {
		return $this->length;
	}

	function getHumanLength($decimals = 2) {
		$sz = 'BKMGTP';
		$factor = floor((strlen($this->length) - 1) / 3);
		return sprintf("%.{$decimals}f", $this->length / pow(1024, $factor))
				. @$sz[$factor];
	}

	function getScan() {
		return $this->scan;
	}

	function getMime() {
		return $this->mime;
	}

	function getParent() {
		return $this->parent;
	}

	function getPath() {
		return $this->path;
	}

	function getRealPath() {
		return $this->realPath;
	}

	function fetchFileDataIds(PDO $db) {
		$selectFileData = $db
				->prepare(
						'SELECT id
						FROM files_metadata
						WHERE fileId = ? AND version = ?');
		execute($selectFileData, $this->getFileId(), PDO::PARAM_INT,
				$this->getVersion(), PDO::PARAM_INT);
		$rows = $selectFileData->fetchAll(PDO::FETCH_ASSOC);

		$ids = array();
		foreach ($rows as $row) {
			array_push($ids, $row['id']);
		}
		return $ids;
	}

	function fetchFileDatas(PDO $db) {
		$fileDatas = array();
		foreach ($this->fetchFileDataIds($db) as $id) {
			array_push($fileDatas, new FileData($id, $db));
		}
		return $fileDatas;
	}
}

?>