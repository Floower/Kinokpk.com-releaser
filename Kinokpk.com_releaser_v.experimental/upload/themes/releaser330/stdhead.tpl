<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251" />
<meta name="Description" content="{$REL_CONFIG.description}{$descradd}" />
<meta name="Keywords" content="{$keywordsadd}{$REL_CONFIG.keywords}" />
<base href="{$REL_CONFIG.defaultbaseurl}/" />
<!--���� ������ �������� ��������� HTML? ������ ��� � PHP/MySQL? �������� � �������, ��������� ��� ���� ���� �������� � ����� ������� http://www.kinokpk.com/staff.php -->
<title>{$title}</title>
<link rel="stylesheet" href="themes/{$REL_CONFIG.ss_uri}/{$REL_CONFIG.ss_uri}.css" type="text/css"/>
<link rel="stylesheet" href="css/features.css" type="text/css"/>
<link rel="stylesheet" href="css/jquery.jgrowl.css" type="text/css"/>
<link rel="stylesheet" href="css/jquery.facebox.css" type="text/css"/>
<link rel="stylesheet" href="css/link/jquery.linkselect.style.select.css" type="text/css"/>
<!--[if IE]>
<link rel="stylesheet" href="css/features_ie.css" type="text/css"/>
<![endif]-->
<link rel="alternate" type="application/rss+xml" title="RSS" href="{$REL_SEO->make_link('rss')}" />
<link rel="alternate" type="application/atom+xml" title="Atom" href="{$REL_SEO->make_link('atom')}" />
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
<script language="javascript" type="text/javascript" src="js/resizer.js"></script>
{if !$CURUSER || $CURUSER.extra_ef}
<!--<script language="javascript" type="text/javascript" src="js/snow.js"></script>-->
{/if}
<script language="javascript" type="text/javascript" src="js/jquery.js"></script>
<script language="javascript" type="text/javascript" src="js/jquery.history.js"></script>
<script language="javascript" type="text/javascript" src="js/jquery.cookie.js"></script>
<script language="javascript" type="text/javascript" src="js/facebox.min.js"></script>
<script language="javascript" type="text/javascript" src="js/jquery.jgrowl_minimized.js"></script>
<script language="javascript" type="text/javascript" src="js/coding.js"></script>
<script language="javascript" type="text/javascript" src="js/features.js"></script>
<script language="javascript" type="text/javascript" src="js/swfobject.js"></script>
<script language="javascript" type="text/javascript" src="js/paginator3000.js"></script>
<script language="javascript" type="text/javascript" src="js/jquery.utils.js"></script>
<script language="javascript" type="text/javascript" src="js/blocks.js"></script>
<!--���������� ����-->
<script language="javascript" type="text/javascript" src="themes/{$REL_CONFIG.ss_uri}/js/topmenu.js"></script>
{$headadd}
<!--[if lte IE 7]>
<font color="red" size="5">���� ������������ ���� ������, %username%! ������, �� ����������� Internet Explorer, ��� ������� �� �������� ��� � �������� ����. ��, ���� ������, %username%, ���������� ���� <a href="http://ie.yandex.ru/">���������� �� ����� ������ ������</a>, ���� �������� ���������� ������������ �� ������������� ����� ����������� � ������ �����. �������!</font>
<![endif]-->

<!-- Theme as it is -->

</head>
<body>
{if $OFFLINE} <h1>Site offline!</h1>{/if}

