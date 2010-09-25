<?php
/**
 * Edit parser
 * @license GNU GPLv3 http://opensource.org/licenses/gpl-3.0.html
 * @package Kinokpk.com releaser
 * @author ZonD80 <admin@kinokpk.com>
 * @copyright (C) 2008-now, ZonD80, Germany, TorrentsBook.com
 * @link http://dev.kinokpk.com
 */

require_once("include/bittorrent.php");

dbconn();

loggedinorreturn();
getlang('upload');

require_once("include/benc.php");

$id = (int) $_POST['id'];
if (!$id) $id = (int) $_GET['id'];
$res = sql_query("SELECT torrents.id, torrents.owner, torrents.info_hash, torrents.filename, torrents.images, torrents.topic_id, torrents.modcomm, torrents.moderated, torrents.moderatedby, torrents.descr FROM torrents WHERE torrents.id = $id");
$row = mysql_fetch_array($res);
if (!$row)
stderr($tracker_lang["error"],$tracker_lang["invalid_id"]);


if (isset($_GET['checkonly'])) {

	headers(true);


	if (get_user_class() < UC_MODERATOR) die($tracker_lang['error'].': '.$tracker_lang['invalid_id']);
	getlang('details');
	$id = (int) $_GET['id'];


	$CACHE->clearGroupCache('block-indextorrents');

	if ($row['moderatedby']) {
		sql_query("UPDATE torrents SET moderatedby=0 WHERE id=$id");
		die($tracker_lang['not_yet_checked'].' <a onclick="return ajaxcheck();" href="takeedit.php?checkonly&id='.$id.'">'.$tracker_lang['check'].'</a>'.$return);
	}
	else {
		sql_query("UPDATE torrents SET moderatedby={$CURUSER['id']}, moderated=1 WHERE id=$id");
		// send notifs
		if (!$row['moderated']) {
			$bfooter = <<<EOD
����� ���������� �����, ��������� �� ���� ������:

			{$CACHEARRAY['defaultbaseurl']}/details.php?id=$id

EOD;
			$descr = format_comment($row['descr']).nl2br($bfooter);
			send_notifs('torrents',format_comment($descr),$CURUSER['id']);
		}

		die($tracker_lang['checked_by'].'<a href="userdetails.php?id='.$CURUSER['id'].'">'.get_user_class_color(get_user_class(),$CURUSER['username']).'</a> <a onclick="return ajaxcheck();" href="takeedit.php?checkonly&id='.$id.'">'.$tracker_lang['uncheck'].'</a>'.$return);
	}
} elseif(isset($_POST['add_trackers'])) {
	if (get_user_class() < UC_UPLOADER) stderr($tracker_lang['error'],$tracker_lang['access_deined']);

	if (!isset($_POST['trackers'])) stderr($tracker_lang['error'],'�� ��� ���� ���������');
	$POSTtrackers = explode("\n",trim((string)$_POST['trackers']));
	if (!$POSTtrackers) stderr($tracker_lang['error'], '������ ��������� ��������');

	$POSTtrackers = array_map("trim",$POSTtrackers);
	$POSTtrackers = array_map("makesafe",$POSTtrackers);
	$res = sql_query("SELECT tracker FROM trackers WHERE torrent=$id AND tracker<>'localhost'") or sqlerr(__FILE__,__LINE__);
	$trackers = array();
	while (list($tracker) = mysql_fetch_array($res)) $trackers[] = $tracker;
	$trackers_to_delete = array_diff($trackers,$POSTtrackers);
	$trackers_to_add = array_diff($POSTtrackers,$trackers);
	foreach ($POSTtrackers as $tid => $tracker) {
		if ($tracker) $state[$tracker] = 'skipped'; else unset($POSTtrackers[$tid]);
	}
	if ($trackers_to_delete)
	foreach ($trackers_to_delete as $tracker) {
		if ($tracker)
		sql_query("DELETE FROM trackers WHERE tracker='$tracker' AND torrent=$id") or sqlerr(__FILE__,__LINE__);
		$state[$tracker] = 'deleted';
	}
	if ($trackers_to_add)
	foreach ($trackers_to_add as $tracker) {
		if ($tracker) {
			$peers = get_remote_peers($tracker, $row['info_hash'],'announce');
			$reason[$tracker] = makesafe($peers['state']);
			if (preg_match('/ok_/',$peers['state'])) {
				sql_query("INSERT INTO trackers (tracker,torrent) VALUES ('$tracker',$id)");// or sqlerr(__FILE__,__LINE__);
				sql_query("UPDATE LOW_PRIORITY trackers SET seeders=".(int)$peers['seeders'].", leechers=".(int)$peers['leechers'].", lastchecked=".time().", state='".mysql_real_escape_string($peers['state'])."' WHERE torrent=$id AND tracker='$tracker'") or sqlerr(__FILE__,__LINE__);
				$state[$tracker] = 'added';
			} else $state[$tracker] = 'failed';
		}
	}
	stdhead($tracker_lang['add_announce_urls']);
	getlang('remotepeers');
	print ('<table width="100%"><tr><td class="colhead">'.$tracker_lang['tracker'].'</td><td class="colhead">'.$tracker_lang['status'].'</td></tr>');
	foreach ($state AS $tracker => $status) {
		print ("<tr><td>$tracker</td><td>{$tracker_lang['tracker_'.$status]}{$reason[$tracker]}</td></tr>");
	}
	print "</table>";
	stdmsg($tracker_lang['success'],'<h1><a href="details.php?id='.$id.'">'.$tracker_lang['back_to_details'].'</a>');
	stdfoot();
	write_log("<a href=\"userdetails.php?id=\"{$CURUSER['id']}>{$CURUSER['username']}</a> �������������� ������� �������� � ID <a href=\"details.php?id=$id\">$id</a>",'torrent');
	die();
}


