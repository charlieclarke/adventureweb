<?php
	class DB {

		protected $db;

		function __construct($path) {
			$this->db = new PDO('sqlite:'.$path);
		}

		function init() {
		}


		function getHeartBeat() {

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

				$numbers[] = $number;

			}
			return $numbers;


		}
		function createTwilioNumber($number, $numberName, $isActive) {
	
			$sql = "INSERT INTO TNumber (TNumber, TNumberName, IsActive) values (?,?,?)";

			$st = $this->db->prepare($sql);
			$st->execute(array($number,$numberName, $isActive));



		}


		function deleteTwilioNumber($numberID) {

			#deletes number, all threads with that number - and timeline!
				
			#TODO...



                }

	
		function updateTwilioNumber($updateTNumberID, $updateNumber, $updateNumberName, $isActive) {

			$sql = "UPDATE TNumber set TNumber = ?, TNumberName=?, IsActive = ?  where TNumberID = ?"; 

			$st = $this->db->prepare($sql);
			$st->execute(array($updateNumber, $updateNumberName, $isActive, $updateTNumberID));

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

		function insertIntoCallTrack($isOutbound, $threadID, $numberID, $twilioID, $status, $inboundDetails) {
			#inserts into the calltrack, and returns the callTrackID

			$sql = "INSERT INTO CallTrack (IsOutbound , ThreadID, TrackNumberID, TrackTime , TwilioID , TwilioFollowup , StatusText, InboundDetails ) values (?,?,?,DATETIME('now'),?,0,?,?)";
			$qq = $this->db->prepare($sql);
			$qq->execute(array($isOutbound, $threadID, $numberID, $twilioID, $status, $inboundDetails));
			#now get the calLTrackID

			$sql = "SELECT TrackID from CallTrack where TrackNumberID = ?";

			$q = $this->db->prepare($sql);
                        $q->execute(array($numberID));

                        $q->setFetchMode(PDO::FETCH_BOTH);

                        $trackID = 0;
                        // fetch
                        while($r = $q->fetch()){
                          $trackID = $r['TrackID'];
                        }

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

			 $sql = "SELECT Thread.TNumberID, TNumber.TNumber, Thread.id, Thread.ThreadDescription, Thread.mp3Name, Thread.DestNumber, Thread.ActionType,Thread.ChildThreadID, Thread.StartTimeHour, Thread.StartTimeMinute, Thread.StopTimeHour, Thread.StopTimeMinute, Thread.FrequencyMinutes from Thread, TNumber where id = ? and TNumber.TNumberID = Thread.TNumberID  ";
 

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
			 $sql = "SELECT TNumber.TNumberID, TNumber.TNumber, Thread.id, Thread.ThreadDescription, Thread.mp3Name, Thread.DestNumber, Thread.ActionType,Thread.ChildThreadID, Thread.StartTimeHour, Thread.StartTimeMinute, Thread.StopTimeHour, Thread.StopTimeMinute, Thread.FrequencyMinutes from Thread, Groups, Number, GroupNumber, TNumber where Thread.DestNumber = Groups.GroupID and Groups.GroupID = GroupNumber.GNGroupID and Number.NumberID = GroupNumber.GNNumberID and Number.NumberID = ? and Thread.TNumberID = TNumber.TNumberID  order by Thread.FrequencyMinutes";

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
                         $sql = "SELECT TNumber.TNumberID, TNumber.TNumber, Thread.id, Thread.ThreadDescription, Thread.mp3Name, Thread.DestNumber, Thread.ActionType,Thread.ChildThreadID, Thread.StartTimeHour, Thread.StartTimeMinute, Thread.StopTimeHour, Thread.StopTimeMinute, Thread.FrequencyMinutes from Thread,  TNumber where Thread.DestNumber = ?  and Thread.TNumberID = TNumber.TNumberID  order by Thread.FrequencyMinutes";

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


		function getTwilioNumberByNumber($number) {
                        $objNumber = new TwilioNumber;

                        $sql = "SELECT TNumberID, TNumberName  FROM TNumber  WHERE TNumber = ?";
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
                        }

                        #if we dont know the number - add it.
                        if ($numberID == 0) {

                                $sql = "INSERT into TNumber (TNumber, TNumberName, IsActive) values(?,?,1)";
                                $q = $this->db->prepare($sql);
                                $q->execute(array($number,'unknown twilio number'));


                                #and get the new numberID
                                $sql = "SELECT TNumberID, TNumberName, IsActive  FROM TNumber  WHERE TNumber = ?";
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
                                }
                        }

                        $objNumber->TwilioNumberID = $numberID;
                        $objNumber->TwilioNumber = $number;
                        $objNumber->TwilioNumberName = $numberName;
			$objNumber->IsActive = $isActive;

                        return $objNumber;

		}
		function getPhoneNumberByNumber($number) {
			$objNumber = new PhoneNumber;

			$sql = "SELECT NumberID, NumberDescription  FROM Number  WHERE Number = ?";
			$q = $this->db->prepare($sql);
			$q->execute(array($number));

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
				$q = $this->db->prepare($sql);
				$q->execute(array($number,'unknown inbound number'));


				#and get the new numberID
				$sql = "SELECT NumberID, NumberDescription  FROM Number  WHERE Number = ?";
				$q = $this->db->prepare($sql);
				$q->execute(array($number));

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

			return $objNumber;

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





	}


	class PhoneNumber {
		public $NumberID;
		public $Number;
		public $NumberDescription;
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

	}

	class ActionType {
		public static $InboundMp3Action = 6;
		public static $InboundTextAction = 5;

		public static $InboundSMSAction = 9;
		public static $DialToneActionType=10;
		public static $KickOffActionType=11;
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
	}

?>
