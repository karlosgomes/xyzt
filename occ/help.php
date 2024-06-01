<?
/* ***** VERIFY SESSION ***** */
include "check_session.php";
if ( $username == "" )
  header( "location:index.php" );
?>

<HTML>

<HEAD>
<TITLE>Chess Online</TITLE>
<LINK rel=stylesheet type="text/css" href="style.css">
</HEAD>

<BODY bgcolor="#ffffff" link="" alink="" vlink="">

<A name="top">

<TABLE width=100% border=0 cellspacing=10 ><TR><TD align="center">
<TABLE width=500 border=0 cellspacing=10 ><TR><TD valign="top">

<? 
if ( $_GET["main"] == "yes" ) { 
?>
<P><A href="index.php">Back to Mainpage</A></P>

<P><B>Online Chess Help</B></P>

<UL>
<LI>At the top you will find your login name and your statistics
so far: the number of wins, draws and losses. Below that you
can choose which games you want to display:
<TABLE border=0 bgcolor="#f2f2f2">
<TR><TD><B>My Games</B></TD><TD>is a list of all games you are 
playing actively. A green light to the left-hand side 
indicates whether it is your turn to move.</TD></TR>
<TR><TD><B>Games Of Friends</B></TD><TD>shows
all current games played between friends of yours.</TD></TR>
<TR><TD><B>My Archived Games</B></TD><TD>is a list of all
finished games you were involved in.</TD></TR>
<TR><TD><B>Archived Games Of Friends</B></TD><TD>shows all
finished games between your friends.</TD></TR>
</TABLE>
If any games show up you can view the chess board by clicking
<I>Enter</I> at the right-hand side of the game info.
</LI>
<LI>
There are two modes for a chess board: <i>input mode</i> and 
<i>browsing mode</i>. <i>Input mode</i> is default for the 
games you play and <i>browsing
mode</i> is default for games of friends or any archived games. The
difference is as follows: <i>Input mode</i> allows to refresh a 
running game
and to enter a move if it is your turn. Only the very current state
of the chess board can be viewed. If you want to analyze
or simply review a game <i>browsing mode</i> comes in. Here you 
can step
forward and backward and go to any move and view the situation on
the board. Useful to get the idea of old games or games you do
not participate in.
</LI>
<LI>You can create a new game against another player by using
the form below the list. You have to specify your opponent
and the color with which you want to play. The game is then
instantly created. Thus only use this when you have appointed
a game with another user by mail.</LI>
<LI>If one user finishes the game either by checkmating the
opponent, aggreeing to a draw or surrender, the other user will
have the possibility to either archive or delete the game.
If (s)he chooses to archive it, the game will be visible to
both users as a finished game.</LI>
<LI>If you enter a chess game a different help will be displayed
explaining how to actually play the game. (Just the interface, not 
the rules. These you should know.)</LI>
</UL>

<? } else { ?>
<P><A href="chess.php">Back to chess board</A></P>

<P><B>Online Chess Help</B></P>

<UL>
<LI>Moves can only be entered in Input mode if you participate
in the game. Check the links above the chess board: if there is
a <I>History Browser</I> link you are in Input mode and if there
is a <I>Input Mode</I> link you are in Browsing mode. Follow the
link to switch between the modes.<BR>
Browsing mode allows to re-view the whole game step by step. You
can rotate the board with the link by that name to view it
from your opponent's perspective. Note that this will reset
the board to the last move.<BR>
As this is all you need to know about Browsing mode the rest
of the help will explain how to play in Input mode.
</LI>
<LI>
The primary way of moving a chessman is to click at it and its
destination position. The full command is then written to the 
editable. However, in some cases you might want to enter a command
directly (or always if you are a geek as I am :). Special 
actions as described below must always be entered by keyboard
so read further!</LI>
<LI>When it is your turn you enter your move in the editable 
<I>Your Move</I>. Such a move is composed of four parts
without blanks in between:<BR>
<i>chessman identifier</I>
<TABLE border=0 bgcolor="#f2f2f2">
<TR><TD><B>K</B></TD><TD>King</TD></TR>
<TR><TD><B>Q</B></TD><TD>Queen</TD></TR>
<TR><TD><B>B</B></TD><TD>Bishop</TD></TR>
<TR><TD><B>N</B></TD><TD>Knight</TD></TR>
<TR><TD><B>R</B></TD><TD>Rook</TD></TR>
<TR><TD><B>P</B></TD><TD>Pawn</TD></TR>
</TABLE>
<I>chessman position</I>
<TABLE border=0 bgcolor="#f2f2f2">
<TR><TD><B>e4, f2, h8</B></TD><TD>for example</TD></TR>
</TABLE>
<I>action identifier</I>
<TABLE border=0 bgcolor="#f2f2f2">
<TR><TD><B>-</B></TD><TD>move</TD></TR>
<TR><TD><B>x</B></TD><TD>attack</TD></TR>
</TABLE>
<I>destination position</I>
<TABLE border=0 bgcolor="#f2f2f2">
<TR><TD><B>a8, c6, g5</B></TD><TD>for example</TD></TR>
</TABLE>
However, you can make use of OCC's autocomplete function
which allows entering moves in the normal chess notation.
Instead of 
<TABLE border=0 bgcolor="#f2f2f2">
<TR><TD><B>Pe2-e4</B></TD><TD>move pawn from e2 to e4.</TD></TR>
<TR><TD><B>Pf4xe5</B></TD><TD>let pawn at f4 attack a chessman at e5</TD></TR>
<TR><TD><B>Nf3xe5</B></TD><TD>let knight at f3 attack a chessman at e5</TD></TR>
<TR><TD><B>Qd8-h4</B></TD><TD>move queen from d8 to h4</TD></TR>
</TABLE>
which will work fine you could also enter
<TABLE border=0 bgcolor="#f2f2f2">
<TR><TD><B>e4</B></TD><TD>move pawn from e2 to e4.</TD></TR>
<TR><TD><B>fxe5</B></TD><TD>let pawn at f4 attack a chessman at e5</TD></TR>
<TR><TD><B>Nxe5</B></TD><TD>let knight at f3 attack a chessman at e5</TD></TR>
<TR><TD><B>Qh4</B></TD><TD>move queen from d8 to h4</TD></TR>
</TABLE>
which is more convenient.<BR>
As you can see, a pawn is not identified by a P but the lower case
letter of the file it is on. Thus pawn moves are always non-ambiguous.
Other chessmen moves might be ambiguous, however. In that case
you have to specify one coordinate
of the chessman's position. Imagine two white rooks at h6 and h2 and
a black queen at h4. The command <B>Rxh4</B> is now ambiguous, which
rook to move? This is resolved by adding either <B>2</B> or <B>6</B>
right after the chessman identifier: <B>R2xh4</B>. Note that
<B>Rhxh4</B> will obviously not resolve the ambiguity, thus it will
result in an error.<BR> 
<I>Note: It is important to have the chessman identifier 
upper case and the coordinates lower case! Only valid and non-ambiguous
moves will be executed.</LI>
<LI>
When a pawn enters the last file it will become a queen  by default.
If for some reason you do not want a queen you have
to add the chessman identifier (N,B,R,Q) right after your command like
<B>b8N</B> for example.
</LI>
<LI>There are five special actions:
<TABLE border=0>
<TR><TD><B>DELETE</B></TD><TD>Delete the game without affecting the 
statistics. This will only work if you have not moved yet.</TD></TR>
<TR><TD><B>---</B></TD><TD>Surrender and let your opponent win.</TD></TR>
<TR><TD><B>draw?</B></TD><TD>Offer a draw to your opponent. If he
refuses it is your turn again else the game ends.</TD></TR>
<TR><TD><B>0-0</B></TD><TD>Do a short castling thus at the king's side
of the board. (use zeros; not the letter O!)</TD></TR>
<TR><TD><B>0-0-0</B></TD><TD>Do a long castling thus at the queen's
side of the board. (use zeros; not the letter O!)</TD></TR>
</TABLE>
<I>Note: Whether castling is possible is displayed to the left-hand 
side of
the board. According to the rules it is not possible to castle when
the king is checked and when either king or rook would be attacked
after castling! Naturally, both must not move before castling.</I></LI>

<LI>
The 'en passant' rule is as follows: If a pawn moves two tiles
in its first move and skips a tile which is under attack by an
enemy pawn, this enemy pawn might kill the pawn just as if it 
moved only one tile forward. Here an example:
<UL>
<LI>Imagine a white pawn at d2 and a black pawn
at c4. It's white's turn.</LI>
<LI>White moves the pawn from d2 to d4. Both pawns or now 
side by side.</LI>
<LI>'En passant' means: Black can do the move <B>cxd3</B>
thus act like the white pawn just moved to d3 instead of
d4.</LI>
<LI><I>The possibility of 'en passant' does only apply to the very
next move of black! Black cannot move a different chessman and try
to kill the pawn at d4 like this the next turn. (same applies
to white if you revert the colors)</I></LI>
</UL>
</LI>

<LI>With each move you make you can also give a comment of
any kind you like. Just a little chatting, an explanation of
your move and so on. Only the last comment will be displayed.
If you do not set a comment with your move it is reset to
<I>no chatter</I> anyway.</LI>

<LI>All moves are saved and you can view the complete history
to the lower left-hand side of the screen. Checks are marked
automatically.</LI>

<LI>
If there is an imbalance of chessmen it will be
displayed to the right-hand side of the board.
</LI>

<LI>The program detects checkmate and stalemate and will end
the game then. If this happens the other player can move
the game to the archive or delete it. As soon as he/she did
so it is removed from the list of current games but can
be viewed in the 'Finished Games' archive.</LI>

<LI>
When the game is over the statistics of both players are 
updated. It is simply count how often one player has won,
lost or drawed. There is no separate counting for each
partner.</LI>

<LI>
The <I>PGN Format</I> link displays the game in the wide-spread 
PGN format. If you save the plain text information to a
.pgn file you will be able to view it with most chess programs.
This allows you to have it analyzed by a computer or to add
alternative moves.
</LI>

<LI>
<B>TODO:</B> The stalemate test does not recognize whether a chessman
is bound by check. So it might assume the possibility of further moves
when there isn't. However you can work around this by offering a draw.
</LI>

</UL>

<? } ?>


<A href="#top">Top</A>

<?include "misc.php";
showFooter();?>
</TD></TR></TABLE>
</TD></TR></TABLE>

</BODY>
</HTML>
