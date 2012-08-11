<?php

require_once 'utils/initPage.php';
unset($_SESSION['search']);

header('Location: ' . url('search.php', $_GET));
exit();
?>
