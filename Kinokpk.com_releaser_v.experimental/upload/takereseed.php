<?php
/**
 * "Ask to reseed" processor
 * @license GNU GPLv3 http://opensource.org/licenses/gpl-3.0.html
 * @package Kinokpk.com releaser
 * @author ZonD80 <admin@kinokpk.com>
 * @copyright (C) 2008-now, ZonD80, Germany, TorrentsBook.com
 * @link http://dev.kinokpk.com
 */

require "include/bittorrent.php";

dbconn();
loggedinorreturn();

$id = (int) $_GET["torrent"];

$res = sql_query("SELECT torrents.seeders, torrents.banned, torrents.leechers, torrents.name, torrents.id, torrents.last_reseed AS lr FROM torrents WHERE torrents.id = $id GROUP BY torrents.id") or sqlerr(__FILE__, __LINE__);
$row = mysql_fetch_array($res);

if (!$row || $row["banned"])
stderr($REL_LANG->say_by_key('error'), $REL_LANG->say_by_key('no_torrent_with_such_id'));

if ($row["times_completed"] == 0)
stderr($REL_LANG->say_by_key('error'), "��������, �� ���� ������� ��� ����� �� ������.");

if ($row["leechers"] == 0)
stderr($REL_LANG->say_by_key('error'), "�� ���� ������� �� ����� ������ �.�. �� ����� �� ������.");

$dt = time() - 24*3600;

if ($row["lr"] > $dt && ($row["lr"]) != 0)
stderr($REL_LANG->say_by_key('error'), "��������, �� ��� �� ������ ����� � �������� ������� �������� �� �������.");

$subject = sqlesc("�������� ������� {$row["name"]}");

$msg = sqlesc("������������!

���� ������ ���������� � ������� <a href=\"".$REL_SEO->make_link('details','id',$id,'name',translit($row["name"]))."\">{$row["name"]}</a>
���� �� ������ ������, �� ��� ������� �������-����, ������ ������� ��� <a href=\"".$REL_SEO->make_link('download','id',$id,'name',translit($row['name']))."\">�����</a>.

������� �� ���� ������!");

sql_query("INSERT INTO messages (sender, receiver, poster, added, subject, msg) SELECT $CURUSER[id], userid, 0, ".time().", $subject, $msg FROM snatched WHERE torrent = $id AND userid != $CURUSER[id]") or sqlerr(__FILE__, __LINE__);

sql_query("UPDATE torrents SET last_reseed = ".time()." WHERE id = $id") or sqlerr(__FILE__, __LINE__);

safe_redirect($REL_SEO->make_link('details','id',$id,'name',translit($row['name'])),2);

$REL_TPL->stdhead("������� ��������� �� ������� $row[name]");

stdmsg("�������", "��� ������ �� ������ ��������� ��������. ����� ����������� � ������� �����, ����� ��������� ������.");

$REL_TPL->stdfoot();

?>