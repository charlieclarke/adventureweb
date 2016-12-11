<?php


	require_once('timeline-lib.php');

	$tdb = new DB('/var/cache/timeline//timeline.db');

	$number = $tdb->getPhoneNumberByNumber('+447971805821');

	print "number is $number->Number\n";
	print "number description is $number->NumberDescription\n";
	print "number ID is $number->NumberID\n";


	$defaultCall = $tdb->getDefaultThreadID('CALL');
	$defaultSMS = $tdb->getDefaultThreadID('SMS');

	print "default call = $defaultCall SMS = $defaultSMS\n";


	$threads = $tdb->getThreadsByPhoneNumberID($number->NumberID);

	foreach($threads as $thread) {

		print "Thread: $thread->ThreadDescription children $thread->ChildThreadText mp3 $thread->mp3Name \n";

		foreach ($thread->ChildThreads as $child) {

			print "\t$child\n";
		}



	}
	$thread = $tdb->getThreadByThreadID(2);

 print "Thread: $thread->ThreadDescription children $thread->ChildThreadText mp3 $thread->mp3Name \n";




	print "acitontype: " . ActionType::$DialToneActionType . " \n";


	$hb = $tdb->getHeartBeat();
	print "hb: $hb->LastHeartBeat ago: $hb->LastHeartBeatAgo \n";
	
	$tnum = $tdb->getAllTwilioNumbers();
	$tnum = $tnum[0];
	$tdb->updateTwilioNumber($tnum->TwilioNumberID, $tnum->TwilioNumber, $tnum->TwilioNumberName, $tnum->IsActive);
?>
