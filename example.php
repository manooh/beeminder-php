<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// {{{ Enable error reporting, if necessary
error_reporting(E_ALL);
ini_set("display_errors", 1);
// }}}


// {{{ Start session. Important!
if (session_status() == PHP_SESSION_NONE) {
	session_start();
}
// }}}

// Include library
require 'beeminderapi.php';

// {{{ Session handling
{
	// Create Beeminder session and connector
	$session = new BeeminderSession();
	$conn    = new BeeminderConnector($session);

	// if session is established (i.e. token set), you can go on and
	// set/retrieve data
	if (!$session->isEstablished()) {
		// Uncomment & configure one of the following:
		
		// {{{ Option 1: using personal authentication token
		//$session->setAuthToken("abc123token"); // personal authentication token
		// }}}
		
		// {{{ Option 2: Client OAuth
		$session->initOAuth('example.php', 'abc123oauth'); // client OAuth
		// }}}
	}
}
// }}}
?>
<!doctype html>

<html lang="en">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />

	<title>Beeminder PHP lib example usage</title>
	<meta name="description" content="How to use the beeminder API" />
	<meta name="keywords"    content="Beeminder, PHP, connector"    />
	<meta name="author"      content="Manuela Hutter"               />

  <!--[if lt IE 9]>
  <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
  <![endif]-->
</head>

<body>
	<h1>Beeminder API In Action</h1>
	<div>
<?php

if ($session->isEstablished()) { // don't write 'else', session might just have been established
	echo "<h2>2) Session established</h2>";
	
	echo "<h3>List user info for 'myusername':</h3>";
	$out = $conn->getUser("myusername");
	echo "<div>Data Dump:<br>";
	var_dump($out);
	echo "</div>";

	echo "<h3>List goal info for 'mygoal':</h3>";
	$out = $conn->getGoal("myusername", "mygoal");
	echo "<div>Data Dump:<br>";
	var_dump($out);
	echo "</div>";

	echo "<h3>List datapoints for 'mygoal':</h3>";
	$out = $conn->getDatapoints("myusername", "mygoal");
	echo "<div>Data Dump:<br>";
	var_dump($out);
	echo "</div>";
/*	
	echo "<h3>Create new datapoint in 'mygoal':</h3>";
	$out = $conn->createDatapoint("myusername", "mygoal", time(), "3", "Datapoint added via PHP");
	echo "<div>Data Dump:<br>";
	var_dump($out);
	echo "</div>";
*/
}
?>
	</div>
</body>
</html>
