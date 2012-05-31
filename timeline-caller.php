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

	$db = new PDO('sqlite:/var/cache/timeline/timeline.db');

?>
<Response>
<Pause length="2"/>
<Say>Hello Tassos</Say>
<Play>http://ec2-176-34-195-123.eu-west-1.compute.amazonaws.com/mp3/fortgreen.mp3</Play>
</Response>


