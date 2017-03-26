<?php
//---------------------------------
	// Insert the page header
	$page_title = 'Main';
	require_once('header.php');
	
	$page = $_SERVER['PHP_SELF'];
	$sec = "300";
	header("Refresh: $sec; url=$page");
	
	require_once('navmenu.php');
	
	require_once('connectvars.php');
	
	date_default_timezone_set("Asia/Shanghai");
	
	require_once('overlap.php');//调用overlap算法
	
	// Unit: Hour
	$Target_TAT_Checkin = 1;
	
	$Target_TAT_Checkout = 12; // default 12 hours
	
	$Target_TAT_Checkout_Arr = array('tencent' => 4); // define specific targets
//---------------------------------------------------------------------------------------------------------------------------------------------------

	echo '<div class="input">';
	
		echo '<form method="post" action="'.$_SERVER['PHP_SELF'].'">';
			//search by 
			echo '<select name="identity" id="identity">';
				echo '<option value="Model">Model</option>';
				echo '<option value="Client">Client</option>';
				echo '<option value="PLO" selected>PLO</option>';
				echo '<option value="SO">SO</option>';
				echo '<option value="WO">WO</option>';
				echo '<option value="SN">SN</option>';
			echo '</select> &nbsp';
			
			if (isset($_POST['identity'])) {
				echo '<script type="text/javascript">';
					echo 'document.getElementById(\'identity\').value = "'.$_POST['identity'].'"';
				echo '</script>';
			}
			
			echo '<input id="entry" name="entry" value="'.htmlspecialchars($_POST['entry']).'"/> &nbsp';
			
			//select time base
			echo '<select name="base" id="base">';
				echo '<option value="BirthDate">BKPL</option>';
				echo '<option value="WHUpdateTime">Material Ready</option>';
				echo '<option value="Check_In">Check In</option>';
				echo '<option value="Check_Out" selected>Check Out</option>';
				echo '<option value="HandoverTime">Handover</option>';
				echo '<option value="PGITime">PGI</option>';
			echo '</select>';
			
			if (isset($_POST['base'])) {
				echo '<script type="text/javascript">';
					echo 'document.getElementById(\'base\').value = "'.$_POST['base'].'"';
				echo '</script>';
			}
			
			echo '&nbsp&nbsp';
			
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
			
			echo '<input type="submit" name="submit" value="查找" />';
			
			echo '&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp (Unit: hour) &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';
			
			echo '<input type="submit" name="checkin" value="显示待进站订单" />';
			
			echo '&nbsp&nbsp&nbsp&nbsp|&nbsp&nbsp&nbsp&nbsp';
			
			echo '<input type="submit" name="checkout" value="显示待出站订单" />';
			
			echo '&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp|&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';

			echo '<iframe id="txtArea1" style="display:none"></iframe>';
			echo '<button id="btnExport" onclick="fnExcelReport();"> 导出 </button>';
		echo '</form>';
	echo '</div>';
	
	echo '</br>';
	
	// Get post data
	$submit = $_POST['submit'];
	$checkin = $_POST['checkin'];
	$checkout = $_POST['checkout'];
	$startdate = $_POST['startdate'];
	$enddate = $_POST['enddate'];
	$base = $_POST['base'];
	$identity = $_POST['identity'];
	
	// Enable multiple PLO/SO search
	if (in_array($identity, array('PLO', 'SO', 'DN')) && !empty($_POST['entry'])) {
		$entries = explode(",",$_POST['entry']);
		$entryArr = array();
		foreach ($entries as $v) {
			array_push($entryArr,trim($v));
		} 
		$entry = "'".implode("','", $entryArr)."'";
	} else {
		$entry = trim($_POST['entry']);
	}
