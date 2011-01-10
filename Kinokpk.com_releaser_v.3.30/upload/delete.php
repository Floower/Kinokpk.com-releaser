<?php
/**
 * Deleter of torrents
 * @license GNU GPLv3 http://opensource.org/licenses/gpl-3.0.html
 * @package Kinokpk.com releaser
 * @author ZonD80 <admin@kinokpk.com>
 * @copyright (C) 2008-now, ZonD80, Germany, TorrentsBook.com
 * @link http://dev.kinokpk.com
 */


require_once("include/bittorrent.php");


function bark($msg) {
	global $REL_TPL;
	$REL_TPL->stdhead($REL_LANG->say_by_key('error'));
	$REL_TPL->stdmsg($REL_LANG->say_by_key('error'), $msg);
	$REL_TPL->stdfoot();
	exit;
}

dbconn();

if (!is_valid_id($_POST["id"])) 			stderr($REL_LANG->say_by_key('error'), $REL_LANG->say_by_key('invalid_id'));

$id = (int) $_POST["id"];

loggedinorreturn();

$res = sql_query("SELECT name,owner,images FROM torrents WHERE id = $id");
$row = mysql_fetch_array($res);
if (!$row)
stderr($REL_LANG->say_by_key('error'),"������ �������� �� ����������.");

if (get_user_class() < UC_MODERATOR)
bark("�� �� ���������! ��� ����� ����� ���������?\n");

$rt = (int) $_POST["reasontype"];

if ( $rt < 1 || $rt > 5)
bark("�������� ������� $rt.");

$reason = $_POST["reason"];

if ($rt == 1)
$reasonstr = "�������: 0 ���������, 0 �������� = 0 �����";
elseif ($rt == 2)
$reasonstr = "�������" . ($reason[0] ? (": " . trim($reason[0])) : "!");
elseif ($rt == 3)
$reasonstr = "Nuked" . ($reason[1] ? (": " . trim($reason[1])) : "!");
elseif ($rt == 4)
{
	if (!$reason[2])
	bark("�� �� �������� ���� ������, ������� ���� ������� �������.");
	$reasonstr = "��������� ������: " . trim($reason[2]);
}
else
{
	if (!$reason[3])
	bark("�� �� �������� �������, ������ �������� �������.");
	$reasonstr = trim($reason[3]);
}

deletetorrent($id);

$REL_CACHE->clearGroupCache('block-indextorrents');

$reasonstr = htmlspecialchars($reasonstr);
write_log("������� $id ($row[name]) ��� ������ ������������� $CURUSER[username] ($reasonstr)\n","torrent");

$REL_TPL->stdhead("������� ������!");

if (isset($_POST["returnto"]))
$ret = "<a href=\"" . htmlspecialchars($_POST["returnto"]) . "\">�����</a>";
else
$ret = "<a href=\"{$REL_CONFIG['defaultbaseurl']}/\">�� �������</a>";

?>
<h2>������� ������!</h2>
<p><?= $ret ?></p>
<?

$REL_TPL->stdfoot();

?>