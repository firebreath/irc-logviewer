<?php

ini_set('display_errors','1');
ini_set('log_errors','1');

include_once('../lib/ListChannels.class.php');

$channels = ListChannels::getList($_REQUEST['server']);	

echo json_encode($channels);

?>