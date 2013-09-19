<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<?php
#header("Cache-Control: no-cache, must-revalidate");

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
	
	$this_url = $base_url . "/SIMClient.php";
	
	$poll_secs = 1500;

?>
<?php 
		echo "<!-- we iare in the PHP vode -->";
	#init database

	require_once('timeline-lib.php');
		echo "<!-- igot lib -->";
	$tdb = new DB($db_location);

		echo "<!-- we have a DB connection -->";

	$guidCookie = "";
	if (array_key_exists("LOGOUT", $_GET)) {
                echo "<!-- we have a call to logout -->";
		setcookie("SIMCOOKIE","", time() - 3600,null,null,null,true);
		//setcookie("SIMCOOKIE","", time() - 3600);
		$guidCookie="";
		$guidNumber = 0;

        } else if (array_key_exists("REGISTERNUMBER", $_GET)) {
                echo "<!-- we have a call to register number -->";
                $desc = strtoupper($_GET["Description"]);

                $guid = $tdb->registerSIMDevice($desc);

                echo "<!-- we have got guid $guid -->";
		setcookie("SIMCOOKIE",$guid, null,null,null,null,true);
		//setcookie("SIMCOOKIE",$guid,time() + 7200);
		$guidCookie=$guid;
		echo"\n\n<!--setting guidCookie to $guidCookie in register number block from regusterSIMDevice api-->\n\n";
        } else {


		#is there a cookie with a GUID in it?
		$guidCookie = $_COOKIE["SIMCOOKIE"];
		echo"\n\n<!--setting guidCookie to $guidCookie in normal block from cookie-->\n\n";
		
	}
?>
<title>Mobile Client Page</title> 
	<meta name="viewport" content="width=device-width, initial-scale=1"/> 
<!--
	<link rel="stylesheet" href="css/jquery.mobile-1.2.1.min.css" />
	<script src="<?php echo $base_url?>js/jquery-1.8.3.min.js"></script>
	<script src="<?php echo $base_url?>js/jquery.mobile-1.2.1.min.js"></script>
-->

<link rel="stylesheet" href="http://code.jquery.com/mobile/1.3.2/jquery.mobile-1.3.2.min.css" />
<script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
<script src="http://code.jquery.com/mobile/1.3.2/jquery.mobile-1.3.2.min.js"></script>



<link rel='stylesheet' type='text/css' href='default.css' />


<script>

function sendMessage(str, guid)
{
//new
	$('#sendBtn').val('Sending...').button('refresh');
	$.ajax({
                type: "GET",
                dataType: "text",
                data: {To: "SIM_1",GUID: guid, Body: str},
                url: "<?php echo $base_url; ?>timeline-inboundSIM.php",
		success: function(xml){
			$('#sendBtn').val('Send').button('refresh');	
                }
        });




//old 
//	$('#sendBtn').val('Sending...').button('refresh');
 // xmlhttp=new XMLHttpRequest();
//xmlhttp.onreadystatechange=function()
  //{
	//$('#sendBtn').val('Send').button('refresh');
  //if (xmlhttp.readyState==4 && xmlhttp.status==200)
    //{
    //location.reload();
   // }
 // }
//theurl="<?php echo $base_url; ?>timeline-inboundSIM.php?To=SIM_1&GUID="+guid+"&Body="+str;

//xmlhttp.open("GET",theurl,true);
//xmlhttp.send();
}


function messageSow(guid) {
	$.ajax({
		type: "GET",
		dataType: "xml",
		data: {action: "SOW"},
		url: "<?php echo $base_url; ?>timeline-svcSIM.php",
		success: function(xml){
			$("#messageList").empty();
			id = "";
			txt = "";
			//$(xml).find("Response>Message").each(function(){
			$(xml).find("Response>Message").each(function(){
				id = $(this).find("SIMID").text();
				txt = $(this).find("SIMTxt").text()
				//id = 1; txt="hshshshs";
				$("#messageList").empty();

			if (txt.indexOf("HIDE") >= 0) {
                                $('#responseForm').hide("slow");
                                $dopop = 0;
                                
                        } else {
                                $('#responseForm').show("slow");
                        }

			txt = txt.replace(/HIDE/g, "");
			txt = txt.replace(/\*\*/g,"<br/>");

			//$("#messageList").prepend("<li style='font-size:15px'>" + txt + "</li>");
			$("#messageList").html( txt );
		});
	}
});



}

function messageSupress(guid) {
$.ajax({
	type: "GET",
	dataType: "xml",
	data: {action: "SUPRESS"},
	url: "<?php echo $base_url; ?>timeline-svcSIM.php",
	success: function(xml){
		messageSow(guid);
	}
});



}


