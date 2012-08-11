<?php

class Routine {

	private static $routines = array();

	private $id, $name, $comment, $metadataId, $metadata;

	private function __construct($id, PDO $db) {
		$selectRoutine = $db
				->prepare(
						'SELECT name, comment, metadataId
						FROM routines
						WHERE id = ?');
		execute($selectRoutine, $id, PDO::PARAM_INT);
		$row = $selectRoutine->fetch(PDO::FETCH_ASSOC);
		if (!$row) {
			throw new InvalidArgumentException('no routine with such id');
		}
		$this->id = $id;
		$this->name = $row['name'];
		$this->comment = $row['comment'];
		$this->metadataId = $row['metadataId'];
		$this->metadata = Metadata::getMetadata($this->getMetadataId(), $db);
	}

	function getId() {
		return $this->id;
	}

	function getName() {
		return $this->name;
	}

	function getComment() {
		return $this->comment;
	}

	function getMetadataId() {
		return $this->metadataId;
	}

	function getMetadata() {
		return $this->metadata;
	}

	static function getRoutine($id, PDO $db) {
		if (!isset(self::$routines[$id])) {
			self::$routines[$id] = new Routine($id, $db);
		}
		return self::$routines[$id];
	}

	static function getAllRoutines($db) {
		$selectRoutines = $db->prepare('SELECT id
										FROM routines');
		execute($selectRoutines);
		$rows = $selectRoutines->fetchAll(PDO::FETCH_ASSOC);
		$all = array();
		foreach ($rows as $id) {
			array_push($all, self::getRoutine($id['id'], $db));
		}
		return $all;
	}
}

?>