<html>
<head>
<link rel='stylesheet' type='text/css' href='default.css' />
<title>call monitoring page</title>
</head>
<body>
<?php 
    header("content-type: text/html");

	$secret = $_GET["secret"];
	$triggerAction = $_GET['TRIGGER'];
	$globalAction = $_GET['GLOBAL'];
	$crudAction = $_GET['CRUD'];
	$threadID = intval($_GET['ThreadID']);

	


	#get config information
	$ini_array = parse_ini_file("config.local");
	
	$local_secret = $ini_array['sharedSecret'];
	$db_location = $ini_array['databasepath'];
	$base_url = $ini_array['phpServer'];
	
	$this_url = $base_url . "/timeline-monitor.php";
	

	#if ($secret != $local_secret) {
#		header("HTTP/1.0 401 Unauthorized");
#		exit();
#	}
		
	#init database
	$db = new PDO('sqlite:'.$db_location);


	#perform actions etc.
	if ($crudAction == 'CREATENUMBER') {
		$newNumber = $_GET["NewNumber"];
		$newNumberDescription = $_GET["NewNumberDescription"];

		$sql = "INSERT INTO Number (Number, NumberDescription) values (?,?)";

		$st = $db->prepare($sql);
		$st->execute(array($newNumber,$newNumberDescription));

	}
	if ($crudAction == 'DELETENUMBER') {


		$numberID = intval($_GET["NumberID"]);
		 $sql = "DELETE FROM Number where NumberID = ?"; 

		$st = $db->prepare($sql);
		$st->execute(array($numberID));
	


	}
	if ($crudAction == 'CREATETHREAD') {
		$newThreadID = intval($_GET["NewThreadID"]);
		$newThreadDescription = $_GET["NewThreadDesc"];
		$newActionID = intval($_GET["NewActionID"]);
		$newThreadNumber = $_GET["NewThreadDestNumber"];
		$newThreadFrequency = $_GET["NewFrequency"];
		$newThreadMp3 = $_GET["NewMp3Name"];
		$newChildThreadID = intval($_GET["NewChildThreadID"]);
		$newStartHour = intval($_GET["NewStartTimeHours"]);
		$newStopHour = intval($_GET["NewStopTimeHours"]);
		$newStartMinute = intval($_GET["NewStartTimeMinutes"]);
		$newStopMinute = intval($_GET["NewStopTimeMinutes"]);
		 $sql = "DELETE FROM Thread where id = ?"; 

		$st = $db->prepare($sql);
		$st->execute(array($newThreadID));

		$sql = "INSERT INTO Thread (id, ThreadDescription, ActionType, DestNumber, FrequencyMinutes, mp3Name, ChildThreadID,StartTimeHour, StopTimeHour,StartTimeMinute, StopTimeMinute) values (?,?,?,?,?,?,?,?,?,?,?)";

		$st = $db->prepare($sql);
		$st->execute(array($newThreadID,$newThreadDescription, $newActionID, $newThreadNumber, $newThreadFrequency, $newThreadMp3, $newChildThreadID,$newStartHour, $newStopHour, $newStartMinute, $newStopMinute));

	}
	if ($crudAction == 'DELETETHREAD') {

		#first - delete all current items in the timeline with this threadID
		#then - delerte the thread itself

		$threadID = intval($_GET["ThreadID"]);
		 $sql = "DELETE FROM TimeLine where ThreadID = ?"; 

		$st = $db->prepare($sql);
		$st->execute(array($threadID));
	
		 $sql = "DELETE FROM Thread where id = ?"; 

		$st = $db->prepare($sql);
		$st->execute(array($threadID));


	}
	if ($globalAction == 'KILL') {
		 $sql = "DELETE FROM TimeLine"; 

		echo("<!-- sql is " . $sql . "-->");
		$count = $db->exec($sql);
		echo("<!-- sql done " . $count . "rows -->");


	}
	if ($triggerAction == 'INSERT') {
		if ($threadID > 0) {
			#we have a valid trigger to insert

			$sql = "INSERT INTO TimeLine select NULL," . $threadID . ",datetime('now'),0,NULL,'triggerd from monitor page',NULL";

			echo("<!-- sql is " . $sql . "-->");
			$count = $db->exec($sql);
			echo("<!-- sql done " . $count . "rows -->");


		}
	}
			
	if ($triggerAction == 'REMOVE') {
		if ($threadID > 0) {
			#we have a valid trigger to insert

			$sql = "DELETE from TimeLine where ThreadID = " . $threadID ;

			echo("<!-- sql is " . $sql . "-->");
			$count = $db->exec($sql);
			echo("<!-- sql done " . $count . "rows -->");


		}
	}
			

	#render page


	$result = $db->query('SELECT * FROM Thread join Action where Thread.ActionType = Action.ActionTypeID');

	echo("<form action='" . $this_page . "' method='get'>");

	echo("<div class='tableTitle'>List of Threads</div><br><div class='tableDescription' width=250px>This is a list of all Threads available. You can trigger a task, which adds it to a timeline. You can remove a task, which deletes all instances if that task from the timeline. You can also (be careful) delete the task.</div><table>");
	echo("<tr><th>ID</th><th>Description</th><th>Type</th><th>Destination Number</th><th>MP3</th><th>Repeat Minutes</th><th>ChildThreadID</th><th>Time Range</th><th>Trigger</th></tr>");
	$rowarray = $result->fetchall(PDO::FETCH_ASSOC);
	$maxID = 0;	