//---------------------------------------------------------------------------------------------------------------------------------------------------
	
	// Connect to 112 DB	
	$dbc = mssql_connect(DB_HOST_112, DB_USER_112, DB_PASSWORD_112) or die("connect db error"); 
	mssql_select_db(DB_NAME_112,$dbc) or die('can not open db table');
	
		$query = "  SELECT 
						PCTMaster.*,
						BPOCustomer.*,
						T.*
					FROM
						PCTMaster
						LEFT JOIN
						BPOCustomer
						ON PCTMaster.BPO = BPOCustomer.BPO
						INNER JOIN
						( SELECT 
							PLO,
							WO,
							Priority,
							MAX (CASE WHEN Operation = 'PRETEST' THEN Operation_End END) Pretest_End,
							MAX (CASE WHEN Operation = 'RUNIN' THEN Operation_Start END) Runin_Start,
							MAX (CASE WHEN Operation = 'RUNIN' THEN Operation_End END) Runin_End,
							MAX (CASE WHEN Operation = 'BF20_TSG_CUSINTENT' THEN Operation_End END) Check_In,
							MAX (CASE WHEN Operation = 'BF20_TSG_CUSINTCHK' THEN Operation_End END) Check_Out
						  FROM 
							OCT
						  GROUP BY
							PLO,
							WO,
							Priority
						) T
						ON PCTMaster.PLO = T.PLO
					WHERE
						(SO NOT LIKE 'PRP%' OR SO IS NULL)
						AND
					";
	if (isset($submit)) {
		if (isset($entry) && !empty($entry)) {
			if ($identity == 'PLO') {
				$query .=" T.{$identity} IN ({$entry})
				";
			} elseif (in_array($identity, array('SO', 'WO'))) {
				$query .=" {$identity} IN ({$entry})
				";
			} elseif ($identity == 'Client') {
				$query .= " (CusName LIKE '%{$entry}%'
							OR
							EndCusName LIKE '%{$entry}%'
							OR
							ShippingName LIKE '%{$entry}%')
				";
			} else {
				$query .=" {$identity} LIKE '%{$entry}%'
				";
			}
		} else {
			if (isset($base) && isset($startdate) && isset($enddate)) {
				$query .=" {$base} BETWEEN '{$startdate}' and '{$enddate}'
						   ORDER BY
						   {$base} DESC 
				";
			}
		}
	} elseif (isset($checkout)) {
		echo '<h1>To Check Out: </h1>';
		
		$query .="  WHUpdateTime IS NOT NULL
					AND
					HandoverTime IS NULL
					AND
					FEFlag = 1				
					AND
					Check_In IS NOT NULL
					AND
				    Check_Out IS NULL
					ORDER BY
				    Priority DESC,
				    Runin_End,
					Check_In DESC
				";
	} elseif (isset($checkin)) {
		echo '<h1>To Check In: </h1>';
		
		$query .="  WHUpdateTime IS NOT NULL
					AND
					HandoverTime IS NULL
					AND
					FEFlag = 1				
					AND
					Check_In IS NULL
					AND
				    (Runin_End IS NOT NULL OR Pretest_End IS NOT NULL)
					ORDER BY
				    Priority DESC,
				    Runin_End DESC,
					Pretest_End DESC
		";	
	} else { // by default
		echo '<h1>Current Active FE Orders </h1>';
		
		$query .="  BirthDate IS NOT NULL
					AND
					PGITime IS NULL
					AND
					FEFlag = 1
				    ORDER BY
				    Priority DESC,
				    Check_Out DESC
			"; 
	}
	

	if (true) {		
		echo '<table width=100%>';
		echo '<tr>';
			echo '<td valign="top">';
				//------print data table
				echo '<table id="major" border="1" cellpadding=2 style="color:navy;font-weight:bold;font-family:Calibri;font-size:9pt;">';
				echo '<thead>';
				echo '<tr bgcolor=navy style=color:white>';
					echo '<th>客户</th>';//-----------------------------------------For Getting CusName
					echo '<th>SO</th>';
					echo '<th>PLO</th>';
					echo '<th>Priority</th>';
					echo '<th>Family</th>';
					echo '<th>Model</th>';
					echo '<th>WO</th>';
					echo '<th>SN</th>';
					echo '<th>BKPL</th>';
					echo '<th>备料结束时间</th>';
					echo '<th>PreTest 结束时间</th>';
					echo '<th>RunIn 结束时间</th>';
					echo '<th data-tsorter="numeric">进站 TAT</th>';
					echo '<th>FE Check In</th>';
					echo '<th data-tsorter="numeric">出站 TAT</th>';
					echo '<th>FE Check Out</th>';
					echo '<th>Handover</th>';
					echo '<th>PGI</th>';
					echo '<th>备注</th>';
				echo '</tr>';
				echo '</thead>';
				
				$Priority = array('Failed' => 0, 'Passed' => 0);

				$data = mssql_query($query,$dbc) or die('search db error ');
				
				while ($row = mssql_fetch_assoc($data)){
					// formatting
					if (isset($row['BirthDate'])) {
						$row['BirthDate'] = date_format(date_create_from_format('M d Y  h:i:s:ua', $row['BirthDate']), 'Y-m-d H:i:s');
					}
					if (isset($row['WHUpdateTime'])) {
						$row['WHUpdateTime'] = date_format(date_create_from_format('M d Y  h:i:s:ua', $row['WHUpdateTime']), 'Y-m-d H:i:s');
					}
					if (isset($row['Pretest_End'])) {
						$row['Pretest_End'] = date_format(date_create_from_format('M d Y  h:i:s:ua', $row['Pretest_End']), 'Y-m-d H:i:s');
					}
					if (isset($row['Runin_Start'])) {
						$row['Runin_Start'] = date_format(date_create_from_format('M d Y  h:i:s:ua', $row['Runin_Start']), 'Y-m-d H:i:s');
					}
					if (isset($row['Runin_End'])) {
						$row['Runin_End'] = date_format(date_create_from_format('M d Y  h:i:s:ua', $row['Runin_End']), 'Y-m-d H:i:s');
					}
					if (isset($row['Check_In'])) {
						$row['Check_In'] = date_format(date_create_from_format('M d Y  h:i:s:ua', $row['Check_In']), 'Y-m-d H:i:s');
					}
					if (isset($row['Check_Out'])) {
						$row['Check_Out'] = date_format(date_create_from_format('M d Y  h:i:s:ua', $row['Check_Out']), 'Y-m-d H:i:s');
					}
					if (isset($row['HandoverTime'])) {
						$row['HandoverTime'] = date_format(date_create_from_format('M d Y  h:i:s:ua', $row['HandoverTime']), 'Y-m-d H:i:s');
					}
					if (isset($row['PGITime'])) {
						$row['PGITime'] = date_format(date_create_from_format('M d Y  h:i:s:ua', $row['PGITime']), 'Y-m-d H:i:s');
					}
					
					// determine checkout target
					foreach ($Target_TAT_Checkout_Arr as $k => $v) {
						if (stristr($row['CusName'], $k) || stristr($row['EndCusName'], $k) || stristr($row['ShippingName'], $k)) {
							$Target_TAT_Checkout = $v;
						}
					}

					// Begin to print
					if ($dev[trim($row['PLO'])] > 0 && !stristr($row['SO'], '4251')) { // Excl. Japan Orders
						echo '<tr bgcolor="#FFC7CE">';
						$Priority['Failed']++;
					} else {
						echo '<tr>';
						$Priority['Passed']++;
					}
							// 客户
							if (isset($row['EndCusName'])) {
								if (trim($row['EndCusName']) <> 'n/a') {
									echo '<td>'.trim($row['EndCusName']).'</td>';
								} else {
									if (isset($row['ShippingName'])) {
										echo '<td>'.trim($row['ShippingName']).'</td>';
									}
									else {
										echo '<td>N/A</td>';
									}
								}
							} else {
								echo '<td>N/A</td>';//-----------------------------------------For Getting CusName
							}
							// SO
							if (isset($row['SO'])) {
								echo '<td>'.$row['SO'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							// PLO
							if (isset($row['PLO'])) {
								echo '<td>'.$row['PLO'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							// Priority
							if (isset($row['Priority'])) {
								echo '<td>'.$row['Priority'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							// Family
							if (isset($row['Family'])) {
								echo '<td>'.$row['Family'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							// Model
							if (isset($row['Model'])) {
								echo '<td>'.$row['Model'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							// WO
							if (isset($row['WO'])) {
								echo '<td>'.$row['WO'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							// SN
							if (isset($row['SN'])) {
								echo '<td>'.$row['SN'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							// BKPL
							if (isset($row['BirthDate'])) {
								echo '<td>'.$row['BirthDate'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							// 备料结束时间
							if (isset($row['WHUpdateTime'])) {
								echo '<td>'.$row['WHUpdateTime'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							// PreTest 结束时间
							if (isset($row['Pretest_End'])) {
								echo '<td>'.$row['Pretest_End'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							// RunIn 结束时间
							if (isset($row['Runin_End'])) {
								echo '<td>'.$row['Runin_End'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							// 进站 TAT
							if (isset($row['Runin_End'])) {
								if (isset($row['Check_In'])) {
									$TAT_Checkin[trim($row['WO'])] = ol($row['Runin_End'], $row['Check_In']);
									if ($TAT_Checkin[trim($row['WO'])] <= $Target_TAT_Checkin) {
										echo '<td bgcolor=\'#C6EFCE\'>'.$TAT_Checkin[trim($row['WO'])].'</td>';
									} else {
										echo '<td bgcolor=\'#FFC7CE\'>'.$TAT_Checkin[trim($row['WO'])].'</td>';
									}
								} else {
									$GAP_Checkin[trim($row['WO'])] = ol($row['Runin_End'], date('Y-m-d H:i:s'));
									if ($GAP_Checkin[trim($row['WO'])] <= $Target_TAT_Checkin) {
										echo '<td bgcolor=\'#C6EFCE\'>TBD</td>';
									} else {
										echo '<td bgcolor=\'#FFC7CE\'>TBD</td>';
									}
								}
							} else {
								echo '<td>TBD</td>';
							}
							// FE Check In
							if (isset($row['Check_In'])) {
								echo '<td>'.$row['Check_In'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							// 出站 TAT
							if (isset($row['Check_In'])) {
								if (isset($row['Check_Out'])) {
									$TAT_Checkout[trim($row['WO'])] = ol($row['Check_In'], $row['Check_Out']);
									if ($TAT_Checkout[trim($row['WO'])] <= $Target_TAT_Checkout) {
										echo '<td bgcolor=\'#C6EFCE\'>'.$TAT_Checkout[trim($row['WO'])].'</td>';
									} else {
										echo '<td bgcolor=\'#FFC7CE\'>'.$TAT_Checkout[trim($row['WO'])].'</td>';
									}
								} else {
									$GAP_Checkout[trim($row['WO'])] = ol($row['Check_In'], date('Y-m-d H:i:s'));
									if ($GAP_Checkout[trim($row['WO'])] <= $Target_TAT_Checkout) {
										echo '<td bgcolor=\'#C6EFCE\'>TBD</td>';
									} else {
										echo '<td bgcolor=\'#FFC7CE\'>TBD</td>';
									}
								}
							} else {
								echo '<td>TBD</td>';
							}
							// FE Check Out
							if (isset($row['Check_Out'])) {
								echo '<td>'.$row['Check_Out'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							// Handover
							if (isset($row['HandoverTime'])) {
								echo '<td>'.$row['HandoverTime'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							// PGI
							if (isset($row['PGITime'])) {
								echo '<td>'.$row['PGITime'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							// 备注
							if (isset($row['Comment7']) && trim($row['Comment7']) <> '') {
								echo "<td bgcolor=\"#FFC7CE\"><a target = '_blank' href=\"./comment.php?PLO=".$row['PLO']."&comment=".$row['Comment7']."&comment2=".$row['Comment7_2']."\">".$row['Comment7']." - ".$row['Comment7_2']."</a></td>";
							} elseif (isset($row['Comment']) && trim($row['Comment']) <> '') {
								echo "<td bgcolor=\"#FFC7CE\"><a target = '_blank' href=\"./comment.php?PLO=".$row['PLO']."&comment=".$row['Comment']."&comment2=".$row['Comment3']."&comment3=".$row['Comment2']."\">".$row['Comment']." - ".$row['Comment2']."</a></td>";
							} else {
								echo "<td><a target = '_blank' href=\"./comment.php?PLO=".$row['PLO']."\">>Add<</a></td>";
							}
						echo '</tr>';
				}
				echo '</table>';
				echo '</td>';	
				echo '<td valign="top">';
				//---------print num table
				echo '<table border="0" cellpadding=2 style="color:navy;font-weight:bold;font-family:Calibri;font-size:9pt;">';
				
				foreach($Priority as $k => $v) {
					if ($k == 'Failed') {
						echo '<tr bgcolor="#FFC7CE">';
					}
					else {
						echo '<tr>';
					}
							echo '<td>'.$k.'</td>';
							echo '<td>'.$v.'</td>';
					echo '</tr>';
				}
				echo '</table>';
				echo '</td>';
			echo '</tr>';
		echo '</table>';
				// echo '<p >';
				
				echo '<script src="tsorter.min.js"></script>';
				echo '<script src="fnExcelReport.js"></script>';
		}
		echo '</body>';
	echo '</html>';
			
	mssql_free_result($data);
	mssql_close($dbc);


?>