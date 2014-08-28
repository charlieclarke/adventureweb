<html>
<head>
<link rel='stylesheet' type='text/css' href='default.css' />
<title>call monitoring page</title>
</head>
<body>
<?php
$machinename =  gethostname();
        if (preg_match("/local/i",$machinename) || preg_match("/wifi/i",$machinename) ) {
                $configfile = "/var/tmp/config.local";
        } else {
                $configfile = "/var/cache/timeline/config.local";
        }

         $ini_array = parse_ini_file($configfile);
?>
<?php
$username = $ini_array['userID'];
$password = $ini_array['password'];




echo "<!-- u:$username p:$password-->";

if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Text to send if user hits Cancel button';
    exit;
} else {
	$login=0;
	if ($_SERVER['PHP_AUTH_USER'] == $username) {
		if ($_SERVER['PHP_AUTH_PW'] == $password) {
			#all is good
			$login=1;
		}
	}
	if ($login == 0) {
		header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Text to send if user hits Cancel button';
    exit;
	}
}
?>
<?php 
    header("content-type: text/html");

	$secret = $_GET["secret"];
	$triggerAction = $_GET['TRIGGER'];
	$globalAction = $_GET['GLOBAL'];
	$crudAction = $_GET['CRUD'];
	$threadID = intval($_GET['ThreadID']);

	

	
	$local_secret = $ini_array['sharedSecret'];
	$db_location = $ini_array['databasepath'];
	$base_url = $ini_array['phpServer'];
	$mp3_url = $ini_array['mp3Server'];
	$instance_name = $ini_array['instanceName'];
	
	$this_url = $base_url . "/timeline-monitor.php";
	

	require_once('timeline-lib.php');

	$tdb = new DB($db_location);
	$tdb->init();

	#if ($secret != $local_secret) {
