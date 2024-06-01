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

$rot_id = 0;
if ( isset($_GET["rotate"]) && $_GET["rotate"] == 0)
  $rot_id = 1;

/* ***** BOARD THEME ***** */
if ( isset($_GET["theme"]) )
  $_SESSION["theme"] = $_GET["theme"];
$theme = $_SESSION["theme"];
include "images/$theme/board.php";
?>

<script language="Javascript">
var preload = new Image(); 
preload.src = "images/h_white.gif";
preload.src = "images/h_black.gif";
for ( index = 0; index < 10; index++)
    preload.src = "images/d" + index + ".gif";

var parse_error = "";
var cur_move = -1, move_count=0, orig_move_count = 0;
var bottom = "w";
var name = "", draw_gap = 0, slot_id = 0;
var diff = Array( 0,0,0,0,0 );
var diff_names = Array( "pawn", "knight", "bishop", "rook", "queen" );
var d1, d2;
var board = new Array(
  0,0,0,0,0,0,0,0,
  0,0,0,0,0,0,0,0,
  0,0,0,0,0,0,0,0,
  0,0,0,0,0,0,0,0,
  0,0,0,0,0,0,0,0,
  0,0,0,0,0,0,0,0,
  0,0,0,0,0,0,0,0,
  0,0,0,0,0,0,0,0
);
var moves = new Array(); /* moves*3 = src,dest,kill pairs */

/* move 0 is handled special to allow initialization:
 * board is cleared and first move is performed */
function gotoMove( move_id )
{
  if ( move_id < 0 ) move_id = 0;
  if ( move_id >= move_count ) 
  {
    move_id = move_count-1;
    if ( move_count < orig_move_count )
      alert( "Only "+move_count+" moves were parsed. Then an error occured:\n "+parse_error );
  }
  if ( move_id == cur_move ) return false;
  if ( move_id == 0 )
  {
    cur_move = 0;
    board = new Array( 
      /* 0=a1 - 63=h8 */
      /* chessmen codes: 0 empty,1-6 white PNBRQK,7-12 black PNBRQK */
      4, 2,3,5, 6, 3,2,4,
      1, 1,1,1, 1, 1,1,1,
      0, 0,0,0, 0, 0,0,0,
      0, 0,0,0, 0, 0,0,0,
      0, 0,0,0, 0, 0,0,0,
      0, 0,0,0, 0, 0,0,0,
      7, 7,7,7, 7, 7,7,7,
      10,8,9,11,12,9,8,10 );
    board[moves[1]] = board[moves[0]]; board[moves[0]] = 0;
  }
  else
  {
    //alert( moves[move_id*3]+"-"+moves[move_id*3+1]+" ("+moves[move_id*3+2]+")" );
    /* go forward or backward */
    if ( cur_move > move_id )
      while( cur_move > move_id )
        moveBackward();
    else
      while( cur_move < move_id )
        moveForward();
  }
  if ( move_id%2 == 0 )
    document.images["colorpin"].src = "images/h_white.gif";
  else
    document.images["colorpin"].src = "images/h_black.gif";
  setRoundNumber(Math.floor(move_id / 2)+1);
  renderBoard();
  return false;
}

function setRoundNumber( round )
{
  d1 = Math.floor(round/10); d2 = Math.floor(round%10);
  document.images["digit1"].src = "images/d"+d1+".gif";
  document.images["digit2"].src = "images/d"+d2+".gif";
}

