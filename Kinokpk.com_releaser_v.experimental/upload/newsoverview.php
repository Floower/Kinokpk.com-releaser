<?php
/**
 * News overview
 * @license GNU GPLv3 http://opensource.org/licenses/gpl-3.0.html
 * @package Kinokpk.com releaser
 * @author ZonD80 <admin@kinokpk.com>
 * @copyright (C) 2008-now, ZonD80, Germany, TorrentsBook.com
 * @link http://dev.kinokpk.com
 */
require "include/bittorrent.php";
dbconn();
loggedinorreturn();

$newsid = (int) $_GET['id'];
if (!is_valid_id($newsid)) 			stderr($REL_LANG->say_by_key('error'), $REL_LANG->say_by_key('invalid_id'));
//$action = $_GET["action"];
//$returnto = $_GET["returnto"];

$REL_TPL->stdhead("��������������� �������");


if (isset($_GET['id'])) {

	$sql = sql_query("SELECT * FROM news WHERE id = {$newsid} ORDER BY id DESC") or sqlerr(__FILE__, __LINE__);
	$news = mysql_fetch_assoc($sql);
	if (!$news) stderr($REL_LANG->say_by_key('error'),$REL_LANG->say_by_key('invalid_id'));

	$added = mkprettytime($news['added']) . " (" . (get_elapsed_time($news["added"],false)) . " {$REL_LANG->say_by_key('ago')})";
	print("<h1>{$news['subject']}</h1>\n");
	print("<table width=\"100%\" border=\"1\" cellspacing=\"0\" cellpadding=\"5\">\n" .
 "<tr><td class=\"colhead\">����������&nbsp;<a href=\"".$REL_SEO->make_link('newsoverview','id',$newsid)."#comments\">��������������</a></td></tr>\n");
	print("<tr><td style=\"vertical-align: top; text-align: left;\">".format_comment($news['body'])."</td></tr>\n");
	print("<tr align=\"right\"><td class=\"colhead\">���������:&nbsp;{$added}</td></tr>\n");

	print("</table><br />\n");

	$subres = sql_query("SELECT SUM(1) FROM comments WHERE toid = ".$newsid." AND type='news'");
	$subrow = mysql_fetch_array($subres);
	$count = $subrow[0];

	$limited = 10;

	if (!$count) {

		print('<div id="newcomment_placeholder">'."<table style=\"margin-top: 2px;\" cellpadding=\"5\" width=\"100%\">");
		print("<tr><td class=colhead align=\"left\" colspan=\"2\">");
		print("<div style=\"float: left; width: auto;\" align=\"left\"> :: ������ ������������ � �������</div>");
		print("<div align=\"right\"><a href=\"".$REL_SEO->make_link('newsoverview','id',$newsid)."#comments\" class=altlink_white>�������� �����������</a></div>");
		print("</td></tr><tr><td align=\"center\">");
		print("������������ ���. <a href=\"".$REL_SEO->make_link('newsoverview','id',$newsid)."#comments\">������� ��������?</a>");
		print("</td></tr></table><br /></div>");

	}
	else {
		list($pagertop, $pagerbottom, $limit) = pager($limited, $count, array('newsoverview','id',$newsid), array(lastpagedefault => 1));

		$subres = sql_query("SELECT nc.type, nc.id, nc.ip, nc.text, nc.ratingsum, nc.user, nc.added, nc.editedby, nc.editedat, u.avatar, u.warned, ".
                  "u.username, u.title, u.info, u.class, u.donor, u.enabled, u.ratingsum AS urating, u.gender, sessions.time AS last_access, e.username AS editedbyname FROM comments AS nc LEFT JOIN users AS u ON nc.user = u.id LEFT JOIN sessions ON nc.user=sessions.uid LEFT JOIN users AS e ON nc.editedby = e.id WHERE nc.toid = " .
                  "".$newsid." AND nc.type='news' GROUP BY nc.id ORDER BY nc.id $limit") or sqlerr(__FILE__, __LINE__);
		$allrows = prepare_for_commenttable($subres,$news['subject'],$REL_SEO->make_link('newsoverview','id',$newsid));




		print("<table class=main cellspacing=\"0\" cellPadding=\"5\" width=\"100%\" >");
		print("<tr><td class=\"colhead\" align=\"center\" >");
		print("<div style=\"float: left; width: auto;\" align=\"left\"> :: ������ ������������</div>");
		print("<div align=\"right\"><a href=\"".$REL_SEO->make_link('newsoverview','id',$newsid)."#comments\" class=altlink_white>�������� �����������</a></div>");
		print("</td></tr>");

		print("<tr><td>");
		print($pagertop);
		print("</td></tr>");
		print("<tr><td>");
		commenttable($allrows);
		print("</td></tr>");
		print("<tr><td>");
		print($pagerbottom);
		print("</td></tr>");
		print("</table>");
	}



	$REL_TPL->assignByRef('to_id',$newsid);
	$REL_TPL->assignByRef('is_i_notified',is_i_notified ( $newsid, 'newscomments' ));
	$REL_TPL->assign('textbbcode',textbbcode('text'));
	$REL_TPL->assignByRef('FORM_TYPE_LANG',$REL_LANG->_('News'));
	$FORM_TYPE = 'news';
	$REL_TPL->assignByRef('FORM_TYPE',$FORM_TYPE);
	$REL_TPL->display('commenttable_form.tpl');

}

$REL_TPL->stdfoot();
?>