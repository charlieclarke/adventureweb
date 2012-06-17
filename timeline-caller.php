<?php 
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

	$threadID = intval($_GET["threadID"]);
	$secret = $_GET["secret"];
	$callTrackID = intval($_GET["CallTrackID"]);

	$dialToneActionType=10;
	$dialToneThreadID = 0;

	$machinename =  gethostname();

	if (preg_match("/local/i",$machinename)) {
                $configfile = "/var/tmp/config.local";
        } else {
                $configfile = "/var/cache/timeline/config.local";
        }
	echo "<!-- config = " . $configfile . "-->";

         $ini_array = parse_ini_file($configfile);

        $local_secret = $ini_array['sharedSecret'];
        $db_location = $ini_array['databasepath'];
        $mp3Server = $ini_array['mp3Server'];
        $phpServer = $ini_array['phpServer'];

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


	$additional_number_id = 0;
	#what is the number we are calling - this becomes the 'additionalNumberID' for callbacks etc.

	sql = "SELECT NumberID FROM CallTrack WHERE TrackID = ?";
        $q = $db->prepare($sql);
        $q->execute(array($callTrackID));


        $q->setFetchMode(PDO::FETCH_BOTH);

        // fetch
        while($r = $q->fetch()){
          $additional_number_id = $r['NumberID'];
        }

	


	#does the thread have a CHILD - if so - spawn it at <frequency> minutes time - note freqeucnt is the freq of the CHILD not the parent.
	#unless the child is a DIALTONE response - in which case, mark the response ot this call as needing 'gather', and send the gather
	# to inboundtone.php noting the threadID of the child...
	$childID = -1;
	$sql = "SELECT ChildThreadID FROM Thread WHERE id = ? and ChildThreadID is not NULL and ChildThreadID > 0";
        $q = $db->prepare($sql);
        $q->execute(array($threadID));


        $q->setFetchMode(PDO::FETCH_BOTH);

        // fetch
        while($r = $q->fetch()){
          $childtext = $r['ChildThreadID'];
        }
	echo("<!-- found childtext " . $childtext . "-->");

	$childIDs = explode(',',$childtext);
	foreach($childIDs as $childID) {
	
		$childID = intval($childID);

		if ($childID > 0) {
			echo("<!-- found a child " . $childID . "-->");


			$freq=-1;
			
			$sql = "SELECT ActionType, FrequencyMinutes FROM Thread WHERE id = ? ";
			$q = $db->prepare($sql);
			$q->execute(array($childID));


			$q->setFetchMode(PDO::FETCH_BOTH);

			// fetch
			while($r = $q->fetch()){
				$actionTypeID = intval($r['ActionType']);
				$freq = intval($r['FrequencyMinutes']);
			}
			echo("<!-- child freq is " . $freq . "-->");

			if ($actionTypeID != $dialToneActionType) {
				echo("<!-- normal child action type, so insert into timeline-->");
				#now we insert the new task to the timeline at now + freq minutes.

				 $sql = "INSERT INTO TimeLine select NULL," . $childID . ",datetime('now','+".$freq." minutes'),0,NULL,'inserted on call',NULL";
			       

				echo("<!-- sql is " . $sql . "-->");
				$count = $db->exec($sql);
				echo("<!-- sql done " . $count . "rows -->");
			} else {
				$dialToneThreadID=$childID;
				echo("<!-- dialtone child action type, so do not insert into timeline-->");
			}
		}
	}

	#update call track
	$sql = "update CallTrack set StatusText='call answered', TrackTime=DATETIME('now') where TrackID=$callTrackID";


                echo("<!-- sql is " . $sql . "-->");
                $count = $db->exec($sql);
                echo("<!-- sql done " . $count . "rows -->");


	$db = null;



	echo "<Response>";
	echo "<Pause length=\"2\"/>";
	if ($dialToneThreadID > 0) {
		echo "\n<Gather method=\"GET\" action=\"$phpServer/timeline-inboundtone.php?ParentThreadID=$threadID&amp;ThreadID=$dialToneThreadID&amp;CallTrackID=$callTrackID&amp;AdditionalNumberID=$additional_number_id\">";
	}
	echo "\n<Play>$mp3Server$mp3name</Play>";
	if ($dialToneThreadID > 0) {
		echo "</Gather>";
	}
	echo "</Response>";

?>

