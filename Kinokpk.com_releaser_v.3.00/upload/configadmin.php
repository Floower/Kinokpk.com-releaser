<?php
/**
 * Configuration script (main)
 * @license GNU GPLv3 http://opensource.org/licenses/gpl-3.0.html
 * @package Kinokpk.com releaser
 * @author ZonD80 <admin@kinokpk.com>
 * @copyright (C) 2008-now, ZonD80, Germany, TorrentsBook.com
 * @link http://dev.kinokpk.com
 */

require "include/bittorrent.php";
dbconn();
loggedinorreturn();
if (get_user_class() < UC_SYSOP) stderr($tracker_lang['error'],$tracker_lang['access_denied']);
httpauth();

if (!isset($_GET['action'])){
	stdhead("�������� ���������");

	begin_frame("�������� ��������� Kinokpk.com releaser ".RELVERSION);
	print('<form action="configadmin.php?action=save" method="POST">');
	print('<table width="100%" border="1">');

	print('<tr><td align="center" colspan="2" class="colhead">�������� ���������</td></tr>');

	print('<tr><td>����� ����� (��� /):</td><td><input type="text" name="defaultbaseurl" size="30" value="'.$CACHEARRAY['defaultbaseurl'].'"> <br/>��������, "http://www.kinokpk.com"</td></tr>');
	print('<tr><td>�������� ����� (title):</td><td><input type="text" name="sitename" size="80" value="'.$CACHEARRAY['sitename'].'"> <br/>��������, "������� �������� �����������"</td></tr>');
	print('<tr><td>�������� ����� (meta description):</td><td><input type="text" name="description" size="80" value="'.$CACHEARRAY['description'].'"> <br/>��������, "����� ������� ���������� ������� ����"</td></tr>');
	print('<tr><td>�������� ����� (meta keywords):</td><td><input type="text" name="keywords" size="80" value="'.$CACHEARRAY['keywords'].'"> <br/>��������, "�������, ����������, ������, �������"</td></tr>');
	print('<tr><td>�����, � �������� ����� ������������ ��������� �����:</td><td><input type="text" name="siteemail" size="30" value="'.$CACHEARRAY['siteemail'].'"> <br/>��������, "bot@kinokpk.com"</td></tr>');
	print('<tr><td>����� ��� ����� � ���������������:</td><td><input type="text" name="adminemail" size="30" value="'.$CACHEARRAY['adminemail'].'"> <br/>��������, "admin@windows.lox"</td></tr>');
	print('<tr><td>���� �������� �� ��������� (��� lang_%����%):</td><td><input type="text" name="default_language" size="2" value="'.$CACHEARRAY['default_language'].'"></td></tr>');
	print('<tr><td>������������ ������� �������������� (��������� �� �������������):</td><td><select name="use_lang"><option value="1" '.($CACHEARRAY['use_lang']==1?"selected":"").'>��</option><option value="0" '.($CACHEARRAY['use_lang']==0?"selected":"").'>���</option> ����������� ������ ������ 2 ����� ����� (ru,en)</select></td></tr>');
	print('<tr><td>����������� ���� ��� ������ � ���������������� (themes/%����%):</td><td><input type="text" name="default_theme" size="10" value="'.$CACHEARRAY['default_theme'].'"> �� ��������� "kinokpk"</td></tr>');
	print('<tr><td>��� �������� ��� ����������� ����� ��������:<br /><small>*�� ������ ������������ ������ <b>{datenow}</b> ��� ������ �������� ����</small></td><td><input type="text" name="yourcopy" size="60" value="'.$CACHEARRAY['yourcopy'].'"> <br/>��������, "&copy; 2008-{datenow} ��� ����"</td></tr>');
	print('<tr><td>������������ ������� ������ (��������� �� �������������):</td><td><select name="use_blocks"><option value="1" '.($CACHEARRAY['use_blocks']==1?"selected":"").'>��</option><option value="0" '.($CACHEARRAY['use_blocks']==0?"selected":"").'>���</option></select></td></tr>');
	print('<tr><td>������������ gzip ������ ��� �������:</td><td><select name="use_gzip"><option value="1" '.($CACHEARRAY['use_gzip']==1?"selected":"").'>��</option><option value="0" '.($CACHEARRAY['use_gzip']==0?"selected":"").'>���</option></select></td></tr>');
	print('<tr><td>������������ ������� ����� �� IP/��������:</td><td><select name="use_ipbans"><option value="1" '.($CACHEARRAY['use_ipbans']==1?"selected":"").'>��</option><option value="0" '.($CACHEARRAY['use_ipbans']==0?"selected":"").'>���</option></select></td></tr>');
	print('<tr><td>��� SMTP:</td><td><input type="text" name="smtptype" size="10" value="'.$CACHEARRAY['smtptype'].'"></td></tr>');
	print('<tr><td>�������� ������ ����� � ��������:</td><td><select name="announce_packed"><option value="1" '.($CACHEARRAY['announce_packed']==1?"selected":"").'>��</option><option value="0" '.($CACHEARRAY['announce_packed']==0?"selected":"").'>���</option></select> �� ���������, ��</td></tr>');

	print('<tr><td align="center" colspan="2" class="colhead">��������� ���������� � ������� IPB</td></tr>');

	print('<tr><td>������������ ���������� � ������� IPB:</td><td><select name="use_integration"><option value="1" '.($CACHEARRAY['use_integration']==1?"selected":"").'>��</option><option value="0" '.($CACHEARRAY['use_integration']==0?"selected":"").'>���</option></select></td></tr>');
	print('<tr><td>������ �� ������ ������ ������ �� ��������:<br /><small>� ���� ������ ��� ������������ ������� ��� ����� �� ������� ������������ ����� ������ ������, ����� ������� ������������� ������ ������ �� ������</small></td><td><select name="ipb_password_priority"><option value="1" '.($CACHEARRAY['ipb_password_priority']==1?"selected":"").'>��</option><option value="0" '.($CACHEARRAY['ipb_password_priority']==0?"selected":"").'>���</option></select></td></tr>');
	print('<tr><td>��� �������� ������� �� �����:<br /><small>*��� ������������� ������� �������� � ����-������<br />���������� ���������� IPB � wikimedia � <a target="_blank" href="http://www.ipbwiki.com/">http://www.ipbwiki.com/</a></small></td><td><select name="exporttype"><option value="wiki" '.($CACHEARRAY['exporttype']=="wiki"?"selected":"").'>� ����-������</option><option value="post" '.($CACHEARRAY['exporttype']=="post"?"selected":"").'>��������������� � ����</option></select></td></tr>');
	print('<tr><td>����� ������ (��� /):</td><td><input type="text" name="forumurl" size="60" value="'.$CACHEARRAY['forumurl'].'"> <br/>��������, "http://forum.pdaprime.ru"</td></tr>');
	print('<tr><td>�������� ������:</td><td><input type="text" name="forumname" size="60" value="'.$CACHEARRAY['forumname'].'"> <br/>��������, "pdaPRIME.ru"</td></tr>');
	print('<tr><td>������� �������� cookie:</td><td><input type="text" name="ipb_cookie_prefix" size="4" value="'.$CACHEARRAY['ipb_cookie_prefix'].'"> �� ��������� IPB, �����</td></tr>');
	print('<tr><td>ID ������-�������:</td><td><input type="text" name="forum_bin_id" size="3" value="'.$CACHEARRAY['forum_bin_id'].'"></td></tr>');
	print('<tr><td>����� ������������� ����� �������� �� �����:</td><td><input type="text" name="defuserclass" size="1" value="'.$CACHEARRAY['defuserclass'].'"> �� ��������� IPB, "3"</td></tr>');
	print('<tr><td>ID ������ ��� �������� ������ �������:<br /><small>*��� ������, ����� ������� �� ��������� � ��������� �����, ���� �������� ������ ��� ����������� ������</small></td><td><input type="text" name="not_found_export_id" size="3" value="'.$CACHEARRAY['not_found_export_id'].'"></td></tr>');
	print('<tr><td>����� �� �������� ������ (��� /):</td><td><input type="text" name="emo_dir" size="10" value="'.$CACHEARRAY['emo_dir'].'"> �� ��������� IPB, "default"</td></tr>');


	print('<tr><td align="center" colspan="2" class="colhead">��������� �����������</td></tr>');

	print('<tr><td>��������� �����������:</td><td><select name="deny_signup"><option value="1" '.($CACHEARRAY['deny_signup']==1?"selected":"").'>��</option><option value="0" '.($CACHEARRAY['deny_signup']==0?"selected":"").'>���</option></select></td></tr>');
	print('<tr><td>��������� ����������� �� ������������:</td><td><select name="allow_invite_signup"><option value="1" '.($CACHEARRAY['allow_invite_signup']==1?"selected":"").'>��</option><option value="0" '.($CACHEARRAY['allow_invite_signup']==0?"selected":"").'>���</option></select></td></tr>');
	print('<tr><td>��������� ���� ��� �����������:</td><td>'.list_timezones('register_timezone',$CACHEARRAY['register_timezone']).'</td></tr>');
	print('<tr><td>������������ ��������� ��������� �� e-mail:</td><td><select name="use_email_act"><option value="1" '.($CACHEARRAY['use_email_act']==1?"selected":"").'>��</option><option value="0" '.($CACHEARRAY['use_email_act']==0?"selected":"").'>���</option></select></td></tr>');
	print('<tr><td>������������ �����:<br /><small>*�� ������ ������������������ �� <a target="_blank" href="http://recaptcha.net">ReCaptcha.net</a> � �������� ��������� � ��������� ����� ��� ������������� ���� �����</small></td><td><select name="use_captcha"><option  value="1" '.($CACHEARRAY['use_captcha']==1?"selected":"").'>��</option><option value="0" '.($CACHEARRAY['use_captcha']==0?"selected":"").'>���</option></select></td></tr>');
	print('<tr><td>��������� ���� �����:</td><td><input type="text" name="re_publickey" size="80" value="'.$CACHEARRAY['re_publickey'].'"></td></tr>');
	print('<tr><td>��������� ���� �����:</td><td><input type="text" name="re_privatekey" size="80" value="'.$CACHEARRAY['re_privatekey'].'"></td></tr>');
	print('<tr><td>����������� ����������� (����.���� �/��� ��):</td><td><input type="text" name="default_notifs" size="120" value="'.$CACHEARRAY['default_notifs'].'"></td></tr>');
	print('<tr><td>����������� ����������� � Email:</td><td><input type="text" name="default_emailnotifs" size="120" value="'.$CACHEARRAY['default_emailnotifs'].'"></td></tr>');
	print('<tr><td colspan="2"><small>*��� ���� ����������� � Kinokpk.com releaser '.RELVERSION.':<br/>unread,torrents,comments,pollcomments,newscomments,usercomments,reqcomments,rgcomments,pages,pagecomments,friends,users,reports,unchecked ; ��������� - <a target="_blank" href="mynotifs.php?settings">��������� ���� �����������</a></small></td></tr>');


	print('<tr><td align="center" colspan="2" class="colhead">��������� �����������</td></tr>');

	print('<tr><td>������������ ���������� �������������:</td><td><input type="text" name="maxusers" size="6" value="'.$CACHEARRAY['maxusers'].'">�������������, ������� 0 ��� ���������� ������</td></tr>');
	print('<tr><td>������������ ���������� ��������� � ������ �����:</td><td><input type="text" name="pm_max" size="4" value="'.$CACHEARRAY['pm_max'].'">���������</td></tr>');
	print('<tr><td>������������ ������ ������:</td><td><input type="text" name="avatar_max_width" size="3" value="'.$CACHEARRAY['avatar_max_width'].'">��������</td></tr>');
	print('<tr><td>������������ ������ ������:</td><td><input type="text" name="avatar_max_height" size="3" value="'.$CACHEARRAY['avatar_max_height'].'">��������</td></tr>');
	print('<tr><td>��������� ����������� � ��������� �������:</td><td><select name="nc"><option value=1 '.($CACHEARRAY['nc']?"selected":"").'>��</option><option value="0" '.(!$CACHEARRAY['nc']?"selected":"").'>���</option></select></td></tr>');
	print('<tr><td>������������ ������ �������-����� � ������:</td><td><input type="text" name="max_torrent_size" size="10" value="'.$CACHEARRAY['max_torrent_size'].'">����</td></tr>');
	print('<tr><td>������������ ���������� �������� ��� ������:</td><td><input type="text" name="max_images" size="2" value="'.$CACHEARRAY['max_images'].'"><br/>��������, "2"</td></tr>');
	print('<tr><td>��������� adult �������:<br /><small>*����� �� ��������� ���������� ��������� "XXX �����", ������������ ����� �������� ����������� ���� ��������� � �������<br /><b>���� ��������� ������, ��� ����, ���������� �� ����� ������� <u>��� ��������</u></b></small></td><td><input type="text" name="pron_cats" size="60" value="'.$CACHEARRAY['pron_cats'].'"><br/>��������, "13,14"</td></tr>');

	print('<tr><td align="center" colspan="2" class="colhead">��������� ������������</td></tr>');

	print('<tr><td>����-�������� � ��������:</td><td><input type="text" name="as_timeout" size="10" value="'.$CACHEARRAY['as_timeout'].'">������</td></tr>');
	print('<tr><td>������������ �������� ��������� 5 ������������ (��������):</td><td><select name="as_check_messages"><option value="1" '.($CACHEARRAY['as_check_messages']==1?"selected":"").'>��</option><option value="0" '.($CACHEARRAY['as_check_messages']==0?"selected":"").'>���</option></select></td></tr>');
	print('<tr><td>�����-�����:</td><td><select name="debug_mode"><option value="1" '.($CACHEARRAY['debug_mode']==1?"selected":"").'>��</option><option value="0" '.($CACHEARRAY['debug_mode']==0?"selected":"").'>���</option></select></td></tr>');

	print('<tr><td align="center" colspan="2" class="colhead">������</td></tr>');

	print('<tr><td>����������� ������������� �������� ������� ������ � ���������.��:<br/><small>*�������� ������, ���� � �������� ������ ���� ������ ���� http://www.kinopoisk.ru/level/1/film/ID_������</small></td><td><select name="use_kinopoisk_trailers"><option value="1" '.($CACHEARRAY['use_kinopoisk_trailers']==1?"selected":"").'>��</option><option value="0" '.($CACHEARRAY['use_kinopoisk_trailers']==0?"selected":"").'>���</option></select></td></tr>');
	print('<tr><td>���������� ������� � ������ ������� �� ��������:<br /><small>*��� ��������� ����� ��������� ���������� �������� ��� browse</small></td><td><input type="text" name="torrentsperpage" size="3" value="'.$CACHEARRAY['torrentsperpage'].'">�������</td></tr>');
	print('<tr><td>������������ TTL (���� �������� ������� ���������):</td><td><select name="use_ttl"><option value="1" '.($CACHEARRAY['use_ttl']==1?"selected":"").'>��</option><option value="0" '.($CACHEARRAY['use_ttl']==0?"selected":"").'>���</option></select></td></tr>');
	print('<tr><td>������������ ������� ����������� ������� �� �������:</td><td><select name="use_wait"><option value="1" '.($CACHEARRAY['use_wait']==1?"selected":"").'>��</option><option value="0" '.($CACHEARRAY['use_wait']==0?"selected":"").'>���</option></select></td></tr>');

	print('<tr><td align="center" colspan="2"><input type="submit" value="��������� ���������"><input type="reset" value="��������"></td></tr></table></form>');
	end_frame();
	stdfoot();

}

