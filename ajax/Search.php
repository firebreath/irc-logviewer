<?php

ini_set('display_errors','0');
ini_set('log_errors','1');

include_once('../lib/Search.class.php');

$keywords = stripcslashes($_REQUEST['keywords']);	
$keywords = strtolower($keywords);
$keywords = preg_replace("/( )+/", " ", $keywords);
$keywords = preg_replace("/ $/", "", $keywords);
$keywords = preg_replace("/^ /", "", $keywords);

$searchResults = new Search($_REQUEST['server'], $_REQUEST['channel'], $keywords);

echo json_encode($searchResults);

?>