function bark($msg) {
	stderr("������", $msg);
}

foreach(explode(":","type:name") as $v) {
	if (!isset($_POST[$v]))
	bark("�� ��� ���� ���������");
}

if (!is_array($_POST["type"]))
bark("������ ��������� ��������� ���������!");
else
foreach ($_POST['type'] as $cat) if (!is_valid_id($cat)) bark($tracker_lang['error'],$tracker_lang['invalid_id']);


if ($_POST['multi']) $multi=1; else $multi=0;


if ($_POST['nofile']) {} else {
	if (isset($_FILES["tfile"]) && !empty($_FILES["tfile"]["name"]))
	$update_torrent = true;
}
$res = sql_query("SELECT torrents.owner, torrents.filename, torrents.images, torrents.topic_id, torrents.modcomm, torrents.moderated FROM torrents WHERE torrents.id = $id");
$row = mysql_fetch_array($res);
if (!$row)
stderr($tracker_lang["error"],$tracker_lang["invalid_id"]);

if (($row["filename"] == 'nofile') && (get_user_class() == UC_UPLOADER)) $tedit = 1; else $tedit = 0;

if ($CURUSER["id"] != $row["owner"] && get_user_class() < UC_MODERATOR && !$tedit)
bark("You're not the owner! How did that happen?\n");

$updateset = array();

////////////////////////////////////////////////

$images = explode(',',$row['images']);

//////////////////////////////////////////////
//////////////Take Image Uploads//////////////

$maxfilesize = 512000; // 500kb

for ($x=0; $x < $CACHEARRAY['max_images']; $x++) {

	if (!empty($_POST['img'.$x])) {
		$img=trim(htmlspecialchars((string)$_POST['img'.$x]));
		if (strpos($img,',') || strpos($img,'?')) stderr($tracker_lang['error'],'������������ ����������� ���������');

		if (!preg_match('/^(.+)\.(gif|png|jpeg|jpg)$/si', $img))
		stderr($tracker_lang['error'],'����������� �������� '.($x+1).' - �� ��������');

		/*  $check = remote_fsize($img);
		 if (!$check) stderr($tracker_lang['error'],'�� ������� ���������� ������ �������� '.$y);
		 if ($check>$maxfilesize) stderr($tracker_lang['error'],'������������ ������ �������� 512kb. ������ ��� �������� �������� '.$y);
		 */ $inames[]=$img;
	} else unset($images[$x]);
}

$image = $inames;

$image = @array_shift($image);
$images = @implode(',',$inames);

