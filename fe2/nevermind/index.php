<?php
//---------------------------------
	// Insert the page header
	$page_title = 'Main';
	
	require_once('header.php');
	
	require_once('navmenu.php');
	
	require_once('connectvars.php');
	
//---------------------------------------------------------------------------------------------------------------------------------------------------
	echo '<table BACKGROUND="images/nevermind.jpg" STYLE="background-repeat: no-repeat;">';
	echo '<tr>';
	echo '<td height=200 width=600>';
	
	echo '<div class="input">';
		echo '<form method="post" action="'.$_SERVER['PHP_SELF'].'">';
			//search by SN, BPO			
			echo '<p style="color:white;margin-left:10px;margin-top:130px;font-size:18pt;"><B>Sales Orders <font size=2>(or Serial Numbers)</font></B>:<br>';
			
			echo '<input id="entry" name="entry" value="'.htmlspecialchars($_POST['entry']).'"/> &nbsp';
			
			echo '<input type="submit" name="submit" value="Submit" /></p>';
			
			echo '<iframe id="txtArea1" style="display:none"></iframe>';
		echo '</form>';
	echo '</div>';
	
	echo '</td>';
	echo '</tr>';
	echo '</table>';

	
	echo '</br>';
	
	// Get post data
	$identity = $_POST['identity'];
	// enable multiple items search
	if (isset($_POST['entry'])) {
		$entries = explode(",",$_POST['entry']);
		$entryArr = array();
		foreach ($entries as $v) {
			array_push($entryArr,trim($v));
		} 
		$entry = "'".implode("','", $entryArr)."'";
	} 
//---------------------------------------------------------------------------------------------------------------------------------------------------

	// Connect to 112 DB	
	$dbc = mssql_connect(DB_HOST_112, DB_USER_112, DB_PASSWORD_112) or die("connect db error"); 
	mssql_select_db(DB_NAME_112,$dbc) or die('can not open db table');
	
		$query0 = "  
					SELECT DISTINCT
						SO,
						SN
					FROM
						PCTMaster P
						INNER JOIN
						OCT O
						ON P.PLO = O.PLO 
					WHERE
					";
	if (!empty($entry)) {
			$query0 .="  SO IN ({$entry})
			";
	} 
	
	if (!empty($entry)) {
		$data = mssql_query($query0,$dbc) or die('search db error ');
		
		if (mssql_num_rows($data)) {
			$SNArr = array();
			
			while ($row = mssql_fetch_assoc($data)){
				$SO[trim($row['SN'])] = trim($row['SO']);
				
				array_push($SNArr,trim($row['SN']));
			}
			$SN = "'".implode("','", $SNArr)."'";
		}
		
		mssql_free_result($data);
		mssql_close($dbc);
	}
	
	// Connect to Solar DB	
	$dbc = mssql_connect(DB_HOST, DB_USER, DB_PASSWORD) or die("connect db error"); 
	// mssql_select_db(DB_NAME,$dbc) or die('can not open db table');
	
		$query = "  
					SELECT
						SUBSTRING(File_nm, 1, 10) SN,
						File_nm,
						File_Status_Option_ky,
						File_Content,
						T2.create_dm
					FROM
						[Solar].[dbo].[File] T1
						INNER JOIN
						[Solar_Files].[dbo].[File] T2
						ON T1.[File_ky] = T2.[File_ky]
					WHERE
						--SUBSTRING(File_nm, 1, 10) = '6CU8052EYX'
						--File_nm LIKE '6CU8052EYX%'
					";
	if (empty($SN) && !empty($entry)) {
			$query .="  SUBSTRING(File_nm, 1, 10) IN ({$entry})
			";
	} 
	else {
			$query .="  SUBSTRING(File_nm, 1, 10) IN ({$SN})
			";
	}
// var_dump($query);
	// Show Table
	if (!empty($entry)) {		
		echo '<table width=100%>';
			echo '<tr>';
				echo '<td valign="top">';
					//------print data table
					echo '<table id="major" border="1" cellpadding=2 style="color:navy;font-weight:bold;font-family:Calibri;font-size:9pt;">';
					echo '<thead>';
					echo '<tr bgcolor=navy style=color:white>';
					  echo '<th data-tsorter="numeric">SO</th>';
					  echo '<th data-tsorter="numeric">SN</th>';
					  echo '<th>Status</th>';
					  echo '<th>File Name</th>';
					  echo '<th>File Content</th>';
					  echo '<th>Create Time</th>';
					echo '</tr>';
					echo '</thead>';
					
					$data = mssql_query($query,$dbc) or die('search db error ');

					while ($row = mssql_fetch_assoc($data)){											
						echo '<tr>';
							echo '<td>'.$SO[$row['SN']].'</td>';
							echo '<td>'.$row['SN'].'</td>';
							echo '<td>'.$row['File_Status_Option_ky'].'</td>';
							echo '<td>'.$row['File_nm'].'</td>';
							echo '<td>'.$row['File_Content'].'</td>';
							echo '<td>'.$row['create_dm'].'</td>';
							//---------------------------------------------------------------------------------------------------------------

							// $xml = new SimpleXMLElement($row['value']);
							// echo '<td>'.$xml.'</td>';

							// $s = $row['value'];
							// $p = xml_parser_create();
							// xml_parse_into_struct($p, $s, $vals[$row['serialNumber']], $index[$row['serialNumber']]);
							// xml_parser_free($p);
							// echo '<td>'.build_table($vals[$row['serialNumber']]).'</td>';
							//---------------------------------------------------------------------------------------------------------------
						echo '</tr>';
					}
					echo '</table>';
				echo '</td>';	
			echo '</tr>';
		echo '</table>';
				
		echo '<script src="tsorter.min.js"></script>';
		echo '<script src="fnExcelReport.js"></script>';
	
		echo '</body>';
		echo '</html>';
				
		mssql_free_result($data);
		mssql_close($dbc);
	}


?>