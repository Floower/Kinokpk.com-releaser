<?php
/**
 * CRONJOB administration
 * @license GNU GPLv3 http://opensource.org/licenses/gpl-3.0.html
 * @package Kinokpk.com releaser
 * @author ZonD80 <admin@kinokpk.com>
 * @copyright (C) 2008-now, ZonD80, Germany, TorrentsBook.com
 * @link http://dev.kinokpk.com
 */

require "include/bittorrent.php";
dbconn();
loggedinorreturn();
if (get_user_class() < UC_SYSOP) stderr($REL_LANG->say_by_key('error'),$REL_LANG->say_by_key('access_denied'));
httpauth();

$REL_TPL->stdhead('��������� cron-�������');

$action = trim((string)$_GET['a']);

/**
 * Generates time-part of crontab line
 * @param int $minval Minutes value
 * @return string Generated part of string
 * @todo Hours, weeks, days.. etc.
 */
function gen_cron_min($minval) {
	//$day = 86400;
	//$hour = 3600;
	if (!$minval) return '*';
	$min = 60;
	$mincount = 0;
	$return = '0';
	if ($minval<$min) {
		while ($mincount<$min) {
			$mincount = $mincount+$minval;
			if ($mincount==60) break;
			$return = $return.",$mincount";
		}
	}
	return $return;
}
if ($action == 'gencrontab') {
	print $REL_LANG->_('This is /etc/crontab lines to add. Edit "/usr/bin/wget" corresponding to your wget location. <a href="%s">Back to cron admincp</a>.',$REL_SEO->make_link('cronadmin')).'<hr/><pre>';
	$mincl = floor($REL_CRON['autoclean_interval']/60);
	$minrm = floor($REL_CRON['remotecheck_interval']/60);
	print gen_cron_min($mincl).'	*	*	*	*	root	/usr/bin/wget -O /dev/null -q '.$REL_CONFIG['defaultbaseurl'].'/cleanup.php > /dev/null 2>&1
	'.gen_cron_min($minrm).'	*	*	*	*	root	/usr/bin/wget -O /dev/null -q '.$REL_CONFIG['defaultbaseurl'].'/remote_check.php > /dev/null 2>&1
	</pre>';
	$REL_TPL->stdfoot();
	die();
}
if (!isset($_POST['save']) && !isset($_POST['reset'])){

	$REL_TPL->begin_frame("��������� cron-�������");
	print('<form action="'.$REL_SEO->make_link('cronadmin').'" method="POST">');
	print('<table width="100%" border="1">');

	if ($REL_CRON['in_remotecheck'] && $REL_CRON['remotecheck_disabled']) $remotecheck_state .= '<font color="red">������ �� ��������� �����, �� ������ ��� �����������. ��������� ����������</font>';
	if (!$REL_CRON['in_remotecheck'] && $REL_CRON['remotecheck_disabled']) $remotecheck_state .= '<font color="green">������� �����������</font>';
	if ($REL_CRON['in_remotecheck'] && !$REL_CRON['remotecheck_disabled']) $remotecheck_state .= '<font color="green">������� ��������</font>';
	if (!$REL_CRON['in_remotecheck'] && !$REL_CRON['remotecheck_disabled']) $remotecheck_state .= '<font color="green">������� � ������ ��������</font>';
	if ($REL_CRON['cron_is_native']==0) $cron_warn = "<br/><font color=\"red\">You must edit /etc/crontab when changing this value. <a href=\"{$REL_SEO->make_link('cronadmin','a','gencrontab')}\">{$REL_LANG->_("Generate crontab entries")}</a>";
		print('<tr><td align="center" colspan="2" class="colhead">'.$REL_LANG->_('Scheduled jobs activation method').'</td></tr>');
	print ('<tr><td>'.$REL_LANG->_('Scheduled jobs activation method').':<br /><small>*'.$REL_LANG->_('You can use built-in functions or crontab. You must edit /etc/crontab corresponding your configuration.').'</small></td><td><select name="cron_is_native"><option value="1" '.($REL_CRON['cron_is_native']==1?"selected":"").'>'.$REL_LANG->_('Native').'</option><option value="0" '.($REL_CRON['cron_is_native']==0?"selected":"").'>'.$REL_LANG->_('crontab').'</option></select>'.($REL_CRON['cron_is_native']==0?" <a href=\"{$REL_SEO->make_link('cronadmin','a','gencrontab')}\">{$REL_LANG->_("Generate crontab entries")}</a>":"").'</td></tr>');
		print('<tr><td align="center" colspan="2" class="colhead">��������� ��������������� ����� | �� <a href="'.$REL_SEO->make_link('retrackeradmin').'">� ���������� ����������</a></td></tr>');
	print('<tr><td>��������� ������� ��������� ��������� �����:<br /><small>*��� ��� ��� ������� ����������� � ������� ������, �� �� ���������� ����� ������������� ��������� �����. ����� �� �������� ������� ������� ��������� ������.</small></td><td><select name="remotecheck_disabled"><option value="1" '.($REL_CRON['remotecheck_disabled']==1?"selected":"").'>��</option><option value="0" '.($REL_CRON['remotecheck_disabled']==0?"selected":"").'>���</option></select> '.$remotecheck_state .'</td></tr>');
	print('<tr><td>����� ������������ ��������� �����:<br /><small>*����� N ������ �������� �������� �� �������� ������.</small></td><td><input type="text" name="remotepeers_cleantime" size="3" value="'.$REL_CRON['remotepeers_cleantime'].'"> <b>������</b></td></tr>');
	print('<tr><td>������� �������� ��������� �� ���:<br/><small>*�� ������� ��������, ����� ��� torrentsbook.com, ���������� ���������� ���������� ����������� ��������. ��� <b>����</b> ����� ��������� ��� �������������� �������</small></td><td><input type="text" name="remote_trackers" size="5" value="'.$REL_CRON['remote_trackers'].'">��������</td></tr>');
	print('<tr><td>�������� ����� ����������:<br/><small>*��� ������� �������� ���������� ��������� ���� ��������. ��� <b>����</b> ������ ����� ����������� ���������'.$cron_warn.'</small></td><td><input type="text" name="remotecheck_interval" size="3" value="'.$REL_CRON['remotecheck_interval'].'">������</td></tr>');


	print('<tr><td align="center" colspan="2" class="colhead">��������� �������</td></tr>');

	print('<tr><td>���������� ����, �� ���������� ������� ��������� ���������������� ��������:</td><td><input type="text" name="signup_timeout" size="2" value="'.$REL_CRON['signup_timeout'].'">����</td></tr>');
	print('<tr><td>����� � ���, ����� ������� ������� ��������� �������:</td><td><input type="text" name="max_dead_torrent_time" size="3" value="'.$REL_CRON['max_dead_torrent_time'].'">������</td></tr>');
	print('<tr><td>����� ������� �� � ��������:'.($cron_warn?'<br/><small>'.$cron_warn.'</small>':'').'</td><td><input type="text" name="autoclean_interval" size="4" value="'.$REL_CRON['autoclean_interval'].'">������</td></tr>');
	print('<tr><td>���������� ���� ��� ������� ������ ��������� �� �������:</td><td><input type="text" name="pm_delete_sys_days" size="2" value="'.$REL_CRON['pm_delete_sys_days'].'">����</td></tr>');
	print('<tr><td>���������� ���� ��� ������� ������ ��������� �� ������������:</td><td><input type="text" name="pm_delete_user_days" size="2" value="'.$REL_CRON['pm_delete_user_days'].'">����</td></tr>');
	print('<tr><td>����� ����� �������� �������� � ����:</td><td><input type="text" name="ttl_days" size="3" value="'.$REL_CRON['ttl_days'].'">����</td></tr>');


	print('<tr><td align="center" colspan="2" class="colhead">��������� �������������� ����������� �������</td></tr>');
	print('<tr><td>����������� ������� ��������:<br /><small>*��� ����� �������� ������ <b>��������������</b> ��������� �������� �������� � �����������, ��������� � ���. ������������ � ����� ������ ������ ��������� �������� ���� �����, �� ��� ������ �� ����� ������ �� �� ���.</small></td><td><select name="rating_enabled"><option value="1" '.($REL_CRON['rating_enabled']==1?"selected":"").'>��</option><option value="0" '.($REL_CRON['rating_enabled']==0?"selected":"").'>���</option></select></td></tr>');
	print('<tr><td>�����, � ������� �������� ������������ ��������� �������� (����������� ������� �� ���� �� ���������):</td><td><input type="text" name="rating_freetime" size="2" value="'.$REL_CRON['rating_freetime'].'">����</td></tr>');
	print('<tr><td>�������� ����� ���������� �������� ��� �������������:</td><td><input type="text" name="rating_checktime" size="4" value="'.$REL_CRON['rating_checktime'].'">�����</td></tr>');
	print('<tr><td>'.$REL_LANG->_("Amount of rating to promote to power user").'</td><td><input type="text" size="3" name="promote_rating" value="'.$REL_CRON['promote_rating'].'"></td></tr>');	
	print('<tr><td>���������� ��������, �������� ������������ �� ������� ������:</td><td><input type="text" size="3" name="rating_perrelease" value="'.$REL_CRON['rating_perrelease'].'"></td></tr>');
	print('<tr><td>���������� ��������, �������� ������������ �� ����������� ������������� ������������:</td><td><input type="text" size="3" name="rating_perinvite" value="'.$REL_CRON['rating_perinvite'].'"></td></tr>');
	print('<tr><td>���������� ��������, �������� ������������ �� ���������� �������:</td><td><input type="text" size="3" name="rating_perrequest" value="'.$REL_CRON['rating_perrequest'].'"></td></tr>');
	print('<tr><td>���������� ��������, �������� ������������ �� �����������:<br /><small>*������ ������� ��� ����������� ������������ ������� � '.$REL_SEO->make_link('myrating').'</small></td><td><input type="text" size="3" name="rating_perseed" value="'.$REL_CRON['rating_perseed'].'"></td></tr>');
	print('<tr><td>���������� ��������, ���������� � ������������ �� ��������� ������:</td><td><input type="text" size="3" name="rating_perleech" value="'.$REL_CRON['rating_perleech'].'"></td></tr>');
	print('<tr><td>���������� ��������, ���������� � ������������ �� ���������� ������:</td><td><input type="text" size="3" name="rating_perdownload" value="'.$REL_CRON['rating_perdownload'].'"></td></tr>');
	print('<tr><td>����� ������� ���������� ���������:</td><td><input type="text" size="4" name="rating_downlimit" value="'.$REL_CRON['rating_downlimit'].'"></td></tr>');
	print('<tr><td>����� ���������� ��������:</td><td><input type="text" size="4" name="rating_dislimit" value="'.$REL_CRON['rating_dislimit'].'"></td></tr>');
	print('<tr><td>������������ ���������� ��������:</td><td><input type="text" size="4" name="rating_max" value="'.$REL_CRON['rating_max'].'"></td></tr>');
	print('<tr><td>������� ������ �������� ����� 1 ������� ������:</td><td><input type="text" size="2" name="rating_discounttorrent" value="'.$REL_CRON['rating_discounttorrent'].'"></td></tr>');


	print('<tr><td align="center" colspan="2" class="colhead">������ ���������</td></tr>');
	print('<tr><td>�������� �������� (���������� ���������� � ��������):</td><td><input type="text" size="5" name="announce_interval" value="'.$REL_CRON['announce_interval'].'">�����</td></tr>');
	print('<tr><td>�������� ������� ������ � ���������� �����/��������:<br /><small>*����� ���������� ������� ������������ ������ �������� ������� ��� ���.<br />*������ �������� �� ����� ���� ������ ��������� �������, ����������, ����� ��� ���� ������ ���.<br />*�������� ���� ������, ��� 0, ���� ������, ����� ������� ����������� ��������</td><td><input type="text" size="3" name="delete_votes" value="'.$REL_CRON['delete_votes'].'">�����</td></tr>');

	print('<tr><td align="center" colspan="2"><input type="submit" name="save" value="��������� ���������"><input type="reset" value="��������"><input type="submit" name="reset" value="�������� ���������� cron"></td></tr>
<tr><td colspan="2"><small>*����� ���������� cron ���������, ���� ������� ������� ���������� ��������� cron-�������, ������� �� ����������� �������� cron ������ ����� <a href="http://httpd.apache.org/docs/2.0/mod/mod_status.html">mod_status</a> ��� apache</small></td></tr></table></form>');
	$REL_TPL->end_frame();
}
elseif (isset($_POST['reset'])) {
	sql_query("UPDATE cron SET cron_value=0 WHERE cron_name IN ('last_cleanup','last_remotecheck','in_cleanup','in_remotecheck','num_cleaned','num_checked')");
	stdmsg($REL_LANG->say_by_key('success'),$REL_LANG->say_by_key('cron_state_reseted'));
}
elseif (isset($_POST['save'])) {

	$reqparametres = array('cron_is_native','max_dead_torrent_time','signup_timeout','autoclean_interval','pm_delete_sys_days','pm_delete_user_days','ttl_days','remotecheck_disabled','announce_interval','delete_votes','remote_trackers','rating_enabled','remotecheck_interval');

	$multi_param = array('remotepeers_cleantime');

	$rating_param = array('rating_freetime','promote_rating','rating_perseed','rating_perinvite','rating_perrequest','rating_checktime','rating_perrelease','rating_dislimit','rating_downlimit', 'rating_perleech', 'rating_perdownload', 'rating_discounttorrent','rating_max');
	$updateset = array();

	foreach ($reqparametres as $param) {
		if (!isset($_POST[$param]) && (($param != 'rating_enabled') || ($param != 'delete_votes') || ($param != 'remote_trackers')))  { stdmsg($REL_LANG->say_by_key('error'),"��������� ���� �� ��������� ($param)",'error'); $REL_TPL->stdfoot(); die; }
		$updateset[] = "UPDATE cron SET cron_value=".sqlesc($_POST[$param])." WHERE cron_name='$param'";
	}

	if ($_POST['remotecheck_disabled'] == 0) {
		foreach ($multi_param as $param) {
			if (!$_POST[$param] || !isset($_POST[$param])) { stdmsg($REL_LANG->say_by_key('error'),"��������� ���� ��� ����������������� �� ���������",'error'); $REL_TPL->stdfoot(); die; }
			$updateset[] = "UPDATE cron SET cron_value=".sqlesc($_POST[$param])." WHERE cron_name='$param'";
		}
	}

	if ($_POST['rating_enabled']) {
		foreach ($rating_param as $param) {
			if (!$_POST[$param] || !isset($_POST[$param])) { stdmsg($REL_LANG->say_by_key('error'),"��������� ���� ��� ����������� ������� �� ���������",'error'); $REL_TPL->stdfoot(); die; }
			$updateset[] = "UPDATE cron SET cron_value=".sqlesc($_POST[$param])." WHERE cron_name='$param'";
		}
	}

	foreach ($updateset as $query) sql_query($query);
	safe_redirect($REL_SEO->make_link('cronadmin'),3);
	stdmsg($REL_LANG->say_by_key('success'),$REL_LANG->say_by_key('cron_settings_saved'));
}
$REL_TPL->begin_frame('������� ��������� cron:');
print ('<table width="100%"><tr><td>');
if (!$REL_CRON['in_cleanup']) print $REL_LANG->say_by_key('cleanup_not_running').'<br />';
if (!$REL_CRON['in_remotecheck']) print $REL_LANG->say_by_key('remotecheck_not_running').'<br />';
print sprintf($REL_LANG->say_by_key('num_cleaned'),$REL_CRON['num_cleaned'])."<br />";
print sprintf($REL_LANG->say_by_key('num_checked'),$REL_CRON['num_checked'])."<br />";
print $REL_LANG->say_by_key('last_cleanup').' '.mkprettytime($REL_CRON['last_cleanup'],true,true)." (".get_elapsed_time($REL_CRON['last_cleanup'])." {$REL_LANG->say_by_key('ago')})<br />";
print $REL_LANG->say_by_key('last_remotecheck').' '.mkprettytime($REL_CRON['last_remotecheck'],true,true)." (".get_elapsed_time($REL_CRON['last_remotecheck'])." {$REL_LANG->say_by_key('ago')})<br />";
print ('</td></tr></table>');
$REL_TPL->end_frame();
$REL_TPL->stdfoot();

?>