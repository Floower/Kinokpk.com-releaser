<?php

/*
 Project: Kinokpk.com releaser
 This file is part of Kinokpk.com releaser.
 Kinokpk.com releaser is based on TBDev,
 originally by RedBeard of TorrentBits, extensively modified by
 Gartenzwerg and Yuna Scatari.
 Kinokpk.com releaser is free software;
 you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.
 Kinokpk.com is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 You should have received a copy of the GNU General Public License
 along with Kinokpk.com releaser; if not, write to the
 Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston,
 MA  02111-1307  USA
 Do not remove above lines!
 */

require_once("include/bittorrent.php");

$action = (string) $_GET["action"];

dbconn();

loggedinorreturn();

if ($action == "add")
{
	if ($_SERVER["REQUEST_METHOD"] == "POST")
	{
		if(!is_valid_id($_POST["pid"])) stderr($REL_LANG->say_by_key("error"),$REL_LANG->say_by_key("invalid_id"));

		$pid = (int) $_POST["pid"];
		$pollname = @mysql_result(sql_query("SELECT question FROM polls WHERE id=$pid"),0);
		if (!$pollname) stderr($REL_LANG->say_by_key("error"),$REL_LANG->say_by_key("invalid_id"));
		$text = trim(((string)$_POST["text"]));
		if (!$text)
		stderr($REL_LANG->say_by_key('error'), $REL_LANG->say_by_key('comment_cant_be_empty'));

		// ANTISPAM AND ANTIFLOOD SYSTEM
		$last_pmres = sql_query("SELECT ".time()."-added AS seconds, text AS msg, id, poll AS torrent FROM pollcomments WHERE user=".$CURUSER['id']." ORDER BY added DESC LIMIT 4");
		while ($last_pmresrow = mysql_fetch_array($last_pmres)){
			$last_pmrow[] = $last_pmresrow;
			$msgids[] = $last_pmresrow['id'];
			$torids[] = $last_pmresrow['torrent'];
		}
		//   print_r($last_pmrow);
		if ($last_pmrow[0]){
			if (($REL_CONFIG['as_timeout'] > round($last_pmrow[0]['seconds'])) && $REL_CONFIG['as_timeout']) {
				$seconds =  $REL_CONFIG['as_timeout'] - round($last_pmrow[0]['seconds']);
				stderr($REL_LANG->say_by_key('error'),"�� ����� ����� ����� ������ �� �����, ����������, ��������� ������� ����� $seconds ������. <a href=\"javascript: history.go(-1)\">�����</a>");
			}

			if ($REL_CONFIG['as_check_messages'] && ($last_pmrow[0]['msg'] == $text) && ($last_pmrow[1]['msg'] == $text) && ($last_pmrow[2]['msg'] == $text) && ($last_pmrow[3]['msg'] == $text)) {
				$msgview='';
				foreach ($msgids as $key => $msgid){
					$msgview.= "\n<a href=\"".$REL_SEO->make_link('polloverview','id',$torids[$key])."#comm$msgid\">����������� ID={$msgid}</a> �� ������������ ".$CURUSER['username'];
				}
				$modcomment = sql_query("SELECT modcomment FROM users WHERE id=".$CURUSER['id']);
				$modcomment = mysql_result($modcomment,0);
				if (strpos($modcomment,"Maybe spammer in poll comments") === false) {
					$arow = sql_query("SELECT id FROM users WHERE class = '".UC_SYSOP."'");

					while (list($admin) = mysql_fetch_array($arow)) {
						sql_query("INSERT INTO messages (poster, sender, receiver, added, msg, subject, location) VALUES(0, 0,
						$admin, '" . time() . "', '������������ <a href=\"".$REL_SEO->make_link('userdetails','id',$CURUSER['id'],'username',translit($CURUSER['username']))."\">".$CURUSER['username']."</a> ����� ���� ��������, �.�. ��� 5 ��������� ��������� ������������ � ������� ��������� ���������.$msgview', '��������� � �����!', 1)") or sqlerr(__FILE__, __LINE__);
					}
					$modcomment .= "\n".time()." - Maybe spammer in poll comments";
					sql_query("UPDATE users SET modcomment = ".sqlesc($modcomment)." WHERE id =".$CURUSER['id']);

				} else {
					sql_query("UPDATE users SET enabled=0, dis_reason='Spam in poll comments' WHERE id=".$CURUSER['id']);

					$arow = sql_query("SELECT id FROM users WHERE class = '".UC_SYSOP."'");

					while (list($admin) = mysql_fetch_array($arow)) {
						sql_query("INSERT INTO messages (poster, sender, receiver, added, msg, subject, location) VALUES(0, 0,
						$admin, '" . time() . "', '������������ <a href=\"".$REL_SEO->make_link('userdetails','id',$CURUSER['id'],'username',translit($CURUSER['username']))."\">".$CURUSER['username']."</a> ������� �������� �� ���� � ������������ � �������, ��� IP ����� (".$CURUSER['ip'].")', '��������� � ����� [���]!', 1)") or sqlerr(__FILE__, __LINE__);
						stderr("�����������!","�� ������� �������� �������� �� ���� � ������������ � �������! ���� �� �� �������� � �������� �������, <a href=\"".$REL_SEO->make_link('contact')."\">������� ������ �������</a>.");
					}
				}
				stderr($REL_LANG->say_by_key('error'),"�� ����� ����� ����� ������ �� �����, ���� 5 ��������� ������������ � ������� ���������. � ������� ����������� ��������. <b><u>��������! ���� �� ��� ��� ����������� ��������� ���������� ���������, �� ������ ������������� ������������� ��������!!!</u></b> <a href=\"javascript: history.go(-1)\">�����</a>");

			}
		}

		// ANITSPAM SYSTEM END
		sql_query("INSERT INTO pollcomments (user, poll, added, text, ip) VALUES (" .
		$CURUSER["id"] . ",$pid, '" . time() . "', " . sqlesc($text) .
	       "," . sqlesc(getip()) . ")") or die(mysql_error());

		$newid = mysql_insert_id();


		$REL_CACHE->clearGroupCache("block-polls");
		send_comment_notifs($pid,"<a href=\"".$REL_SEO->make_link('polloverview','id',$pid)."#comm$newid\">{$pollname}</a>",'pollcomments');

		safe_redirect($REL_SEO->make_link('polloverview','id',$pid)."#comm$newid");
		die;
	}
}
elseif ($action == "quote")
{
	if(!is_valid_id($_GET["cid"])) stderr($REL_LANG->say_by_key("error"),$REL_LANG->say_by_key("invalid_id"));
	$commentid = (int) $_GET["cid"];

	$res = sql_query("SELECT pc.*, p.id AS pid, u.username FROM pollcomments AS pc LEFT JOIN polls AS p ON pc.poll = p.id JOIN users AS u ON pc.user = u.id WHERE pc.id=$commentid") or sqlerr(__FILE__,__LINE__);
	$arr = mysql_fetch_array($res);
	if (!$arr)
	stderr($REL_LANG->say_by_key('error'), $REL_LANG->say_by_key('invalid_id'));

	stdhead("���������� ����������� � ������");

	$text = "<blockquote><p>" . format_comment($arr["text"]) . "</p><cite>$arr[username]</cite></blockquote><hr /><br /><br />\n";
	
	print("<form method=\"post\" name=\"comment\" action=\"".$REL_SEO->make_link('pollcomment','action','add')."\">\n");
	print("<input type=\"hidden\" name=\"pid\" value=\"$arr[pid]\" />\n");
	?>

<table class="main" border="0" cellspacing="0" cellpadding="3">
	<tr>
		<td class="colhead"><?
		print("���������� ����������� � ������");
		?></td>
	</tr>
	<tr>
		<td><?
		print textbbcode("text",$text);
		?></td>
	</tr>
</table>

		<?

		print("<p><input type=\"submit\" value=\"��������\" /></p></form>\n");

		stdfoot();

}
elseif ($action == "edit")
{
	if(!is_valid_id($_GET["cid"])) stderr($REL_LANG->say_by_key("error"),$REL_LANG->say_by_key("invalid_id"));
	$commentid = (int) $_GET["cid"];

	$res = sql_query("SELECT pc.*, p.id AS pid FROM pollcomments AS pc LEFT JOIN polls AS p ON pc.poll = p.id WHERE pc.id=$commentid") or sqlerr(__FILE__,__LINE__);
	$arr = mysql_fetch_array($res);
	if (!$arr)
	stderr($REL_LANG->say_by_key('error'), $REL_LANG->say_by_key('invalid_id'));

	if ($arr["user"] != $CURUSER["id"] && get_user_class() < UC_MODERATOR)
	stderr($REL_LANG->say_by_key('error'), $REL_LANG->say_by_key('access_denied'));

	if ($_SERVER["REQUEST_METHOD"] == "POST")
	{
		$text = ((string) $_POST["text"]);
		$returnto = htmlentities($_POST["returnto"]);

		if ($text == "")
		stderr($REL_LANG->say_by_key('error'), $REL_LANG->say_by_key('comment_cant_be_empty'));
		$text = sqlesc($text);

		$editedat = sqlesc(time());

		sql_query("UPDATE pollcomments SET text=$text, editedat=$editedat, editedby=$CURUSER[id] WHERE id=$commentid") or sqlerr(__FILE__, __LINE__);

		if ($returnto)
		safe_redirect(" $returnto");
		else
		safe_redirect(" {$REL_CONFIG['defaultbaseurl']}/");      // change later ----------------------
		die;
	}

	stdhead("�������������� ����������� � ������");

	print("<form method=\"post\" name=\"comment\" action=\"".$REL_SEO->make_link('pollcomment','action','edit','cid',$commentid)."\">\n");
	print("<input type=\"hidden\" name=\"returnto\" value=\"".$REL_SEO->make_link('polloverview','id',$arr["pid"])."#comm$commentid\" />\n");
	print("<input type=\"hidden\" name=\"cid\" value=\"$commentid\" />\n");
	?>

<table class="main" border="0" cellspacing="0" cellpadding="3">
	<tr>
		<td class="colhead"><?
		print("�������������� ����������� � ������");
		?></td>
	</tr>
	<tr>
		<td><?
		print(textbbcode("text",$arr["text"]));
		?></td>
	</tr>
</table>

		<?

		print("<p><input type=\"submit\" value=\"���������������\" /></p></form>\n");

		stdfoot();
		die;
}

