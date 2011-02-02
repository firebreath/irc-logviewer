<?php

ini_set('display_errors','0');
ini_set('log_errors','1');

include_once('../lib/GetConversation.class.php');

$keywords = stripslashes($_REQUEST['keywords']);	
$keywords = preg_replace("/( )+/", " ", $keywords);
$keywords = preg_replace("/ $/", "", $keywords);
$keywords = preg_replace("/^ /", "", $keywords);

$conversation = new GetConversation($_REQUEST['server'], $_REQUEST['channel'], $_REQUEST['startTime'], $_REQUEST['endTime'], $keywords);

echo json_encode($conversation);

?>