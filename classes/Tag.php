<?php

class Tag {

	private $name, $userId, $added;

	private static $all;

	function __construct($result) {
		$this->name = $result['name'];
		$this->userId = $result['userId'];
		$this->added = $result['added'];
	}

	function getName() {
		return $this->name;
	}

	function getUserId() {
		return $this->userId;
	}

	function getAdded() {
		return $this->added;
	}

	static function getAllTagNames(PDO $db) {
		if (!isset(self::$all)) {
			$sel = $db->prepare('SELECT id, name FROM tags');
			execute($sel);
			$tags = $sel->fetchAll(PDO::FETCH_ASSOC);
			self::$all = array();
			foreach ($tags as $tag) {
				array_push(self::$all, $tag['name']);
			}
		}
		return self::$all;
	}
}

?>