<?

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



//loggedinorreturn();

$REL_TPL->stdhead("�������");

$REL_TPL->begin_main_frame();
$REL_TPL->begin_frame("��� ����������������");
print(nl2br('��� ����, ����� �������� � ���������� ��������������� �������� �� ����� �����, ��������� ��������������� ������ ��
<div align="center"><img src="pic/abusemail.gif"/></div>
<b>��������!</b> ������, ������������ �� �����-���� ������ ����� ������ ������ �� ���������������.
�� �������� �������, ��� � ���� ���������, �������������� ����� �������� �� ���������, ���������, �� �������� ��� ������� ��������� �������������� � �.�. � �.�.
�� � ������������� �� ���� ����� �����, �� ��� �� ����� ��������� ������ ��� ��������� � ���������� ���:

��������������! ����������, ������������� �� ������ �������, ������������� ������������� ��� �������� ������������� � ��������������� ����� � �� ����� ���� ���������/���������� �� ������ ���������. �� �������� �����, �� �������-���������, �� ����� ������ ���������� ��� ����������� ���� �� ����� ����� ������� �������������� �� ����� ������������� ���������� ������� �����. ����� �� ����, ��, ��� ������������, ��� ����� ������������� ������ � �������������� �������� �� ����� ��������� �������������. ������ ������� ��������� ����� ��������� � ������������ ������������� ����������, ���������� �� �����. ��� ������, �������������� �� FTP � HTTP �������� ����� ������ �������� � �������� ��������. �� ������, �������������� � ������� Torrent-���� ��������������� ����� ���������� �����. ���� ��� ���������� �����, �� �� ������ ���������� ������������ ����� � HD �������� � ����������������.
���� �� ��������� ���������������� ������ � �� ������, ����� ��������� ����� ��� �������� �� �����, �������� ������������� �����. ���� ����� ������� ��������� � ��������� ������ �������.

<div align="center">
������� ��� ��������� �� <b>TBDev YSE PRE RC 6</b>. �������� ������ ������ �������� �������������� ������� Kinokpk.com
��������� ������ ������� �������� ����������� � ��������������� � <a target="_blank" href="http://dev.kinokpk.com">������ ������������ �������� Kinokpk.com</a></div>
'));
$REL_TPL->end_frame();
$REL_TPL->end_main_frame();
$REL_TPL->stdfoot();
?>