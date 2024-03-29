<?php
//===========================================================================
//* --    ~~                Crafty Syntax Live Help                ~~    -- *
//===========================================================================
//           URL:   http://www.craftysyntax.com/    EMAIL: livehelp@craftysyntax.com
//         Copyright (C) 2003-2009 Eric Gerdes   (http://www.craftysyntax.com )
// ----------------------------------------------------------------------------
// Please check http://www.craftysyntax.com/ or REGISTER your program for updates
// --------------------------------------------------------------------------
// NOTICE: Do NOT remove the copyright and/or license information any files. 
//         doing so will automatically terminate your rights to use program.
//         If you change the program you MUST clause your changes and note
//         that the original program is Crafty Syntax Live help or you will 
//         also be terminating your rights to use program and any segment 
//         of it.        
// --------------------------------------------------------------------------
// LICENSE:
//     This program is free software; you can redistribute it and/or
//     modify it under the terms of the GNU General Public License
//     as published by the Free Software Foundation; 
//     This program is distributed in the hope that it will be useful,
//     but WITHOUT ANY WARRANTY; without even the implied warranty of
//     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//     GNU General Public License for more details.
//
//     You should have received a copy of the GNU General Public License
//     along with this program in a file named LICENSE.txt .
//===========================================================================
require_once("visitor_common.php");

$where="";
if(empty($UNTRUSTED['offset'])){ $UNTRUSTED['offset'] = ""; }
if(empty($UNTRUSTED['department'])){ $UNTRUSTED['department'] = ""; }
if(empty($UNTRUSTED['printit'])){ $UNTRUSTED['printit'] = ""; } 
if(!(empty($UNTRUSTED['department']))){ $where = " WHERE recno=". intval($UNTRUSTED['department']); }
if(empty($UNTRUSTED['timeof'])){ $UNTRUSTED['timeof'] = 0; } 
if(empty($UNTRUSTED['tab'])){ $UNTRUSTED['tab'] = "";  }

//department information:
$sqlquery = "SELECT * FROM livehelp_departments $where ";
$data_d = $mydatabase->query($sqlquery);  
$department_a = $data_d->fetchRow(DB_FETCHMODE_ASSOC);
$department = $department_a['recno'];
$topbackground = $department_a['topbackground']; 
$colorscheme = $department_a['colorscheme']; 
$colorscheme = $department_a['colorscheme']; 
$theme   = $department_a['theme']; 

$midbackground = $department_a['midbackground']; 
$midbackcolor = $department_a['midbackcolor']; 

$timeout = $department_a['timeout']; 
$dieat = date("YmdHis", mktime( date("H"), date("i"), date("s")+($timeout*2), date("m"), date("d"), date("Y")) );

//user information:
$sqlquery = "SELECT * FROM livehelp_users WHERE sessionid='".$identity['SESSIONID']."'";	
$people = $mydatabase->query($sqlquery);
$people = $people->fetchRow(DB_FETCHMODE_ASSOC);
$myid = $people['user_id'];
$channel = $people['onchannel'];
$isnamed = $people['isnamed'];
$username = $people['username']; 
 
// see if anyone is online . if not send them to the leave a message page..
$sqlquery = "SELECT * 
          FROM livehelp_users,livehelp_operator_departments 
          WHERE livehelp_users.user_id=livehelp_operator_departments.user_id
            AND livehelp_users.isonline='Y'
            AND livehelp_users.isoperator='Y' 
            AND livehelp_operator_departments.department=".intval($department);
$data = $mydatabase->query($sqlquery);  
if($data->numrows() == 0){
 ?>
   <SCRIPT type="text/javascript" >
    window.parent.location.replace("livehelp.php?tab=1&doubleframe=yes&pageurl=offline.php&department=<?php echo $department; ?>");       
    </SCRIPT>
 <?php
  if(!($serversession))
    $mydatabase->close_connect();
  exit;
} 

// see if someone is talking to this user on this channel if so send to chat:
$sqlquery = "SELECT * FROM livehelp_operator_channels WHERE channel=" . intval($channel) ." AND userid=".intval($myid) . " LIMIT 1";
$counting = $mydatabase->query($sqlquery);

// update the status of the use to chat:
$rightnow = date("YmdHis");

  $sqlquery = "UPDATE livehelp_users
            SET chataction=$rightnow,status='chat' 
            WHERE user_id=".intval($myid);            
  $mydatabase->query($sqlquery); 
 
      $sqlquery = "UPDATE livehelp_users SET chataction=$rightnow,status='chat' WHERE sessionid='".$identity['SESSIONID']."'";
      $mydatabase->query($sqlquery);  
      
