<?php

	ini_set('mssql.charset', 'UTF-8');
	
	echo "<meta charset=\"UTF-8\">";

	echo $argv[1];
	
	require_once('connectvars.php'); 
	
//---------------------------------------------------------------------------------------------------------------------------------------------------

// if (isset($_GET['ok']) && $_GET['ok']==1) {
if (isset($argv[1]) && $argv[1]=='ok') {
		
	// $sn = htmlspecialchars($_POST['sn']);
	// $sn = trim($sn);
	$rack = "R-%-%";
	
	$hour = 5;

	$query = "  
				update UUT_Instance 
				set active_fg = 0, Status_fg = 'C'
				where active_fg = 1 and Rack_ky in (select Rack_ky from Rack where Work_Object LIKE '{$rack}') and DATEDIFF(hour, create_dm, GETDATE())>{$hour}
	";
	
	$dbc = mssql_connect(DB_HOST, DB_USER, DB_PASSWORD) or die('ERROR: connect db error: ' . mssql_get_last_message());
	mssql_select_db(DB_NAME,$dbc) or die('ERROR: can not open db table: ' . mssql_get_last_message());


	mssql_query( 'SET CONCAT_NULL_YIELDS_NULL ON', $dbc );
	mssql_query( 'SET ANSI_WARNINGS ON', $dbc );
	mssql_query( 'SET ANSI_PADDING ON', $dbc );

	mssql_query($query,$dbc) or die('ERROR: update failed: ' . mssql_get_last_message());

	mssql_close($dbc);
		
}	

?>
