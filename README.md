IRC Log Viewer
=============

This was an IRC log parser written to make it possible to search IRC log files
for the #firebreath channel on irc.freenode.net.

The goal was to make it easier to find pervious discussions based on keyword
matching (partial or complete, including searching for special characters).

It attempts to group discussions by date/time and rank results by relevance,
noting keyword frequency and who mentioned them.

It's currently a functional work in progress.

You can find a live example running at http://logs.firebreath.org/


Requirements
-------

### Server Requirements
- PHP 5
- No Apache or database configuration required.


### Client Requirements
- Internet Explorer 7+, Firefox 3.5+ or recent version of Safari or Chrome.
- JavaScript must be enabled.


If it doesn't work in your browser, do let me know. Feel free to send me a
screenshot. I'm aware there are currently usability issues on mobile browsers.


Configuration
-------

Configure lib/config.ini to point to your log file directory. You will need
to use a directory structure as described below.

TIP: If you don't want to change how you currently save your IRC logs, you can
use symlinks to create a structure with directories that point to wherever
you currently save your log files to.

Example (if you currently save your logs in ~/irclogs/firebreath/):

	mkdir -p "/var/irclogs/irc.freenode.net/#firebreath"
	ln -s ~/irclogs/firebreath/ "/var/irclogs/irc.freenode.net/#firebreath"

### Directory Structure

Directory structure should be in the form:

	{servername}/{channelname}/{logfiles}
	
Log files must have the date in the filename in YYYYDDMM or YYYY-DD-MM format.

Examples:

	/var/irclogs/irc.freenode.net/#firebreath/#firebreath_20100131.log
	/var/irclogs/irc.freenode.net/%23firebreath/2010-01-31.txt


### Log File Format

Actual log lines must be in the form "{time} {user} {message}" (or 
"{time} {message}" for system messages).

Examples:

	[08:01:04] <bob> Hello everyone!
	[11:23] <dave_1|home> Hey
	17:22 <sue> Hi 
	18:50:17 *** Quits: Alex (~alex@host) (Remote host closed the connection)


Scalability/Performance
-------

It's been designed to support a range of log file formats, and specifically to
need as little configuration as possible (hence not requiring a database).

It works by parsing raw log files and recording keyword hits, using strpos() 
for fast matching. In practice, this seems to be fast enough to allow parsing
through ~6 years/(30MB+) worth of logs in less than 10 seconds.

With a log directory structure of ./servername/channel/ adding additional 
servers / channels doesn't impact performance.


Contributing
-------

I'd love it if you'd like to help with bug reports, feature requests or code!
I suggest joining us on irc://irc.freenode.net/%23firebreath or email 
<iaincollins@firebreath.org>.