echo "<br><br>";
	
	foreach($rowarray as $row)
	{
		$rowstyle = ($row[id] % 2)==0?"d0":"d1";
		
		echo "<tr class='" .$rowstyle . "'>";
		echo "<td>$row[id]</td><td>$row[ThreadDescription]</td><td>$row[ActionType] ($row[ActionName])</td><td> $row[DestNumber]</td><td>$row[mp3Name]</td><td>$row[MinutesBeforeText]$row[FrequencyMinutes]$row[MinutesAfterText]</td><td>$row[ChildThreadID]</td><td>$row[StartTimeHour]:$row[StartTimeMinute] -&gt; $row[StopTimeHour]:$row[StopTimeMinute]</td><td><a href='$this_url?secret=$secret_local&TRIGGER=INSERT&ThreadID=$row[id]'>insert</a>|<a href='$this_url?secret=$secret_local&TRIGGER=REMOVE&ThreadID=$row[id]'>remove</a>|<a href='$this_url?secret=$secret_local&CRUD=DELETETHREAD&ThreadID=$row[id]'>delete</a></td>";
		echo "</tr>";
		$maxID = $row[id];
	}
	$rowstyle = (($maxID+1) % 2)==0?"d0":"d1";
	echo "<tr class='" .$rowstyle . "'>";
	echo "<input type='hidden' name='CRUD' value='CREATETHREAD'/>";
	echo "<input type='hidden' name='secret' value='" . $local_secret . "'/>";
	echo "<td><input type='text' size=2 name='NewThreadID' value='" . ($maxID + 1) . "'/></td>";
	echo "<td><input type='text' name='NewThreadDesc' value='Thread Description'/></td>";
	echo "<td><select name='NewActionID'>";

		$result = $db->query("SELECT * from Action");
		$rowarray = $result->fetchall(PDO::FETCH_ASSOC);
		foreach($rowarray as $row) {

			echo "<option value='$row[ActionTypeID]'>$row[ActionTypeID] ($row[ActionName])</option>";
		}

	echo "</td>";
	#echo "<td><input type='text' name='NewThreadDestNumber' value='+44 xxxx xxxx xxxx '/></td>";
	echo "<td><select  style='width:100px;margin:5px 0 5px 0;' name='NewThreadDestNumber'>";

		$result = $db->query("SELECT * from Number");
		$rowarray = $result->fetchall(PDO::FETCH_ASSOC);
		foreach($rowarray as $row) {

			echo "<option value='$row[Number]'>$row[Number] ($row[NumberDescription])</option>";
		}

	echo "</select>";
	echo "</td>";
	echo "<td><input type='text' name='NewMp3Name' value='mp3name / message'/></td>";
	echo "<td><input type='text' name='NewFrequency' value='0'/></td>";
	echo "<td><select  style='width:100px;margin:5px 0 5px 0;' name='NewChildThreadID'>";

		echo "<option value='0'>0 (no child thread)</option>";
		$result = $db->query("SELECT * from Thread");
		$rowarray = $result->fetchall(PDO::FETCH_ASSOC);
		foreach($rowarray as $row) {

			echo "<option value='$row[id]'>$row[id] ($row[ThreadDescription])</option>";
		}

	echo "</select>";
	echo "</td>";
	echo "<td><input type='text' size=1  name='NewStartTimeHours' value='00'/>:";
	echo "<input type='text' size=1 name='NewStartTimeMinutes' value='00'/>-&gt;";
	echo "<input type='text' size=1 name='NewStopTimeHours' value='23'/>:";
	echo "<input type='text' size=1 name='NewStopTimeMinutes' value='59'/>";
	echo "</td>";
	echo "<td><input type='submit' value='Submit' /></td>";
	echo "</tr>";
	echo("</table>");
	echo("</form>");

	echo("<br><br>");

	echo("<div id='container'>");
	echo("<div id='timelineDiv' >");
	echo("<div class='tableTitle'>Current TimeLine</div><br><div class='tableDescription' width=250px>The TimeLine shows all Actions from all Threads..</div>");
	echo "<a href='$this_url?secret=$secret_local&GLOBAL=KILL'>Kill TimeLine</a>";
	echo "&nbsp;&nbsp;<a href='$this_url?secret=$secret_local'>Refresh Page</a>";

	$result = $db->query('select Thread.ThreadDescription,TimeLine.id, TimeLine.ThreadID, TimeLine.ActivityTime, TimeLine.Completed, TimeLine.CompletedTime, TimeLine.Description, TimeLine.Notes, Thread.ActionType, Thread.mp3Name, Thread.DestNumber, Thread.FrequencyMinutes from TimeLine, Thread where TimeLine.ThreadID = Thread.id order by TimeLine.ActivityTime desc');





        echo("<table>");
        echo("<tr><th>ID</th><th>Time</th><th>Thread Description</th><th>Number</th><th>MP3 / Text</th><th>Completed?</th><th>Comment</th></tr>");
        $rowarray = $result->fetchall(PDO::FETCH_ASSOC);
        
        foreach($rowarray as $row)
        {
		$rowstyle = ($row[id] % 2)==0?"d0":"d1";
		
		echo "<tr class='" .$rowstyle . "'>";
		$completed=($row[Completed]==1)?'Yes (' . $row[CompletedTime] . ")":'No';
                echo "<td>$row[id]</td><td>$row[ActivityTime]</td><td>$row[ThreadDescription]</td><td> $row[DestNumber]</td><td>$row[mp3Name]</td><td>$completed</td><td>$row[Description]</td>";
                echo "</tr>";
        }
        echo("</table>");
	echo("</div>"); #end of the timeline div
	echo("<div id='numberMgmt' >");
	
	echo("<div class='tableTitle'>Number Management</div><br><div class='tableDescription' width=250px>Here we can manage all the phone numbers we know about.</div><br>");

	$result = $db->query('select * from Number');

	echo("<form action='" . $this_page . "' method='get'>");

	echo "<input type='hidden' name='CRUD' value='CREATENUMBER'/>";
        echo "<input type='hidden' name='secret' value='" . $local_secret . "'/>";

	echo("<table>");
	echo("<tr><th>Number</th><th>Description</th><th></th></tr>");
	
	$rowarray = $result->fetchall(PDO::FETCH_ASSOC);
        foreach($rowarray as $row)
        {
		$rowstyle = ($row[id] % 2)==0?"d0":"d1";
		
		echo "<tr class='" .$rowstyle . "'>";
                echo "<td>$row[Number]</td><td>$row[NumberDescription]</td><td><a href='$this_url?secret=$secret_local&CRUD=DELETENUMBER&NumberID=$row[NumberID]'>delete</a></td>";
                echo "</tr>";
        }
	echo "<tr class='" .$rowstyle . "'>";
        echo "<td><input type='text' size=20 name='NewNumber' value='+44 xxxx xxx xxx'/></td>";
        echo "<td><input type='text' name='NewNumberDescription' value='NumberDescription'/></td>";
	echo "<td><input type='submit' name='Add' />";
	echo "</table>";
	echo "</form>";


	echo("</div>"); #end of the number mgmt div
	echo("</div>"); #end of the container div 
?>

</body>
</html>
