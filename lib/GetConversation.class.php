<?php

// Load config file
if (!array_key_exists('irc_log_search', $GLOBALS))
	$GLOBALS['irc_log_search'] = parse_ini_file("config.ini", true);

class GetConversation {

	public $conversation;
	
	public function __construct($server, $channel, $startTime, $endTime, $keywords) {

		$this->conversation = array();
		
		//die("$startTime - $endTime");
		
		$startTimeStamp = strtotime($startTime);
		$endTimeStamp = strtotime($endTime);
		$date = date('Y-m-d', strtotime($startTime) );
			
		// Use default (relative) directory of "logs" if no value explicitly specified
		$logDir = $GLOBALS['irc_log_search']['options']['irc_log_dir'];
		if ($logDir == "")
			$logDir = dirname(__FILE__)."/../logs";
		
		$logDir .= "/".$server."/".$channel;
		if (!is_dir($logDir))
			throw new Exception("IRC log directory not valid.");

		$pathToFile = "";
		
		$dirHandle = opendir($logDir);
		$i = 0;
		while(($filename = readdir($dirHandle)) !== false) {
			if(substr($filename, 0, 1) != ".") { // Don't include hidden directories (i.e. beginning with a ".")
				if (is_file($logDir."/".$filename)) { // Only open files		
				
					// Get the day from the filename (this is why filenames must have the date in them, in the e.g. "mylog_YYYY-MM-DD.log" or "mylogYYYYMMDD.txt", etc..
					$dateFromFilename = preg_replace('/^(.*)(\d{4})(.*?)(\d{2})(.*?)(\d{2}?)(.*?)$/', "$2-$4-$6", $filename);					
					$dateRangeStart = strtotime($dateFromFilename." 00:00:00");
					$dateRangeEnd = strtotime($dateFromFilename." 23:59:59");
					
					if ($startTimeStamp >= $dateRangeStart && $startTimeStamp <= $dateRangeEnd) {
						$pathToFile = $logDir."/".$filename;
						break;
					}
				}
			}
			$i++;
		}
		closedir($dirHandle);		

		
		// TODO: Make these config options
		$startTimeStamp = $startTimeStamp - (60 * 5); // Show leading 5 minutes
		$endTimeStamp = $endTimeStamp + (60 * 60); // Show trailing 60 minutes	

		// Min / Max lines (so that even if a channel is really quiet or really busy, responses are useful)
		$minLines = 25; // Good when people ask questions during the night that are not answered for hours
		$maxLines = 250; // TODO: Create UI option so users can load more of a convo if they really want

		$lineCounter = 0;
		$fileHandle = fopen($pathToFile, "r") or die("Unable to open IRC log file for reading.");
		while(!feof($fileHandle)) {
			$line = fgets($fileHandle);

			//echo(htmlentities($line)."<br>");
			
			// Get timestamp (based on filename + time on line where match was found)
			@list($time, $username, $msg) = explode(' ', $line, 3);
			$username = preg_replace("/[^A-z0-9:_()\\|-]/", "", $username);		
			$time = preg_replace("/[^0-9:]/", "", $time);
			$timestamp = strtotime($date." ".$time);	
	
			if ($time && $username) {			
				$msg = preg_replace("/\n$/", "", $msg);			
				$msg = htmlentities($msg);				
				//$msg = $this->highliteUrls($msg);	
				$msg = $this->highliteKeywords($msg, $keywords);									
				
				if ($timestamp >= $startTimeStamp) {
					array_push($this->conversation, array('time' => $time, 'user' => $username, 'msg' => $msg));
					$lineCounter++;
				}

			} else {
				@list($time, $msg) = explode(' ', $line, 2);
				$time = preg_replace("/[^0-9:]/", "", $time);
				$timestamp = strtotime($date." ".$time);	

				$msg = preg_replace("/\n$/", "", $msg);			
				$msg = htmlentities($msg);
				//$msg = $this->highliteUrls($msg);				
				$msg = $this->highliteKeywords($msg, $keywords);				
				
				if ($timestamp >= $startTimeStamp) {
					array_push($this->conversation, array('time' => $time, 'msg' => $msg));									
					$lineCounter++;					
				}
			}
			
			// Only attempt to exit if we have got at least number of lines in $minLines 
			if ($lineCounter >= $minLines)
				if ($timestamp > $endTimeStamp)
					break;
			
			// Exit early if we have hit $maxLines
			if ($lineCounter >= $maxLines)		
				break;
			
		}
		fclose($fileHandle);	
		
		return $this->conversation;
	}
	
	private function highliteUrls($line) {
		// FIXME: 1) is borked regex 2) Conflicts with highliteKeywords() :-( Not sure how to resolve the latter problem.
		$pattern = "@\b(https?://)?(([0-9a-zA-Z_!~*'().&=+$%-]+:)?[0-9a-zA-Z_!~*'().&=+$%-]+\@)?(([0-9]{1,3}\.){3}[0-9]{1,3}|([0-9a-zA-Z_!~*'()-]+\.)*([0-9a-zA-Z][0-9a-zA-Z-]{0,61})?[0-9a-zA-Z]\.[a-zA-Z]{2,6})(:[0-9]{1,4})?((/[0-9a-zA-Z_!~*'().;?:\@&=+$,%#-]+)*/?)@";
		$line = preg_replace($pattern, '<a href="\0" target="_blank">\0</a>', $line);	
		return $line;
	}
	
	private function highliteKeywords($line, $keywords) {
	
		// Allow mo more than 10 keywords (to avoid easily overloading server)
		$keywords = explode(" ", $keywords, 10);
		
		// Remove any duplicates (for efficency)
		$keywords = array_unique($keywords);
									
		foreach ($keywords as $keyword) {
			$keyword_escaped = htmlentities(preg_quote($keyword));
			$newLine = @preg_replace("/$keyword_escaped/i", "<span class=\"keyword\">".htmlentities("$0")."</span>", $line);
			
			if ($newLine)
				$line = $newLine;
		}
		
		return $line;
	}
	
}



?>