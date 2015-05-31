<html>
<head>
<link rel='stylesheet' type='text/css' href='default.css' />
<title>call monitoring page</title>
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
/*$username = $ini_array['userID'];
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

	#if ($secret != $local_secret) {
#		header("HTTP/1.0 401 Unauthorized");
#		exit();
#	}
		
	#init database
	$db = new PDO('sqlite:'.$db_location);

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
                 $sql = "DELETE FROM GroupNumber where GroupNumberID = ?";

                $st = $db->prepare($sql);
                $st->execute(array($numberID));



        }

		
	if ($crudAction == 'CREATENUMBER') {
		$newNumber = $_GET["NewNumber"];
		$newNumberDescription = $_GET["NewNumberDescription"];


		$newNumber = preg_replace('/\s+/', '', $newNumber);

		$sql = "INSERT INTO Number (Number, NumberDescription,CloneID) values (?,?,?)";

		$st = $db->prepare($sql);
		$st->execute(array($newNumber,$newNumberDescription,$clone->CloneID));

	}
	if ($crudAction == 'DELETENUMBER') {


		echo "<!-- delete number-->";
		$numberID = intval($_GET["NumberID"]);
		 $sql = "DELETE FROM Number where NumberID = ? "; 
		$st = $db->prepare($sql);
		$st->execute(array($numberID));

		echo "<!-- delete number-- $sql ID = $numberID>";
		$sql = "DELETE FROM GroupNumber where GNNumberID = ?";
                $st = $db->prepare($sql);
                $st->execute(array($numberID));
	
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

		 $sql = "UPDATE Number set Number = ?, NumberDescription=? where NumberID = ?"; 

		$st = $db->prepare($sql);
		$st->execute(array($updateNumber, $updateNumberDescription, $updateNumberID));
	

		$updateNumberID = 0;
	}


	if ($crudAction == 'CREATEGROUP') {
                $newGroup = $_GET["NewGroupName"];

                $sql = "INSERT INTO Groups (GroupName,CloneID) values (?,?)";

                $st = $db->prepare($sql);
                $st->execute(array($newGroup,$clone->CloneID));

        }
        if ($crudAction == 'DELETEGROUP') {


                $groupID = intval($_GET["GroupID"]);
                 $sql = "DELETE FROM Groups where GroupID = ? and GroupID > 0";
                $st = $db->prepare($sql);
                $st->execute(array($groupID));

		$sql = "DELETE FROM GroupNumber where GNGroupID = ?";
                $st = $db->prepare($sql);
                $st->execute(array($groupID));



        }

	if ($crudAction == 'CLEARGROUP') {


                $groupID = intval($_GET["GroupID"]);

                $sql = "DELETE FROM GroupNumber where GNGroupID = ?";
                $st = $db->prepare($sql);
                $st->execute(array($groupID));



        }

        $updateGroupID = 0;
        if ($crudAction == 'EDITGROUP') {

                #just marking which number to edit

                $updateGroupID = intval($_GET["GroupID"]);

        }
        if ($crudAction == "UPDATEGROUP") {
                $updateGroupID = intval($_GET["UpdateGroupID"]);
                $updateGroupName = $_GET["UpdateGroupName"];

                 $sql = "UPDATE Groups set GroupName = ? where GroupID = ?";

                $st = $db->prepare($sql);
                $st->execute(array($updateGroupName, $updateGroupID));


                $updateGroupID = 0;
        }


	#render

        #top menu bar
	echo($tdb->renderMenuBar($base_url, $instance_name));

        echo("<br><br>");

	echo("<div id='outer' width=700>");
	echo("<div id='left' style='display: inline;float: left;'>");

	echo("<div class='tableTitle'>Number Management</div><br><div class='tableDescription' width=250px>Here we can manage all the phone numbers we know about.</div>working on clone $clone->CloneName<br>");

	$result = $db->query("select * from Number where NumberID > 0 and CloneID = $clone->CloneID order by NumberID desc");

	echo("<form action='" . $this_page . "' method='get'>");

        echo "<input type='hidden' name='secret' value='" . $local_secret . "'/>";

	echo("<table>");
	echo("<tr><th>Number</th><th>Description</th><th></th></tr>");
	
	$rowarray = $result->fetchall(PDO::FETCH_ASSOC);
        foreach($rowarray as $row)
        {
		$rowstyle = ($row[id] % 2)==0?"d0":"d1";
		$currentDisplayNumber = $row['NumberID'];
		
		echo "<tr class='" .$rowstyle . "'>";

		if ($updateNumberID > 0) {
			if ($currentDisplayNumber == $updateNumberID) {
				#show edit box
				echo "<input type='hidden' name='UpdateNumberID' value='" . $updateNumberID . "'/>";
				echo "<td><input type='text' size=20 name='UpdateNumber' value='$row[Number]'/></td>";
				echo "<td><input type='text' name='UpdateNumberDescription' value='$row[NumberDescription]'/></td>";
				echo "<td><input type='submit' name='Update' value='ok' />";

				echo "<input type='hidden' name='CRUD' value='UPDATENUMBER'/>";

				

			} else {
				#show row without crud
				echo "<td>$row[Number]</td><td>$row[NumberDescription]</td><td>-</td>";
	
			}
		} else {
			#show row with crud
			echo "<td>$row[Number]</td><td>$row[NumberDescription]</td><td><a href='$this_url?secret=$secret_local&CRUD=EDITNUMBER&NumberID=$row[NumberID]'>edit</a>|<a href='$this_url?secret=$secret_local&CRUD=DELETENUMBER&NumberID=$row[NumberID]'>delete</a></td>";
		}
                echo "</tr>";
        }
	if ($updateNumberID == 0) {
		echo "<input type='hidden' name='CRUD' value='CREATENUMBER'/>";
		echo "<tr class='" .$rowstyle . "'>";
		echo "<td><input type='text' size=20 name='NewNumber' value='+44 xxxx xxx xxx'/></td>";
		echo "<td><input type='text' name='NewNumberDescription' value='NumberDescription'/></td>";
		echo "<td><input type='submit' name='Add' value='Add' />";
		echo "</tr>";
	}
	echo "</table>";

	echo "</form>"; #end of number mgmt form

	echo("</div>"); #end of the number mgmt div


	echo("<div class='tableTitle'>Group Management</div><br><div class='tableDescription' width=250px>Here we can manage groups.</div><br>");

	$result = $db->query("select * from Groups where GroupID > 0 and CloneID = $clone->CloneID");




	 echo("<form action='" . $this_page . "' method='get'>");
	echo("<table>");
	echo("<tr><th>GroupName</th><th></th></tr>");
	
	$rowarray = $result->fetchall(PDO::FETCH_ASSOC);
        foreach($rowarray as $row)
        {
		$rowstyle = ($row[GroupID] % 2)==0?"d0":"d1";
		$currentDisplayGroup = $row['GroupID'];
		
		echo "<tr class='" .$rowstyle . "'>";

		if ($updateGroupID > 0) {
			if ($currentDisplayGroup == $updateGroupID) {
				#show edit box
				echo "<input type='hidden' name='UpdateGroupID' value='" . $updateGroupID . "'/>";
				echo "<td><input type='text' size=20 name='UpdateGroupName' value='$row[GroupName]'/></td>";
				echo "<td><input type='submit' name='Update' value='ok' />";

				echo "<input type='hidden' name='CRUD' value='UPDATEGROUP'/>";

				

			} else {
				#show row without crud
				echo "<td>$row[GroupName]</td><td>-</td>";
	
			}
		} else {
			#show row with crud
			echo "<td>$row[GroupName]</td><td><a href='$this_url?secret=$secret_local&CRUD=EDITGROUP&GroupID=$row[GroupID]'>edit</a>|<a href='$this_url?secret=$secret_local&CRUD=DELETEGROUP&GroupID=$row[GroupID]'>delete</a></td>";
		}
                echo "</tr>";
        }
	if ($updateGroupID == 0) {
		echo "<input type='hidden' name='CRUD' value='CREATEGROUP'/>";
		echo "<tr class='" .$rowstyle . "'>";
		echo "<td><input type='text' size=20 name='NewGroupName' value='your group name'/></td>";
		echo "<td><input type='submit' name='Add' value='Add' />";
		echo "</tr>";
	}
	echo "</table>";
	echo "</form>";


	echo("</div>"); #end of the group mgmt div
	
	echo("<div class='tableTitle'>Group Membership</div><br><div class='tableDescription' width=250px>Here we can manage all the phone numbers we know about.</div><br>");

        $groupresult = $db->query("select * from Groups where GroupID > 0 and cloneID = $clone->CloneID" );


        echo "<input type='hidden' name='secret' value='" . $local_secret . "'/>";


        $grouparray = $groupresult->fetchall(PDO::FETCH_ASSOC);
	 foreach($grouparray as $group)
        {
        echo("<form action='" . $this_page . "' method='get'>");
                $groupName = $group['GroupName'];
                $groupID = $group['GroupID'];

		echo("<div class='tableDescription' width=250px>" . $groupName . "</div><br>");

		#add number to group.
	
		echo "<td><select  style='width:100px;margin:5px 0 5px 0;' name='ADDNUMBERTOGROUP'>";

                $numberresult = $db->query("SELECT * from Number where NumberID not in (select GNNumberID from GroupNumber where GNGroupID = " . $groupID . ") and NumberID > 0 and CloneID = $clone->CloneID");
                $numberarray = $numberresult->fetchall(PDO::FETCH_ASSOC);
                foreach($numberarray as $numberrow) {

                        echo "<option value='$numberrow[NumberID]_$groupID'>$numberrow[NumberDescription] ($numberrow[Number])</option>";
                }

        echo "</select><input id='submit$groupID' type='submit' value='add'>&nbsp;&nbsp;<a href='$this_url?secret=$secret_local&CRUD=CLEARGROUP&GroupID=$groupID'>clear group</a>";

		$result = $db->query("select * from Number, GroupNumber where GroupNumber.GNNumberID = Number.NumberID and GNGroupID = " . $groupID );

		echo("<table>");
		echo("<tr><th>Number</th><th>Description</th><th></th></tr>");

		$rownum = 0;
		$rowarray = $result->fetchall(PDO::FETCH_ASSOC);
		foreach($rowarray as $row)
		{
			$rowstyle = ($rownum++ % 2)==0?"d0":"d1";
			$currentDisplayNumber = $row['NumberID'];

			echo "<tr class='" .$rowstyle . "'>";

			echo "<td>$row[Number]</td><td>$row[NumberDescription]</td><td><a href='$this_url?secret=$secret_local&CRUD=DELETENUMBERFROMGROUP&GroupNumberID=$row[GroupNumberID]'>remove</a></td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "</form>";


	} 
	echo("</div>");
	echo("<div id='right' style='display: inline;float: left;'>");

	echo("<div class='tableTitle'>STASH</div><br><div class='tableDescription' width=250px>Here we can see any stashed info.</div><br>");



	$sql = "SELECT NumberDescription, StashKey, StashValue from Stash join Number on Number.NumberID = Stash.NumberID where Number.CloneID = $clone->CloneID order by StashKey, NumberDescription" ;
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
