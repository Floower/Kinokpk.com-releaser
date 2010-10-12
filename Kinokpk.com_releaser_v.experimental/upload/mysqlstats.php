<?

/*
 Project: Kinokpk.com releaser
 This file is part of Kinokpk.com releaser.
 Kinokpk.com releaser is based on TBDev,
 originally by RedBeard of TorrentBits, extensively modified by
 Gartenzwerg and Yuna Scatari.
 Kinokpk.com releaser is free software;
 you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.
 Kinokpk.com is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 You should have received a copy of the GNU General Public License
 along with Kinokpk.com releaser; if not, write to the
 Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston,
 MA  02111-1307  USA
 Do not remove above lines!
 */

// TRANSLATON BY 7Max7

require "include/bittorrent.php";
dbconn();
loggedinorreturn();
httpauth();

if (get_user_class() < UC_ADMINISTRATOR)
stderr("������", "������ ��������.");

$GLOBALS["byteUnits"] = array('����', '��', '��', '��', '��', '��', 'E�');

$day_of_week = array('�����������', '�����������', '�������', '�����', '�������', '�������', '�������');
$month = array('������', '�������', '�����', '������', '���', '����', '����', '�������', '��������', '�������', '������', '�������');

$datefmt = '%d %B, %Y � %I:%M %p';
$timespanfmt = '%s ����, %s �����, %s ����� %s ������';
////////////////// FUNCTION LIST /////////////////////////

function formatByteDown($value, $limes = 6, $comma = 0)
{
	$dh           = pow(10, $comma);
	$li           = pow(10, $limes);
	$return_value = $value;
	$unit         = $GLOBALS['byteUnits'][0];

	for ( $d = 6, $ex = 15; $d >= 1; $d--, $ex-=3 ) {
		if (isset($GLOBALS['byteUnits'][$d]) && $value >= $li * pow(10, $ex)) {
			$value = round($value / ( pow(1024, $d) / $dh) ) /$dh;
			$unit = $GLOBALS['byteUnits'][$d];
			break 1;
		} // end if
	} // end for

	if ($unit != $GLOBALS['byteUnits'][0]) {
		$return_value = number_format($value, $comma, '.', ',');
	} else {
		$return_value = number_format($value, 0, '.', ',');
	}

	return array($return_value, $unit);
} // end of the 'formatByteDown' function


function timespanFormat($seconds)
{
	$return_string = '';
	$days = floor($seconds / 86400);
	if ($days > 0) {
		$seconds -= $days * 86400;
	}
	$hours = floor($seconds / 3600);
	if ($days > 0 || $hours > 0) {
		$seconds -= $hours * 3600;
	}
	$minutes = floor($seconds / 60);
	if ($days > 0 || $hours > 0 || $minutes > 0) {
		$seconds -= $minutes * 60;
	}
	return (string)$days." ���� ". (string)$hours." ����� ". (string)$minutes." ����� ". (string)$seconds." ������ ";
}


function localisedDate($timestamp = -1, $format = '')
{
	global $datefmt, $month, $day_of_week;

	if ($format == '') {
		$format = $datefmt;
	}

	if ($timestamp == -1) {
		$timestamp = time();
	}

	$date = preg_replace('@%[aA]@', $day_of_week[(int)strftime('%w', $timestamp)], $format);
	$date = preg_replace('@%[bB]@', $month[(int)strftime('%m', $timestamp)-1], $date);

	return strftime($date, $timestamp);
} // end of the 'localisedDate()' function

////////////////////// END FUNCTION LIST /////////////////////////////////////


$REL_TPL->stdhead("��������� Mysql");
echo '<h2>'."\n".'������ ���� ������ (MYSQL)'."\n".'</h2><br />'."\n";

$res = @sql_query('SHOW STATUS') or Die(mysql_error());
while ($row = mysql_fetch_row($res)) {
	$serverStatus[$row[0]] = $row[1];
}
@mysql_free_result($res);
unset($res);
unset($row);

