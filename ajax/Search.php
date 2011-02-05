<?php

ini_set('display_errors','0');
ini_set('log_errors','1');

include_once('../lib/Search.class.php');

$server = $_REQUEST['server'];
$channel = $_REQUEST['channel'];
$keywords = $_REQUEST['keywords'];

if (get_magic_quotes_gpc()) {
	$server = stripslashes($server);
	$channel = stripslashes($channel);
	$keywords = stripslashes($keywords);
}

$searchResults = new Search($server, $channel, $keywords);

echo json_encode($searchResults);

?>