<?
/* ***** VERIFY SESSION ***** */
include "check_session.php";
if ( $username == "" )
  header( "location:index.php" );

/* ***** GAME FILE ***** */
if ( isset( $_POST ) && isset($_POST["gamefile"]) )
  $_SESSION["gamefile"] = $_POST["gamefile"];
else
if ( isset( $_GET ) && isset($_GET["gamefile"]) )
  $_SESSION["gamefile"] =  $_GET["gamefile"];
$gamefile = $_SESSION["gamefile"];
if (preg_match('/[^\w\-]/', $gamefile)) {$gamefile="invalid_name";}

/* ***** BOARD THEME ***** */
if ( isset($_GET["theme"]) )
  $_SESSION["theme"] = $_GET["theme"];
$theme = $_SESSION["theme"];
include "images/$theme/board.php";
?>

<script language="Javascript">
function assembleCmd( part )
{
  var cmd = window.document.cmdform.chessmove.value;
  if ( cmd == part )
    window.document.cmdform.chessmove.value = "";
  else
  if ( cmd.length == 0 || cmd.length >= 6 )
  {
    if ( part.charAt(0) != '-' && part.charAt(0) != 'x' )
      window.document.cmdform.chessmove.value = part;
  }
  else
  {
    if ( part.charAt(0) == '-' || part.charAt(0) == 'x' )
      window.document.cmdform.chessmove.value = cmd + part;
    else
      window.document.cmdform.chessmove.value = part;
  }
  return false;
}

function confirm_undo()
{
  if (confirm("Are you sure you want to undo your last move?"))
    return true; 
   else
    return false;
}
</script>

<HTML>

<HEAD>
<TITLE>Online Chess Club</TITLE>
<LINK rel=stylesheet type="text/css" href="style.css">
</HEAD>

<BODY bgcolor="#ffffff" link="" alink="" vlink="">

<TABLE width=100% border=0 cellspacing=0 ><TR><TD align="center">

<P><IMG alt="" src="images/logo.jpg"><BR><BR>
[ <A href="index.php">Overview</A> |
 <A href="browser.php?gamefile=<?=$gamefile?>">History Browser</A> |
 <A href="pgn.php?gamefile=<?=$gamefile?>">PGN Format</A> |
 <A href="help.php">Help</A> |
<A href="logout.php">Logout</A> ]
</P>

<TABLE width=<?=$t_main_table_width?> border=0 cellspacing=0 cellpadding=0><TR><TD valign="top">


<?
include "misc.php";

//$start = getMicrotime();

$browsing_mode = 0;
$ac_move = "";
$w_figures = array();
$b_figures = array();
$attackers = array();
$board = array( 
  "", "", "", "", "", "", "", "",
  "", "", "", "", "", "", "", "",
  "", "", "", "", "", "", "", "",
  "", "", "", "", "", "", "", "",
  "", "", "", "", "", "", "", "",
  "", "", "", "", "", "", "", "",
  "", "", "", "", "", "", "", "",
  "", "", "", "", "", "", "", "" );

