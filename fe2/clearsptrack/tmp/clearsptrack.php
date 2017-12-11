<?php

$host = '16.187.226.128';
$port = '1433';
$server = $host . ',' . $port;
$database = 'Solar';
$user = 'sa';
$password = 'Support2015';

$link = mssql_connect ($server, $user, $password);
if (!$link)
{
	die('ERROR: Could not connect: ');
}

mssql_select_db($database);

$query = 'select R.Work_Object,  U.Serial_Number, UI.Status_fg
from 
UUT_Instance UI
left join
UUT U
on UI.UUT_ky = U.UUT_ky
left join
Rack R
on UI.Rack_ky = R.Rack_ky
where R.Work_Object LIKE "R-%-%" and UI.active_fg = 1
order by R.Work_Object';

$result = mssql_query($query);
if (!$result) 
{
	$message = 'ERROR: ';
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
?>