function moveForward()
{
  if (cur_move == move_count-1 ) return;
  cur_move++; pos = cur_move*3;
  if ( moves[pos] == 0 && moves[pos+1] == 0 ) return;
  /* castling is special */
  if ( moves[pos] > 63 )
  {
    rook_start = Math.floor(moves[pos]  %100);
    rook_end   = Math.floor(moves[pos+1]%100);
    king_start = Math.floor(moves[pos]  /100);
    king_end   = Math.floor(moves[pos+1]/100);

    //alert( rook_start+"-"+rook_end+"  "+king_start+"-"+king_end );

    board[rook_end] = board[rook_start]; 
    board[rook_start] = 0;
    board[king_end] = board[king_start]; 
    board[king_start] = 0;
  }
  else
  {
    if ( moves[pos+1] > 63 )
    {
      /*promotion*/
      dest = Math.floor(moves[pos+1]%100);
      upg = Math.floor(moves[pos+1]/100);
      board[dest] = board[moves[pos]]+upg;
    }
    else
      board[moves[pos+1]] = board[moves[pos]]; 
    board[moves[pos]] = 0;
    if ( moves[pos+2] > 63 )
    {
      pawn_pos = Math.floor(moves[pos+2]/100);
      board[pawn_pos] = 0;
    }
  }
}

function moveBackward()
{
  if (cur_move == 0 ) return;
  pos = cur_move*3; cur_move--;
  if ( moves[pos] == 0 && moves[pos+1] == 0 ) return;
  /* castling is special */
  if ( moves[pos] > 63 )
  {
    rook_start = Math.floor(moves[pos]  %100);
    rook_end   = Math.floor(moves[pos+1]%100);
    king_start = Math.floor(moves[pos]  /100);
    king_end   = Math.floor(moves[pos+1]/100);

    //alert( rook_start+"-"+rook_end+"  "+king_start+"-"+king_end );

    board[rook_start] = board[rook_end]; 
    board[rook_end] = 0;
    board[king_start] = board[king_end]; 
    board[king_end] = 0;
  }
  else
  {
    if ( moves[pos+1] > 63 )
    {
      dest = Math.floor(moves[pos+1]%100);
      upg = Math.floor(moves[pos+1]/100);
      board[moves[pos]] = board[dest]-upg; 
    }
    else
    {
      dest = moves[pos+1];
      board[moves[pos]] = board[dest];
    }
    if ( moves[pos+2] > 0 )
    {
      if ( moves[pos+2] > 12 )
      {
        /* en passant move */
        pawn_pos = Math.floor(moves[pos+2]/100);
        chessman = Math.floor(moves[pos+2]%100);
        board[pawn_pos] = chessman;
        board[dest] = 0;
      }
      else
        board[dest] = moves[pos+2];
    }
    else
      board[dest] = 0;
  }
}

function showDiff()
{
  for ( i = 0; i < 15; i++ )
    document.images["tslot"+i].src = "images/spacer.gif";

  for ( i = 0; i < 5; i++ ) diff[i] = 0;
  for ( i = 0; i < 64; i++ )
    if ( board[i] > 0 )
    {
      if ( board[i] >= 7 && board[i] < 12 )
        diff[board[i]-7]--;
      else
      if ( board[i] >= 1 && board[i] < 6 )
        diff[board[i]-1]++;
    }

  /* show superiority at top */
  slot_id = 0;
  for( i = 0; i < 5; i++ )
  {
    name = diff_names[4-i];
    if ( bottom=="b" && diff[4-i]<0 ) 
    {
      for ( j = 0; j < -diff[4-i]; j++ )
      {
        document.images["tslot"+slot_id].src = "images/<?=$theme?>/sb"+name+".gif";
        slot_id++;
      }
    }
    else
    if (bottom=="w" && diff[4-i]>0)
    {
      for ( j = 0; j < diff[4-i]; j++ )
      {
        document.images["tslot"+slot_id].src = "images/<?=$theme?>/sw"+name+".gif";
        slot_id++;
      }
    }
  }
  /* show superiority at bottom */
  if ( slot_id > 0 )
  {
    document.images["tslot"+slot_id].src="images/<?=$theme?>/sempty.gif";
    slot_id++;
  }
  for( i = 0; i < 5; i++ )
  {
    name = diff_names[4-i];
    if ( bottom=="b" && diff[4-i]>0 ) 
    {
      for ( j = 0; j < diff[4-i]; j++ )
      {
        document.images["tslot"+slot_id].src = "images/<?=$theme?>/sw"+name+".gif";
        slot_id++; 
      }
    }
    else
    if (bottom=="w" && diff[4-i]<0)
    {
      for ( j = 0; j < -diff[4-i]; j++ )
      {
        document.images["tslot"+slot_id].src = "images/<?=$theme?>/sb"+name+".gif";
        slot_id++;
      }
    }
  }
}

