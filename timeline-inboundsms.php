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

        $db = new PDO('sqlite:'.$db_location);

	$inboundMp3Action = 6;
	$inboundTextAction = 5;
	$inboundSMSAction = 9;
	#see if we can find the number

        $sql = "SELECT NumberID, NumberDescription  FROM Number  WHERE Number = ?";
        $q = $db->prepare($sql);
        $q->execute(array($inboundnumber));


        $q->setFetchMode(PDO::FETCH_BOTH);

	$numberID = 0;
	$numberDescription='unknown';
        // fetch
        while($r = $q->fetch()){
          $numberID = $r['NumberID'];
          $numberDescription = $r['NumberDescription'];
        }


	#if we dont know the number - add it.
	if ($numberID == 0) {

		$sql = "INSERT into Number (Number, NumberDescription) values(?,?)";
		$q = $db->prepare($sql);
		$q->execute(array($inboundnumber,'unknown inbound number'));


		#and get the new numberID
		$sql = "SELECT NumberID, NumberDescription  FROM Number  WHERE Number = ?";
		$q = $db->prepare($sql);
		$q->execute(array($inboundnumber));

		$q->setFetchMode(PDO::FETCH_BOTH);

		$numberID = 0;
		$numberDescription='unknown';
		// fetch
		while($r = $q->fetch()){
		  $numberID = $r['NumberID'];
		  $numberDescription = $r['NumberDescription'];
		}
	}


	#get the DEFAULT thread
	
	
	$sql = "SELECT ThreadID from DefaultInboundThread where Type='SMS'";
        $q = $db->prepare($sql);
        $q->execute();


        $q->setFetchMode(PDO::FETCH_BOTH);

        $defaultThreadID = 0;
        // fetch
        while($r = $q->fetch()){
          $defaultThreadID = $r['ThreadID'];
        }

	#see if there are any inbound threads associated with this number

	$sql = "SELECT Thread.id, Thread.mp3Name, Thread.ActionType,Thread.ChildThreadID from Thread, Groups, Number, GroupNumber where Thread.DestNumber = Groups.GroupID and Groups.GroupID = GroupNumber.GNGroupID and Number.NumberID = GroupNumber.GNNumberID and Number.Number = ? and Thread.ActionType in (?,?) and Thread.id not in (?)  order by Thread.FrequencyMinutes";

	echo("<!--exec sql " . $sql . "-->");
	$q = $db->prepare($sql);
        $q->execute(array($inboundnumber, $inboundSMSAction, $inboundSMSAction,$defaultThreadID));

        $q->setFetchMode(PDO::FETCH_BOTH);
	
	$todoxml = "";
        while($r = $q->fetch()){
		$threadID = $r['id'];
		echo("<!-- got threadID of $r[id] -->"); 
		$defaultThreadID = 0;

		$actionTypeID = $r['ActionType'];
		$mp3Name = $r['mp3Name'];
		$childtext=$r['ChildThreadID'];

		deal_with_thread($threadID, $actionTypeID, $mp3Name, $childtext,"inbound SMS: $smsMessageBody",$numberID);

	}


	if ($defaultThreadID >0) {
		echo("<!-- doing default bahvious -->"); 
		#do default behaviour
		$sql = "SELECT Thread.id, Thread.mp3Name, Thread.ActionType,Thread.ChildThreadID from Thread where Thread.id  in (?)  order by Thread.FrequencyMinutes";

		echo("<!--exec sql " . $sql . "-->");
		$q = $db->prepare($sql);
		$q->execute(array($defaultThreadID));

		$q->setFetchMode(PDO::FETCH_BOTH);


		 while($r = $q->fetch()){
			$threadID = $r['id'];
			echo("<!-- got default threadID of $r[id] -->");

			$actionTypeID = $r['ActionType'];
			$mp3Name = $r['mp3Name'];
			$childtext=$r['ChildThreadID'];

			deal_with_thread($threadID, $actionTypeID, $mp3Name, $childtext,"inbound SMS: $smsMessageBody",$numberID);
		}

        }









#render the page
    // now great the caller
?>
<Response>
    <?php echo $todoxml ?>
</Response>

<?php
#function definitions

function deal_with_children($childtext) {

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

			 $sql = "INSERT INTO TimeLine (ThreadId, ActivityTime, Completed, CompletedTime, Description, Notes, AdditionalNumberID) values( $childID ,datetime('now','+$freq minutes'),0,NULL,'inserted on SMS',NULL,$numberID)";


			echo("<!-- sql is " . $sql . "-->");
			$count = $db->exec($sql);
			echo("<!-- sql done " . $count . "rows -->");
		}
	}
}

function insert_into_calltrack($threadID, $numberID, $comment)  {
	global $db;

	$sql = "INSERT INTO CallTrack (IsOutbound , ThreadID, TrackNumberID, TrackTime , TwilioID , TwilioFollowup , StatusText, InboundDetails ) values (0,?,?,DATETIME('now'),'',0,$comment,'')";
	$qq = $db->prepare($sql);
	$qq->execute(array($threadID, $numberID));
}


function deal_with_thread($threadID, $actionTypeID, $mp3Name, $childtext,$calltracktext,$numberID) {
	echo "<!--dealing with threadID $threadID-->";
	$dealWithChildren = 0;
	global $inboundSMSAction;
	global $smsMessageBody;
	global $db;


	$dealWithChildren = 0;
	if ($actionTypeID == $inboundSMSAction) {

		echo"<!-- found matching inbound SMS action -->";
		#if the content of the text matches the mp3name field - OR the mp3name is blank, kick off the children.

		#this is a TODO...
		if (empty($mp3Name)) {
			$dealWithChildren=1;
			echo "<!-- no filter so lets deal with children -->";
		} else {
			$posInString = strpos(" $smsMessageBody", "$mp3Name");
			echo"<!-- found $mp3Name in $smsMessageBody at $posInString -->";

			if ($posInString > 0) {
				$dealWithChildren = 1;
			} else {
				$dealWithChildren=0;

				echo"<!-- found $mp3Name in $smsMessageBody at $posInString -->";
			}
		}
		

	}

	if ($dealWithChildren > 0) {
		#TODO: put insertion of children  into a subroutine

		echo"<!-- sort out kids -->";
		deal_with_children($childtext);


	}

		#insert the thread into calltrack...
	$sql = "INSERT INTO CallTrack (IsOutbound , ThreadID, TrackNumberID, TrackTime , TwilioID , TwilioFollowup , StatusText, InboundDetails ) values (0,?,?,DATETIME('now'),'',0,'inbound SMS picked up: $calltracktext','')";
	$qq = $db->prepare($sql);
	$qq->execute(array($threadID, $numberID));
}

#end of function defs
?>
