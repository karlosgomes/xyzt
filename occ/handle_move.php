<?

/* save a complete game to file */
function saveGame( $game, $headline, $comment, $gamefile )
{
  global $board,$res_games; /* move was done here so save this board */

  $hfile = fopen( "$res_games/$gamefile", "w" );
  
  /* start and move date */
  fwrite( $hfile, substr($game[0],0,17) );
  fwrite( $hfile, date( "Y m d H i", time() ) );
  fwrite( $hfile, "\n" );
  
  /* header */
  fwrite( $hfile, "$headline[0] $headline[1] $headline[2] " );
  fwrite( $hfile, "$headline[3] $headline[4] $headline[5] " );
  fwrite( $hfile, "$headline[6] $headline[7] $headline[8] " );
  fwrite( $hfile, "$headline[9] $headline[10] $headline[11] " );
  fwrite( $hfile, "$headline[12]\n" );
  
  /* figures */
  for ( $i = 0; $i < 64; $i++ )
    if ( $board[$i] != "" && substr($board[$i],0,1) == "w" )
    {
      $fig = substr($board[$i],1,1);
      $coord = boardIndexToCoord($i);
      fwrite( $hfile, "$fig$coord " );
    }
  fwrite( $hfile, "\n" );
  for ( $i = 0; $i < 64; $i++ )
    if ( $board[$i] != "" && substr($board[$i],0,1) == "b" )
    {
      $fig = substr($board[$i],1,1);
      $coord = boardIndexToCoord($i);
      fwrite( $hfile, "$fig$coord " );
    }
  fwrite( $hfile, "\n" );
    
  /* history from game array. if a new line was needed
   * the first comment line was overwritten which does
   * not matter as we add a new comment. */
  for ( $i = 0; $i < $headline[2]; $i++ )
    fwrite( $hfile, $game[$i+4] );
   
  /* remove \ from comment */
  $comment = str_replace( "\\", "", $comment );
  /* add comment */
  if ( $headline[3] == "w" )
    $playername = $headline[1]; /* prev player said so */
  else
    $playername = $headline[0];
  if ( !empty($comment) )
    fwrite( $hfile, "$playername says: $comment\n" );
  else
    fwrite( $hfile, "no chatter\n" );
   
  fclose( $hfile );
}

/* check a series of tiles given a start, an end tile
 * which is not included to the check and a position
 * change for each iteration. return true if not blocked. 
 * all values are given for 1dim board. */
function pathIsNotBlocked( $start, $end, $change )
{
  global $board;

  for ( $pos = $start; $pos != $end; $pos += $change )
  {
    /* DEBUG: echo "path: $pos: '$board[$pos]' "; */
    if ( $board[$pos] != "" )
       return 0;
  }
  return 1;
}

/* get the empty tiles between start and end as an 1dim array.
 * whether the path is clear is not checked. */
function getPath( $start, $end, $change )
{
  $path = array(); $i = 0;
  for ( $pos = $start; $pos != $end; $pos += $change )
    $path[$i++] = $pos;
  return $path;
}

/* get the change value that must be added to create
 * the 1dim path for figure moving from fig_pos to
 * dest_pos. it is assumed that the movement is valid!
 * no additional checks as in tileIsReachable are
 * performed. rook, queen and bishop are the only
 * units that can have empty tiles in between. */
function getPathChange( $fig, $fig_pos, $dest_pos )
{
  $change = 0;
  $fy = floor($fig_pos/8); $fx = $fig_pos%8;
  $dy = floor($dest_pos/8); $dx = $dest_pos%8;
  switch ( $fig )
  {
    /* bishop */
    case 'B':
      if ( $dy < $fy ) $change = -8; else $change =  8;
      if ( $dx < $fx ) $change -= 1; else $change += 1;
      break;
    /* rook */
    case 'R':
      if ( $fx==$dx ) 
      {
        if ( $dy<$fy ) $change = -8; else $change = 8;
      }
      else {
        if ( $dx<$fx ) $change = -1; else $change = 1;
      }
      break;
    /* queen */
    case 'Q':
      if ( abs($fx-$dx) == abs($fy-$dy) )
      {
        if ( $dy < $fy ) $change = -8; else $change =  8;
        if ( $dx < $fx ) $change -= 1; else $change += 1;
      }
      else if ( $fx==$dx ) {
        if ( $dy<$fy ) $change = -8; else $change = 8;
      } 
      else
      {
        if ( $dx<$fx ) $change = -1; else $change = 1;
      }
      break;
  }
  return $change;
}

/* check whether dest_pos is in reach for unit of fig_type
 * at tile fig_pos. it is not checked whether the tile
 * itself is occupied but only the tiles in between. 
 * this function does not check pawns. */
function tileIsReachable( $fig, $fig_pos, $dest_pos )
{
  global $board;

  if ( $fig_pos==$dest_pos) return;
  $result = 0;
  $fy = floor($fig_pos/8); $fx = $fig_pos%8;
  $dy = floor($dest_pos/8); $dx = $dest_pos%8;
  /* DEBUG:  echo "$fx,$fy --> $dx,$dy: "; */
  switch ( $fig )
  {
    /* knight */
    case 'N':
      if ( abs($fx-$dx)==1 && abs($fy-$dy)==2 )
        $result = 1;
      if ( abs($fy-$dy)==1 && abs($fx-$dx)==2 )
        $result = 1;
      break;
    /* bishop */
    case 'B':
      if ( abs($fx-$dx) != abs($fy-$dy) )
        break;
      if ( $dy < $fy ) $change = -8; else $change =  8;
      if ( $dx < $fx ) $change -= 1; else $change += 1;
      if ( pathIsNotBlocked( $fig_pos+$change, $dest_pos, $change ) )
        $result = 1;
      break;
    /* rook */
    case 'R':
      if ( $fx!=$dx && $fy!=$dy )
        break;
      if ( $fx==$dx ) 
      {
        if ( $dy<$fy ) $change = -8; else $change = 8;
      }
      else {
        if ( $dx<$fx ) $change = -1; else $change = 1;
      }
      if ( pathIsNotBlocked( $fig_pos+$change, $dest_pos, $change ) )
        $result = 1;
      break;
    /* queen */
    case 'Q':
      if ( abs($fx-$dx) != abs($fy-$dy) && $fx!=$dx && $fy!=$dy )
        break;
      if ( abs($fx-$dx) == abs($fy-$dy) )
      {
        if ( $dy < $fy ) $change = -8; else $change =  8;
        if ( $dx < $fx ) $change -= 1; else $change += 1;
      }
      else if ( $fx==$dx ) {
        if ( $dy<$fy ) $change = -8; else $change = 8;
      } 
      else
      {
        if ( $dx<$fx ) $change = -1; else $change = 1;
      }
      if ( pathIsNotBlocked( $fig_pos+$change, $dest_pos, $change ) )
        $result = 1;
      break;
    /* king */
    case 'K':
      if ( abs($fx-$dx) > 1 || abs($fy-$dy) > 1 ) break;
      $kings = 0;
      $adj_tiles = getAdjTiles( $dest_pos );
      foreach( $adj_tiles as $tile )
        if ( $board[$tile][1] == 'K' ) $kings++;
      if ( $kings == 2 ) break;
      $result = 1;
      break;
  }

  /* DEBUG: echo " $result<BR>"; */
  return $result;
}

