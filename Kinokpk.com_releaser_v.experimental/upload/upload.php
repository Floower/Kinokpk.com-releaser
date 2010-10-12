<?php
/**
 * Upload torrent script
 * @license GNU GPLv3 http://opensource.org/licenses/gpl-3.0.html
 * @package Kinokpk.com releaser
 * @author ZonD80 <admin@kinokpk.com>
 * @copyright (C) 2008-now, ZonD80, Germany, TorrentsBook.com
 * @link http://dev.kinokpk.com
 */


require_once("include/bittorrent.php");


dbconn();



loggedinorreturn();


//stderr('�������� ������� �������� ���������','�������� ����� ������� �������� ��������� ��������������');
$REL_TPL->stdhead($REL_LANG->say_by_key('upload_torrent'));
$tree = make_tree();

if (!isset($_GET['type'])) {
	$REL_TPL->begin_frame("�������� ��������� ������");
	print '<div align="center">
<form name="upload" action="'.$REL_SEO->make_link('upload').'" method="GET">
<table border="0" cellspacing="0" cellpadding="5">
<tr><td align="center" style="border: 0px;">'.gen_select_area('type',$tree).'</td></tr>
<tr><td align="center" colspan="2" style="border:0;"><input type="submit" class="btn button" value="�����" /></td></tr>
</table>
</form>
</div>
';
	$REL_TPL->end_frame();
	$REL_TPL->stdfoot();
	die();
}

elseif (!is_valid_id($_GET["type"])) {			stdmsg($REL_LANG->say_by_key('error'),$REL_LANG->say_by_key('invalid_id')); $REL_TPL->stdfoot();   exit;}

$type = (int) $_GET['type'];


$cat = get_cur_branch($tree,$type);
if (!$cat) {			stdmsg($REL_LANG->say_by_key('error'),$REL_LANG->say_by_key('invalid_id')); $REL_TPL->stdfoot();   exit;}


if (strlen($CURUSER['passkey']) != 32) {
	$CURUSER['passkey'] = md5($CURUSER['username'].time().$CURUSER['passhash']);
	sql_query("UPDATE users SET passkey='$CURUSER[passkey]' WHERE id=$CURUSER[id]");
}

?>
<script type="text/javascript">
//<!--
function checkname(element,is_sending) {
	var clicked;
	if (!clicked) {
		 element.value = '';
		clicked = true;
	}
	if (is_sending) {

		}
}
//-->
</script>
<form name="upload" enctype="multipart/form-data"
	action="<?=$REL_SEO->make_link('takeupload');?>" method="post">
<table border="1" cellspacing="0" cellpadding="5">
	<input type="hidden" name="type[]" value="<?=$type?>" />
	<tr>
		<td class="colhead" colspan="2"><?print $REL_LANG->say_by_key('upload_torrent')?></td>
	</tr>
	<?
	//tr($REL_LANG->say_by_key('announce_url'), $announce_urls[0], 1);
	tr($REL_LANG->say_by_key('torrent_file'), "<input  type=file name=tfile  size=80><br /><input type=\"checkbox\"  name=\"multi\" value=\"1\">&nbsp;{$REL_LANG->say_by_key('multitracker_torrent')}<br /><small>{$REL_LANG->say_by_key('multitracker_torrent_notice')}</small>\n", 1);
	if (get_user_class()>=UC_UPLOADER && $REL_CONFIG['use_dc'])
	tr($REL_LANG->say_by_key('tiger_hash'),"<input type=\"text\" size=\"60\" maxlength=\"38\" name=\"tiger_hash\" value=\"{$row['tiger_hash']}\"><br/>".$REL_LANG->say_by_key('tiger_hash_notice'),1);

	tr($REL_LANG->say_by_key('torrent_name')."<font color=\"red\">*</font>", "<input type=\"text\" name=\"name\" size=\"80\" value=\"������� / Original (��� ��� �������� �����) [��������, ����������]\"/><br />(".$REL_LANG->say_by_key('taken_from_torrent').")\n", 1);

	$imagecontent = '<br />';

	for ($i = 0; $i < $REL_CONFIG['max_images']; $i++) {
		$imagecontent.="<b>".$REL_LANG->say_by_key('image')." ".($i+1)." (URL):</b>&nbsp&nbsp<input type=\"text\" size=\"63\" name=\"img$i\"><hr />";
	}

	tr($REL_LANG->say_by_key('images'), $REL_LANG->say_by_key('max_file_size').": 500kb<br />".$REL_LANG->say_by_key('avialable_formats').": .jpg .png .gif$imagecontent\n", 1);
	tr($REL_LANG->say_by_key('description').'<br/>'.$REL_LANG->say_by_key('description_notice'),textbbcode("descr"),1);

	/// RELEASE group
	$rgarrayres = sql_query("SELECT id,name FROM relgroups ORDER BY added DESC");
	while($rgarrayrow = mysql_fetch_assoc($rgarrayres)) {
		$rgarray[$rgarrayrow['id']] = $rgarrayrow['name'];
	}

	if ($rgarray) {
		$rgselect = '<select name="relgroup" style="width: 150px;"><option value="0">('.$REL_LANG->say_by_key('choose').')</option>';
		foreach ($rgarray as $rgid=>$rgname) $rgselect.='<option value="'.$rgid.'">'.$rgname."</option>\n";
		$rgselect.='</select>';
	}
	if ($rgselect)
	tr($REL_LANG->say_by_key('relgroup'),$rgselect,1);

	$childs = get_childs($tree,$cat['parent_id']);
	if ($childs) {
		$chsel='<table width="100%" border="1">';
		foreach($childs as $child)
		if ($cat['id'] != $child['id']) $chsel.="<tr><td><input type=\"checkbox\" name=\"type[]\" value=\"{$child['id']}\">&nbsp;{$child['name']}</td></tr>";
		$chsel.="</table>";
	}
	tr ($REL_LANG->say_by_key('main_category'),get_cur_position_str($tree,$cat['id']),1);
	if ($chsel)
	tr ($REL_LANG->say_by_key('subcats'),$chsel,1);


	if (get_user_class() >= UC_MODERATOR) {
		tr($REL_LANG->say_by_key('golden'), "<input type=checkbox name=free value=\"1\"> ".$REL_LANG->say_by_key('golden_descr'), 1);
		tr("������", "<input type=\"checkbox\" name=\"sticky\" value=\"1\">���������� ���� ������� (������ �������)", 1);
	}
	tr("����� ��� ��������", "<input type=\"checkbox\" name=\"nofile\" value=\"1\">���� ����� ��� �������� ; ������: <input type=\"text\" name=\"nofilesize\" size=\"20\" /> ��", 1);
	tr("<font color=\"red\">�����</font>", "<input type=\"checkbox\" name=\"annonce\" value=\"1\">��� �����-���� ����� ������. ������ ��������� �� �����������,<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;�� ����������� �������� ����� ����� �������������!", 1);
	?>
	<tr>
		<td align="center" colspan="2"><input type="submit" class="btn button"
			value="<?=$REL_LANG->say_by_key('upload');?>" /></td>
	</tr>
</table>
</form>
	<?

	$REL_TPL->stdfoot();

	?>