<?php
	// timing
	$time_start = microtime(true); 

	date_default_timezone_set('Asia/Shanghai');
	
	ini_set('max_execution_time', 18000); //18000 seconds = 300 minutes
	
	ini_set('mssql.charset', 'UTF-8');
	
	echo "<meta charset=\"UTF-8\">";
	
	require_once('connectvars.php'); 
	
	if (isset($_GET['days'])) {
		$days = $_GET['days'];
	} else {
		$days = 1; // retrieve how many days of data
	}

//---------------------------------------------------------------------------------------------------------------------------------------------------

	// Connect to 112 DB
	$dbc = mssql_connect(DB_HOST_112, DB_USER_112, DB_PASSWORD_112) or die("connect db error");	
	mssql_select_db(DB_NAME_112,$dbc) or die('can not open db table');
	
	$query = "  SELECT 
					DISTINCT 
					PLO,
					CASE WHEN FEFlag is null THEN 0 ELSE 1 END AS Info_BKPL,
					CASE WHEN LineInputTime is null THEN 0 ELSE 1 END AS Info_WH,
					CASE WHEN HandoverTime is null THEN 0 ELSE 1 END AS Info_HO,
					CASE WHEN PGITime is null THEN 0 ELSE 1 END AS Info_PGI
				FROM 
					PCTMaster
				WHERE 
					--DATEDIFF(n,BirthDate,GETDATE()) <= 60*24*{$days}
					BirthDate BETWEEN DATEADD(day,-{$days}-1,GETDATE()) AND DATEADD(day,-{$days},GETDATE())
					--AND
					--PGITime is null
					"; 

	$data = mssql_query($query,$dbc) or die('search db error ');

	if (!mssql_num_rows($data)) {
		echo 'No Need To Update For Now </BR>';
	}
	else {
		$PLOArr = array();
		$PLOArr_WH = array();
		
		while ($row = mssql_fetch_assoc($data)){
			array_push($PLOArr,trim($row['PLO']));

			if ($row['Info_WH'] == 0) {
				array_push($PLOArr_WH,trim($row['PLO']));
			}
			
			$Info[trim($row['PLO'])]['BKPL'] = $row['Info_BKPL'];
			$Info[trim($row['PLO'])]['WH'] = $row['Info_WH'];
			$Info[trim($row['PLO'])]['HO'] = $row['Info_HO'];
			$Info[trim($row['PLO'])]['PGI'] = $row['Info_PGI'];
		}
		
		$PLO_WH = "'".implode("','", $PLOArr_WH)."'";

		mssql_free_result($data);
		mssql_close($dbc); 
		
		// Connect to 117 DB
		$dbc = mssql_connect(DB_HOST_117, DB_USER_117, DB_PASSWORD_117) or die("connect db error");	
		mssql_select_db(DB_NAME_117,$dbc) or die('can not open db table');
		
		if (isset($PLO_WH)) {
			$query = "  SELECT 
						DISTINCT 
						PLO# AS PLO,
						[WH-PLOdetails].OnlineNo AS OnlineNo,
						PLOQty AS PLOQTY,
						PL,
						CONVERT(VARCHAR(24),WHInputTime,120) WHInputTime,
						CONVERT(VARCHAR(24),WHUpdateTime,120) WHUpdateTime,
						CONVERT(VARCHAR(24),LineInputTime,120) LineInputTime
					FROM 
						[WH-PLOdetails]
						LEFT JOIN
						[WH-onlineNo]
						ON [WH-PLOdetails].OnlineNo = [WH-onlineNo].OnlineNo
					WHERE 
						--DATEDIFF(n,CreateTime,GETDATE()) <= 60*24*{$days}
						--AND
						PLO# IN ({$PLO_WH})";
						
			$data = mssql_query($query,$dbc) or die('search db error ');

			if (!mssql_num_rows($data)) {
				echo 'No Need To Update From WH For Now </BR>';
			}
			else {
				while ($row = mssql_fetch_assoc($data)){
					$Attr[trim($row['PLO'])]['OnlineNo'] = trim($row['OnlineNo']);
					$Attr[trim($row['PLO'])]['PLOQTY'] = trim($row['PLOQTY']);
					$Attr[trim($row['PLO'])]['PL'] = trim($row['PL']);				
					$Attr[trim($row['PLO'])]['WHInputTime'] = $row['WHInputTime'];
					$Attr[trim($row['PLO'])]['WHUpdateTime'] = $row['WHUpdateTime'];
					$Attr[trim($row['PLO'])]['LineInputTime'] = $row['LineInputTime'];
				}

				mssql_free_result($data);
				mssql_close($dbc); 
			}
		}
		
		require_once('parser_HO.php');//-----------------------------------------For Getting SFNG Data

		if (isset($Attr)) {
			// $strArr = array();
			
			// Connect to 112 DB
			$dbc = mssql_connect(DB_HOST_112, DB_USER_112, DB_PASSWORD_112) or die("connect db error");	
			mssql_select_db(DB_NAME_112,$dbc) or die('can not open db table');
			
			foreach ($Attr as $k => $v) {
				$clauseArr = array();
				
				$alias = array('HandoverTime' => 'Handover Date', 'Putaway' => 'Putaway', 'PGITime' => 'PGI_DATE'); // define parsing item
				
				foreach ($alias as $a => $b) {
					if (isset($v[$b]) && (!empty($v[$b]) || $v[$b] == '&nbsp')) {
						array_push($clauseArr,"{$a}='{$v[$b]}'" );
					}
				}
				$clause = implode(',', $clauseArr);

				if (!empty($k) && !empty($clause)) {
					$query = "
						UPDATE PCTMaster SET {$clause} WHERE PLO='{$k}'
					";
					
					mssql_query($query,$dbc) or die('search db error ');
				}
			}
			
			mssql_close($dbc);
		}
	}
	
	// total
	echo 'Total execution time in seconds: ' . (microtime(true) - $time_start);
?>