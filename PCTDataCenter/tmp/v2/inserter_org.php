<?php
	// timing
	$time_start = microtime(true); 

	date_default_timezone_set('Asia/Shanghai');
	
	ini_set('max_execution_time', 1500); //1500 seconds = 25 minutes
	
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
	
	$query = "  IF OBJECT_ID('dbo.PCTMaster', 'U') IS NULL
					BEGIN
						CREATE TABLE PCTMaster
						(
						SO NVARCHAR(20) NULL,
						PLO NVARCHAR(20) NOT NULL,
						BPO NVARCHAR(20) NULL,
						Family NVARCHAR(20) NULL,
						Model NVARCHAR(20) NULL,
						Product NVARCHAR(20) NULL,
						CreateTime SMALLDATETIME NULL,
						ImportTime SMALLDATETIME NULL,
						BirthDate SMALLDATETIME NULL,

						PicklistPL NVARCHAR(20) NULL,
						PicklistTime SMALLDATETIME NULL,
						
						OnlineNo NVARCHAR(20) NULL,
						PLOQTY INT NULL,
						PL NVARCHAR(20) NULL,
						WHInputTime SMALLDATETIME NULL,
						WHUpdateTime SMALLDATETIME NULL,
						LineInputTime SMALLDATETIME NULL,
						
						FEFlag INT NULL,
						
						DN NVARCHAR(20) NULL,
						
						HandoverTime SMALLDATETIME NULL,
						Putaway INT NULL,
						
						ShipRef NVARCHAR(20) NULL,
						PGITime SMALLDATETIME NULL,

						Comment NVARCHAR(200) NULL,
						Comment2 NVARCHAR(200) NULL,
						Comment3 NVARCHAR(400) NULL,	
						Comment4 NVARCHAR(200) NULL,
						Comment5 NVARCHAR(200) NULL,
						Comment6 NVARCHAR(400) NULL,

						ConfigType NVARCHAR(20) NULL
						);
						CREATE INDEX PCTMasterIndex
						ON PCTMaster (PLO);
					END
	
				SELECT 
					DISTINCT 
					PLO
				FROM 
					PCTMaster
				WHERE 
					DATEDIFF(n,ImportTime,GETDATE()) <= 60*24*{$days}
					--ImportTime BETWEEN DATEADD(day,-{$days}-1,GETDATE()) AND DATEADD(day,-{$days},GETDATE())
					"; 

	$data = mssql_query($query,$dbc) or die('search db error ');

	if (!mssql_num_rows($data)) {
		echo "Initiate Cold Start <br>";
	}
	else {
		$PLOArray = array();
		while ($row = mssql_fetch_assoc($data)){
			array_push($PLOArray,trim($row['PLO']));//获取 PLO at 112DB
		}
		$PLO = "'".implode("','", $PLOArray)."'";
	}
	
	mssql_free_result($data);
	mssql_close($dbc); 
	
	// Connect to RTP DB
	$dbc = mssql_connect(DB_HOST_RTP, DB_USER_RTP, DB_PASSWORD_RTP) or die("connect db error");
	mssql_select_db(DB_NAME_RTP,$dbc) or die('can not open db table');
	
	$query = "  SELECT 
					DISTINCT
					SO# AS SO,
					PLO# AS PLO,
					CONVERT(VARCHAR(24),ImportTime,120) ImportTime
				FROM 
					RtpData
				WHERE 
					ShipPoint = 'BF40'
					AND
					DATEDIFF(n,ImportTime,GETDATE()) <= 60*24*{$days}
					--ImportTime BETWEEN DATEADD(day,-{$days}-1,GETDATE()) AND DATEADD(day,-{$days},GETDATE())
					";
	if (isset($PLO)) {
		$query .= "
					AND
					PLO# NOT IN ({$PLO})"; 
	}

	$data = mssql_query($query,$dbc) or die('search db error');
	
	if (!mssql_num_rows($data)) {
		echo "No New PLO <br>";
		
		mssql_free_result($data);
		mssql_close($dbc); 
	} else {
		while ($row = mssql_fetch_assoc($data)){
			$RTP[trim($row['PLO'])]['SO'] = trim($row['SO']);
			
			$RTP[trim($row['PLO'])]['ImportTime'] = $row['ImportTime'];
		}

		mssql_free_result($data);
		mssql_close($dbc); 

		if (isset($RTP)) {
			// $strArr = array();
			
			// Connect to 112 DB
			$dbc = mssql_connect(DB_HOST_112, DB_USER_112, DB_PASSWORD_112) or die("connect db error");	
			mssql_select_db(DB_NAME_112,$dbc) or die('can not open db table');
			
			foreach ($RTP as $k => $v) {
				// array_push($strArr, "IF NOT EXISTS (SELECT PLO FROM PCTMaster WHERE PLO = '{$k}') INSERT INTO PCTMaster (PLO, SO, ImportTime, CreateTime) VALUES ('{$k}', '{$v['SO']}', '{$v['ImportTime']}', GETDATE())"); 
				
				$query = "
					IF NOT EXISTS (SELECT PLO FROM PCTMaster WHERE PLO = '{$k}') INSERT INTO PCTMaster (PLO, SO, ImportTime, CreateTime) VALUES ('{$k}', '{$v['SO']}', '{$v['ImportTime']}', GETDATE())
				";
				
				mssql_query($query,$dbc) or die('search db error ');
			}		
			// $query = implode(' ', $strArr);

			mssql_close($dbc);
		}
	}
	// total
	echo 'Total execution time in seconds: ' . (microtime(true) - $time_start);
?>