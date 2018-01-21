<?php
	// timing
	$time_start = microtime(true); 

	date_default_timezone_set('Asia/Shanghai');
	
	ini_set('max_execution_time', 60*60); // 60 minutes
	
	ini_set('mssql.charset', 'UTF-8');
	
	echo "<meta charset=\"UTF-8\">";
	
	require_once('connectvars.php'); 
	
	// if (isset($_GET['days'])) {
		// $days = $_GET['days'];
	// } else {
		// $days = 1; // retrieve how many days of data
	// }

//---------------------------------------------------------------------------------------------------------------------------------------------------

	// Connect to 112 DB
	$dbc = mssql_connect(DB_HOST_112, DB_USER_112, DB_PASSWORD_112) or die("connect db error");	
	mssql_select_db(DB_NAME_112,$dbc) or die('can not open db table');
	
	$query = "  IF OBJECT_ID('dbo.OCT', 'U') IS NULL
					BEGIN
						CREATE TABLE OCT
						(
						PLO NVARCHAR(20) NOT NULL,
						WO NVARCHAR(20) NULL,
						Operation NVARCHAR(20) NULL,
						Operation_Start SMALLDATETIME NULL,
						Operation_End SMALLDATETIME NULL,
						CreateTime SMALLDATETIME NULL,
						Priority INT NULL
						);
						CREATE INDEX OCTIndex
						ON OCT (WO);
					END
	
				SELECT 
					DISTINCT 
					PLO
				FROM 
					PCTMaster
				WHERE 
					WHUpdateTime IS NOT NULL
					AND
					HandoverTime IS NULL
					-- AND
					-- FEFlag = 1
					"; 

	$data = mssql_query($query,$dbc) or die('search db error ');

	if (!mssql_num_rows($data)) {
		echo "当前无正在产线的 FE PLO <br>";
	} else {
		$PLOArr = array();
		while ($row = mssql_fetch_assoc($data)){
			array_push($PLOArr,trim($row['PLO']));//获取 PLO at 112DB
		}
		// $PLO = "'".implode("','", $PLOArr)."'";
	}
	
	mssql_free_result($data);
	
	require_once('parser_oct.php');//-----------------------------------------For Getting SFNG Data	
	
	require_once('parser_sn.php');//-----------------------------------------For Getting SFNG Data for SN

	if (isset($OperationTime)) {
		// $strArr = array();
		
		foreach ($OperationTime as $k => $v) {
			foreach ($v as $subk => $subv) {
				foreach ($subv as $subsubk => $subsubv) {
					// array_push($strArr, "IF NOT EXISTS (SELECT * FROM OCT WHERE PLO = '{$k}' AND WO = '{$subk}' AND Operation = '{$subsubk}') INSERT INTO OCT (PLO, WO, Operation, Operation_Start, Operation_End, CreateTime, Priority, SN) VALUES ('{$k}', '{$subk}', '{$subsubk}', '{$subsubv['start']}', '{$subsubv['end']}', GETDATE(), '{$subsubv['priority']}', '{$SerialNumber[$subk]}')"); 
					// $query2 = implode(' ', $strArr);
					$query2 = "
						IF NOT EXISTS (SELECT * FROM OCT WHERE PLO = '{$k}' AND WO = '{$subk}' AND Operation = '{$subsubk}') INSERT INTO OCT (PLO, WO, Operation, Operation_Start, Operation_End, CreateTime, Priority, SN) VALUES ('{$k}', '{$subk}', '{$subsubk}', '{$subsubv['start']}', '{$subsubv['end']}', GETDATE(), '{$subsubv['priority']}', '{$SerialNumber[$subk]}')
					";
					mssql_query($query2,$dbc) or die('search db error ');
				}
			}
		}
		
		mssql_close($dbc);
	}
	

	// total
	echo 'Total execution time in seconds: ' . (microtime(true) - $time_start);
?>