<?php

if (!defined('BB_ROOT')) die(basename(__FILE__));

require_once(INC_DIR .'functions_admin.php');

if ($bb_cfg['prune_enable'])
{
	$sql = "SELECT forum_id, prune_days FROM ". FORUMS_TABLE ." WHERE prune_days != 0";

	foreach ($db->fetch_rowset($sql) as $row)
	{
		topic_delete('prune', $row['forum_id'], (TIMENOW - 86400*$row['prune_days']));
	}
}