#		header("HTTP/1.0 401 Unauthorized");
#		exit();
#	}
		
	#init database
	$db = new PDO('sqlite:'.$db_location);


	#perform actions etc.
	if ($crudAction == 'UPDATEDEFAULTINBOUNDTHREADSMS') {

                $sql = "update DefaultInboundThread set ThreadID = ? where Type='SMS'";

                $st = $db->prepare($sql);
                $st->execute(array($threadID));

        }

	if ($crudAction == 'UPDATEDEFAULTINBOUNDTHREADSIM') {

                $sql = "update DefaultInboundThread set ThreadID = ? where Type='SIM'";

                $st = $db->prepare($sql);
                $st->execute(array($threadID));

        }
	if ($crudAction == 'NAMETONUMBER') {


		$sql = "update Number set NumberDescription = ? where NumberID = ?";
		$st = $db->prepare($sql);
		$name = $_GET['Name'];
		$numberID = intval($_GET['NumberID']);
		echo "<!--making numberID $numberID be $name-->\n";
		$st->execute(array($name, $numberID));

	}

	if ($crudAction == 'UPDATEDEFAULTINBOUNDTHREADCALL') {

                $sql = "update DefaultInboundThread set ThreadID = ? where Type='CALL'";

                $st = $db->prepare($sql);
                $st->execute(array($threadID));

        }



	if ($crudAction == 'CREATENUMBER') {
		$newNumber = $_GET["NewNumber"];
		$newNumberDescription = $_GET["NewNumberDescription"];

		$sql = "INSERT INTO Number (Number, NumberDescription) values (?,?)";

		$st = $db->prepare($sql);
		$st->execute(array($newNumber,$newNumberDescription));

	}
	if ($crudAction == 'DELETENUMBER') {


		$numberID = intval($_GET["NumberID"]);
		 $sql = "DELETE FROM Number where NumberID = ?"; 

		$st = $db->prepare($sql);
		$st->execute(array($numberID));
	


	}
	if ($crudAction == 'EDITTHREAD') {
		$editThreadID = intval($_GET["ThreadID"]);
	} else {
		$editThreadID = 0;
	}
	if ($crudAction == 'CREATETHREAD') {
		$newThreadID = intval($_GET["NewThreadID"]);
		$newTNumberID = intval($_GET["NewTNumberID"]);
		$newThreadDescription = $_GET["NewThreadDesc"];
		$newActionID = intval($_GET["NewActionID"]);
		$newThreadNumber = $_GET["NewThreadDestNumber"];
		$newThreadFrequency = $_GET["NewFrequency"];
		$newThreadMp3 = $_GET["NewMp3Name"];
		$newChildThreadID_list =$_GET["NewChildThreadID"];
		$newStartHour = intval($_GET["NewStartTimeHours"]);
		$newStopHour = intval($_GET["NewStopTimeHours"]);
		$newStartMinute = intval($_GET["NewStartTimeMinutes"]);
		$newStopMinute = intval($_GET["NewStopTimeMinutes"]);

		$newChildThreadID = implode(',',$newChildThreadID_list);


		#removing < and >  input validation - and also doing output encoding when rendering.
		#this is so we can have HTML in the questions...
		$newThreadMp3 = preg_replace('/[^a-zA-Z0-9?_ @#%\[\]\.\(\)%&-<>]/s', '', $newThreadMp3);

		if ($newChildThreadID == "") {
			$newChildThreadID = 0;
		}

		if ($newTNumberID > 0) {

			 $sql = "DELETE FROM Thread where id = ?"; 

			$st = $db->prepare($sql);
			$st->execute(array($newThreadID));

			$sql = "INSERT INTO Thread (id, TNumberID, ThreadDescription, ActionType, DestNumber, FrequencyMinutes, mp3Name, ChildThreadID,StartTimeHour, StopTimeHour,StartTimeMinute, StopTimeMinute) values (?,?,?,?,?,?,?,?,?,?,?,?)";

			$st = $db->prepare($sql);
			$st->execute(array($newThreadID,$newTNumberID,$newThreadDescription, $newActionID, $newThreadNumber, $newThreadFrequency, $newThreadMp3, $newChildThreadID,$newStartHour, $newStopHour, $newStartMinute, $newStopMinute));
		} else {
			#not a new thread - actualy making them active or inactive.

			$activeIDs = $_GET['active_grp'];
			  if(empty($activeIDs))
			  {
			    echo("You didn't select any active ones.");
			  } 
			  else
			  {
			    $N = count($activeIDs);
				$sep="";
				$list = "(";
				foreach($activeIDs as $aid){
					if (preg_match("/active_(\d+)/",$aid,$matches) > 0) {
					$list = $list . $sep . $matches[1];
					$sep = ",";

					}
			 
				}
				$list = $list . ")";

				echo("You selected $N ids: $list ");
				$sql = "update Thread set Active = 1 where ID in " . $list;
				$st = $db->prepare($sql);
				$st->execute();
				$sql = "update Thread set Active = 0 where ID not in " . $list;
				$st = $db->prepare($sql);
				$st->execute();
			}

		}

	}
	if ($crudAction == 'DELETETHREAD') {

		#first - delete all current items in the timeline with this threadID
		#then - delerte the thread itself

		$threadID = intval($_GET["ThreadID"]);
		 $sql = "DELETE FROM TimeLine where ThreadID = ?"; 

		$st = $db->prepare($sql);
		$st->execute(array($threadID));
	
		 $sql = "DELETE FROM Thread where id = ?"; 

		$st = $db->prepare($sql);
		$st->execute(array($threadID));


	}
	if ($globalAction == 'KILL') {
		 $sql = "DELETE FROM TimeLine"; 

		echo("<!-- sql is " . $sql . "-->");
		$count = $db->exec($sql);
		echo("<!-- sql done " . $count . "rows -->");


	}

	if ($globalAction == 'TRUNCATE') {
                #grab current timeline and whack it into a file

                $fname = "/var/tmp/timeline_dump_" . time();


                $fh = fopen($fname, 'w');


                $result = $db->query("SELECT * from CallTrack");
                $rowarray = $result->fetchall(PDO::FETCH_ASSOC);
                foreach($rowarray as $row) {

                        foreach($row as $col) {
                                fwrite($fh, $col);
                                fwrite($fh,',');
                        }
                        fwrite($fh,"\n");

                }
                fclose($fh);


                #and then delete
                 $sql = "DELETE FROM CallTrack where TrackID not in (select TrackID from CallTrack order by TrackID desc LIMIT 10)";

                echo("<!-- sql is " . $sql . "-->");
                $count = $db->exec($sql);
                echo("<!-- sql done " . $count . "rows -->");


        }

	#triggers of SEND

	
		echo "<!--pre  kick off number triggeraction = $triggerAction-->\n";
	if ($triggerAction == 'KICKOFFNUMBER') {
		echo "<!-- kick off number-->\n";
                $triggerDate= $_GET["INSERTTIME"];
		$additionalNumberID = intval($_GET['AdditionalNumberID']);

                if ($threadID > 0) {
                        #we have a valid kick to insert
			echo "<!-- kick off numbea: about to insert to timelinerfor add number $additionalNumberID ->\n";
			$tdb->insertToTimeLineTime($threadID, $triggerDate, $additionalNumberID,'send from monitor page'); 
	
			echo "<!-- kick off numbea: insertrd into -->\n";

                }
        }

	if ($triggerAction == 'KICKOFFGROUP') {
                $triggerDate= $_GET["INSERTTIME"];
                $groupID = intval($_GET['GroupID']);
		$threadID = intval($_GET['ThreadID']);

		echo "<!-- kick off group: threadID = $threadID groupID = $groupID-->\n";
                if ($threadID > 0) {
                        #we have a valid kick to insert
			#get all numbers in the group.

			echo "<--abut to get  numbers array-->\n";
			$objNumberArray = $tdb->getPhoneNumbersByGroupID($groupID);
			echo "<--got numbers array-->\n";
			foreach($objNumberArray as $objNumber) {
				$tdb->insertToTimeLineTime($threadID, $triggerDate, $objNumber->NumberID,"sent from monitor page as part of group $groupID");    
			}

                }
        }

	#triggers from tthread table



	if ($triggerAction == 'INSERT') {
		if ($threadID > 0) {
			#we have a valid trigger to insert

			$sql = "INSERT INTO TimeLine select NULL," . $threadID . ",datetime('now'),0,NULL,'triggerd from monitor page',NULL,0";

			echo("<!-- sql is " . $sql . "-->");
			$count = $db->exec($sql);
			echo("<!-- sql done " . $count . "rows -->");


		}
	}

	if ($triggerAction == 'INSERTTIME') {
		$triggerDate= $_GET["INSERTTIME"];

                if ($threadID > 0) {
                        #we have a valid trigger to insert

                        $sql = "INSERT INTO TimeLine select NULL," . $threadID . ",'$triggerDate',0,NULL,'timetriggered from monitoring page',NULL,NULL";

                        echo("<!-- sql is " . $sql . "-->");
                        $count = $db->exec($sql);
                        echo("<!-- sql done " . $count . "rows -->");


                }
        }			
	if ($triggerAction == 'REMOVE') {
		if ($threadID > 0) {
			#we have a valid trigger to insert

			$sql = "DELETE from TimeLine where ThreadID = " . $threadID ;

			echo("<!-- sql is " . $sql . "-->");
			$count = $db->exec($sql);
			echo("<!-- sql done " . $count . "rows -->");


		}
	}

	#get last heartbeat from db

			
	echo "<!--about ot get heartbeat --!>";
	$result = $db->query("SELECT HeartBeatTime, strftime('%s','now') - strftime('%s',HeartBeatTime) as LastHeartBeatAgo, DATETIME('now') as CurrentDBTime FROM HeartBeat where HeartBeatName='LastTimeLine'");

	echo "<!--got heartbeat --!>";
	echo "<!--got heartbeat2 --!>";
        $rowarray = $result->fetchall(PDO::FETCH_ASSOC);


	echo "<!--got heartbeat 2--!>";
	$lastHeartBeat = 'never';
	$lastHeartBeatAgo = -100;
	$currentDBTime = '';
        foreach($rowarray as $row)
        {

                $lastHeartBeat = $row['HeartBeatTime'];
                $lastHeartBeatAgo = $row['LastHeartBeatAgo'];
                $currentDBTime = $row['CurrentDBTime'];
	} 

	if ($lastHeartBeatAgo < 2) {
		$heartBeatText = "TimeLine Active and OK - $lastHeartBeat";
	} else {
		$heartBeatText = "TimeLine Appears Down - $lastHeartBeat";
	}

	#render page

	#top menu bar
	echo("<div class='menuBar'><a href=$base_url/timeline-monitor.php>Monitor and Manager Threads</a>&nbsp;|&nbsp;<a href=$base_url/timeline-groups.php>Manage Numbers and Groups</a>&nbsp;|&nbsp;<a href=$base_url/timeline-twilio.php>Manage Twilio Account</a>&nbsp;|&nbsp;<a href=$base_url/timeline-bulk.php>Manage Bulk Agents</a>&nbsp;|&nbsp;<a href=$base_url/timeline-stash.php>STASH Console</a>&nbsp;|&nbsp;$instance_name&nbsp|&nbsp$heartBeatText</div>");
	echo("<br><br>");


	echo("<form action='" . $this_page . "' method='get'>");


	echo("<br><br>");
	 echo("<div class='tableTitle'>List of mp3s</div><br>");



	$dirlist = file_get_contents($mp3_url);

	preg_match_all("(\"\w+\.mp3\")", $dirlist, $out, PREG_PATTERN_ORDER); 
	foreach($out[0] as $mp3) {
		echo($mp3 . ", ");

	}

	echo("<br><br>");
	echo("Insert Thread at a specific Time onto TimeLine: Time in GMT to kick off insert. Format is [YYYY-MM-DD hh:mm:ss]");
	echo("<input name='INSERTTIME' size=23 value='$currentDBTime'>");

	echo "<input name='TRIGGER' value='INSERTTIME' type='hidden'><select  style='width:100px;margin:5px 0 5px 0;' name='ThreadID'>";

                $result = $db->query("SELECT * from Thread");
                $rowarray = $result->fetchall(PDO::FETCH_ASSOC);
                foreach($rowarray as $row) {

                        echo "<option value='$row[id]'>$row[id] ($row[ThreadDescription])</option>";
                }

        echo "</select><input type='submit' value='trigger at time'>";



	echo("</form>");


	#SEND thread

	echo("<form action='" . $this_page . "' method='get'>");
        echo("Send KickOff Thread to a number at a specific Time onto TimeLine: Time in GMT to kick off insert.");
        echo("<input name='INSERTTIME' size=23 value='$currentDBTime'>");

        echo "<input name='TRIGGER' value='KICKOFFNUMBER' type='hidden'><select  style='width:100px;margin:5px 0 5px 0;' name='ThreadID'>";

	$atID=ActionType::$KickOffActionType;
                $result = $db->query("SELECT * from Thread where ActionType=$atID");
                $rowarray = $result->fetchall(PDO::FETCH_ASSOC);
                foreach($rowarray as $row) {

                        echo "<option value='$row[id]'>$row[id] ($row[ThreadDescription])</option>";
                }

        echo "</select>";
	echo "<select  style='width:100px;margin:5px 0 5px 0;' name='AdditionalNumberID'>";
        
                $result = $db->query("SELECT * from Number where NumberID>0");
                $rowarray = $result->fetchall(PDO::FETCH_ASSOC);
                foreach($rowarray as $row) {

                        echo "<option value='$row[NumberID]'>$row[id] ($row[NumberDescription])</option>";
                }

        echo "</select>";
       echo "<input type='submit' value='trigger at time'>";
	echo "</form>";
	echo "<br>";

	echo("<form action='" . $this_page . "' method='get'>");        echo("Send KickOff Thread to a GROUP  at a specific Time onto TimeLine: Time in GMT to kick off insert.");
        echo("<input name='INSERTTIME' size=23 value='$currentDBTime'>");

        echo "<input name='TRIGGER' value='KICKOFFGROUP' type='hidden'><select  style='width:100px;margin:5px 0 5px 0;' name='ThreadID'>";

        $atID=ActionType::$KickOffActionType;
                $result = $db->query("SELECT * from Thread where ActionType=$atID");
                $rowarray = $result->fetchall(PDO::FETCH_ASSOC);
                foreach($rowarray as $row) {

                        echo "<option value='$row[id]'>$row[id] ($row[ThreadDescription])</option>";
                }
         
        echo "</select>";
        echo "<select  style='width:100px;margin:5px 0 5px 0;' name='GroupID'>";
        
                $result = $db->query("SELECT * from Groups where GroupID>0");
                $rowarray = $result->fetchall(PDO::FETCH_ASSOC);
                foreach($rowarray as $row) {

                        echo "<option value='$row[GroupID]'>$row[id] ($row[GroupName])</option>";
                }
    
        echo "</select>";
       echo "<input type='submit' value='trigger at time'>";
        echo "<br>";


	echo("</form>");

	

	#default inbound thread
	echo("<form>");
	echo("Set the Default Inbound SMS Thread:");
	echo "<input name='CRUD' value='UPDATEDEFAULTINBOUNDTHREADSMS' type='hidden'><select  style='width:100px;margin:5px 0 5px 0;' name='ThreadID'>";

                $result = $db->query("SELECT * from Thread where id in (Select ThreadID from DefaultInboundThread where Type='SMS')");
                $rowarray = $result->fetchall(PDO::FETCH_ASSOC);
                foreach($rowarray as $row) {

                        echo "<option value='$row[id]'>CURRENT: $row[id] ($row[ThreadDescription])</option>";
                }
                $result = $db->query("SELECT * from Thread");
                $rowarray = $result->fetchall(PDO::FETCH_ASSOC);
                foreach($rowarray as $row) {

                        echo "<option value='$row[id]'>$row[id] ($row[ThreadDescription])</option>";
                }

        echo "</select><input type='submit' value='set'>";

	echo "</form>";

	echo("<form>");
        echo("Set the Default Inbound Call Thread:");
        echo "<input name='CRUD' value='UPDATEDEFAULTINBOUNDTHREADCALL' type='hidden'><select  style='width:100px;margin:5px 0 5px 0;' name='ThreadID'>";

                $result = $db->query("SELECT * from Thread where id in (Select ThreadID from DefaultInboundThread where Type='CALL')");
                $rowarray = $result->fetchall(PDO::FETCH_ASSOC);
                foreach($rowarray as $row) {

                        echo "<option value='$row[id]'>CURRENT: $row[id] ($row[ThreadDescription])</option>";
                }
                $result = $db->query("SELECT * from Thread");
                $rowarray = $result->fetchall(PDO::FETCH_ASSOC);
                foreach($rowarray as $row) {

                        echo "<option value='$row[id]'>$row[id] ($row[ThreadDescription])</option>";
                }

        echo "</select><input type='submit' value='set'>";

        echo "</form>";
	
	echo("<form>");
        echo("Set the Default Inbound SIM / Web Thread:");
        echo "<input name='CRUD' value='UPDATEDEFAULTINBOUNDTHREADSIM' type='hidden'><select  style='width:100px;margin:5px 0 5px 0;' name='ThreadID'>";

                $result = $db->query("SELECT * from Thread where id in (Select ThreadID from DefaultInboundThread where Type='SIM')");
                $rowarray = $result->fetchall(PDO::FETCH_ASSOC);
                foreach($rowarray as $row) {

                        echo "<option value='$row[id]'>CURRENT: $row[id] ($row[ThreadDescription])</option>";
                }
                $result = $db->query("SELECT * from Thread");
                $rowarray = $result->fetchall(PDO::FETCH_ASSOC);
                foreach($rowarray as $row) {

                        echo "<option value='$row[id]'>$row[id] ($row[ThreadDescription])</option>";
                }

        echo "</select><input type='submit' value='set'>";

        echo "</form>";

	echo "<form action='" . $this_page . "' method='get'><table>";


	

	$result = $db->query('SELECT * FROM Thread,Action, Groups, TNumber where Thread.DestNumber = Groups.GroupID and Thread.ActionType = Action.ActionTypeID and Thread.TNumberID = TNumber.TNumberID');

	$tablehead = "<tr><th>Active</th><th>ID</th><th >Twilio Number</th><th>Description</th><th>Type</th><th>Phone Number Group</th><th>MP3 / message</th><th>Repeat Minutes</th><th>ChildThreadID</th><th>Time Range</th><th>Trigger</th></tr>";
	echo $tablehead;

	$tablebody = "";
	$rowarray = $result->fetchall(PDO::FETCH_ASSOC);
	$maxID = 0;	
