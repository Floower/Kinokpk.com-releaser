<?php
/**
 * Profile edit parser
 * @license GNU GPLv3 http://opensource.org/licenses/gpl-3.0.html
 * @package Kinokpk.com releaser
 * @author ZonD80 <admin@kinokpk.com>
 * @copyright (C) 2008-now, ZonD80, Germany, TorrentsBook.com
 * @link http://dev.kinokpk.com
 */

require "include/bittorrent.php";

dbconn();

loggedinorreturn();

function puke($text = "You have forgotten here someting?") {
	global $REL_LANG;
	stderr($REL_LANG->say_by_key('error'), $text);
}

function barf($text = "������������ ������") {
	global $REL_LANG;
	stderr($REL_LANG->say_by_key('success'), $text);
}

if (get_user_class() < UC_MODERATOR)
puke($REL_LANG->say_by_key('access_denied'));

$action = (string)$_POST["action"];

if (($action == 'ownsupport') && (get_user_class()>=UC_ADMINISTRATOR)) {
	$supportfor = ($_POST["support"]?htmlspecialchars((string)$_POST["supportfor"]):'');
	$updateset[] = "supportfor = " . sqlesc($supportfor);
	sql_query("UPDATE users SET " . implode(", ", $updateset) . " WHERE id = {$CURUSER['id']}") or sqlerr(__FILE__, __LINE__);
	safe_redirect($REL_SEO->make_link('my'),0);
	stderr($REL_LANG->say_by_key('success'),'�� ������� ������� ���� ������ ���������','success');

}
elseif (($action == 'delnick') && (get_user_class()>=UC_ADMINISTRATOR)) {
	$nid = (int)$_POST['id'];
	$REL_DB->query("DELETE FROM nickhistory WHERE id=$nid");
	$REL_TPL->stderr($REL_LANG->_('Success'),$REL_LANG->_('Nick deleted from history'));
}
elseif ($action == "edituser") {
	$userid = (int) $_POST["userid"];
	$CLASS = @mysql_result(sql_query("SELECT class FROM users WHERE id = $userid"),0);
	if ($CLASS >= get_user_class()) stderr($REL_LANG->say_by_key('error'),$REL_LANG->say_by_key('access_denied'));

	$title = $_POST["title"];
	$avatar = (int)$_POST["avatar"];
	$resetb = $_POST["resetb"];
	$birthday = ($resetb?", birthday = '0000-00-00'":"");
	$enabled = ((!isset($_POST["enabled"]) || $_POST["enabled"])?1:0);
	$dis_reason = htmlspecialchars($_POST['disreason']);
	$warned = $_POST["warned"]?1:0;
	$warnlength = (int) $_POST["warnlength"];
	$warnpm = $_POST["warnpm"];
	$donor = $_POST["donor"];
	$uploadtoadd = (int)$_POST["amountup"];
	$downloadtoadd=  (int)$_POST["amountdown"];
	$ratingtoadd=  (int)$_POST["amountrating"];
	$discounttoadd=  (int)$_POST["amountdiscount"];
	$formatup = $_POST["formatup"];
	$formatdown = $_POST["formatdown"];
	$mpup = $_POST["upchange"];
	$rch = $_POST["ratingchange"];
	$dch = $_POST['discountchange'];
	$mpdown = $_POST["downchange"];
	$supportfor = ($_POST["support"]?htmlspecialchars($_POST["supportfor"]):'');
	$deluser = $_POST["deluser"];

	if ($ratingtoadd > 0) $updateset[] = 'ratingsum = ratingsum'.($rch=='plus'?'+':'-').$ratingtoadd;
	if ($discounttoadd > 0) $updateset[] = 'discount = discount'.($dch=='plus'?'+':'-').$discounttoadd;

	$class = (int) $_POST["class"];
	if (!is_valid_id($userid) || !is_valid_user_class($class))
	stderr($REL_LANG->say_by_key('error'), "�������� ������������� ������������ ��� ������.");
	// check target user class
	$res = sql_query("SELECT warned, enabled, username, class, modcomment, num_warned, avatar FROM users WHERE id = $userid") or sqlerr(__FILE__, __LINE__);
	$arr = mysql_fetch_assoc($res) or puke("������ MySQL: " . mysql_error());
	if ($avatar)
	{
		@unlink (ROOT_PATH."avatars/".$arr['avatar']);
		$updateset[] = "avatar = ''";

	}
	$curenabled = $arr["enabled"];
	$curclass = $arr["class"];
	$curwarned = $arr["warned"];
	if (get_user_class() == UC_SYSOP)
	$modcomment = (string)($_POST["modcomment"]);
	else
	$modcomment = $arr["modcomment"];
	// User may not edit someone with same or higher class than himself!
	if ($curclass >= get_user_class() || $class >= get_user_class())
	puke("��� ������ ������!");

	if ($curclass != $class) {
		// Notify user
		$what = ($class > $curclass ? "��������" : "��������");
		$msg = sqlesc("�� ���� $what �� ������ \"" . get_user_class_name($class) . "\" ������������� $CURUSER[username].");
		$added = sqlesc(time());
		$subject = sqlesc("�� ���� $what");
		sql_query("INSERT INTO messages (sender, receiver, msg, added, subject) VALUES(0, $userid, $msg, $added, $subject)") or sqlerr(__FILE__, __LINE__);
		$updateset[] = "class = $class";
		$what = ($class > $curclass ? "�������" : "���������");
		$modcomment = date("Y-m-d") . " - $what �� ������ \"" . get_user_class_name($class) . "\" ������������� $CURUSER[username].\n". $modcomment;
	}

	// some Helshad fun
	// $fun = ($CURUSER['id'] == 277) ? " Tremble in fear, mortal." : "";
	$num_warned = 1 + $arr["num_warned"]; //��� ���-�� ��������������
	if ($curwarned != $warned) {
		$updateset[] = "warned = 0";
		$updateset[] = "warneduntil = 0";
		$subject = sqlesc("���� �������������� �����");
		if (!$warned)
		{
			$modcomment = date("Y-m-d") . " - �������������� ���� ������������ " . $CURUSER['username'] . ".\n". $modcomment;
			$msg = sqlesc("���� �������������� ���� ������������ " . $CURUSER['username'] . ".");
		}
		$added = sqlesc(time());
		sql_query("INSERT INTO messages (sender, receiver, msg, added, subject) VALUES (0, $userid, $msg, $added, $subject)") or sqlerr(__FILE__, __LINE__);
	} elseif ($warnlength) {
		if (strlen($warnpm) == 0)
		stderr($REL_LANG->say_by_key('error'), "�� ������ ������� ������� �� ������� ������� ��������������!");
		if ($warnlength == 255) {
			$modcomment = date("Y-m-d") . " - ������������ ������������� " . $CURUSER['username'] . ".\n�������: $warnpm\n" . $modcomment;
			$msg = sqlesc("�� �������� <a href=\"".$REL_SEO->make_link('rules')."#warning\">��������������</a> �� ������������� ���� �� $CURUSER[username]" . ($warnpm ? "\n\n�������: $warnpm" : ""));
			$updateset[] = "warneduntil = 0";
			$updateset[] = "num_warned = $num_warned";
		} else {
			$warneduntil = (time() + $warnlength * 604800);
			$dur = $warnlength . " �����" . ($warnlength > 1 ? "�" : "�");
			$msg = sqlesc("�� �������� <a href=\"".$REL_SEO->make_link('rules')."#warning\">��������������</a> �� $dur �� ������������ " . $CURUSER['username'] . ($warnpm ? "\n\n�������: $warnpm" : ""));
			$modcomment = date("Y-m-d") . " - ������������ �� $dur ������������� " . $CURUSER['username'] .	".\n�������: $warnpm\n" . $modcomment;
			$updateset[] = "warneduntil = $warneduntil";
			$updateset[] = "num_warned = $num_warned";
		}
		$added = sqlesc(time());
		$subject = sqlesc("�� �������� ��������������");
		sql_query("INSERT INTO messages (sender, receiver, msg, added, subject) VALUES (0, $userid, $msg, $added, $subject)") or sqlerr(__FILE__, __LINE__);
		$updateset[] = "warned = 1";
	}

	if ($enabled != $curenabled) {
		$modifier = (int) $CURUSER['id'];
		if ($enabled) {
			if (!isset($_POST["enareason"]) || empty($_POST["enareason"]))
			puke("������� ������� ������ �� ��������� ������������!");
			$enareason = htmlspecialchars($_POST["enareason"]);
			$modcomment = date("Y-m-d") . " - ������� ������������� " . $CURUSER['username'] . ".\n�������: $enareason\n" . $modcomment;

		} else   {
			if (empty($dis_reason))
			puke("������� ������� ������ �� ���������� ������������!");
			$modcomment = date("Y-m-d") . " - �������� ������������� " . $CURUSER['username'] . ".\n�������: $dis_reason\n" . $modcomment;

		}
	}

	$updateset[] = "enabled = " . $enabled;
	if ($dis_reason) $updateset[] = "dis_reason = ".sqlesc($dis_reason);
	$updateset[] = "donor = " . sqlesc($donor);
	$updateset[] = "supportfor = " . sqlesc($supportfor);
	//$updateset[] = "support = " . sqlesc($support);
	$updateset[] = "title = " . sqlesc($title);
	$updateset[] = "modcomment = " . sqlesc($modcomment);
	if ($_POST['resetkey']) {
		$passkey = md5($CURUSER['username'].time().$CURUSER['passhash']);
		$REL_DB->query("UPDATE xbt_users SET torrent_pass='' WHERE uid=".sqlesc($CURUSER[id]));
	}
	write_log("������������ {$CURUSER['username']} �������� �������� ��� ������������� � ID <a href=\"".$REL_SEO->make_link('userdetails','id',$userid,'username',translit($arr['username']))."\">$userid</a>, ���������:<br/> <pre>".var_export($updateset,true)."</pre>",'modtask');
	sql_query("UPDATE users SET	" . implode(", ", $updateset) . " $birthday WHERE id = $userid") or sqlerr(__FILE__, __LINE__);

	if (!empty($_POST["deluser"])) {
		$res=@sql_query("SELECT * FROM users WHERE id = $userid") or sqlerr(__FILE__, __LINE__);
		$user = mysql_fetch_array($res);
		$username = $user["username"];
		$avatar = $user['avatar'];
		$email=$user["email"];
		delete_user($userid);
		@unlink(ROOT_PATH.$avatar);
		$deluserid=$CURUSER["username"];
		write_log("������������ $username ��� ������ ������������� $deluserid",'modtask');
		barf();
	} else {
		$returnto = makesafe($_POST["returnto"]);
		safe_redirect("$returnto");
		die;
	}
} elseif ($action == "confirmuser") {
	$userid = (int)$_POST["userid"];
	$confirm = (int)$_POST["confirm"];
	if (!is_valid_id($userid))
	stderr($REL_LANG->say_by_key('error'), $REL_LANG->say_by_key('invalid_id'));
	$updateset[] = "confirmed = " . $confirm;
	write_log("������������ {$CURUSER['username']} �������� �������� ��� ������������� c ID $userid, ���������:<br/> <pre>".var_export($updateset,true)."</pre>",'modtask');
	sql_query("UPDATE users SET " . implode(", ", $updateset) . " WHERE id = $userid") or sqlerr(__FILE__, __LINE__);
	$returnto = makesafe($_POST["returnto"]);

	safe_redirect($returnto);
}

puke();

?>