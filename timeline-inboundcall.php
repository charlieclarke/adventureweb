<?php 
	
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";


    $inboundnumber = $_REQUEST['From'];
	$twilionumber = $_REQUEST['To'];

	$twilioSID = $_REQUEST['AccountSid'];

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
	$tdb->init();

	$inboundMp3Action = 6;
	$inboundTextAction = 5;
	#see if we can find the number


	$objInboundNumber=$tdb->getPhoneNumberByNumber($inboundnumber);
	$objTwilioNumber=$tdb->getTwilioNumberByNumber($twilionumber);

	echo "<!-- getting clone info for twilio SID $twilioSID -->\n";
	$objClone = $tdb->getCloneByTwilioSID($twilioSID);

	echo "<!-- the twilio number is $twilionumber which is TNumberID $objTwilioNumber->TwilioNumberID and clone $objClone->CloneID  -->\n";
	echo "<!-- the mp3server is $mp3Server AND $objClone->MP3URL -->\n";
	#get the DEFAULT thread
	
        $defaultThreadID = $tdb->getDefaultThreadID('CALL');

	#get all inbound threads associated with this number - including default, and all others that are not 
	#even inbound calls etc. 


	$objThreadsArray = $tdb->getThreadsByPhoneNumberID($objInboundNumber->NumberID);

	#loop through all threads.
	#if they are NOT the default ID, AND they are relevent to us - then process.
	$todoxml = "";
	$todoxml = "<Say>your clone is" . $objClone->CloneName . "</Say> ";
	$gather_pre="";	
	$gather_post = "";
	$num = sizeof($objThreadsArray);
	
	echo("<!--got $num objects -->\n");
	#check to see if the thread is inbound, matches a number, and is not the default.
	foreach($objThreadsArray as $objThread) {
		echo("<!-- got threadID of $objThread->ThreadID which has twilio number of $objThread->TwilioNumberID -->\n"); 
		$ofInterest=handle_thread($objThread, $objTwilioNumber, $defaultThreadID,$objInboundNumber);

		if ($ofInterest > 0) {
			echo "<!--this thread IS of interest-->";
			$defaultThreadID = 0;
			$objMatchThread = $objThread;
		}
	}

	#now see if the thread matches the NULL group

	if ($defaultThreadID > 0) {


		$objThreadsArray = $tdb->getThreadsByNumberGroupID(0);
		$num = sizeof($objThreadsArray);
		echo("<!--got $num threads which match the null group -->\n");

		foreach($objThreadsArray as $objThread) {
			echo("<!-- got threadID of $objThread->ThreadID which has twilio number of $objThread->TwilioNumberID -->\n");
			$ofInterest=handle_thread($objThread, $objTwilioNumber, $defaultThreadID,$objInboundNumber);


			if ($ofInterest > 0) {
				echo "<!--this null group thread IS of interest-->\n";
				$objMatchThread = $objThread;
				$defaultThreadID = 0;
			}
		}

	}

	if ($defaultThreadID > 0) {
		$objMatchThread = $tdb->getThreadByThreadID($defaultThreadID);
		do_thread_action($objMatchThread);
		echo "<!--using the default threadID-->\n";
	}


	#now add to calltrack, do children
	$callTrackID = $tdb->insertIntoCallTrack(0, $objMatchThread->ThreadID, $objInboundNumber->NumberID, '', 'inbound call answered', '','');
	#deal with children
	handle_children($objMatchThread,$objInboundNumber);



#render the page
    // now greet the caller
?>
<Response>
    <?php echo $gather_pre ?>
    <?php echo $todoxml ?>
    <?php echo $gather_post ?>
</Response>

<?php
function handle_children($objThread,$objInboundNumber) {

	global $tdb;
	global $gather_pre;
	global $gather_post;
	global $phpServer;

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
				$freq = $objChildThread->Frequency;
				echo("<!-- child freq is " . $freq . "-->");

				$tdb->insertToTimeLineOffset($childID, $freq, $objInboundNumber->NumberID,"inserted as child of call thread $objThread->ThreadID with freq $freq");
			}
		}
	}


}
function handle_thread($objThread, $objTwilioNumber, $ignoreThreadID, $objInboundNumber) {


	$ofInterest = 0;
	if ($objThread->ThreadID != $ignoreThreadID && $objThread->TwilioNumberID == $objTwilioNumber->TwilioNumberID) {
		echo "<!--threadID $objThread->ThreadID might be of interest. actiontypeid is $actionTypeID-->\n" ;

		$ofInterest = do_thread_action($objThread, $objInboundNumber);
	}

	return $ofInterest;
}
function do_thread_action($objThread, $objInboundNumber) {
	global $todoxml;
	global $inboundMp3Action;
	global $inboundTextAction;
	global $mp3Server;
	global $objClone;
	
	$ofInterest = 0;
	$actionTypeID = $objThread->ActionTypeID;
	$mp3Name = $objThread->mp3Name;

	$clonemp3Server = $objClone->MP3URL;

	echo("<!--doing action: $mp3Name aciton type $actionTypeID ($inboundMp3Action)($inboundTextAction)-->\n");
	#echo("<!--mp3 server is $mp3Server-->\n");
	echo("<!--mp3 server is $clonemp3Server-->\n");
	if ($actionTypeID == $inboundMp3Action) {
		$todoxml = $todoxml . "<Play>$clonemp3Server" . "$mp3Name</Play>";
		$ofInterest = 1;
		echo "<!--threadID $objThread->ThreadID is of interest inbound mp3-->\n" ;
	} else if ($actionTypeID == $inboundTextAction) {
		echo "<!--threadID $objThread->ThreadID is of interest inbound text-->\n" ;
		$ofInterest = 1;
		$saytext = $mp3Name;
		$saytext = str_replace("[InboundName]",$objInboundNumber->NumberDescription, $saytext);
		$todoxml = $todoxml . "<Say voice='woman'>$saytext.</Say>";
	} else {
		echo "<!--threadID $objThread->ThreadID is of no interest -->\n" ;
	}
	return $ofInterest;

}
		#if we found a thread of interest, we need to do children, and add to calltrack
#		if ($ofInterest == 1) {
#			echo "<!--threadID $objThread->ThreadID is of interest so proceed-->\n" ;
#			#insert the thread into calltrack...
#			$callTrackID = $tdb->insertIntoCallTrack(0, $objThread->ThreadID, $objInboundNumber->NumberID, '', 'inbound call answered', '');
#			#deal with children
#			handle_chilren($objThread);
#
#		} else {
#			echo "<!--threadID $objThread->ThreadID not of interest so move ot next one-->\n";
#		}
#	}




?>