</script>
<script type="text/javascript">

//global variable for which scrren
gScreenMode = "none";
gInpopUp = 0;
gMsgTID = 0;
gNumChecks = 0;
function getMessageAndPopup() {
gNumChecks++;
//if (gScreenMode == "message" && gInpopUp == 0) {
if (gScreenMode == "message" ) {
	$.ajax({
	  type: "GET",
	  dataType: "xml",
	  data: {action: "GETNEW"},
	  url: "<?php echo $base_url; ?>timeline-svcSIM.php",
	  success: function(xml){
		$(xml).find("Response>Message").each(function(){


			if (gInpopUp == 1) {
				$("#popupNewMsg").popup("close") 
				//gInpopUp = 0;	
			}

			id = $(this).find("SIMID").text();
			txt = $(this).find("SIMTxt").text()


			//since we have a quesiton, lets show the response form
			//note - we need to filter this somehow..
			$dopop = 1;
			if (txt.indexOf("HIDE") >= 0) {
				$('#responseForm').hide("slow");
				$dopop = 0;
				
			} else {
				$('#responseForm').show("slow");
			}


			txt = txt.replace(/HIDE/g, "");

			//id = 1; txt="hshshshs";
			//$("#popupNewMsgTxt").html(id + ' ' + txt);
		//	$("#popupNewMsgTxt").html(txt.replace(/\*\*/g,"<br/>"));
				$("#popupNewMsgSIMID").val(id);

				if ($dopop == 1){ 
					gInpopUp = 1;	
					setTimeout(function(){$("#popupNewMsgTxt").html(txt.replace(/\*\*/g,"<br/>"));$("#popupNewMsg").popup("open");},1000);
				}
				//$("#popupNewMsg").popup("open");
				markMessageRcvd(id)
				messageSow();
			});
		  }
	 });
	}


}
function markMessageRcvd(simid) {

	$.ajax({
	  type: "GET",
	  dataType: "xml",
	  data: {action: "MARKRCVD",SIMID: simid},
	  url: "<?php echo $base_url; ?>timeline-svcSIM.php",
	  success: function(xml){
		
	  }
	 });


}


</script>



</head>
<body>

<div data-role="page">


	<div data-role="header" data-position="fixed">


		<h1 id='header-text' >Playful</h1>
<div data-role="header" data-position="fixed">
<a data-ajax="false" data-iconpos=notext href='<?php echo($this_url); ?>' data-icon="refresh">logout</a>
<a data-ajax="false" data-iconpos=notext data-icon="delete" href='<?php echo($this_url); ?>?LOGOUT' >
<span class="ui-btn-text">  </span>
</a>





	</div><!-- /header -->
	<!--<div data-role="footer" data-position="fixed"> 
		<h4>Messages</h4> 
                <a data-ajax="false" data-icon="cancel" href='<?php echo($this_url); ?>?LOGOUT' data-theme="a">
<span class="ui-btn-text">logout</span>
</a>
	</div>
-->

	<div data-role="popup" id="popupNewMsg" data-theme="a" data-overlay-theme="a" class="ui-content" > 
	<form><input type='hidden' id="popupNewMsgSIMID"/></form>
		<p id="popupMsgPreamble"></p><br/>
		<span id="popupNewMsgTxt"></span>
	</div><!--the popup div-->
	<div data-role="content" data-theme="d">	


