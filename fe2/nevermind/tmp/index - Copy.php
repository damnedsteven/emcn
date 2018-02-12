<?php
//---------------------------------
	// Insert the page header
	$page_title = 'Main';
	
	require_once('header.php');
	
	require_once('navmenu.php');
	
	require_once('connectvars.php');
	
	// build_table
	function build_table($array){
		// start table
		$html = '<table border="1" cellpadding=2 style="color:navy;font-weight:bold;font-family:Calibri;font-size:9pt;">';
		// header row
		$html .= '<tr>';
		foreach($array[0] as $key=>$value){
				$html .= '<th>' . $key . '</th>';
			}
		$html .= '</tr>';

		// data rows
		foreach( $array as $key=>$value){
			$html .= '<tr>';
			foreach($value as $key2=>$value2){
				if (is_array($value2)) {
					$html .= '<td>';
					foreach( $value2 as $key3=>$value3){
						if (is_array($value3)) {
							$html .= '<tr>';
							foreach($value3 as $key4=>$value4){
								$html .= '<td>' . $value4 . '</td>';
							}
							$html .= '</tr>';
						} else {
							$html .= '<td>' . $value3 . '</td>';
						}
					}
					$html .= '</td>';
				} else {
					$html .= '<td>' . $value2 . '</td>';
				}
			}
			$html .= '</tr>';
		}

		// finish table and return it

		$html .= '</table>';
		return $html;
	}
	
//---------------------------------------------------------------------------------------------------------------------------------------------------

	echo '<div class="input">';
	
		echo '<form method="post" action="'.$_SERVER['PHP_SELF'].'">';
			//search by SN, BPO			
			echo 'Enter SN(s) or BPO(s):&nbsp&nbsp&nbsp&nbsp';
			
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
	if (!empty($entry)) {
			$query .="  (serialNumber IN ({$entry})
						OR
						orderNumber IN ({$entry}))
			";
	} 

	// Show PGIed PLOs of yesterday by default
	if (!empty($entry)) {		
		echo '<table width=100%>';
			echo '<tr>';
				echo '<td valign="top">';
					//------print data table
					echo '<table id="major" border="1" cellpadding=2 style="color:navy;font-weight:bold;font-family:Calibri;font-size:9pt;">';
					echo '<thead>';
					echo '<tr bgcolor=navy style=color:white>';
					  echo '<th data-tsorter="numeric">BPO#</th>';
					  echo '<th data-tsorter="numeric">Unit SN#</th>';
					  echo '<th>ACT Config</th>';
					  // echo '<th>HDD1 SN#</th>';
					  // echo '<th>DIMM1 SN#</th>';
					echo '</tr>';
					echo '</thead>';
					
					$data = mssql_query($query,$dbc) or die('search db error ');

					while ($row = mssql_fetch_assoc($data)){											
						echo '<tr>';
							echo '<td>'.$row['orderNumber'].'</td>';
							echo '<td>'.$row['serialNumber'].'</td>';
							//---------------------------------------------------------------------------------------------------------------

							// $xml = new SimpleXMLElement($row['value']);
							// echo '<td>'.$xml.'</td>';

							$s = $row['value'];
							$p = xml_parser_create();
							xml_parse_into_struct($p, $s, $vals[$row['serialNumber']], $index[$row['serialNumber']]);
							xml_parser_free($p);
							echo '<td>'.build_table($vals[$row['serialNumber']]).'</td>';
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