$updateset[]="images=".sqlesc($images);

////////////////////////////////////////////////

if (($_POST['nofile']) && (empty($_POST['nofilesize']))) bark("�� �� ������� ������ �� ������� ������!");

if ($_POST['nofile']) {$fname = 'nofile'; } else {
	$fname = $row["filename"];
	preg_match('/^(.+)\.torrent$/si', $fname, $matches);
	$shortfname = $matches[1];
}

if ($update_torrent) {

	$f = $_FILES["tfile"];
	$fname = unesc($f["name"]);

	if (empty($fname))
	bark("���� �� ��������. ������ ��� �����!");
	if (!validfilename($fname))
	bark("�������� ��� �����!");
	if (!preg_match('/^(.+)\.torrent$/si', $fname, $matches))
	bark("�������� ��� ����� (�� .torrent).");
	$tmpname = $f["tmp_name"];
	if (!is_uploaded_file($tmpname))
	bark("eek");
	if (!filesize($tmpname))
	bark("������ ����!");
	$dict = bdec_file($tmpname, $CACHEARRAY['max_torrent_size']);
	if (!isset($dict))
	bark("��� �� ����� �� ����������? ��� �� �������-����������� ����!");
	list($info) = dict_check($dict, "info");
	list($dname, $plen, $pieces) = dict_check($info, "name(string):piece length(integer):pieces(string)");
	if (strlen($pieces) % 20 != 0)
	bark("invalid pieces");

	$filelist = array();
	$totallen = dict_get($info, "length", "integer");
	if (isset($totallen)) {
		$filelist[] = array($dname, $totallen);
		$torrent_type = 0;
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
			if ($ffe == 'Thumbs.db')
			{
				stderr("������", "� ��������� ��������� ������� ����� Thumbs.db!");
				die;
			}
		}
		$torrent_type = 1;
	}

	$dict=bdec(benc($dict)); // double up on the becoding solves the occassional misgenerated infohash

	unset($dict['value']['nodes']); // remove cached peers (Bitcomet & Azareus)
	unset($dict['value']['azureus_properties']); // remove azureus properties
	unset($dict['value']['comment']);
	unset($dict['value']['created by']);
	unset($dict['value']['publisher']);
	unset($dict['value']['publisher.windows-1251']);
	unset($dict['value']['publisher-url']);
	unset($dict['value']['publisher-url.windows-1251']);


	if (!$multi) {
		//  $dict['value']['info']['value']['private']=bdec('i1e');  // add private tracker flag
		unset($dict['value']['announce-list']);
		unset($dict['value']['announce']);

	} else $anarray = get_announce_urls($dict);

	if ($multi && !$anarray) stderr($tracker_lang['error'],'���� �������-���� �� �������� ���������������. <a href="javascript:history.go(-1);">�����</a>');

	$dict=bdec(benc($dict)); // double up on the becoding solves the occassional misgenerated infohash

	list($info) = dict_check($dict, "info");

	$infohash = sha1($info["string"]);
	move_uploaded_file($tmpname, ROOT_PATH."torrents/$id.torrent");
      $fp = @file_put_contents("torrents/$id.torrent", benc($dict['value']['info']));
      if (!$fp) stderr($REL_LANG->say_by_key('error'),'�������� torrent ����� �� �������');
      
	$updateset[] = "info_hash = " . sqlesc($infohash);
	$updateset[] = "filename = " . sqlesc($fname);
	sql_query("DELETE FROM files WHERE torrent = $id");
	sql_query("DELETE FROM trackers WHERE torrent = ".$id);
	// insert localhost tracker
	if ($update_torrent) sql_query("INSERT INTO trackers (torrent,tracker) VALUES ($id,'localhost')");
	// Insert remote trackers //
	if ($anarray) {
		foreach ($anarray as $anurl) sql_query("INSERT INTO trackers (torrent,tracker) VALUES ($id,".sqlesc($anurl).")");
	}
	// trackers insert end
	$nf = count($filelist);

	sql_query("INSERT INTO files (torrent, filename, size) VALUES ($id, ".sqlesc($dname).",".$totallen.")");
	$updateset[] = "size = ".$totallen;
	$updateset[] = "numfiles = ".$nf;
	$updateset[] = "ismulti = ".$torrent_type;
	if ($_POST['nofile']) $dname = 'nofile';

}
// ����� �� ��������

