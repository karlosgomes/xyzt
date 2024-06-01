<?
/* ***** PASSWORDS ***** */
$passwords = array ( 
  "dummy1" => "removeme",
  "dummy2" => "removemetoo",
  "dummy3" => "andmeaswell",
  "caldeira" => "moita",
  "carlos" => "karlos"
);

/* ***** RESTRICTIONS ***** */
/* if a user is not supposed to see all other
 * users then add his/her name to this list
 * and give the usernames of all visible friends
 * as an array. only these users may then be
 * challenged. */
$user_restr = array(
 "dummy1" => array( "dummy2" ),
 "dummy2" => array( "dummy1", "dummy3" ),
 "caldeira" => array( "carlos"),
 "carlos" => array( "caldeira")
);
?>
