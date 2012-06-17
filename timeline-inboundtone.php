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

        $db = new PDO('sqlite:'.$db_location);

	$inboundMp3Action = 6;
	$inboundTextAction = 5;
	$inboundSMSAction = 9;
	$inboundDialToneAction = 10;
	

        echo "<!-- dbloc = " . $db_location . "-->";
	#update call track noting the digits...


	update_calltrack_status($callTrackID, " got keypress: $digits");


	echo "<Response><Say>thank you for giving me $digits</Say></Response>";


	#get the children of the parent thread

	$sql = "SELECT ChildThreadID from Thread where id = $parentThreadID";

	echo("<!--exec sql " . $sql . "-->");
	$q = $db->prepare($sql);
        $q->execute(array());

        $q->setFetchMode(PDO::FETCH_BOTH);
        
        $threadList = "";
        while($r = $q->fetch()){

		$childtext = $r['ChildThreadID'];
	echo("<!--child test is $childtext-->");
		$childIDs = explode(',',$childtext);
		foreach($childIDs as $childID) {

			$childID = intval($childID);

			if ($childID > 0) {
				$threadList = $threadList . "$childID,";
			}
		}
	}
	$threadList = $threadList . "0";


	#get the child threads which are of type $inboundDialToneAction

	$sql = "SELECT Thread.id, Thread.mp3Name, Thread.ActionType,Thread.ChildThreadID from Thread where Thread.id in ($threadList) and Thread.ActionType=?";

		echo "<!-- tone thread is $threadID-->";
	echo("<!--exec sql " . $sql . "-->");
	$q = $db->prepare($sql);
        $q->execute(array($inboundDialToneAction));

        $q->setFetchMode(PDO::FETCH_BOTH);
	
	$todoxml = "";
        while($r = $q->fetch()){

		$threadID = $r['id'];
		$actionTypeID = $r['ActionType'];
		$mp3Name = $r['mp3Name'];
		$childtext=$r['ChildThreadID'];
		$additional_number_id = $r['AdditionalNumberID'];
		#todo: get additional number ID from calltrack

		echo "<!-- tone thread is $threadID - mp3 is $mp3Name deal wih-->";
		$additional_number_id = 0;
		deal_with_tone_thread($threadID, $mp3Name, $childtext,$digits, $additional_number_id);
		echo "<!-- dealt with tone thread-->";

	}
	echo "<!-- finished with  thread  $threadID-->";

function deal_with_children($childtext, $additional_number_id) {

	global $numberID;
	global $db;
	
	echo("<!-- deal with children $childtext -->");
	#deal with children
	$childIDs = explode(',',$childtext);
	foreach($childIDs as $childID) {

		$childID = intval($childID);

		if ($childID > 0) {
			echo("<!-- found a child " . $childID . "-->");

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
#id INTEGER PRIMARY KEY, ThreadId INTEGER, ActivityTime DATETIME, Completed INTEGER, CompletedTime DATETIME, Description TEXT, Notes TEXT, AdditionalNumberID INTEGER

			 $sql = "INSERT INTO TimeLine (ThreadId, ActivityTime, Completed, CompletedTime, Description, Notes, AdditionalNumberID) values( $childID ,datetime('now','+$freq minutes'),0,NULL,'inserted on match tone',NULL,$additional_number_id)";


			echo("<!-- sql is " . $sql . "-->");
			$count = $db->exec($sql);
			echo("<!-- sql done " . $count . "rows -->");
		}
	}
}


function update_calltrack_status($callTrackID, $comment) {


	global $db;

	$sql = "UPDATE CallTrack set StatusText = StatusText || ' $comment' where TrackID = $callTrackID";

        echo "<!-- sql is $sql-->";
        $q = $db->prepare($sql);
        $q->execute(array());


}
function insert_into_calltrack($threadID, $numberID, $comment)  {
	global $db;

	$sql = "INSERT INTO CallTrack (IsOutbound , ThreadID, TrackNumberID, TrackTime , TwilioID , TwilioFollowup , StatusText, InboundDetails ) values (0,?,?,DATETIME('now'),'',0,$comment,'')";
	$qq = $db->prepare($sql);
	$qq->execute(array($threadID, $numberID));
}


function deal_with_tone_thread($threadID, $mp3Name, $childtext,$digits, $additional_number_id) {
	global $db;
	echo "<!--dealing with threadID $threadID-->";
	$dealWithChildren = 0;


	global $callTrackID;

	#if the content of the text matches the mp3name field - OR the mp3name is blank, kick off the children.

	#this is a TODO...
	if (empty($mp3Name)) {
		$dealWithChildren=1;
		echo "<!-- no filter so lets deal with children -->";
	} else {

		if ($mp3Name == $digits) {
			$dealWithChildren = 1;
		} else {
			$dealWithChildren=0;

			echo"<!-- did not find [$mp3Name] in [$digits]  -->";
		}
	}

		


	if ($dealWithChildren > 0) {
		#TODO: put insertion of children  into a subroutine
		update_calltrack_status($callTrackID, " Matched $mp3name, add children.");

		echo"<!-- sort out kids -->";
		deal_with_children($childtext,$additional_number_id);


	}

		#insert the thread into calltrack...
}

#end of function defs
?>
