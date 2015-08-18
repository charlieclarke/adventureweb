<html>
<head>
<link rel='stylesheet' type='text/css' href='default.css' />
<title>twilio management page</title>
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
/*
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
*/
?>
<?php 
    header("content-type: text/html");

	$secret = $_GET["secret"];
	$crudAction = $_GET['CRUD'];
	
	$local_secret = $ini_array['sharedSecret'];
	$db_location = $ini_array['databasepath'];
	$base_url = $ini_array['phpServer'];
	$instance_name = $ini_array['instanceName'];
	
	$this_url = $base_url . "/timeline-twilio.php";
	
 require_once('timeline-lib.php');

        $tdb = new DB($db_location);



#beginning of the login code
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Text to send if user hits Cancel button';
    exit;
} else {
        $login=0;

        $username = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];

        $clone = $tdb->getCloneByUser($username, $password);


        if ($clone->CloneID >= 0) {
                $login=1;
        }
        if ($login == 0) {
                header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Text to send if user hits Cancel button';
    exit;
        }

}
#end of the login code


	#if ($secret != $local_secret) {
#		header("HTTP/1.0 401 Unauthorized");
#		exit();
#	}
		

	#perform actions etc.

		
	if ($crudAction == 'CREATETNUMBER') {
		$newNumber = $_GET["NewTNumber"];
		$newNumberName = $_GET["NewTNumberName"];
		$isActive = $_GET["TNumberIsActive"];
		$prefixWL = $_GET["TNumberPrefixWL"];

		$twitterConsumerKey = $_GET["TNumberTwitterConsumerKey"];
		$twitterConsumerKeySecret = $_GET["TNumberTwitterConsumerKeySecret"];
		$twitterAccessToken = $_GET["TNumberTwitterAccessToken"];
		$twitterAccessTokenSecret = $_GET["TNumberTwitterAccessTokenSecret"];

		$tdb->createTwilioNumber($newNumber, $newNumberName, $isActive,$prefixWL,$clone->CloneID,$twitterConsumerKey,$twitterConsumerKeySecret,$twitterAccessToken,$twitterAccessTokenSecret);


	}
	if ($crudAction == 'DELETETNUMBER') {


		echo "<!-- delete number-->";
		$numberID = intval($_GET["TNumberID"]);

		$tdb->deleteTwilioNumber($numberID,$clone->CloneID);

	}
	$updateTNumberID = 0;
	if ($crudAction == 'EDITTNUMBER') {

		#just marking which number to edit

		$updateTNumberID = intval($_GET["TNumberID"]);

	} 

	if ($crudAction == "UPDATETNUMBER") {
		$updateTNumberID = intval($_GET["UpdateTNumberID"]);
		$updateNumber = $_GET["UpdateTNumber"];
                $updateNumberName = $_GET["UpdateTNumberName"];
                $isActive = $_GET["UpdateIsActive"];
		$prefixWL = $_GET["UpdatePrefixWL"];

		$twitterConsumerKey = $_GET["UpdateTwitterConsumerKey"];
                $twitterConsumerKeySecret = $_GET["UpdateTwitterConsumerKeySecret"];
                $twitterAccessToken = $_GET["UpdateTwitterAccessToken"];
                $twitterAccessTokenSecret = $_GET["UpdateTwitterAccessTokenSecret"];

		$updateNumber = preg_replace('/\s+/', '', $updateNumber);

		$tdb->updateTwilioNumber($updateTNumberID, $updateNumber, $updateNumberName, $isActive,$prefixWL,$twitterConsumerKey,$twitterConsumerKeySecret,$twitterAccessToken,$twitterAccessTokenSecret);
	

		$updateTNumberID = 0;
	}



	#render

	 #top menu bar
