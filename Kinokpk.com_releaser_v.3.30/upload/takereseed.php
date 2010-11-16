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

require "include/bittorrent.php";

dbconn();
loggedinorreturn();

$id = (int) $_GET["torrent"];

$res = sql_query("SELECT SUM(trackers.seeders) AS seeders, torrents.banned, SUM(trackers.leechers) AS leechers, torrents.name, torrents.times_completed, torrents.id, torrents.last_reseed AS lr FROM torrents LEFT JOIN trackers ON torrents.id=trackers.torrent WHERE torrents.id = $id GROUP BY torrents.id") or sqlerr(__FILE__, __LINE__);
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

sql_query("INSERT INTO messages (sender, receiver, poster, added, subject, msg) SELECT $CURUSER[id], userid, 0, ".time().", $subject, $msg FROM snatched WHERE torrent = $id AND userid != $CURUSER[id] AND finished = 1") or sqlerr(__FILE__, __LINE__);

sql_query("UPDATE torrents SET last_reseed = ".time()." WHERE id = $id") or sqlerr(__FILE__, __LINE__);

safe_redirect($REL_SEO->make_link('details','id',$id,'name',translit($row['name'])),2);

$REL_TPL->stdhead("������� ��������� �� ������� $row[name]");

stdmsg("�������", "��� ������ �� ������ ��������� ��������. ����� ����������� � ������� �����, ����� ��������� ������.");

$REL_TPL->stdfoot();

?>