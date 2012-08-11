<?php

class Comment {
	private $id, $added, $userId, $body;

	function __construct($id, PDO $db) {
		$sel = $db
				->prepare(
						"SELECT c.id AS id, datetime(c.added, 'localtime') AS added,
						c.userId AS userId, b.body
						FROM comments c, commentBodies b
						WHERE b.id = c.id AND c.id = ?");
		execute($sel, $id, PDO::PARAM_INT);
		$row = $sel->fetch(PDO::FETCH_ASSOC);
		if (!$row) {
			throw new InvalidArgumentException('no comment with such id');
		}
		$this->id = $id;
		$this->added = $row['added'];
		$this->userId = $row['userId'];
		$this->body = $row['body'];
	}

	function getId() {
		return $this->id;
	}

	function getAdded() {
		return $this->added;
	}

	function getUserId() {
		return $this->userId;
	}

	function getBody() {
		return $this->body;
	}

	static function getAllCommentIdsFor($targetId, $targetTable, PDO $db) {
		$sel = $db
				->prepare(
						"SELECT id
						FROM comments
						WHERE targetId = ? AND targetTable = ?
						ORDER BY added");
		execute($sel, $targetId, PDO::PARAM_INT, $targetTable, PDO::PARAM_STR);
		$selIds = $sel->fetchAll(PDO::FETCH_ASSOC);
		$ids = array();
		foreach ($selIds as $id) {
			array_push($ids, $id['id']);
		}
		return $ids;
	}

	static function getAllCommentsFor($targetId, $targetTable, PDO $db) {
		$comments = array();
		foreach (self::getAllCommentIdsFor($targetId, $targetTable, $db) as $commentId) {
			array_push($comments, new Comment($commentId, $db));
		}
		return $comments;
	}

	static function addCommentFor($targetId, $targetTable, $userId, $body,
			PDO $db) {
		$insert = $db
				->prepare(
						"INSERT INTO comments (userId, targetId, targetTable)
						VALUES (?, ?, ?)");
		execute($insert, $userId, PDO::PARAM_INT, $targetId, PDO::PARAM_INT,
				$targetTable, PDO::PARAM_STR);
		$id = $db->lastInsertId();
		$insertBody = $db
				->prepare(
						"INSERT INTO commentBodies (id, body)
						VALUES (?, ?)");
		execute($insertBody, $id, PDO::PARAM_INT, $body, PDO::PARAM_STR);
	}

	static function removeComment($id, $userId, PDO $db) {
		$delete = $db
				->prepare(
						"DELETE FROM comments
						WHERE userId = ? AND id = ?");
		execute($delete, $userId, PDO::PARAM_INT, $id, PDO::PARAM_INT);
	}
}

?>