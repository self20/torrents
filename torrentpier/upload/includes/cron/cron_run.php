<?php

if (!defined('BB_ROOT')) die(basename(__FILE__));

define('IN_CRON', true);

// Set SESSION vars
$db->query("
	SET SESSION
	  myisam_sort_buffer_size = 16*1024*1024
	, bulk_insert_buffer_size =  8*1024*1024
	, join_buffer_size        =  4*1024*1024
	, read_buffer_size        =  4*1024*1024
	, read_rnd_buffer_size    =  8*1024*1024
	, sort_buffer_size        =  4*1024*1024
	, tmp_table_size          = 80*1024*1024
	, group_concat_max_len    =  1*1024*1024
");

// Restore vars at shutdown
$db->add_shutdown_query("
	SET SESSION
	  myisam_sort_buffer_size = DEFAULT
	, bulk_insert_buffer_size = DEFAULT
	, join_buffer_size        = DEFAULT
	, read_buffer_size        = DEFAULT
	, read_rnd_buffer_size    = DEFAULT
	, sort_buffer_size        = DEFAULT
	, tmp_table_size          = DEFAULT
	, group_concat_max_len    = DEFAULT
");

// $cron_jobs obtained in cron_check.php
foreach ($cron_jobs as $job)
{
	$job_script = CRON_JOB_DIR . basename($job['cron_script']);

	if (file_exists($job_script))
	{
		$cron_start_time   = utime();
		$cron_runtime_log  = '';
		$cron_write_log    = (CRON_LOG_ENABLED && (CRON_FORCE_LOG || $job['log_enabled'] >= 1));
		$cron_sql_log_file = CRON_LOG_DIR .'SQL-'. basename($job['cron_script']);

		if ($cron_write_log)
		{
			$msg = array();
			$msg[] = 'start';
			$msg[] = date('m-d');
			$msg[] = date('H:i:s');
			$msg[] = sprintf('%-4s', round(get_loadavg(), 1));
			$msg[] = sprintf('%05d', getmypid());
			$msg[] = $job['cron_title'];
			$msg = join(LOG_SEPR, $msg);
			bb_log($msg . LOG_LF, CRON_LOG_DIR . CRON_LOG_FILE);
		}

		if ($job['log_sql_queries'])
		{
			$db->log_next_query(100000, $cron_sql_log_file);
		}

		@set_time_limit(600);
		require($job_script);

		if ($job['log_sql_queries'])
		{
			$db->log_next_query(0);
			bb_log(LOG_LF, $cron_sql_log_file);
		}

		if ($cron_write_log)
		{
			$msg = array();
			$msg[] = '  end';
			$msg[] = date('m-d');
			$msg[] = date('H:i:s');
			$msg[] = sprintf('%-4s', round(get_loadavg(), 1));
			$msg[] = sprintf('%05d', getmypid());
			$msg[] = round(utime() - $cron_start_time) .'/'. round(utime() - TIMESTART) . ' sec';
			$msg = join(LOG_SEPR, $msg);
			$msg .= LOG_LF .'------=-------=----------=------=-------=----------';
			bb_log($msg . LOG_LF, CRON_LOG_DIR . CRON_LOG_FILE);

			if ($cron_runtime_log)
			{
				$runtime_log_file = ($job['log_file']) ? $job['log_file'] : $job['cron_script'];
				bb_log($cron_runtime_log . LOG_LF, CRON_LOG_DIR . basename($runtime_log_file));
			}
		}

		$db->query("
			UPDATE ". CRON_TABLE ." SET
				last_run = NOW(),
				run_counter = run_counter + 1,
				next_run =
			CASE
				WHEN schedule = 'hourly' THEN
					DATE_ADD(NOW(), INTERVAL 1 HOUR)
				WHEN schedule = 'daily' THEN
					DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 1 DAY), INTERVAL TIME_TO_SEC(run_time) SECOND)
				WHEN schedule = 'weekly' THEN
					DATE_ADD(
						DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(NOW()) DAY), INTERVAL 7 DAY),
					INTERVAL CONCAT(ROUND(run_day-1), ' ', run_time) DAY_SECOND)
				WHEN schedule = 'monthly' THEN
					DATE_ADD(
						DATE_ADD(DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(NOW())-1 DAY), INTERVAL 1 MONTH),
					INTERVAL CONCAT(ROUND(run_day-1), ' ', run_time) DAY_SECOND)
				ELSE
					DATE_ADD(NOW(), INTERVAL TIME_TO_SEC(run_interval) SECOND)
			END
			WHERE cron_id = {$job['cron_id']}
			LIMIT 1
		");

		sleep(3);
	}
	else
	{
		$cron_err_msg = "Can't run \"{$job['cron_title']}\" : file \"$job_script\" not found". LOG_LF;
		bb_log($cron_err_msg, 'cron_error');
	}
}

