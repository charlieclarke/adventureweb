<?php 
	
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    // make an associative array of callers we know, indexed by phone number
    // if the caller is known, then greet them by name
    // otherwise, consider them just another monkey
    $inboundnumber = $_REQUEST['From'];


	#sort out config 

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

        $db = new PDO('sqlite:'.$db_location);

	require_once('timeline-lib.php');

	$tdb = new DB($db_location);

	$inboundMp3Action = 6;
	$inboundTextAction = 5;
	#see if we can find the number


	$objInboundNumber=$tdb->getPhoneNumberByNumber($inboundnumber);


	#get the DEFAULT thread
	
        $defaultThreadID = $tdb->getDefaultThreadID('CALL');

	#get all inbound threads associated with this number - including default, and all others that are not 
	#even inbound calls etc. 


	$objThreadsArray = $tdb->getThreadsByPhoneNumberID($objInboundNumber->NumberID);
	

	#loop through all threads.
	#if they are NOT the default ID, AND they are relevent to us - then process.
	$todoxml = "";
	$gather_pre="";	
	$gather_post = "";
	foreach($objThreadsArray as $objThread) {
		echo("<!-- got threadID of $objThread->ThreadID -->"); 

		$ofInterest=0;
		

		if ($objThread->ThreadID != $defaultThreadID) {

			$actionTypeID = $objThread->ActionTypeID;
			$mp3Name = $objThread->mp3Name;

			if ($actionTypeID == $inboundMp3Action) {
				$defaultThreadID = 0;
				$todoxml = $todoxml . "<Play>$mp3Server$mp3Name</Play>";
				$ofInterest = 1;
			} else if ($actionTypeID == $inboundTextAction) {

				$ofInterest = 1;
				$defaultThreadID = 0;
				$saytext = $mp3Name;
				$saytext = str_replace("[InboundName]",$objInboundNumber->NumberDescription, $saytext);
				$todoxml = $todoxml . "<Say voice='woman'>$saytext.</Say>";

			}

			#if we found a thread of interest, we need to do children, and add to calltrack

			if ($ofInterest == 1) {

				#insert the thread into calltrack...

				$callTrackID = $tdb->insertIntoCallTrack(0, $objThread->ThreadID, $objInboundNumber->NumberID, '', 'inbound call answered', '');
				#deal with children
				foreach($objThread->ChildThreads as $childID) {

					$childID = intval($childID);

					if ($childID > 0) {
						echo("<!-- found a child " . $childID . "-->");

						$objChildThread = $tdb->getThreadByThreadID($childID);

						echo("<!-- got child ID object-->");
						#see if its a dialtone child - if so, we dont do anything active
						#we jsut make sure the response has a Gather

						if ($objChildThread->ActionTypeID == ActionType::$DialToneActionType) {

						echo("<!-- found a child which is a dial tone-->");
							$gather_pre = "<Gather method=\"GET\" action=\"$phpServer/timeline-inboundtone.php?ParentThreadID=$objThread->ThreadID&amp;ThreadID=$childID&amp;CallTrackID=$callTrackID&amp;AdditionalNumberID=$objInboundNumber->NumberID\">";
							$gather_post = "</Gather>";

						} else {

							$freq = $objChildThread->FrequencyMinutes;
							echo("<!-- child freq is " . $freq . "-->");

							$tdb->insertToTimeLineOffset($childID, $freq, $objInboundNumber->NumberID,"inserted as child of call thread $objThread->ThreadID");
						}
					}
				}

			}
		} 
	}

	if ($defaultThreadID >0) {

		$objThread = $tdb->getThreadByThreadID($defaultThreadID);
		#do default behaviour
		$actionTypeID = $objThread->ActionTypeID;
		$mp3Name = $objThread->mp3Name;

		if ($actionTypeID == $inboundMp3Action) {
			$defaultThreadID = 0;
			$todoxml = $todoxml . "<Play>$mp3Server$mp3Name</Play>";
		} else if ($actionTypeID == $inboundTextAction) {

			$defaultThreadID = 0;
			$saytext = $mp3Name;
			$saytext = str_replace("[InboundName]",$objInboundNumber->NumberDescription, $saytext);
			$todoxml = $todoxml . "<Say voice='woman'>$saytext.</Say>";

		}

		#if we found a thread of interest, we need to do children, and add to calltrack

		if ($defaultThreadID ==0) {

			#deal with children
			foreach($objThread->ChildThreads as $childID) {

				$childID = intval($childID);

				if ($childID > 0) {
					echo("<!-- found a child " . $childID . "-->");

					$objChildThread = $tdb->getThreadByThreadID($childID);

					  $freq = $objChildThread->FrequencyMinutes;
					echo("<!-- child freq is " . $freq . "-->");

					$tdb->insertToTimeLineOffset($childID, $freq, $objInboundNumber->NumberID,'inserted as child of call');
				}
			}

			#insert the thread into calltrack...

			$tdb->insertIntoCallTrack(0, $objThread->ThreadID, $objInboundNumber->NumberID, '', 'inbound call answered', '');
		}



	}







#render the page
    // now greet the caller
?>
<Response>
    <?php echo $gather_pre ?>
    <?php echo $todoxml ?>
    <?php echo $gather_post ?>
</Response>


