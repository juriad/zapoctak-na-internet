<?php

class FileData {

	private $id, $fileId, $version, $metadataId, $metadata, $fileName;

	private $text, $wrap;

	function __construct($id, PDO $db) {
		$selectFileData = $db
				->prepare(
						'SELECT fileId, version, metadataId, fileName
						FROM files_metadata
						WHERE id = ?');
		execute($selectFileData, $id, PDO::PARAM_INT);
		$row = $selectFileData->fetch(PDO::FETCH_ASSOC);
		if (!$row) {
			throw new InvalidArgumentException('no file data with such id');
		}
		$this->id = $id;
		$this->fileId = $row['fileId'];
		$this->version = $row['version'];
		$this->metadataId = $row['metadataId'];
		$this->metadata = Metadata::getMetadata($this->getMetadataId(), $db);
		$this->fileName = $row['fileName'];
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

	function getMetadataId() {
		return $this->metadataId;
	}

	function getMetadata() {
		return $this->metadata;
	}

	function getFileName() {
		return $this->fileName;
	}

	// array(text, wrap)
	function fetchText(PDO $db) {
		if (!isset($this->text)) {
			$selectText = $db
					->prepare(
							'SELECT content, wrap
							FROM textData
							WHERE id = ?');
			// must be PARAM_STR - table is virtual fts4
			execute($selectText, $this->getId(), PDO::PARAM_STR);
			$row = $selectText->fetch(PDO::FETCH_ASSOC);
			if (!$row) {
				throw new InvalidArgumentException('no such text data');
			}
			$this->text = $row['content'];
			$this->wrap = $row['wrap'] == 1;
		}
		return array('text' => $this->text, 'wrap' => $this->wrap);
	}
}

?>