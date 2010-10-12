<?php
/**
 * Request viewer
 * @license GNU GPLv3 http://opensource.org/licenses/gpl-3.0.html
 * @package Kinokpk.com releaser
 * @author ZonD80 <admin@kinokpk.com>
 * @copyright (C) 2008-now, ZonD80, Germany, TorrentsBook.com
 * @link http://dev.kinokpk.com
 */


require "include/bittorrent.php";

dbconn();
loggedinorreturn();

if ($_SERVER["REQUEST_METHOD"] == 'POST')
$action = $_POST["action"];
else
$action = $_GET["action"];

$tree=make_tree();
if ($action == 'new') {
	if ($_SERVER['REQUEST_METHOD']=='POST') {
		$requesttitle = htmlspecialchars($_POST["requesttitle"]);
		if (!$requesttitle)
		stderr($REL_LANG->say_by_key('error'),"�� �� ����� ��������");
		$request = $requesttitle;
		$descr = unesc($_POST["descr"]);
		if (!$descr)
		stderr($REL_LANG->say_by_key('error'),"�� ������ ������ ��������!");
		if (!is_valid_id($_POST["category"]))
		stderr($REL_LANG->say_by_key('error'),"�� ������ ������� ��������� ��� �������!");
		$cat = (int) $_POST["category"];
		$request = sqlesc($request);
		$descr = sqlesc($descr);
		$cat = sqlesc($cat);
		sql_query("INSERT INTO requests (hits,userid, cat, request, descr, added) VALUES(1,$CURUSER[id], $cat, $request, $descr, '" . time() . "')") or sqlerr(__FILE__,__LINE__);
		$id = mysql_insert_id();


		$REL_CACHE->clearGroupCache("block-req");

		@sql_query("INSERT INTO addedrequests VALUES(0, $id, $CURUSER[id])") or sqlerr(__FILE__, __LINE__);
		safe_redirect($REL_SEO->make_link('requests','id',$id));
		die;
	}
	$REL_TPL->stdhead("������� ������");

	print("<h1>������� ������</h1><p>����� ���������� ���� �������, ������� <a href=\"".$REL_SEO->make_link('viewrequests','requestorid',$CURUSER['id'])."\">�����</a></p>\n<br />\n");
	?>
<table border=1 width=550 cellspacing=0 cellpadding=5>
	<tr>
		<td class=colhead align=left>����� ��������� (����� ���������, ��� ��
		��� ������ ��������, ������� �� �����)</td>
	</tr>
	<tr>
		<td align=left>
		<form method="get" action="<?=$REL_SEO->make_link('browse');?>"><input
			type="text" name="search" size="40"
			value="<?= htmlspecialchars($searchstr) ?>" />&nbsp;�&nbsp<?php
			print(gen_select_area('cat',$tree,(int)$_GET['cat'])."<input type=\"submit\" value=\"������!\">");
			print("</form>");
			print("</td></tr></table>");
			print("<form method=post action=\"".$REL_SEO->make_link('requests')."\" name=request>\n");
			print("<table border=1 cellspacing=0 cellpadding=5>\n");
			print("<tr><td class=colhead align=left colspan=2>��������� ����������� ����</a></td><tr>\n");
			print("<tr><td align=left><b>��������: </b><br /><input type=text size=80 name=requesttitle></td>");
			print("<td align=center><b>���������: </b><br />");
			print(gen_select_area('category',$tree));
			print("</td></tr>");
			print("<br />\n");
			print("<tr><td align=center colspan=2><b>��������: </b><br />\n");
			print textbbcode("descr");
			print ("<input type=hidden name=action value=new>");
			print("<tr><td align=center colspan=2><input type=submit value=\"������!\">\n");
			print("</form>\n");
			print("</table>\n");
			$REL_TPL->stdfoot();
			die;
}
if ($action == 'edit') {
	if ($_SERVER['REQUEST_METHOD']=='POST') {

		if (!is_valid_id($_POST["id"])) 			stderr($REL_LANG->say_by_key('error'), $REL_LANG->say_by_key('invalid_id'));
		$id = (int) $_POST["id"];
		$name = htmlspecialchars($_POST["requesttitle"]);
		$descr = $_POST["msg"];

		if (!is_valid_id($_POST["category"])) 			stderr($REL_LANG->say_by_key('error'), $REL_LANG->say_by_key('invalid_id'));
		$cat = (int) $_POST["category"];
		$name = sqlesc($name);
		$descr = sqlesc($descr);
		$cat = sqlesc($cat);

		sql_query("UPDATE requests SET cat=$cat, request=$name, descr=$descr WHERE id=$id") or sqlerr(__FILE__, __LINE__);


		$REL_CACHE->clearGroupCache("block-req");

		safe_redirect($REL_SEO->make_link('requests','id',$id));
	}
	if (!is_valid_id($_GET["id"])) 			stderr($REL_LANG->say_by_key('error'), $REL_LANG->say_by_key('invalid_id'));
	$id = (int) $_GET["id"];

	$res = sql_query("SELECT * FROM requests WHERE id = $id");
	$row = mysql_fetch_array($res);
	if ($CURUSER["id"] != $row["userid"])
	{
		if (get_user_class() < UC_MODERATOR)
		stderr("������!", "�� �� �������� ������� �������.");
	}
	$REL_TPL->stdhead("�������������� ������� \"" . $row["request"] . "\"");
	if (!$row)
	die();
	$where = "WHERE userid = " . $CURUSER["id"] . "";
	$res2 = sql_query("SELECT * FROM requests $where") or sqlerr(__FILE__, __LINE__);
	$num2 = mysql_num_rows($res2);
	print("<form method=post name=form action=\"".$REL_SEO->make_link('requests')."\"></a>\n");
	print("<table border=1 width=560 cellspacing=0 cellpadding=5>\n");
	print("<tr><td class=colhead align=left>�������������� ������� \"" . $row["request"] . "\"</td><tr>\n");
	print("<tr><td align=left>��������: <input type=text size=40 name=requesttitle value=\"" . htmlspecialchars($row["request"]) . "\">");

	print("&nbsp;���������: ".gen_select_area("category",$tree,$row['cat'])."<p /><b>��������</b>:<br />\n");
	print textbbcode("msg",$row["descr"]);
	print("<input type=\"hidden\" name=\"id\" value=\"$id\">\n");
	print("<input type=\"hidden\" name=\"action\" value=\"edit\">\n");
	print("<tr><td align=center><input type=submit value=\"�������������!\">\n");
	print("</form>\n");
	print("</table>\n");

	$REL_TPL->stdfoot();

	die;
}

