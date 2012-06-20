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


	require_once('timeline-lib.php');

        $tdb = new DB($db_location);

	#if ($secret != $local_secret) {
#		header("HTTP/1.0 401 Unauthorized");
#		exit();
#	}
		
	echo("<!-- playing mp3 associated with threadID " . $threadID . "-->");	


	$objThread = $tdb->getThreadByThreadID($threadID);


	$additional_number_id = 0;
	#what is the number we are calling - this becomes the 'additionalNumberID' for callbacks etc.
        $additional_number_id = $tdb->getNumberIDFromCallTrack($callTrackID);;

	


	#does the thread have a CHILD - if so - spawn it at <frequency> minutes time - note freqeucnt is the freq of the CHILD not the parent.
	#unless the child is a DIALTONE response - in which case, mark the response ot this call as needing 'gather', and send the gather
	# to inboundtone.php noting the threadID of the child...

	foreach($objThread->ChildThreads as $childID) {
	

		if ($childID > 0) {
			echo("<!-- found a child " . $childID . "-->");


			$freq=-1;
			
			$objChild = $tdb->getThreadByThreadID($childID);

			if ($objChild->ActionTypeID != ActionType::$DialToneActionType) {
				echo("<!-- normal child action type, so insert into timeline-->");
				#now we insert the new task to the timeline at now + freq minutes.

				$tdb->insertToTimeLineOffset($childID, $objChild->Frequency, $additional_number_id,'inserted on call');

			} else {
				$dialToneThreadID=$childID;
				echo("<!-- dialtone child action type, so do not insert into timeline-->");
			}
		}
	}

	#update call track
	$tdb->update_calltrack_status($callTrackID, 'call answered');


	echo "<Response>";
	echo "<Pause length=\"2\"/>";
	if ($dialToneThreadID > 0) {
		echo "\n<Gather method=\"GET\" action=\"$phpServer/timeline-inboundtone.php?ParentThreadID=$threadID&amp;ThreadID=$dialToneThreadID&amp;CallTrackID=$callTrackID&amp;AdditionalNumberID=$additional_number_id\">";
	}
	echo "\n<Play>$mp3Server$objThread->mp3Name</Play>";
	if ($dialToneThreadID > 0) {
		echo "</Gather>";
	}
	echo "</Response>";

?>

