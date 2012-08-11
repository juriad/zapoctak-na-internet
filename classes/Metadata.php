<?php

class Metadata {

	private static $metadatas = array();

	private $id, $name, $type, $externalFile;

	private function __construct($id, PDO $db) {
		$selectMetadata = $db
				->prepare(
						'SELECT name, type, externalFile
						FROM metadata
						WHERE id = ?');
		execute($selectMetadata, $id, PDO::PARAM_INT);
		$row = $selectMetadata->fetch(PDO::FETCH_ASSOC);
		if (!$row) {
			throw new InvalidArgumentException('no routine with such id');
		}
		$this->id = $id;
		$this->name = $row['name'];
		$this->type = $row['type'];
		$this->externalFile = $row['externalFile'] == 1;
	}

	function getId() {
		return $this->id;
	}

	function getName() {
		return $this->name;
	}

	function getType() {
		return $this->type;
	}

	function isExternalFile() {
		return $this->externalFile;
	}

	static function getMetadata($id, PDO $db) {
		if (!isset(self::$metadatas[$id])) {
			self::$metadatas[$id] = new Metadata($id, $db);
		}
		return self::$metadatas[$id];
	}
}

?>