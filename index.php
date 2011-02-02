<?php
	// Version string in URLs is used to prevent unwanted caching of JS and CSS files following upgrades.
	// As a side effect, also causes browsers to more aggressively cache if it remains unchanged.
	$version = "0.17b";
?>
<!DOCTYPE html>
<html>
	<head>
		<title>FireBreath IRC Log Search</title>
		<script type="text/javascript" src="http://www.google.com/jsapi?autoload=%7B%22modules%22%3A%5B%7Bname%3A%22maps%22%2Cversion%3A3%2Cother_params%3A%22sensor%3Dfalse%22%7D%2C%7B%22name%22%3A%22jquery%22%2C%22version%22%3A%221.3.2%22%7D%2C%7B%22name%22%3A%22jqueryui%22%2C%22version%22%3A%221.7.2%22%7D%5D%7D"></script>		
		<script src="js/main.js?v=<?= $version ?>"></script>		
		<link rel="stylesheet" href="css/main.css?v=<?= $version ?>" />
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" /
		<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
	</head>
	<body>
	
		<!-- Header -->
		<div id="header">
			<h1>FireBreath IRC Log Search</h1>
			<div class="version">Version <?= $version ?></div>
		</div>
		
		<div id="ircLogSearchContainer">
		
			<!-- Search Form -->
			<div id="ircLogSearchFormContainer">
				<form id="ircLogSearchForm" name="ircLogSearchForm" method="get" onsubmit="ircLogSearch.search(); return false;">
					<label for="ircServer">Server:</label>
					<select id="ircServer" name="server" onchange="ircLogSearch.populateIrcChannelList();">
		
					</select>	
					<label for="ircChannel">Channel:</label>
					<select id="ircChannel" name="channel">
					</select>				
					<input type="text" id="keywords" name="keywords" value="<?= $searchKeywords ?>" />
					<button type="button" name="search" onclick="ircLogSearch.search(); return false;" value="search">Search Chat Logs</button>
				</form>
			</div>
			
			<!-- Search Results -->
			<div id="ircLogSearchResults">
				<div id="ircLogSearchResultsConversations"></div>
				<div id="ircLogSearchResultsLogView"></div>	
			</div>			
			
		</div>
		
		<!-- Footer -->	
		<div id="footer">
			<a href="http://validator.w3.org/check?uri=referer">W3C Validator</a>
		</div>
	</body>
</html>
