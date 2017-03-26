<!DOCTYPE html>
<html>
<head>
<title>Egoras Fail</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="text/javascript" src="dropdownBox.js"></script>
</head>
<body>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?PLO=".$_GET['PLO']; ?>">

<!-- Paste this code into the BODY section of your HTML document  -->

<form action="" method="get">

	<label for="access">Are You Sherrill? </label>
	<input type="radio" name="access" value="1"> Yes 
	<input type="radio" name="access" value="0" checked> No<br><br>

	<label for="comment">Egoras Fail? </label>
	<input type="radio" name="comment" value="1" checked> Yes 
	<input type="radio" name="comment" value="0"> No<br><br>

	<label for="comment2">Details: </label><br>
	<textarea name="comment2" id="comment2" rows="5" cols="50"><?php if (isset($_GET['comment2'])) {echo $_GET['comment2'];} ?></textarea><br><br>
	

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

if (!empty($_POST['access'])) {
	
	if (isset($_GET['PLO']) && isset($_POST['comment'])) {

		$query = "  UPDATE PCTMaster 
					SET EgorasFail=\"{$_POST['comment']}\", EgorasFailDetail=\"{$_POST['comment2']}\" 
					WHERE PLO='{$_GET['PLO']}'
		";

		var_dump($query);
		// Connect to 112 DB
		$dbc = mssql_connect(DB_HOST_112, DB_USER_112, DB_PASSWORD_112) or die("connect db error");	
		mssql_select_db(DB_NAME_112,$dbc) or die('can not open db table');

		mssql_query($query,$dbc) or die('search db error ');

		mssql_close($dbc);
		
		echo "<script>window.close();</script>";
	}	
}


	
?>

</body>
</html>