<table border="0" class="index">
  <tr>
    <td valign="top" class="itd"><div class="tl"></div></td>
    <td valign="top" class="itdc"><div class="index2">
        <div class="header1">
          <div class="header3">
            <div class="header2">
              <div class="logo"><a href="index.php"><img src="themes/releaser330/images/spacer.gif" height="60" width="217" border="0" alt="�������" /></a></div>
              <div class="cp"></div>
              <div class="banner"><a href="contact.php"><img src="http://dev.kinokpk.com/images/b468_dev.jpg" border="0" alt="��" /></a></div>
            </div>
          </div>
        </div>
        <!--����-->
        <div class="tmenur">
          <ul class="topmenu" id="topmenu">
            <li><a href="index.php">�������</a></li>
            <li><a rel="tmlnk3" href="browse.php">��������</a></li>
            <li><a rel="tmlnk4" href="rules.php">����</a></li>
            {if !$CURUSER}
            <li><a href="login.php">����</a></li>
            {else}
            	<li><a rel="tmlnk5"  href="{$REL_SEO->make_link('mynotifs')}">{$REL_LANG->_("Notifications")}{if $REL_NOTIFS.total} ({$REL_NOTIFS.total}){/if}</a></li>    
            {/if}
            <li><a rel="tmlnk1" href="my.php">������������</a></li>
            <li><a rel="tmlnk2" href="staff.php">�������</a></li>
          </ul>
        </div>
        <div id="tmlnk1" class="submenu">
          <ul class="reset">
            <li><a href="my.php"><span>��������� ��������</span></a></li>
            <li><a href="bookmarks.php"><span>��������</span></a></li>
            <li><a href="myrating.php"><span>��� �������</span></a></li>
            <li><a href="present.php"><span>������� �������</span></a></li>
            <li><a href="mytorrents.php"><span>��� ��������</span></a></li>
            <li><a href="subnet.php"><span>������</span></a></li>
            <li><a href="friends.php"><span>��� ������</span></a></li>
            <li><a href="invite.php"><span>����������</span></a></li>
            <li><a href="users.php"><span>������������</span></a></li>
            <li><a href="logout.php"><span>�����!</span></a></li>
          </ul>
        </div>
        <div id="tmlnk2" class="submenu">
          <ul class="reset">
            <li><a href="admincp.php"><span>�������</span></a></li>
            <li><a href="online.php"><span>�� �� ������?!</span></a></li>
            <li><a href="viewreport.php"><span>������ �� ��������</span></a></li>
            <li><a href="staffmess.php"><span>�������� ��</span></a></li>
            <li><a href="ipcheck.php"><span>�������� �� IP</span></a></li>
            <li><a href="setclass.php"><span>������� �����</span></a></li>
            <li><a href="clearcache.php"><span>�������� ���</span></a></li>
          </ul>
        </div>
        <div id="tmlnk3" class="submenu">
          <ul class="reset">
            <li><a href="upload.php"><span>��������� �����</span></a></li>
            <li><a href="relgroups.php"><span>�����-������</span></a></li>
          </ul>
        </div>
        <div id="tmlnk4" class="submenu">
          <ul class="reset">
            <li><a href="rules.php"><span>�������</span></a></li>
            <li><a href="faq.php"><span>����</span></a></li>
            <li><a href="pagebrowse.php"><span>��������</span></a></li>
            <li><a href="topten.php"><span>��� 10</span></a></li>
          </ul>
        </div>
                <div id="tmlnk5" class="submenu">
          <ul class="reset">
	{foreach from=$REL_NOTIFS.notifs key=notify item=ncount}
		<li><a href="{if $notify<>'unread'}{$REL_SEO->make_link('mynotifs','type',$notify)}{else}{$REL_SEO->make_link('message')}{/if}">{$REL_LANG->_(ucfirst($notify))}: {$ncount}</a></li>
		{/foreach}
          </ul>
        </div>
        <script type="text/javascript">cssdropdown.startchrome("topmenu")</script>
        <!--/����-->
        <div class="search" align="right">
          <form action="browse.php" method="get" name="search">
            <input class="search_input" type="text" value="&nbsp;�����..." size="15" alt="�����" maxlength="40" name="search" />
            <div class="search_bottom">
              <input value="�����" type="image" src="themes/releaser330/images/spacer.gif" style="height:25px; width:30px; border:0px;" />
            </div>
          </form>
        </div>
        <div class="b4">
          <div class="b6">
            <div class="b5">
              <div class="cont">
                <table width="100%" border="0" class="bottom" style="vertical-align:top;">
                  <tr>
                    <td class="bottom" width="200" valign="top">
                    {show_blocks('l')}                    
                    </td>
                    <td valign="top">
                    {show_blocks('t')}