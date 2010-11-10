<?php
/**
 * Profile edit parser
 * @license GNU GPLv3 http://opensource.org/licenses/gpl-3.0.html
 * @package Kinokpk.com releaser
 * @author ZonD80 <admin@kinokpk.com>
 * @copyright (C) 2008-now, ZonD80, Germany, TorrentsBook.com
 * @link http://dev.kinokpk.com
 */


require_once("include/bittorrent.php");

function bark($msg) {
	stderr("��������� ������", $msg);
}

$SETlang = substr(trim((string)$_POST["language"]),0,2);
setcookie("lang", $SETlang, 0x7fffffff);
dbconn();

loggedinorreturn();

if (!mkglobal("email:oldpassword:chpassword:passagain"))
bark("missing form data");

// $set = array();

$updateset = array();
$changedemail = 0;

if ($chpassword != "") {
	if (strlen($chpassword) > 40)
	bark("��������, ��� ������ ������� ������� (�������� 40 ��������)");
	if ($chpassword != $passagain)
	bark("������ �� ���������. ���������� ��� ���.");
	if ($CURUSER["passhash"] != md5($CURUSER["secret"] . $oldpassword . $CURUSER["secret"]))
	bark("�� ����� ������������ ������ ������.");

	$sec = mksecret();

	$passhash = md5($sec . $chpassword . $sec);
	$updateset[] = "secret = " . sqlesc($sec);
	$updateset[] = "passhash = " . sqlesc($passhash);

	logincookie($CURUSER["id"], $passhash, $CURUSER['language']);
	$passupdated = 1;
}
if ($email != $CURUSER["email"]) {
	if (!validemail($email))
	bark("��� �� ������ �� ��������� E-Mail.");
	$r = sql_query("SELECT id FROM users WHERE email=" . sqlesc($email)) or sqlerr(__FILE__, __LINE__);
	if (mysql_num_rows($r) > 0)
	bark("���� e-mail ����� ��� ������������ ����� �� ������������� �������. (<b>$email</b>)");
	$changedemail = 1;
}

$username = trim((string)$_POST['username']);

if ($username<>$CURUSER['username']) {
	if (strlen($username) > 12)
	bark($REL_LANG->_('Sorry, but your username is too big. It must be < 12 symbols. Please <a href="javascript:history.go(-1);">try again</a>.'));

	if (!validusername($username))
	bark($REL_LANG->_('Sorry, but you entered invalid username. Allowed characters are: a-Z0-9_. Please <a href="javascript:history.go(-1);">try again</a>.'));

	$check = @mysql_result(sql_query("SELECT 1 FROM users WHERE username=".sqlesc($username)),0);
	if ($check)
	bark($REL_LANG->_('Sorry, but this username is already in use. Please <a href="javascript:history.go(-1);">try again</a> and select another.'));

	$updateset[] = "username = ".sqlesc($username);
}
$acceptpms = (string) $_POST["acceptpms"];
$deletepms = ($_POST["deletepms"] ? 1 : 0);
$savepms = ($_POST["savepms"] ? 1 : 0);
$avatars = ($_POST["avatars"]? 1 : 0);
$extra_ef = ($_POST["extra_ef"]? 1 : 0);

if (isset($_POST['timezone'])) $updateset[] = "timezone = ".(int)$_POST['timezone'];

if (!is_valid_id($_POST["gender"])) stderr($REL_LANG->say_by_key('error'),"����� �� � ��� ���?");
$gender = (int)$_POST["gender"];
$updateset[] = "gender =  " . $gender;

///////////////// BIRTHDAY MOD /////////////////////

$birthday = @date("{$_POST["year"]}.{$_POST["month"]}.{$_POST["day"]}");
if (!$birthday) stderr($REL_LANG->say_by_key('error'),"�� ������� �������� ���� ��������");

///////////////// BIRTHDAY MOD /////////////////////
$updateset[] = "birthday = " . sqlesc($birthday);

if ($_POST['resetpasskey'])
$updateset[] = "passkey=''";

$updateset[] = "passkey_ip = ".($_POST["passkey_ip"] != "" ? sqlesc(getip()) : "''");