/* check whether pawn at figpos may attack destpos.
 * by meaning whether it is diagonal. */
function checkPawnAttack( $fig_pos, $dest_pos )
{
  global $board;
  
  if ( $board[$fig_pos][0] == 'w' )
  {
    if ( ($fig_pos % 8) > 0 && $dest_pos == $fig_pos+7 )
      return 1;
    if ( ($fig_pos % 8) < 7 && $dest_pos == $fig_pos+9 )
      return 1;
  }
  else if ( $board[$fig_pos][0] == 'b' )
  {
    if ( ($fig_pos % 8) < 7 && $dest_pos == $fig_pos-7 )
      return 1;
    if ( ($fig_pos % 8) > 0 && $dest_pos == $fig_pos-9 )
      return 1;
  }
  return 0;
}

/* check whether pawn at figpos may move to destpos.
 * first move may be two tiles instead of just one. 
 * again the last tile is not checked but just the path
 * in between. */
function checkPawnMove( $fig_pos, $dest_pos )
{
  global $board;
  $first_move = 0;
  
  if ( $board[$fig_pos][0] == 'w' )
  {
    if ( $fig_pos >= 8 && $fig_pos <= 15 )
      $first_move = 1;
    if ( $dest_pos==$fig_pos+8 )
      return 1;
    if ( $first_move && ( $dest_pos==$fig_pos+16 ) )
    if ( $board[$fig_pos+8] == "" )
      return 1;
  }
  else if ( $board[$fig_pos][0] == 'b' )
  {
    if ( $fig_pos >= 48 && $fig_pos <= 55 )
      $first_move = 1;
    if ( $dest_pos==$fig_pos-8 )
      return 1;
    if ( $first_move && ( $dest_pos==$fig_pos-16 ) )
    if ( $board[$fig_pos-8] == "" )
      return 1;
  }
  return 0;
}

/* check all figures of 'opp' whether they attack
 * the given position */
function tileIsUnderAttack( $opp, $dest_pos )
{
  global $board;

  for ( $i = 0; $i < 64; $i++ )
    if ( $board[$i][0] == $opp )
    {
      if ( ($board[$i][1]=='P' && checkPawnAttack($i,$dest_pos)) ||
           ($board[$i][1]!='P' && 
                tileIsReachable($board[$i][1],$i,$dest_pos)) )
      {
        /*DEBUG: echo "attack test: $i: ",$opp,"P<BR>"; */
        return 1;
      }
    }
  return 0;
}

/* check all figures of 'opp' whether they attack
 * the king of player */
function kingIsUnderAttack( $player, $opp )
{
  global $board;
 
  for ( $i = 0; $i < 64; $i++ )
    if ( $board[$i][0] == $player && $board[$i][1] == 'K' )
    {
      $king_pos = $i;
      break;
    }
  /*DEBUG: echo "$player king is at $king_pos<BR>"; */
  
  return tileIsUnderAttack( $opp, $king_pos );
}

/* check whether player's king is check mate */
function isCheckMate( $player, $opp )
{
  global $board;
 
  for ( $i = 0; $i < 64; $i++ )
    if ( $board[$i][0] == $player && $board[$i][1] == 'K' )
    {
      $king_pos = $i;
      $king_x = $i % 8;
      $king_y = floor($i/8);
      break;
    }

  /* test adjacent tiles while king is temporarly removed */
  $adj_tiles = getAdjTiles( $king_pos );
  $contents = $board[$king_pos]; $board[$king_pos] = "";
  foreach ( $adj_tiles as $dest_pos ) 
  {
    if ( $board[$dest_pos][0] == $player ) continue;
    if ( tileIsUnderAttack($opp,$dest_pos) ) continue;
    $board[$king_pos] = $contents;
    return 0;
  }
  $board[$king_pos] = $contents;

  /* DEBUG:  echo "King cannot escape by itself! "; */

  /* get all figures that attack the king */
  $attackers = array(); $count = 0;
  for ( $i = 0; $i < 64; $i++ )
    if ( $board[$i][0] == $opp )
    {
      if ( ($board[$i][1]=='P' && checkPawnAttack($i,$king_pos)) ||
           ($board[$i][1]!='P' && 
                tileIsReachable($board[$i][1],$i,$king_pos)) )
      {
          $attackers[$count++] = $i;
      }
    }
  /* DEBUG: 
  for( $i = 0; $i < $count; $i++ )
    echo "Attacker: $attackers[$i] ";
  echo "Attackercount: ",count($attackers), " "; */
 
  /* if more than one there is no chance to escape */
  if ( $count > 1 ) return 1;

  /* check whether attacker can be killed by own figure */
  $dest_pos = $attackers[0];
  for ( $i = 0; $i < 64; $i++ )
    if ( $board[$i][0] == $player )
    {
      if ( ($board[$i][1]=='P' && checkPawnAttack($i,$dest_pos)) ||
           ($board[$i][1]!='P' && $board[$i][1]!='K' &&
              tileIsReachable($board[$i][1],$i,$dest_pos)) ||
           ($board[$i][1]=='K' && 
              tileIsReachable($board[$i][1],$i,$dest_pos) &&
              !tileIsUnderAttack($opp,$dest_pos)) )
      {
        /* DEBUG: echo "candidate: $i "; */
        $can_kill_atk = 0;
        $contents_def = $board[$i];
        $contents_atk = $board[$dest_pos];
        $board[$dest_pos] = $board[$i];
        $board[$i] = "";
        if ( !tileIsUnderAttack($opp,$king_pos) )
          $can_kill_atk = 1;
        $board[$i] = $contents_def;
        $board[$dest_pos] = $contents_atk;
        if ( $can_kill_atk )
        {
          /* DEBUG: echo "$i can kill attacker"; */
          return 0;
        }    
      }
    }
 
  /* check whether own unit can block the way */
  
  /* if attacking unit is a knight there
   * is no way to block the path */
  if ( $board[$dest_pos][1] == 'N' ) return 1;

  /* if enemy is adjacent to king there is no
   * way either */
  $dest_x = $dest_pos % 8;
  $dest_y = floor($dest_pos/8);
  if ( abs($dest_x-$king_x)<=1 && abs($dest_y-$king_y)<=1 )
    return 1;

  /* get the list of tiles between king and attacking
   * unit that can be blocked to stop the attack */
  $change = getPathChange($board[$dest_pos][1],$dest_pos,$king_pos);
  /* DEBUG:  echo "path change: $change "; */
  $path = getPath($dest_pos+$change,$king_pos,$change);
  /* DEBUG: foreach( $path as $tile ) echo "tile: $tile "; */
  foreach( $path as $pos )
  {
    for ( $i = 0; $i < 64; $i++ )
      if ( $board[$i][0] == $player )
      {
        if ( ($board[$i][1]=='P' && checkPawnMove($i,$pos)) ||
             ($board[$i][1]!='P' && $board[$i][1]!='K' &&
                  tileIsReachable($board[$i][1],$i,$pos)) )
        {
            /* DEBUG: echo "$i can block "; */
            return 0;
        }
      }
  }
  return 1;
}

