<?php

ini_set('display_errors','0');
ini_set('log_errors','1');

include_once('../lib/GetConversation.class.php');

$server = $_REQUEST['server'];
$channel = $_REQUEST['channel'];
$startTime = $_REQUEST['startTime'];	
$endTime = $_REQUEST['endTime'];	
$keywords = $_REQUEST['keywords'];

if (get_magic_quotes_gpc()) {
	$server = stripslashes($server);
	$channel = stripslashes($channel);
	$startTime = stripslashes($startTime);
	$endTime = stripslashes($endTime);	
	$keywords = stripslashes($keywords);
}

$conversation = new GetConversation($server, $channel, $startTime, $endTime, $keywords);

echo json_encode($conversation);

?>