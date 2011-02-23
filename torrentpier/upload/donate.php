<?php

/*
	This file is part of TorrentPier

	TorrentPier is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	TorrentPier is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	A copy of the GPL 2.0 should have been included with the program.
	If not, see http://www.gnu.org/licenses/

	Official SVN repository and contact information can be found at
	http://code.google.com/p/torrentpier/
 */

define('IN_PHPBB', true);
define('BB_SCRIPT', 'donate');
define('BB_ROOT', './');
$phpEx = substr(strrchr(__FILE__, '.'), 1);
require(BB_ROOT ."common.$phpEx");

// Start session management
$user->session_start();

$l_title = 'Помощь трекеру';

//include($phpbb_root_path . 'language/lang_' . $board_config['default_lang'] . '/' . $lang_file . '.' . $phpEx);
//include("{$phpbb_root_path}language/lang_{$board_config['default_lang']}/lang_faq_attach.$phpEx");

//
// Lets build a page ...
//
$template->assign_vars(array(
	'PAGE_TITLE' => $l_title,
	'L_DONATE_TITLE' => $l_title,
));

print_page('donate.tpl');