/* ***** CHECK A SUBMITTED MOVE OR UNDO ***** */
$show_archive_button = "yes";
if ( isset( $_POST ) )
{
  include "handle_move.php";
  if ( isset( $_POST["undo_move"] ) )
    $move_result = handleUndo( $gamefile );
  else
  {
    $refresh_only = $_POST["refresh_only"];
    $close_action = $_POST["close_action"];
    if ( $_POST["draw_offered"] == "yes" )
    {
      if ( $_POST["refuse_draw"] == "Refuse" )
        $move_result = 
          handleMove( $gamefile, "refuse_draw", $chesscomment );
      else
        $move_result = 
          handleMove( $gamefile, "accept_draw", $chesscomment );
    }
    else
    if ( $close_action == "archive" )
    {
      /* only allow if user is active (which it may not be if
         undo function was used) */
      if ( file_exists( "$res_games/$gamefile" ) )
      {
        $game = file( "$res_games/$gamefile");
        $headline = explode(' ',trim($game[1]));
        if ( $headline[4] == '?' || $headline[4] == 'D' )
          $move_result = "The game is still active!";
        else
          if ( ($headline[3]=='w' && $username!=$headline[0]) ||
               ($headline[3]=='b' && $username!=$headline[1]) )
            $move_result = "It is not your turn!";
          else
          {
            rename( "$res_games/$gamefile", "$res_archive/$gamefile" );
            $move_result = "The game has been moved to archive.";
            $show_archive_button = "no";
            clearstatcache();
          }
      }
    }
    else
    if ( $close_action == "delete" )
    {
      /* only allow if user is active (which it may not be if
         undo function was used) */
      if ( file_exists( "$res_games/$gamefile" ) )
      {
        $game = file( "$res_games/$gamefile");
        $headline = explode(' ',trim($game[1]));
        if ( $headline[4] == '?' || $headline[4] == 'D' )
          $move_result = "The game is still active!";
        else
          if ( ($headline[3]=='w' && $username!=$headline[0]) ||
               ($headline[3]=='b' && $username!=$headline[1]) )
            $move_result = "It is not your turn!";
          else
          {
            $move_result = "The game has been deleted.";
            unlink( "$res_games/$gamefile" ); 
            clearstatcache();
          }
      }
    }
    if ( $refresh_only != "yes" )
    {
      $chessmove = $_POST["chessmove"];
      $chesscomment = $_POST["chesscomment"];
      if ( !empty($chessmove) )
      {
        $move_result = handleMove( $gamefile, $chessmove, $chesscomment );
      }
    }
  }
}

/* ***** LOAD GAME ***** */
$is_archived = 0;
if ( file_exists( "$res_games/$gamefile" ) )
  $game = file( "$res_games/$gamefile");
else
  if ( file_exists( "$res_archive/$gamefile" ) )
  {
    $show_archive_button = "no";
    $game = file( "$res_archive/$gamefile");
    $is_archived = 1;
  }
  else
  {
    /* game is screwed so give a dummy */
    $game = array( "0 0 0 0 0 0 0 0 0 0 0 0",
                   "nouser nouser 0 w - 0 0 0 0 x x",
                   "", "", 
                   "The game \"$gamefile\" does not exist!" );
    $is_archived = 1;
  }

/* ***** SPLIT HEADLINE ***** */
$headline = explode( " ", trim($game[1]) );

/* ***** COMPUTE WHETHER UNDO OKAY ***** */
$may_undo = 0;
if ( $headline[11] != 'x' && $is_archived == 0 )
if ( ($headline[3] == 'b' && $headline[0] == $username) ||
     ($headline[3] == 'w' && $headline[1] == $username) )
{
  $undo_time_left = timePassed( trim($game[0]) );
  if ( $undo_time_left < 1200 )
  {
    $may_undo = 1;
    $undo_time_left = 20 - floor($undo_time_left / 60);
  }
}

/* ***** BUILD HEADER ***** */
/* HEADER:
 * white_name black_name turn active_player:[wb]
 * status:[wb-?] w_short_castle_ok w_long_castle_ok
 * b_short_castle_ok b_long_castle_ok
 * w_long_pawn_move:[a-h] b_long_pawn_move:[a-h]
 *
 * init 2tile pawn move is only stored for one turn
 * to enable en passant rule */
$player_w = $headline[0];
$player_b = $headline[1];
$player_opp = $player_b; /* opponent */
$player_color = "w"; /* our color */
if ( $username==$player_b )
{
  $player_opp = $player_w;
  $player_color = "b";
}
$move_id = $headline[2]; /* current number of move */
$move_player = "White";
if ( $headline[3] == "b" )
  $move_player = "Black";
/*$white_castle_info = "White may still castle";
if ( $headline[5] == 0 && $headline[6] == 0 )
  $white_castle_info = "White <I>cannot</I> castle anymore.";
else if ( $headline[5] == 1 && $headline[6] == 1 )
  $white_castle_info = "$white_castle_info short and long.";
else if ( $headline[5] == 1 )
  $white_castle_info = "$white_castle_info short.";
else if ( $headline[6] == 1 )
  $white_castle_info = "$white_castle_info long.";
$black_castle_info = "Black may still castle";
if ( $headline[7] == 0 && $headline[8] == 0 )
  $black_castle_info = "Black <I>cannot</I> castle anymore.";
else if ( $headline[7] == 1 && $headline[8] == 1 )
  $black_castle_info = "$black_castle_info short and long.";
else if ( $headline[7] == 1 )
  $black_castle_info = "$black_castle_info short.";
else if ( $headline[8] == 1 )
  $black_castle_info = "$black_castle_info long.";*/
