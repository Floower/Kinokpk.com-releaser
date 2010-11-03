<?php
/**
 * Torrent upload parser
 * @license GNU GPLv3 http://opensource.org/licenses/gpl-3.0.html
 * @package Kinokpk.com releaser
 * @author ZonD80 <admin@kinokpk.com>
 * @copyright (C) 2008-now, ZonD80, Germany, TorrentsBook.com
 * @link http://dev.kinokpk.com
 */

require_once("include/bittorrent.php");
require_once(ROOT_PATH."include/benc.php");

ini_set("upload_max_filesize",$REL_CONFIG['max_torrent_size']);

function bark($msg) {
	global $REL_LANG;
	stderr($REL_LANG->say_by_key('error'), $msg." <a href=\"javascript:history.go(-1);\">{$REL_LANG->say_by_key('ago')}</a>");
}

dbconn();


loggedinorreturn();


foreach(explode(":","type:name") as $v) {
	if (!isset($_POST[$v]))
	bark("�� ��� ���� ���������");
}



if ($_POST['annonce']) { $_POST['nofile'] = 1; $_POST['nofilesize'] = 0; }

if ($_POST['nofile']) {} else {
	if (!isset($_FILES["tfile"]))
	bark("missing form data");

	if (($_POST['nofile']) && (empty($_POST['nofilesize']))) bark("�� �� ������� ������ �� ������� ������!");

	$f = $_FILES["tfile"];
	$fname = unesc($f["name"]);
	if (empty($fname))
	bark("���� �� ��������. ������ ��� �����!");
}


if (!is_array($_POST["type"]))
bark("������ ��������� ��������� ���������!");
else
foreach ($_POST['type'] as $cat) if (!is_valid_id($cat)) bark($REL_LANG->say_by_key('error'),$REL_LANG->say_by_key('invalid_id'));

$catsstr = implode(',',$_POST['type']);

if ($_POST['nofile']) {} else {

	if (!validfilename($fname))
	bark("�������� ��� �����!");
	if (!preg_match('/^(.+)\.torrent$/si', $fname, $matches))
	bark("�������� ��� ����� (�� .torrent).");
	$shortfname = $torrent = $matches[1];
	$tiger_hash = trim((string)$_POST['tiger_hash']);
	if ((!preg_match("/[^a-zA-Z0-9]/",$tiger_hash) || (strlen($tiger_hash)<>38)) && $tiger_hash) stderr($REL_LANG->say_by_key('error'),$REL_LANG->say_by_key('invalid_tiger_hash'));
}

if ($_POST['multi']) $multi=1; else $multi=0;

if (!empty($_POST["name"]))
$torrent = unesc((string)$_POST["name"]); else bark("�� �� ����� �������� ������");

if (!preg_match("#(.*?) \/ (.*?) \([0-9-]+\) \[(.*?)\]#si",$torrent))
bark ("��� ������ ��������� �� �� �������:<br/>{$REL_LANG->say_by_key('taken_from_torrent')}");

if ($_POST['nofile']) {} else {
	$tmpname = $f["tmp_name"];
	if (!is_uploaded_file($tmpname))
	bark("eek");
	if (!filesize($tmpname))
	bark("������ ����!");

	$dict = bdec_file($tmpname, $REL_CONFIG['max_torrent_size']);
	if (!isset($dict))
	bark("��� �� ����� �� ����������? ��� �� �������-����������� ����!");
}

if ($_POST['free'] AND get_user_class() >= UC_MODERATOR) {
	$free = 1;
} else {
	$free = 0;
};

if ($_POST['sticky'] AND get_user_class() >= UC_MODERATOR)
$sticky = 1;
else
$sticky = 0;

