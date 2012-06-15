<?php 

	header("Content-type: text/csv");
	header("Content-Disposition: attachment; filename=file.csv");
	header("Pragma: no-cache");
	header("Expires: 0");



$machinename =  gethostname();
        if (preg_match("/local/i",$machinename)) {
                $configfile = "/var/tmp/config.local";
        } else {
                $configfile = "/var/cache/timeline/config.local";
        }

         $ini_array = parse_ini_file($configfile);
$username = $ini_array['userID'];
$password = $ini_array['password'];


	
	$local_secret = $ini_array['sharedSecret'];
	$db_location = $ini_array['databasepath'];
	$base_url = $ini_array['phpServer'];
	$mp3_url = $ini_array['mp3Server'];
	$instance_name = $ini_array['instanceName'];
	
	$this_url = $base_url . "/timeline-monitor.php";
	

		
	#init database
	$db = new PDO('sqlite:'.$db_location);


	$result = $db->query('select CallTrack.IsOutbound, Thread.ThreadDescription,Number.Number, Thread.mp3Name, CallTrack.TrackTime, CallTrack.StatusText from Thread, Number, CallTrack where Thread.id = CallTrack.ThreadID and CallTrack.TrackNumberID = Number.NumberID order by CallTrack.TrackID desc');


        $rowarray = $result->fetchall(PDO::FETCH_ASSOC);

        foreach($rowarray as $row)
        {

                $inout = ($row['IsOutbound']!=0)?"OUTBOUND":"INBOUND";

                echo "$inout,$row[TrackTime],$row[ThreadDescription],'$row[Number]',$row[mp3Name],$row[StatusText]\n";
        }
?>