elseif ($action == "delete")
{
	if (get_user_class() < UC_MODERATOR)
	stderr($REL_LANG->say_by_key('error'), $REL_LANG->say_by_key('access_denied'));

	if(!is_valid_id($_GET["cid"])) stderr($REL_LANG->say_by_key("error"),$REL_LANG->say_by_key("invalid_id"));
	$commentid = (int) $_GET["cid"];


	$res = sql_query("SELECT poll FROM pollcomments WHERE id=$commentid")  or sqlerr(__FILE__,__LINE__);
	$arr = mysql_fetch_array($res);
	if ($arr)
	$pid = $arr["poll"];
	else
	stderr($REL_LANG->say_by_key('error'), $REL_LANG->say_by_key('invalid_id'));

	sql_query("DELETE FROM pollcomments WHERE id=$commentid") or sqlerr(__FILE__,__LINE__);

	list($commentid) = mysql_fetch_row(sql_query("SELECT id FROM pollcomments WHERE poll = $pid ORDER BY added DESC LIMIT 1"));

	$returnto = $REL_SEO->make_link('polloverview','id',$pid)."#comm$commentid";


	$REL_CACHE->clearGroupCache("block-polls");

	if ($returnto)
	safe_redirect(" $returnto");
	else
	safe_redirect(" {$REL_CONFIG['defaultbaseurl']}/");      // change later ----------------------
	die;
}
else
stderr($REL_LANG->say_by_key('error'), "Unknown action");

die;
?>