/* check whether there is no further move possible */
/* TODO: reconize when move is not possible because of
 * check */
function isStaleMate( $player,$opp )
{
  global $board;

  for ( $i = 0; $i < 64; $i++ )
    if ( $board[$i][0] == $player )
      switch ($board[$i][1] )
      {
        case 'K':
          $adj_tiles = getAdjTiles( $i );
          foreach ( $adj_tiles as $pos ) 
          {
            if ( $board[$pos][0] == $player ) continue;
            if ( tileIsUnderAttack($opp,$pos) ) continue;
            return 0;
          }
          /* DEBUG:  echo "King cannot escape by itself! "; */
          break;
        case 'P':
          if ( $player == 'w' )
          {
            if ( $board[$i+8] == "" ) return 0;
            if ( ($i%8) > 0 && $board[$i+7][0] != $player ) return 0;
            if ( ($i%8) < 7 && $board[$i+9][0] != $player ) return 0;
          }
          else
          {
            if ( $board[$i-8] == "" ) return 0;
            if ( ($i%8) > 0 && $board[$i-9][0] != $player ) return 0;
            if ( ($i%8) < 7 && $board[$i-7][0] != $player ) return 0;
          }
          break;
        case 'B':
          if ( $i-9 >= 0  && $board[$i-9][0] != $player ) return 0;
          if ( $i-7 >= 0  && $board[$i-7][0] != $player ) return 0;
          if ( $i+9 <= 63 && $board[$i+9][0] != $player ) return 0;
          if ( $i+7 <= 63 && $board[$i+7][0] != $player ) return 0;
          break;
        case 'R':
          if ( $i-8 >= 0  && $board[$i-8][0] != $player ) return 0;
          if ( $i-1 >= 0  && $board[$i-1][0] != $player ) return 0;
          if ( $i+8 <= 63 && $board[$i+8][0] != $player ) return 0;
          if ( $i+1 <= 63 && $board[$i+1][0] != $player ) return 0;
          break;
        case 'Q':
          $adj_tiles = getAdjTiles( $i );
          foreach ( $adj_tiles as $pos )
            if ( $board[$pos][0] != $player ) 
              return 0;
          break;
        case 'N':
          if ( $i-17 >= 0  && $board[$i-17][0] != $player ) return 0; 
          if ( $i-15 >= 0  && $board[$i-15][0] != $player ) return 0;
          if ( $i-6  >= 0  && $board[$i-6][0]  != $player ) return 0;
          if ( $i+10 <= 63 && $board[$i+10][0] != $player ) return 0;
          if ( $i+17 <= 63 && $board[$i+17][0] != $player ) return 0;
          if ( $i+15 <= 63 && $board[$i+15][0] != $player ) return 0;
          if ( $i+6  <= 63 && $board[$i+6][0]  != $player ) return 0;
          if ( $i-10 >= 0  && $board[$i-10][0] != $player ) return 0;
          break;
      }
      
  return 1;
}

/* check the result for white and black player
 * and update their stats appropiately */
function incUserVal( $user, $index )
{
  $aux = explode( ":", trim($user) );
  $aux[$index]++;
  return implode( ":", $aux );
}
function updateStats( $white, $black, $game_result )
{
  global $res_users;
  $white_updated = 0; $black_updated = 0;
  $users = file( $res_users );
  $hfile = fopen( $res_users, "w" );
  for ( $i = 0; $i < count($users); $i++ )
  {
    if ( !$white_updated &&
         strncmp( $users[$i], "$white:", strlen($white)+1 ) == 0 )
    {
      switch ($game_result)
      {
        case "w": 
          fwrite( $hfile, incUserVal( $users[$i], 1 ) ); break;
        case "-": 
          fwrite( $hfile, incUserVal( $users[$i], 2 ) ); break;
        case "b": 
          fwrite( $hfile, incUserVal( $users[$i], 3 ) ); break;
      }
      fwrite( $hfile, "\n" );
      $white_updated = 1;
    }
    else
    if ( !$black_updated &&
         strncmp( $users[$i], "$black:", strlen($black)+1 ) == 0 )
    {
      switch ($game_result)
      {
        case "b": 
          fwrite( $hfile, incUserVal( $users[$i], 1 ) ); break;
        case "-": 
          fwrite( $hfile, incUserVal( $users[$i], 2 ) ); break;
        case "w": 
          fwrite( $hfile, incUserVal( $users[$i], 3 ) ); break;
      }
      fwrite( $hfile, "\n" );
      $black_updated = 1;
    }
    else /* unchanged */
      fwrite( $hfile, $users[$i] ); 
  }
  fclose( $hfile );
}

/* allow normal chess notation and translate into a full
 * move description which is then parsed. return an error
 * if any and store the result in global ac_move */
