<?php 
	
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    // here we are going to recieve a SIM message from the mobile client
	//and process it the same way we handle an inbound SMS

    $inboundnumber = $_REQUEST['From'];
    $smsMessageBody = $_REQUEST['Body'];
$inboundGUID = $_REQUEST['GUID'];
$guidCookie = $_COOKIE["SIMCOOKIE"];


	$twilionumber = $_REQUEST['To'];

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
	$inboundSIMAction = 13;
	#see if we can find the number

	#bad security but good testing
	$guidCookie = $inboundGUID;

	echo("<!--got guidCookie $guidCookie-->\n");
	if ($guidCookie == $inboundGUID) {
		//we have at least the correct cookie.
		//lets get the number

		$objInboundNumber= $tdb->getPhoneNumberByGuid($guidCookie);	
		echo("<!--got inboundNumber $objInboundNumber->NumberID-->\n");


	} else {
		exit("unknown inbound number");
	}
	//$objInboundNumber=$tdb->getPhoneNumberByNumber($inboundnumber);
	$objTwilioNumber=$tdb->getTwilioNumberByNumber($twilionumber);

	echo("<!--got twilionumber of  $objTwilioNumber->TwilioNumberID-->\n");
	echo"<!-- got SMS message of $smsMessageBody-->\n";
	#get the DEFAULT thread
	
	$defaultThreadID = $tdb->getDefaultThreadID('SIM');


	#see if there are any inbound threads associated with this number

	#first - any with the group

	#then - any on the null group which are NOT blank

	#then - default

	#only allow ONE match. as soon as one match is found, THAT is the threadID we work with. 
	#only register ONCE in calltrak


	

	#get threads which match for this number
	$objThreadsArray = $tdb->getThreadsByPhoneNumberID($objInboundNumber->NumberID);


	$todoxml = "";

	#first for NON blank mp3 names
	echo("<!-- doing nunmber match group NON BLANK bahvious -->"); 
	foreach($objThreadsArray as $objThread) {
		if (!empty($objThread->mp3Name)) {
			echo("<!-- got threadID of $objThread->ThreadID -->"); 

			$ofInterest = deal_with_thread($objThread, $objInboundNumber,"inbound SIM: $smsMessageBody",$objTwilioNumber);

			if ($ofInterest > 0) {
				#then at least one thread matched, so we go forward
				$defaultThreadID = 0;
				$objMatchThread = $objThread;
				$note = "SIM filter match";
			}
		}

	}
	#now do it again for blanks
	
	if ($defaultThreadID > 0) {
		echo("<!-- doing nunmber match group BLANK bahvious -->"); 
		foreach($objThreadsArray as $objThread) {
			if (empty($objThread->mp3Name)) {
				echo("<!-- got threadID of $objThread->ThreadID -->");

				$ofInterest = deal_with_thread($objThread, $objInboundNumber,"inbound SIM: $smsMessageBody",$objTwilioNumber);

				if ($ofInterest > 0) {
					#then at least one thread matched, so we go forward
					$defaultThreadID = 0;
					$objMatchThread = $objThread;
					$note = "SIM number match";
					echo "<!--matched blank number group behaviour-->";
				}
	
			}

		}
	}
		


	#now get threads which match the null group


	echo "<!--about to get null group threads-->\n";
	$objThreadsArray = $tdb->getThreadsByNumberGroupID(0);

	$num = sizeof($objThreadsArray);
	echo "<!--got null group threads $num -->\n";
	if ($defaultThreadID >0) {
	
		echo("<!-- doing null group NON BLANK bahvious -->"); 
		#do default behaviour

		foreach($objThreadsArray as $objThread) {
			echo "<!--got thread-->\n";
                        if (!empty($objThread->mp3Name)) {
				echo("<!--trying to match $smsMessageBody to $objThread->mp3Name-->\n");

                                $ofInterest = deal_with_thread($objThread, $objInboundNumber,"inbound SIM: $smsMessageBody",$objTwilioNumber);
        
                                if ($ofInterest > 0) {
                                        #then at least one thread matched, so we go forward
                                        $defaultThreadID = 0;
                                        $objMatchThread = $objThread;
					$note = "SIM filter match - null group";
                                }
                        }

                }




        }

	#and blank null group

	if ($defaultThreadID >0) {
                echo("<!-- doing null group blank bahvious -->");
                #do default behaviour

                foreach($objThreadsArray as $objThread) {
                        if (empty($objThread->mp3Name)) {

                                $ofInterest = deal_with_thread($objThread, $objInboundNumber,"inbound SIM: $smsMessageBody",$objTwilioNumber);

                                if ($ofInterest > 0) {
                                        #then at least one thread matched, so we go forward 
                                       $defaultThreadID = 0;
                                        $objMatchThread = $objThread;
					$note = "SIM number match - null group";
                                }
                        }

                }




        }

	#AND NOW DEFAULT
	if ($defaultThreadID > 0) {
		$objMatchThread = $tdb->getThreadByThreadID($defaultThreadID);
			$note="SIM default behaviour";
	}

	#deal with children

	deal_with_children($objMatchThread, $objInboundNumber);

	$tdb->insertIntoCallTrack(0, $objMatchThread->ThreadID, $objInboundNumber->NumberID, '', "inbound $note ($objMatchThread->mp3Name): $smsMessageBody", '',$smsMessageBody);





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
			$tdb->insertToTimeLineOffset($objChildThread->ThreadID, $freq, $objNumber->NumberID,'inserted on SIM') ;


		}
	}
}



function deal_with_thread($objThread, $objInboundNumber,$calltracktext,$objTwilioNumber) {
	echo "<!--dealing with threadID $threadID-->";
	$dealWithChildren = 0;

	global $tdb;
	global $smsMessageBody;

	$ofInterest=0;
	$threadID = 0;
	if ($objThread->ActionTypeID == ActionType::$InboundSIMAction  && $objThread->TwilioNumberID == $objTwilioNumber->TwilioNumberID) {

		echo"<!-- found matching inbound SIM action $objThread->ThreadID with mp3 $objThread->mp3Name on $objTwilioNumber->TwilioNumberName  -->";
		#if the content of the text matches the mp3name field - OR the mp3name is blank, kick off the children.

		#this is a TODO...
		if (empty($objThread->mp3Name)) {
			$ofInterest=1;
			echo "<!-- no filter so lets deal with children -->";
		} else {
			$posInString = strpos(" $smsMessageBody", "$objThread->mp3Name");

			if ($posInString > 0) {
				$ofInterest = 1;
				echo"<!-- found $mp3Name in $smsMessageBody at $posInString -->";
			} else {
				$ofInterest=0;

				echo"<!-- DID NOT find $mp3Name in $smsMessageBody at $posInString -->";
			}
		}
		

	}


	return $ofInterest;
}

#end of function defs
?>
