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


	#insert into CallTrack

	$sql = "INSERT INTO CallTrack (IsOutbound , ThreadID, TrackNumberID, TrackTime , TwilioID , TwilioFollowup , StatusText, InboundDetails ) values (0,?,?,DATETIME('now'),'',0,'inbound call answered','')";
	$q = $db->prepare($sql);
        $q->execute(array($defaultThreadID, $numberID));



#render the page
    // now greet the caller
?>
<Response>
    <Say voice="woman">Hello <?php echo $numberDescription ?>.</Say>
</Response>