function renderBoard()
{
  for ( i = 0; i < 64; i++ )
  {
    if ( board[i] == 0 )
    {
      document.images["b"+i].src = "images/<?=$theme?>/empty.gif";
      continue;
    }
    value = board[i];
    if ( value >= 7 ) 
    {
      pref = "b"; 
      value -= 6;
    }
    else 
      pref = "w";
    switch (value)
    {
      case 1: chessman = "pawn.gif"; break;
      case 2: chessman = "knight.gif"; break;
      case 3: chessman = "bishop.gif"; break;
      case 4: chessman = "rook.gif"; break;
      case 5: chessman = "queen.gif"; break;
      case 6: chessman = "king.gif"; break;
    }
    document.images["b"+i].src = "images/<?=$theme?>/"+pref+chessman;
  }
  showDiff();   
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
  <A href="chess.php?gamefile=<?=$gamefile?>">Input Mode</A> |
  <A href="browser.php?gamefile=<?=$gamefile?>&rotate=<?=$rot_id?>">Rotate Board</A> |
 <A href="pgn.php?gamefile=<?=$gamefile?>">PGN Format</A> |
  <A href="help.php">Help</A> |
<A href="logout.php">Logout</A> ]
</P>

<TABLE width=<?=$t_main_table_width?> border=0 cellspacing=0 cellpadding=0><TR><TD width=100% valign="top">

<?
include "misc.php";
include "handle_move.php";

//$start = getMicrotime();

$browsing_mode = 1;
$ac_move = "";
$w_figures = array();
$b_figures = array();
$board = array( 
  "", "", "", "", "", "", "", "",
  "", "", "", "", "", "", "", "",
  "", "", "", "", "", "", "", "",
  "", "", "", "", "", "", "", "",
  "", "", "", "", "", "", "", "",
  "", "", "", "", "", "", "", "",
  "", "", "", "", "", "", "", "",
  "", "", "", "", "", "", "", "" );

/* ***** LOAD GAME ***** */
if ( file_exists( "$res_games/$gamefile" ) )
  $game = file( "$res_games/$gamefile");
else
  if ( file_exists( "$res_archive/$gamefile" ) )
  {
    $show_archive_button = "no";
    $game = file( "$res_archive/$gamefile");
  }
  else
  {
    /* game is screwed so give a dummy */
    $game = array( "0 0 0 0 0 0 0 0 0 0 0 0",
                   "nouser nouser 0 w - 0 0 0 0 x x",
                   "", "", 
                   "The game \"$gamefile\" does not exist!" );
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
$headline = explode( " ", trim($game[1]) );
$player_w = $headline[0];
$player_b = $headline[1];
$player_color = "w"; /* our color */
if ( $username==$player_b )
  $player_color = "b";
if ( $rot_id == 1 )
{
  if ( $player_color == "w" )
    $player_color = "b";
  else
    $player_color = "w";
}
$move_id = $headline[2]; /* current number of move */
$new_dir = "b"; if ( $player_color != "w" ) $new_dir = "w";
?>
<P align="center" class="header">
  <B><?=$headline[0]?></B> versus <B><?=$headline[1]?></B><BR>
<?

/* ***** GAME RESULT ***** */
if ( $headline[4] != "?" && $headline[4] != "D" )
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
    echo "<FONT class=\"warning\">This game is over: $game_result!</FONT>";
}
echo "</P>";

