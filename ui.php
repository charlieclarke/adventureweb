<html>
<head>
<link rel='stylesheet' type='text/css' href='default.css' />
<title>call monitoring page</title>


 <script src="http://code.jquery.com/jquery-1.9.1.js"></script>
  <script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
  <script src="js/jquery.jsPlumb-1.4.1-all-min.js"></script>

  <link rel="stylesheet" href="/resources/demos/style.css" />
  <style>
  #draggable { width: 150px; height: 150px; padding: 0.5em;border: 1px solid black; }
  #draggable2 { width: 150px; height: 150px; padding: 0.5em;border: 1px solid black; }
  </style>
<style>  
        .ui-widget-content {   
            background-color: #EEEEEF;  
            border: 1px solid #346789;  
            border-radius: 0.5em;  
            box-shadow: 2px 2px 19px #AAAAAA;  
            color: black;  
            height: 5em;  
            position: absolute;  
            width: 5em;  
            cursor: pointer;  
        }  
    </style>  
  <script>
  $(function() {
    //$( "#draggable" ).draggable();


	function makeNewBox() {
		$( "<div><p>Hello</p></div>" ).draggable().appendTo( "body" )

	}


	$('#makenew').click(makeNewBox);
  });

jsPlumb.bind("ready", function() {
	jsPlumb.draggable("draggable"); 
	jsPlumb.draggable("draggable2"); 
        var e0 = jsPlumb.addEndpoint("draggable"),
        e1 = jsPlumb.addEndpoint("draggable2");

        jsPlumb.connect({ source:e0, target:e1, anchor:[ "Perimeter", { shape:"Circle" } ] ,connector:[ "Flowchart"] });
     });


  </script>


</head>

<body>
<?php
$machinename =  gethostname();
        if (preg_match("/local/i",$machinename)) {
                $configfile = "/var/tmp/config.local";
        } else {
                $configfile = "/var/cache/timeline/config.local";
        }

         $ini_array = parse_ini_file($configfile);
?>
<?php
$username = $ini_array['userID'];
$password = $ini_array['password'];


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
	$crudAction = $_GET['CRUD'];
	
	$local_secret = $ini_array['sharedSecret'];
	$db_location = $ini_array['databasepath'];
	$base_url = $ini_array['phpServer'];
	$instance_name = $ini_array['instanceName'];
	
	$this_url = $base_url . "/timeline-groups.php";
	

	#if ($secret != $local_secret) {
#		header("HTTP/1.0 401 Unauthorized");
#		exit();
#	}
		
	#init database
	$db = new PDO('sqlite:'.$db_location);


	#perform actions etc.


	#render

	 #top menu bar
#get last heartbeat from db


        $result = $db->query("SELECT HeartBeatTime, strftime('%s','now') - strftime('%s',HeartBeatTime) as LastHeartBeatAgo FROM HeartBeat where HeartBeatName='LastTimeLine'");

        $rowarray = $result->fetchall(PDO::FETCH_ASSOC);

        $lastHeartBeat = 'never';
        $lastHeartBeatAgo = -100;
        foreach($rowarray as $row)
        {

                $lastHeartBeat = $row['HeartBeatTime'];
                $lastHeartBeatAgo = $row['LastHeartBeatAgo'];
        }

        if ($lastHeartBeatAgo < 2) {
                $heartBeatText = "TimeLine Active and OK - $lastHeartBeat";
        } else {
                $heartBeatText = "TimeLine Appears Down - $lastHeartBeat";
        }

        #render page

        #top menu bar
        echo("<div class='menuBar'><a href=$base_url/timeline-monitor.php>Monitor and Manager Threads</a>&nbsp;|&nbsp;<a href=$base_url/timeline-groups.php>Manage Numbers and Groups</a>&nbsp;|&nbsp;$instance_name&nbsp;|&nbsp;$heartBeatText</div>");

        echo("<br><br>");


?>

<div id="draggable" class="ui-widget-content">
  <p>Drag me around</p>
</div>
<br><br>

<div id="draggable2" class="ui-widget-content">
  <p>Drag me around too</p>
</div>

<script>
</script>

<a id=makenew>Make New</a>


</body>
</html>