function completeMove( $player, $move )
{
  /*
   * [a-h][1-8|a-h][RNBQK]              pawn move/attack
   * [PRNBQK][a-h][1-8]                 figure move 
   * [PRNBQK][:x][a-h][1-8]             figure attack
   * [PRNBQK][1-8|a-h][a-h][1-8]        ambigous figure move
   * [a-h][:x][a-h][1-8][[RNBQK]        ambigous pawn attack 
   * [PRNBQK][1-8|a-h][:x][a-h][1-8]    ambigous figure attack
   */
  global $board, $ac_move, $w_figures,  $b_figures, $browsing_mode;
  $error = "format is totally unknown!";

  $ac_move = $move;

  if ( strlen($move)>=6 ) 
  {
    /* full move: a pawn requires a ? in the end
     * to automatically choose a queen on last line */
    if ( $move[0] == 'P' )
    if ( $move[strlen($move)-1]<'A' || $move[strlen($move)-1]>'Z' )
      $ac_move = "$move?";
    return "";
  }

  /* allow last letter to be a capital one indicating
   * the chessmen a pawn is supposed to transform into,
   * when entering the last file. we split this character
   * to keep the autocompletion process the same. */
  $pawn_upg = "?";
  if ( $move[strlen($move)-1]>='A' && $move[strlen($move)-1]<='Z' )
  {
    $pawn_upg = $move[strlen($move)-1];
    $move = substr( $move, 0, strlen($move)-1 );
  }
  if ( $pawn_upg == "P" || $pawn_upg == "K" )
    return "A pawn may only become either a knight, a bishop, a rook or a queen!";

  if ( $move[0]>='a' && $move[0]<='h' )
  {
    /* pawn move. either it's 2 or for characters as 
     * listed above */
    if ( strlen($move) == 4 )
    {
      if ( $move[1] != 'x' )
        return "use x to indicate an attack";
      $dest_x = $move[2];
      $dest_y = $move[3];
      $src_x  = $move[0];
      if ( $player == 'w' )
        $src_y  = $dest_y-1;
      else
        $src_y  = $dest_y+1;
      $ac_move = sprintf( "P%s%dx%s%d%s", 
                          $src_x,$src_y,$dest_x,$dest_y,
                          $pawn_upg );
      return "";
    }
    else
    if (strlen($move) == 2 )
    {
      $fig = sprintf( "%sP", $player );
      if ( $move[1] >= '1' && $move[1] <= '8' )
      {
        /* pawn move */
        $pos = boardCoordToIndex( $move );
        if ( $pos == 64 ) return "coordinate $move is invalid";
        if ( $player == 'w' )
        {
          while( $pos >= 0 && $board[$pos] != $fig ) $pos -= 8;
          if ( $pos < 0 ) $not_found = 1;
        }
        else
        {
          while( $pos <= 63 && $board[$pos] != $fig ) $pos += 8;
          if ( $pos > 63 ) $not_found = 1;
        }
        $pos = boardIndexToCoord( $pos );
        if ( $not_found || $pos == "" )
          return "cannot find $player pawn in column $move[0]";
        else {
          $ac_move = sprintf( "P%s-%s%s", $pos, $move, $pawn_upg );
          return "";
        }
      }
      else
      {
        /* notation: [a-h][a-h] for pawn attack no longer allowed 
         * except for history browser */
        if ( $browsing_mode == 0 )
            return "please use denotation [a-h]x[a-h][1-8] for pawn attacks (see help for more information)";
        /* pawn attack must be only one pawn in column! */
        $pawns = 0;
        $start = boardCoordToIndex( sprintf( "%s1", $move[0] ) );
        if ( $start == 64 ) return "coordinate $move[0] is invalid";
        for ( $i = 1; $i <= 8; $i++, $start+=8 )
          if ( $board[$start] == $fig ) 
          {
            $pawns++;
            $pawn_line = $i;
          }
        if ( $pawns == 0 )
          return "there is no pawn in column $move[0]";
        else if ( $pawns > 1 )
          return "there is more than one pawn in column $move[0]";
        else
        {
          if ( $player == 'w' )
            $dest_line = $pawn_line+1;
          else
            $dest_line = $pawn_line-1;
          $ac_move = sprintf( "P%s%dx%s%d", 
                            $move[0],$pawn_line,$move[1],$dest_line );
          return "";
        }
      }
    }
  }
  else
  {
    /* figure move */
    $dest_coord = substr( $move, strlen($move)-2, 2 );
    $action = $move[strlen($move)-3];
    if ( $action != 'x' ) $action = '-';
    if ( $player == 'w' ) 
      $figures = $w_figures;
    else
      $figures = $b_figures;
    $fig_count = 0;
    foreach( $figures as $figure )
      if ( $figure[0] == $move[0] )
      {
        $fig_count++;
        if ( $fig_count == 1 )
          $pos1 = substr( $figure, 1, 2 );
        else
          $pos2 = substr( $figure, 1, 2 );
      }
    if ( $fig_count == 0 )
      return sprintf( "there is no figure %s = %s", 
                      $move[0], getFullFigureName($move[0]) );
    else
    if ( $fig_count == 1 )
    {
       $ac_move = sprintf( "%s%s%s%s",
                      $move[0], $pos1, $action, $dest_coord ); 
       return "";
    }
    else
    {
      /* two figures which may cause ambiguity */
      $dest_pos = boardCoordToIndex( $dest_coord );
      if ( $dest_pos == 64 ) 
        return "coordinate $dest_coord is invalid";
      if ( tileIsReachable( $move[0], 
                            boardCoordToIndex($pos1), $dest_pos ) )
        $fig1_can_reach = 1;
      if ( tileIsReachable( $move[0], 
                            boardCoordToIndex($pos2), $dest_pos ) )
        $fig2_can_reach = 1;
      if ( !$fig1_can_reach && !$fig2_can_reach )
        return sprintf( "neither of the %s = %s can reach %s",
                        $move[0], getFullFigureName($move[0]),
                        $dest_coord );
      else
      if ( $fig1_can_reach && $fig2_can_reach )
      {
        /* ambiguity - check whether a hint is given */
        if ( ($action=='-' && strlen($move)==4) ||
             ($action=='x' && strlen($move)==5) )
          $hint = $move[1];
        if ( empty($hint) )
          return sprintf( "both of the  %s = %s can reach %s",
                          $move[0], getFullFigureName($move[0]),
                          $dest_coord );
        else
        {
          if ( $hint>='1' && $hint<='8' )
          {
            if ( $pos1[1]==$hint && $pos2[1]!=$hint )
              $move_fig1 = 1;
            if ( $pos2[1]==$hint && $pos1[1]!=$hint )
              $move_fig2 = 1;
          }
          else
          {
            if ( $pos1[0]==$hint && $pos2[0]!=$hint )
              $move_fig1 = 1;
            if ( $pos2[0]==$hint && $pos1[0]!=$hint )
              $move_fig2 = 1;
          }
          if ( !$move_fig1 && !$move_fig2 )
            return "ambiguity is not properly resolved";
          if ( $move_fig1 )
            $ac_move = sprintf( "%s%s%s%s",
                          $move[0], $pos1, $action, $dest_coord );
          else
            $ac_move = sprintf( "%s%s%s%s",
                          $move[0], $pos2, $action, $dest_coord );
          return;
        }
      }
      else
      {
        if ( $fig1_can_reach )
          $ac_move = sprintf( "%s%s%s%s",
                        $move[0], $pos1, $action, $dest_coord ); 
        else
          $ac_move = sprintf( "%s%s%s%s",
                        $move[0], $pos2, $action, $dest_coord ); 
        return "";
      }
    }
  }

  return $error;
}

/* a hacky function that uses autocomplete to short
 * a full move. if this fails there is no warning
 * but the move is kept anchanged */
function convertFullToChessNotation($player,$move)
{
  global $ac_move;
  $new_move = $move;

  $old_ac_move = $ac_move; /* backup required as autocomplete
                              will overwrite it */
                  
  /* valid pawn moves are always non-ambigious */
  if ( $move[0] == 'P' )
  {
    /* skip P anycase. for attacks skip source digit
       and for moves skip source pos and - */
    if ( $move[3] == '-' )
      $new_move = substr( $move, 4 );
    else
    if ( $move[3] == 'x' )
      $new_move = sprintf("%s%s", $move[1], substr( $move, 3 ) );
  }
  else
  {
    /* try to remove the source position and check whether it
     * is a non-ambigious move. if it is add one of the components
     * and check again */
    if ( $move[3] == '-' )
      $dest = substr( $move, 4 );
    else
    if ( $move[3] == 'x' )
      $dest = substr( $move, 3 );
    $new_move = sprintf("%s%s", $move[0], $dest );
    if ( completeMove($player,$new_move) != "" )
    {
      /* add a component */
      $new_move = sprintf("%s%s%s", $move[0], $move[1], $dest );
      if ( completeMove($player,$new_move) != "" )
      {
        /* add other component */
        $new_move = sprintf("%s%s%s", $move[0], $move[2], $dest );
        if ( completeMove($player,$new_move) != "" )
           $new_move = $move; /* give up */
      }
    }
  }
  
  $ac_move = $old_ac_move;
  return $new_move;
}

