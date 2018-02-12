<?php
//---------------------------------
	// Insert the page header
	$page_title = 'Main';
	
	require_once('header.php');
	
	require_once('navmenu.php');
	
	require_once('connectvars.php');
	
//---------------------------------------------------------------------------------------------------------------------------------------------------

	echo '<div class="input">';
	
		echo '<form method="post" action="'.$_SERVER['PHP_SELF'].'">';
			//search by SN, BPO			
			echo 'Enter SN(s) or SO(s):&nbsp&nbsp&nbsp&nbsp';
			
			echo '<input id="entry" name="entry" value="'.htmlspecialchars($_POST['entry']).'"/> &nbsp';
			
			echo '&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';
			
			echo '<input type="submit" name="submit" value="Search" />';
			
			echo '&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';
			
			echo '<iframe id="txtArea1" style="display:none"></iframe>';
			echo '<button id="btnExport" onclick="fnExcelReport();"> EXPORT </button>';
		echo '</form>';
	echo '</div>';
	
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
					SELECT
						SO,
						BPO
					FROM
						PCTMaster
					WHERE
					";
	if (!empty($entry)) {
			$query0 .="  SO IN ({$entry})
			";
	} 
	
	if (!empty($entry)) {
		$data = mssql_query($query0,$dbc) or die('search db error ');
		
		if (mssql_num_rows($data)) {
			$BPOArr = array();
			
			while ($row = mssql_fetch_assoc($data)){
				$SO[trim($row['BPO'])] = trim($row['SO']);
				
				array_push($BPOArr,trim($row['BPO']));//获取 PLO at 112DB
			}
			$BPO = "'".implode("','", $BPOArr)."'";
		}
		
		mssql_free_result($data);
		mssql_close($dbc);
	}
	
	// Connect to DataMonster DB	
	$dbc = mssql_connect(DB_HOST_DM, DB_USER_DM, DB_PASSWORD_DM) or die("connect db error"); 
	mssql_select_db(DB_NAME_DM,$dbc) or die('can not open db table');
	
		$query = "  
					SELECT
						orderNumber,
						serialNumber,
						value
					FROM
						ReMUS_Member
						INNER JOIN
						ReMUS_ImageStor
						ON ReMUS_Member.memberID = ReMUS_ImageStor.memberID
					WHERE
						name = 'act_cfg.xml'
						AND
						--serialNumber = '6CU6467TT1'
					";
	if (empty($BPO) && !empty($entry)) {
			$query .="  serialNumber IN ({$entry})
			";
	} 
	else {
			$query .="  orderNumber IN ({$BPO})
			";
	}

	// Show Table
	if (!empty($entry)) {		
		// ------print data table
		echo '<table id="major" border="1" cellpadding=2 style="color:navy;font-weight:bold;font-family:Calibri;font-size:9pt;">';
		echo '<thead>';
		echo '<tr bgcolor=navy style=color:white>';
		  echo '<th data-tsorter="numeric">No.</th>';
		  echo '<th data-tsorter="numeric">SO#</th>';
		  echo '<th data-tsorter="numeric">Unit SN#</th>';
		  echo '<th>DIMM SN#</th>';
		  echo '<th>HDD SN#</th>';
		  echo '<th>NIC SN#</th>';
		echo '</tr>';
		echo '</thead>';
		
		$data = mssql_query($query,$dbc) or die('search db error ');

		$count = 0;
		
		while ($row = mssql_fetch_assoc($data)){	
			$count++;
			
			echo '<tr>';
				echo '<td>'.$count.'</td>';
				echo '<td>'.$SO[trim($row['orderNumber'])].'</td>';
				echo '<td>'.$row['serialNumber'].'</td>';

				$xml[trim($row['serialNumber'])]=simplexml_load_string($row['value']) or die("Error: Cannot create object");
$str = $row['value'];

header('Content-Disposition: attachment; filename="sample.xml"');
header('Content-Type: text/plain'); # Don't use application/force-download - it's not a real MIME type, and the Content-Disposition header is sufficient
header('Content-Length: ' . strlen($str));
header('Connection: close');


echo $str;
				//
				$XPATH[trim($row['serialNumber'])]['DIMM'] = $xml[trim($row['serialNumber'])]->xpath('//structure/property[@name="spdDimmManufacturerSerialNo"]/@value');
				$XPATH[trim($row['serialNumber'])]['HDD'] = $xml[trim($row['serialNumber'])]->xpath('//device[@class="hardDrive"]/property[@name="serialNumber"]/@value');
				// $XPATH[trim($row['serialNumber'])]['NIC'] = $xml[trim($row['serialNumber'])]->xpath('//device[@class="networkDevice"]/property[@name="serrNum"]/@value');
				$XPATH[trim($row['serialNumber'])]['NIC'] = $xml[trim($row['serialNumber'])]->xpath('//device[@name="FRUEEPROM0"]/property[@name="productSerialNumber"]/@value');
				
				foreach ($XPATH[trim($row['serialNumber'])] as $k => $v) {
					$SN_Arr[trim($row['serialNumber'])][$k] = array();
					
					foreach ($v as $subk => $subv) {
						foreach ($subv as $subsubk => $subsubv) {
							array_push($SN_Arr[trim($row['serialNumber'])][$k], $subsubv);
						}
					}
					
					$SN_Arr[trim($row['serialNumber'])][$k] = array_unique($SN_Arr[trim($row['serialNumber'])][$k]);
					
					$SN[trim($row['serialNumber'])][$k] = implode(", ", $SN_Arr[trim($row['serialNumber'])][$k]);
				}

				echo '<td>'.$SN[trim($row['serialNumber'])]['DIMM'].'</td>';
				echo '<td>'.$SN[trim($row['serialNumber'])]['HDD'].'</td>';
				echo '<td>'.$SN[trim($row['serialNumber'])]['NIC'].'</td>';
				//
				
				


			echo '</tr>';
		}
		echo '</table>';			
				
		echo '<script src="tsorter.min.js"></script>';
		echo '<script src="fnExcelReport.js"></script>';
	
		echo '</body>';
		echo '</html>';
				
		mssql_free_result($data);
		mssql_close($dbc);
	}


?>