<?php 
	
    header("content-type: application/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    // here we are going to return useful values to the SIMCLient.
	//the first one simply returns the most recent message
	//that we need to show. 

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



	$inboundMp3Action = 6;
	$inboundTextAction = 5;
	$inboundSMSAction = 9;
	$inboundSIMAction = 13;
	#see if we can find the number

	#bad security but good testing - this doesnt protects against CSRF

	$inboundGUID = $guidCookie;

	echo("<!--got guidCookie $guidCookie-->\n");
	if ($guidCookie == $inboundGUID) {
		//we have at least the correct cookie.
		//lets get the number

		$objInboundNumber= $tdb->getPhoneNumberByGuid($guidCookie);	
		echo("<!--got inboundNumber $objInboundNumber->NumberID-->\n");


	} else {
		exit("unknown inbound number");
	}

	if ($_GET["action"] == "GETNEW") {

		#get the messages for this number which have not yet been reeived

		$messageArray = $tdb->getSIMMessages($objInboundNumber->NumberID, $guidCookie);
		echo("<Response>\n");

		if (count($messageArray) > 0) {
			$txt = $messageArray[0]->SIMTxt;
			$id = $messageArray[0]->SIMID;
			echo("<Message><SIMID>$id</SIMID>\n<SIMTxt>$txt</SIMTxt>\n</Message>\n");

		} else {
			#print nothing...
		}
		echo("</Response>\n");
	} 
	else if ($_GET["action"] == "MARKRCVD"){
		$SIMID = $_GET["SIMID"];
		echo("<!--in markrcvd - with simid of $SIMID-->\n");
		//do some input validation!!!

		$tdb->markSIMMessageRcvd($SIMID, $inboundGUID);
		echo("<Response>non</Response>\n");


	} else if ($_GET["action"] == "SOW") {
		$messageArray = $tdb->getSIMMessages($objInboundNumber->NumberID, $guidCookie,1);
                echo("<Response>\n");

		$txt="";
		$id = "";
                foreach($messageArray as $m) {
                        $txt = $m->SIMTxt;
                        $id = $m->SIMID;

                } 
		if ($txt != "") {
			echo("<Message><SIMID>$id</SIMID>\n<SIMTxt>$txt</SIMTxt>\n</Message>\n");
		}
                echo("</Response>\n");
		


	} else if ($_GET["action"] == "SUPRESS") {
                $tdb->supressSIMMessages($objInboundNumber->NumberID, $guidCookie,1);
                echo("<Response>\n");

                echo("</Response>\n");



        }


#render the page
    // now great the caller

?>