/* check whether it is user's turn and the move is valid. 
 * if the move is okay update the game file. */
function handleMove( $gamefile, $move, $comment )
{
  global $board, $username, $ac_move, $res_games;

  /* DEBUG: echo "HANDLE: $move, $comment<BR>"; */

  $result = "undefined";
  $move_handled = 0;
  if ( !file_exists("$res_games/$gamefile") )
    return "ERROR: Game \"$gamefile\" does not exist!";
  $game = file( "$res_games/$gamefile");

  /* get number of move and color of current player and 
   * whether castling is possible. */
  $headline = explode( " ", trim($game[1]) );
  $player_w = $headline[0];
  $player_b = $headline[1];
  $cur_move = $headline[2];
  $cur_player = $headline[3]; /* b or w */
  if ( ($cur_player=="w" && $username!=$player_w) ||
       ($cur_player=="b" && $username!=$player_b) )
  {
    return "It is not your turn!";
  }
  if ( $cur_player == "w" )
    $cur_opp = "b";
  else 
    $cur_opp = "w";
  if ( $headline[4] != "?" && $headline[4] != "D" )
  { 
    return "This game is over. It is not 
            possible to enter any further moves.";
  }
  /* headline castling meaning: 0 - rook or king moved
                                1 - possible
                                9 - performed */
  if ( $cur_player=="w" )
  {
    $may_castle_short = $headline[5];
    $may_castle_long  = $headline[6];
  }
  else
  {
    $may_castle_short = $headline[7];
    $may_castle_long  = $headline[8];
  }
  
  /* DEBUG echo "HANDLE: w=$player_w, b=$player_b, c=$cur_player, ";
  echo "m=$cur_move, may_castle=$may_castle_short, ";
  echo "$may_castle_long  <BR>";*/
  
  /* fill chess board */
  fillChessBoard( trim($game[2]), trim($game[3]) );

  /* allow two-step of king to indicate castling */
  if ( $cur_player == 'w' && $move == "Ke1-g1" )
    $move = "0-0";
  else
  if ( $cur_player == 'w' && $move == "Ke1-c1" )
    $move = "0-0-0";
  else
  if ( $cur_player == 'b' && $move == "Ke8-g8" )
    $move = "0-0";
  else
  if ( $cur_player == 'b' && $move == "Ke8-c8" )
    $move = "0-0-0";
   
  /* backup full move input for game history before
   * splitting figure type apart */
  $history_move = $move;
   
  /* clear last move - won't be saved yet if anything 
     goes wrong */
  $headline[11] = "x";
  $headline[12] = 'x';
    
  /* HANDLE MOVES:
   * ---                               surrender 
   * 0-0                               short castling
   * 0-0-0                             long castling
   * draw?                             offer a draw
   * accept_draw                       accept the draw
   * refuse_draw                       refuse the draw
   * [PRNBQK][a-h][1-8][-:x][a-h][1-8] unshortened move
   */
  if ( $move == "DELETE" )
  {
    if ( ($cur_player == 'w' && $cur_move == 0) ||
         ($cur_player == 'b' && $cur_move == 1) )
    {
      unlink( "$res_games/$gamefile" );
      $result = "You deleted the game.";
    }
    else
      $result = "ERROR: You cannot delete a game when you have already moved!";
  }
  else
  if ( $move == "draw?" && $headline[4] == "?" )
  {
    $headline[4] = "D";
    $result = "You have offered a draw.";
    $draw_handled = 1;
    $headline[11] = "DrawOffered";
  }
  else
  if ( $move == "refuse_draw" && $headline[4] == "D" )
  {
    $headline[4] = "?";
    $draw_handled = 1;
    $result = "You refused the draw.";
    $headline[11] = "DrawRefused";
  }
  else
  if ( $move == "accept_draw" && $headline[4] == "D" )
  {
    $headline[4] = "-";
    $draw_handled = 1;
    $result = "You accepted the draw.";
    $headline[11] = "DrawAccepted";
    if ( $headline[3] == "b" )
    {
      /* new move started as white offered the draw */
      $headline[2]++;
      $game[3+$headline[2]] = sprintf( "%03d\n", $headline[2] );
    }
    $game[3+$headline[2]] = sprintf( "%s draw\n", 
                                     trim($game[3+$headline[2]]) );
  }
  else
  if ( $move == "---" )
  {
    /* surrender */
    $headline[4] = $cur_opp;
    $result = "You have surrendered to your opponent.";
    $move_handled = 1;
    $headline[11] = "Surrender";
  } 
  else if ( $move == "0-0" )
  {
    /* short castling */
    if ( $may_castle_short != 1 || $may_castle_long == 9 )
      return "ERROR: You cannot castle short any longer!";
    if ( $cur_player=="b" && $board[61]=="" && $board[62]=="" )
    {
        if ( kingIsUnderAttack( "b", "w" ) )
          return "ERROR: You cannot escape check by castling!";
        if ( tileIsUnderAttack( "w", 62 ) || 
             tileIsUnderAttack( "w", 61 ) )
          return "ERROR: Either king or rook would be under attack after short castling!";
        $may_castle_short = 9;
        $board[60] = "";
        $board[62] = "bK";
        $board[61] = "bR";
        $board[63] = "";
    }
    if ( $cur_player=="w" && $board[5]=="" && $board[6]=="" )
    {
        if ( kingIsUnderAttack( "w", "b" ) )
          return "ERROR: You cannot escape check by castling!";
        if ( tileIsUnderAttack( "b", 5 ) || 
             tileIsUnderAttack( "b", 6 ) )
          return "ERROR: Either king or rook would be under attack after short castling!";
      $may_castle_short = 9;
      $board[4] = "";
      $board[6] = "wK";
      $board[5] = "wR";
      $board[7] = "";
    }
    if ( $may_castle_short != 9 )
      return "ERROR: Cannot castle short because the way is blocked!";
    $result = "You castled short.";
    $move_handled = 1;
    $headline[11] = "0-0";
  }
  else if ( $move == "0-0-0" )
  {
    /* long castling */
    if ( $may_castle_long != 1 || $may_castle_short == 9 )
      return "ERROR: You cannot castle long any longer!";
    if ( $cur_player=="b"  && $board[57]=="" &&
         $board[58]==""    && $board[59]=="" )
    {
        if ( kingIsUnderAttack( "b", "w" ) )
          return "ERROR: You cannot escape check by castling!";
        if ( tileIsUnderAttack( "w", 58 ) || 
             tileIsUnderAttack( "w", 59 ) )
          return "ERROR: Either king or rook would be under attack after short castling!";
        $may_castle_long = 9;
        $board[56] = "";
        $board[58] = "bK";
        $board[59] = "bR";
        $board[60] = "";
    }
    if ( $cur_player=="w" && $board[1]=="" && 
         $board[2]==""    && $board[3]=="" )
    {
        if ( kingIsUnderAttack( "w", "b" ) )
          return "ERROR: You cannot escape check by castling!";
        if ( tileIsUnderAttack( "b", 2 ) || 
             tileIsUnderAttack( "b", 3 ) )
          return "ERROR: Either king or rook would be under attack after short castling!";
      $may_castle_long = 9;
      $board[0] = "";
      $board[2] = "wK";
      $board[3] = "wR";
      $board[4] = "";
    }
    if ( $may_castle_long != 9 )
      return "ERROR: Cannot castle long because the way is blocked!";
    $result = "You castled long.";
    $move_handled = 1;
    $headline[11] = "0-0-0";
  }
  else
  {
    /* [PRNBQK][a-h][1-8][-:x][a-h][1-8][RNBQK] full move */

    /* allow short move description by autocompleting to
     * full description */
    $ac_error = completeMove( $cur_player, trim($move) );
    if ( $ac_error != "" )
      return "ERROR: autocomplete: $ac_error";
    else 
      $move = $ac_move;
    $headline[11] = str_replace( "?", "", $move );
    
    /* a final captial letter may only be N,B,R,Q for the
     * appropiate chessman */
    $c = $move[strlen($move)-1];
    if ( $c >= 'A' && $c <= 'Z' )
    if ( $c != 'N' && $c != 'B' && $c != 'R' && $c != 'Q' )
      return "ERROR: only N (knight), B (bishop), R (rook) and Q (queen) are valid chessman identifiers";
    
    /* if it is a full move, try to shorten the history move */
    if ( strlen( $history_move ) >= 6 )
      $history_move = 
          convertFullToChessNotation($cur_player,$history_move);
    /* DEBUG: echo "Move: $move ($history_move)<BR>"; */
    
    /* validate figure and position */
    $fig_type = $move[0];
    $fig_name = getFullFigureName( $fig_type );
    if ( $fig_name == "empty" )
      return "ERROR: Figure $fig_type is unknown!";
    $fig_coord = substr($move,1,2);
    $fig_pos = boardCoordToIndex( $fig_coord );
    if ( $fig_pos == 64 ) return "ERROR: $fig_coord is invalid!";
    /* DEBUG  echo "fig_type: $fig_type, fig_pos: $fig_pos<BR>"; */
    if ( $board[$fig_pos] == "" )
      return "ERROR: Tile $fig_coord is empty.";
    if ( $board[$fig_pos][0] != $cur_player )
      return "ERROR: Figure does not belong to you!";
    if ( $board[$fig_pos][1] != $fig_type )
      return "ERROR: Figure does not exist!";
    
    /* get target index */
    $dest_coord = substr($move,4,2);
    $dest_pos = boardCoordToIndex( $dest_coord );
    if ( $dest_pos == 64 )
      return "ERROR: $dest_coord is invalid!";
    if ( $dest_pos == $fig_pos )
      return "ERROR: Current position and destination are equal!";
    /* DEBUG  echo "dest_pos: $dest_pos<BR>"; */

    /* get action */
    $action = $move[3];
    if ( $move[3] == "-" ) 
      $action = 'M'; /* move */
    else if ( $move[3] == 'x' )
      $action = 'A'; /* attack */
    else
      return "ERROR: $action is unknown! Please use - for a move
              and x for an attack.";

    /* if attack an enemy unit must be present on tile
     * and if move then tile must be empty. in both cases
     * the king must not be checked after moving. */
     
    /* check whether the move is along a valid path and
     * whether all tiles in between are empty thus the path
     * is not blocked. the final destination tile is not 
     * checked here. */
    if ( $fig_type != 'P' )
    {
        if ( !tileIsReachable( $fig_type, $fig_pos, $dest_pos ) )
          return "ERROR: Tile $dest_coord is out of moving range for $fig_name at $fig_coord!";
    }
    else {
      if ( $action == 'M' && !checkPawnMove( $fig_pos, $dest_pos ) )
        return "ERROR: Tile $dest_coord is out of moving range for $fig_name at $fig_coord!";
      if ( $action == 'A' && !checkPawnAttack( $fig_pos, $dest_pos ) )
        return "ERROR: Tile $dest_coord is out of attacking range for $fig_name at $fig_coord!";
    }
     
    $en_passant_okay = 0;
    /* check action */
    if ( $action == 'M' && $board[$dest_pos] != "" )
        return "ERROR: Tile $dest_coord is occupied. You cannot move there.";
    if ( $action == 'A' && $board[$dest_pos] == "" ) {
      /* en passant of pawn? */
      if ( $fig_type == 'P' ) {
        if ( $cur_player == 'w' )
        {
          if ( $headline[10] != 'x' )
          if ( $dest_pos%8 == $headline[10] )
          if ( floor($dest_pos/8) == 5 )
            $en_passant_okay = 1;
        }
        else
        {
          if ( $headline[9] != 'x' )
          if ( $dest_pos%8 == $headline[9] )
          if ( floor($dest_pos/8) == 2 )
            $en_passant_okay = 1;
        }
        if ( $en_passant_okay == 0 )
          return "ERROR: en-passant no longer possible!";
      }
      else
        return "ERROR: Tile $dest_coord is empty. You cannot attack it.";
    }
    if ( $action == 'A' && $board[$dest_pos][0] == $cur_player )
      return "ERROR: You cannot attack own unit at $dest_coord.";
      
    /* backup affected tiles */
    $old_fig_tile = $board[$fig_pos];
    $old_dest_tile = $board[$dest_pos];

    /* perform move */
    $board[$fig_pos] = "";
    if ( $board[$dest_pos] != "" )
      $headline[12] = sprintf("%s%s",$board[$dest_pos],$dest_pos);
    $board[$dest_pos] = "$cur_player$fig_type";
    if ( $en_passant_okay ) {
      /* kill pawn */
      if ( $cur_player == 'w' ) 
      {
        $board[$dest_pos-8] = "";
        $headline[12] = sprintf("bP%s",$dest_pos-8);
      }
      else
      {
        $board[$dest_pos+8] = "";
        $headline[12] = sprintf("wP%s",$dest_pos+8);
      }
    }

    /* check check :) */
    if ( kingIsUnderAttack( $cur_player, $cur_opp ) )
    {
      $board[$fig_pos] = $old_fig_tile;
      $board[$dest_pos] = $old_dest_tile;
      if ( $en_passant_okay ) {
       /* respawn en-passant pawn */
        if ( $cur_player == 'w' ) 
          $board[$dest_pos-8] = "bP";
        else
          $board[$dest_pos+8] = "wP";
      }
      return "ERROR: Move is invalid because king would be under attack then.";
    }

    /* check whether this forbids any castling */
    if ( $fig_type == 'K' ) {
      if ( $may_castle_short == 1 )
        $may_castle_short = 0;
      if ( $may_castle_long == 1 )
        $may_castle_long  = 0;
    }
    if ( $fig_type == 'R' ) {
      if ( $may_castle_long == 1 && ($fig_pos%8) == 0 )
        $may_castle_long = 0;
      if ( $may_castle_short == 1 && ($fig_pos%8) == 7 )
        $may_castle_short = 0;
    }

    /* if a pawn moved two tiles this will allow 'en passant'
     * for next turn. */
    if ( $fig_type == 'P' && abs($fig_pos-$dest_pos) == 16 )
    {
      if ( $cur_player == 'w' )
        $headline[9]  = $fig_pos%8;
      else
        $headline[10] = $fig_pos%8;
    }
    else 
    {
      /* clear 'en passant' of OUR last move */
      if ( $cur_player == 'w' )
        $headline[9]  = 'x';
      else
        $headline[10] = 'x';
    }
  
    if ($action == 'M' )
      $result = "$fig_name moved from $fig_coord to $dest_coord";
    else
      $result = "$fig_name attacked $dest_coord from $fig_coord";
    
    /* if pawn reached last line convert into a queen */
    if ( $fig_type == 'P' )
    {
      if ( ($cur_player=='w' && $dest_pos>= 56) || 
           ($cur_player=='b' && $dest_pos<= 7 ) )
      {
        $pawn_upg = $move[strlen($move)-1];
        if ( $pawn_upg == '?' ) 
        {
          $pawn_upg = 'Q';
          $history_move = sprintf( "%sQ", $history_move );
        }
        $board[$dest_pos] = "$cur_player$pawn_upg";
        $result = sprintf( "%s ... and became a %s!", 
                           $result, getFullFigureName( $pawn_upg ) );
      }
    }
  
    $move_handled = 1;
  }
  
  /* if a legal move was performed test whether you
   * check the opponent or even check-mate him. then 
   * update castling and en-passant flags, select the
   * next player and add the move to the history. */
  if ( $move_handled ) 
  {
    if ( kingIsUnderAttack( $cur_opp, $cur_player ) )
    {
      /* if this is check mate finish the game. if not
       * just add a + to the move. */
      if ( isCheckMate( $cur_opp, $cur_player ) )
      {
        $headline[4] = $cur_player;
        $mate_type = 1;
      }
      $history_move = sprintf( "%s+", $history_move );
    }
    else if ( isStaleMate( $cur_opp, $cur_player ) )
    {
      $headline[4] = "-";
      $mate_type = 2;
    }

    /* store possible castling modification */
    if ( $cur_player=="w" )
    {
      $headline[5] = $may_castle_short;
      $headline[6] = $may_castle_long;
    }
    else 
    {
      $headline[7] = $may_castle_short;
      $headline[8] = $may_castle_long;
    }
   
    /* update move and current player in headline and
    /* save move */
    if ( $headline[3] == "w" )
    {
      /* new move started */
      $headline[2]++;
      $game[3+$headline[2]] = sprintf( "%03d\n", $headline[2] );
      /* DEBUG: echo $game[3+$headline[2]]; */
    }
    $game[3+$headline[2]] = sprintf( "%s %s\n", 
                                     trim($game[3+$headline[2]]),
                                     $history_move );
    /* if other player can't any more moves end the
     * game and enter his move automatically */
    if ( $mate_type > 0 ) 
    {
      if ( $mate_type == 1 ) 
      {
        $mate_name = "mate";
        $result = "$result ... CHECKMATE!";
      }
      else
      {
        $mate_name = "stalemate";
        $result = "$result ... STALEMATE!";
      }
      if ( $headline[3] == "b" )
      {
        /* new move started */
        $headline[2]++;
        $game[3+$headline[2]] = sprintf( "%03d\n", $headline[2] );
      }
      $game[3+$headline[2]] = sprintf( "%s %s\n", 
                                       trim($game[3+$headline[2]]),
                                       $mate_name );
    }
  }
  if ( $move_handled || $draw_handled )
  {
    /* update stats when game is over. includes surrender */
    if ( $headline[4] != "?" && $headline[4] != "D" )
        updateStats( $headline[0], $headline[1], $headline[4] );

    /* set next player */
    if ( $headline[3] == "b" )
      $headline[3] = "w";
    else
      $headline[3] = "b";
 
    /* save game */
    saveGame( $game, $headline, $comment, $gamefile );
  }

  return $result;
}