if ($action=='reset')
{
	if (!is_valid_id($_GET["requestid"])) 			stderr($REL_LANG->say_by_key('error'), $REL_LANG->say_by_key('invalid_id'));
	$requestid = (int) $_GET["requestid"];
	$res = sql_query("SELECT userid, filledby FROM requests WHERE id =$requestid") or sqlerr(__FILE__, __LINE__);
	$arr = mysql_fetch_assoc($res);
	if (($CURUSER[id] == $arr[userid]) || (get_user_class() >= UC_MODERATOR) || ($CURUSER[id] == $arr[filledby]))
	{
		@sql_query("UPDATE requests SET filled='', filledby=0 WHERE id=$requestid") or sqlerr(__FILE__, __LINE__);


		$REL_CACHE->clearGroupCache("block-req");

		stderr($REL_LANG->say_by_key('success'),"������ ����� $requestid ��� ������� �������.",'success');
	}
	else
	stderr($REL_LANG->say_by_key('error'),"��������, �� �� �� ������ �������� ������ ����� �������");

}

if ($action=='filled')
{
	$filledurl = (string)$_POST["filledurl"];
	if (!is_valid_id($_POST["requestid"])) 			stderr($REL_LANG->say_by_key('error'), $REL_LANG->say_by_key('invalid_id'));
	$requestid = (int) $_POST["requestid"];
	if (substr($filledurl, 0, strlen($REL_CONFIG['defaultbaseurl'])) != $REL_CONFIG['defaultbaseurl'])
	{
		stderr($REL_LANG->say_by_key('error'), $REL_LANG->say_by_key('invalid_id'));
	}

	$res = sql_query("SELECT users.username, requests.userid, requests.request FROM requests INNER JOIN users ON requests.userid = users.id WHERE requests.id = " . sqlesc($requestid)) or sqlerr(__FILE__, __LINE__);
	$arr = mysql_fetch_assoc($res);
	$filledurl = htmlspecialchars($filledurl);
	$msg = "��� ������, <a href=\"".$REL_SEO->make_link('requests','id',$requestid)."\"><b>" . $arr['request'] . "</b></a> ��� �������� ������������� <a href=\"".$REL_SEO->make_link('userdetails','id',$CURUSER["id"],'username', translit($CURUSER["username"]))."\"><b>" . $CURUSER["username"] . "</b></a>. �� ������ ������� ��� <a href=" . $filledurl. "><b>���</b></a>. ���������� �� �������� ������� �������. ���� ��� �� ��, ��� �� ������� ��� �� �����-�� �������� ��� �� ���������� ����������, �� ������� <a href=\"".$REL_SEO->make_link('requests','action','reset','requestid',$requestid)."\">�����</a>.";
	$subject = "��� ������ ��������";
	sql_query ("UPDATE requests SET filled = " . sqlesc($filledurl) . ", filledby = $CURUSER[id] WHERE id = " . sqlesc($requestid)) or sqlerr(__FILE__, __LINE__);

	if ($REL_CRON['rating_enabled']) sql_query("UPDATE users SET ratingsum=ratingsum+{$REL_CRON['rating_perrequest']} WHERE id = {$CURUSER['id']}") or sqlerr(__FILE__,__LINE__);

	$REL_CACHE->clearGroupCache("block-req");

	sql_query("INSERT INTO messages (poster, sender, receiver, added, msg, location, subject) VALUES(0, 0, $arr[userid], '" . time() . "', " . sqlesc($msg) . ", 1, " . sqlesc($subject) . ")") or sqlerr(__FILE__, __LINE__);
	stderr($REL_LANG->say_by_key('success'),"������ ����� $requestid ��� ������� �������� � <a href=\"$filledurl\">$filledurl</a>. ������������ <a href=\"".$REL_SEO->make_link('userdetails','id',$arr['userid'],'username',translit($arr['username']))."\"><b>$arr[username]</b></a> ������������� ������� �� ���� ���������. ���� �� ������� ������ ��� �������� ������ ������������ �������, �� ���������� �������� ���� ���������� ����� <a href=\"".$REL_SEO->make_link('requests','action','reset','requestid',$requestid)."\">�����</a>.",'success');
}