<?php
	$messageArray = array();

	$objValidNumber = new PhoneNumber;
	if ($guidCookie != "" ) {
		//lets get the number associated with this GUID
		$guidNumber = $tdb->getPhoneNumberByGuid($guidCookie);
		$objValidNumber = $guidNumber;
		$guid = $guidCookie;
		if ($objValidNumber->NumberID > 0) {
			
		} else {
			$guidCookie = "";
			echo"\n\n<!--setting guidCookie to $guidCookie becuase invalud number-->\n\n";
		}
		echo("<!-- got guidCOokie of $guidCookie -->");
	}

	if ($guidCookie != "" ) {
		echo"\n\n<!--guidcookie is not blank it is $guidCookie so drawign table-->\n\n";
		echo("<table>");
		echo("<tr>");
		echo("<td>"); //the main col
		//echo("<div style='font-size:20pt;font-family:Georgia'>Hello Agent $objValidNumber->NumberDescription <!--($objValidNumber->NumberID) $guid--></div><br/><div  >Messaging System.</div><br/>");
		$agentName= $objValidNumber->NumberDescription;

		//the table where we show any messages recieved
//echo("<ul id=\"messageList\" data-role=\"listview\">\n");
echo("<span id=\"messageList\" style=\"font-size:12px\">\n");

//echo("<li>item</li>");
//echo("</ul>\n");
echo("</span>\n");

		//echo("<label for=\"basic\">Message</label>\n");
		echo("<div id='responseForm'  style='display:none'>\n");

		echo("<form >\n");
		echo("<table><tr><td>\n");
		echo("<div   data-role=\"controlgroup\" data-type=\"horizontal\">\n");

		echo("<input type=\"radio\" name=\"answer\" id=\"answer-a\" value=\"ANSWER A\" />");
         	echo("<label for=\"answer-a\">A</label>");

		echo("<input type=\"radio\" name=\"answer\" id=\"answer-b\" value=\"ANSWER B\" />");
         	echo("<label for=\"answer-b\">B</label>");

		echo("<input type=\"radio\" name=\"answer\" id=\"answer-c\" value=\"ANSWER C\" />");
         	echo("<label for=\"answer-c\">C</label>");

		echo("<input type=\"radio\" name=\"answer\" id=\"answer-d\" value=\"ANSWER D\" />");
         	echo("<label for=\"answer-d\">D</label>");

		echo("</div>\n");
		echo("<input type=\"text\" name=\"name\" id=\"message\" data-mini=\"true\" value='comment...' />\n");
?>
<script>
$("#message").focus(function() {
  this.value = "";
});
</script>
<?PHP

		echo("\n\n</td><td>\n");
		echo("</td></tr><tr><td>\n");

		echo("<input type=button id='sendBtn' value='Done'  onclick=\"sendMessage($('[name=answer]:checked').val() + ' ' + $('#message').val(),'$guidCookie');$('#message').val('');$('[name=answer]:checked').prop('checked', false).checkboxradio('refresh');\">\n");
		#echo("<input type=button value='Send'  onclick=\"sendMessage(document.getElementById('message').value,'$guidCookie')\">\n");
		echo("</td></tr></table>");
		echo("</form>");

		echo("</div>\n");
		echo("<div><!--Messages--></div><br/><div> </div><br/>\n");

		$i=0;


		echo("</td>"); //the main col
		echo("</table>");

		//get any new messages etc.
		echo("<script>gScreenMode = 'message';</script>\n");
echo("<br/>\n");
echo("<br/>\n");
echo("<br/>\n");
echo("<br/>\n");
echo("<br/>\n");
echo("<br/>\n");
echo("<br/>\n");
echo("<br/>\n");
echo("<br/>\n");
echo("<br/>\n");
echo("<br/>\n");
echo("<br/>\n");
echo("<br/>\n");
echo("<br/>\n");
echo("<br/>\n");
echo("<br/>\n");
	} else {
		//we are in 'not logged in mode'

		//show the who are you screen
		echo"\n\n<!--guidcookie is blank  so drawign login screen-->\n\n";

		echo("<script>gScreenMode = 'agent';</script>\n");

		echo("<div class='tableTitle'>Who Are You?</div><br/><div class='tableDescription' width=250px>Please tell us your agent name.</div><br/>");

		echo("<div>"); //the who are you div
		echo("<form data-ajax=\"false\" action='" . $this_url . "' method='get'>");

		echo "<input type='hidden' name='secret' value='" . $local_secret . "'/>";
		echo "<input type='hidden' name='REGISTERNUMBER' value='yes'/>";
		echo "<input type='text' id='Description' name='Description' value='AGENT NAME'/>";
		echo "<input type='submit' name='Reg' value='Play' data-theme='a'/>";


		echo "</form>"; #end of reg GUID form
		echo "<br/><br/>\n";
		echo "<span style='font-size:8px'>Dislaimer Text: Get some legal stuff here.</span>\n";
?>
<script>
$("#Description").focus(function() {
  this.value = "";
});
</script>
<?PHP
	

		echo("</div>"); //end of the who are you div


	
	}
		
		

		


?>
	</div><!-- /content -->

</div><!-- /page -->


</body>
<script>
//alert('here');
$(document).ready(function(){
	//alert('ready');
	pollSecs = <?php echo($poll_secs); ?>;
	gMsgTID = setInterval(getMessageAndPopup, 1000);
	messageSupress("<?php echo($guidCookie); ?>");
	//messageSow("<?php echo($guidCookie); ?>");
	gInpopUp=0;

	if (gScreenMode=='message') {
		$('#header-text').text('Agent <?php echo($agentName); ?>');
	}

	$( '#popupNewMsg' ).on({
		popupafterclose: function() {
		gInpopUp = 0;
		}
	});	
});
</script>
</html>