if ($_POST['nofile']) {} else {

	unset($dict['value']['nodes']); // remove cached peers (Bitcomet & Azareus)
	unset($dict['value']['azureus_properties']); // remove azureus properties
	unset($dict['value']['comment']);
	unset($dict['value']['created by']);
	unset($dict['value']['publisher']);
	unset($dict['value']['publisher.utf-8']);
	unset($dict['value']['publisher-url']);
	unset($dict['value']['publisher-url.utf-8']);

	if (!$multi) {
		//  $dict['value']['info']['value']['private']=bdec('i1e');  // add private tracker flag
		unset($dict['value']['announce-list']);
		unset($dict['value']['announce']);

	} else $anarray = get_announce_urls($dict);

	if ($multi && !$anarray) stderr($REL_LANG->say_by_key('error'),'���� �������-���� �� �������� ���������������. <a href="javascript:history.go(-1);">�����</a>');

	$dict=bdec(benc($dict)); // double up on the becoding solves the occassional misgenerated infohash

	list($info) = dict_check($dict, "info");

	list($dname, $plen, $pieces) = dict_check($info, "name(string):piece length(integer):pieces(string)");

	/*if (!in_array($ann, $announce_urls, 1))
	 bark("�������� Announce URL! ������ ���� ".$announce_urls[0]);*/

	if (strlen($pieces) % 20 != 0)
	bark("invalid pieces");

	$filelist = array();
	$totallen = dict_get($info, "length", "integer");
	if (isset($totallen)) {
		$filelist[] = array($dname, $totallen);
		$type = 0;
	} else {
		$flist = dict_get($info, "files", "list");
		if (!isset($flist))
		bark("missing both length and files");
		if (!count($flist))
		bark("no files");
		$totallen = 0;
		foreach ($flist as $fn) {
			list($ll, $ff) = dict_check($fn, "length(integer):path(list)");
			$totallen += $ll;
			$ffa = array();
			foreach ($ff as $ffe) {
				if ($ffe["type"] != "string")
				bark("filename error");
				$ffa[] = $ffe["value"];
			}
			if (!count($ffa))
			bark("filename error");
			$ffe = implode("/", $ffa);
			$filelist[] = array($ffe, $ll);
			/*	if ($ffe == 'Thumbs.db')
			 {
			 stderr("������", "� ��������� ��������� ������� ����� Thumbs.db!");
			 die;
			 }*/
		}
		$type = 1;
	}

	$infohash = sha1($info["string"]);

}

//////////////////////////////////////////////
//////////////Take Image Uploads//////////////

$maxfilesize = 512000; // 500kb

for ($x=0; $x < $REL_CONFIG['max_images']; $x++) {
	$y=$x+1;
	if (!empty($_POST['img'.$x])) {
		$img=trim(htmlspecialchars((string)$_POST['img'.$x]));
		if (strpos($img,',') || strpos($img,'?')) stderr($REL_LANG->say_by_key('error'),'������������ ����������� ���������');

		if (!preg_match('/^(.+)\.(gif|png|jpeg|jpg)$/si', $img))
		stderr($REL_LANG->say_by_key('error'),'����������� �������� '.($x+1).' - �� ��������');

		/*  $check = remote_fsize($img);
		 if (!$check) stderr($REL_LANG->say_by_key('error'),'�� ������� ���������� ������ �������� '.$y);
		 if ($check>$maxfilesize) stderr($REL_LANG->say_by_key('error'),'������������ ������ �������� 512kb. ������ ��� �������� �������� '.$y);
		 */ $inames[]=$img;
	}
}


$image = $inames;

$images = @implode(',',$inames);

$image = @array_shift($image);

// FORUMDESC will be used in email notifs
if (!$image) $forumdesc = "<div align=\"center\"><img src=\"{$REL_CONFIG['defaultbaseurl']}/pic/noimage.gif\" border=\"0\" class=\"linked-image\" /></div><br />";
if ($image) $forumdesc = "<div align=\"center\"><a href=\"$image\" target=\"_blank\"><img alt=\"������ ��� ������ (�������� ��� ��������� ������� �����������)\" src=\"$image\" border=\"0\" class=\"linked-image\" /></a></div><br />";
	$catssql= sql_query("SELECT name FROM categories WHERE id IN ($catsstr)");
	while (list($catname) = mysql_fetch_array($catssql)) $forumcats[]=$catname;
	$forumcats = implode(', ',$forumcats);