if ($action == 'vote')
{
	if (!is_valid_id($_GET["voteid"])) 			stderr($REL_LANG->say_by_key('error'), $REL_LANG->say_by_key('invalid_id'));
	$requestid = (int) $_GET["voteid"];
	$userid = $CURUSER["id"];
	$res = sql_query("SELECT * FROM addedrequests WHERE requestid=$requestid AND userid = $userid") or sqlerr(__FILE__, __LINE__);
	$arr = mysql_fetch_assoc($res);
	$voted = $arr;
	if ($voted) {
		stderr($REL_LANG->say_by_key('error'), "<p>�� ��� ���������� �� ���� ������, ����� ���������� ������ ���� ��� �� ���� ������</p><p>��������� � <a href=\"".$REL_SEO->make_link('viewrequests')."\"><b>��������</b></a></p>");
	} else {
		sql_query("UPDATE requests SET hits = hits + 1 WHERE id=$requestid") or sqlerr(__FILE__, __LINE__);
		@sql_query("INSERT INTO addedrequests VALUES(0, $requestid, $userid)") or sqlerr(__FILE__, __LINE__);


		$REL_CACHE->clearGroupCache("block-req");

		stderr("��� ����� ������", "<p>��� ����� ��� ������</p><p>��������� � <a href=\"".$REL_SEO->make_link('viewrequests')."\"><b>������</b></a></p>",'success');
	}
}

if (!is_valid_id($_GET["id"])) 			stderr($REL_LANG->say_by_key('error'), $REL_LANG->say_by_key('invalid_id'));
$id = (int) $_GET["id"];

$res = sql_query("SELECT * FROM requests WHERE id = $id") or sqlerr(__FILE__, __LINE__);
$num = mysql_fetch_array($res);

if (mysql_num_rows($res) == 0)
stderr ($REL_LANG->say_by_key('error'), $REL_LANG->say_by_key('invalid_id'));

$s = $num["request"];

$REL_TPL->stdhead("������ ������� \"$s\"");

print("<table width=\"600\" border=\"1\" cellspacing=\"0\" cellpadding=\"5\">\n");
print("<tr><td class=\"colhead\" colspan=\"2\">������ ������� \"$s\"</td></tr>");
print("<tr><td align=left>������</td><td width=90% align=left>$num[request]</td></tr>");
print("<tr><td align=left>����</td><td width=90% align=left>" . format_comment($num["descr"]) . "</td></tr>");
print("<tr><td align=left>��������</td><td width=90% align=left>".mkprettytime($num[added])."</td></tr>");

$cres = sql_query("SELECT username, id, class FROM users WHERE id=$num[userid]");

$carr = mysql_fetch_assoc($cres);
$username = $carr['username'];
$user_req_id = $carr["id"];
print("<tr><td align=left>��������</td><td width=90% align=left><a href=\"".$REL_SEO->make_link('userdetails','id',$user_req_id,'username',translit($username))."\">".get_user_class_color($carr['class'], $username)."</a></td></tr>");
print("<tr><td align=left>���������� �� ���� ������</td><td width=50% align=left><a href=\"".$REL_SEO->make_link('requests','action','vote','voteid',$id)."\"><b>����������</b></a></td></tr></tr>");

