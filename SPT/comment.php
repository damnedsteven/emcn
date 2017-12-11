<!DOCTYPE html>
<html>
<head>
<title>Cause Of Failure</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="text/javascript" src="dropdownBox.js"></script>
</head>
<body>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?PLO=".$_GET['PLO']; ?>">

<!-- Paste this code into the BODY section of your HTML document  -->

<form action="" method="get">
	<label for="comment">添加/更新备注: </label><br><br>
		<select name="comment" id="comment" onchange="populate(this)">
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
	<select name="comment2" id="comment2"></select>
	<br/><br/>

	<label for="comment3"> 详因: </label><br>
	<textarea name="comment3" id="comment3" rows="5" cols="50"><?php if (isset($_GET['comment3'])) {echo $_GET['comment3'];} ?></textarea><br><br>
	
	<input type="radio" name="applyto" value="plo" checked> 添加到: PLO#<?php echo $_GET['PLO']; ?>
	
	<br><br>
	
	<input type="radio" name="applyto" value="seta"> 添加到: 
	<select name="set_a" id="set_a">
		<option value="SO">SO</option>
		<option value="BPO">BPO</option>
		<option value="OnlineNo">备料批次</option>
		<option value="ShipRef" selected>ShipRef</option>
	</select>
	
	<br><br>
	
	<input type="radio" name="applyto" value="setb"> 添加到:
	<select name="set_b" id="set_b">
		<option value="Family" selected>Family</option>
		<option value="Model">Model</option>
		<option value="Product">Product</option>
	</select>
	
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
	?>
	
	<br><br>
	
	<input type="radio" name="applyto" value="setc"> 添加到: 以下 PLOs (用回车隔开):<br>
	<textarea name="set_c" id="set_c" rows="5" cols="50"></textarea>
	
	<br><br>

<?php
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
		if (isset($_POST['applyto'])) {
			if ($_POST['applyto'] == 'seta') {
				$query = "  UPDATE PCTMaster 
							SET Comment4='{$_POST['comment']}', Comment5='{$_POST['comment2']}', Comment6='{$_POST['comment3']}' 
							WHERE PLO IN (SELECT PLO FROM PCTMaster WHERE {$_POST['set_a']}=(SELECT {$_POST['set_a']} FROM PCTMaster WHERE PLO='{$_GET['PLO']}'))
				";
			} elseif ($_POST['applyto'] == 'setb') {
				if (isset($_POST['startdate']) && isset($_POST['enddate'])) {
					$startdate = date('Y-m-d H:i:s', strtotime($_POST['startdate']));
					$enddate = date('Y-m-d H:i:s', strtotime($_POST['enddate']));
					$query = "  UPDATE PCTMaster 
								SET Comment4='{$_POST['comment']}', Comment5='{$_POST['comment2']}', Comment6='{$_POST['comment3']}' 
								WHERE PLO IN (SELECT PLO FROM PCTMaster WHERE {$_POST['set_b']}=(SELECT {$_POST['set_b']} FROM PCTMaster WHERE PLO='{$_GET['PLO']}'))
								AND
								PGITime BETWEEN '{$startdate}' and '{$enddate}'
					";
				}				
			} elseif ($_POST['applyto'] == 'setc') {
				//添加到: 以下 PLOs (用回车隔开)
				if (!empty($_POST['set_c'])) {
					$entries = explode("\n",$_POST['set_c']);
					$entryArr = array();
					foreach ($entries as $v) {
						array_push($entryArr,trim($v));
					} 
					$entry = "'".implode("','", $entryArr)."'";
				}
				$query = "UPDATE PCTMaster SET Comment4='{$_POST['comment']}', Comment5='{$_POST['comment2']}', Comment6='{$_POST['comment3']}' WHERE PLO IN ({$entry}) ";
			} else {
				$query = "UPDATE PCTMaster SET Comment4='{$_POST['comment']}', Comment5='{$_POST['comment2']}', Comment6='{$_POST['comment3']}' WHERE PLO='{$_GET['PLO']}' ";
			}
		} 
		// var_dump($query);
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