<?php
/**
 * Global user statistics
 * @license GNU GPLv3 http://opensource.org/licenses/gpl-3.0.html
 * @package Kinokpk.com releaser
 * @author ZonD80 <admin@kinokpk.com>
 * @copyright (C) 2008-now, ZonD80, Germany, TorrentsBook.com
 * @link http://dev.kinokpk.com
 */

require_once "include/bittorrent.php";

dbconn();

loggedinorreturn();

$page = (int) $_GET["page"];

$search = (string) $_GET['search'];

$search = htmlspecialchars(trim($search));

$class = (int) $_GET['class'];
if ($class == '-' || !is_valid_user_class($class))
$class = '';

if ($search != '' || $class) {
	$query = "username LIKE '%" . sqlwildcardesc($search) . "%' AND confirmed=1";
	if ($search)
	$q = "search=" . $search;
}

if (is_valid_user_class($class)) {
	$query .= " AND class = $class";
	$q .= ($q ? "&amp;" : "") . "class=$class";
}

if ($query) $query = " WHERE ".$query;


$REL_TPL->stdhead($REL_LANG->say_by_key('users'));


if ((get_user_class () >= UC_MODERATOR) && $_GET['act']) {
	if ($_GET['act'] == "users") {
		$REL_TPL->begin_frame("������������ � ��������� ���� 0");

		echo '<table width="100%" border="0" align="center" cellpadding="2" cellspacing="0">';
		echo "<tr><td class=colhead align=left>������������</td><td class=colhead>�������</td><td class=colhead>IP</td><td class=colhead>���������������</td><td class=colhead>��������� ��� ��� �� �������</td>"/*<td class=colhead>��������</td><td class=colhead>�������</td>*/."</tr>";


		$result = sql_query ("SELECT users.id,users.username,users.class,users.ratingsum,users.added,users.last_access,users.ip"/*, (SELECT SUM(1) FROM peers WHERE seeder=1 AND userid=users.id) AS seeding, (SELECT SUM(1) FROM snatched LEFT JOIN torrents ON snatched.torrent=torrents.id WHERE snatched.finished=1 AND torrents.free=0 AND NOT FIND_IN_SET(torrents.freefor,userid) AND userid=users.id AND snatched.userid<>torrents.owner) AS downloaded*/." FROM users WHERE ratingsum<0 AND enabled = 1 ORDER BY ratingsum DESC");
		while ($row = mysql_fetch_array($result)) {
			$records = true;
			$ratio = ratearea($row['ratingsum'],$row['id'],'users', $CURUSER['id']);
			echo "<tr><td><a href=\"".$REL_SEO->make_link('userdetails','id',$row["id"],'username',translit($row["username"]))."\"><b>".$row["username"]."</b></a></td><td><strong>".$ratio."</strong></td><td>".$row["ip"]."</td><td>".mkprettytime($row["added"])."</td><td>".mkprettytime($row["last_access"])." (".get_elapsed_time($row["last_access"],false)." {$REL_LANG->say_by_key('ago')})</td>"/*<td>".(int)$row['downloaded']."</td><td>".(int)$row['seeding']."</td>*/."</tr>";


		}
		if (!$records) stdmsg($REL_LANG->say_by_key('error'),$REL_LANG->say_by_key('nothing_found'),'error');

		echo "</table>";
		$REL_TPL->end_frame(); }

		elseif ($_GET['act'] == "last") {
			$REL_TPL->begin_frame("��������� ������������");

			echo '<table width="100%" border="0" align="center" cellpadding="2" cellspacing="0">';
			echo "<tr><td class=colhead align=left>������������</td><td class=colhead>�������</td><td class=colhead>IP</td><td class=colhead>���������������</td><td class=colhead>���������&nbsp;���&nbsp;���&nbsp;��&nbsp;�������</td></tr>";

			$result = sql_query ("SELECT * FROM users WHERE enabled = 1 AND confirmed=1 ORDER BY added DESC LIMIT 100");
			while($row = mysql_fetch_array($result)) {
				$records = true;
				$ratio = ratearea($row['ratingsum'],$row['id'],'users', $CURUSER['id']);
				echo "<tr><td><a href=\"".$REL_SEO->make_link('userdetails','id',$row["id"],'username',translit($row["username"]))."\"><b>".$row["username"]."</b></a></td><td><strong>".$ratio."</strong></td><td>".$row["ip"]."</td><td>".mkprettytime($row["added"])."</td><td>".mkprettytime($row["last_access"])."</td></tr>";


			}
			if (!$records) stdmsg($REL_LANG->say_by_key('error'),$REL_LANG->say_by_key('nothing_found'),'error');
			echo "</table>";
			$REL_TPL->end_frame(); }

			elseif ($_GET['act'] == "banned") {
				$REL_TPL->begin_frame("��������� ������������");

				echo '<table width="100%" border="0" align="center" cellpadding="2" cellspacing="0">';
				echo "<tr><td class=colhead align=left>������������</td><td class=colhead>�������</td><td class=colhead>IP</td><td class=colhead>���������������</td><td class=colhead>��������� ��� ���</td></tr>";
				$result = sql_query ("SELECT * FROM users WHERE enabled = 0 ORDER BY last_access DESC ");
				if ($row = mysql_fetch_array($result)) {
					do {
						$ratio = ratearea($row['ratingsum'],$row['id'],'users', $CURUSER['id']);
						echo "<tr><td><a href=\"".$REL_SEO->make_link('userdetails','id',$row["id"],'username',translit($row["username"]))."\"><b>".$row["username"]."</b></a></td><td><strong>".$ratio."</strong></td><td>".$row["ip"]."</td><td>".mkprettytime($row["added"])."</td><td>".mkprettytime($row["last_access"])."</td></tr>";


					} while($row = mysql_fetch_array($result));
				} else {print "<tr><td colspan=7>��������, ������� �� ����������!</td></tr>";}
				echo "</table>";
				$REL_TPL->end_frame(); }

}
elseif (!isset($_GET['act'])) {

	print("<h1>������������</h1>\n");
	print("<div class=\"friends_search\">");
	print("<form method=\"get\" style='margin-bottom: 20px;' action=\"".$REL_SEO->make_link('users')."\">\n");
	print("<span class='browse_users'>".$REL_LANG->say_by_key('search')."<input type=\"text\" size=\"30\" name=\"search\" value=\"".$search."\"></span> \n");
	print("<select name=\"class\">\n");
	print("<option value=\"-\">(��� ������)</option>\n");
	for ($i = 0;;++$i) {
		if ($c = get_user_class_name($i))
		print("<option value=\"$i\"" . (is_valid_user_class($class) && $class == $i ? " selected" : "") . ">$c</option>\n");
		else
		break;
	}
	print("</select>\n");
	print("<input type=\"submit\" class=\"button\" style=\"margin-top:5px\" value=\"{$REL_LANG->say_by_key('go')}\">\n");
	print("</form>\n");
	print("</div\n");

	$res = sql_query("SELECT SUM(1) FROM users$query") or sqlerr(__FILE__, __LINE__);
	$count = mysql_result($res,0);
	if (!$count) { stdmsg($REL_LANG->say_by_key('error'),$REL_LANG->say_by_key('nothing_found'),'error'); $REL_TPL->stdfoot(); die(); }
	$perpage = 50;
	list($pagertop, $pagerbottom, $limit) = pager($perpage, $count, "users.php?".($q?$q.'&amp;':''));



	$res = sql_query("SELECT u.*, c.name, c.flagpic FROM users AS u LEFT JOIN countries AS c ON c.id = u.country$query ORDER BY username $limit") or sqlerr(__FILE__, __LINE__);
	$num = mysql_num_rows($res);

	print ('<div id="users-table">');
	print ("<p>$pagertop</p>");
	print("<table cellspacing=\"0\" cellpadding=\"5\" border=\"1\" style=\"width: 964px;\">\n");
	print("<tr><td class=\"colhead\" align=\"left\">���</td><td class=\"colhead\">���������������</td><td class=\"colhead\">��������� ����</td><td class=\"colhead\">�������</td><td class=\"colhead\">���</td><td class=\"colhead\" align=\"left\">�������</td><td class=\"colhead\">������</td></tr>\n");
	while ($arr = mysql_fetch_assoc($res)) {
		if ($arr['country'] > 0) {
			$country = "<td style=\"padding: 0px\" align=\"center\"><img src=\"pic/flag/$arr[flagpic]\" alt=\"$arr[name]\" title=\"$arr[name]\"></td>";
		}
		else
		$country = "<td align=\"center\">---</td>";
		$ratio = ratearea($arr['ratingsum'],$arr['id'],'users', $CURUSER['id']);

		if ($arr["gender"] == "1") $gender = "<img src=\"pic/male.gif\" alt=\"������\" title=\"������\" style=\"margin-left: 4pt\">";
		elseif ($arr["gender"] == "2") $gender = "<img src=\"pic/female.gif\" alt=\"�������\" title=\"�������\" style=\"margin-left: 4pt\">";
		else $gender = "<div align=\"center\"><b>?</b></div>";

		print("<tr><td align=\"left\"><a href=\"".$REL_SEO->make_link('userdetails','id',$arr['id'],'username',translit($arr["username"]))."\"><b>".get_user_class_color($arr["class"], $arr["username"])."</b></a>" .($arr["donated"] > 0 ? "<img src=\"pic/star.gif\" border=\"0\" alt=\"Donor\">" : "")."</td>" .
"<td>".mkprettytime($arr['added'])."</td><td>".mkprettytime($arr['last_access'])." (".get_elapsed_time($arr["last_access"],false)." {$REL_LANG->say_by_key('ago')})</td><td>$ratio</td><td>$gender</td>".
"<td align=\"left\">" . get_user_class_name($arr["class"]) . "</td>$country</tr>\n");
	}
	print("</table>\n");
	print ("<p>$pagerbottom</p>");
	print('</div></div>');

}
$REL_TPL->stdfoot();

?>