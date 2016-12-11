<html>
<head>
<link rel='stylesheet' type='text/css' href='default.css' />
<title>bulk user management</title>
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
	
	$this_url = $base_url . "/timeline-groups.php";


	require_once('timeline-lib.php');

        $tdb = new DB($db_location);
        $tdb->init();



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
		
	#init database
	$db = new PDO('sqlite:'.$db_location);


	#perform actions etc.

	if (array_key_exists("ADDNUMBERTOGROUP", $_GET)) {
		echo "<!-- we have an add number to group to do -->";
		$groupnumber = explode("_", $_GET["ADDNUMBERTOGROUP"]);
		if (count($groupnumber) == 2) {

			echo "<!-- we have a valid parameter -->";
			$sql = "INSERT INTO GroupNumber (GNNumberID, GNGroupID) values (?,?)";

			$st = $db->prepare($sql);
			$st->execute($groupnumber);

		} else {
			echo "invalud param " . $_GET["ADDNUMBERTOGROUP"] . "::" . $groupnumber[1];
		}

	}

	if ($crudAction == 'DELETENUMBERFROMGROUP') {


                $numberID = intval($_GET["GroupNumberID"]);
                 $sql = "DELETE FROM GroupNumber where GroupNumberID = ? and GroupNumberID in (select GroupNumberID from GroupNumber join Number on GNNumberID = NumberID where CloneID = ?)";

                $st = $db->prepare($sql);
                $st->execute(array($numberID,$clone->CloneID));



        }

		
	if ($crudAction == 'CREATEBULKNUMBER') {
		$newNumberDescriptions = $_GET["NewNumberDescription"];
		$GroupNumberID = $_GET["GroupNumberID"];


		$descArray = preg_split('#\s+#', $newNumberDescriptions, null, PREG_SPLIT_NO_EMPTY);


		$newNumber = '00';


		foreach($descArray as $desc) {

		$sql = "DELETE from GroupNumber where GNNumberID in (select  NumberID from Number where NumberDescription=? and CloneID=?)";
		$st = $db->prepare($sql);
		$st->execute(array($desc, $clone->CloneID));
		$sql = "DELETE from Number where NumberDescription=? and CloneID = ?";
		$st = $db->prepare($sql);
		$st->execute(array($desc, $clone->CloneID));
		$sql = "INSERT INTO Number (Number, NumberDescription, CloneID) values (?,?,?)";
		$st = $db->prepare($sql);
		$st->execute(array($newNumber,$desc,$clone->CloneID));
		$sql = "INSERT INTO GroupNumber (GNNumberID, GNGroupID ) select NumberID, ? from Number where NumberDescription=?";
		$st = $db->prepare($sql);
		$st->execute(array($GroupNumberID,$desc));
		}

	}
	if ($crudAction == 'BULKGROUP') {
                $GroupNumberID = $_GET["GroupNumberID"];




		 $numberIDs = $_GET['number_grp'];


                foreach($numberIDs as $num) {
			echo("adding $num");
		
			if (preg_match("/active_(\d+)/",$num,$matches) > 0) {
				$numID = $matches[1];

				$sql = "DELETE from GroupNumber where GNNumberID = ? and GNGroupID = ? and GroupNumberID in (select GroupNumberID from GroupNumber join Number on GNNumberID = NumberID where CloneID = ?)";
				$st = $db->prepare($sql);
				$st->execute(array($numID, $GroupNumberID,$clone->CloneID));
				$sql = "INSERT INTO GroupNumber (GNNumberID, GNGroupID ) values (?,?)";
				#this needs to be filtered. have been bad and havnt done it.
				$st = $db->prepare($sql);
				$st->execute(array($numID,$GroupNumberID));
				echo($sql);
			}
		}

        }

	if ($crudAction == 'DELETENUMBER') {


		echo "<!-- delete number-->";
		$numberID = intval($_GET["NumberID"]);
		 $sql = "DELETE FROM Number where NumberID = ? and NumberID > 0 and CloneID=?"; 
		$st = $db->prepare($sql);
		$st->execute(array($numberID,$clone->CloneID));

		echo "<!-- delete number-->";
		$sql = "DELETE FROM GroupNumber where GNNumberID = ? and GroupNumberID in (select GroupNumberID from GroupNumber join Number on GNNumberID = NumberID where CloneID = ?)";
                $st = $db->prepare($sql);
                $st->execute(array($numberID,$clone->CloneID));
	
		echo "<!-- delete number-->";


	}
	$updateNumberID = 0;
	if ($crudAction == 'EDITNUMBER') {

		#just marking which number to edit

		$updateNumberID = intval($_GET["NumberID"]);

	} 
	if ($crudAction == "UPDATENUMBER") {
		$updateNumberID = intval($_GET["UpdateNumberID"]);
		$updateNumber = $_GET["UpdateNumber"];
                $updateNumberDescription = $_GET["UpdateNumberDescription"];


		 $updateNumber = preg_replace('/\s+/', '', $updateNumber);

		 $sql = "UPDATE Number set Number = ?, NumberDescription=? where NumberID = ? and cloneID=?"; 

		$st = $db->prepare($sql);
		$st->execute(array($updateNumber, $updateNumberDescription, $updateNumberID,$clone->CloneID));
	

		$updateNumberID = 0;
	}


	if ($crudAction == 'CREATEGROUP') {
                $newGroup = $_GET["NewGroupName"];

                $sql = "INSERT INTO Groups (GroupName,CloneID) values (?,CloneID)";

                $st = $db->prepare($sql);
                $st->execute(array($newGroup, $clone->CloneID));

        }
        if ($crudAction == 'DELETEGROUP') {


                $groupID = intval($_GET["GroupID"]);
                 $sql = "DELETE FROM Groups where GroupID = ? and GroupID > 0 and CloneID = ?";
                $st = $db->prepare($sql);
                $st->execute(array($groupID,$clone->CloneID));

		$sql = "DELETE FROM GroupNumber where GNGroupID = ? and GroupNumberID in (select GroupNumberID from GroupNumber join Number on GNNumberID = NumberID where CloneID = ?)";
                $st = $db->prepare($sql);
                $st->execute(array($groupID,$clone->CloneID));



        }
        $updateGroupID = 0;
        if ($crudAction == 'EDITGROUP') {

                #just marking which number to edit

                $updateGroupID = intval($_GET["GroupID"]);

        }
        if ($crudAction == "UPDATEGROUP") {
                $updateGroupID = intval($_GET["UpdateGroupID"]);
                $updateGroupName = $_GET["UpdateGroupName"];

                 $sql = "UPDATE Groups set GroupName = ? where GroupID = ? and cloneID=?";

                $st = $db->prepare($sql);
                $st->execute(array($updateGroupName, $updateGroupID,$clone->CloneID));


                $updateGroupID = 0;
        }


	#render

	 #top menu bar
	echo("<!--about to do reder menu--!>");

	echo($tdb->renderMenuBar($base_url, $instance_name));
        echo("<br><br>");

	echo("<div id='outer' width=700>");
	echo("<div id='left' style='display: inline;float: left;'>");

	echo("<div class='tableTitle'>Number Management</div><br><div class='tableDescription' width=250px>Here we can manage  phone numbers in BULK.</div><br>");

	$result = $db->query('select * from Number where NumberID > 0 and CloneID = '. $clone->CloneID);

	echo("<form action='" . $this_page . "' method='get'>");

        echo "<input type='hidden' name='secret' value='" . $local_secret . "'/>";

	echo("<table>");
	echo("<tr><th>Number</th><th>Description</th><th><input type='checkbox' id='s' onclick=\"toggle(document.getElementById('s'));\"/></th></tr>");
	