// ������� �������
$res = @sql_query('SELECT UNIX_TIMESTAMP() - ' . $serverStatus['Uptime']);
$row = mysql_fetch_row($res);
//echo sprintf("Server Status Uptime", timespanFormat($serverStatus['Uptime']), localisedDate($row[0])) . "\n";
?>

<table id="torrenttable" border="1">
	<tr>
		<td><?
		print("Mysql �������� ". timespanFormat($serverStatus['Uptime']) .". ��� ������� ". localisedDate($row[0])) . "\n";

		$dbname = $mysql_db;

		$result = sql_query("SHOW TABLES FROM ".$dbname."");
		$content = "";
		while (list($name) = mysql_fetch_array($result)) $content .= "<option value=\"".$name."\" selected>".$name."</option>";
		echo "<table border=\"0\" cellspacing=\"0\" cellpadding=\"3\" align=\"center\">"
		."<form method=\"post\" action=\"".$REL_SEO->make_link('mysqlstats')."\">"
		."<tr><td><select name=\"datatable[]\" size=\"10\" multiple=\"multiple\" style=\"width:400px\">".$content."</select></td><td>"
		."<table border=\"0\" cellspacing=\"0\" cellpadding=\"3\">"
		."<tr><td valign=\"top\"><input type=\"radio\" name=\"type\" value=\"Optimize\" checked></td><td>����������� ���� ������<br /><font class=\"small\">��������� ����������� ���� ������, �� ���������� � ������ � �������������� � ���� ��������� � ������. ������������� ������������ ������ ������� ������� ���� ��� � ������.</font></td></tr>"
		."<tr><td valign=\"top\"><input type=\"radio\" name=\"type\" value=\"Repair\"></td><td>������ ���� ������<br /><font class=\"small\">��� ����������� ��������� MySQL �������, �� ����� ���������� �����-���� ��������, ����� ��������� ����������� ��������� ������ ���� ������, ������������� ���� ������� ��������� ������ ����������� ������.</font></td></tr></table>"
		."</td></tr>"
		."<input type=\"hidden\" name=\"op\" value=\"StatusDB\">"
		."<tr><td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"��������� ��������\"></td></tr></form></table>";

		if ($_POST['type'] == "Optimize") {
			$result = sql_query("SHOW TABLE STATUS FROM ".$dbname."");
			$tables = array();
			while ($row = mysql_fetch_array($result)) {
				$total = $row['Data_length'] + $row['Index_length'];
				$totaltotal += $total;
				$free = ($row['Data_free']) ? $row['Data_free'] : 0;
				$totalfree += $free;
				$i++;
				$otitle = (!$free) ? "<font color=\"#FF0000\">�� ���������</font>" : "<font color=\"#009900\">��������������</font>";
				//sql_query("OPTIMIZE TABLE ".$row[0]."");
				$tables[] = $row[0];
				$content3 .= "<tr class=\"bgcolor1\"><td align=\"center\">".$i."</td><td>".$row[0]."</td><td>".mksize($total)."</td><td align=\"center\">".$otitle."</td><td align=\"center\">".mksize($free)."</td></tr>";
			}
			sql_query("OPTIMIZE TABLE ".implode(", ", $tables));
			echo "<center><font class=\"option\">����������� ���� ������: ".$dbname."<br />����� ������ ���� ������: ".mksize($totaltotal)."<br />����� ��������� �������: ".mksize($totalfree)."<br /><br />"
			."<table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\"><tr><td class=\"colhead\" align=\"center\">�</td><td class=\"colhead\">�������</td><td class=\"colhead\">������</td><td class=\"colhead\">������</td><td class=\"colhead\">��������� �������</td></tr>"
			."".$content3."</table>";
		} elseif ($_POST['type'] == "Repair") {
			$result = sql_query("SHOW TABLE STATUS FROM ".$dbname."");
			while ($row = mysql_fetch_array($result)) {
				$total = $row['Data_length'] + $row['Index_length'];
				$totaltotal += $total;
				$i++;
				$rresult = sql_query("REPAIR TABLE ".$row[0]."");
				$otitle = (!$rresult) ? "<font color=\"#FF0000\">������</font>" : "<font color=\"#009900\">OK</font>";
				$content4 .= "<tr class=\"bgcolor1\"><td align=\"center\">".$i."</td><td>".$row[0]."</td><td>".mksize($total)."</td><td align=\"center\">".$otitle."</td></tr>";
			}
			echo "<center><font class=\"option\">������ ���� ������: ".$dbname."<br />����� ������ ���� ������: ".mksize($totaltotal)."<br /><br />"
			."<table border=\"0\" cellpadding=\"3\" cellspacing=\"1\" width=\"100%\"><tr><td class=\"colhead\" align=\"center\">�</td><td class=\"colhead\">�������</td><td class=\"colhead\">������</td><td class=\"colhead\">������</td></tr>"
			."".$content4."</table>";
		}


		?></td>
	</tr>
