<?php

global $REL_CACHE, $REL_SEO;

if (!defined('BLOCK_FILE')) {
	safe_redirect(" ../".$REL_SEO->make_link('index'));
	exit;
}

$content .= "<table  width=\"100%\"><tr><td  valign=\"top\" align=\"center\">";

$content .= "<small>[<a href=\"".$REL_SEO->make_link('viewrequests')."\">��</a>] [<a href=\"".$REL_SEO->make_link('requests','action','new')."\">��������</a>]</small><hr /><table border=\"1\"><tr><td align=\"center\">";

$reqarray = $REL_CACHE->get('block-req', 'query');

if ($reqarray===false) {

	$reqarray = array();
	$req=sql_query("SELECT requests.* FROM requests INNER JOIN categories ON requests.cat = categories.id WHERE requests.filled = '' ORDER BY added DESC LIMIT 3");

	while ($reqres = @mysql_fetch_array($req))
	$reqarray[]=$reqres;

	$REL_CACHE->set('block-req', 'query', $reqarray);
}

if (!$reqarray) {$content .= '<b>��� ��������</b>'; } else
foreach ($reqarray as $requests) {
	if ($requests[filledby]!=0) {
		$done = "<a href=".$REL_SEO->make_link(addslashes($requests['filled']))."><img border=\"0\" src=\"pic/chk.gif\" alt=\"��������\"/></a>";
	}
	else {
		$done = "";
	}

	$content .= "<a href=\"".$REL_SEO->make_link('requests','id',$requests['id'])."\"><b>$requests[request]</b></a>&nbsp;&nbsp;&nbsp;$done<br /><small> [����:  $requests[comments], �����������: $requests[hits]]<br /><a href=\"".$REL_SEO->make_link('requests','action','vote','voteid',$requests['id'])."\">�������������� � �������</a></small><br />";

}

$content .= "</td></tr></table></td></tr></table>";

?>