<?php

class PathValidator {

	// Provides a means to check $server is a valid directory
	// Designed to be secure regardless of Apache or PHP configuration.
	public function validateServerLogDir($baseLogDir, $server) {
				
		// Check base log dir is valid
		if (!is_dir($baseLogDir))
			throw new Exception("IRC log directory not valid. Please check config.ini");
		
		// Check server name is valid (i.e. points to a valid dir)
		$serverIsValid = false;
		$dirHandle = opendir($baseLogDir);
		while(($item = readdir($dirHandle)) !== false) {
			if(substr($item, 0, 1) != ".") { // Don't include hidden directories (i.e. beginning with a ".")
				if (is_dir($baseLogDir."/".$item)) // Only match directories
					if (strcmp($server,$item) == 0) {// If dir found in root of logdir
						$serverIsValid = true;
						break;
					}
			}
		}
		if ($serverIsValid !== true)
			throw new Exception("Server name not valid (no log directory exists for this server).");
					
		return true;		
	}

	// Provides a means to check both $server and $channel are valid directories
	// Designed to be secure regardless of Apache or PHP configuration.
	public function validateChannelLogDir($baseLogDir, $server, $channel) {
				
		// Check base log dir is valid
		if (!is_dir($baseLogDir))
			throw new Exception("IRC log directory not valid. Please check config.ini");
		
		// Validate $server
		PathValidator::validateServerLogDir($baseLogDir, $server);

		// Check channel name is valid (i.e. points to a valid dir)
		$dirHandle = opendir($baseLogDir."/".escapeshellcmd($server));
		$channelIsValid = false;
		while(($item = readdir($dirHandle)) !== false) {
			if(substr($item, 0, 1) != ".") { // Don't include hidden directories (i.e. beginning with a ".")
				if (is_dir($baseLogDir."/".addslashes($server)."/".$item)) // Only match directories
					if (strcmp($channel,$item) == 0) {// If dir found in root of logdir
						$channelIsValid = true;
						break;
					}
			}
		}
		if ($channelIsValid !== true)
			throw new Exception("Channel name not valid (no log directory exists for this channel on the specified server).");
		
		return true;		
	}
	
}

?>