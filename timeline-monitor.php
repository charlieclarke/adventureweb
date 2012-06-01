<html>
<head>
<title>call monitoring page</title>
</head>
<body>
<?php 
    header("content-type: text/html");

	$secret = $_GET["secret"];
	$triggerAction = $_GET['TRIGGER'];
	$globalAction = $_GET['GLOBAL'];
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


	echo("List of tasks<br><table border=1>");
	echo("<tr><td>Thread ID</td><td>Description</td><td>Type</td><td>Destination Number</td><td>MP3</td><td>Repeat Minutes</td><td>ChildThreadID</td><td>Trigger</td></tr>");
	$rowarray = $result->fetchall(PDO::FETCH_ASSOC);
	
	foreach($rowarray as $row)
	{
		echo "<tr>";
		echo "<td>$row[id]</td><td>$row[ThreadDescription]</td><td>$row[ActionType] ($row[ActionName])</td><td> $row[DestNumber]</td><td>$row[mp3Name]</td><td>$row[MinutesBeforeText]$row[FrequencyMinutes]$row[MinutesAfterText]</td><td>$row[ChildThreadID]<td><td><a href='$this_url?secret=$secret_local&TRIGGER=INSERT&ThreadID=$row[id]'>insert</a>|<a href='$this_url?secret=$secret_local&TRIGGER=REMOVE&ThreadID=$row[id]'>remove</a>";
		echo "</tr>";
	}
	echo("</table>");
	echo("<br><br>");

	echo "<a href='$this_url?secret=$secret_local&GLOBAL=KILL'>Kill TimeLine</a>";
	echo "&nbsp;&nbsp;<a href='$this_url?secret=$secret_local'>Refresh Page</a>";

	$result = $db->query('select Thread.ThreadDescription,TimeLine.id, TimeLine.ThreadID, TimeLine.ActivityTime, TimeLine.Completed, TimeLine.CompletedTime, TimeLine.Description, TimeLine.Notes, Thread.ActionType, Thread.mp3Name, Thread.DestNumber, Thread.FrequencyMinutes from TimeLine, Thread where TimeLine.ThreadID = Thread.id order by TimeLine.ActivityTime');


        echo("<table border=1 >");
        echo("<tr><td>Time</td><td>Thread Description</td><td>Number</td><td>MP3</td><td>Completed?</td><td>Completed Time</td><td>Comment</td></tr>");
        $rowarray = $result->fetchall(PDO::FETCH_ASSOC);
        
        foreach($rowarray as $row)
        {
                echo "<tr>";
		$completed=($row[Completed]==1)?'Yes':'No';
                echo "<td>$row[ActivityTime]</td><td>$row[ThreadDescription]</td><td> $row[DestNumber]</td><td>$row[mp3Name]</td><td>$completed</td><td>$row[CompletedTime]</td><td>$row[Description]</td>";
                echo "</tr>";
        }
        echo("</table>");
?>

</body>
</html>
