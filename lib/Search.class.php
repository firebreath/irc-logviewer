<?php

include_once(dirname(__FILE__).'/SearchResult.class.php');

// Load config file
if (!array_key_exists('irc_log_search', $GLOBALS))
	$GLOBALS['irc_log_search'] = parse_ini_file("config.ini", true);

class Search {


	public $searchResults = array();
	
	public $conversations;
	
	private $matches;
	private $maxConversationLeadTime;
	private $maxConversationTime;
	private $maxConversationIdleTime;
	private $maxUniqueConversationLength;
	
	public function __construct($server, $channel, $keywords) {
				
		$this->searchResults = array();
		
		// Following changes elsewhere in the code, these don't really work as intended. :-( ... but it's not so important now in practice. 		
		// Still needs to be reviewed as it's now nasty old cruft.
		$this->maxConversationLeadTime = 60 * 5; // Include X seconds prior to first keyword mention (suggest ~5 min)
		$this->maxConversationTime = 60 * 60 * 24; // Include up to X seconds of conversation after first mention (suggest ~60 min)
		$this->maxConversationIdleTime = 60 * 60 * 24; // Max number of seconds a conversation can 'idle' for before being regarded as 'over' - NB: MUST be >= LeadTime!
		$this->maxUniqueConversationLength = 60 * 60 * 24; // Allow X seconds between mentions of a keyword counts as a unique conversation (suggest ~30 min)
				
		// Use default (relative) directory of "logs" if no value explicitly specified
		$logDir = $GLOBALS['irc_log_search']['options']['irc_log_dir'];
		if ($logDir == "")
			$logDir = dirname(__FILE__)."/../logs";
		
		$logDir .= "/".$server."/".$channel;
		if (!is_dir($logDir))
			throw new Exception("IRC log directory not valid. $logDir");
				
		$this->conversations = array();
		$this->matches = array();
		$conversations = array();
		
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
		
		//die(print_r($this->searchResults,1));
			
		//asort($this->searchResults, SORT_NUMERIC);
		

		//echo count($this->matches);
		//array = "";

	/*
		$i = 0;
		$prevTimestamp = 0;
		$prevPathToLogFile = "";
		//for ($i=0; $i<count($this->matches); $i++) {
		foreach ($this->matches as $timestamp => $pathToLogFile) {
			if ($i===0) {
				//array_push($conversations, array($timestamp => $pathToLogFile));
				$conversations[$timestamp] = $pathToLogFile;
				$prevTimestamp = $timestamp;
				$prevPathToLogFile = $pathToLogFile;
				$i++;	
				continue;
			} else {			
			
				if (strcmp($prevPathToLogFile, $pathToLogFile) == 0) {	
					// Only attempt to consolidate conversations in the same logfile
					$differenceInSeconds = $timestamp - $prevTimestamp;
					if ($differenceInSeconds > $this->maxUniqueConversationLength)
						$conversations[$timestamp] = $pathToLogFile;				
				} else {
					// If the result is in a log file more recent than the last logfile
					// (i.e. the filename is different) then add it as a unique conversation
					$conversations[$timestamp] = $pathToLogFile;	
				}
				
				$prevTimestamp = $timestamp;		
				$prevPathToLogFile = $pathToLogFile;				
				$i++;						
			}
		}
	*/
		/*
		// Assumes requires log file name in format "channelname_YYYYMMDD.log"
		foreach ($conversations as $timestamp => $pathToLogFile) {


			$conversation = $this->getConversationSummary($pathToLogFile, $timestamp, $keywords);
			
			// FIXME: Don't know why this happens, SERIOUS BUG (all items in $conversation returned blank)
			if (!$conversation['startTime'] || !$conversation['endTime'])		
				continue;
					
			// Sort Keywords and Users by frequency mentioned
			arsort($conversation['keywords'], SORT_NUMERIC);
			arsort($conversation['users'], SORT_NUMERIC);	
			
			// Calculate duration
			$conversation['duration'] = $conversation['endTime'] - $conversation['startTime'];			
			$conversation['duration'] = intval($conversation['duration'] / 60);
			
			if ($conversation['duration'] >= 60) {
					$conversation['duration'] = intval(($conversation['duration']  / 60)) ." hours";
			} else {
				$conversation['duration'] .= " minutes";
			}

			$conversation['startTime'] = date('Y-m-d H:i:s',$conversation['startTime']);
			$conversation['endTime'] = date('Y-m-d H:i:s',$conversation['endTime']);
					
			// Assign a 'score' based on frequency of keywords matched
			$conversation['score'] = 0;
			foreach ($conversation['keywords'] as $keyword => $matches) {
				$conversation['score'] += $matches;
			}
			
			if (count($conversation['users']) > 0)
				array_push($this->conversations,$conversation);
		
		}		
		*/
	
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