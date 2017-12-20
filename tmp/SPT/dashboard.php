<?php
	// Insert the page header
	$page_title = 'Dashboard';
	
	require_once('header.php');
	$sec = "1800";
	header("Refresh: $sec; url=$page");
	
	require_once('navmenu.php');
?>

	<table>
		<tr>
			<td colspan=2>
				<div id="graph6">
					<img width="1200" height="300" src="graph6.php" />
				</div>
			</td>
		</tr>
		<tr>
			<td>
				<div id="graph7">
					<img width="600" height="250" src="graph7.php" />
				</div>
				<br>
				<div id="graph8">
					<img width="600" height="250" src="graph8.php" />
				</div>
			</td>
			<td>
				<div id="graph9">
					<img width="600" height="250" src="graph9.php" />
				</div>
				<br>
				<div id="graph10">
					<img width="600" height="250" src="graph10.php" />
				</div>
			</td>
		</tr>
	</table>
	
	
<?php
	// Insert the page footer
	require_once('footer.php');
?>