$disp_move_id = $move_id;
if ( $move_player == "White" )
  $disp_move_id++;
echo "<P align=\"center\" class=\"header\">
        <B>$headline[0]</B> versus <B>$headline[1]</B><BR>
        Round $disp_move_id ($move_player)
      </P>";
/*echo " <P align=\"center\" class=tiny>($white_castle_info)<BR>
        ($black_castle_info)
      </P>";*/
$is_playing = 0;
if ( ($move_player=="White" && $username==$player_w) ||
     ($move_player=="Black" && $username==$player_b) )
if ( $headline[4] == '?' )
  $is_playing = 1;

/* ***** PRINT RESULT OF MOVE SUBMISSION ***** */
if ( !empty($move_result) )
{
  echo "<P><FORM style=\"color: 8888ff\" onSubmit=\"return confirm_undo();\" method=\"post\"><B>$move_result</B>";
  if ( $may_undo ) 
  {
      echo "&nbsp;&nbsp;<INPUT type=\"submit\" name=\"undo_move\" value=\"Undo\">";
    //echo "&nbsp;($undo_time_left m.)";
  }
  echo "</FORM></P>";
}
else
if ( $is_archived == 0 && $headline[11]!='x' )
{
  echo "<P><FORM style=\"color: 8888ff\" onSubmit=\"return confirm_undo();\" method=\"post\">";
  if ( $move_player == "Black" )
    echo "<B>White's";
  else
    echo "<B>Black's";
  echo " last move: $headline[11]</B>";
  if ( $may_undo )
  {
    echo "&nbsp;&nbsp;<INPUT type=\"submit\" name=\"undo_move\" value=\"Undo\">";
    //echo "&nbsp;($undo_time_left m.)";
  }
  echo "</FORM></P>";
}

/* ***** CHATTER ***** */
echo "<P><I>";
for ( $i = 4+$move_id; $i < count($game); $i++ )
  echo "$game[$i]<BR>";
echo "</I></P>";