//echo "<br><br>";
	
	$default_thread_description='thread description';
	$default_action_type=1;
	$default_twilio_number=0;
	$default_group_id = 0;
	$default_mp3_name = 'mp3 name / text';
	$default_frequency_minutes = 0;
	$default_child_thread_id_1 = 0;
	$default_child_thread_id_2 = 0;
	$default_start_time_hour = 0;
	$default_start_time_minute = 0;
	$default_stop_time_hour = 23;
	$default_stop_time_minute = 59;
	
	$rownum=0;

	$sortcolumn = "id";
	$sortdir = "asc";
	$sortdir = "desc";

	$keyarray = array();
	
	foreach ($rowarray as $key => $row)
	{
	    $keyarray[$key] = $row[$sortcolumn];
	}

	array_multisort($keyarray, $sortdir == 'asc' ? SORT_ASC :SORT_DESC, $rowarray);

	foreach($rowarray as $row) //loop for rendering each row
	{
		$rowstyle = (++$rownum % 2)==0?"d0":"d1";
		
		$tablebody .= "<tr class='" .$rowstyle . "'>";

		$checked = $row['Active'] == 1?'checked':'';

		$checkboxcode = "<input type='checkbox' name='active_grp[]' value='active_$row[id]' $checked/>";


		$encodedMP3Name = htmlspecialchars($row['mp3Name']);
		$insertLink = ($row['ActionType']==ActionType::$KickOffActionType)?"insert":"<a href='$this_url?secret=$secret_local&TRIGGER=INSERT&ThreadID=$row[id]'>insert</a>";
		$tablebody .= "<td>$checkboxcode</td><td>$row[id]</td><td>$row[TNumberID] $row[TNumberName]</td><td>$row[ThreadDescription]</td><td>$row[ActionType] ($row[ActionName])</td><td> $row[GroupName]</td><td>$encodedMP3Name</td><td>$row[MinutesBeforeText]$row[FrequencyMinutes]$row[MinutesAfterText]</td><td>$row[ChildThreadID]</td><td>$row[StartTimeHour]:$row[StartTimeMinute] -&gt; $row[StopTimeHour]:$row[StopTimeMinute]</td><td>$insertLink|<a href='$this_url?secret=$secret_local&TRIGGER=REMOVE&ThreadID=$row[id]'>remove</a>|<a href='$this_url?secret=$secret_local&CRUD=DELETETHREAD&ThreadID=$row[id]'>delete</a>|<a href='$this_url?secret=$secret_local&CRUD=EDITTHREAD&ThreadID=$row[id]'>edit</a></td>";
		$tablebody .= "</tr>";
		$maxID = max($maxID,$row[id]);


		#capture details if we are editing...
		if ($editThreadID == $row['id']) {

			$default_thread_description=$row['ThreadDescription'];
			$default_twilio_number=$row['TNumberID'];
			$default_action_type=$row['ActionType'];
			$default_group_id = $row[GroupID];
			$default_mp3_name = $row['mp3Name'];
			$default_frequency_minutes = $row['FrequencyMinutes'];
			$default_child_thread_id = explode(',',$row['ChildThreadID']);
			$default_start_time_hour = $row['StartTimeHour'];
			$default_start_time_minute = $row['StartTimeMinute'];
			$default_stop_time_hour = $row['StopTimeHour'];
			$default_stop_time_minute = $row['StopTimeMinute'];
			


		} 
	} //end of loop for rentering each row
	$rowstyle = (($maxID+1) % 2)==0?"d0":"d1";

	if ($editThreadID > 0) {
		$new_thread_id = $editThreadID;
	} else {
		$new_thread_id = $maxID + 1;
	}
	
	echo "<tr class='" .$rowstyle . "'>";
	echo "<input type='hidden' name='CRUD' value='CREATETHREAD'/>";
	echo "<input type='hidden' name='secret' value='" . $local_secret . "'/>";
	echo "<td></td><td><input type='text' size=2 name='NewThreadID' value='$new_thread_id'/></td>";



	echo "<td><select name='NewTNumberID'>";

		$selected = (0 == $default_twilio_number)?'selected':'';
		echo "<option $selected value='0'>Choose a Number</option>";
                $result = $db->query("SELECT * from TNumber");
                $rowarray = $result->fetchall(PDO::FETCH_ASSOC);
                foreach($rowarray as $row) {

                        $selected = ($row['TNumberID'] == $default_twilio_number)?'selected':'';

                        echo "<option $selected value='$row[TNumberID]'>$row[TNumberID] ($row[TNumberName])</option>";
                }

        echo "</td>";


	echo "<td><input type='text' name='NewThreadDesc' value=" . json_encode($default_thread_description) . "/></td>";
	echo "<td><select name='NewActionID'>";

		$result = $db->query("SELECT * from Action");
		$rowarray = $result->fetchall(PDO::FETCH_ASSOC);
		foreach($rowarray as $row) {

			$selected = ($row['ActionTypeID'] == $default_action_type)?'selected':'';

			echo "<option $selected value='$row[ActionTypeID]'>$row[ActionTypeID] ($row[ActionName])</option>";
		}

	echo "</td>";
	#echo "<td><input type='text' name='NewThreadDestNumber' value='+44 xxxx xxxx xxxx '/></td>";
	echo "<td><select  style='width:100px;margin:5px 0 5px 0;' name='NewThreadDestNumber'>";

		$result = $db->query("SELECT * from Groups");
		$rowarray = $result->fetchall(PDO::FETCH_ASSOC);
		foreach($rowarray as $row) {

			$selected = ($row['GroupID'] == $default_group_id)?'selected':'';
			echo "<option $selected value='$row[GroupID]'>$row[GroupName] </option>";
		}

	echo "</select>";
	echo "</td>";
	echo "<td><input type='text' name='NewMp3Name' value=" . json_encode($default_mp3_name) . "/></td>";
	echo "<td><input type='text' name='NewFrequency' value='$default_frequency_minutes'/></td>";
	echo "<td><select  style='width:100px;margin:5px 0 5px 0;' name='NewChildThreadID[]' multiple='multiple' size=3>";

		echo "<option value='0'>0 (no child thread)</option>";
		$result = $db->query("SELECT * from Thread");
		$rowarray = $result->fetchall(PDO::FETCH_ASSOC);
		foreach($rowarray as $row) {
			$selected = (in_array($row['id'],$default_child_thread_id))?'selected':'';

			echo "<option $selected  value='$row[id]'>$row[id] ($row[ThreadDescription])</option>";
		}

	echo "</select>";
	echo "</td>";
	echo "<td><input type='text' size=1  name='NewStartTimeHours' value='$default_start_time_hour'/>:";
	echo "<input type='text' size=1 name='NewStartTimeMinutes' value='$default_start_time_minute'/>-&gt;";
	echo "<input type='text' size=1 name='NewStopTimeHours' value='$default_stop_time_hour'/>:";
	echo "<input type='text' size=1 name='NewStopTimeMinutes' value='$default_stop_time_minute'/>";
	echo "</td>";
	echo "<td><input type='submit' value='Submit' /></td>";
	echo "</tr>";
	echo $tablebody;
	echo("</table>");
	echo("</form>");

	echo("<br><br>");

	echo("<div id='container'>");
	echo("<div id='timelineDiv' >");
	echo("<div class='tableTitle'>Current TimeLine</div><br><div class='tableDescription' width=250px>The TimeLine shows all Actions from all Threads..</div>");
	echo "<a href='$this_url?secret=$secret_local&GLOBAL=KILL'>Kill TimeLine</a>";
	echo "&nbsp;&nbsp;<a href='$this_url?secret=$secret_local'>Refresh Page</a>";

	$result = $db->query("select TimeLine.ActivityTime > datetime('now') as FUTURE, Thread.ThreadDescription,TimeLine.id, TimeLine.AdditionalNumberID, TimeLine.ThreadID, TimeLine.ActivityTime, TimeLine.Completed, TimeLine.CompletedTime, TimeLine.Description, TimeLine.Notes, Thread.ActionType, Thread.mp3Name, Thread.DestNumber, Thread.FrequencyMinutes from TimeLine, Thread where TimeLine.ThreadID = Thread.id order by TimeLine.ActivityTime desc");



	


        echo("<table>");
        echo("<tr><th>ID</th><th>Time</th><th>Thread Description</th><th>Number</th><th>MP3 / Text</th><th>Completed?</th><th>Comment</th></tr>");
        $rowarray = $result->fetchall(PDO::FETCH_ASSOC);
        
	$rownum=0;

	$objAllNumbers = $tdb->getAllPhoneNumbers();
	$objAllGroups = $tdb->getAllNumberGroups();

        foreach($rowarray as $row)
        {
		$rowstyle = (++$rownum % 2)==0?"d0":"d1";
		$futstyle = ($rownum % 2)==0?"f0":"f1";


		$rowstyle = ($row['FUTURE'] == 0)?$rowstyle:$futstyle;
		

		if ($row['DestNumber'] != 0) {
			$the_number = "group: $row[DestNumber] " . $objAllGroups[$row['DestNumber']]->GroupName;
		} else {
			$the_number = "number: $row[AdditionalNumberID] " . $objAllNumbers[$row['AdditionalNumberID']]->NumberDescription;
		
		}

		echo "<tr class='" .$rowstyle . "'>";
		$completed=($row[Completed]==1)?'Yes (' . $row[CompletedTime] . ")":'No';
                echo "<td>$row[id]</td><td>$row[ActivityTime]</td><td>$row[ThreadDescription]</td><td> $the_number</td><td>$row[mp3Name]</td><td>$completed</td><td>$row[Description]</td>";
                echo "</tr>";
        }
        echo("</table>");
	echo("</div>"); #end of the timeline div
	echo("<div id='callTrak' >");

	echo("<div class='tableTitle'>History</div><br><div class='tableDescription' width=250px>The History shows all inbound and outbound calls</div>");