/* perform a move without any checks. used for the animated
 * chessboard. the provided move history must be completely
 * valid. */
function findFigure( $figures, $name )
{
  for ( $i = 0; $i < count($figures); $i++ )
    if ( $figures[$i] == $name ) return $i;
  return -1;
}
function quickMove( $player, $move, $src, $dest )
{
  global $board, $w_figures, $b_figures;
  $fig_changed = false;
  $kill = 0;

  if ( $player == 'w' )
  {
    $figures = $w_figures;
    $opp_figures = $b_figures;
  }
  else
  {
    $figures = $b_figures;
    $opp_figures = $w_figures;
  }
    
  if ( $move == "0-0" )
  {
    if ( $player == 'w' )
    {
      $board[4] = "";
      $board[6] = "wK";
      $board[5] = "wR";
      $board[7] = "";
      $i = findFigure( $figures, "Ke1" );
      $j = findFigure( $figures, "Rh1" );
      if ( $i >= 0 && $j >= 0 )
      {
        $figures[$i] = "Kg1";
        $figures[$j] = "Rf1";
        $fig_changed = true;
      }
    }
    else
    {
      $board[60] = "";
      $board[62] = "bK";
      $board[61] = "bR";
      $board[63] = "";
      $i = findFigure( $figures, "Ke8" );
      $j = findFigure( $figures, "Rh8" );
      if ( $i >= 0 && $j >= 0 )
      {
        $figures[$i] = "Kg8";
        $figures[$j] = "Rf8";
        $fig_changed = true;
      }
    }
  }
  else
  if ( $move == "0-0-0" )
  {
    if ( $player == 'w' )
    {
      $board[0] = "";
      $board[2] = "wK";
      $board[3] = "wR";
      $board[4] = "";
      $i = findFigure( $figures, "Ke1" );
      $j = findFigure( $figures, "Ra1" );
      if ( $i >= 0 && $j >= 0 )
      {
        $figures[$i] = "Kc1";
        $figures[$j] = "Rd1";
        $fig_changed = true;
      }
    }
    else
    {
      $board[56] = "";
      $board[58] = "bK";
      $board[59] = "bR";
      $board[60] = "";
      $i = findFigure( $figures, "Ke8" );
      $j = findFigure( $figures, "Ra8" );
      if ( $i >= 0 && $j >= 0 )
      {
        $figures[$i] = "Kc8";
        $figures[$j] = "Rd8";
        $fig_changed = true;
      }
    }
  }
  else
  {
    $name = substr($move,0,3);
    $i = findFigure( $figures, $name );
    if ( $i >= 0 )
    {
      /* pawn promotion? */
      $c = $move[strlen($move)-1];
      if ( $c != 'Q' && $c != 'R' && $c != 'B' && $c != 'N' )
        $c = $move[0];
      else
        $board[$src] = "$player$c";
      
      $figures[$i] = sprintf("%s%s", $c, substr($move,4,2));
      echo "/*$name --> $figures[$i]*/ ";
      /* if this was attack kill figure */
      if ( $move[3] == 'x' )
      {
        $name = sprintf("%s%s",$board[$dest][1],substr($move,4,2));
        $i = findFigure( $opp_figures, $name );
        if ( $i >= 0 ) 
        {
          $opp_figures[$i] = "xxx";
          echo "/*$name --> xxx*/ ";
        }
        if ( $board[$dest] == "" )
        {
          /* en passant kill */
          if ( $player == 'w' )
          {
            $kill = 100*($dest-8)+7;
            $board[$dest-8] = "";
          }
          else
          {
            $kill = 100*($dest+8)+1;
            $board[$dest+8] = "";
          }
        }
        else
        {
          if ( $board[$dest][0] == 'w' )
            $kill = 1;
          else
            $kill = 7;
          switch ( $board[$dest][1] )
          {
            case 'P': $kill += 0; break;
            case 'N': $kill += 1; break;
            case 'B': $kill += 2; break;
            case 'R': $kill += 3; break;
            case 'Q': $kill += 4; break;
            case 'K': $kill += 5; break;
          }
        }
      }
      $fig_changed = true;
    }
    $board[$dest] = $board[$src]; $board[$src] = "";
  }
  if ( $fig_changed )
  {
    if ( $player == 'w' ) 
    {
      $w_figures = $figures;
      $b_figures = $opp_figures;
    }
    else
    {
      $b_figures = $figures;
      $w_figures = $opp_figures;
    }
  }
  return $kill;
}

