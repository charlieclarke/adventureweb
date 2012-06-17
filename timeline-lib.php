<?php
	class DB {

		protected $db;

		function __construct($path) {
			$this->db = new PDO('sqlite:'.$path);
		}

		function init() {
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
		function insertToTimeLineOffset($threadID, $offsetMinutes, $additionalNumberID,$notes) {

			$sql = "INSERT INTO TimeLine (ThreadId, ActivityTime, Completed, CompletedTime, Description, Notes, AdditionalNumberID) values( $threadID ,datetime('now','+$offsetMinutes minutes'),0,NULL,'$notes',NULL,$additionalNumberID)";


			echo("<!-- insertToTImeLineOffset: sql is " . $sql . "-->");
			$count = $this->db->exec($sql);
			echo("<!-- sql done " . $count . "rows -->");





		}

		function getThreadByThreadID($threadID) {

			 $sql = "SELECT Thread.id, Thread.ThreadDescription, Thread.mp3Name, Thread.DestNumber, Thread.ActionType,Thread.ChildThreadID, Thread.StartTimeHour, Thread.StartTimeMinute, Thread.StopTimeHour, Thread.StopTimeMinute, Thread.FrequencyMinutes from Thread where id = ?  ";
 

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

			 $sql = "SELECT Thread.id, Thread.ThreadDescription, Thread.mp3Name, Thread.DestNumber, Thread.ActionType,Thread.ChildThreadID, Thread.StartTimeHour, Thread.StartTimeMinute, Thread.StopTimeHour, Thread.StopTimeMinute, Thread.FrequencyMinutes from Thread, Groups, Number, GroupNumber where Thread.DestNumber = Groups.GroupID and Groups.GroupID = GroupNumber.GNGroupID and Number.NumberID = GroupNumber.GNNumberID and Number.NumberID = ?  order by Thread.FrequencyMinutes";

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

	}

	class ActionType {
		public static $InboundMp3Action = 6;
		public static $InboundTextAction = 5;

		public static $InboundSMSAction = 9;
		public static $DialToneActionType=10;
	}

?>