echo "<a href='$this_url?secret=$secret_local&GLOBAL=TRUNCATE'>Truncate History to most recent 10</a>";
        echo("&nbsp|&nbsp;");
        echo "<a href='$base_url/timeline-csvhistory.php'>Export History</a>";



	$result = $db->query('select CallTrack.IsOutbound, Thread.ThreadDescription,Number.Number,Number.NumberDescription, Thread.mp3Name, CallTrack.TrackNumberID, CallTrack.TrackTime, CallTrack.StatusText from Thread, Number, CallTrack where Thread.id = CallTrack.ThreadID and CallTrack.TrackNumberID = Number.NumberID order by CallTrack.TrackID desc');


        echo("<table>");
        echo("<tr><th> </th><th>Time</th><th>Thread Description</th><th>Number</th><th>MP3 / Text</th><th>Status</th><th>Capture</th></tr>");
        $rowarray = $result->fetchall(PDO::FETCH_ASSOC);

	$rownum=1;
        foreach($rowarray as $row)
        {
                $rowstyle = ($rownum++ % 2)==0?"d0":"d1";
                $inout = ($row[IsOutbound]!=0)?"OUTBOUND":"INBOUND";

                echo "<tr class='" .$rowstyle . "'>";
		$capture = "";
		if (preg_match("/unknown/",$row['NumberDescription'],$n)) {
			if (preg_match("/.*SMS.*:(.*)$/",$row['StatusText'],$matches)) {
				$capture = "<a href='$this_url?secret=$secret_local&CRUD=NAMETONUMBER&NumberID=$row[TrackNumberID]&Name=" . htmlspecialchars($matches[1]) . "'>" . htmlspecialchars($matches[1]) . "</a>";
			}
		}
                echo "<td>$inout</td><td>$row[TrackTime]</td><td>$row[ThreadDescription]</td><td>$row[Number] $row[NumberDescription]</td><td>" . htmlspecialchars($row['mp3Name']) . "</td><td>" . htmlspecialchars( $row['StatusText']) . "</td><td>$capture</td>";
                echo "</tr>";
        }
        echo("</table>");

	


	echo("</div>"); #end of the number mgmt div
	echo("</div>"); #end of the container div 
?>

</body>
</html>
