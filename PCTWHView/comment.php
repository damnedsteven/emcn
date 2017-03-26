<!DOCTYPE html>
<html>
<head>
<title>RM 备注</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?PLO=".$_GET['PLO']; ?>">

<!-- Paste this code into the BODY section of your HTML document  -->

<form action="" method="get">
	<label for="comment">添加 RM 备注: </label><br><br>
		<select name="comment" id="comment">
			<option value="TBD">选择备料情况</option>
			<option value="产线发不上，仓库满仓">产线发不上，仓库满仓</option>
			<option value="Schenker延误">Schenker延误</option>
			<option value="备料延误，不影响生产">备料延误，不影响生产</option>
			<option value="MM SHORT">MM SHORT</option>
			<option value="系统维护">系统维护</option>
			<option value="盘点">盘点</option>
			<option value="MM COA延误">MM COA延误</option>
			<option value="HOLD">HOLD</option>
			<option value="cancel">cancel</option>
			<option value="产线休息">产线休息</option>
			<option value="MM休息">MM休息</option>
			<option value="rework">rework</option>
			<option value="short">short</option>
			<option value="其它">其它</option>
		</select>
	
	<br/><br/>

	<label for="comment2"> 其它原因 (or Short P/N): </label><br>
	<textarea name="comment2" id="comment2" rows="5" cols="50"><?php if (isset($_GET['comment2'])) {echo $_GET['comment2'];} ?></textarea><br><br>	
	
	<label for="eta"> 到料时间 （如缺料）: </label><br>
	<input id="eta" name="eta" type="datetime" value="<?php date_default_timezone_set("Asia/Shanghai"); if (isset($_GET['eta'])) {echo $_GET['eta'];} ?>"/><br><br>
	
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
	if (isset($_POST['eta'])) {
				echo '<script type="text/javascript">';
					echo 'document.getElementById(\'eta\').value = "'.$_POST['eta'].'"';
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
			if ($_POST['comment'] <> 'short') {
				$query = "UPDATE Picklist SET Comment7='{$_POST['comment']}', Comment7_2='{$_POST['comment2']}' ";
			} else {
				if (!empty($_POST['eta'])) {
					$query = "UPDATE Picklist SET Comment7='{$_POST['comment']}', Comment8='{$_POST['comment2']}', Comment9='{$_POST['eta']}' ";
				} else {
					$query = "UPDATE Picklist SET Comment7='{$_POST['comment']}', Comment8='{$_POST['comment2']}' ";
				}
			}
			
			if ($_POST['applyto'] == 'seta') {
				$query .= " WHERE PLO IN (SELECT PLO FROM PCTMaster WHERE {$_POST['set_a']}=(SELECT {$_POST['set_a']} FROM PCTMaster WHERE PLO='{$_GET['PLO']}'))
				";
			} elseif ($_POST['applyto'] == 'setb') {
				if (isset($_POST['startdate']) && isset($_POST['enddate'])) {
					$startdate = date('Y-m-d H:i:s', strtotime($_POST['startdate']));
					$enddate = date('Y-m-d H:i:s', strtotime($_POST['enddate']));
					$query .= " WHERE PLO IN (SELECT PLO FROM PCTMaster WHERE {$_POST['set_b']}=(SELECT {$_POST['set_b']} FROM PCTMaster WHERE PLO='{$_GET['PLO']}'))
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
				$query .= " WHERE PLO IN ({$entry}) ";
			} else {
				$query .= " WHERE PLO='{$_GET['PLO']}' ";
			}
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