</table>

		<?
		mysql_free_result($res);
		unset($res);
		unset($row);
		//����� ���������� �������� N01heDc=
		$queryStats = array();
		$tmp_array = $serverStatus;
		foreach($tmp_array AS $name => $value) {
			if (substr($name, 0, 4) == 'Com_') {
				$queryStats[str_replace('_', ' ', substr($name, 4))] = $value;
				unset($serverStatus[$name]);
			}
		}
		unset($tmp_array);
		?>

<ul>
	<li><!-- ������ ������� --> <b>������ �������: </b> ����� ������
	�������� ������� � ������� ���������� ������� <br />
	<table border="0">
		<tr>
			<td valign="top">
			<table id="torrenttable" border="0">
				<tr>
					<th colspan="2" bgcolor="lightgrey">&nbsp;������&nbsp;</th>
					<th bgcolor="lightgrey">&nbsp;&nbsp;�� ���&nbsp;</th>
				</tr>
				<tr>
					<td bgcolor="#EFF3FF">&nbsp;���������&nbsp;</td>
					<td bgcolor="#EFF3FF" align="right">&nbsp;<? echo join(' ', formatByteDown($serverStatus['Bytes_received'])); ?>&nbsp;</td>
					<td bgcolor="#EFF3FF" align="right">&nbsp;<? echo join(' ', formatByteDown($serverStatus['Bytes_received'] * 3600 / $serverStatus['Uptime'])); ?>&nbsp;</td>
				</tr>
				<tr>
					<td bgcolor="#EFF3FF">&nbsp;�������&nbsp;</td>
					<td bgcolor="#EFF3FF" align="right">&nbsp;<? echo join(' ', formatByteDown($serverStatus['Bytes_sent'])); ?>&nbsp;</td>
					<td bgcolor="#EFF3FF" align="right">&nbsp;<? echo join(' ', formatByteDown($serverStatus['Bytes_sent'] * 3600 / $serverStatus['Uptime'])); ?>&nbsp;</td>
				</tr>
				<tr>
					<td bgcolor="lightgrey">&nbsp;�����&nbsp;</td>
					<td bgcolor="lightgrey" align="right">&nbsp;<? echo join(' ', formatByteDown($serverStatus['Bytes_received'] + $serverStatus['Bytes_sent'])); ?>&nbsp;</td>
					<td bgcolor="lightgrey" align="right">&nbsp;<? echo join(' ', formatByteDown(($serverStatus['Bytes_received'] + $serverStatus['Bytes_sent']) * 3600 / $serverStatus['Uptime'])); ?>&nbsp;</td>
				</tr>
			</table>
			</td>
			<td valign="top">
			<table id="torrenttable" border="0">
				<tr>
					<th colspan="2" bgcolor="lightgrey">&nbsp;����������&nbsp;</th>
					<th bgcolor="lightgrey">&nbsp;&oslash;&nbsp;�� ���&nbsp;</th>
					<th bgcolor="lightgrey">&nbsp;%&nbsp;</th>
				</tr>
				<tr>
					<td bgcolor="#EFF3FF">&nbsp;����������� �������&nbsp;</td>
					<td bgcolor="#EFF3FF" align="right">&nbsp;<? echo number_format($serverStatus['Aborted_connects'], 0, '.', ','); ?>&nbsp;</td>
					<td bgcolor="#EFF3FF" align="right">&nbsp;<? echo number_format(($serverStatus['Aborted_connects'] * 3600 / $serverStatus['Uptime']), 2, '.', ','); ?>&nbsp;</td>
					<td bgcolor="#EFF3FF" align="right">&nbsp;<? echo ($serverStatus['Connections'] > 0 ) ? number_format(($serverStatus['Aborted_connects'] * 100 / $serverStatus['Connections']), 2, '.', ',') . '&nbsp;%' : '---'; ?>&nbsp;</td>
				</tr>
				<tr>
					<td bgcolor="#EFF3FF">&nbsp;�������� ���������&nbsp;</td>
					<td bgcolor="#EFF3FF" align="right">&nbsp;<? echo number_format($serverStatus['Aborted_clients'], 0, '.', ','); ?>&nbsp;</td>
					<td bgcolor="#EFF3FF" align="right">&nbsp;<? echo number_format(($serverStatus['Aborted_clients'] * 3600 / $serverStatus['Uptime']), 2, '.', ','); ?>&nbsp;</td>
					<td bgcolor="#EFF3FF" align="right">&nbsp;<? echo ($serverStatus['Connections'] > 0 ) ? number_format(($serverStatus['Aborted_clients'] * 100 / $serverStatus['Connections']), 2 , '.', ',') . '&nbsp;%' : '---'; ?>&nbsp;</td>
				</tr>
				<tr>
					<td bgcolor="lightgrey">&nbsp;�����&nbsp;</td>
					<td bgcolor="lightgrey" align="right">&nbsp;<? echo number_format($serverStatus['Connections'], 0, '.', ','); ?>&nbsp;</td>
					<td bgcolor="lightgrey" align="right">&nbsp;<? echo number_format(($serverStatus['Connections'] * 3600 / $serverStatus['Uptime']), 2, '.', ','); ?>&nbsp;</td>
					<td bgcolor="lightgrey" align="right">&nbsp;<? echo number_format(100, 2, '.', ','); ?>&nbsp;%&nbsp;</td>
				</tr>
			</table>
			</td>
		</tr>
	</table>
	</li>
	<br />
	<li><!-- ������� --> <? print("<b>���������� ��������: </b> � ������� ������� - ". number_format($serverStatus['Questions'], 0, '.', ',')." �������� ���� �������� �� ������.\n"); ?>
	<table border="0">
		<tr>
			<td colspan="2"><br />
			<table id="torrenttable" border="0" align="right">
				<tr>
					<th bgcolor="lightgrey">&nbsp;�����&nbsp;</th>
					<th bgcolor="lightgrey">&nbsp;&oslash;&nbsp;��&nbsp;���&nbsp;</th>
					<th bgcolor="lightgrey">&nbsp;&oslash;&nbsp;��&nbsp;�����&nbsp;</th>
					<th bgcolor="lightgrey">&nbsp;&oslash;&nbsp;��&nbsp;������&nbsp;</th>
				</tr>
				<tr>
					<td bgcolor="#EFF3FF" align="right">&nbsp;<? echo number_format($serverStatus['Questions'], 0, '.', ','); ?>&nbsp;</td>
					<td bgcolor="#EFF3FF" align="right">&nbsp;<? echo number_format(($serverStatus['Questions'] * 3600 / $serverStatus['Uptime']), 2, '.', ','); ?>&nbsp;</td>
					<td bgcolor="#EFF3FF" align="right">&nbsp;<? echo number_format(($serverStatus['Questions'] * 60 / $serverStatus['Uptime']), 2, '.', ','); ?>&nbsp;</td>
					<td bgcolor="#EFF3FF" align="right">&nbsp;<? echo number_format(($serverStatus['Questions'] / $serverStatus['Uptime']), 2, '.', ','); ?>&nbsp;</td>
				</tr>
			</table>
			</td>
		</tr>
		<tr>
			<td valign="top">
			<table id="torrenttable" border="0">
				<tr>
					<th colspan="2" bgcolor="lightgrey">&nbsp;���&nbsp;�������&nbsp;</th>
					<th bgcolor="lightgrey">&nbsp;&oslash;&nbsp;��&nbsp;���&nbsp;</th>
					<th bgcolor="lightgrey">&nbsp;%&nbsp;</th>
				</tr>
				<?

				$useBgcolorOne = TRUE;
				$countRows = 0;
				foreach ($queryStats as $name => $value) {


					?>
				<tr>
					<td bgcolor="#EFF3FF">&nbsp;<? echo htmlspecialchars($name); ?>&nbsp;</td>
					<td bgcolor="#EFF3FF" align="right">&nbsp;<? echo number_format($value, 0, '.', ','); ?>&nbsp;</td>
					<td bgcolor="#EFF3FF" align="right">&nbsp;<? echo number_format(($value * 3600 / $serverStatus['Uptime']), 2, '.', ','); ?>&nbsp;</td>
					<td bgcolor="#EFF3FF" align="right">&nbsp;<? echo number_format(($value * 100 / ($serverStatus['Questions'] - $serverStatus['Connections'])), 2, '.', ','); ?>&nbsp;%&nbsp;</td>
				</tr>
				<?
				$useBgcolorOne = !$useBgcolorOne;
				if (++$countRows == ceil(count($queryStats) / 2)) {
					$useBgcolorOne = TRUE;
					?>
			</table>
			</td>
			<td valign="top">
			<table id="torrenttable" border="0">
				<tr>
					<th colspan="2" bgcolor="lightgrey">&nbsp;���&nbsp;�������&nbsp;</th>
					<th bgcolor="lightgrey">&nbsp;&oslash;&nbsp;��&nbsp;���&nbsp;</th>
					<th bgcolor="lightgrey">&nbsp;%&nbsp;</th>
				</tr>
				<?
				}
				}
				unset($countRows);
				unset($useBgcolorOne);
				?>
			</table>
			</td>
		</tr>
	</table>
	</li>
	<?
	//Unset used variables
	unset($serverStatus['Aborted_clients']);
	unset($serverStatus['Aborted_connects']);
	unset($serverStatus['Bytes_received']);
	unset($serverStatus['Bytes_sent']);
	unset($serverStatus['Connections']);
	unset($serverStatus['Questions']);
	unset($serverStatus['Uptime']);

	if (!empty($serverStatus)) {
		?>
	<br />
	<li><b>������ ��������</b><br />
	<table border="0">
		<tr>
			<td valign="top">
			<table id="torrenttable" border="0">
				<tr>
					<th bgcolor="lightgrey">&nbsp;�������&nbsp;</th>
					<th bgcolor="lightgrey">&nbsp;��������&nbsp;</th>
				</tr>
				<?  $useBgcolorOne = TRUE;   $countRows = 0; foreach($serverStatus AS $name => $value) { ?>
				<tr>
					<td bgcolor="#EFF3FF">&nbsp;<? echo htmlspecialchars(str_replace('_', ' ', $name)); ?>&nbsp;</td>
					<td bgcolor="#EFF3FF" align="right">&nbsp;<? echo htmlspecialchars($value); ?>&nbsp;</td>
				</tr>
				<?
				$useBgcolorOne = !$useBgcolorOne;
				if (++$countRows == ceil(count($serverStatus) / 3) || $countRows == ceil(count($serverStatus) * 2 / 3)) {
					$useBgcolorOne = TRUE;
					?>
			</table>
			</td>
			<td valign="top">
			<table id="torrenttable" border="0">
				<tr>
					<th bgcolor="lightgrey">&nbsp;�������&nbsp;</th>
					<th bgcolor="lightgrey">&nbsp;��������&nbsp;</th>
				</tr>
				<? } } unset($useBgcolorOne); ?>
			</table>
			</td>
		</tr>
	</table>
	</li>
	<? } ?>
</ul>
	<? $REL_TPL->stdfoot(); ?>