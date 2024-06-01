<?
/* clear any post/get input of identification */
$username = ""; 

/* if your server uses load balancing or anything that
 * causes session variables to be mixed up you can provide
 * a fixed location (physical directory) here. must have
 * full execute/read/write access! */ 
//session_save_path( "/home/allusers/myself/htdocs/tmp" );
session_start();
if ( isset( $_SESSION["username"] ) )
  $username = $_SESSION["username"];
?>