$name = htmlspecialchars((string)($_POST['name']));

$updateset[] = "name = " . sqlesc($name);

$modcomm = (string)$_POST['modcomm'];
if ($row['modcomm'] != $modcomm) $updateset[] = "modcomm = ".sqlesc('��������� ��������� '.$CURUSER['username'].' � '.mkprettytime(time())."\n".htmlspecialchars($modcomm));

$catsstr = implode(',',$_POST['type']);

$updateset[] = "category = " . sqlesc($catsstr);

if ($_POST['nofile']) {

	$wastor = sql_query("SELECT filename FROM torrents WHERE id =".$id);
	$wastor = mysql_result($wastor,0);

	if ($wastor != 'nofile') {
		sql_query("DELETE FROM files WHERE torrent = ".$id);
		sql_query("DELETE FROM peers WHERE torrent = ".$id);
		sql_query("DELETE FROM snatched WHERE torrent = ".$id);
		sql_query("DELETE FROM trackers WHERE torrent = ".$id);
		$updateset[] = "filename = 'nofile'";

		$ff = "torrents/" . $id.".torrent";
		@unlink($ff);
	}

	$nfz = $_POST['nofilesize'];
	$nofilesize = (int)($nfz*1024*1024);
	$updateset[] = "size = " . $nofilesize;
}

// get relgroup
$relgroup = (int)$_POST['relgroup'];

if ($relgroup) {
	$relgroup = @mysql_result(sql_query("SELECT id FROM relgroups WHERE id=$relgroup"),0);

	if (!$relgroup) stderr($tracker_lang['error'],$tracker_lang['no_relgroup']);

}
$updateset[] = "relgroup = $relgroup";

if(get_user_class() >= UC_MODERATOR) {
	$updateset[] = "free = '".($_POST["free"]? 1 : 0)."'";

	$updateset[] = "banned = ".($_POST["banned"]?1:0);
	$updateset[] = "sticky = ".($_POST['sticky']?1:0);

	$updateset[] = "visible = '" . ($_POST["visible"] ? 1 : 0) . "'";
}


if ((get_user_class() >= UC_UPLOADER) && isset($_POST['approve'])) {
	$updateset[] = "moderated = 1";
	$updateset[] = "moderatedby = ".$CURUSER["id"];
	// send notifs
	if (!$row['moderated']) {
		$bfooter = <<<EOD
����� ���������� �����, ��������� �� ���� ������:

		{$CACHEARRAY['defaultbaseurl']}/details.php?id=$id

EOD;
		$descr = format_comment($row['descr']).nl2br($bfooter);
		send_notifs('torrents',format_comment($descr),$CURUSER['id']);
	}
} else $updateset[] = "moderatedby = 0";


$descr = ((string)$_POST['descr']);

$updateset[] = 'descr = '.sqlesc($descr);
/// get kinopoisk.ru trailer!

$online = get_trailer($descr);

// end get kinopoisk.ru trailer
if ($online) $updateset[] = 'online = '.sqlesc($online);

if ($_POST['upd']) $updateset[] = "added = '" . time() . "'";

sql_query("UPDATE torrents SET " . join(",", $updateset) . " WHERE id = $id");
if (mysql_errno() == 1062) stderr($tracker_lang['error'],'Torrent already uploaded!'); elseif (mysql_errno()) sqlerr(__FILE__,__LINE__);

$CACHE->clearGroupCache('block-indextorrents');


