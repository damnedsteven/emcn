<!DOCTYPE html>
<html>
<head>
<title>Short 备注</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?PLO=".$_GET['PLO']; ?>">

<!-- Paste this code into the BODY section of your HTML document  -->

<form action="" method="get">
	
	<label for="comment"> Short P/N: </label><br>
	<textarea name="comment" id="comment" rows="5" cols="50"><?php if (isset($_GET['comment'])) {echo $_GET['comment'];} ?></textarea><br><br>
	
	<label for="eta"> ETA: </label><br>
	<input id="eta" name="eta" type="datetime" value="<?php date_default_timezone_set("Asia/Shanghai"); echo date("Y-m-d H:i"); ?>"/><br><br>
	
	<input type="checkbox" name="apply" id="apply" value="1">
	<label for="apply">添加到整个备料批次</label><br><br>

	<input type="submit" name="submit" value="Submit" />

</form>

<?php
if (isset($_GET['comment'])) {
	echo '<script type="text/javascript">';
		echo 'document.getElementById(\'comment\').value = "'.$_GET['comment'].'"';
	echo '</script>';
}
if (isset($_POST['eta'])) {
			echo '<script type="text/javascript">';
				echo 'document.getElementById(\'eta\').value = "'.$_POST['eta'].'"';
			echo '</script>';
}
?>

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
						SET Comment8='{$_POST['comment']}', Comment9='{$eta}' 
						WHERE PLO IN (SELECT PLO FROM PCTMaster WHERE OnlineNo=(SELECT OnlineNo FROM PCTMaster WHERE PLO='{$_GET['PLO']}'))
			";
		} else {
			$query = "  UPDATE Picklist 
						SET Comment8='{$_POST['comment']}', Comment9='{$eta}' 
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