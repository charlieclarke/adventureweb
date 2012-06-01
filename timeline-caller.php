<?php 
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

	$threadID = intval($_GET["threadID"]);
	$secret = $_GET["secret"];


	 $ini_array = parse_ini_file("config.local");

        $local_secret = $ini_array['sharedSecret'];
        $db_location = $ini_array['databasepath'];

	#if ($secret != $local_secret) {
#		header("HTTP/1.0 401 Unauthorized");
#		exit();
#	}
		
	echo("<!-- playing mp3 associated with threadID " . $threadID . "-->");	
	$mp3name = "blank.mp3";

	$db = new PDO('sqlite:'.$db_location);

	$sql = "SELECT mp3Name FROM Thread WHERE id = ?";
	$q = $db->prepare($sql);
	$q->execute(array($threadID));


	$q->setFetchMode(PDO::FETCH_BOTH);

	// fetch
	while($r = $q->fetch()){
	  $mp3name = $r['mp3Name'];
	}


	#does the thread have a CHILD - if so - spawn it at <frequency> minutes time - note freqeucnt is the freq of the CHILD not the parent.
	$childID = -1;
	$sql = "SELECT ChildThreadID FROM Thread WHERE id = ? and ChildThreadID is not NULL and ChildThreadID > 0";
        $q = $db->prepare($sql);
        $q->execute(array($threadID));


        $q->setFetchMode(PDO::FETCH_BOTH);

        // fetch
        while($r = $q->fetch()){
          $childID = intval($r['ChildThreadID']);
        }

	if ($childID > 0) {
		echo("<!-- found a child " . $childID . "-->");
		#TODO split to multiple children eg SMS and a call

		$freq=-1;
		
		$sql = "SELECT FrequencyMinutes FROM Thread WHERE id = ? ";
		$q = $db->prepare($sql);
		$q->execute(array($childID));


		$q->setFetchMode(PDO::FETCH_BOTH);

		// fetch
		while($r = $q->fetch()){
		  $freq = intval($r['FrequencyMinutes']);
		}
		echo("<!-- child freq is " . $freq . "-->");
		#now we insert the new task to the timeline at now + freq minutes.

		 $sql = "INSERT INTO TimeLine select NULL," . $childID . ",datetime('now','+".$freq." minutes'),0,NULL,'inserted on call',NULL";
               

                echo("<!-- sql is " . $sql . "-->");
		$count = $db->exec($sql);
		echo("<!-- sql done " . $count . "rows -->");
	}


	$db = null;



?>
<Response>
<Pause length="2"/>
<Play>http://ec2-176-34-195-123.eu-west-1.compute.amazonaws.com/mp3/<?php echo($mp3name); ?></Play>
</Response>


