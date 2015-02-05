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
	
	$this_url = $base_url . "/timeline-clone.php";
	
 require_once('timeline-lib.php');

        $tdb = new DB($db_location);




	#if ($secret != $local_secret) {
#		header("HTTP/1.0 401 Unauthorized");
#		exit();
#	}
		

	#perform actions etc.

		
	if ($crudAction == 'CREATETCLONE') {
		$newCloneName = $_GET["NewCloneName"];
		$newCloneTwilioAcountSID = $_GET["NewCloneTwilioAcountSID"];
		$newCloneTwilioAuthToken = $_GET["NewCloneTwilioAuthToken"];
		$newCloneUserName = $_GET["NewCloneUserName"];
		$newClonePassword = $_GET["NewClonePassword"];
		$newCloneMP3URL = $_GET["NewCloneMP3URL"];

		echo("<!--about to call create clone--!>");

		$tdb->createClone($newCloneName, $newCloneTwilioAcountSID, $newCloneTwilioAuthToken,$newCloneUserName, $newClonePassword,$newCloneMP3URL);


	}
	if ($crudAction == 'DELETETNUMBER') {


		echo "<!-- delete number-->";
		$numberID = intval($_GET["TNumberID"]);

		$tdb->deleteTwilioNumber($numberID);

	}
	$updateCloneID = 0;
	if ($crudAction == 'EDITCLONE') {

		#just marking which number to edit

		$updateCloneID = intval($_GET["CLONEID"]);

	} 

	if ($crudAction == "UPDATECLONE") {

		$updateCloneID = $_GET["UpdateCloneID"];
		$updateCloneName = $_GET["UpdateCloneName"];
                $updateCloneTwilioAcountSID = $_GET["UpdateCloneTwilioAcountSID"];
                $updateCloneTwilioAuthToken = $_GET["UpdateCloneTwilioAuthToken"];
                $updateCloneUserName = $_GET["UpdateCloneUserName"];
                $updateClonePassword = $_GET["UpdateClonePassword"];
                $updateCloneMP3URL = $_GET["UpdateCloneMP3URL"];


		$tdb->updateCloneAllFields($updateCloneID, $updateCloneName, $updateCloneTwilioAcountSID, $updateCloneTwilioAuthToken, $updateCloneUserName, $updateClonePassword,$updateCloneMP3URL);
	

		$updateCloneID = 0;
	}



	#render

	 #top menu bar
#get last heartbeat from db


        echo($tdb->renderMenuBar($base_url, $instance_name));
        echo("<br><br>");


	echo("<div class='tableTitle'>Clone Management</div><br><div class='tableDescription' width=250px>Here we can manage our clones. note - one twilio account per clone!</div><br>");

	$clones = $tdb->getAllClones();


	echo("<form action='" . $this_page . "' method='get'>");

        echo "<input type='hidden' name='secret' value='" . $local_secret . "'/>";

	echo("<table>");
	echo("<tr><th>CloneID</th><th>Clone Name</th><th>Twilio Account</th><th>Twilio Auth Token</th><th>UserName</th><th>Password</th><th>Mp3 URL</th><th></th></tr>");
	
	$rownum=0;
        foreach($clones as $clone)
        {
		$rowstyle = (++$rownum % 2)==0?"d0":"d1";
		
		echo "<tr class='" .$rowstyle . "'>";

		if ($updateCloneID > 0) {
			if ($clone->CloneID  == $updateCloneID) {
				#show edit box
				echo "<input type='hidden' name='UpdateCloneID' value='" . $updateCloneID . "'/>";
				echo "<td>$clone->CloneID</td>";
				echo "<td><input type='text' name='UpdateCloneName' value='$clone->CloneName'/></td>";
				echo "<td><input type='text' name='UpdateCloneTwilioAcountSID' value='$clone->TwilioAcountSID'/></td>";
				echo "<td><input type='text' name='UpdateCloneTwilioAuthToken' value='$clone->TwilioAuthToken'/></td>";

				echo "<td><input type='text' name='UpdateCloneUserName' value='$clone->UserName'/></td>";
				echo "<td><input type='text' name='UpdateClonePassword' value='$clone->Password'/></td>";
				echo "<td><input type='text' name='UpdateCloneMP3URL' value='$clone->MP3URL'/></td>";

				echo "<td><input type='submit' name='Update' value='ok' />";

				echo "<input type='hidden' name='CRUD' value='UPDATECLONE'/>";

				

			} else {
				#show row without crud
				echo "<td>$clone->CloneID</td><td>$clone->CloneName</td><td>$clone->TwilioAcountSID</td><td>$clone->TwilioAuthToken</td><td>$clone->UserName</td><td>$clone->Password</td><td>$clone->MP3URL</td><td>-</td>";
	
			}
		} else {
			#show row with crud
		echo "<td>$clone->CloneID</td><td>$clone->CloneName</td><td>$clone->TwilioAcountSID</td><td>$clone->TwilioAuthToken</td><td>$clone->UserName</td><td>$clone->Password</td><td>$clone->MP3URL</td><td><a href='$this_url?secret=$secret_local&CRUD=EDITCLONE&CLONEID=$clone->CloneID'>edit</a>|<a href='$this_url?secret=$secret_local&CRUD=DELETECLONE&CLONEID=$clone->CloneID'>delete</a></td>";
		}
                echo "</tr>";
        }
	if ($updateCloneID == 0) {
		echo "<input type='hidden' name='CRUD' value='CREATETCLONE'/>";
		echo "<tr class='" .$rowstyle . "'>";
		echo "<td></td>";
		echo "<td><input type='text' name='NewCloneName' value='Clone Name'/></td>";
		echo "<td><input type='text' name='NewCloneTwilioAcountSID' value='put the SID in here'/></td>";
		echo "<td><input type='text' name='NewCloneTwilioAuthToken' value='put the auth token in here'/></td>";

		echo "<td><input type='text' name='NewCloneUserName' value='username'/></td>";
		echo "<td><input type='text' name='NewClonePassword' value='password'/></td>";
		echo "<td><input type='text' name='NewCloneMP3URL' value='mp3-url'/></td>";

		echo "<td><input type='submit' name='Add' value='Add' />";
		echo "</tr>";
	}
	echo "</table>";

	echo "</form>"; #end of number mgmt form

	echo("</div>"); #end of the number mgmt div

?>

</body>
</html>