elseif ($_GET['action'] == 'save'){
	$reqparametres = array('torrentsperpage','maxusers','max_torrent_size','max_images','defaultbaseurl','siteemail','adminemail','sitename','description','keywords',
'forumname','yourcopy','pm_max','default_language',
'avatar_max_width','avatar_max_height','default_theme','nc','deny_signup','allow_invite_signup',
'use_ttl','use_email_act','use_wait','use_lang','use_captcha','use_blocks','use_gzip','use_ipbans','smtptype',
'as_timeout','as_check_messages','use_integration','debug_mode','ipb_cookie_prefix','announce_packed','pron_cats','register_timezone','default_notifs','default_emailnotifs','use_kinopoisk_trailers');
	$int_param = array('exporttype','forumurl','forum_bin_id','defuserclass','not_found_export_id','emo_dir','ipb_password_priority');
	$captcha_param = array('re_publickey','re_privatekey');

	$updateset = array();

	foreach ($reqparametres as $param) {
		if (!isset($_POST[$param]) && ($param != 'forumname') && ($param != 'ipb_cookie_prefix') && ($param != 'pron_cats')) stderr($tracker_lang['error'],"��������� ���� �� ��������� ($param)");
		$updateset[] = "UPDATE cache_stats SET cache_value=".sqlesc($_POST[$param])." WHERE cache_name='$param'";
	}

	if ($_POST['use_integration'] == 1) {
		foreach ($int_param as $param) {
			if (!isset($_POST[$param])) stderr($tracker_lang['error'],"��������� ���� ��� ���������� � ������� �� ���������");
			$updateset[] = "UPDATE cache_stats SET cache_value=".sqlesc($_POST[$param])." WHERE cache_name='$param'";
		}
	}
	if ($_POST['use_captcha'] == 1) {
		foreach ($captcha_param as $param) {
			if (!$_POST[$param] || !isset($_POST[$param])) stderr($tracker_lang['error'],"��������� ��� ��������� ����� ����� �� ����������");
			$updateset[] = "UPDATE cache_stats SET cache_value=".sqlesc($_POST[$param])." WHERE cache_name='$param'";
		}
	}

	foreach ($updateset as $query) sql_query($query);

	$CACHE->clearCache('system','config');

	safe_redirect(" configadmin.php");

}

else stderr($tracker_lang['error'],"Unknown action");

?>