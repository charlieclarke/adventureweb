<html>
<head>
<link rel='stylesheet' type='text/css' href='default.css' />
<title>scene management page</title>
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
    header("content-type: text/html");

	$secret = $_GET["secret"];
	$crudAction = $_GET['CRUD'];
	
	$local_secret = $ini_array['sharedSecret'];
	$db_location = $ini_array['databasepath'];
	$base_url = $ini_array['phpServer'];
	$instance_name = $ini_array['instanceName'];
	
	$this_url = $base_url . "/timeline-scene.php";
	
 require_once('timeline-lib.php');

        $tdb = new DB($db_location);


#beginning of the login code
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Text to send if user hits Cancel button';
    exit;
} else {
        $login=0;

        $username = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];

        $clone = $tdb->getCLoneByUser($username, $password);


        if ($clone->CloneID >= 0) {
                $login=1;
        }
        if ($login == 0) {
                header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Text to send if user hits Cancel button';
    exit;
        }

}
#end of the login code

		

	#perform actions etc.

		
	if ($crudAction == 'CREATETSCENE') {
		$newSceneName = $_GET["NewSceneName"];

		echo("<!--about to call create scene--!>");

		$tdb->createScene($newSceneName, $clone->CloneID);


	}
	if ($crudAction == 'DELETESCENE') {


		echo "<!-- delete scene-->";
		$numberID = intval($_GET["SceneID"]);

#security todo: make sure the user owns the sceneID
		#$tdb->deleteScene($sceneID);

	}
	$updateSceneID = 0;
	if ($crudAction == 'EDITSCENE') {

		#just marking which number to edit

		$updateSceneID = intval($_GET["SCENEID"]);

	} 

	if ($crudAction == "UPDATESCENE") {

		$updateSceneID = $_GET["UpdateSceneID"];
		$updateSceneName = $_GET["UpdateSceneName"];

#security todo: make sure the user owns the sceneID
		$tdb->updateSceneAllFields($updateSceneID, $updateSceneName,$clone->CloneID);
	

		$updateSceneID = 0;
	}



	#render

	 #top menu bar
#get last heartbeat from db


        echo($tdb->renderMenuBar($base_url, $instance_name));
        echo("<br><br>");


	echo("<div class='tableTitle'>Scene Management</div><br><div class='tableDescription' width=250px>Here we can manage our scenes. note - one twilio account per scene!</div><br>");

	$scenes = $tdb->getAllScenes($clone->CloneID);


	echo("<form action='" . $this_page . "' method='get'>");

        echo "<input type='hidden' name='secret' value='" . $local_secret . "'/>";

	echo("<table>");
	echo("<tr><th>SceneID</th><th>Scene Name</th><th></th></tr>");
	
	$rownum=0;
        foreach($scenes as $scene)
        {
		$rowstyle = (++$rownum % 2)==0?"d0":"d1";
		
		echo "<tr class='" .$rowstyle . "'>";

		if ($updateSceneID > 0) {
			if ($scene->SceneID  == $updateSceneID) {
				#show edit box
				echo "<input type='hidden' name='UpdateSceneID' value='" . $updateSceneID . "'/>";
				echo "<td>$scene->SceneID</td>";
				echo "<td><input type='text' name='UpdateSceneName' value='$scene->SceneName'/></td>";

				echo "<td><input type='submit' name='Update' value='ok' />";

				echo "<input type='hidden' name='CRUD' value='UPDATESCENE'/>";

				

			} else {
				#show row without crud
				echo "<td>$scene->SceneID</td><td>$scene->SceneName</td><td>-</td>";
	
			}
		} else {
			#show row with crud
		echo "<td>$scene->SceneID</td><td>$scene->SceneName</td><td><a href='$this_url?secret=$secret_local&CRUD=EDITSCENE&SCENEID=$scene->SceneID'>edit</a>|<a href='$this_url?secret=$secret_local&CRUD=DELETESCENE&SCENEID=$scene->SceneID'>delete</a></td>";
		}
                echo "</tr>";
        }
	if ($updateSceneID == 0) {
		echo "<input type='hidden' name='CRUD' value='CREATETSCENE'/>";
		echo "<tr class='" .$rowstyle . "'>";
		echo "<td></td>";
		echo "<td><input type='text' name='NewSceneName' value='Scene Name'/></td>";

		echo "<td><input type='submit' name='Add' value='Add' />";
		echo "</tr>";
	}
	echo "</table>";

	echo "</form>"; #end of number mgmt form

	echo("</div>"); #end of the number mgmt div

?>

</body>
</html>
