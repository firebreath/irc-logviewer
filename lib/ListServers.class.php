<?php

// Load config file
if (!array_key_exists('irc-logviewer-config', $GLOBALS))
	$GLOBALS['irc-logviewer-config'] = parse_ini_file("config.ini", true);

class ListServers {

	public function getList() {
	
		$logDir = $GLOBALS['irc-logviewer-config']['irc-logviewer']['irc_log_dir'];
		if (!is_dir($logDir))
			throw new Exception("IRC log directory not valid.");
			
		$result = array();				
		$dirHandle = opendir($logDir);
		$i = 0;
		while(($item = readdir($dirHandle)) !== false) {
			if(substr($item, 0, 1) != ".") { // Don't include hidden directories (i.e. beginning with a ".")
				if (is_dir($logDir."/".$item)) // Only list directories (not files)					
					array_push($result, $item);
			}
			$i++;
		}
		closedir($dirHandle);		
				
		return $result;
	}

}

?>