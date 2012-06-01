<?php 
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

	$threadID = $_GET["threadID"];
	$secret = $_GET["secret"];

	$local_secret = chop(file_get_contents ("/var/cache/timeline/sharedsecret"));

	#if ($secret != $local_secret) {
#		header("HTTP/1.0 401 Unauthorized");
#		exit();
#	}
		
	echo("<!-- playing mp3 associated with threadID " . $threadID . "-->");	
	$mp3name = "blank.mp3";

	$db = new PDO('sqlite:/var/cache/timeline/timeline.db');

	$sql = "SELECT mp3Name FROM Thread WHERE id = ?";
	$q = $db->prepare($sql);
	$q->execute(array($threadID));


	$q->setFetchMode(PDO::FETCH_BOTH);

	// fetch
	while($r = $q->fetch()){
	  $mp3name = $r['mp3Name'];
	}


	#does the thread have a CHILD - if so - spawn it at <frequency> minutes time - note freqeucnt is the freq of the CHILD not the parent.
	$childID = -1;
	$sql = "SELECT ChildThreadID FROM Thread WHERE id = ? and ChildThreadID is not NULL and ChildThreadID > 0";
        $q = $db->prepare($sql);
        $q->execute(array($threadID));


        $q->setFetchMode(PDO::FETCH_BOTH);

        // fetch
        while($r = $q->fetch()){
          $childID = $r['ChildThreadID'];
        }

	if ($childID > 0) {
		echo("<!-- found a child " . $childID . "-->");
		#TODO split to multiple children eg SMS and a call

		$freq=-1;
		
		$sql = "SELECT FrequencyMinutes FROM Thread WHERE id = ? ";
		$q = $db->prepare($sql);
		$q->execute(array($childID));


		$q->setFetchMode(PDO::FETCH_BOTH);

		// fetch
		while($r = $q->fetch()){
		  $freq = $r['FrequencyMinutes'];
		}
		echo("<!-- child freq is " . $freq . "-->");
		#now we insert the new task to the timeline at now + freq minutes.

		 $sql = "INSERT INTO TimeLine select NULL," . $childID . ",datetime('now','+".$freq." minutes'),0,NULL,'inserted on call',NULL";
               
                #$qq = $db->prepare($sql);


                echo("<!-- sql is " . $sql . "-->");
                #$qq->execute();
		$count = $db->exec($sql);
		echo("<!-- sql done " . $count . "rows -->");
	}


	$db = null;



?>
<Response>
<Pause length="2"/>
<Play>http://ec2-176-34-195-123.eu-west-1.compute.amazonaws.com/mp3/<?php echo($mp3name); ?></Play>
</Response>