$forumdesc .= "<table width=\"100%\" border=\"1\"><tr><td valign=\"top\"><b>��� (����):</b></td><td>".$forumcats."</td></tr><tr><td><b>��������:</b></td><td>" . sqlesc($torrent) ."</td></tr>";


// DEFINE size FOR forum & email notifs
if ($_POST['nofile']) {
	$forumsize = mksize($_POST['nofilesize']); } else { $forumsize = mksize($totallen/1024/1024);    }


	$descr = (string) $_POST['descr'];

	if (!$descr) stderr($REL_LANG->say_by_key('error'),'�� �� ����� ��������');

	//////////////////////////////////////////////

	// get relgroup
	$relgroup = (int)$_POST['relgroup'];

	if ($relgroup) {
		$relgroup = @mysql_result(sql_query("SELECT id FROM relgroups WHERE id=$relgroup"),0);

		if (!$relgroup) stderr($REL_LANG->say_by_key('error'),$REL_LANG->say_by_key('no_relgroup'));
	}

	/// get kinopoisk.ru trailer!

	$online = get_trailer($descr);

	// end get kinopoisk.ru trailer

	// Replace punctuation characters with spaces
	if ($_POST['nofile']) {
		$nofilesize = (float)$_POST['nofilesize'];
		$fname = 'nofile';
		$infohash = md5($torrent);
		$torrent = htmlspecialchars(str_replace("_", " ", $torrent));
		if ($_POST['annonce'])
		$torrent .= " | �����"; else $torrent .= " - ����� ��� ��������";

		$totallen = (float)($nofilesize*1024*1024);

		$ret = sql_query("INSERT INTO torrents (filename, owner, visible, sticky, info_hash, tiger_hash, name, descr, size, free, images, category, online, added, last_action, relgroup".((get_user_class() >= UC_UPLOADER)?', moderatedby, moderated':'').") VALUES (" . implode(",", array_map("sqlesc", array($fname, $CURUSER["id"], 1, $sticky, $infohash, $tiger_hash, $torrent, $descr, $totallen, $free, $images, $catsstr, $online))) . ", " . time() . ", " . time() . ", $relgroup".((get_user_class() >= UC_UPLOADER)?', '.$CURUSER['id'].', 1':'').")");
	} else {

		$torrent = htmlspecialchars(str_replace("_", " ", $torrent));

		$ret = sql_query("INSERT INTO torrents (filename, owner, visible, sticky, info_hash, name, descr, size, numfiles, ismulti, free, images, category, online, added, last_action, relgroup".((get_user_class() >= UC_UPLOADER)?', moderatedby, moderated':'').") VALUES (" . implode(",", array_map("sqlesc", array($fname, $CURUSER["id"], 1, $sticky, $infohash, $torrent, $descr, $totallen, count($filelist), $type, $free, $images, $catsstr, $online))) . ", " . time() . ", " . time() . ", $relgroup".((get_user_class() >= UC_UPLOADER)?', '.$CURUSER['id'].', 1':'').")");
	}
	if (!$ret) {
		if (mysql_errno() == 1062)
		bark("$id torrent already uploaded!");
		bark("mysql puked: ".mysql_error());
	}
	$id = mysql_insert_id();

	//insert localhost tracker
	if (!$_POST['nofile']) sql_query("INSERT INTO trackers (torrent,tracker) VALUES ($id,'localhost')");

	// Insert remote trackers //
	if ($anarray) {
		foreach ($anarray as $anurl) sql_query("INSERT INTO trackers (torrent,tracker) VALUES ($id,".sqlesc(strip_tags($anurl)).")");
	}
	// trackers insert end


	// making forum desc
	$forumdesc .= "<tr><td valign=\"top\"><b>".$REL_LANG->say_by_key('description').":</b></td><td>".format_comment($descr)."</td></tr>";

	$forumdesc .= "<tr><td valign=\"top\"><b>������ �����:</b></td><td>".$forumsize."</td></tr>";

	$topicfooter .= "<tr><td valign=\"top\"><b>".((!$_POST['nofile'])?"������� {$REL_CONFIG['defaultbaseurl']}:":"����� {$REL_CONFIG['defaultbaseurl']}:")."</b></td><td><div align=\"center\">[<span style=\"color:#FF0000\"><a href=\"{$REL_CONFIG['defaultbaseurl']}/{$REL_SEO->make_link('details','id',$id,'name',translit($torrent))}\">���������� ���� ����� �� {$REL_CONFIG['defaultbaseurl']}</a></span>]</div></td></tr></table>";

	$forumdesc .=$topicfooter;
	// end

	$REL_CACHE->clearGroupCache('block-indextorrents');

	sql_query("INSERT INTO notifs (checkid, userid, type) VALUES ($id, $CURUSER[id], 'relcomments')") or sqlerr(__FILE__,__LINE__);
	@sql_query("DELETE FROM files WHERE torrent = $id");

	if ($_POST['nofile']) {
	} else   {
		foreach ($filelist as $file) {
			@sql_query("INSERT INTO files (torrent, filename, size) VALUES ($id, ".sqlesc($file[0]).",".$file[1].")");
		}
	}
	if ($_POST['nofile']) {} else {
		$fp = @file_put_contents("torrents/$id.torrent", benc($dict['value']['info']));
		if (!$fp) stderr($REL_LANG->say_by_key('error'),'�������� torrent ����� �� �������');
	}

	write_log("������� ����� $id ($torrent) ��� ����� ������������� " . $CURUSER["username"],"torrent");

	/* Email notifs */

	$body = <<<EOD
