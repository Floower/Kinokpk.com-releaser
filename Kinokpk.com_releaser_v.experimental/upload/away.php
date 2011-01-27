<?php
/**
 * Just a redirect script
 * @license GNU GPLv3 http://opensource.org/licenses/gpl-3.0.html
 * @package Kinokpk.com releaser
 * @author ZonD80 <admin@kinokpk.com>
 * @copyright (C) 2008-now, ZonD80, Germany, TorrentsBook.com
 * @link http://dev.kinokpk.com
 */

require_once ("include/bittorrent.php");
dbconn(false);

$url = strip_tags(trim((string)preg_replace("#(.*?)url=#si", "", getenv("REQUEST_URI"))));
print('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">
<head>
<meta http-equiv="Content-Type" content="text/html; charset='.$REL_LANG->say_by_key('language_charset').'" />
<meta name="Description" content="'.$REL_CONFIG['description'].'" />
<meta name="Keywords" content="'.$REL_CONFIG['keywords'].'" />
<title>'.$REL_CONFIG['sitename'].' - ������� �� ������� ������</title>
</head>
<body  style="padding:20px 180px; font-size:12px; font-family:Tahoma; line-height:200%">
<h2>'.$REL_CONFIG['sitename'].' - ������� �� ������� ������</h2>

�� ��������� ���� '.$REL_CONFIG['sitename'].' �� ������� ������ <b>'.iconv("utf8","windows-1251",urldecode($url)).'</b>, ��������������� ����� �� ����������. <br/>
<a href="'.$REL_SEO->make_link('staff').'">�������������</a> '.$REL_CONFIG['sitename'].' �� ����� ��������������� �� ���������� ����� <b>'.iconv("utf8","windows-1251",urldecode($url)).'</b> � ������������ ����������� <b>�� ���������</b> ������� ����� ������, ������� ��������� � '.$REL_CONFIG['sitename'].' (�������� <b>e-mail</b>, <b>������</b> � <b>cookies</b>), �� ��������� ������. <br/><br/>
����� ����, ���� <b>'.iconv("utf8","windows-1251",urldecode($url)).'</b> ����� ��������� ������, ������ � ������ ����������� ���������, ������� ��� ������ ����������. <br/>
���� � ��� ��� ��������� ��������� �������� ����� �����, ����� ����� �� ���� �� ����������, ���� ���� �� ����� �������� ��� ������ �� ������ �� ����� <a href="'.$REL_SEO->make_link('friends').'">������</a>. <br/><br/>
���� �� ��� �� ����������, ������� �� <a href="'.$url.'" id="to_go">'.iconv("utf8","windows-1251",urldecode($url)).'</a>. <br/>
���� �� �� ������ ��������� ������������� ������ �������� � ����������, ������� <a href="javascript:history.go(-1);">������</a>.
</body>
</html>
');
?>