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
$username = "admin";
$password  = "warsaw";
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
	$triggerAction = $_GET['TRIGGER'];
	$globalAction = $_GET['GLOBAL'];
	$crudAction = $_GET['CRUD'];
	$threadID = intval($_GET['ThreadID']);

	

	
	$local_secret = $ini_array['sharedSecret'];
	$db_location = $ini_array['databasepath'];
	$base_url = $ini_array['phpServer'];
	$mp3_url = $ini_array['mp3Server'];
	$instance_name = $ini_array['instanceName'];
	
	$this_url = $base_url . "/timeline-monitor.php";
	

	#if ($secret != $local_secret) {
#		header("HTTP/1.0 401 Unauthorized");
#		exit();
#	}
		
	#init database
	$db = new PDO('sqlite:'.$db_location);


	#perform actions etc.
	if ($crudAction == 'UPDATEDEFAULTINBOUNDTHREADSMS') {

                $sql = "update DefaultInboundThread set ThreadID = ? where Type='SMS'";

                $st = $db->prepare($sql);
                $st->execute(array($threadID));

        }

	if ($crudAction == 'UPDATEDEFAULTINBOUNDTHREADCALL') {

                $sql = "update DefaultInboundThread set ThreadID = ? where Type='CALL'";

                $st = $db->prepare($sql);
                $st->execute(array($threadID));

        }



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
		$newChildThreadID1 = intval($_GET["NewChildThreadID1"]);
		$newChildThreadID2 = intval($_GET["NewChildThreadID2"]);
		$newStartHour = intval($_GET["NewStartTimeHours"]);
		$newStopHour = intval($_GET["NewStopTimeHours"]);
		$newStartMinute = intval($_GET["NewStartTimeMinutes"]);
		$newStopMinute = intval($_GET["NewStopTimeMinutes"]);

		$newChildThreadID = 0;

		if ($newChildThreadID1 > 0) {
			$newChildThreadID = $newChildThreadID1;
			if ($newChildThreadID2 > 0) {
				$newChildThreadID = $newChildThreadID . "," . $newChildThreadID2;
			}
		}

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

			$sql = "INSERT INTO TimeLine select NULL," . $threadID . ",datetime('now'),0,NULL,'triggerd from monitor page',NULL,0";

			echo("<!-- sql is " . $sql . "-->");
			$count = $db->exec($sql);
			echo("<!-- sql done " . $count . "rows -->");


		}
	}

	if ($triggerAction == 'INSERTTIME') {
		$triggerDate= $_GET["INSERTTIME"];

                if ($threadID > 0) {
                        #we have a valid trigger to insert

                        $sql = "INSERT INTO TimeLine select NULL," . $threadID . ",'$triggerDate',0,NULL,'timetriggered from monitoring page',NULL";

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

	#get last heartbeat from db

			
	$result = $db->query("SELECT HeartBeatTime, strftime('%s','now') - strftime('%s',HeartBeatTime) as LastHeartBeatAgo, DATETIME('now') as CurrentDBTime FROM HeartBeat where HeartBeatName='LastTimeLine'");

        $rowarray = $result->fetchall(PDO::FETCH_ASSOC);

	$lastHeartBeat = 'never';
	$lastHeartBeatAgo = -100;
	$currentDBTime = '';
        foreach($rowarray as $row)
        {

                $lastHeartBeat = $row['HeartBeatTime'];
                $lastHeartBeatAgo = $row['LastHeartBeatAgo'];
                $currentDBTime = $row['CurrentDBTime'];
	} 

	if ($lastHeartBeatAgo < 2) {
		$heartBeatText = "TimeLine Active and OK - $lastHeartBeat";
	} else {
		$heartBeatText = "TimeLine Appears Down - $lastHeartBeat";
	}

	#render page

	#top menu bar
	echo("<div class='menuBar'><a href=$base_url/timeline-monitor.php>Monitor and Manager Threads</a>&nbsp;|&nbsp;<a href=$base_url/timeline-groups.php>Manage Numbers and Groups</a>&nbsp;|&nbsp;$instance_name&nbsp|&nbsp$heartBeatText</div>");
	echo("<br><br>");


	echo("<form action='" . $this_page . "' method='get'>");

	echo("<div class='tableTitle'>List of Threads</div><br><div class='tableDescription' width=250px>This is a list of all Threads available. You can trigger a task, which adds it to a timeline. Use the bottom row to add a new task. You can remove a task, which deletes all instances if that task from the timeline. You can also (be careful) delete the task itself.</div>");


	echo("<br><br>");
	 echo("<div class='tableTitle'>List of mp3s</div><br>");



	$dirlist = file_get_contents($mp3_url);

	preg_match_all("(\"\w+\.mp3\")", $dirlist, $out, PREG_PATTERN_ORDER); 
	foreach($out[0] as $mp3) {
		echo($mp3 . ", ");

	}

	echo("<br><br>");
	echo("Insert Thread at a specific Time onto TimeLine: Time in GMT to kick off insert. Format is [YYYY-MM-DD hh:mm:ss]");
	echo("<input name='INSERTTIME' size=23 value='$currentDBTime'>");

	echo "<input name='TRIGGER' value='INSERTTIME' type='hidden'><select  style='width:100px;margin:5px 0 5px 0;' name='ThreadID'>";

                $result = $db->query("SELECT * from Thread");
                $rowarray = $result->fetchall(PDO::FETCH_ASSOC);
                foreach($rowarray as $row) {

                        echo "<option value='$row[id]'>$row[id] ($row[ThreadDescription])</option>";
                }

        echo "</select><input type='submit' value='trigger at time'>";



	echo("</form>");

	#default inbound thread
	echo("<form>");
	echo("Set the Default Inbound SMS Thread:");
	echo "<input name='CRUD' value='UPDATEDEFAULTINBOUNDTHREADSMS' type='hidden'><select  style='width:100px;margin:5px 0 5px 0;' name='ThreadID'>";

                $result = $db->query("SELECT * from Thread where id in (Select ThreadID from DefaultInboundThread where Type='SMS')");
                $rowarray = $result->fetchall(PDO::FETCH_ASSOC);
                foreach($rowarray as $row) {

                        echo "<option value='$row[id]'>CURRENT: $row[id] ($row[ThreadDescription])</option>";
                }
                $result = $db->query("SELECT * from Thread");
                $rowarray = $result->fetchall(PDO::FETCH_ASSOC);
                foreach($rowarray as $row) {

                        echo "<option value='$row[id]'>$row[id] ($row[ThreadDescription])</option>";
                }

        echo "</select><input type='submit' value='set'>";

	echo "</form>";

	echo("<form>");
        echo("Set the Default Inbound Call Thread:");
        echo "<input name='CRUD' value='UPDATEDEFAULTINBOUNDTHREADCALL' type='hidden'><select  style='width:100px;margin:5px 0 5px 0;' name='ThreadID'>";

                $result = $db->query("SELECT * from Thread where id in (Select ThreadID from DefaultInboundThread where Type='CALL')");
                $rowarray = $result->fetchall(PDO::FETCH_ASSOC);
                foreach($rowarray as $row) {

                        echo "<option value='$row[id]'>CURRENT: $row[id] ($row[ThreadDescription])</option>";
                }
                $result = $db->query("SELECT * from Thread");
                $rowarray = $result->fetchall(PDO::FETCH_ASSOC);
                foreach($rowarray as $row) {

                        echo "<option value='$row[id]'>$row[id] ($row[ThreadDescription])</option>";
                }

        echo "</select><input type='submit' value='set'>";

        echo "</form>";
	

	echo "<form action='" . $this_page . "' method='get'><table>";

	$result = $db->query('SELECT * FROM Thread,Action, Groups where Thread.DestNumber = Groups.GroupID and Thread.ActionType = Action.ActionTypeID');
	echo("<tr><th>ID</th><th>Description</th><th>Type</th><th>Phone Number Group</th><th>MP3 / message</th><th>Repeat Minutes</th><th>ChildThreadID</th><th>Time Range</th><th>Trigger</th></tr>");
	$rowarray = $result->fetchall(PDO::FETCH_ASSOC);
	$maxID = 0;	
echo "<br><br>";
	
	foreach($rowarray as $row)
	{
		$rowstyle = ($row[id] % 2)==0?"d0":"d1";
		
		echo "<tr class='" .$rowstyle . "'>";
		echo "<td>$row[id]</td><td>$row[ThreadDescription]</td><td>$row[ActionType] ($row[ActionName])</td><td> $row[GroupName]</td><td>$row[mp3Name]</td><td>$row[MinutesBeforeText]$row[FrequencyMinutes]$row[MinutesAfterText]</td><td>$row[ChildThreadID]</td><td>$row[StartTimeHour]:$row[StartTimeMinute] -&gt; $row[StopTimeHour]:$row[StopTimeMinute]</td><td><a href='$this_url?secret=$secret_local&TRIGGER=INSERT&ThreadID=$row[id]'>insert</a>|<a href='$this_url?secret=$secret_local&TRIGGER=REMOVE&ThreadID=$row[id]'>remove</a>|<a href='$this_url?secret=$secret_local&CRUD=DELETETHREAD&ThreadID=$row[id]'>delete</a></td>";
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

		$result = $db->query("SELECT * from Groups");
		$rowarray = $result->fetchall(PDO::FETCH_ASSOC);
		foreach($rowarray as $row) {

			echo "<option value='$row[GroupID]'>$row[GroupName] </option>";
		}

	echo "</select>";
	echo "</td>";
	echo "<td><input type='text' name='NewMp3Name' value='mp3name / message'/></td>";
	echo "<td><input type='text' name='NewFrequency' value='0'/></td>";
	echo "<td><select  style='width:100px;margin:5px 0 5px 0;' name='NewChildThreadID1'>";

		echo "<option value='0'>0 (no child thread)</option>";
		$result = $db->query("SELECT * from Thread");
		$rowarray = $result->fetchall(PDO::FETCH_ASSOC);
		foreach($rowarray as $row) {

			echo "<option value='$row[id]'>$row[id] ($row[ThreadDescription])</option>";
		}

	echo "</select>";
	echo "<br>";
	echo "<select  style='width:100px;margin:5px 0 5px 0;' name='NewChildThreadID2'>";

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
        
	$rownum=0;
        foreach($rowarray as $row)
        {
		$rowstyle = (++$rownum % 2)==0?"d0":"d1";
		
		echo "<tr class='" .$rowstyle . "'>";
		$completed=($row[Completed]==1)?'Yes (' . $row[CompletedTime] . ")":'No';
                echo "<td>$row[id]</td><td>$row[ActivityTime]</td><td>$row[ThreadDescription]</td><td> $row[DestNumber]</td><td>$row[mp3Name]</td><td>$completed</td><td>$row[Description]</td>";
                echo "</tr>";
        }
        echo("</table>");
	echo("</div>"); #end of the timeline div
	echo("<div id='callTrak' >");

	echo("<div class='tableTitle'>History</div><br><div class='tableDescription' width=250px>The History shows all inbound and outbound calls</div>");
	echo "<br>";

	$result = $db->query('select CallTrack.IsOutbound, Thread.ThreadDescription,Number.Number, Thread.mp3Name, CallTrack.TrackTime, CallTrack.StatusText from Thread, Number, CallTrack where Thread.id = CallTrack.ThreadID and CallTrack.TrackNumberID = Number.NumberID order by CallTrack.TrackID desc');


        echo("<table>");
        echo("<tr><th> </th><th>Time</th><th>Thread Description</th><th>Number</th><th>MP3 / Text</th><th>Status</th></tr>");
        $rowarray = $result->fetchall(PDO::FETCH_ASSOC);

	$rownum=1;
        foreach($rowarray as $row)
        {
                $rowstyle = ($rownum++ % 2)==0?"d0":"d1";
                $inout = ($row[IsOutbound]!=0)?"OUTBOUND":"INBOUND";

                echo "<tr class='" .$rowstyle . "'>";
                echo "<td>$inout</td><td>$row[TrackTime]</td><td>$row[ThreadDescription]</td><td>$row[Number]</td><td>" . htmlspecialchars($row['mp3Name']) . "</td><td>" . htmlspecialchars( $row['StatusText']) . "</td>";
                echo "</tr>";
        }
        echo("</table>");

	


	echo("</div>"); #end of the number mgmt div
	echo("</div>"); #end of the container div 
?>

</body>
</html>
