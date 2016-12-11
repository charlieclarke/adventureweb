<?php
  $base_url = "http://aws.com"; 


                       #top menu bar

                        $pattern = "/http:\/\/(.*)/";

                        $replacement = "http://log:out@$1";
                        $logout_url = preg_replace($pattern, $replacement, $base_url);

echo $logout_url;

?>