$info = trim((string)$_POST["info"]);
if (!is_valid_id($_POST["stylesheet"])) stderr($REL_LANG->say_by_key('error'),"������� ������ ����� ����������");
$stylesheet = $_POST["stylesheet"];
if (!is_valid_id($_POST['country'])) stderr($REL_LANG->say_by_key('error'),"������� ������� ������");
$country = (int)$_POST["country"];

$updateset[] = "language = " . sqlesc($SETlang);

$icq =  unesc((int)$_POST["icq"]);
if (strlen($icq) > 10)
bark("����, ����� icq ������� �������  (���� - 10)");
$updateset[] = "icq = " . sqlesc($icq);

$msn = unesc($_POST["msn"]);
if (strlen($msn) > 30)
bark("����, ��� msn ������� �������  (���� - 30)");
$updateset[] = "msn = " . sqlesc(htmlspecialchars($msn));

$aim = unesc($_POST["aim"]);
if (strlen($aim) > 30)
bark("����, ��� aim ������� �������  (���� - 30)");
$updateset[] = "aim = " . sqlesc(htmlspecialchars($aim));

$yahoo = unesc($_POST["yahoo"]);
if (strlen($yahoo) > 30)
bark("����, ��� yahoo ������� �������  (���� - 30)");
$updateset[] = "yahoo = " . sqlesc(htmlspecialchars($yahoo));

$mirc = unesc($_POST["mirc"]);
if (strlen($mirc) > 30)
bark("����, ��� mirc ������� �������  (���� - 30)");
$updateset[] = "mirc = " . sqlesc(htmlspecialchars($mirc));

$skype = unesc($_POST["skype"]);
if (strlen($skype) > 20)
bark("����, ��� skype ������� �������  (���� - 20)");
$updateset[] = "skype = " . sqlesc(htmlspecialchars($skype));

$privacy = (string)$_POST['privacy'];
if ($privacy != "normal" && $privacy != "highest" && $privacy != "strong")
bark($REL_LANG->_("Privacy level is unknown"));

$updateset[] = "privacy = '$privacy'";

$website = unesc($_POST["website"]);
$updateset[] = "website = " . sqlesc(htmlspecialchars($website));

$updateset[] = "stylesheet = $stylesheet";
$updateset[] = "country = $country";

//$updateset[] = "timezone = $timezone";
//$updateset[] = "dst = '$dst'";
$updateset[] = "info = " . sqlesc(cleanhtml(substr($info,0,$REL_CONFIG['sign_length'])));
$updateset[] = "acceptpms = " . sqlesc($acceptpms);
$updateset[] = "deletepms = '$deletepms'";
$updateset[] = "savepms = '$savepms'";
$updateset[] = "pron = ".($_POST['pron']?1:0);
$updateset[] = "avatars = '$avatars'";
$updateset[] = "extra_ef = '$extra_ef'";


if ($changedemail) {
	$sec = mksecret();
	$hash = md5($sec . $email . $sec);
	$obemail = urlencode($email);
	$updateset[] = "editsecret = " . sqlesc($sec);
	$body = <<<EOD
�� ������ ������ �� ��������� e-mail ��� ������������ {$CURUSER["username"]}
�� {$REL_CONFIG['defaultbaseurl']}. ����� ������� ������:$email.

���� �� �� ��������� ��������, ��������� � ���� ������, �� �������������� ��� ������.

���� �� ������������� ������ �������� e-mail, �� ����������� �� ��������� ������:
	{$REL_CONFIG['defaultbaseurl']}/{$REL_SEO->make_link('confirmemail','id',$CURUSER['id'],'confirmcode',$hash,'email',$obemail)}

EOD;

	sent_mail($email, $REL_CONFIG['sitename'], $REL_CONFIG['siteemail'], "{$REL_CONFIG['defaultbaseurl']} ������������� ��������� �������", $body);
	$string.= "<br /><h2>".$REL_LANG->say_by_key('my_mail_sent')."</h2>";
}

sql_query("UPDATE users SET " . implode(",", $updateset) . " WHERE id = " . $CURUSER["id"]) or sqlerr(__FILE__,__LINE__);

setcookie("lang", (string) trim($_POST["language"]), 0x7fffffff, "/");
safe_redirect($REL_SEO->make_link('my'),1);
stderr($REL_LANG->say_by_key('success'),$REL_LANG->say_by_key('my_updated').$string,'success');

?>