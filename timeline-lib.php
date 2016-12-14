<?php
	class DB {

		protected $db;

		function __construct($path) {
			$this->db = new PDO('sqlite:'.$path);
			$this->db->query("PRAGMA synchronous = OFF");
		}

		function init() {
		}


		function getDBTime() {


			echo("<!--in  get db time--!>\n");

                         $result = $this->db->query("SELECT HeartBeatTime, strftime('%s','now') - strftime('%s',HeartBeatTime) as LastHeartBeatAgo, DATETIME('now') as CurrentDBTime FROM HeartBeat where HeartBeatName='LastTimeLine'");

                        $rowarray = $result->fetchall(PDO::FETCH_ASSOC);

                        foreach($rowarray as $row)
                        {
				$currentDBTime = $row['CurrentDBTime'];

                        }

                        return $currentDBTime;


		}



		function getHeartBeat() {

			 echo("<!--in  get heart beat --!>\n");

			 $result = $this->db->query("SELECT HeartBeatTime, strftime('%s','now') - strftime('%s',HeartBeatTime) as LastHeartBeatAgo FROM HeartBeat where HeartBeatName='LastTimeLine'");

			$rowarray = $result->fetchall(PDO::FETCH_ASSOC);

			$lastHeartBeat = 'never';
			$lastHeartBeatAgo = -100;


			$beat = new HeartBeat();


			foreach($rowarray as $row)
			{

				$beat->LastHeartBeat = $row['HeartBeatTime'];
				$beat->LastHeartBeatAgo = $row['LastHeartBeatAgo'];
			}

			return $beat;


		}
		        #render
        	function renderMenuBar($base_url, $instance_name) {

			#top menu bar
			#get last heartbeat from db

			echo("<!--in renderMenuBar - about to get heart beat --!>\n");
			$heartBeat = $this->getHeartBeat();

			echo("<!--in renderMenuBar - got  heart beat --!>\n");
			if ($heartBeat->LastHeartBeatAgo < 2) {
				$heartBeatText = "TimeLine Active and OK - $heartBeat->LastHeartBeat";
			} else {
				$heartBeatText = "TimeLine Appears Down - $heartBeat->LastHeartBeat";
			}
			#render page

			#top menu bar
			$pattern = "/http:\/\/(.*)/";

			$replacement = "http://log:out@$1";
			$logout_url = preg_replace($pattern, $replacement, $base_url);
			
			$menu_text = "<div class='menuBar'><a href=$base_url/timeline-monitor.php>Monitor and Manager Threads</a>&nbsp;|&nbsp;<a href=$base_url/timeline-groups.php>Numbers and Groups</a>&nbsp;|&nbsp;<a href=$base_url/timeline-twilio.php>Twilio Account</a>&nbsp;|&nbsp;<a href=$base_url/timeline-bulk.php>Manage Bulk Agents</a>&nbsp;|&nbsp;<a href=$base_url/timeline-stash.php>STASH Console</a>&nbsp;|&nbsp;<a href=$base_url/timeline-scene.php>Scenes</a>&nbsp;|&nbsp;<a href=$base_url/timeline-clone.php>Clones</a>&nbsp;|&nbsp;$instance_name&nbsp|&nbsp$heartBeatText&nbsp;|&nbsp;<a href=$logout_url/timeline-monitor.php>logout</a></div>";

			return $menu_text;


		}


	###deal with scenes

/*   public $SceneID;
                public $CloneID;
                public $SceneName;
                public $IsActive;
*/

		function getAllScenes($cloneID) {



	echo("<!--in get all scenes--!>");

                        $scenes = array();
			$stmt = $this->db->prepare("select * from Scene where CloneID = ?");

                        $stmt->execute(array($cloneID));
			while ($row = $stmt->fetch()) {

				echo("<!--in get all scenes got sene" . $row['SceneID'] . "--!>");
                                $scene = new Scene();
                                $scene->SceneID = $row['SceneID'];
                                $scene->SceneName = $row['SceneName'];

                                $scenes[] = $scene;

                        }
                        return $scenes;


                }

		function createScene($newSceneName, $cloneID){

			$sql = "INSERT INTO Scene (SceneName, CloneID, IsActive) values (?,?,?)";

                        $st = $this->db->prepare($sql);
                        $st->execute(array($newSceneName, $cloneID, 1));


		}

		function updateSceneAllFields($updateSceneID, $updateSceneName,$cloneID) {


			$sql = "UPDATE Scene set SceneName = ? where SceneID = ? and CloneID = ? ";

                        $st = $this->db->prepare($sql);


			 $st->execute(array($updateSceneName,$updateSceneID, $cloneID));


		}

	###deal with clones


		function getCloneByUser($username, $password) {


			$clone = new SystemClone();
			$clone->CloneID = -1;
			echo("<!-- looking for $username $password -->");
    
                        $stmt = $this->db->prepare("SELECT * FROM Clone, CloneTwilio where Clone.CloneID = CloneTwilio.CloneTwilioID and UserName = ? and Password = ?");

                        $stmt->execute(array($username, $password));
                          while ($row = $stmt->fetch()) {


                                $clone->CloneID = $row['CloneID'];
                                echo("<!--got row - clone $clone->CloneID  -->");
                                $clone->CloneName = $row['CloneName'];
                                $clone->TwilioAcountSID = $row['twilioAcountSID'];
                                $clone->TwilioAuthToken = $row['twilioAuthToken'];
                                $clone->UserName = $row['UserName'];
                                $clone->Password = $row['Password'];
                                $clone->MP3URL = $row['MP3URL'];
                          }

                        return $clone;

		}

		function getCloneByTwilioSID($twilioSID) {


			$clone = new SystemClone();
			echo("<!-- in getCloneByTwilioSID: looking for sid $twilioSID -->");

			$stmt = $this->db->prepare("SELECT * FROM Clone, CloneTwilio where Clone.CloneID = CloneTwilio.CloneTwilioID and twilioAcountSID = ?");

			$stmt->execute(array($twilioSID));
			  while ($row = $stmt->fetch()) {


                                $clone->CloneID = $row['CloneID'];
                                $clone->CloneName = $row['CloneName'];
                                $clone->TwilioAcountSID = $row['twilioAcountSID'];
                                $clone->TwilioAuthToken = $row['twilioAuthToken'];
                                $clone->UserName = $row['UserName'];
                                $clone->Password = $row['Password'];
				$clone->MP3URL = $row['MP3URL'];
				echo("<!--got row - clone $clone->CloneID -->");
			  }
			#$clone->CloneName = $twilioSID;

			return $clone;

		}


		function getCloneByThreadID($threadID) {


                        $clone = new SystemClone();
                        echo("<!-- looking for $threadID -->");

                        $stmt = $this->db->prepare("SELECT * FROM Clone, Scene, Thread where Clone.CloneID = Scene.CloneID and Thread.SceneID = Scene.SceneID and Thread.id = ? ");

                        $stmt->execute(array($threadID));
                          while ($row = $stmt->fetch()) {
                                echo("<!--got row - clone $clone->CloneID -->");


                                $clone->CloneID = $row['CloneID'];
                                $clone->CloneName = $row['CloneName'];
                                $clone->TwilioAcountSID = $row['twilioAcountSID'];
                                $clone->TwilioAuthToken = $row['twilioAuthToken'];
                                $clone->UserName = $row['UserName'];
                                $clone->Password = $row['Password'];
                                $clone->MP3URL = $row['MP3URL'];
                          }
                        #$clone->CloneName = $twilioSID;

                        return $clone;

                }


		function getAllClones() {

                         $result = $this->db->query('select * from Clone join CloneTwilio on Clone.CloneID = CloneTwilio.CloneTwilioID');

                        $rowarray = $result->fetchall(PDO::FETCH_ASSOC);

                        $clones = array();


	echo("<!--in get all clones--!>");

                        foreach($rowarray as $row)
                        {
				echo("<!--in get all clones got clone" . $row['CloneID'] . "--!>");
                                $clone = new SystemClone();
                                $clone->CloneID = $row['CloneID'];
                                $clone->CloneName = $row['CloneName'];
                                $clone->TwilioAcountSID = $row['twilioAcountSID'];
                                $clone->TwilioAuthToken = $row['twilioAuthToken'];
				$clone->UserName = $row['UserName'];
				$clone->Password = $row['Password'];
				$clone->MP3URL = $row['MP3URL'];

                                $clones[] = $clone;

                        }
                        return $clones;


                }

		function createClone($newCloneName, $newCloneTwilioAcountSID, $newCloneTwilioAuthToken,$newCloneUserName, $newClonePassword,$newCloneMP3URL){

			$sql = "INSERT INTO Clone (CloneName, UserName, Password,MP3URL) values (?,?,?,?)";

                        $st = $this->db->prepare($sql);
                        $st->execute(array($newCloneName, $newCloneUserName, $newClonePassword,$newCloneMP3URL));


			$cloneID = $this->db->lastInsertId();
	
		


			$sql = "INSERT INTO CloneTwilio (CloneTwilioID, twilioAcountSID, twilioAuthToken) values (?,?,?)";

                        $st = $this->db->prepare($sql);
                        $st->execute(array($cloneID, $newCloneTwilioAcountSID, $newCloneTwilioAuthToken));

		}

		function updateCloneAllFields($updateCloneID, $updateCloneName, $updateCloneTwilioAcountSID, $updateCloneTwilioAuthToken, $updateCloneUserName, $updateClonePassword,$updateCloneMP3URL) {


			$sql = "UPDATE Clone set CloneName = ?, UserName=?, Password = ?,MP3URL = ? where CloneID = ?";

                        $st = $this->db->prepare($sql);
                        $st->execute(array($updateCloneName, $updateCloneUserName, $updateClonePassword,$updateCloneMP3URL, $updateCloneID));



			$sql = "UPDATE CloneTwilio set twilioAcountSID = ?, twilioAuthToken=? where CloneTwilioID = ?";

                        $st = $this->db->prepare($sql);
                        $st->execute(array($updateCloneTwilioAcountSID, $updateCloneTwilioAuthToken, $updateCloneID));


		}



	###deal with twilioNumbers

		function getAllTwilioNumbers() {

			 $result = $this->db->query('select * from TNumber where TNumberID > 0');

			$rowarray = $result->fetchall(PDO::FETCH_ASSOC);

			$numbers = array();

			foreach($rowarray as $row)
			{
				$number = new TwilioNumber();
				$number->TwilioNumberID = $row['TNumberID']; 
				$number->TwilioNumber = $row['TNumber'];
				$number->TwilioNumberName =  $row['TNumberName']; 
				$number->IsActive = $row['IsActive'];
				$number->PrefixWL = $row['PrefixWL'];
				$number->TwitterConsumerKey = $row['TwitterConsumerKey'];
				$number->TwitterConsumerKeySecret = $row['TwitterConsumerKeySecret'];
				$number->TwitterAccessToken = $row['TwitterAccessToken'];
				$number->TwitterAccessTokenSecret = $row['TwitterAccessTokenSecret'];

				$numbers[] = $number;

			}
			return $numbers;


		}

		function getAllTwilioNumbersByCloneID($cloneID) {

                         $sql = 'select * from TNumber where TNumberID > 0 and CloneID = ?';

			echo "<!-- sql is $sql cloneID = $cloneID-->";



                        $numbers = array();


			$q = $this->db->prepare($sql);
                        $q->execute(array($cloneID));

                        $q->setFetchMode(PDO::FETCH_BOTH);

                        // fetch
                        while($row = $q->fetch()){
                                $number = new TwilioNumber();
                                $number->TwilioNumberID = $row['TNumberID'];
                                $number->TwilioNumber = $row['TNumber'];
                                $number->TwilioNumberName =  $row['TNumberName'];
                                $number->IsActive = $row['IsActive'];
                                $number->PrefixWL = $row['PrefixWL'];
				$number->TwitterConsumerKey = $row['TwitterConsumerKey'];
                                $number->TwitterConsumerKeySecret = $row['TwitterConsumerKeySecret'];
                                $number->TwitterAccessToken = $row['TwitterAccessToken'];
                                $number->TwitterAccessTokenSecret = $row['TwitterAccessTokenSecret'];



                                $numbers[] = $number;

                        }
                        return $numbers;


                }



		function createTwilioNumber($number, $numberName, $isActive,$prefixWL,$cloneID,$twitterConsumerKey, $twitterConsumerKeySecret,$twitterAccessToken,$twitterAccessTokenSecret) {
	
			$sql = "INSERT INTO TNumber (TNumber, TNumberName, IsActive,PrefixWL,CloneID,TwitterConsumerKey, TwitterConsumerKeySecret, TwitterAccessToken, TwitterAccessTokenSecret) values (?,?,?,?,?,?,?,?,?)";

			$st = $this->db->prepare($sql);
			$st->execute(array($number,$numberName, $isActive,$prefixWL,$cloneID,$twitterConsumerKey,$twitterConsumerKeySecret,$twitterAccessToken,$twitterAccessTokenSecret));



		}


		function deleteTwilioNumber($numberID,$cloneID) {

			#deletes number, all threads with that number - and timeline!
				
			$sql= "delete from Thread where id in (select thread.id from thread, tnumber where thread.tnumberID = tnumber.tnumberID and tnumber.tnumberid = ? and tnumber.cloneID = ?)";
			$st = $this->db->prepare($sql);
                        $st->execute(array($numberID,$cloneID));	


			$sql= "delete from TNumber where TNumberID = ? and CloneID = ?";
			$st = $this->db->prepare($sql);
                        $st->execute(array($numberID, $cloneID));	




                }

	
		function updateTwilioNumber($updateTNumberID, $updateNumber, $updateNumberName, $isActive,$prefixWL, $twitterConsumerKey, $twitterConsumerKeySecret,$twitterAccessToken,$twitterAccessTokenSecret) {

			$sql = "UPDATE TNumber set TNumber = ?, TNumberName=?, IsActive = ?, PrefixWL = ?,TwitterConsumerKey = ?, TwitterConsumerKeySecret = ?, TwitterAccessToken=?, TwitterAccessTokenSecret=?  where TNumberID = ?"; 

			$st = $this->db->prepare($sql);
			$st->execute(array($updateNumber, $updateNumberName, $isActive, $prefixWL,$twitterConsumerKey,$twitterConsumerKeySecret,$twitterAccessToken,$twitterAccessTokenSecret,$updateTNumberID));

		}




		function getNumberIDFromCallTrack($callTrackID) {
			$sql = "SELECT TrackNumberID FROM CallTrack WHERE TrackID = ?";
			$q = $this->db->prepare($sql);
			$q->execute(array($callTrackID));


			$q->setFetchMode(PDO::FETCH_BOTH);

			// fetch
			$additional_number_id = 0;

			while($r = $q->fetch()){
			  $additional_number_id = $r['NumberID'];
			}

			return $additional_number_id;
			

		}


	
		function update_calltrack_status($callTrackID, $comment) {

			$sql = "UPDATE CallTrack set StatusText = StatusText || '$comment' where TrackID = ?";

			echo "<!-- sql is $sql-->";
			$q = $this->db->prepare($sql);
			$q->execute(array($callTrackID));

		}

		function insertIntoCallTrack($isOutbound, $threadID, $numberID, $twilioID, $status, $inboundDetails,$raw) {
			#inserts into the calltrack, and returns the callTrackID

			$sql = "INSERT INTO CallTrack (IsOutbound , ThreadID, TrackNumberID, TrackTime , TwilioID , TwilioFollowup , StatusText, InboundDetails,RawText ) values (?,?,?,DATETIME('now'),?,0,?,?,?)";
			$qq = $this->db->prepare($sql);
			$qq->execute(array($isOutbound, $threadID, $numberID, $twilioID, $status, $inboundDetails,$raw));
			#now get the calLTrackID

			$trackID = $this->db->lastInsertId();

                        return $trackID;


		}



		function insertToTimeLineOffset($threadID, $offsetMinutes, $NumberID,$notes) {

			$sql = "INSERT INTO TimeLine (ThreadId, ActivityTime, Completed, CompletedTime, Description, Notes, AdditionalNumberID) values( $threadID ,datetime('now','+$offsetMinutes minutes'),0,NULL,'$notes',NULL,$NumberID)";


                        echo("<!-- insertToTImeLineOffset: sql is " . $sql . "-->");
                        $count = $this->db->exec($sql);
                        echo("<!-- sql done " . $count . "rows -->");


                }
		function insertToTimeLineTime($threadID, $datetime, $NumberID,$notes) {

			#datetime must be in GMT and in the format for sqlite.
	
                        $sql = "INSERT INTO TimeLine (ThreadId, ActivityTime, Completed, CompletedTime, Description, Notes, AdditionalNumberID) values( $threadID ,'$datetime',0,NULL,'$notes',NULL,$NumberID)";


                        echo("<!-- insertToTImeLineTIme: sql is  $sql  datetime is $datetime -->");


                        $count = $this->db->exec($sql);
                        echo("<!-- sql done " . $count . "rows -->");
			echo "<!--done-->\n";



                }



		function getThreadByThreadID($threadID) {

			 $sql = "SELECT TNumber.PrefixWL,Thread.TNumberID, TNumber.TNumber, Thread.id, Thread.ThreadDescription, Thread.mp3Name, Thread.DestNumber, Thread.ActionType,Thread.ChildThreadID, Thread.StartTimeHour, Thread.StartTimeMinute, Thread.StopTimeHour, Thread.StopTimeMinute, Thread.FrequencyMinutes from Thread, TNumber where id = ? and TNumber.TNumberID = Thread.TNumberID  ";
 

                        echo("<!--exec sql " . $sql . "-->");
                        $q = $this->db->prepare($sql);
                        $q->execute(array($threadID));

                        $q->setFetchMode(PDO::FETCH_BOTH);

                        $thread = new Thread();
                        while($r = $q->fetch()){



                                $thread->ThreadID = $r['id'];
                                $thread->ThreadDescription = $r['ThreadDescription'];
                                $thread->ActionTypeID = $r['ActionType'];
                                $thread->DestGroupID = $r['DestNumber'];
                                $thread->mp3Name = $r['mp3Name'];;
                                $thread->StartTimeHour = $r['StartTimeHour'];
                                $thread->StartTimeMinute = $r['StartTimeMinute'];
                                $thread->StopTimeHour = $r['StopTimeHour'];
                                $thread->StopTimeMinute = $r['StopTimeMinute'];
                                $thread->Frequency = $r['FrequencyMinutes'];
                                $thread->ChildThreadText = $r['ChildThreadID'];
                                $thread->TNumberID = $r['TNumberID'];
                                $thread->TNumber = $r['TNumber'];
                                $thread->TNumberPrefixWL = $r['PrefixWL'];
                                $thread->ChildThreads = array();

                                #deal with children
                                $childIDs = explode(',',$thread->ChildThreadText);
                                foreach($childIDs as $childID) {

                                        $thread->ChildThreads[] = intval($childID);

                                }

                        }

                        return $thread;


		}

		function getThreadsByPhoneNumberID($numberID) {
			#returns an array of Thread objects for threads which can react to $numberID
			#at the moment does NOT filter on time - this is a TODO
			#however - changed on 17th sept 2013 - this now filters on Active
			 $sql = "SELECT TNumber.PrefixWL,TNumber.TNumberID, TNumber.TNumber, Thread.id, Thread.ThreadDescription, Thread.mp3Name, Thread.DestNumber, Thread.ActionType,Thread.ChildThreadID, Thread.StartTimeHour, Thread.StartTimeMinute, Thread.StopTimeHour, Thread.StopTimeMinute, Thread.FrequencyMinutes from Thread, Groups, Number, GroupNumber, TNumber where Thread.DestNumber = Groups.GroupID and Groups.GroupID = GroupNumber.GNGroupID and Number.NumberID = GroupNumber.GNNumberID and Number.NumberID = ? and Thread.TNumberID = TNumber.TNumberID and Thread.Active=1  order by Thread.FrequencyMinutes";

			echo("<!--exec sql " . $sql . "-->");
			$q = $this->db->prepare($sql);
			$q->execute(array($numberID));

			$q->setFetchMode(PDO::FETCH_BOTH);
			
			$threads = array();	
			while($r = $q->fetch()){

				$thread = new Thread();
			
				$thread->ThreadID = $r['id'];
				$thread->ThreadDescription = $r['ThreadDescription'];
				$thread->ActionTypeID = $r['ActionType'];
				$thread->DestGroupID = $r['DestNumber'];
				$thread->mp3Name = $r['mp3Name'];;
				$thread->StartTimeHour = $r['StartTimeHour'];
				$thread->StartTimeMinute = $r['StartTimeMinute'];
				$thread->StopTimeHour = $r['StopTimeHour'];
				$thread->StopTimeMinute = $r['StopTimeMinute'];
				$thread->Frequency = $r['FrequencyMinutes'];
				$thread->ChildThreadText = $r['ChildThreadID'];
				$thread->TwilioNumberID = $r['TNumberID'];
				$thread->TwilioNumber = $r['TNumber'];
                                $thread->TNumberPrefixWL = $r['PrefixWL'];
				$thread->ChildThreads = array();

				#deal with children
				$childIDs = explode(',',$thread->ChildThreadText);
				foreach($childIDs as $childID) {

					$thread->ChildThreads[] = intval($childID);

				}

				$threads[] = $thread;
			}

			return $threads;

		}

		function getThreadsByNumberGroupID($groupID) {
                        #returns an array of Thread objects for threads which can react to $numberID
                        #at the moment does NOT filter on time - this is a TODO
                         $sql = "SELECT TNumber.PrefixWL,TNumber.TNumberID, TNumber.TNumber, Thread.id, Thread.ThreadDescription, Thread.mp3Name, Thread.DestNumber, Thread.ActionType,Thread.ChildThreadID, Thread.StartTimeHour, Thread.StartTimeMinute, Thread.StopTimeHour, Thread.StopTimeMinute, Thread.FrequencyMinutes from Thread,  TNumber where Thread.DestNumber = ?  and Thread.TNumberID = TNumber.TNumberID and Thread.Active=1  order by Thread.FrequencyMinutes";

                        echo("<!--exec sql " . $sql . "-->");
                        $q = $this->db->prepare($sql);
                        $q->execute(array($groupID));

                        $q->setFetchMode(PDO::FETCH_BOTH);

                        $threads = array();
                        while($r = $q->fetch()){

                                $thread = new Thread();

                                $thread->ThreadID = $r['id'];
                                $thread->ThreadDescription = $r['ThreadDescription'];
                                $thread->ActionTypeID = $r['ActionType'];
                                $thread->DestGroupID = $r['DestNumber'];
                                $thread->mp3Name = $r['mp3Name'];;
                                $thread->StartTimeHour = $r['StartTimeHour'];
                                $thread->StartTimeMinute = $r['StartTimeMinute'];
                                $thread->StopTimeHour = $r['StopTimeHour'];
                                $thread->StopTimeMinute = $r['StopTimeMinute'];
                                $thread->Frequency = $r['FrequencyMinutes'];
                                $thread->ChildThreadText = $r['ChildThreadID'];
                                $thread->TwilioNumberID = $r['TNumberID'];
                                $thread->TwilioNumber = $r['TNumber'];
                                $thread->TNumberPrefixWL = $r['PrefixWL'];
                                $thread->ChildThreads = array();

                                #deal with children
                                $childIDs = explode(',',$thread->ChildThreadText);
                                foreach($childIDs as $childID) {

                                        $thread->ChildThreads[] = intval($childID);

                                }

                                $threads[] = $thread;
                        }

                        return $threads;

                }



		function getDefaultThreadID($type) {

			#returns the threadID of the default thread 

			$sql = "SELECT ThreadID from DefaultInboundThread where Type=?";
			$q = $this->db->prepare($sql);
			$q->execute(array($type));


			$q->setFetchMode(PDO::FETCH_BOTH);

			$defaultThreadID = 0;
			// fetch
			while($r = $q->fetch()){
			  $defaultThreadID = $r['ThreadID'];
			}

			return $defaultThreadID;

		}


		function getTwilioNumberByNumber($number,$cloneID) {
                        $objNumber = new TwilioNumber;

                        $sql = "SELECT TNumberID, TNumberName,PrefixWL, IsActive  FROM TNumber  WHERE TNumber = ?";
                        $q = $this->db->prepare($sql);
                        $q->execute(array($number));

                        $q->setFetchMode(PDO::FETCH_BOTH);

                        $numberID = 0;
                        $numberName='unknown';
                        // fetch
                        while($r = $q->fetch()){
                          $numberID = $r['TNumberID'];
                          $numberName = $r['TNumberName'];
				$isActive = $r['IsActive'];
				$prefixWL = $r['PrefixWL'];
                        }

                        #if we dont know the number - add it.
                        if ($numberID == 0) {

                                $sql = "INSERT into TNumber (TNumber, TNumberName, IsActive, PrefixWL,CloneID) values(?,?,1,'',?)";
                                $q = $this->db->prepare($sql);
                                $q->execute(array($number,'unknown twilio number',$cloneID));


                                #and get the new numberID
                                $sql = "SELECT TNumberID, TNumberName, IsActive,PrefixWL  FROM TNumber  WHERE TNumber = ?";
                                $q = $this->db->prepare($sql);
                                $q->execute(array($number));

                                $q->setFetchMode(PDO::FETCH_BOTH);

                                $numberID = 0;
                                $numberDescription='unknown';
                                // fetch
                                while($r = $q->fetch()){
					$numberID = $r['TNumberID'];
					$numberName = $r['TNumberName'];
					$isActive = $r['IsActive'];
					$prefixWL = $r['PrefixWL'];
                                }
                        }

                        $objNumber->TwilioNumberID = $numberID;
                        $objNumber->TwilioNumber = $number;
                        $objNumber->TwilioNumberName = $numberName;
			$objNumber->IsActive = $isActive;
			$objNumber->PrefixWL = $prefixWL;
			$objNumber->CloneID = $cloneID;

                        return $objNumber;

		}
		function getPhoneNumberByNumber($number,$cloneID) {
			$objNumber = new PhoneNumber;

			$sql = "SELECT NumberID, NumberDescription  FROM Number  WHERE Number = ? and CloneID = ?";
			$q = $this->db->prepare($sql);
			$q->execute(array($number,$cloneID));

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

				$sql = "INSERT into Number (Number, NumberDescription,CloneID) values(?,?,?)";
				$q = $this->db->prepare($sql);
				$q->execute(array($number,'unknown inbound number',$cloneID));


				#and get the new numberID
				$sql = "SELECT NumberID, NumberDescription  FROM Number  WHERE Number = ? and CloneID = ?";
				$q = $this->db->prepare($sql);
				$q->execute(array($number,$cloneID));

				$q->setFetchMode(PDO::FETCH_BOTH);

				$numberID = 0;
				$numberDescription='unknown';
				// fetch
				while($r = $q->fetch()){
				  $numberID = $r['NumberID'];
				  $numberDescription = $r['NumberDescription'];
				}
			}

			$objNumber->NumberID = $numberID;
			$objNumber->Number = $number;
			$objNumber->NumberDescription = $numberDescription;
			$objNumber->CloneID = $cloneID;

			return $objNumber;

		}

		function getPhoneNumbersByGroupIDCloneID($groupID,$cloneID) {
			echo "<!--get numners by groupID: entered-->\n";
                        $objNumber = new PhoneNumber;
			$numbers = array();

                        $sql = "SELECT Number, NumberID, NumberDescription  FROM Number, GroupNumber  WHERE Number.NumberID = GroupNumber.GNNumberID and GroupNumber.GNGroupID = ? and Number.CloneID=?";
			echo "<!--get numners by groupID: sql is $sql-->\n";
                        $q = $this->db->prepare($sql);
                        $q->execute(array($groupID,$cloneID));
			echo "<!--get numners by groupID: sql is finished-->\n";

                        $q->setFetchMode(PDO::FETCH_BOTH);

                        $numberID = 0;
                        $numberDescription='unknown';
                        // fetch
                        while($r = $q->fetch()){
                          $numberID = $r['NumberID'];
                          $numberDescription = $r['NumberDescription'];
				$number = $r['Number'];

				$objNumber = new PhoneNumber;
				$objNumber->NumberID = $numberID;
				$objNumber->Number = $number;
				$objNumber->NumberDescription = $numberDescription;

				$numbers[] = $objNumber;

                        }

                        return $numbers;

                }

		function getPhoneNumbersByGroupID($groupID) {
			echo "<!--get numners by groupID: entered-->\n";
                        $objNumber = new PhoneNumber;
			$numbers = array();

                        $sql = "SELECT Number, NumberID, NumberDescription  FROM Number, GroupNumber  WHERE Number.NumberID = GroupNumber.GNNumberID and GroupNumber.GNGroupID = ?";
			echo "<!--get numners by groupID: sql is $sql-->\n";
                        $q = $this->db->prepare($sql);
                        $q->execute(array($groupID));
			echo "<!--get numners by groupID: sql is finished-->\n";

                        $q->setFetchMode(PDO::FETCH_BOTH);

                        $numberID = 0;
                        $numberDescription='unknown';
                        // fetch
                        while($r = $q->fetch()){
                          $numberID = $r['NumberID'];
                          $numberDescription = $r['NumberDescription'];
				$number = $r['Number'];

				$objNumber = new PhoneNumber;
				$objNumber->NumberID = $numberID;
				$objNumber->Number = $number;
				$objNumber->NumberDescription = $numberDescription;

				$numbers[] = $objNumber;

                        }

                        return $numbers;

                }


		function getAllPhoneNumbers() {
			echo "<!--get numners by groupID: entered-->\n";
                        $objNumber = new PhoneNumber;
                        $numbers = array();

                        $sql = "SELECT Number, NumberID, NumberDescription  FROM Number";
                        echo "<!--get numners by groupID: sql is $sql-->\n";
                        $q = $this->db->prepare($sql);
                        $q->execute(array());
                        echo "<!--get numners by groupID: sql is finished-->\n";

                        $q->setFetchMode(PDO::FETCH_BOTH);

                        $numberID = 0;
                        $numberDescription='unknown';
                        // fetch
                        while($r = $q->fetch()){
                          $numberID = $r['NumberID'];
                          $numberDescription = $r['NumberDescription'];
                                $number = $r['Number'];

                                $objNumber = new PhoneNumber;
                                $objNumber->NumberID = $numberID;
                                $objNumber->Number = $number;
                                $objNumber->NumberDescription = $numberDescription;

                                $numbers[$numberID] = $objNumber;

                        }

                        return $numbers;

                }


		function getAllPhoneNumbersByCloneID($cloneID) {
                        echo "<!--get numners by cloneID: entered-->\n";
                        $objNumber = new PhoneNumber;
                        $numbers = array();

                        $sql = "SELECT Number, NumberID, NumberDescription  FROM Number where CloneID = ?";
                        echo "<!--get numners by cloneID: sql is $sql-->\n";
                        $q = $this->db->prepare($sql);
                        $q->execute(array($cloneID));
                        echo "<!--get numners by cloneID: sql is finished-->\n";

                        $q->setFetchMode(PDO::FETCH_BOTH);

                        $numberID = 0;
                        $numberDescription='unknown';
                        // fetch
                        while($r = $q->fetch()){
                          $numberID = $r['NumberID'];
                          $numberDescription = $r['NumberDescription'];
                                $number = $r['Number'];

                                $objNumber = new PhoneNumber;
                                $objNumber->NumberID = $numberID;
                                $objNumber->Number = $number;
                                $objNumber->NumberDescription = $numberDescription;

                                $numbers[$numberID] = $objNumber;

                        }

                        return $numbers;

                }


		function registerSIMDevice($codename){
			//codename needs to match a number description in the PhoneNumber table.
			//if not unique, will choose the first one ordered by numberID
			//then adds into the SIMNumberMap table (if needed deletes first)
			//adds in a GUID and returns the GUID/
			//TODO - add in logic for names not existing, names already registered etc. etc.

			$guid = exec("openssl rand -hex 16");

			$objNumber = $this->getPhoneNumberByDescription($codename);
			if ($objNumber->NumberID > 0) {
				//we have an existing number...

				$sql = "DELETE from SIMNumberMap where NumberID = ?";
				$st = $this->db->prepare($sql);
				$st->execute(array($objNumber->NumberID));


				$sql = "INSERT into SIMNumberMap (NumberID, GUID) values(?,?)";
				$st = $this->db->prepare($sql);
				$st->execute(array($objNumber->NumberID,$guid));

				return $guid;
			} else {
				return "";
			}
		}
		function retrieveSIMGuid($codename) {

			$guid = "not found";
			$objNumber = new PhoneNumber;
			//$objNumber = getPhoneNumberByDescription("STONED");
			$objNumber = $this->getPhoneNumberByDescription($codename);
			$sql = "SELECT GUID from SIMNumberMap where NumberID = ?";


			$q = $this->db->prepare($sql);
                        $q->execute(array($objNumber->NumberID));

                        $q->setFetchMode(PDO::FETCH_BOTH);
                        // fetch

                        while($r = $q->fetch()){
                          $guid = $r['GUID'];
                        }

                        return $guid;

			

		}

		function getPhoneNumberByDescription($description) {
			$objNumber = new PhoneNumber;

			$sql = "SELECT NumberID, Number, NumberDescription  FROM Number  WHERE NumberDescription = ? ORDER BY NumberID LIMIT 1";
			$q = $this->db->prepare($sql);
			$q->execute(array($description));

			$q->setFetchMode(PDO::FETCH_BOTH);

			$numberID = 0;
			$numberDescription='unknown';
			// fetch
			while($r = $q->fetch()){
			  $numberID = $r['NumberID'];
			  $numberDescription = $r['NumberDescription'];
			  $number = $r['Number'];
			}

			$objNumber->NumberID = $numberID;
			$objNumber->Number = $number;
			$objNumber->NumberDescription = $numberDescription;

			return $objNumber;

		}

		function getPhoneNumberByGuid($guid) {
			echo"<!-- in getPhoneNumberByGuid -->";
			$objNumber = new PhoneNumber;
			echo"<!-- in getPhoneNumberByGuid guid is $guid -->";

                        $sql = "SELECT Number.NumberID as NumberID, Number.Number AS Number, Number.NumberDescription AS NumberDescription  FROM Number, SIMNumberMap  WHERE GUID = ? and Number.NumberID = SIMNumberMap.NumberID ORDER BY Number.NumberID LIMIT 1"; 
                        $q = $this->db->prepare($sql);
                        $q->execute(array($guid));

			echo"<!-- in getPhoneNumberByGuid: finished sql -->";
                        $q->setFetchMode(PDO::FETCH_BOTH);

                        $numberID = 0;
                        $numberDescription='unknown';
			$number='';
                        // fetch
                        while($r = $q->fetch()){
			echo"<!-- get number by guid: in sql loop-->";
                          $numberID = $r['NumberID'];
                          $numberDescription = $r['NumberDescription'];
                          $number = $r['Number'];
                        }

			echo"<!-- finished sql loop-->";
                        $objNumber->NumberID = $numberID;
                        $objNumber->Number = $number;
                        $objNumber->NumberDescription = $numberDescription;

                        return $objNumber;

                }


		function getAllNumberGroups() {
                        $groups = array();

                        $sql = "SELECT GroupID, GroupName  FROM Groups";
                        $q = $this->db->prepare($sql);
                        $q->execute(array());
                        echo "<!--get numners by groupID: sql is finished-->\n";

                        $q->setFetchMode(PDO::FETCH_BOTH);

                        $groupID = 0;
                        $groupName='unknown';
                        // fetch
                        while($r = $q->fetch()){
                          $groupID = $r['GroupID'];
                          $groupName = $r['GroupName'];
                                $objGroup = new NumberGroup;
                                $objGroup->GroupID = $groupID;
                                $objGroup->GroupName = $groupName;

                                $groups[$groupID] = $objGroup;

                        }

                        return $groups;


		}
	
		function sendSIMMessage($dstNumberID, $msgTxt) {

			$sql = "INSERT INTO SIMMessage (DstNumberID,SIMTime, SIMText, SIMIsRcvd, SIMIsOutbound ) values (?,DATETIME('now'), ?,0,1)";
			
			$st = $this->db->prepare($sql);
                        $st->execute(array($dstNumberID, $msgTxt));
			


		}
		function markSIMMessageRcvd($SIMID, $guid) {

			//first make sure the GUID matches the phonenumber

                        echo "<!-- in markrcvd - working with guid of $guid-->";

                        $messageArray = array();

                        $matchedNumber = $this->getPhoneNumberByGuid($guid);

			echo "<!-- get messages got a number $matchedNumber->NumberID compare with $numberID-->";
			$numberID = $matchedNumber->NumberID;
                        if ($matchedNumber->NumberID == 0) {
                                #do nothing - messageArray remains empty

                                echo "<!-- so doing nothing...-->";
                        } else {
                                echo "<!-- got valid GUID etc...-->";

				//only update the message if it is owned by this numberID...

				$sql = "UPDATE SIMMEssage set SIMIsRcvd = 1 where SIMID=? and DstNumberID = ?";

				$q = $this->db->prepare($sql);
                                echo "<!-- sql now prepared calling with $numberID...-->";
                                $q->execute(array($SIMID,$numberID));

				

			}

		}

		
		function supressSIMMessages($numberID, $guid,$except) {
                        //first make sure the GUID matches the phonenumber
			//suppress all messages apart fom the most recent except...

                        echo "<!-- in supress messages - working with guid of $guid-->";

                        $messageArray = array();

                        $matchedNumber = $this->getPhoneNumberByGuid($guid);

                echo "<!-- supress messages got a number $matchedNumber->NumberID compare with $numberID-->";
                        if ($matchedNumber->NumberID != $numberID || $numberID == 0) {
                                #do nothing - messageArray remains empty

                                echo "<!-- so doing nothing...-->";
                        } else {

                                echo "<!-- executing sql to get messagfes...-->";
				$sql = "UPDATE SIMMessage set SIMIsSupressed = 1 where DstNumberID = ? and SIMID not in (select SIMID from SIMMessage where DstNumberID = ? order by SIMID desc LIMIT ?)";

				$q = $this->db->prepare($sql);
                                echo "<!-- sql now prepared calling with $numberID...-->";
                                $q->execute(array($numberID,$numberID, $except));
			}
		}

	

		function getSIMMessages($numberID, $guid,$sow) {
			//all = 1 is SoW - gets all messages in rcvd or not
			//all-0 only gets the rcvd ones.
			//first make sure the GUID matches the phonenumber

			echo "<!-- in get messages - working with guid of $guid-->";

			$messageArray = array();
			
			$matchedNumber = $this->getPhoneNumberByGuid($guid);

		echo "<!-- get messages SOW = $sow got a number $matchedNumber->NumberID compare with $numberID-->";
			if ($matchedNumber->NumberID != $numberID || $numberID == 0) {
				#do nothing - messageArray remains empty

				echo "<!-- so doing nothing...-->";
			} else {

				echo "<!-- executing sql to get messagfes...-->";
				if ($sow == 0) {
					$sql = "SELECT SIMID, DstNumberID,SIMTime, SIMText, SIMIsRcvd, SIMIsOutbound FROM SIMMessage where SIMIsRcvd=0 AND SIMIsOutbound = 1 and SIMIsSupressed = 0 and DstNumberID = ? order by SIMID";
				} else {

					$sql = "SELECT SIMID, DstNumberID,SIMTime, SIMText, SIMIsRcvd, SIMIsOutbound FROM SIMMessage where SIMIsOutbound = 1 and SIMIsSupressed = 0  and DstNumberID = ? order by SIMID";
				}
				echo "<!-- sql is $sql...-->";
				$q = $this->db->prepare($sql);
				echo "<!-- sql now prepared calling with $numberID...-->";
				$q->execute(array($numberID));

				echo "<!-- sql now execed...-->";
				$q->setFetchMode(PDO::FETCH_BOTH);

				// fetch
				while($r = $q->fetch()){
				echo"<!--in sql loop-->";
					$simID = $r['SIMID'];
					$dstNumberID = $r['DstNumberID'];
					$simTime = $r['SIMTime'];
					$simIsReceived = $r['SIMIsRcvd'];
					$simIsOutbound = $r['SIMIsOutbound'];
					$simText = $r['SIMText'];

					$objSIM = new SIMMessage;
					$objSIM->SIMID = $simID;
					$objSIM->DstNumberID = $dstNumberID;
					$objSIM->SIMTime = $simTime;
					$objSIM->SIMTxt = $simText;
					$objSIM->SIMIsReceived = $simIsReceived;
					$objSIM->SIMIsOutbound = $simIsOutbound;

					$messageArray[] = $objSIM;

				}

			}
			echo "<!-- now at end of getSIMMessages-->";
			return $messageArray;


		}

	}

	class SIMMessage {
		public $SIMID;
		public $DstNumberID;
		public $SIMTime;
		public $SIMTxt;
		public $SIMIsReceived;
		public $SIMIsOutbound;
	}

	class NumberGroup {

		public $GroupID;
		public $GroupName;
		public $SceneID;

	}

	class PhoneNumber {
		public $NumberID;
		public $Number;
		public $NumberDescription;
		public $CloneID;
	}


	class Thread {
		public $ThreadID;
		public $ThreadDescription;
		public $ActionTypeID;
		public $DestGroupID;
		public $mp3Name;
		public $StartTimeHour;
		public $StartTimeMinute;
		public $StopTimeHour;
		public $StopTimeMinute;
		public $Frequency;
		public $ChildThreadText;
		public $ChildThreads;
		public $TwilioNumberID;
		public $TwilioNumber;
		public $TNPrefixWL;
		public $SceneID;

	}

	class ActionType {
		public static $InboundMp3Action = 6;
		public static $InboundTextAction = 5;

		public static $InboundSMSAction = 9;
		public static $DialToneActionType=10;
		public static $KickOffActionType=11;
		public static $InboundSIMAction = 13;
	}

	class HeartBeat {

		public $LastHeartBeat;
		public $LastHeartBeatAgo;

	}


	class TwilioNumber {
		public $TwilioNumberID;
		public $TwilioNumber;
		public $TwilioNumberName;
		public $IsActive;
		public $PrefixWL;
		public $CloneID;
		public $TwitterScreenName;
		public $TwitterAccessToken;
		public $TwitterAccessTokenSecret;
		public $TwitterConsumerKey;
		public $TwitterConsumerKeySecret;
	}
	
	class SystemClone {
		public $CloneID;
		public $CloneName;
		public $TwilioAcountSID;
		public $TwilioAuthToken;
		public $UserName;
		public $Password;
		public $MP3URL;
		
	}
	class Scene { 
		public $SceneID;
		public $CloneID;
		public $SceneName;
		public $IsActive;
	}

	

?>
