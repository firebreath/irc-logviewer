<?php

include_once(dirname(__FILE__).'/SearchResult.class.php');

// Load config file
if (!array_key_exists('irc_log_search', $GLOBALS))
	$GLOBALS['irc_log_search'] = parse_ini_file("config.ini", true);

class Search {

	public $searchResults = array();
			
	public function __construct($server, $channel, $keywords) {
				
		$this->searchResults = array();	
				
		// Use default (relative) directory of "logs" if no value explicitly specified
		$logDir = $GLOBALS['irc_log_search']['options']['irc_log_dir'];
		if ($logDir == "")
			$logDir = dirname(__FILE__)."/../logs";
		
		$logDir .= "/".$server."/".$channel;
		if (!is_dir($logDir))
			throw new Exception("IRC log directory not valid. $logDir");
				
		$dirHandle = opendir($logDir);
		$i = 0;
		while(($filename = readdir($dirHandle)) !== false) {
			if(substr($filename, 0, 1) != ".") { // Don't include hidden directories (i.e. beginning with a ".")
				if (is_file($logDir."/".$filename)) { // Only open files	
					$searchResult = $this->searchInFile($logDir, $filename, $keywords);

					// Push search result into search results if any keywords match
					if (count($searchResult->keywords) > 0)
						array_push($this->searchResults, $searchResult);
				}
			}
			$i++;
		}
		closedir($dirHandle);		
	
		// Sort all conversations by score (those with the highest score first)
		// TODO: Optimise so don't need array_reverse()!
		function sortByScore($a, $b) {
			if ($a->keywordScore == $b->keywordScore)
				return 0;	
				
			return ($a->keywordScore < $b->keywordScore) ? -1 : 1;
		}	
		usort($this->searchResults, "sortByScore");				
		$this->searchResults = array_reverse($this->searchResults);

		return $this->searchResults;
	}
	
	
	private function searchInFile($dir, $filename, $keywords) {

		// Get date from path to file (requires log file name in format along the lines of "channelname_YYYYMMDD.log" or "#channelYYYY-MM-DD.txt")
		$date = preg_replace('/^(.*)(\d{4})(.*?)(\d{2})(.*?)(\d{2}?)(.*?)$/', "$2-$4-$6", $filename);		
	
		$searchResult = new SearchResult();
		$searchResult->filename = $filename;
		$searchResult->startTime = "";
		$searchResult->endTime = "";
								
		// Top the keyword limit at 50 
		$keywords = explode(" ", $keywords, 50);		
		$keywords = array_unique($keywords);
		
		$fileHandle = fopen($dir."/".$filename, "r") or die("Unable to open IRC log file for searching.");
		while(!feof($fileHandle)) {
			$line = fgets($fileHandle);									
					
			@list($time, $restOfLine) = explode(' ', $line, 2);
			$time = preg_replace("/[^0-9:]/", "", $time);	
			
			// Completely skip over malformed lines (e.g. not HH:MM or HH:MM:SS)			
			if (!$time)
				continue;
			if (strlen($time) < 5) 
				continue;
			
			// Create timestamp for comparisos					
			$timestamp = strtotime($date." ".$time);		

			// Look for matching keywords anywhere on the line (after the time)
			foreach ($keywords as $keyword) {

				$pos = @strpos(strtolower($restOfLine),strtolower($keyword));
				if ($pos !== false) {	
					// Record there was a match (adding to the keywords array if it's not already found)
					if (array_key_exists($keyword, $searchResult->keywords)) {
						$searchResult->keywords[$keyword]++;
					} else {
						$searchResult->keywords[$keyword] = 1;
					}	
	
					// If there is no recorded timestamp, the conversation starts here!				
					if ($searchResult->startTime == "")
						$searchResult->startTime = $timestamp;			

					// Always update last timestamp when we find a keyword
					$searchResult->endTime = $timestamp;
					
					// Increasing the keywordScore by one for every positive match 
					$searchResult->keywordScore++;
		
					// Check for users (not all lines will have usernames in them - e.g. some will be system messages)
					@list($username, $junk) = explode(' ', $restOfLine, 2);
					$username = preg_replace("/[^A-z0-9:_()\\|-]/", "", $username);	
					if ($username) {
						// Count mentions of user		
						if ($username != "") {
							if (array_key_exists($username, $searchResult->users)) {
								$searchResult->users[$username]++;
							} else {
								$searchResult->users[$username] = 1;
							}
						}			
					}
					
				}				
			}	

		}
		fclose($fileHandle);	
		
		if (count($searchResult->keywords) > 0) {
			// Duration (in seconds)
			$searchResult->duration = $searchResult->endTime - $searchResult->startTime;
			
			// Convert from UNIX timestamps to a human readable timestamp
			$searchResult->startTime = date('Y-m-d H:i:s', $searchResult->startTime);
			$searchResult->endTime = date('Y-m-d H:i:s', $searchResult->endTime);
			 		 
			// Sort keyword and user arrays by frequency of mentions
			arsort($searchResult->keywords, SORT_NUMERIC);
			arsort($searchResult->users, SORT_NUMERIC);		
		}
					
		return $searchResult;
	}

}



?>