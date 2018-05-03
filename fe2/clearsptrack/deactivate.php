<?php

	ini_set('mssql.charset', 'UTF-8');
	
	echo "<meta charset=\"UTF-8\">";

	// echo $argv[1];
	
	require_once('connectvars.php'); 
	
//---------------------------------------------------------------------------------------------------------------------------------------------------

// force deactivate UUTs in SOLAR SPT racks
if (isset($argv[1]) && $argv[1]=='solar') {
		
	// $sn = htmlspecialchars($_POST['sn']);
	// $sn = trim($sn);
	$rack = "R-%-%";
	$rack2 = "Blade-%-%";
	$rack3 = "Rack%-%-%";
	$rack4 = "Rack%-%-%-%";
	
	$hour = 5;
	
	$query0 = "
				select Serial_Number from UUT
				where UUT_ky in (
								select UUT_ky from UUT_Instance
								where active_fg = 1 and Rack_ky in (select Rack_ky from Rack where Work_Object LIKE '{$rack}' OR Work_Object LIKE '{$rack2}' OR Work_Object LIKE '{$rack3}' OR Work_Object LIKE '{$rack4}') and DATEDIFF(hour, create_dm, GETDATE())>{$hour})
	";

	$query = "  
				update UUT_Instance 
				set active_fg = 0, Status_fg = 'C', Location = 'Forced'
				where active_fg = 1 and Rack_ky in (select Rack_ky from Rack where Work_Object LIKE '{$rack}' OR Work_Object LIKE '{$rack2}' OR Work_Object LIKE '{$rack3}' OR Work_Object LIKE '{$rack4}') and DATEDIFF(hour, create_dm, GETDATE())>{$hour}
	";	
	
	$dbc = mssql_connect(DB_HOST, DB_USER, DB_PASSWORD) or die('ERROR: connect db error: ' . mssql_get_last_message());
	mssql_select_db(DB_NAME,$dbc) or die('ERROR: can not open db table: ' . mssql_get_last_message());


	mssql_query( 'SET CONCAT_NULL_YIELDS_NULL ON', $dbc );
	mssql_query( 'SET ANSI_WARNINGS ON', $dbc );
	mssql_query( 'SET ANSI_PADDING ON', $dbc );

	// find SNs to deactivate
	$result = mssql_query($query0,$dbc) or die('search db error ');
	
	if (!$result) 
		{
			$message = 'ERROR: ' . mssql_get_last_message();
			return $message;
		}
		else
		{
			$SNArr = array();
			
			// while (($row = mssql_fetch_row($result))){
			while ($row = mssql_fetch_assoc($result)){
				array_push($SNArr,trim($row['Serial_Number']));
			}
			// print_r($result);
			// $SNs = "'".implode("','", $SNArr)."'";
			mssql_free_result($result);
		}
	
	// deactivate UUTs
	mssql_query($query,$dbc) or die('ERROR: update failed: ' . mssql_get_last_message());

	mssql_close($dbc);
		
}

// force deactivate UUTs in remus SPT racks
if (isset($argv[2]) && $argv[2]=='remus') {
	// $SNArr = array('6CU751NCE5', '6CU751NCNT');
	if (empty($SNArr)) {
		echo "No outstanding SNs to deactivate... <br>";
		
	} else {
		// Connect to Remus DB
		$dbc = mssql_connect(DB_HOST_70, DB_USER_70, DB_PASSWORD_70) or die("connect db error");	
		mssql_select_db(DB_NAME_70,$dbc) or die('can not open db table');
		
		// $strArr = array();
			
		foreach ($SNArr as $SN) {
			// array_push($strArr, "IF NOT EXISTS (SELECT PLO FROM PCTMaster WHERE PLO = '{$v['PLO#']}') INSERT INTO PCTMaster (PLO, Line, SO, BirthDate, CreateTime, PLOQTY, Family, BPO, ShipRef, Product, PL) VALUES ('{$v['PLO#']}', '{$v['Line#']}', '{$v['SO#']}', '{$v['RTP Date']}', GETDATE(), '{$v['Qty']}', '{$v['Product Family']}', '{$v['OSP#']}', '{$v['Shipref']}', '{$v['Part#']}', '{$v['Product Line']}')"); 
			$query = "
						INSERT INTO Status
						(memberID, 
						stationID, 
						stateID, 
						status, statusDetail, wait, updateTime, createTime)
						VALUES
						((select memberID from Member where serialNumber = '{$SN}'), 
						(select stationID from Station where memberID = (select memberID from Member where serialNumber = '{$SN}') and createTime = (select max(createTime) from Station where memberID = (select memberID from Member where serialNumber = '{$SN}'))
						), 
						(select stateID from State where memberID = (select memberID from Member where serialNumber = '{$SN}') and createTime = (select max(createTime) from State where memberID = (select memberID from Member where serialNumber = '{$SN}'))
						), 
						0, 'Finalized', 0, GETDATE(), GETDATE())
			";
			
			mssql_query($query,$dbc) or die('search db error ');
		}
		
		// $query = implode(' ', array_reverse($strArr));

		mssql_close($dbc);
	}	
}
?>
