<html>
<head>
<link rel='stylesheet' type='text/css' href='default.css' />
<title>SIM test page</title>
</head>
<body>
<?php
$machinename =  gethostname();
        if (preg_match("/local/i",$machinename)) {
                $configfile = "/var/tmp/config.local";
        } else {
                $configfile = "/var/cache/timeline/config.local";
        }

         $ini_array = parse_ini_file($configfile);
?>
<?php
$username = $ini_array['userID'];
$password = $ini_array['password'];


if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Text to send if user hits Cancel button';
    exit;
} else {
	$login=0;
	if ($_SERVER['PHP_AUTH_USER'] == $username) {
		if ($_SERVER['PHP_AUTH_PW'] == $password) {
			#all is good
			$login=1;
		}
	}
	if ($login == 0) {
		header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Text to send if user hits Cancel button';
    exit;
	}
}
?>
<?php 
    header("content-type: text/html");

	$secret = $_GET["secret"];
	$crudAction = $_GET['CRUD'];
	
	$local_secret = $ini_array['sharedSecret'];
	$db_location = $ini_array['databasepath'];
	$base_url = $ini_array['phpServer'];
	$instance_name = $ini_array['instanceName'];
	
	$this_url = $base_url . "/timeline-groups.php";
	

		echo "<!-- we iare in the PHP vode -->";
	#if ($secret != $local_secret) {
#		header("HTTP/1.0 401 Unauthorized");
#		exit();
#	}
		
	#init database

	require_once('timeline-lib.php');
		echo "<!-- igot lib -->";
	$tdb = new DB($db_location);

		echo "<!-- we have a DB connection -->";


	#perform actions etc.

	$objLookupNumber = new PhoneNumber;
	$lookedUpGuid = "";
	$messageArray = array();
	$desc = "search";
	if (array_key_exists("GETNUMBERBYDESC", $_GET)) {
		echo "<!-- we have a test api call to get a number by description -->";
		$desc = $_GET["Description"];


		$objLookupNumber=$tdb->getPhoneNumberByDescription($desc);


		echo "<!-- we have got number $objLookupNumber->NumberID -->";
		$lookedUpGuid = $tdb->retrieveSIMGuid($desc);
		echo "<!-- we have got GUID of $lookedUpGuid -->";
		echo "<!-- we have got a number of  of $objLookupNumber->NumberID -->";


		$messageArray = $tdb->getSIMMessages($objLookupNumber->NumberID, $lookedUpGuid);
		
		echo "<!-- we have got messages-->";


	}

	if (array_key_exists("REGISTERNUMBER", $_GET)) {
		echo "<!-- we have a test api call to register number -->";
		$desc = $_GET["Description"];


		$guid = $tdb->registerSIMDevice($desc);


		echo "<!-- we have got guid $guid -->";


	}

	if (array_key_exists("SENDMESSAGE", $_GET)) {
                echo "<!-- we have a test api call to send a message -->";
                $numberID = $_GET["NumberID"];
                $GUID = $_GET["GUID"];
                $text = $_GET["MessageText"];


                $tdb->sendSIMMessage($numberID, $text);


                echo "<!-- we have got guid $guid -->";


        }


	#render

	 #top menu bar
#get last heartbeat from db


	$heartBeat = $tdb->getHeartBeat();


	if ($heartBeat->LastHeartBeatAgo < 2) {
		$heartBeatText = "TimeLine Active and OK - $heartBeat->LastHeartBeat";
	} else {
		$heartBeatText = "TimeLine Appears Down - $heartBeat->LastHeartBeat";
	}

        #render page

        #top menu bar
        echo("<div class='menuBar'><a href=$base_url/timeline-monitor.php>Monitor and Manager Threads</a>&nbsp;|&nbsp;<a href=$base_url/timeline-groups.php>Manage Numbers and Groups</a>&nbsp;|&nbsp;$instance_name&nbsp;|&nbsp;$heartBeatText</div>");

        echo("<br><br>");


	echo("<div class='tableTitle'>Lookup Number</div><br><div class='tableDescription' width=250px>lookup a number.</div><br>");


	echo("<form action='" . $this_page . "' method='get'>");

        echo "<input type='hidden' name='secret' value='" . $local_secret . "'/>";
        echo "<input type='hidden' name='GETNUMBERBYDESC' value='yes'/>";
        echo "<input type='text' name='Description' value='$desc'/>";
        echo "<input type='submit' name='Look Up' value='yes'/>";

	echo("<table>");
	echo("<tr><th>NumberID</th><th>Number</th><th>Description</th><th></th></tr>");
	
		echo "<tr class='" .$rowstyle . "'>";

				#show row without crud
				echo "<td>$objLookupNumber->NumberID</td><td>$objLookupNumber->Number</td><td>$objLookupNumber->NumberDescription</td><td>-</td>";
	
                echo "</tr>";
	echo "</table>";
	echo "Representing GUID $lookedUpGuid";

	echo "</form>"; #end of number mgmt form

	echo("</div>"); #end of the number mgmt div

 echo("<div class='tableTitle'>Register a Codename</div><br><div class='tableDescription' width=250px>lookup a number.</div><br>");


        echo("<form action='" . $this_page . "' method='get'>");

        echo "<input type='hidden' name='secret' value='" . $local_secret . "'/>";
        echo "<input type='hidden' name='REGISTERNUMBER' value='yes'/>";
        echo "<input type='text' name='Description' value='$desc'/>";
        echo "<input type='submit' name='Reg' value='yes'/>";

        echo "Representing GUID $guid";

        echo "</form>"; #end of reg GUID form

        echo("</div>"); #end of the reg GUID 


	echo("<div class='tableTitle'>Send Message</div><br><div class='tableDescription' width=250px>send meesage to SIM.</div><br>");


        echo("<form action='" . $this_page . "' method='get'>");

        echo "<input type='hidden' name='secret' value='" . $local_secret . "'/>";
        echo "<input type='hidden' name='SENDMESSAGE' value='yes'/>";
        echo "<input type='text' name='MessageText' value='message text here'/>";
        echo "<input type='text' name='GUID' value='$lookedUpGuid'/>";
        echo "<input type='text' name='NumberID' value='0'/>";
        echo "<input type='submit' name='Look Up' value='send'/>";


        echo "</form>"; #end of send message form

        echo("</div>"); #end of the send message div

 echo("<div class='tableTitle'>Recieved messages</div><br><div class='tableDescription' width=250px>messages from the looked up number.</div><br>");

	$i=0;
	echo("<table>");
        echo("<tr><th>SIMID</th><th>Text</th></tr>");
	foreach($messageArray as $msg)
        {
                $rowstyle = ($i++ % 2)==0?"d0":"d1";

                echo "<tr class='" .$rowstyle . "'>";

			echo "<td>$msg->SIMID</td><td>$msg->SIMTxt</td>";
		echo "</tr>";
	}


        echo "</table>"; #end of messages form

        echo("</div>"); #end of themessages div 


?>

</body>
</html>