// get chatmode:
if(empty($CSLH_Config['chatmode'])) 
   $CSLH_Config['chatmode'] = "flush-xmlhttp-refresh";

// see if any operators are on mobile online..
$sqlquery = "SELECT * 
          FROM livehelp_users,livehelp_operator_departments 
          WHERE livehelp_users.user_id=livehelp_operator_departments.user_id
            AND livehelp_users.isonline='Y'
            AND livehelp_users.isoperator='Y' 
            AND (livehelp_users.ismobile='Y' OR livehelp_users.ismobile='P')
            AND livehelp_operator_departments.department=".intval($department);
$ops = $mydatabase->query($sqlquery);  
 
  while($daoperator = $ops->fetchRow(DB_FETCHMODE_ASSOC)){
      $lastcalled = $daoperator['lastcalled'];
      $cellphone = $daoperator['cellphone'];  
      $cell_invite = $daoperator['cell_invite'];  
      $ismobile = $daoperator['ismobile'];  
      
   	  if($cell_invite=="Y" && ((date("YmdHis") - $lastcalled)>150)){
   	    $sqlquery = "UPDATE livehelp_users SET lastcalled='".date("YmdHis")."' WHERE user_id ='".$daoperator['user_id']."'";	
        $mydatabase->query($sqlquery);
        $headers = "MIME-Version: 1.0" . $newline;
        $headers .= "Content-type: $contenttype; charset=$charset" . $newline;
        $headers .= "X-Mailer: php" . $newline;
        if($ismobile == "Y"){
          $url = "Chat request: \n " . $CSLH_Config['webpath'] . "/mobile/requests.php";
        } else {
          $url = "\n A Chat request has come in ! \n Open the iphone app and answer the chat! ";
        }
                  
        mail($cellphone, '',$url, $headers);
   	  }
   }


$chatmodes = explode('-',$CSLH_Config['chatmode']);
switch ($chatmodes[0]){
	 case "xmlhttp":
      $page = "is_xmlhttp.php";
      break;
	 case "flush":
      $page = "is_flush.php";
      break;
   default:
      $page = "user_chat_refresh.php";
      break;
 }      

   
if( $counting->numrows() != 0){
    ?>
    <SCRIPT type="text/javascript">
       window.location.replace("<?php echo $page; ?>?try=0&scriptname=user_chat&department=<?php echo $department; ?><?php echo $querystringadd; ?>"); 	 
    </SCRIPT>
 <?php
if(!($serversession))
  $mydatabase->close_connect();
  exit;
} 

?>



<SCRIPT type="text/javascript">
function connect_user(){
  window.parent.location.replace("livehelp.php?connect_user=1&try=0&scriptname=user_chat&department=<?php echo $department; ?><?php echo $querystringadd; ?>"); 	 
}
 
function timeout_user(){
   window.parent.location.replace("livehelp.php?doubleframe=yes&pageurl=offline.php&department=<?php echo $department; ?>");       	
}

function csgetimage(){	 
	setTimeout('csgetimage()', 2000);
	imageloaded = 1;
	 // set a number to identify this page .
	 csID=Math.round(Math.random()*9999);
	 randu=Math.round(Math.random()*9999);
   
   cscontrol = new Image;      	 
	 var u = 'image.php?randu=' + randu + '&department=<?php echo $UNTRUSTED['department']; ?>&what=channelcheck&cslhVISITOR=<?php echo $identity['SESSIONID']; ?>&dieat=<?php echo $dieat; ?>';
	 
	 if (ismac > -1){
       document.getElementById("imageformac").src= u;
       document.getElementById("imageformac").onload = lookatimage;
    } else {
       cscontrol.src = u;
       cscontrol.onload = lookatimage;
    }   
    
}
function refreshit(){
  window.location.replace("user_connect.php");
}

function lookatimage(){
	
	if(typeof(cscontrol) == 'undefined' ){
		setTimeout('refreshit()',9000); 
    return; 
 }  
   
	 	if (ismac > -1)
      w = document.getElementById("imageformac").width;
    else
      w = cscontrol.width;      	  

    if(w == 55){
           delete cscontrol;
           imageloaded = 0; 	
  	       connect_user();
	   } 	        	            
    if(w == 101){
       timeout_user();
    }
     delete cscontrol;            
     imageloaded = 0;      
} 

 

var ismac = navigator.platform.indexOf('Mac');	
var csTimeout = 299; 
var imageloaded = 0;
var cscontrol = new Image;
   setTimeout('csgetimage()', 2000);
</SCRIPT>

<?php 
include "themes/$theme/connecting.php";
?>
<img id="imageformac" name="imageformac" src=images/blank.gif border="0">
<?php

if(!($serversession))
    $mydatabase->close_connect();
    exit;
?>
</body>
</html>