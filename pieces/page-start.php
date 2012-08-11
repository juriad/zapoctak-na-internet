<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title><?php h($title); ?></title>
	<link rel="stylesheet" href="style.css">
	<link rel="stylesheet" href="jquery-ui-1.8.18.custom.css">
	<script src="jquery-1.7.1.min.js"></script>
	<script src="jquery.tablesorter.min.js"></script>
	<script src="jquery-ui-1.8.18.custom.min.js"></script>
	<script src="dateUtils.js"></script>
	<script src="script.js"></script>
</head>
<body>
	<div id="main">
		<div id="header">
<?php
include 'pieces/header.php';
?>
		</div>
		<div id="page">
<?php
if (isset($_SESSION['user'])) {
?>
			<div id="menu">
<?php
	include 'pieces/menu.php';
?>
			</div>
<?php
}
?>
			<div id="content">
				<div id="messages">
<?php
if (isset($_SESSION['messages']) && count($_SESSION['messages']) > 0) {
?>
					<ul id='messagesList'>
<?php
	foreach ($_SESSION['messages'] as $message) {
?>
						<li class='<?php echo $message->getClass(); ?>'>
						<?php h($message->getMessage()); ?></li>
<?php
	}
	$_SESSION['messages'] = array();
?>
					</ul>
<?php
}
?>
				</div>