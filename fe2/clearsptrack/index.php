<!DOCTYPE html>
<html>
<head>
<title>Clear Rack</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="text/javascript" src="dropdownBox.js"></script>
<style>
table, th, td {
    border: 1px solid black;
    border-collapse: collapse;
}
th, td {
    padding: 15px;
}
</style>
</head>
<body>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">

<!-- Paste this code into the BODY section of your HTML document  -->

<form action="" method="get">

	<label for="sn">Enter SN#: </label><br>
	<textarea name="sn" id="sn" rows="5" cols="50"></textarea><br><br>
	
	<input type="submit" name="submit" value="Submit" />

</form>

<script>
    window.onunload = refreshParent;
    function refreshParent() {
        window.opener.location.reload();
    }
</script>

<?php

	ini_set('mssql.charset', 'UTF-8');
	
	echo "<meta charset=\"UTF-8\">";
	
	require_once('connectvars.php'); 
	
//---------------------------------------------------------------------------------------------------------------------------------------------------

if (isset($_POST['sn'])) {
		
	$sn = htmlspecialchars($_POST['sn']);
	$sn = trim($sn);
	$rack = "R-%-%";
	$rack2 = "Blade-%-%";
	$rack3 = "Rack%-%-%";
	$rack4 = "Rack%-%-%-%";
	if(strlen($sn)<>10 && substr($sn,0,6)<>'VMware'){
		echo "Invalid SN#!<br />";
		exit;
	} else {
		
		$query = "  
					update UUT_Instance 
					set active_fg = 0, Status_fg = 'C'
					where Rack_ky in (select Rack_ky from Rack where Work_Object LIKE '{$rack}' OR Work_Object LIKE '{$rack2}' OR Work_Object LIKE '{$rack3}' OR Work_Object LIKE '{$rack4}') and UUT_ky = (select UUT_ky from UUT where Serial_Number = '{$sn}')
		";
		
		$dbc = mssql_connect(DB_HOST, DB_USER, DB_PASSWORD) or die('ERROR: connect db error: ' . mssql_get_last_message());
		mssql_select_db(DB_NAME,$dbc) or die('ERROR: can not open db table: ' . mssql_get_last_message());


		mssql_query( 'SET CONCAT_NULL_YIELDS_NULL ON', $dbc );
		mssql_query( 'SET ANSI_WARNINGS ON', $dbc );
		mssql_query( 'SET ANSI_PADDING ON', $dbc );

		mssql_query($query,$dbc) or die('ERROR: update failed: ' . mssql_get_last_message());

		mssql_close($dbc);
		
		// echo "<script>window.close();</script>";
	}	
}	

?>

<?php
	ini_set('mssql.charset', 'UTF-8');
	
	echo "<meta charset=\"UTF-8\">";
	
	require_once('connectvars.php'); 
// Connect to Solar DB
		$dbc = mssql_connect(DB_HOST, DB_USER, DB_PASSWORD) or die('ERROR: connect db error: ' . mssql_get_last_message());
		mssql_select_db(DB_NAME,$dbc) or die('ERROR: can not open db table: ' . mssql_get_last_message());
		
		$query = 'select R.Work_Object Rack,  U.Serial_Number SN, UI.Status_fg Status, UI.create_dm Created, GETDATE() Now, DATEDIFF(hour, UI.create_dm, GETDATE()) LastedHours
					from 
					UUT_Instance UI
					left join
					UUT U
					on UI.UUT_ky = U.UUT_ky
					left join
					Rack R
					on UI.Rack_ky = R.Rack_ky
					where (R.Work_Object LIKE "R-%-%" or R.Work_Object LIKE "Blade-%-%" or R.Work_Object LIKE "Rack%-%-%" or R.Work_Object LIKE "Rack%-%-%-%") and UI.active_fg = 1
					order by LastedHours DESC, R.Work_Object';

		$result = mssql_query($query);
// var_dump($query);		
		if (!$result) 
		{
			$message = 'ERROR: ' . mssql_get_last_message();
			return $message;
		}
		else
		{
			$i = 0;
			echo '<html><body><table><tr>';
			while ($i < mssql_num_fields($result))
			{
				$meta = mssql_fetch_field($result, $i);
				echo '<td>' . $meta->name . '</td>';
				$i = $i + 1;
			}
			echo '</tr>';
			
			while ( ($row = mssql_fetch_row($result))) 
			{
				$count = count($row);
				$y = 0;
				echo '<tr>';
				while ($y < $count)
				{
					$c_row = current($row);
					echo '<td>' . $c_row . '</td>';
					next($row);
					$y = $y + 1;
				}
				echo '</tr>';
			}
			mssql_free_result($result);
			
			echo '</table></body></html>';
		}

		mssql_close($dbc);
?>

</body>
</html>