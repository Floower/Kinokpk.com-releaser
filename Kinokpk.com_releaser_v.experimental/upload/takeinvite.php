<?php
/**
 * Invites processor
 * @license GNU GPLv3 http://opensource.org/licenses/gpl-3.0.html
 * @package Kinokpk.com releaser
 * @author ZonD80 <admin@kinokpk.com>
 * @copyright (C) 2008-now, ZonD80, Germany, TorrentsBook.com
 * @link http://dev.kinokpk.com
 */

require_once("include/bittorrent.php");

dbconn();

loggedinorreturn();

function bark($msg) {
	$REL_TPL->stdhead();
	stdmsg($REL_LANG->say_by_key('error'), $msg);
	$REL_TPL->stdfoot();
	die;
}

$id = (int) $_GET["id"];
if (!$id) $id = (int) $_POST['id'];
if (!$id)
stderr($REL_LANG->say_by_key('error'), $REL_LANG->say_by_key('invalid_id'));

if (get_user_class() <= UC_MODERATOR)
$id = $CURUSER["id"];

$hash  = md5(mt_rand(1, 1000000));
if ($REL_CONFIG['use_captcha']){

	require_once('include/recaptchalib.php');
	$resp = recaptcha_check_answer ($REL_CONFIG['re_privatekey'],
	$_SERVER["REMOTE_ADDR"],
	$_POST["recaptcha_challenge_field"],
	$_POST["recaptcha_response_field"]);

	if (!$resp->is_valid) {
		stderr($REL_LANG->say_by_key('error'), "��������� ��� ������������� ��������. <a href=\"javascript:history.go(-1);\">���������� ��� ���</a>");
	}

}
$email =  trim((string)$_POST['email']);
if (!validemail($email)) stderr($REL_LANG->say_by_key('error'),'Email ����� ������ �������');

$res = sql_query("SELECT 1 FROM users WHERE email='$email'");
$check = @mysql_result($res,0);
if ($check) stderr($REL_LANG->say_by_key('error'),'����� email ��� ���������������!');

$subject = "����������� �� {$REL_CONFIG['sitename']}";
$body = "��� ���� ��� ������� � ����� {$CURUSER['username']} ���������� ��� ������������������ �� {$REL_CONFIG['sitename']}<br/>
��� ����������� �������� �� ���� ������:
<a href=\"{$REL_SEO->make_link('signup')}\">{$REL_SEO->make_link('signup')}</a><br/>
����������� ��������� ��� �����������:<b>$hash</b><hr/>
������� �� ��������, � ��������� {$REL_CONFIG['sitename']}";

sql_query("INSERT INTO invites (inviter, invite, time_invited) VALUES (" . implode(", ", array_map("sqlesc", array($id, $hash, time()))) . ")") or sqlerr(__FILE__,__LINE__);

sql_query("INSERT INTO cron_emails (emails, subject, body) VALUES (".sqlesc($email).",".sqlesc($subject).",".sqlesc($body).")") or sqlerr(__FILE__,__LINE__);

safe_redirect($REL_SEO->make_link('invite','id',$id));

?>