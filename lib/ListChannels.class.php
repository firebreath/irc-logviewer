<?php

// Load config file
if (!array_key_exists('irc_log_search', $GLOBALS))
	$GLOBALS['irc_log_search'] = parse_ini_file("config.ini", true);

class ListChannels {

	public function getList($server) {

		$logDir = $GLOBALS['irc_log_search']['options']['irc_log_dir'];
		
		// Use default (relative) directory of "logs" if no value explicitly specified
		if ($logDir == "")
			$logDir = dirname(__FILE__)."/../logs";
		
		$logDir .= "/".escapeshellcmd($server)."/";
		
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