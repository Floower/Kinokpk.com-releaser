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


require_once("include/benc.php");

$id = (int) $_POST['id'];
if (!$id) $id = (int) $_GET['id'];
$res = sql_query("SELECT torrents.id, torrents.name, torrents.owner, torrents.info_hash, torrents.filename, torrents.images, torrents.modcomm, torrents.moderated, torrents.moderatedby, torrents.descr FROM torrents WHERE torrents.id = $id");
$row = mysql_fetch_array($res);
if (!$row)
stderr($REL_LANG->say_by_key("error"),$REL_LANG->say_by_key("invalid_id"));


if (isset($_GET['checkonly'])) {

	headers(true);


	if (get_user_class() < UC_MODERATOR) die($REL_LANG->say_by_key('error').': '.$REL_LANG->say_by_key('invalid_id'));

	$id = (int) $_GET['id'];

	$REL_CACHE->clearGroupCache('block-indextorrents');

	if ($row['moderatedby']) {
		sql_query("UPDATE torrents SET moderatedby=0 WHERE id=$id");
		die($REL_LANG->say_by_key('not_yet_checked').' <a onclick="return ajaxcheck();" href="'.$REL_SEO->make_link('takeedit','checkonly','','id',$id).'">'.$REL_LANG->say_by_key('check').'</a>'.$return);
	}
	else {
		sql_query("UPDATE torrents SET moderatedby={$CURUSER['id']}, moderated=1 WHERE id=$id");
		// send notifs
		if (!$row['moderated']) {
			sql_query("UPDATE users SET ratingsum = ratingsum + {$REL_CRON['rating_perrelease']} WHERE id={$row['owner']}");
			$bfooter = <<<EOD
����� ���������� �����, ��������� �� ���� ������:

			{$REL_SEO->make_link('details','id',$id,'name',translit($row['name']))}

EOD;
			$descr = format_comment($row['descr']).nl2br($bfooter);
			send_notifs('torrents',format_comment($descr));
		}

		die($REL_LANG->say_by_key('checked_by').'<a href="'.$REL_SEO->make_link('userdetails','id',$CURUSER['id'],'username',translit($CURUSER['username'])).'">'.get_user_class_color(get_user_class(),$CURUSER['username']).'</a> <a onclick="return ajaxcheck();" href="'.$REL_SEO->make_link('takeedit','checkonly','','id',$id).'">'.$REL_LANG->say_by_key('uncheck').'</a>'.$return);
	}
} elseif(isset($_POST['add_trackers'])) {
	if (get_user_class() < UC_UPLOADER) stderr($REL_LANG->say_by_key('error'),$REL_LANG->say_by_key('access_deined'));

	if (!isset($_POST['trackers'])) stderr($REL_LANG->say_by_key('error'),'�� ��� ���� ���������');
	$POSTtrackers = explode("\n",trim((string)$_POST['trackers']));
	if (!$POSTtrackers) stderr($REL_LANG->say_by_key('error'), '������ ��������� ��������');

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
				sql_query("INSERT INTO trackers (tracker,torrent) VALUES (".sqlesc(strip_tags($tracker)).",$id)");// or sqlerr(__FILE__,__LINE__);
				sql_query("UPDATE LOW_PRIORITY trackers SET seeders=".(int)$peers['seeders'].", leechers=".(int)$peers['leechers'].", lastchecked=".time().", state='".mysql_real_escape_string($peers['state'])."' WHERE torrent=$id AND tracker='$tracker'") or sqlerr(__FILE__,__LINE__);
				$state[$tracker] = 'added';
			} else $state[$tracker] = 'failed';
		}
	}
	$REL_TPL->stdhead($REL_LANG->say_by_key('add_announce_urls'));

	print ('<table width="100%"><tr><td class="colhead">'.$REL_LANG->say_by_key('tracker').'</td><td class="colhead">'.$REL_LANG->say_by_key('status').'</td></tr>');
	foreach ($state AS $tracker => $status) {
		print ("<tr><td>$tracker</td><td>{$REL_LANG->say_by_key('tracker_'.$status)}{$reason[$tracker]}</td></tr>");
	}
	print "</table>";
	stdmsg($REL_LANG->say_by_key('success'),'<h1><a href="'.$REL_SEO->make_link('details','id', $row['id'] ,'name',translit($row['name'])).'">'.$REL_LANG->say_by_key('back_to_details').'</a>');
	$REL_TPL->stdfoot();
	write_log("<a href=\"".$REL_SEO->make_link('userdetails','id',$CURUSER['id'],'username',translit($CURUSER['username']))."\">{$CURUSER['username']}</a> �������������� ������� �������� � ID <a href=\"".$REL_SEO->make_link('details','id',$id,'name',translit($row['name']))."\">$id</a>",'torrent');
	die();
}


function bark($msg) {
	global $REL_LANG;
	stderr($REL_LANG->say_by_key('error'), $msg." <a href=\"javascript:history.go(-1);\">{$REL_LANG->say_by_key('ago')}</a>");
}

foreach(explode(":","type:name") as $v) {
	if (!isset($_POST[$v]))
	bark("�� ��� ���� ���������");
}

$name = htmlspecialchars((string)($_POST['name']));
if (!preg_match("#(.*?) \/ (.*?) \([0-9-]+\) \[(.*?)\]#si",$name))
bark ("��� ������ ��������� �� �� �������:<br/>{$REL_LANG->say_by_key('taken_from_torrent')}");

