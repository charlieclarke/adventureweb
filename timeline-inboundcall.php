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

        $db = new PDO('sqlite:'.$db_location);

	$inboundMp3Action = 6;
	$inboundTextAction = 5;
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
	
	
	$sql = "SELECT ThreadID from DefaultInboundThread";
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
        $q->execute(array($inboundnumber, $inboundMp3Action, $inboundTextAction,$defaultThreadID));

        $q->setFetchMode(PDO::FETCH_BOTH);
	
	$todoxml = "";
        while($r = $q->fetch()){
		$threadID = $r['id'];
		echo("<!-- got threadID of $r[id] -->"); 
		$defaultThreadID = 0;

		$actionTypeID = $r['ActionType'];
		$mp3Name = $r['mp3Name'];
		$childThread=$r['ChildThreadID'];

		if ($actionTypeID == $inboundMp3Action) {
			#play mp3

			$todoxml = $todoxml . "<Play>$mp3Server$mp3Name</Play>";

		} else if ($actionTypeID == $inboundTextAction) {

			$saytext = $mp3Name;

			$saytext = str_replace("[InboundName]",$numberDescription, $saytext);
			$todoxml = $todoxml . "<Say voice='woman'>$saytext.</Say>";

			#play text
		}

		#deal with children

		#insert the thread into calltrack...
		$sql = "INSERT INTO CallTrack (IsOutbound , ThreadID, TrackNumberID, TrackTime , TwilioID , TwilioFollowup , StatusText, InboundDetails ) values (0,?,?,DATETIME('now'),'',0,'inbound call answered','')";
		$qq = $db->prepare($sql);
		$qq->execute(array($threadID, $numberID));


	} 

	if ($defaultThreadID >0) {
		#do default behaviour
		$sql = "SELECT Thread.id, Thread.mp3Name, Thread.ActionType,Thread.ChildThreadID from Thread where Thread.id  in (?)  order by Thread.FrequencyMinutes";

		echo("<!--exec sql " . $sql . "-->");
		$q = $db->prepare($sql);
		$q->execute(array($defaultThreadID));

		$q->setFetchMode(PDO::FETCH_BOTH);

		$todoxml = "";
		while($r = $q->fetch()){
			$threadID = $r['id'];
			echo("<!-- got threadID of $r[id] -->"); 
			$defaultThreadID = 0;

			$actionTypeID = $r['ActionType'];
			$mp3Name = $r['mp3Name'];
			$childThread=$r['ChildThreadID'];

			if ($actionTypeID == $inboundMp3Action) {
				#play mp3

				$todoxml = $todoxml . "<Play>$mp3Server$mp3Name</Play>";

			} else if ($actionTypeID == $inboundTextAction) {

				$saytext = $mp3Name;

				$saytext = str_replace("[InboundName]",$numberDescription, $saytext);
				$todoxml = $todoxml . "<Say voice='woman'>$saytext.</Say>";

				#play text
			}

			#deal with children

			#insert the thread into calltrack...
			$sql = "INSERT INTO CallTrack (IsOutbound , ThreadID, TrackNumberID, TrackTime , TwilioID , TwilioFollowup , StatusText, InboundDetails ) values (0,?,?,DATETIME('now'),'',0,'inbound call answered default behaviour','')";
			$qq = $db->prepare($sql);
			$qq->execute(array($threadID, $numberID));


		}

	}







#render the page
    // now greet the caller
?>
<Response>
    <?php echo $todoxml ?>
</Response>


