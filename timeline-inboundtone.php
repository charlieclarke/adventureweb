<?php 
	
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    // make an associative array of callers we know, indexed by phone number
    // if the caller is known, then greet them by name
    // otherwise, consider them just another monkey

	$threadID = intval($_REQUEST['ThreadID']);
	$callTrackID = intval($_REQUEST['CallTrackID']);
	$digits = intval($_REQUEST['Digits']);
	$parentThreadID = intval($_REQUEST['ParentThreadID']);
	
	$additional_number_id = intval($_REQUEST['AdditionalNumberID']);

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
	$inboundDialToneAction = 10;
	

        echo "<!-- dbloc = " . $db_location . "-->";
	#update call track noting the digits...


	$tdb->update_calltrack_status($callTrackID, " got keypress: $digits");


	echo "<Response><Say>thank you for giving me $digits</Say></Response>";


	#get the children of the parent thread

	$objThread = $tdb->getThreadByThreadID($parentThreadID);
        
        $threadList = "";

	foreach($objThread->ChildThreads as $childID) {
		echo "<!--looking at child $childID\n-->";
		$objChild = $tdb->getThreadByThreadID($childID);
	
		echo "<!--got child $childID\n-->";
		if ($objChild->ActionTypeID == ActionType::$DialToneActionType) {
			echo "<!-- child matchs actiontype-->";

			#we need to deal with this child.

			deal_with_tone_thread($objChild,$digits, $additional_number_id);
			echo "<!-- finish deal with child matchs actiontype-->";
		}

	}
	echo "<!--DONE-->";


function deal_with_children($objThread, $additional_number_id) {

	global $tdb;

	global $numberID;
	
	echo("<!-- deal with children $childtext -->");
	#deal with children
	foreach($objThread->ChildThreads as $childID) {

		if ($childID > 0) {
			echo("<!-- found a child " . $childID . "-->");

			$objChild = $tdb->GetThreadByThreadID($childID);

			$freq = $objChild->Frequency;

			#todo: if the child is some kind of calltree filter, do the call tree filter mp3 / text, and
			#put the gather back to here...

			echo("<!-- child freq is " . $freq . "-->");

			$tdb->insertToTimeLineOffset($childID, $freq, $additional_number_id,'inserted on match tone');
			echo "<!-- done inserting to timeline-->";
			
		}
	}
}


function deal_with_tone_thread($objThread,$digits, $additional_number_id) {
	global $tdb;
	echo "<!--dealing with threadID $objThread->ThreadID-->";
	$dealWithChildren = 0;


	global $callTrackID;

	#if the content of the text matches the mp3name field - OR the mp3name is blank, kick off the children.

	#this is a TODO...
	if (empty($objThread->mp3Name)) {
		$dealWithChildren=1;
		echo "<!-- no filter so lets deal with children -->";
	} else {

		if ($objThread->mp3Name == $digits) {
			$dealWithChildren = 1;
		} else {
			$dealWithChildren=0;

			echo"<!-- did not find [$objThread->mp3Name] in [$digits]  -->";
		}
	}


	if ($dealWithChildren > 0) {
		#TODO: put insertion of children  into a subroutine
		$tdb->update_calltrack_status($callTrackID, " Matched $mp3name, add children.");

		echo"<!-- sort out kids -->";
		deal_with_children($objThread,$additional_number_id);


	}

}

#end of function defs
?>
