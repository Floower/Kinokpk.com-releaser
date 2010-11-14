<?php
/**
 * Password recovery
 * @license GNU GPLv3 http://opensource.org/licenses/gpl-3.0.html
 * @package Kinokpk.com releaser
 * @author ZonD80 <admin@kinokpk.com>
 * @copyright (C) 2008-now, ZonD80, Germany, TorrentsBook.com
 * @link http://dev.kinokpk.com
 */


require "include/bittorrent.php";

dbconn();

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
	if ($REL_CONFIG['use_captcha']) {
		require_once(ROOT_PATH.'include/recaptchalib.php');
		$resp = recaptcha_check_answer ($REL_CONFIG['re_privatekey'],
		$_SERVER["REMOTE_ADDR"],
		$_POST["recaptcha_challenge_field"],
		$_POST["recaptcha_response_field"]);

		if (!$resp->is_valid) stderr($REL_LANG->say_by_key('error'), $REL_LANG->say_by_key('test_humanity'));
	}

	$email = trim(htmlspecialchars((string)$_POST["email"]));
	if (!$email || !validemail($email))
	stderr($REL_LANG->say_by_key('error'), "�� ������ ������ email �����");
	$res = sql_query("SELECT * FROM users WHERE email=" . sqlesc($email) . " LIMIT 1") or sqlerr(__FILE__, __LINE__);
	$arr = mysql_fetch_array($res) or stderr($REL_LANG->say_by_key('error'), "Email ����� �� ������ � ���� ������.\n");

	$sec = mksecret();

	sql_query("UPDATE users SET editsecret=" . sqlesc($sec) . " WHERE id=" . $arr["id"]) or sqlerr(__FILE__, __LINE__);
	if (!mysql_affected_rows())
	stderr($REL_LANG->say_by_key('error'), "������ ���� ������. ��������� � ��������������� ������������ ���� ������.");

	$hash = md5($sec . $email . $arr["passhash"] . $sec);

	$body = nl2br("
��, ��� ���-�� ������, ��������� ����� ������ � �������� ��������� � ���� ������� ($email).

���� ��� ���� �� ��, �������������� ��� ������. ��������� �� ���������.

���� �� ������������� ���� ������, ��������� �� ��������� ������:

{$REL_SEO->make_link('recover','confirm',1,'id',$arr["id"],'secret',$hash)}


����� ���� ��� �� ��� ��������, ��� ������ ����� ������� � ����� ������ ����� ��������� ��� �� E-Mail.

--
{$REL_CONFIG['sitename']}
");

if (sent_mail($arr['email'], $REL_CONFIG['sitename'], $REL_CONFIG['siteemail'],  "{$REL_CONFIG['defaultbaseurl']} �������������� ������",  wordwrap($body,70))==false) stderr($REL_LANG->say_by_key('error'),"������ ��� �������� ������");

stderr($REL_LANG->say_by_key('success'), "�������������� ������ ���� ����������.\n" .
		" ����� ��������� ����� (������ �����) ��� ������� ������ � ����������� ����������.",'success');
}
elseif(isset($_GET['confirm']))
{

	if (!is_valid_id($_GET["id"]))
	stderr($REL_LANG->say_by_key('error'), $REL_LANG->say_by_key('invalid_id'));

	$id = (int) $_GET["id"];
	$md5 = $_GET["secret"];

	$res = sql_query("SELECT username, email, passhash, editsecret FROM users WHERE id = $id");
	$arr = mysql_fetch_array($res) or stderr($REL_LANG->say_by_key('error'),"��� ������������ � ����� ID");

	$email = $arr["email"];

	$sec = hash_pad($arr["editsecret"]);
	if (preg_match('/^ *$/s', $sec))
	stderr($REL_LANG->say_by_key('error'),"������ ���������� ���� �������������");
	if ($md5 != md5($sec . $email . $arr["passhash"] . $sec))
	stderr($REL_LANG->say_by_key('error'),"��� ������������� �������");

	// generate new password;
	$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

	$newpassword = "";
	for ($i = 0; $i < 10; $i++)
	$newpassword .= $chars[mt_rand(0, strlen($chars) - 1)];

	$sec = mksecret();

	$newpasshash = md5($sec . $newpassword . $sec);
	//$username = @mysql_result(sql_query("SELECT username FROM users WHERE id=$id"),0);

	sql_query("UPDATE users SET secret=" . sqlesc($sec) . ", editsecret='', passhash=" . sqlesc($newpasshash) . " WHERE id=$id AND editsecret=" . sqlesc($arr["editsecret"]));


	if (!mysql_affected_rows())
	stderr($REL_LANG->say_by_key('error'), "���������� �������� ������ ������������. ��������� ��������� � ��������������� ������������ ���� ������.");

	$body = nl2br("
�� ������ ������� �� �������������� ������, �� ������������� ��� ����� ������.

��� ���� ����� ������ ��� ����� ��������:

    ������������: {$arr["username"]}
    ������:       $newpassword

�� ������ ����� �� ���� ���: {$REL_SEO->make_link('login')}

--
{$REL_CONFIG['sitename']}
");

$mail_sent = sent_mail($email,$REL_CONFIG['sitename'],$REL_CONFIG['siteemail'], "{$REL_CONFIG['defaultbaseurl']} ������ ��������", $body);
if (!$mail_sent) stderr($REL_LANG->say_by_key('error'),'Mail not sent, configure smtp/sendmail or contact site admin');
stderr($REL_LANG->say_by_key('success'), "����� ������ �� �������� ���������� �� E-Mail <b>$email</b>.\n" .
    "����� ��������� ����� (������ �����) �� �������� ���� ����� ������.",'success');
}
else
{
	$REL_TPL->stdhead("�������������� ������");
	?>
<form method="post" action="<?=$REL_SEO->make_link('recover');?>">
<table border="1" cellspacing="0" cellpadding="5">
	<tr>
		<td class="colhead" colspan="2">�������������� ����� ������������ ���
		������</td>
	</tr>
	<tr>
		<td colspan="2">����������� ����� ���� ��� ������������� ������<br />
		� ���� ������ ����� ���������� ��� �� �����.<br />
		<br />
		�� ����� ������ ����������� ������.</td>
	</tr>
	<tr>
		<td class="rowhead">����������������� email</td>
		<td><input type="text" size="40" name="email"></td>
	</tr>
	<?php
	if ($REL_CONFIG['use_captcha']) {
		require_once(ROOT_PATH.'include/recaptchalib.php');
		print '<tr><td colspan="2" align="center">'.$REL_LANG->say_by_key('you_people').'</td></tr>';
		print '<tr><td colspan="2" align="center">'.recaptcha_get_html($REL_CONFIG['re_publickey']).'</td></tr>';
	}
	?>
	<tr>
		<td colspan="2" align="center"><input type="submit"
			value="������������"></td>
	</tr>
</table>
	<?
	$REL_TPL->stdfoot();
}

?>