if (!is_array($_POST["type"]))
bark("������ ��������� ��������� ���������!");
else
foreach ($_POST['type'] as $cat) if (!is_valid_id($cat)) bark($REL_LANG->say_by_key('error'),$REL_LANG->say_by_key('invalid_id'));


if ($_POST['multi']) $multi=1; else $multi=0;

$updateset = array();

if ($_POST['nofile']) {} else {
	if (isset($_FILES["tfile"]) && !empty($_FILES["tfile"]["name"]))
	$update_torrent = true;
	$tiger_hash = trim((string)$_POST['tiger_hash']);
	if ((!preg_match("/[^a-zA-Z0-9]/",$tiger_hash) || (strlen($tiger_hash)<>38)) && $tiger_hash) stderr($REL_LANG->say_by_key('error'),$REL_LANG->say_by_key('invalid_tiger_hash'));
	$updateset[] = "tiger_hash = ".sqlesc($tiger_hash);
}

if (($row["filename"] == 'nofile') && (get_user_class() == UC_UPLOADER)) $tedit = 1; else $tedit = 0;

if ($CURUSER["id"] != $row["owner"] && get_user_class() < UC_MODERATOR && !$tedit)
bark("You're not the owner! How did that happen?\n");

////////////////////////////////////////////////

$images = explode(',',$row['images']);

//////////////////////////////////////////////
//////////////Take Image Uploads//////////////

$maxfilesize = 512000; // 500kb

for ($x=0; $x < $REL_CONFIG['max_images']; $x++) {

	if (!empty($_POST['img'.$x])) {
		$img=trim(htmlspecialchars((string)$_POST['img'.$x]));
		if (strpos($img,',') || strpos($img,'?')) stderr($REL_LANG->say_by_key('error'),'������������ ����������� ���������');

		if (!preg_match('/^(.+)\.(gif|png|jpeg|jpg)$/si', $img))
		stderr($REL_LANG->say_by_key('error'),'����������� �������� '.($x+1).' - �� ��������');

		/*  $check = remote_fsize($img);
		 if (!$check) stderr($REL_LANG->say_by_key('error'),'�� ������� ���������� ������ �������� '.$y);
		 if ($check>$maxfilesize) stderr($REL_LANG->say_by_key('error'),'������������ ������ �������� 512kb. ������ ��� �������� �������� '.$y);
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
	$dict = bdec_file($tmpname, $REL_CONFIG['max_torrent_size']);
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

	$infohash = sha1($info["string"]);
	move_uploaded_file($tmpname, ROOT_PATH."torrents/$id.torrent");
	$fp = fopen("torrents/$id.torrent", "w");
	if ($fp) {
		@fwrite($fp, benc($dict['value']['info']), strlen(benc($dict['value']['info'])));
		fclose($fp);
		@chmod($fp, 0644);
	}
	$updateset[] = "info_hash = " . sqlesc($infohash);
	$updateset[] = "filename = " . sqlesc($fname);
	sql_query("DELETE FROM files WHERE torrent = $id");
	sql_query("DELETE FROM trackers WHERE torrent = ".$id);
	// insert localhost tracker
	if ($update_torrent) sql_query("INSERT INTO trackers (torrent,tracker) VALUES ($id,'localhost')");
	// Insert remote trackers //
	if ($anarray) {
		foreach ($anarray as $anurl) sql_query("INSERT INTO trackers (torrent,tracker) VALUES ($id,".sqlesc(strip_tags($anurl)).")");
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

	if (!$relgroup) stderr($REL_LANG->say_by_key('error'),$REL_LANG->say_by_key('no_relgroup'));

}
$updateset[] = "relgroup = $relgroup";

if(get_user_class() >= UC_MODERATOR) {
	$updateset[] = "free = '".($_POST["free"]? 1 : 0)."'";

	$updateset[] = "banned = ".($_POST["banned"]?1:0);
	$updateset[] = "sticky = ".($_POST['sticky']?1:0);

	$updateset[] = "visible = '" . ($_POST["visible"] ? 1 : 0) . "'";
}


if ((get_user_class() >= UC_UPLOADER) && isset($_POST['approve'])) {
	if (!$row['moderated']) {
		sql_query("UPDATE users SET ratingsum = ratingsum + {$REL_CRON['rating_perrelease']} WHERE id={$row['owner']}");
	}
	$updateset[] = "moderated = 1";
	$updateset[] = "moderatedby = ".$CURUSER["id"];
	// send notifs
	if (!$row['moderated']) {
		$bfooter = <<<EOD
����� ���������� �����, ��������� �� ���� ������:

		{$REL_SEO->make_link('details','id',$id,'name',translit($row['name']))}

EOD;
		$descr = "<hr/>".format_comment($row['descr']).nl2br($bfooter);
		send_notifs('torrents',format_comment($descr));
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
if (mysql_errno() == 1062) stderr($REL_LANG->say_by_key('error'),'Torrent already uploaded!'); elseif (mysql_errno()) sqlerr(__FILE__,__LINE__);

$REL_CACHE->clearGroupCache('block-indextorrents');

write_log("������� '$name' ��� �������������� ������������� $CURUSER[username]\n","torrent");

$returl = $REL_SEO->make_link('details','id',$id,'name',translit($row['name']));
if (isset($_POST["returnto"]))
$returl .= "&returnto=" . strip_tags($_POST["returnto"]);


safe_redirect($returl,1);

stderr($REL_LANG->say_by_key('success'),"����� ������� ��������, ������ �� ��������� � ��� �������".($anarray?"<img src=\"".$REL_SEO->make_link('remote_check','id',$id)."\" width=\"0px\" height=\"0px\" border=\"0\"/>":''),'success');

?>