if ($CACHEARRAY['use_integration']) {
	/// IPB INTEGRATION ///// EDIT WIKI CONTAINER ////////////

	if ($image <> '') $image = "<div align=\"center\"><a href=\"$image\" target=\"_blank\"><img alt=\"������ ��� ������ (�������� ��� ��������� ������� �����������)\" src=\"$image\" width=\"240\" border=\"0\" class=\"linked-image\" /></a></div><br />"; else
	$image = "<div align=\"center\"><img src=\"{$CACHEARRAY['defaultbaseurl']}/pic/noimage.gif\" border=\"0\" class=\"linked-image\" /></div><br />";

	if (!empty($_POST['topic'])) {
		if (is_valid_id($_POST['topic'])) {
			$topicid =  (int) $_POST['topic'];
			sql_query("UPDATE torrents SET topic_id =".$topicid." WHERE id =".$id);
			$topicedit = 1;
		} else stderr($tracker_lang["error"],$tracker_lang["invalid_id"]);
	}  else {
		$topicid = $row['topic_id'];
	}


	if ($topicid <> 0) {
		$forumdesc = $image;
		$tree=make_tree();
		$cats = explode(',',$_POST['type']);
		$cat= array_shift($cats);
		$cat = get_cur_branch($tree,$cat);
		$childs = get_childs($tree,$cat['parent_id']);
		if ($childs) {
			foreach($childs as $child)
			if (($cat['id'] != $child['id']) && in_array($child['id'],$cats)) $chsel[]=makesafe($child['name']);
		}

		$forumdesc .= "<table width=\"100%\" border=\"1\"><tr><td valign=\"top\"><b>��� (����):</b></td><td>".get_cur_position_str($tree,$cat['id']).(is_array($chsel)?', '.implode(', ',$chsel):'')."</td></tr><tr><td><b>��������:</b></td><td>$name</td></tr>";
		$forumdesc .= "<tr><td valign=\"top\"><b>".$tracker_lang['description'].":</b></td><td>".format_comment($descr)."</td></tr>";


		$isnofilesize = sql_query("SELECT filename,size FROM torrents WHERE id = $id");
		$isnofilesize = mysql_fetch_array($isnofilesize);
		$topicfooter = "<tr><td valign=\"top\"><b>������ �����:</b></td><td>".round($isnofilesize['size']/1024/1024)." ��</td></tr>";

		$topicfooter .= "<tr><td valign=\"top\"><b>".(($isnofilesize['filename'] != 'nofile')?"������� {$CACHEARRAY['defaultbaseurl']}:":"����� {$CACHEARRAY['defaultbaseurl']}:")."</b></td><td><div align=\"center\">[<span style=\"color:#FF0000\"><a href=\"{$CACHEARRAY['defaultbaseurl']}/details.php?id=".$id."\">���������� ���� ����� �� {$CACHEARRAY['defaultbaseurl']}</a></span>]</div></td></tr></table>";

		$forumdesc .= $topicfooter;

		// connecting to IPB DB
		forumconn();
		//connection opened

		$postid = sql_query("SELECT topic_firstpost FROM ".$fprefix."topics WHERE tid=".$topicid);
		$postid = mysql_result($postid,0);

		sql_query("UPDATE ".$fprefix."topics SET title = ".sqlesc($name)." WHERE tid=".$topicid);


		if ($CACHEARRAY['exporttype'] == "wiki")
		sql_query("UPDATE ".$fprefix."posts SET wiki = ".sqlesc($forumdesc).", post = '---' WHERE pid=".$postid);
		else
		sql_query("UPDATE ".$fprefix."posts SET post = ".sqlesc($forumdesc)." WHERE pid=".$postid);

		if ($topicedit) {
			$cutplus = strpos($name,"+");
			if ($cutplus === false)
			$topicname = $name;
			else $topicname = substr($name,0,$cutplus);
			if (!empty($_POST['source'])) $dsql = ", description = ".sqlesc(htmlspecialchars($_POST['source'])); else $dsql = '';
			$topic = sql_query("UPDATE ".$fprefix."topics SET title = ".sqlesc($topicname).$dsql." WHERE tid =".$topicid);

		}


		// closing IPB DB connection
		relconn();
		// connection closed

	}
	//////////////////////END/////////////////////////////////////
}

write_log("������� '$name' ��� �������������� ������������� $CURUSER[username]\n","torrent");

$returl = "details.php?id=$id";
if (isset($_POST["returnto"]))
$returl .= "&returnto=" . strip_tags($_POST["returnto"]);


safe_redirect($returl,1);

stderr($tracker_lang['success'],"����� ������� ��������, ������ �� ��������� � ��� �������".($anarray?"<img src=\"remote_check.php?id=$id\" width=\"0px\" height=\"0px\" border=\"0\"/>":''),'success');

?>