/* ***** CONTROL BUTTONS ***** */
?>
<P align="center">
<A href="first" onClick="return gotoMove(0);"><IMG alt="" src="images/h_first.gif" border=0></A>
<A href="first" onClick="return gotoMove(cur_move-1);"><IMG alt="" src="images/h_backward.gif" border=0></A>

<IMG width=2 height=2 alt="" src="images/spacer.gif">
<IMG name="colorpin" alt="" src="images/h_white.gif"><IMG name="digit1" alt="" src="images/d0.gif"><IMG name="digit2" alt="" src="images/d1.gif"><IMG alt="" src="images/h_right.gif">
<IMG width=2 height=2 alt="" src="images/spacer.gif">

<A href="first" onClick="return gotoMove(cur_move+1);"><IMG alt="" src="images/h_forward.gif" border=0></A>
<A href="first" onClick="return gotoMove(move_count-1);"><IMG alt="" src="images/h_last.gif" border=0></A>
</P>
<?

/* ***** MOVE HISTORY ***** */
echo "Move History:<BR><TABLE border=0>";
$js_index = 0;
for ( $i = 1, $line = 4; $i <= $move_id; $i++, $line++ )
{
  $moves = explode( " ", trim($game[$line]) );
  echo "<TR><TD>$i.</TD>";
  if ( count($moves) > 1 )
  {
    echo "<TD><A href=\"$js_index\" onClick=\"return gotoMove($js_index);\">$moves[1]</A></TD>";
    $js_index++;
  }
  else
    echo "<TD></TD>";
  if ( count($moves) > 2 )
  {
    echo "<TD><A href=\"$js_index\" onClick=\"return gotoMove($js_index);\">$moves[2]</A></TD>";
    $js_index++;
  }
  else
    echo "<TD></TD>";
  echo "</TR>";
}
echo "</TABLE>";
?>
<script language="Javascript">
  move_count=<?=$js_index?>;
  orig_move_count = move_count;
</script>

</TD><TD><IMG width=10 alt="" src="images/spacer.gif"></TD><TD width=0 valign="top">

<?
/* ***** FILL CHESS BOARD ARRAY ***** */
fillChessBoard(
  "Ra1 Nb1 Bc1 Qd1 Ke1 Bf1 Ng1 Rh1 Pa2 Pb2 Pc2 Pd2 Pe2 Pf2 Pg2 Ph2",
  "Ra8 Nb8 Bc8 Qd8 Ke8 Bf8 Ng8 Rh8 Pa7 Pb7 Pc7 Pd7 Pe7 Pf7 Pg7 Ph7");

