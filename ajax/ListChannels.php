<?php

ini_set('display_errors','0');
ini_set('log_errors','1');

include_once('../lib/ListChannels.class.php');

$server = $_REQUEST['server'];

if (get_magic_quotes_gpc()) {
	$server = stripslashes($server);
}

$channels = ListChannels::getList($server);

echo json_encode($channels);

?>