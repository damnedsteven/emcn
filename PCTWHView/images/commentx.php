<!DOCTYPE html>
<html>
<head>
<title>FG 备注</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="text/javascript" src="dropdownBox.js"></script>
</head>
<body>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?PLO=".$_GET['PLO']; ?>">

<!-- Paste this code into the BODY section of your HTML document  -->

<form action="" method="get">
	<label for="comment">添加 FG 备注: </label><br><br>
		<select name="comment" id="comment">
			<option value="TBD">选择大类</option>
			<option value="Material Shortage">Material Shortage</option>
			<option value="Group Order">Group Order</option>
			<option value="SAP Hold">SAP Hold</option>
			<option value="Order Management Issue">Order Management Issue</option>
			<option value="Operation Issue">Operation Issue</option>
			<option value="Engineering Issue">Engineering Issue</option>
			<option value="Special Downtime">Special Downtime</option>
			<option value="Others">Others</option>
		</select>
	
	<br/>
	<select name="comment3" id="comment3"></select>
	<br/><br/>

	<label for="comment2"> 详因: </label><br>
	<textarea name="comment2" id="comment2" rows="5" cols="50"><?php if (isset($_GET['comment2'])) {echo $_GET['comment2'];} ?></textarea><br><br>
	
	<input type="checkbox" name="apply" id="apply" value="1">
	<label for="apply">添加到: </label>
	<select name="set_a" id="set_a">
		<option value="SO">SO</option>
		<option value="BPO">BPO</option>
		<option value="OnlineNo" selected>OnlineNo</option>
		<option value="Order Management Issue">ShipRef</option>
	</select>
	
	<br><br>
	
	<input type="checkbox" name="apply2" id="apply2" value="1">
	<label for="apply2">添加到: </label>
	<select name="set_b" id="set_b">
		<option value="Family" selected>Family</option>
		<option value="Model">Model</option>
		<option value="Product">Product</option>
	</select>
	
	<br><br>
	
	<input type="checkbox" name="apply3" id="apply3" value="1">
	<label for="apply3">添加到以下 PLOs (用逗号隔开): </label><br>
	<textarea name="set_c" id="set_c" rows="2" cols="50"></textarea>
	
	<br><br>

<?php
//time input box
echo '<label for="startdate"> From : </label><input id="startdate" name="startdate" type="date" value="'.date("Y-m-d").'"/> &nbsp';
if (isset($_POST['startdate'])) {
	echo '<script type="text/javascript">';
		echo 'document.getElementById(\'startdate\').value = "'.$_POST['startdate'].'"';
	echo '</script>';
}

echo '<label for="enddate"> To : </label><input id="enddate" name="enddate" type="date" value="'.date("Y-m-d",strtotime(date("Y-m-d")."+1 days")).'"/> &nbsp';
if (isset($_POST['enddate'])) {
	echo '<script type="text/javascript">';
		echo 'document.getElementById(\'enddate\').value = "'.$_POST['enddate'].'"';
	echo '</script>';
}

if (isset($_GET['comment'])) {
	echo '<script type="text/javascript">';
		echo 'document.getElementById(\'comment\').value = "'.$_GET['comment'].'"';
	echo '</script>';
}
if (isset($_GET['comment2'])) {
	echo '<script type="text/javascript">';
		echo 'document.getElementById(\'comment2\').value = "'.$_GET['comment2'].'"';
	echo '</script>';
}
if (isset($_GET['comment3'])) {
			echo '<script type="text/javascript">';
				echo 'document.getElementById(\'comment3\').value = "'.$_GET['comment3'].'"';
			echo '</script>';
}
?>

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

	if (isset($_GET['PLO']) && !empty($_POST['comment'])) {
		$timestamp = strtotime($_POST['eta']);
		$eta = date('Y-m-d H:i:s', $timestamp);
		if (isset($_POST['apply'])) {
			$query = "  UPDATE Picklist 
						SET Comment7='{$_POST['comment']}', Comment8='{$_POST['comment2']}', Comment9='{$eta}' 
						WHERE PLO IN (SELECT PLO FROM PCTMaster WHERE OnlineNo=(SELECT OnlineNo FROM PCTMaster WHERE PLO='{$_GET['PLO']}'))
			";
		} else {
			$query = "  UPDATE Picklist 
						SET Comment7='{$_POST['comment']}', Comment8='{$_POST['comment2']}', Comment9='{$eta}' 
						WHERE PLO='{$_GET['PLO']}' ";
		}
		// Connect to 112 DB
		$dbc = mssql_connect(DB_HOST_112, DB_USER_112, DB_PASSWORD_112) or die("connect db error");	
		mssql_select_db(DB_NAME_112,$dbc) or die('can not open db table');

		mssql_query($query,$dbc) or die('search db error ');

		mssql_close($dbc);
		
		echo "<script>window.close();</script>";
	}

	
?>

</body>
</html>