����� ������������� ����� �� {$REL_CONFIG['sitename']}!

��������! ��� ��������� ���������� �������������, � ����� ����� ���� ��� �� �������� ����������� � ����� ���� ������!
��������: $torrent
������ �����: $forumsize
���������: {$forumcats}
�����: {$CURUSER['username']}

���������� � ������:
-------------------------------------------------------------------------------
	$forumdesc
-------------------------------------------------------------------------------
EOD;

	$bfooter = <<<EOD
����� ���������� �����, ��������� �� ���� ������:

	{$REL_CONFIG['defaultbaseurl']}/{$REL_SEO->make_link('details','id',$id,'name',translit($torrent))}

EOD;

	$body .= $bfooter;
	$descr .= nl2br($bfooter);


	if (get_user_class() < UC_UPLOADER) {
		write_sys_msg($CURUSER['id'],sprintf($REL_LANG->say_by_key('uploaded_body'),"<a href=\"".$REL_SEO->make_link('details','id',$id,'name',translit($torrent))."\">$torrent</a>"),$REL_LANG->say_by_key('uploaded'));
		send_notifs('unchecked',nl2br($body));
	} else {
		send_notifs('torrents',format_comment($descr));
	}


	$announce_urls_list[] = $REL_CONFIG['defaultbaseurl']."/".$REL_SEO->make_link('announce','passkey',$CURUSER['passkey']);
	$announce_sql = sql_query("SELECT tracker FROM trackers WHERE torrent=$id AND tracker<>'localhost'");
	while (list($announce) = mysql_fetch_array($announce_sql)) $announce_urls_list[] = $announce;

	$retrackers = get_retrackers();
	//var_dump($retrackers);
	if ($retrackers) foreach ($retrackers as $announce)
	if (!in_array($announce,$announce_urls_list)) $announce_urls_list[] = $announce;

	$link = make_magnet($infohash,makesafe($torrent),$announce_urls_list);

	if ($REL_CRON['rating_enabled']) { $msg = sprintf($REL_LANG->say_by_key('upload_notice'),$REL_CRON['rating_perrelease'],$id,$link); }
	else $msg = sprintf($REL_LANG->say_by_key('upload_notice_norating'),$id,$link);


	safe_redirect($REL_SEO->make_link('details','id',$id,'name',$torrent),3);
	stderr($REL_LANG->say_by_key('uploaded'),$msg.($anarray?"<img src=\"".$REL_SEO->make_link('remote_check','id',$id)."\" width=\"0px\" height=\"0px\" border=\"0\"/>":''),'success');

	?>