function handleUndo( $gamefile )
{
  global $board, $username, $ac_move, $res_games;

  if ( !file_exists("$res_games/$gamefile") )
    return "ERROR: Game \"$gamefile\" does not exist!";
  $game = file( "$res_games/$gamefile");

  /* get game info and check whether player may undo a move */
  $headline = explode( " ", trim($game[1]) );
  $player_w = $headline[0];
  $player_b = $headline[1];
  $cur_move = $headline[2];
  $cur_player = $headline[3]; /* b or w */
  if ( $cur_player == 'w' )
    $old_player = 'b';
  else
    $old_player = 'w';
  if ( $player_w != $username && $player_b != $username )
    return "ERROR: You do not participate in this game!";
  if ( ($cur_player == 'b' && $player_w != $username) ||
       ($cur_player == 'w' && $player_b != $username) )
    return "ERROR: You cannot undo your opponent's last move!";
  if ( $headline[11] == 'x' ) 
    return "ERROR: There is no move that could be undone!";
  if ( timePassed( trim($game[0]) ) >= 1200 )
    return "ERROR: Undo time limit of 20 minutes exceeded!";

  /* fill chess board */
  fillChessBoard( trim($game[2]), trim($game[3]) );

  $move = $headline[11];
  $kill = $headline[12]; /* killed chessman if any */

  $undo_okay = 0; /* 0 - nothing
                     1 - normal undo
                     2 - undo without turn dec
                     3 - undo with inverted history delete
                     4 - undo with white: delete last line 
                                   black: delete last line + 
                                          last entry of prev line */

  /* undo surrender */
  if ( $move == "Surrender" )
    $undo_okay = 1;
  else
  /* undo draw accepted/offer/refuse */
  if ( $move == "DrawOffered" )
    $undo_okay = 2;
  else
  if ( $move == "DrawRefused" )
    $undo_okay = 2;
  else
  if ( $move == "DrawAccepted" )
    $undo_okay = 3;
  else
  /* undo castling */
  if ( $move == "0-0" )
  {
    if ( $cur_player == 'b' )
    {
      /* white castled short */
      $board[5] = ""; $board[6] = "";
      $board[4] = "wK"; $board[7] = "wR";
      $headline[5] = 1;
    }
    else
    {
      /* black castled short */
      $board[61] = ""; $board[62] = "";
      $board[60] = "bK"; $board[63] = "bR";
      $headline[7] = 1;
    }
    if ( $headline[4] != '?' )
      $undo_okay = 4;
    else
      $undo_okay = 1;
  }
  else
  if ( $move == "0-0-0" )
  {
    if ( $cur_player == 'b' )
    {
      /* white castled long */
      $board[2] = ""; $board[3] = "";
      $board[4] = "wK"; $board[0] = "wR";
      $headline[6] = 1;
    }
    else
    {
      /* black castled long */
      $board[58] = ""; $board[59] = "";
      $board[60] = "wK"; $board[56] = "bR";
      $headline[8] = 1;
    }
    if ( $headline[4] != '?' )
      $undo_okay = 4;
    else
      $undo_okay = 1;
  }
  else
  {
    /* undo normal move (includes en passant) */
    $fig = $move[0];
    $src = boardCoordToIndex( substr($move,1,2) );
    $dest = boardCoordToIndex( substr($move,4,2) );
    if ( $src >= 0 && $src < 64 && $dest >= 0 && $dest < 64 )
    {
      $board[$src] = "$old_player$fig";
      $board[$dest] = "";
      if ( $kill != "x" )
      {
        $fig = substr($kill,0,2);
        $dest = substr($kill,2,2);
        $board[$dest] = "$fig";
      }
      if ( $headline[4] != '?' )
        $undo_okay = 4;
      else
        $undo_okay = 1;
    }
  }

  if ( $undo_okay > 0 )
  {
    /* correct game state */
    if ( $move == "DrawRefused" && $move == "DrawAccepted" )
      $headline[4] = 'D';
    else
      $headline[4] = '?';
    
    /* clear own en passant flag */
    if ( $cur_player == 'b' )
      $headline[9] = 'x';
    else
      $headline[10] = 'x';
    
    /* modify history */
    if ( $undo_okay == 1 || $undo_okay == 3 )
    {
      if ( ($undo_okay == 1 && $cur_player == 'b') ||
           ($undo_okay == 3 && $cur_player == 'w') )
      {
        $headline[2] = $headline[2]-1;
      }
      else
      {
        /* remove from history */
        $aux = explode( ' ', $game[3+$headline[2]] );
        $game[3+$headline[2]] = sprintf( "%s %s\n", $aux[0], $aux[1] );
      }
    }
    else
    if ( $undo_okay == 4 )
    {
      $headline[2] = $headline[2]-1;
      if ( $cur_player == 'w')
      {
        /* remove from history */
        $aux = explode( ' ', $game[3+$headline[2]] );
        $game[3+$headline[2]] = sprintf( "%s %s\n", $aux[0], $aux[1] );
      }
    }

    /* switch players */
    if ( $cur_player == 'b' )
      $headline[3] = 'w';
    else
      $headline[3] = 'b';
    
    $headline[11] = "x"; $headline[12] = "x";

    /* save game */
    saveGame( $game, $headline, "", $gamefile );
  }
 
  return "Move '$move' undone!";
}

?>
