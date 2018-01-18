<?php
	// timing
	$time_start = microtime(true); 

	date_default_timezone_set('Asia/Shanghai');
	
	ini_set('max_execution_time', 1500); //1500 seconds = 25 minutes
	
	ini_set('mssql.charset', 'UTF-8');
	
	echo "<meta charset=\"UTF-8\">";
	
	require_once('connectvars.php'); 

//---------------------------------------------------------------------------------------------------------------------------------------------------

	// Get RTP Data 
	require_once('parser_order.php');
	
	if (empty($order)) {
		echo "No New Order <br>";
		
	} else {
		$strArr = array();
			
		foreach ($order as $k => $v) {
			array_push($strArr, "IF NOT EXISTS (SELECT PLO FROM PCTMaster WHERE PLO = '{$v['PLO#']}') INSERT INTO PCTMaster (PLO, Line, SO, BirthDate, CreateTime, PLOQTY, Family, BPO, ShipRef, Product, PL) VALUES ('{$v['PLO#']}', '{$v['Line#']}', '{$v['SO#']}', '{$v['RTP Date']}', GETDATE(), '{$v['Qty']}', '{$v['Product Family']}', '{$v['OSP#']}', '{$v['Shipref']}', '{$v['Part#']}', '{$v['Product Line']}')"); 
		}
		
		$query = implode(' ', array_reverse($strArr));

		// Connect to 112 DB
		$dbc = mssql_connect(DB_HOST_112, DB_USER_112, DB_PASSWORD_112) or die("connect db error");	
		mssql_select_db(DB_NAME_112,$dbc) or die('can not open db table');

		mssql_query($query,$dbc) or die('search db error ');

		mssql_close($dbc);
	}
	// total
	echo 'Total execution time in seconds: ' . (microtime(true) - $time_start);
?>