/* ***** MOVE EDITABLE ***** */
if ( $headline[4] == "D" )
{
  if ( ($move_player=="White" && $username==$player_w) ||
       ($move_player=="Black" && $username==$player_b) )
  {
    ?><FONT class="warning">
        <?=$player_opp?> offers a draw. Do you accept?
      </FONT>
      <P><FORM method="post">
      <INPUT type="hidden" name="draw_offered" value="yes">
      <TABLE border=0>
      <TR>
        <TD align="right"><INPUT type="submit" name="accept_draw" value="Accept"></TD><TD><INPUT type="submit" name="refuse_draw" value="Refuse"></TD>
      <TR><TD>Your Comment:</TD>
        <TD align="right">
          <TEXTAREA cols=20 rows=5 name="chesscomment"></TEXTAREA>
        </TD>
      </TR>
      </TABLE>
      </FORM></P><?
  }
  else
  if ( $username!=$player_w && $username!=$player_b )
  {
    if ( $headline[3] == 'w' )
      echo "<P class=\"warning\">It is white's turn. (You do not participate in this game.)</P>";
    else
      echo "<P class=\"warning\">It is black's turn. (You do not participate in this game.)</P>";
  }
  else
  {
    ?><P class="warning">
        It is <?=$player_opp?>'s turn. You have to wait until
        this user accepted or refused your draw offer.
      </P>
      <P>
        <FORM method="post">
          <INPUT type="hidden" name="refresh_only" value="yes">
          <INPUT type="submit" name="refresh" value="Refresh Board">
        </FORM>
      </P><?
  }
}
else
if ( $headline[4] != "?" )
{
  if ( $headline[4] == "-" )
    $game_result = "draw";
  else
    if ( $headline[4] == "w" )
    {
      if ( $player_w == $username )
        $game_result = "you won";
      else
        $game_result = "$player_w won";
    }
    else
    {
      if ( $player_b == $username )
        $game_result = "you won";
      else
        $game_result = "$player_b won";
    }
  echo "<P class=\"warning\">This game is over: $game_result!</P>"; 
  if ( (($move_player=="White" && $username==$player_w) ||
       ($move_player=="Black" && $username==$player_b)) &&
       $show_archive_button == "yes" )
  {
    ?><FORM method="post"><P>
      <SELECT name="close_action">
      <OPTION value="archive">Archive Game</OPTION>
      <OPTION value="delete">Delete Game</OPTION>
      </SELECT>
      &nbsp;&nbsp;&nbsp;&nbsp;
      <INPUT type="submit" name="archive_submit" value="Close">
      </P></FORM><?
  }
} else
if ( ($move_player=="White" && $username==$player_w) ||
     ($move_player=="Black" && $username==$player_b) )
{
  ?><FORM name="cmdform" method="post"><P>
    <TABLE border=0>
    <TR>
      <TD>Your Move:</TD>
      <TD align="right">
        <INPUT type="text" size=10 name="chessmove"
               value="<?=$chessmove?>">
      </TD>
    </TR>
    <TR>
      <TD>Your Comment:</TD>
      <TD align="right">
        <TEXTAREA cols=25 rows=6 name="chesscomment"><?=$chesscomment?></TEXTAREA>
      </TD>
    </TR>
    <TR>
      <TD></TD>
      <TD><INPUT type="submit" name="submit" value="Move!"></TD>
    </TR>
    </TABLE>
    </P></FORM><?
} else
if ( $username!=$player_w && $username!=$player_b )
{
  if ( $headline[3] == 'w' )
    echo "<P class=\"warning\">It is white's turn. (You do not participate in this game.)</P>";
  else
    echo "<P class=\"warning\">It is black's turn. (You do not participate in this game.)</P>";
}
else
{
    ?><P class="warning">
        It is <?=$player_opp?>'s turn. You have to wait until
        this user made its move.
      </P>
      <P>
        <FORM method="post">
          <INPUT type="submit" name="refresh" value="Refresh Board">
          <INPUT type="hidden" name="refresh_only" value="yes">
        </FORM>
      </P><?
}

/* ***** MOVE HISTORY ***** */
echo "Move History:<BR><TABLE border=0>";
for ( $i = 1, $line = 4; $i <= $move_id; $i++, $line++ )
{
  $moves = explode( " ", trim($game[$line]) );
  echo "<TR><TD>$i.</TD>";
  if ( count($moves) > 1 )
    echo "<TD>$moves[1]</TD>";
  else
    echo "<TD></TD>";
  if ( count($moves) > 2 )
    echo "<TD>$moves[2]</TD>";
  else
    echo "<TD></TD>";
  echo "</TR>";
}
echo "</TABLE>";
?>

</TD><TD><IMG width=10 alt="" src="images/spacer.gif"></TD><TD width=0 valign="top">

<?
/* ***** FILL CHESS BOARD ARRAY ***** */
$diff = fillChessBoard( trim($game[2]), trim($game[3]) );

