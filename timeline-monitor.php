<html>
<head>
<title>call monitoring page</title>
</head>
<body>
<?php 
    header("content-type: text/html");

	$secret = $_GET["secret"];

	$local_secret = chop(file_get_contents ("/var/cache/timeline/sharedsecret"));

	#if ($secret != $local_secret) {
#		header("HTTP/1.0 401 Unauthorized");
#		exit();
#	}
		

	$db = new PDO('sqlite:/var/cache/timeline/timeline.db');


	$result = $db->query('SELECT * FROM Thread');


	echo("List of tasks<br><table border=1>");
	echo("<tr><td>Description</td><td>Type</td><td>Destination Number</td><td>MP3</td><td>Repeat Minutes</td></tr>");
	$rowarray = $result->fetchall(PDO::FETCH_ASSOC);
	
	foreach($rowarray as $row)
	{
		echo "<tr>";
		echo "<td>$row[ThreadDescription]</td><td>$row[ActionType]</td><td> $row[DestNumber]</td><td>$row[mp3Name]</td><td>$row[FrequencyMinutes]</td>";
		echo "</tr>";
	}
	echo("</table>");
	echo("<br><br>");


	$result = $db->query('select Thread.ThreadDescription,TimeLine.id, TimeLine.ThreadID, TimeLine.ActivityTime, TimeLine.Completed, TimeLine.CompletedTime, TimeLine.Description, TimeLine.Notes, Thread.ActionType, Thread.mp3Name, Thread.DestNumber, Thread.FrequencyMinutes from TimeLine, Thread where TimeLine.ThreadID = Thread.id');


        echo("<table border=1 >");
        echo("<tr><td>Time</td><td>Thread Description</td><td>Number</td><td>MP3</td><td>Completed?</td><td>Completed Time</td></tr>");
        $rowarray = $result->fetchall(PDO::FETCH_ASSOC);
        
        foreach($rowarray as $row)
        {
                echo "<tr>";
		$completed=($row[Completed]==1)?'Yes':'No';
                echo "<td>$row[ActivityTime]</td><td>$row[ThreadDescription]</td><td> $row[DestNumber]</td><td>$row[mp3Name]</td><td>$completed</td><td>$row[CompletedTime]</td>";
                echo "</tr>";
        }
        echo("</table>");
?>

</body>
</html>