#get last heartbeat from db


        echo($tdb->renderMenuBar($base_url, $instance_name));
        echo("<br><br>");


	echo("<div class='tableTitle'>Twilio Management</div><br><div class='tableDescription' width=250px>Here we can manage out twilio numbers.</div><br>");

	$twilioNumbers = $tdb->getAllTwilioNumbersByCloneID($clone->CloneID);


	echo("<form action='" . $this_page . "' method='get'>");

        echo "<input type='hidden' name='secret' value='" . $local_secret . "'/>";

	echo("<table>");
	echo("<tr><th>Number</th><th>Description</th><th>Active</th><th>Prefix</th><th>TwitterAccessToken</th><th width=10%>TwitterAccessTokenSecret</th><th>TwitterConsumerKey</th><th>TwitterConsumerKeySecret</th></tr>");
	
	$rownum=0;
        foreach($twilioNumbers as $tnumber)
        {
		$rowstyle = (++$rownum % 2)==0?"d0":"d1";
		
		echo "<tr class='" .$rowstyle . "'>";

		if ($updateTNumberID > 0) {
			if ($tnumber->TwilioNumberID  == $updateTNumberID) {
				#show edit box
				echo "<input type='hidden' name='UpdateTNumberID' value='" . $updateTNumberID . "'/>";
				echo "<td><input type='text' size=20 name='UpdateTNumber' value='$tnumber->TwilioNumber'/></td>";
				echo "<td><input type='text' name='UpdateTNumberName' value='$tnumber->TwilioNumberName'/></td>";
				echo "<td><input type='text' size=1 name='UpdateIsActive' value='$tnumber->IsActive'/></td>";
				echo "<td><input type='text' name='UpdatePrefixWL' value='$tnumber->PrefixWL'/></td>";
				echo "<td><input type='text' name='UpdateTwitterAccessToken' value='$tnumber->TwitterAccessToken'/></td>";
				echo "<td width=10%><input type='text' name='UpdateTwitterAccessTokenSecret' value='$tnumber->TwitterAccessTokenSecret'/></td>";
				echo "<td><input type='text' name='UpdateTwitterConsumerKey' value='$tnumber->TwitterConsumerKey'/></td>";
				echo "<td><input type='text' name='UpdateTwitterConsumerKeySecret' value='$tnumber->TwitterConsumerKeySecret'/></td>";

				echo "<td><input type='submit' name='Update' value='ok' />";

				echo "<input type='hidden' name='CRUD' value='UPDATETNUMBER'/>";

				

			} else {
				#show row without crud
				echo "<td>$tnumber->TwilioNumber</td><td>$tnumber->TwilioNumberName]</td><td>$tnumber->IsActive</td><td>$tnumber->PrefixWL</td>";
	
				echo "<td>$tnumber->TwitterAccessToken</td>";
				echo "<td>$tnumber->TwitterAccessTokenSecret</td>";
				echo "<td>$tnumber->TwitterConsumerKey</td>";
				echo "<td>$tnumber->TwitterConsumerKeySecret</td>";
				echo "<td>-</td>";
	
			}
		} else {
			#show row with crud
		echo "<td>$tnumber->TwilioNumber</td><td>$tnumber->TwilioNumberName</td><td>$tnumber->IsActive</td><td>$tnumber->PrefixWL</td>";

		echo "<td>$tnumber->TwitterAccessToken</td>";
		echo "<td>$tnumber->TwitterAccessTokenSecret</td>";
		echo "<td>$tnumber->TwitterConsumerKey</td>";
		echo "<td>$tnumber->TwitterConsumerKeySecret</td>";
		echo "<td>-</td><td><a href='$this_url?secret=$secret_local&CRUD=EDITTNUMBER&TNumberID=$tnumber->TwilioNumberID'>edit</a>|<a href='$this_url?secret=$secret_local&CRUD=DELETETNUMBER&TNumberID=$tnumber->TwilioNumberID'>delete</a></td>";
		}
                echo "</tr>";
        }
	if ($updateTNumberID == 0) {
		echo "<input type='hidden' name='CRUD' value='CREATETNUMBER'/>";
		echo "<tr class='" .$rowstyle . "'>";
		echo "<td><input type='text' size=20 name='NewTNumber' value='+44 xxxx xxx xxx'/></td>";
		echo "<td><input type='text' name='NewTNumberName' value='NumberDescription'/></td>";
		echo "<td><input type='text' size = 1 name='TNumberIsActive' value='1'/></td>";
		echo "<td><input type='text' name='TNumberPrefixWL' value='+44'/></td>";
		echo "<td><input type='text' name='TNumberTwitterAccessToken' value=''/></td>";
		echo "<td><input type='text' name='TNumberTwitterAccessTokenSecret' value=''/></td>";
		echo "<td><input type='text' name='TNumberTwitterConsumerKey' value=''/></td>";
		echo "<td><input type='text' name='TNumberTwitterConsumerKeySecret' value=''/></td>";
		echo "<td><input type='submit' name='Add' value='Add' />";
		echo "</tr>";
	}
	echo "</table>";

	echo "</form>"; #end of number mgmt form

	echo("</div>"); #end of the number mgmt div

?>

</body>
</html>
