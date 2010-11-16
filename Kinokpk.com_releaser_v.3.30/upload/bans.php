<?php
/**
 * Bans administration
 * @license GNU GPLv3 http://opensource.org/licenses/gpl-3.0.html
 * @package Kinokpk.com releaser
 * @author ZonD80 <admin@kinokpk.com>
 * @copyright (C) 2008-now, ZonD80, Germany, TorrentsBook.com
 * @link http://dev.kinokpk.com
 */

require_once ("include/bittorrent.php");

dbconn();
loggedinorreturn();
httpauth();

if (get_user_class() < UC_ADMINISTRATOR)
stderr($REL_LANG->say_by_key('error'),$REL_LANG->say_by_key('access_denied'));


if (is_valid_id($_GET['remove']))
{
	$remove = (int) $_GET['remove'];
	sql_query("DELETE FROM bans WHERE id=$remove") or sqlerr(__FILE__, __LINE__);
	write_log("��� ����� '$remove' ��� ���� ������������� $CURUSER[username]","bans");

	$REL_CACHE->clearGroupCache("bans");
	safe_redirect($REL_SEO->make_link('bans'),0);
	die();
}

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
	$mask = trim($_POST['mask']);
	$descr = trim($_POST['descr']);
	if (!$mask)
	stderr($REL_LANG->say_by_key('error'), $REL_LANG->say_by_key('missing_form_data'));
	$mask = sqlesc(htmlspecialchars($mask));
	$descr = sqlesc(htmlspecialchars($descr));
	$userid = $CURUSER['id'];
	$added = time();
	sql_query("INSERT INTO bans (mask,descr,user,added) VALUES($mask,$descr,$userid,$added)") or sqlerr(__FILE__, __LINE__);
	write_log("����� '$mask' ���� �������� ������������� $CURUSER[username]","bans");

	$REL_CACHE->clearGroupCache("bans");
	safe_redirect($REL_SEO->make_link('bans'),0);
	die;
}

$res = sql_query("SELECT bans.*, users.username, users.class FROM bans LEFT JOIN users ON bans.user = users.id ORDER BY id DESC") or sqlerr(__FILE__, __LINE__);

$REL_TPL->stdhead("���� �� IP");

if (mysql_num_rows($res) == 0)
print("<p align=\"center\"><b>".$REL_LANG->say_by_key('nothing_found')."</b></p>\n");

else
{
	//print("<table border=1 cellspacing=0 cellpadding=5>\n");
	print('<table width="100%" border="1">');
	print("<h1>���� �� IP</h1>\n");
	print("<tr><td class=\"colhead\" align=\"center\">��������</td><td class=\"colhead\" align=\"center\">IP-�����</td><td class=\"colhead\" align=\"center\">�������</td><td class=\"colhead\" align=\"center\">�������</td><td class=\"colhead\" align=\"center\">����������</td></tr>\n");

	while ($arr = mysql_fetch_assoc($res))
	{

		print("<tr><td  class=\"row1\" align=\"center\">".mkprettytime($arr['added'])."</td>".
	  "<td  class=\"row1\" align=\"center\">$arr[mask]</td>".
	  "<td  class=\"row1\" align=\"center\">$arr[descr]</td>".
	  "<td  class=\"row1\" align=\"center\"><a href='".$REL_SEO->make_link('userdetails','id',$arr['user'],'username',$arr['username'])."'>".get_user_class_color($arr['class'],$arr['username'])."</td>".
 	    "<td  class=\"row1\" align=\"center\"><a href=\"".$REL_SEO->make_link('bans','remove',$arr['id'])."\">D</a></td></tr>\n");
	}
	print('</table>');
}

print("<br />\n");
print("<form method=\"post\" action=\"".$REL_SEO->make_link('bans')."\">\n");
print('<table border=\"1\" cellspacing=\"0\" cellpadding=\"5\">');
print("<tr><td class=\"colhead\" colspan=\"2\">�������� ���</td></tr>");
print("<tr><td class=\"rowhead\">�����</td><td class=\"row1\"><input type=\"text\" name=\"mask\" size=\"40\"/></td></tr>\n");
print("<tr><td class=\"rowhead\">�������</td><td class=\"row1\"><input type=\"text\" name=\"descr\" size=\"40\"/></td></tr>\n");
print("<tr><td class=\"row1\" align=\"center\" colspan=\"2\"><input type=\"submit\" value=\"��������\" class=\"btn\"/></td></tr>\n");
print('</table>');
print("</form>\n");

$REL_TPL->stdfoot();

?>