if ($num["filled"] == '')
{
	print("<form method=post action=\"".$REL_SEO->make_link('requests')."\">");
	print("<tr><td align=left>��������� ������</td><td>������� <b>������</b> ����� ��������, �������� ��� ".$REL_SEO->make_link('details','id',11)." (������ ����������/�������� ��� �� ������� ����/�������)");
	print("<input type=text size=80 name=filledurl>\n");
	print("<input type=hidden value=$id name=requestid>");
	print("<input type=hidden name=action value=filled>");
	print("<input type=submit value=\"��������� ������\">\n</form></td></tr>");
}
if (get_user_class() >= UC_MODERATOR || $CURUSER["id"] == $num["userid"])
print("<tr><td align=left>�����</td><td width=50% align=left><a OnClich=\"return confirm('�� �������?')\" href=\"".$REL_SEO->make_link('viewrequests','delreq[]',$id)."\">".$REL_LANG->say_by_key('delete')."</a> <b>|</b> <a href=\"".$REL_SEO->make_link('requests','action','reset','requestid',$id)."\">�������� ����������</a>  <b>|</b>  <a href=\"".$REL_SEO->make_link('requests','action','edit','id',$id)."\">".$REL_LANG->say_by_key('edit')."</a></center></td></tr>");

$subres = sql_query("SELECT SUM(1) FROM comments WHERE toid = $id AND type='req'");
$subrow = mysql_fetch_array($subres);
$count = $subrow[0];
print("</table>");

print("<p><a name=\"startcomments\"></a></p>\n");

if (!$count) {
	print('<div id="newcomment_placeholder">'."<table style=\"margin-top: 2px;\" cellpadding=\"5\" width=\"100%\">");
	print("<tr><td class=colhead align=\"left\" colspan=\"2\">");
	print("<div style=\"float: left; width: auto;\" align=\"left\"> :: ������ ������������ ".is_i_notified($id,'reqcomments')."</div>");
	print("<div align=\"right\"><a href=\"".$REL_SEO->make_link('requests','id',$id)."#comments\">�������� �����������</a></div>");
	print("</td></tr><tr><td align=\"center\">");
	print("������������ ���. <a href=\"".$REL_SEO->make_link('requests','id',$id)."#comments\">������� ��������?</a>");
	print("</td></tr></table><br /></div>");

} else {
	list($pagertop, $pagerbottom, $limit) = pager(20, $count, $REL_SEO->make_link('requests','id',$id)."&", array(lastpagedefault => 1));
	$subres = sql_query("SELECT c.type, c.id, c.ip, c.text, c.ratingsum, c.user, c.added, c.editedby, c.editedat, u.avatar, u.warned, ".
		"u.username, u.title, u.class, u.donor, u.ratingsum AS urating, u.enabled, s.time AS last_access, e.username AS editedbyname FROM comments c LEFT JOIN users AS u ON c.user = u.id LEFT JOIN users AS e ON c.editedby = e.id  LEFT JOIN sessions AS s ON s.uid=u.id WHERE c.toid = " .
		"$id AND c.type='req' GROUP BY c.id ORDER BY c.id $limit") or sqlerr(__FILE__, __LINE__);
		$allrows = prepare_for_commenttable($subres, $s,$REL_SEO->make_link('requests','id',$id));
	print("<table class=main cellSpacing=\"0\" cellPadding=\"5\" width=\"100%\" >");
	print("<tr><td class=\"colhead\" align=\"center\" >");
	print("<div style=\"float: left; width: auto;\" align=\"left\"> :: ������ ������������</div>");
	print("<div align=\"right\"><a href=\"".$REL_SEO->make_link('requests','id',$id)."#comments\" class=altlink_white>�������� �����������</a></div>");
	print("</td></tr>");
	//		print($commentbar);
	print("<tr><td>");
	print($pagertop);
	print("</td></tr>");
	print("<tr><td>");
	commenttable($allrows);
	print("</td></tr>");
	print("<tr><td>");
	print($pagerbottom);
	print("</td></tr>");
	print("</table>");
}
$REL_TPL->assignByRef('to_id',$id);
$REL_TPL->assignByRef('is_i_notified',is_i_notified ( $id, 'reqcomments' ));
$REL_TPL->assign('textbbcode',textbbcode('text'));
$REL_TPL->assignByRef('FORM_TYPE_LANG',$REL_LANG->_('Request'));
$FORM_TYPE = 'reqcomments';
$REL_TPL->assignByRef('FORM_TYPE',$FORM_TYPE);
$REL_TPL->display('commenttable_form.tpl');
print '</table>';

//print($commentbar);
$REL_TPL->stdfoot();
die;

?>