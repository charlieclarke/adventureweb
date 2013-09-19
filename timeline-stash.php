<html>
<head>

<script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>

<link rel='stylesheet' type='text/css' href='default.css' />
<title>STASH CONSOLE</title>
<script>
$(document).ready(function(){

	    var table = $('#ttable');
    
    $('#description_header, #q_header')
        .wrapInner('<span title="sort this column"/>')
        .each(function(){
            
            var th = $(this),
                thIndex = th.index(),
                inverse = false;
            
            th.click(function(){
                
                table.find('td').filter(function(){
                    
                    return $(this).index() === thIndex;
                    
                }).sortElements(function(a, b){
                    
                    return $.text([a]) > $.text([b]) ?
                        inverse ? -1 : 1
                        : inverse ? 1 : -1;
                    
                }, function(){
                    
                    // parentNode is the element we want to move
                    return this.parentNode; 
                    
                });
                
                inverse = !inverse;
                    
            });
                
        });

});
</script>

</head>
<body>
<?php
require_once('timeline-lib.php');

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
	$triggerAction = $_GET['TRIGGER'];
	
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

$tdb = new DB($db_location);
        $tdb->init();

	#perform actions etc.

	if ($crudAction == 'DELETESTASH') {


		echo "<!-- delete number-->";
		 $sql = "DELETE FROM Stash"; 
		$st = $db->prepare($sql);
		$st->execute();

		echo "<!-- delete number-->";


	}

if ($triggerAction == 'KICKOFFGROUP') {
                $triggerDate= $_GET["INSERTTIME"];
                $groupID = intval($_GET['GroupID']);
                $threadID = intval($_GET['ThreadID']);

                echo "<!-- kick off group: threadID = $threadID groupID = $groupID-->\n";
                if ($threadID > 0) {
                        #we have a valid kick to insert
                        #get all numbers in the group.

                        $objNumberArray = $tdb->getPhoneNumbersByGroupID($groupID);
                        foreach($objNumberArray as $objNumber) {
                                $tdb->insertToTimeLineTime($threadID, $triggerDate, $objNumber->NumberID,"sent from monitor page as part of group $groupID");
                        }

                }
        }


	#render

	 #top menu bar
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
        echo("<div class='menuBar'><a href=$base_url/timeline-monitor.php>Monitor and Manager Threads</a>&nbsp;|&nbsp;<a href=$base_url/timeline-groups.php>Manage Numbers and Groups</a>&nbsp;|&nbsp;<a href=$base_url/timeline-stash.php>STASH Console</a>&nbsp;|&nbsp;$instance_name&nbsp;|&nbsp;$heartBeatText</div>");

        echo("<br><br>");

	echo("<div id='outer' width=700>");
	echo("<div id='left' style='display: inline;float: left;'>");
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

	echo("<div class='tableTitle'>STASH</div><br><div class='tableDescription' width=250px>Here we can see any stashed info.</div><br>");

echo("<form action='" . $this_page . "' method='get'>");  echo "<input name='CRUD' value='DELETESTASH' type='hidden'>\n\n";
echo "<input type='submit' value='DELETE the STASH'>";

	$sql = 'SELECT NumberDescription, StashKey, StashValue from Stash join Number on Number.NumberID = Stash.NumberID order by StashKey, NumberDescription' ;
	$result = $db->query($sql);

	echo("<table id = 'ttable'>");
	echo("<tr><th id = 'description_header'>Number</th><th id='q_header'>Key</th><th>Value</th></tr>");

	$rownum = 0;
	$rowarray = $result->fetchall(PDO::FETCH_ASSOC);
	foreach($rowarray as $row)
	{
		$rowstyle = ($rownum++ % 2)==0?"d0":"d1";
		$currentDisplayNumber = $row['NumberID'];

		echo "<tr class='" .$rowstyle . "'>";

		echo "<td>$row[NumberDescription]</td><td>$row[StashKey]</td><td>$row[StashValue]'</td>";
		echo "</tr>";
	}
	echo "</table>";
o("</div>");
	echo("</div>"); //the outermost div
?>

</body>
</html>