?>

<script>
	
function toggle(source) {
  checkboxes = document.getElementsByName('number_grp[]');
  for(var i=0, n=checkboxes.length;i<n;i++) {
    checkboxes[i].checked = source.checked;
  }
}
</script>



<?php
	$rowarray = $result->fetchall(PDO::FETCH_ASSOC);
        foreach($rowarray as $row)
        {
		$rowstyle = ($row[id] % 2)==0?"d0":"d1";
		$currentDisplayNumber = $row['NumberID'];

		$checked = "";
		$checkboxcode = "<input type='checkbox' name='number_grp[]' value='active_$row[NumberID]' $checked/>";

		
		echo "<tr class='" .$rowstyle . "'>";

		#show row with crud
		echo "<td>$row[Number]</td><td>$row[NumberDescription]</td><td>$checkboxcode</td>";
		echo "</tr>\n";
        }
	echo "</table>";
	#draw a dropdown with all the groups.
	echo "<select  style='width:100px;margin:5px 0 5px 0;' name='GroupNumberID'>";

                $numberresult = $db->query("SELECT * from Groups where GroupID > 0 and CloneID=" . $clone->CloneID);
                $numberarray = $numberresult->fetchall(PDO::FETCH_ASSOC);
                foreach($numberarray as $numberrow) {

                        echo "<option value='$numberrow[GroupID]'>$numberrow[GroupName]</option>";
                }

        echo "</select>";
	echo "<input type='hidden' name='CRUD' value='BULKGROUP'/>";
	echo "<input type='submit' name='go' value='go'/>";
	

	echo "</form>"; #end of number mgmt form

	echo("</div>"); #end of the number mgmt div


	echo("<div class='tableTitle'>BULK UPLOAD</div><br><div class='tableDescription' width=250px>Here we can do a bulk uplaod of agent names</div><br>");

	echo("<div>");


	 echo("<form action='" . $this_page . "' method='get'>");
	
	echo "<input type='hidden' name='CRUD' value='CREATEBULKNUMBER'/>";
	echo("<textarea name='NewNumberDescription' id ='NewNumberDescription' rows='20' cols='30'>s</textarea>");
	echo "<input type='submit' name='go' value='go'/>";
	//draw the drop down of which group we want to add all these into...
	echo "<select  style='width:100px;margin:5px 0 5px 0;' name='GroupNumberID'>";

                $numberresult = $db->query("SELECT * from Groups where GroupID > 0 and CloneID = ". $clone->CloneID);
                $numberarray = $numberresult->fetchall(PDO::FETCH_ASSOC);
                foreach($numberarray as $numberrow) {

                        echo "<option value='$numberrow[GroupID]'>$numberrow[GroupName]</option>";
                }

        echo "</select>";


	echo "</form>";


	echo("</div>"); #end of the group mgmt div
	
	echo("<div id='right' style='display: inline;float: left;'>");

	echo("<div class='tableTitle'>STASH</div><br><div class='tableDescription' width=250px>Here we can see any stashed info.</div><br>");



	$sql = 'SELECT NumberDescription, StashKey, StashValue from Stash join Number on Number.NumberID = Stash.NumberID where CloneID = ' . $clone->CloneID . ' order by StashKey, NumberDescription' ;
	$result = $db->query($sql);

	echo("<table>");
	echo("<tr><th>Number</th><th>Key</th><th></th><th>Value</th></tr>");

	$rownum = 0;
	$rowarray = $result->fetchall(PDO::FETCH_ASSOC);
	foreach($rowarray as $row)
	{
		$rowstyle = ($rownum++ % 2)==0?"d0":"d1";
		$currentDisplayNumber = $row['NumberID'];

		echo "<tr class='" .$rowstyle . "'>";

		echo "<td>$row[NumberDescription]</td><td>$row[StashKey]</td><td>$row[StashValue]'</td>";
		echo "</tr>";
	}
	echo "</table>";




	echo("</div>");
	echo("</div>"); //the outermost div
?>

</body>
</html>
