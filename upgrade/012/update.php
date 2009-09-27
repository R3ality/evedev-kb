<?php
// Add timestamp to kb3_inv_detail
// Create kb3_inv_all, kb3_inv_crp with timestamps
function update012()
{
	global $url, $header, $footer;
	//Checking if this Update already done
	if (CURRENT_DB_UPDATE < "012" )
	{
		if(is_null(config::get('012updatestatus'))) config::set('012updatestatus',0);
		$qry = new DBQuery(true);

		if(config::get('012updatestatus') <1)
		{
			// Add timestamp column to kb3_inv_detail
			$qry->execute("SHOW COLUMNS FROM kb3_inv_detail LIKE 'ind_timestamp'");
			if(!$qry->recordCount())
			{
				$qry->execute("ALTER TABLE kb3_inv_detail ADD ind_timestamp
					DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER ind_kll_id");
				config::set('012updatestatus',1);
				echo $header;
				echo "12. kb3_inv_detail timestamp column added";
				echo $footer;
				die();
			}
		}
		if(config::get('012updatestatus') <2)
		{
			// Add timestamp column to kb3_inv_detail
			$qry->execute("SHOW INDEX FROM kb3_inv_detail");
			$indextexists = false;
			while($testresult = $qry->getRow())
			{
				if($testresult['Column_name'] == 'ind_timestamp')
					$indextexists = true;
			}
			if(!$indextexists)
			{
				$qry->execute("ALTER TABLE `kb3_inv_detail` ADD INDEX ( `ind_timestamp` ) ");
				config::set('012updatestatus',2);
				echo $header;
				echo "12. kb3_inv_detail timestamp index added";
				echo $footer;
				die();
			}
		}
		if(config::get('012updatestatus') <3)
		{
			// Add pilot,timestamp index to kb3_inv_detail
			$qry->execute("SHOW INDEX FROM kb3_inv_detail");
			$indexpexists = false;
			while($testresult = $qry->getRow())
			{
				if($testresult['Key_name'] == 'ind_plt_time')
					$indexpexists = true;
			}
			if(!$indexpexists)
			{
				$qry->execute("ALTER TABLE `kb3_inv_detail` ADD INDEX ind_plt_time ( `ind_plt_id`,`ind_timestamp` ) ");
				config::set('012updatestatus',3);
				echo $header;
				echo "12. kb3_inv_detail pilot index added";
				echo $footer;
				die();
			}
		}
		if(config::get('012updatestatus') <4)
		{
			$qry->execute("SHOW TABLES LIKE 'kb3_inv_all'");
			if($qry->recordCount())	$qry->execute("DROP TABLE kb3_inv_all");
			$qry->execute("SHOW TABLES LIKE 'kb3_inv_crp'");
			if($qry->recordCount())	$qry->execute("DROP TABLE kb3_inv_crp");
			$qry->execute("SHOW TABLES LIKE 'kb3_inv_plt'");
			if($qry->recordCount())	$qry->execute("DROP TABLE kb3_inv_plt");
			// kb3_inv_all (kll_id, all_id, timestamp)
			$sql = "CREATE TABLE IF NOT EXISTS `kb3_inv_all` (
				  `ina_kll_id` int(6) NOT NULL DEFAULT '0',
				  `ina_all_id` int(3) NOT NULL DEFAULT '0',
				  `ina_timestamp` datetime NOT NULL,
				  PRIMARY KEY (`ina_kll_id`,`ina_all_id`),
				  KEY `ina_all_time` (`ina_all_id`,`ina_timestamp`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8";
			$qry->execute($sql);
			// kb3_inv_crp (kll_id, crp_id, timestamp)
			$sql = "CREATE TABLE IF NOT EXISTS `kb3_inv_crp` (
				  `inc_kll_id` int(6) NOT NULL DEFAULT '0',
				  `inc_crp_id` int(3) NOT NULL DEFAULT '0',
				  `inc_timestamp` datetime NOT NULL,
				  PRIMARY KEY (`inc_kll_id`,`inc_crp_id`),
				  KEY `inc_crp_time` (`inc_crp_id`,`inc_timestamp`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8";
			$qry->execute($sql);
			config::set('012updatestatus',4);
			echo $header;
			echo "12. kb3_inv_all and kb3_inv_crp created.";
			echo $footer;
			die();
		}
		if(config::get('012updatestatus') <5)
		{
			$step = 10000;
			$qry->execute("SELECT COUNT(*) as cnt FROM kb3_kills");
			$result = $qry->getRow();
			$max = $result['cnt'];
			if(!config::get('012_5_status')) config::set('012_5_status','0');
			// add times to kb3_inv_detail.
			$qry->execute("UPDATE kb3_inv_detail join kb3_kills on ind_kll_id = kll_id
				SET ind_timestamp = kll_timestamp
				WHERE ind_timestamp < '0001-01-01'
				AND kll_id >= ".config::get('012_5_status')."
				AND kll_id < ".(config::get('012_5_status') + $step));
			if($max < config::get('012_5_status') + $step)
			{
				config::set('012updatestatus',5);
				config::del('012_5_status');
				echo $header;
				echo "12. kb3_inv_detail timestamp added.";
				echo $footer;
				die();
			}
			else
			{
				config::set('012_5_status', config::get('012_5_status') + $step);
				echo $header;
				echo "12. kb3_inv_detail timestamp updated rows ".(config::get('012_5_status') - $step)." - ".config::get('012_5_status');
				echo $footer;
				die();
			}
		}
		if(config::get('012updatestatus') <6)
		{
			$step = 10000;
			$qry->execute("SELECT COUNT(*) as cnt FROM kb3_kills");
			$result = $qry->getRow();
			$max = $result['cnt'];
			if(!config::get('012_6_status')) config::set('012_6_status','0');
			// add times to kb3_inv_detail.
			$qry->execute("INSERT IGNORE INTO kb3_inv_all
				SELECT ind_kll_id, ind_all_id, ind_timestamp
				FROM kb3_inv_detail
				WHERE ind_kll_id >= ".config::get('012_6_status')."
					AND ind_kll_id < ".(config::get('012_6_status') + $step)."
				GROUP BY ind_kll_id, ind_all_id");
			if($max < config::get('012_6_status') + $step)
			{
				config::del('012_6_status');
				config::set('012updatestatus',6);
				echo $header;
				echo "12. kb3_inv_all filled.";
				echo $footer;
				die();
			}
			else
			{
				config::set('012_6_status', config::get('012_6_status') + $step);
				echo $header;
				echo "12. kb3_inv_all rows ".(config::get('012_6_status') - $step);
				echo " - ".config::get('012_6_status')." added.";
				echo $footer;
				die();
			}
		}
		if(config::get('012updatestatus') <7)
		{
			$step = 10000;
			// add times to kb3_inv_detail.
			$qry->execute("SELECT COUNT(*) as cnt FROM kb3_kills");
			$result = $qry->getRow();
			$max = $result['cnt'];
			if(!config::get('012_7_status')) config::set('012_7_status','0');

			$qry->execute("INSERT IGNORE INTO kb3_inv_crp
				SELECT ind_kll_id, ind_crp_id, ind_timestamp
				FROM kb3_inv_detail
				WHERE ind_kll_id >= ".config::get('012_7_status')."
					AND ind_kll_id < ".(config::get('012_7_status') + $step)."
				GROUP BY ind_kll_id, ind_crp_id");
			if($max < config::get('012_7_status') + $step)
			{
				config::del('012_7_status');
				config::set('012updatestatus',7);
				echo $header;
				echo "12. kb3_inv_crp filled.";
				echo $footer;
				die();
			}
			else
			{
				config::set('012_7_status', config::get('012_7_status') + $step);
				echo $header;
				echo "12. kb3_inv_crp rows ".(config::get('012_7_status') - $step);
				echo " - ".config::get('012_7_status')." added.";
				echo $footer;
				die();
			}
		}
		if(config::get('012updatestatus') <8)
		{
			// add times to kb3_inv_detail.
			$qry->execute("ALTER TABLE `kb3_log` CHANGE `log_ip_address` `log_ip_address` VARCHAR( 100 ) NOT NULL");
			config::set('012updatestatus',8);
			echo $header;
			echo "12. kb3_log expanded.";
			echo $footer;
			die();
		}
		if(config::get('012updatestatus') <9)
		{
			// add times to kb3_inv_detail.
			$qry->execute("SHOW COLUMNS FROM kb3_comments LIKE 'ip'");
			if(!$qry->recordCount())
			{
				$qry->execute("ALTER TABLE `kb3_comments` ADD `ip` VARBINARY( 39 ) NOT NULL DEFAULT '0:0:0:0'");
			}
			config::set('012updatestatus',9);
			echo $header;
			echo "12. kb3_comments ip field added.";
			echo $footer;
			die();
		}
		if(config::get('012updatestatus') <10)
		{
			$qry->execute("UPDATE kb3_config SET cfg_value = 'default' where cfg_key = 'style_name'");
			$qry->execute("INSERT IGNORE INTO kb3_config (cfg_site, cfg_key, cfg_value) SELECT cfg_site, 'theme_name', 'default' FROM kb3_config GROUP BY cfg_site");
			config::set('012updatestatus',10);
			echo $header;
			echo "12. theme set to default.";
			echo $footer;
			die();
		}
		if(config::get('012updatestatus') <11)
		{
			$qry->execute("UPDATE `kb3_navigation` SET url = '?a=self_detail' WHERE descr = 'Stats';");
			$qry->execute("DELETE FROM `kb3_navigation` WHERE url = '?a=losses';");
			$qry->execute("DELETE FROM `kb3_navigation` WHERE url = '?a=kills';");
			config::set('012updatestatus',11);
			echo $header;
			echo "12. Navigation updated.";
			echo $footer;
			die();
		}

		killCache();
		config::set("DBUpdate", "012");
		$qry->execute("UPDATE kb3_config SET cfg_value = '012' WHERE cfg_key = 'DBUpdate'");
		config::del("012updatestatus");
		echo $header;
		echo "Update 012 completed.";
		echo $footer;
		die();
	}
}