/* ***** BUILD CHESS BOARD TABLE ***** */
echo "<TABLE width=0 border=0 cellpadding=4 cellspacing=0><TR>";
echo "<TD bgcolor=\"$t_frame_color\">";
echo "<TABLE width=0 border=0 cellpadding=0 cellspacing=0>";
if ( $player_color == "w" )
{
  $index=56;
  $pos_change = 1;
  $line_change = -16;
}
else {
  $index=7;
  $pos_change = -1;
  $line_change = 16;
}
for ( $y = 0; $y < 9; $y++ )
{
  echo "<TR>";
  for ( $x = 0; $x < 9; $x++ )
  {
    if ( $y == 8 )
    {
      if ( $x > 0 )
      {
        if ( $player_color == "w" )
          $c = chr(96+$x);
        else
          $c = chr(96+9-$x);
        echo "<TD align=\"center\"><IMG height=4 src=\"images/spacer.gif\"><BR><B style=\"color: $t_coord_color\">$c</B></TD>";
      }
      else
        echo "<TD></TD><TD></TD>";
    } 
    else if ( $x == 0 )
    {
      if ( $player_color == "w" )
        $i = 8-$y;
      else
        $i = $y+1;
      echo "<TD><B style=\"color: $t_coord_color\">$i</B></TD><TD><IMG width=4 src=\"images/spacer.gif\"></TD>";
    } 
    else {
      $entry = $board[$index];
      $color = substr( $entry, 0, 1);
      $name = getFullFigureName( $entry[1] );
      if ( (($y+1)+($x)) % 2 == 0 )
        $tile = "$theme/w_square.jpg";
      else
        $tile = "$theme/b_square.jpg";
      if ( $name != "empty" )
      {
        if ( $is_playing )
        {
          if ( $player_color !=  $color )
            $cmdpart = sprintf( "x%s", boardIndexToCoord($index) );
          else
            $cmdpart = sprintf( "%s%s", $board[$index][1],
                                        boardIndexToCoord($index) );
          ?>
            <TD background="images/<?=$tile?>"><A href="" onClick="return assembleCmd('<?=$cmdpart?>');"><IMG border=0 src="images/<?=$theme?>/<?=$color?><?=$name?>.gif"></A></TD>
          <?
        }
        else
          echo "<TD background=\"images/$tile\"><IMG src=\"images/$theme/",$color,$name,".gif\"></TD>";
      }
      else
      {
        if ( $is_playing )
        {
          $cmdpart = sprintf( "-%s", boardIndexToCoord($index) );
          ?>
            <TD background="images/<?=$tile?>"><A href="" onClick="return assembleCmd('<?=$cmdpart?>');"><IMG border=0 src="images/<?=$theme?>/empty.gif"></A></TD>
          <?
        }
        else
          echo "<TD background=\"images/$tile\"><IMG src=\"images/$theme/empty.gif\"></TD>";
      }
      $index += $pos_change;
    }
  }
  $index += $line_change;
  echo "</TR>";
}
echo "</TABLE></TD>";
if ( !empty($diff) )
{
  $diff_names = array( "pawn", "knight", "bishop", "rook", "queen" );
  ?><TD bgcolor="#ffffff"><IMG width=10 alt="" src="images/spacer.gif"></TD>
    <TD bgcolor="#ffffff">
    <TABLE border=0 cellpadding=2 cellspacing=0 bgcolor="<?=$t_frame_color?>">
    <TR><TD valign="top"><?
  /* show superiority at top */
  $draw_gap = 0;
  for( $i = 0; $i < 5; $i++ )
  {
    $name = $diff_names[4-$i];
    if ( $player_color=="w" && $diff[4-$i]<0 ) 
    {
      $draw_gap = 1;
      for ( $j = 0; $j < -$diff[4-$i]; $j++ )
        echo "<IMG src=\"images/$theme/sb$name.gif\"><BR>";
    }
    else
    if ($player_color=="b" && $diff[4-$i]>0)
    {
      $draw_gap = 1;
      for ( $j = 0; $j < $diff[4-$i]; $j++ )
        echo "<IMG src=\"images/$theme/sw$name.gif\"><BR>";
    }
  }
  echo "</TD></TR><TR><TD><IMG width=2 height=40 src=\"images/spacer.gif\"></TD></TR><TR><TD>";
  /* show superiority at bottom */
  for( $i = 0; $i < 5; $i++ )
  {
    $name = $diff_names[$i];
    if ( $player_color=="w" && $diff[$i]>0 ) 
    {
      for ( $j = 0; $j < $diff[$i]; $j++ )
        echo "<IMG src=\"images/$theme/sw$name.gif\"><BR>";
    }
    else
    if ($player_color=="b" && $diff[$i]<0)
    {
      for ( $j = 0; $j < -$diff[$i]; $j++ )
        echo "<IMG src=\"images/$theme/sb$name.gif\"><BR>";
    }
  }
  echo "</TD></TR></TABLE></TD>";
}
echo "</TR></TABLE>";

showFooter();
echo "<P class=\"tiny\">$t_credits</P>";

//echo "<BR><BR>Built Time: ",getMicrotime()-$start," secs";
?>

</TD></TR></TABLE>
</TD></TR></TABLE>

</BODY>
</HTML>

