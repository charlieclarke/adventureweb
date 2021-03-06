<?php 
	
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    // make an associative array of callers we know, indexed by phone number
    // if the caller is known, then greet them by name
    // otherwise, consider them just another monkey
    $inboundnumber = $_REQUEST['From'];
    $smsMessageBody = $_REQUEST['Body'];

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


	require_once('timeline-lib.php');

	$tdb = new DB($db_location);

	$inboundMp3Action = 6;
	$inboundTextAction = 5;
	$inboundSMSAction = 9;
	#see if we can find the number

	$objClone = $tdb->getCloneByTwilioSID($twilioSID);
	$objTwilioNumber=$tdb->getTwilioNumberByNumber($twilionumber,$objClone->CloneID);
	$objInboundNumber=$tdb->getPhoneNumberByNumber($inboundnumber,$objClone->CloneID);

	#get the DEFAULT thread
	$defaultThreadID = 1;#this is now a placeholder for 'found match'

	#see if there are any inbound threads associated with this number

	#first - any with a named group - find match text, then match on blank.

	#then - any on the null group which are NOT blank. and then on a null group 'blank' catch all.


	#only allow ONE match. as soon as one match is found, THAT is the threadID we work with. 
	#only register ONCE in calltrak
	

	#get threads which match for this number
	$objThreadsArray = $tdb->getThreadsByPhoneNumberID($objInboundNumber->NumberID);


	$todoxml = "";

	#first for NON blank mp3 names not in the null group
	echo("<!-- working on inbound SMS message $smsMessageBody -->\n"); 
	echo("<!-- doing nunmber match group NON BLANK bahvious -->"); 
	foreach($objThreadsArray as $objThread) {
		if ($defaultThreadID > 0 && $objThread->DestGroupID > 0) {
			if (!empty($objThread->mp3Name)) {
				
				echo("<!-- looking at non blank matches - matching against threadID of $objThread->ThreadID -->"); 
				echo("<!--trying to match $smsMessageBody to $objThread->mp3Name -->\n");

				$ofInterest = deal_with_thread($objThread, $objInboundNumber,"inbound SMS: $smsMessageBody",$objTwilioNumber);

				if ($ofInterest > 0) {
					#then at least one thread matched, so we go forward
					$defaultThreadID = 0;
					$objMatchThread = $objThread;
					$note = "SMS filter match";
					echo "<!--matched nonblank number group behaviour - -->";
				}
			}
			}

	}
	#now do it again for blanks not in the null group
	
	if ($defaultThreadID > 0) {
		echo("<!-- doing nunmber match group BLANK bahvious -->"); 
		foreach($objThreadsArray as $objThread) {
			if ($defaultThreadID > 0) {
				if (empty($objThread->mp3Name)) {
					echo("<!-- looking at mpty matches got threadID of $objThread->ThreadID -->");

					$ofInterest = deal_with_thread($objThread, $objInboundNumber,"inbound SMS: $smsMessageBody",$objTwilioNumber);

					if ($ofInterest > 0) {
						#then at least one thread matched, so we go forward
						$defaultThreadID = 0;
						$objMatchThread = $objThread;
						$note = "SMS blank number match";
						echo "<!--matched blank number group behaviour-->";
					}
		
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

		foreach($objThreadsArray as $objThread) {
			echo "<!--got thread-->\n";
                        if (!empty($objThread->mp3Name)) {
				echo("<!--trying to match $smsMessageBody to $objThread->mp3Name -->\n");

                                $ofInterest = deal_with_thread($objThread, $objInboundNumber,"inbound SMS: $smsMessageBody",$objTwilioNumber);
        
                                if ($ofInterest > 0) {
                                        #then at least one thread matched, so we go forward
                                        $defaultThreadID = 0;
                                        $objMatchThread = $objThread;
					$note = "SMS filter match - null group";
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

                                $ofInterest = deal_with_thread($objThread, $objInboundNumber,"inbound SMS: $smsMessageBody",$objTwilioNumber);

                                if ($ofInterest > 0) {
                                        #then at least one thread matched, so we go forward 
                                       $defaultThreadID = 0;
                                        $objMatchThread = $objThread;
					$note = "SMS number match - null group";
                                }
                        }

                }

        }

	#deal with children

	if ($defaultThreadID > 0) {
		echo("\n<!-- doing default nothing found -->\n");
		$tdb->insertIntoCallTrack(0, 0, $objInboundNumber->NumberID, '', "inbound SMS no match on $smsMessageBody", '',$smsMessageBody);

	} else {
		deal_with_children($objMatchThread, $objInboundNumber);

		$tdb->insertIntoCallTrack(0, $objMatchThread->ThreadID, $objInboundNumber->NumberID, '', "inbound $note ($objMatchThread->mp3Name): $smsMessageBody", '',$smsMessageBody);
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



function deal_with_thread($objThread, $objInboundNumber,$calltracktext,$objTwilioNumber) {
	#echo "<!--dealing with threadID $threadID -->";
	$dealWithChildren = 0;

	global $tdb;
	global $smsMessageBody;

	$ofInterest=0;
	$threadID = 0;
	echo "<!-- dealing with thread. tnumber is $objTwilioNumber->TwilioNumberID | $objThread->TwilioNumberID action type $objThread->ActionTypeID $objThread->mp3Name -->\n";
	if ($objThread->ActionTypeID == ActionType::$InboundSMSAction  && $objThread->TwilioNumberID == $objTwilioNumber->TwilioNumberID) {

		echo"<!-- found matching inbound SMS action $objThread->ThreadID with mp3 $objThread->mp3Name on $objTwilioNumber->TwilioNumberName  -->";
		#if the content of the text matches the mp3name field - OR the mp3name is blank, kick off the children.

		#this is a TODO...
		if (empty($objThread->mp3Name)) {
			$ofInterest=1;
			echo "<!-- matched because the filter is blanks.-->";
		} else {
			$posInString = strpos(strtolower(" $smsMessageBody"), strtolower("$objThread->mp3Name"));

			if ($posInString > 0) {
				$ofInterest = 1;
				echo"<!-- found $objThread->mp3Name in $smsMessageBody at $posInString -->";
			} else {
				$ofInterest=0;

				echo"<!-- DID NOT find $objThread->mp3Name in $smsMessageBody at $posInString -->";
			}
		}
		

	}


	return $ofInterest;
}

#end of function defs
?>
