<?php
/**
 * Invites
 * @license GNU GPLv3 http://opensource.org/licenses/gpl-3.0.html
 * @package Kinokpk.com releaser
 * @author ZonD80 <admin@kinokpk.com>
 * @copyright (C) 2008-now, ZonD80, Germany, TorrentsBook.com
 * @link http://dev.kinokpk.com
 */

require_once "include/bittorrent.php";

dbconn();


loggedinorreturn();

if (isset($_GET['id']) && !is_valid_id($_GET['id'])) stderr($REL_LANG->say_by_key('error'),$REL_LANG->say_by_key('invalid_id'));

$id = (int) $_GET["id"];
$type = unesc($_GET["type"]);
$invite = $_GET["invite"];

$REL_TPL->stdhead("�����������");

function bark($msg) {
	global $REL_TPL;
	$REL_TPL->stdmsg("������", $msg,'error');
	$REL_TPL->stdfoot();
}

if ($id == 0) {
	$id = $CURUSER["id"];
}

if ($type == 'new') {
	print("<form method=post action=\"".$REL_SEO->make_link('takeinvite')."\">".
	"<input type=hidden name=id value=$id />".
	"<table border=1 width=100% cellspacing=0 cellpadding=5>".
	"<tr class=tabletitle><td colspan=2><b>������� ��������������� ���</b></td></tr>".
	"<tr class=tableb><td align=center colspan=2>{$REL_LANG->say_by_key('invite_notice_get')}</td></tr>");
	tr("������� email, ���� ����� ������� �����������:",'<input type="text" size="40" name="email">',1,1);
	if ($REL_CONFIG['use_captcha']) {

		require_once('include/recaptchalib.php');
		tr ("������� ����� � ��������:",recaptcha_get_html($REL_CONFIG['re_publickey']),1,1);

	}

	print (	"<tr class=tableb><td align=center colspan=2><input type=submit value=\"�������\"></td></tr>".
	"</form></table>");
} elseif ($type == 'del') {
	$ret = sql_query("SELECT * FROM invites WHERE invite = ".sqlesc($invite)) or sqlerr(__FILE__,__LINE__);
	$num = mysql_fetch_assoc($ret);
	if ($num[inviter]==$id) {
		sql_query("DELETE FROM invites WHERE invite = ".sqlesc($invite)) or sqlerr(__FILE__,__LINE__);
		stdmsg("�������", "����������� �������.");
	} else
	stdmsg("������", "��� �� ��������� ������� �����������.");
} else {
	if (get_user_class() <= UC_UPLOADER && !($id == $CURUSER["id"])) {
		bark("� ��� ��� ����� ������ ����������� ����� ������������.");
	}


	$ret = sql_query("SELECT u.id, u.username, u.class, u.email, u.ratingsum, u.warned, u.enabled, u.donor, u.email, invites.confirmed FROM invites LEFT JOIN users AS u ON u.id=invites.inviteid WHERE invitedby = $id") or sqlerr(__FILE__,__LINE__);
	$num = mysql_num_rows($ret);
	print("<form method=post action=\"".$REL_SEO->make_link('takeconfirm','id',$id)."\"><table border=1 width=100% cellspacing=0 cellpadding=5>".
	"<tr class=tabletitle><td colspan=7><b>������ ������������ ����</b> (".(int)$num.")</td></tr>");

	if(!$num) {
		print("<tr class=tableb><td colspan=7>��� ����� ���� �� ���������.</tr>");
	} else {
		print("<tr class=tableb><td><b>������������</b></td><td><b>Email</b></td><td><b>�������</b></td><td><b>������</b></td>");
		if ($CURUSER[id] == $id || get_user_class() >= UC_MODERATOR)
		print("<td align=center><b>�����������</b></td>");
		print("</tr>");
		for ($i = 0; $i < $num; ++$i) {
			$arr = mysql_fetch_assoc($ret);
			if (!$arr[confirmed])
			$user = "<td align=left>$arr[username]</td>";
			else
			$user = "<td align=left><a href=\"".$REL_SEO->make_link('userdetails','id',$arr['id'],'username',translit($arr['username']))."\">" . get_user_class_color($arr["class"], "$arr[username]") . "</a>" . ($arr["warned"] ? "&nbsp;<img src=pic/warned.gif border=0 alt='Warned'>" : "") . (!$arr["enabled"] ? "&nbsp;<img src=pic/disabled.gif border=0 alt='Disabled'>" : "") . ($arr["donor"] ? "&nbsp;<img src=pic/star.gif border=0 alt='Donor'>" : "")."</td>";

			$ratio = (($arr['ratingsum']>0)?"+{$arr['ratingsum']}":$arr['ratingsum']);

			if ($arr["confirmed"])
			$status = "<a href=\"".$REL_SEO->make_link('userdetails','id',$arr['id'],'username',translit($arr['username']))."\"><font color=green>�����������</font></a>";
			else
			$status = "<font color=red>�� �����������</font>";

			print("<tr class=tableb>$user<td>$arr[email]</td><td>$ratio</td><td>$status</td>");

			if ($CURUSER[id] == $id || get_user_class() >= UC_SYSOP) {
				print("<td align=center>");
				if (!$arr[confirmed])
				print("<input type=\"checkbox\" name=\"conusr[]\" value=\"" . $arr[id] . "\" />");
				print("</td>");
			}
			print("</tr>");
		}
	}
	if ($CURUSER[id] == $id || get_user_class() >= UC_SYSOP) {
		print("<input type=hidden name=email value=$arr[email]>");
		print("<tr class=tableb><td colspan=7 align=right><input type=submit value=\"����������� �������������, �������� ������� � �������� �� � ������!\"></form></td></tr>");
	}
	print("</table><br />");

	$rul = sql_query("SELECT SUM(1) FROM invites WHERE inviter = $id") or sqlerr(__FILE__,__LINE__);
	$arre = mysql_fetch_row($rul);
	$number1 = $arre[0];
	$rer = sql_query("SELECT invite, time_invited FROM invites WHERE inviter = $id AND confirmed=0") or sqlerr(__FILE__,__LINE__);
	$num1 = mysql_num_rows($rer);

	print("<table border=1 width=100% cellspacing=0 cellpadding=5>".
	"<tr class=tabletitle><td colspan=6><b>������ �������� �����������</b> ($number1)</td></tr>");

	if(!$num1) {
		print("<tr class=tableb><td colspan=6>�� ������ ������ ���� �� ������� �������� �����������.</tr>");
	} else {
		print("<tr class=tableb><td><b>��� �����������</b></td><td><b>���� ��������</b></td><td>�������</td></tr>");
		for ($i = 0; $i < $num1; ++$i) {
			$arr1 = mysql_fetch_assoc($rer);
			print("<tr class=tableb><td>$arr1[invite]</td><td>".mkprettytime($arr1['time_invited'])."</td>");
			print ("<td><a href=\"".$REL_SEO->make_link('invite','invite',$arr1['invite'],'type','del')."\" onClick=\"return confirm('�� �������?')\">������� �����������</a></td></tr>");
		}
	}

	print("<tr class=tableb><td colspan=7 align=center><form method=get action=\"".$REL_SEO->make_link('invite','id',$id,'type','new')."\"><input type='hidden' name='id' value='$id' /><input type='hidden' name='type' value='new' /><input type=submit value=\"������� �����������\"></form></td></tr>");
	print("</table>");
}
$REL_TPL->stdfoot();

?>
