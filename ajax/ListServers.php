<?php

ini_set('display_errors','1');
ini_set('log_errors','1');

include_once('../lib/ListServers.class.php');

$servers = ListServers::getList();	

echo json_encode($servers);

?>