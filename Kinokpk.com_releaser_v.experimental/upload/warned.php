<?php
/**
 * Shows warned users
 * @license GNU GPLv3 http://opensource.org/licenses/gpl-3.0.html
 * @package Kinokpk.com releaser
 * @author ZonD80 <admin@kinokpk.com>
 * @copyright (C) 2008-now, ZonD80, Germany, TorrentsBook.com
 * @link http://dev.kinokpk.com
 */

require "include/bittorrent.php";

dbconn();

loggedinorreturn();

if (get_user_class() < UC_MODERATOR)
stderr($REL_LANG->say_by_key('error'), "�������� � �������.");

$REL_TPL->stdhead("��������������� ������������");
$warned = number_format(get_row_count("users", "WHERE warned=1"));
$REL_TPL->begin_frame("��������������� ������������: ($warned)", true);

$res = sql_query("SELECT id,username,added,last_access,ratingsum,warneduntil,donor,enabled,warned,class FROM users WHERE warned=1 ORDER BY users.warneduntil") or sqlerr(__FILE__, __LINE__);
$num = mysql_num_rows($res);
print("<table border=1 width='100%' ellspacing=0 cellpadding=2><form action=\"".$REL_SEO->make_link('nowarn')."\" method=post>\n");
print("<tr align=center><td class=colhead width=90>������������</td>
<td class=colhead width=70>���������������</td>
<td class=colhead width=75>���������&nbsp;���&nbsp;���&nbsp;��&nbsp;�������</td>
<td class=colhead width=75>�����</td>
<td class=colhead width=45>�������</td>
<td class=colhead width=125>���������</td>
<td class=colhead width=65>������</td>
<td class=colhead width=65>���������</td></tr>\n");
for ($i = 1; $i <= $num; $i++)
{
	$arr = mysql_fetch_assoc($res);

	$ratio = ratearea($arr['ratingsum'],$arr['id'],'users',$CURUSER['id']);

	$added = mkprettytime($arr['added']);
	$last_access = get_elapsed_time($arr['last_access'])." {$REL_LANG->say_by_key('ago')}";
	$class=get_user_class_name($arr["class"]);

	print("<tr><td align=left><a href=\"".$REL_SEO->make_link('userdetails','id',$arr['id'],'username',translit($arr['username']))."\"><b>$arr[username]</b></a>" .get_user_icons($arr)."</td>
<td align=center>$added</td>
<td align=center>$last_access</td>
<td align=center>$class</td>
<td align=center>$ratio</td>
<td align=center>".($arr[warneduntil]?'����� '.get_elapsed_time($arr[warneduntil]):'�������')."</td>
<td bgcolor=\"#008000\" align=center><input type=\"checkbox\" name=\"usernw[]\" value=\"$arr[id]\"></td>
<td bgcolor=\"#FF0000\" align=center><input type=\"checkbox\" name=\"desact[]\" value=\"$arr[id]\"".(!$arr['enabled']?' checked':'')."></td></tr>\n");
}

print("<tr><td colspan=10 align=right><input type=\"submit\" name=\"submit\" value=\"���������\"></td></tr>\n");
print("<input type=\"hidden\" name=\"nowarned\" value=\"nowarned\"></form></table>\n");

print("<p>$pagemenu<br />$browsemenu</p>");

$REL_TPL->end_frame();

$REL_TPL->stdfoot();
?>
