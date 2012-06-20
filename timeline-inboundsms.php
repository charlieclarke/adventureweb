<?php 
	
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    // make an associative array of callers we know, indexed by phone number
    // if the caller is known, then greet them by name
    // otherwise, consider them just another monkey
    $inboundnumber = $_REQUEST['From'];
    $smsMessageBody = $_REQUEST['Body'];


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


	require_once('timeline-lib.php');

	$tdb = new DB($db_location);


#        $db = new PDO('sqlite:'.$db_location);

	$inboundMp3Action = 6;
	$inboundTextAction = 5;
	$inboundSMSAction = 9;
	#see if we can find the number


	$objInboundNumber=$tdb->getPhoneNumberByNumber($inboundnumber);

	#get the DEFAULT thread
	
	$defaultThreadID = $tdb->getDefaultThreadID('SMS');


	#see if there are any inbound threads associated with this number

	
	$objThreadsArray = $tdb->getThreadsByPhoneNumberID($objInboundNumber->NumberID);


	$todoxml = "";

	foreach($objThreadsArray as $objThread) {
                echo("<!-- got threadID of $objThread->ThreadID -->"); 

		$ofInterest = deal_with_thread($objThread, $objInboundNumber,"inbound SMS: $smsMessageBody");

		if ($ofInterest > 0) {
			#then at least one thread matched, so we go forward
			$defaultThreadID = 0;
		}

	}


	if ($defaultThreadID >0) {
		echo("<!-- doing default bahvious -->"); 
		#do default behaviour
		$objThread = $tdb->getThreadByThreadID($defaultThreadID);


		deal_with_thread($objThread, $objInboundNumber,"inbound SMS: $smsMessageBody");


        }




#render the page
    // now great the caller
?>
<Response>
    <?php echo $todoxml ?>
</Response>

<?php
#function definitions

function deal_with_children($objThread, $objNumber) {

	global $tdb;
	
	echo("<!-- deal with children $objThread->ChildThreadText -->");
	#deal with children
	foreach($objThread->ChildThreads as $childID) {


		if ($childID > 0) {
			echo("<!-- found a child " . $childID . "-->");

			$freq=-1;

			$objChildThread = $tdb->getThreadByThreadID($childID);

			$freq = $objChildThread->Frequency;


			echo("<!-- child freq is " . $freq . "-->");
			$tdb->insertToTimeLineOffset($objChildThread->ThreadID, $freq, $objNumber->NumberID,'inserted on SMS') ;


		}
	}
}



function deal_with_thread($objThread, $objInboundNumber,$calltracktext) {
	echo "<!--dealing with threadID $threadID-->";
	$dealWithChildren = 0;

	global $tdb;
	global $smsMessageBody;

	$dealWithChildren = 0;
	if ($objThread->ActionTypeID == ActionType::$InboundSMSAction) {

		echo"<!-- found matching inbound SMS action $objThread->ThreadID with mp3 $objThread->mp3Name  -->";
		#if the content of the text matches the mp3name field - OR the mp3name is blank, kick off the children.

		#this is a TODO...
		if (empty($objThread->mp3Name)) {
			$dealWithChildren=1;
		$tdb->insertIntoCallTrack(0, $objThread->ThreadID, $objInboundNumber->NumberID, '', "inbound SMS picked up: $calltracktext", '');
			echo "<!-- no filter so lets deal with children -->";
		} else {
			$posInString = strpos(" $smsMessageBody", "$objThread->mp3Name");
			echo"<!-- found $objThread->mp3Name in $smsMessageBody at $posInString -->";

			if ($posInString > 0) {
				$dealWithChildren = 1;
			$tdb->insertIntoCallTrack(0, $objThread->ThreadID, $objInboundNumber->NumberID, '', "inbound SMS picked up: $calltracktext - matched $objThread->mp3Name", '');
			} else {
				$dealWithChildren=0;
			$tdb->insertIntoCallTrack(0, $objThread->ThreadID, $objInboundNumber->NumberID, '', "inbound SMS picked up: $calltracktext - did NOT match $objThread->mp3Name", '');

				echo"<!-- found $mp3Name in $smsMessageBody at $posInString -->";
			}
		}
		

	}

	if ($dealWithChildren > 0) {

		echo"<!-- sort out kids -->";
		deal_with_children($objThread, $objInboundNumber);


	}



	return $dealWithChildren;
}

#end of function defs
?>