/* ***** BUILD JAVASCRIPT MOVES ***** */
echo "<script language=\"Javascript\">\n";
$js_index = 0; $invalid_move = 0;
for ( $i = 1, $line = 4; $i <= $move_id; $i++, $line++ )
{
  $moves = explode( " ", trim($game[$line]) );
  for ( $j = 1, $color="w"; $j <= 2; $j++, $color="b" )
    if (  count($moves) > $j )
    {
      if ( $moves[$j] == "draw" || $moves[$j] == "mate" ||
           $moves[$j] == "stalemate" || $moves[$j] == "---" )
      {
        echo "moves[$js_index]=0;";   $js_index++;
        echo "moves[$js_index]=0;";   $js_index++;
        echo "moves[$js_index]=0;\n"; $js_index++;
        $invalid_move = 1;
        break;
      }
      $src = 0; $dest = 0; $kill = 0;
      if ( $moves[$j][strlen($moves[$j])-1] == "+" )
        $moves[$j] = substr($moves[$j],0,strlen($moves[$j])-1);
      if ( $moves[$j] == "0-0" )
      {
        if ( $color=='w' )
          {$src = 407; $dest = 605;}
        else
          {$src = 6063; $dest = 6261;}
        $ac_move = "0-0";
      }
      else if ( $moves[$j] == "0-0-0" )
      {
        if ( $color=='w' )
          {$src = 400; $dest = 203;}
        else
          {$src = 6056; $dest = 5859;}
        $ac_move = "0-0-0";
      }
      else
        $ac_error = completeMove( $color, $moves[$j] );
      //echo "($moves[$j] --> $ac_move) ";
      if ( $ac_error == "" )
      {
        if ($src == 0 && $dest == 0 )
        {
          $src = boardCoordToIndex(substr($ac_move,1,2));
          $dest = boardCoordToIndex(substr($ac_move,4,2));
        }
        /* $src, $dest will not be changed when castling: */
        $kill = quickMove( $color, $ac_move, $src, $dest );
        /* modify $dest to reflect chessman promotion if any */
        $c = $moves[$j][strlen($moves[$j])-1];
        if ( $c == 'Q' || $c == 'R' || $c == 'B' || $c == 'N' )
        {
          switch ( $c )
          {
            case 'N': $dest += 100; break;
            case 'B': $dest += 200; break;
            case 'R': $dest += 300; break;
            case 'Q': $dest += 400; break;
          }
        }
      }
      else
      {
        echo "move_count=$js_index/2;\n";
        echo "parse_error = \"$js_index: $color: $ac_move: $ac_error\";\n";
//        for ( $i = 0;  $i < count($w_figures); $i++ )
//          echo "$w_figures[$i] ";
        $invalid_move = 1;
        break;
      }
      echo "moves[$js_index]=$src;";    $js_index++;
      echo "moves[$js_index]=$dest;";   $js_index++;
      echo "moves[$js_index]=$kill;\n"; $js_index++;
    }
  if ( $invalid_move ) break;
}
echo "</script>\n";

/* ***** BUILD CHESS BOARD TABLE ***** */
echo "<TABLE width=0 border=0 cellpadding=4 cellspacing=0>";
echo "<TR><TD bgcolor=\"$t_frame_color\">";
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
      if ( (($y+1)+($x)) % 2 == 0 )
        $tile = "$theme/w_square.jpg";
      else
        $tile = "$theme/b_square.jpg";
      echo "<TD name=\"b$index\" background=\"images/$tile\"><IMG name=\"b$index\" src=\"images/$theme/empty.gif\"></TD>";
      $index += $pos_change;
    }
  }
  $index += $line_change;
  echo "</TR>";
}
echo "</TABLE></TD></TR>";
echo "<TR><TD><IMG height=2 src=\"images/spacer.gif\"></TD></TR>";
echo "<TR><TD bgcolor=\"$t_frame_color\">";
for ($i = 0; $i < 15; $i++ )
  echo "<IMG name=\"tslot$i\" src=\"images/$theme/sempty.gif\">";
echo "</TD></TR>";
echo "</TD></TR></TABLE>";

showFooter();
//echo "<BR><BR>Built Time: ",getMicrotime()-$start," secs";
echo "<P class=\"tiny\">$t_credits</P>";
?>

</TD></TR></TABLE>
</TD></TR></TABLE>

<script language="Javascript">
<? 
for ( $i = 0; $i < 64; $i++ )
  if ( $board[$i] != "" )
  {
    if ( $board[$i][0] == 'w' )
      $id = 1;
    else
      $id = 7;
    switch ( $board[$i][1] )
    {
      case 'P': $id += 0; break;
      case 'N': $id += 1; break;
      case 'B': $id += 2; break;
      case 'R': $id += 3; break;
      case 'Q': $id += 4; break;
      case 'K': $id += 5; break;
    }
    echo "board[$i] = $id;\n";
  }
echo "cur_move = move_count-1; bottom=\"$player_color\";
setRoundNumber(Math.floor(cur_move/2)+1); renderBoard();\n";
?>
</script>;

</BODY>
</HTML>

