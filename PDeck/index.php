<?php
//---------------------------------
	// Insert the page header
	$page_title = 'Main';
	
	require_once('header.php');
	
	require_once('navmenu.php');
	
	require_once('connectvars.php');
	
	require_once('overlap.php');//调用overlap算法
	
	// Unit: Hour
	$Target_TAT_PreAssy = 8;
	$Target_TAT_Assy = 8;
	$Target_TAT_Test = 18.5;
	$Target_TAT_HO = 7.5;
	$Target_TAT_P = 42;
	
	// $Target_TAT_Checkout_Arr = array('tencent' => 4); // define specific targets
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
			
			echo '&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp (Unit: hour)';
			
			
			echo '&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp|&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';

			echo '<iframe id="txtArea1" style="display:none"></iframe>';
			echo '<button id="btnExport" onclick="fnExcelReport();"> 导出 </button>';
		echo '</form>';
	echo '</div>';
	
	echo '</br>';
	
	// Get post data
	$submit = $_POST['submit'];
	$startdate = $_POST['startdate'];
	$enddate = $_POST['enddate'];
	$base = $_POST['base'];
	$identity = $_POST['identity'];
	
	// Enable multiple PLO/SO search
	if (in_array($identity, array('PLO', 'SO', 'DN')) && !empty($_POST['entry'])) {
		$entries = explode(" ",$_POST['entry']);
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
						T.*
					FROM
						PCTMaster
						INNER JOIN
						( SELECT 
							PLO,
							MAX (CASE WHEN Operation = 'BF20_TSG_BUILD1' THEN Operation_End END) BUILD1_End,
							MAX (CASE WHEN Operation = 'BF20_TSG_BUILD2' THEN Operation_End END) BUILD2_End,
							MAX (CASE WHEN Operation = 'BF20_TSG_BUILD3' THEN Operation_End END) BUILD3_End,
							MAX (CASE WHEN Operation = 'BF20_TSG_BUILD4' THEN Operation_End END) BUILD4_End,
							MAX (CASE WHEN Operation = 'BF20_TSG_BUILD5' THEN Operation_End END) BUILD5_End,
							MAX (CASE WHEN Operation = 'EVA_SPU_BUILD1' THEN Operation_End END) BUILD_End,
							MAX (CASE WHEN Operation IN ('PRETEST', 'EL_ENCL_TEST') THEN Operation_Start END) Pretest_Start,
							MAX (CASE WHEN Operation IN ('RUNIN', 'CCT_TEST') THEN Operation_End END) Runin_End,
							MAX (CASE WHEN Operation = 'BF20_TSG_CUSINTCHK' THEN Operation_End END) Check_Out
						  FROM 
							OCT
						  GROUP BY
							PLO
						) T
						ON PCTMaster.PLO = T.PLO
						LEFT JOIN
						ProductFamily
						ON PCTMaster.Family=ProductFamily.ProductFamily AND (PCTMaster.ConfigType=ProductFamily.ConfigType OR PCTMaster.ConfigType is NULL)
					WHERE
						Family LIKE '%TSG%'
						AND
						ProductFamily.ConfigType NOT IN ('PPS Option', 'PPS Option 3F')
						AND
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
	} elseif (isset($_GET['PLO'])) {
			$query .=" T.PLO IN ({$_GET['PLO']})
				";
	} else { // by default
		$query .="  BirthDate IS NOT NULL
					AND
					PGITime IS NULL
				    ORDER BY
				    HandoverTime DESC
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
					echo '<th>SO</th>';
					echo '<th>PLO</th>';
					echo '<th>Family</th>';
					echo '<th>Model</th>';
					echo '<th>SKU#</th>';
					echo '<th>QTY</th>';
					echo '<th>FE?</th>';
					echo '<th>WH备料结束时间</th>';
					echo '<th data-tsorter="numeric">PreAssy TAT</th>';
					echo '<th>P备料结束时间</th>';
					echo '<th data-tsorter="numeric">Assy TAT</th>';
					echo '<th>装配结束时间</th>';
					echo '<th data-tsorter="numeric">Test TAT</th>';
					echo '<th>测试结束时间</th>';
					echo '<th data-tsorter="numeric">Handover TAT</th>';
					echo '<th>Handover</th>';
					echo '<th data-tsorter="numeric">P TAT</th>';
					echo '<th>备注</th>';
					echo '<th>详情</th>';
				echo '</tr>';
				echo '</thead>';
				
				$Priority = array('Failed' => 0, 'Passed' => 0);

				$data = mssql_query($query,$dbc) or die('search db error ');
				
				while ($row = mssql_fetch_assoc($data)){
					// formatting
					if (isset($row['WHUpdateTime'])) {
						$row['WHUpdateTime'] = date_format(date_create_from_format('M d Y  h:i:s:ua', $row['WHUpdateTime']), 'Y-m-d H:i:s');
					}
					if (isset($row['MailSendTime'])) {
						$row['MailSendTime'] = date_format(date_create_from_format('M d Y  h:i:s:ua', $row['MailSendTime']), 'Y-m-d H:i:s');
					}
					if (isset($row['LineInputTime'])) {
						$row['LineInputTime'] = date_format(date_create_from_format('M d Y  h:i:s:ua', $row['LineInputTime']), 'Y-m-d H:i:s');
					}
					if (isset($row['BUILD_End'])) {
						$AssyPass[trim($row['PLO'])] = date_format(date_create_from_format('M d Y  h:i:s:ua', $row['BUILD_End']), 'Y-m-d H:i:s');
					} else {
						if (isset($row['Pretest_Start'])) {
							$tmp = max($row['BUILD1_End'], $row['BUILD2_End'], $row['BUILD3_End'], $row['BUILD4_End'], $row['BUILD5_End']);
							$AssyPass[trim($row['PLO'])] = date_format(date_create_from_format('M d Y  h:i:s:ua', $tmp), 'Y-m-d H:i:s');
						}
					}
					if ($row['FEFlag'] === 1 ) {
						if (isset($row['Check_Out'])) {
							$TestPass[trim($row['PLO'])] = date_format(date_create_from_format('M d Y  h:i:s:ua', $row['Check_Out']), 'Y-m-d H:i:s');
						}
					} else {
						if (isset($row['Runin_End'])) {
							$TestPass[trim($row['PLO'])] = date_format(date_create_from_format('M d Y  h:i:s:ua', $row['Runin_End']), 'Y-m-d H:i:s');
						}
					}
					if (isset($row['HandoverTime'])) {
						$row['HandoverTime'] = date_format(date_create_from_format('M d Y  h:i:s:ua', $row['HandoverTime']), 'Y-m-d H:i:s');
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
							// SKU
							if (isset($row['Product'])) {
								echo '<td>'.$row['Product'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							// QTY
							if (isset($row['PLOQTY'])) {
								echo '<td>'.$row['PLOQTY'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							// Is Fe Order?
							if (isset($row['FEFlag'])) {
								if ($row['FEFlag'] == 1) {
									echo '<td>Y</td>';		
								} else {
									echo '<td>N</td>';
								}
							} else {
								echo '<td>TBD</td>';
							}
							// 备料结束时间 - WH
							if (isset($row['WHUpdateTime'])) {
								echo '<td>'.$row['WHUpdateTime'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							// PreAssy TAT
							if (isset($row['WHUpdateTime'])) {
								if (isset($row['LineInputTime'])) {
									$TAT_PreAssy[trim($row['PLO'])] = ol($row['WHUpdateTime'], $row['LineInputTime']);
									if ($TAT_PreAssy[trim($row['PLO'])] <= $Target_TAT_PreAssy) {
										echo '<td bgcolor=\'#C6EFCE\'>'.$TAT_PreAssy[trim($row['PLO'])].'</td>';
									} else {
										echo '<td bgcolor=\'#FFC7CE\'>'.$TAT_PreAssy[trim($row['PLO'])].'</td>';
									}
								} else {
									$GAP_PreAssy[trim($row['PLO'])] = ol($row['WHUpdateTime'], date('Y-m-d H:i:s'));
									if ($GAP_PreAssy[trim($row['PLO'])] <= $Target_TAT_PreAssy) {
										echo '<td bgcolor=\'#C6EFCE\'>剩余: '.($Target_TAT_PreAssy - $GAP_PreAssy[trim($row['PLO'])]).'</td>';
									} else {
										echo '<td bgcolor=\'#FFC7CE\'>超时: '.($GAP_PreAssy[trim($row['PLO'])] - $Target_TAT_PreAssy).'</td>';
									}
								}
							} else {
								echo '<td>TBD</td>';
							}
							// 备料结束时间 - P
							if (isset($row['LineInputTime'])) {
								echo '<td>'.$row['LineInputTime'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							// Assy TAT
							if (isset($row['LineInputTime'])) {
								if (isset($AssyPass[trim($row['PLO'])])) {
									$TAT_Assy[trim($row['PLO'])] = ol($row['LineInputTime'], $AssyPass[trim($row['PLO'])]);
									if ($TAT_Assy[trim($row['PLO'])] <= $Target_TAT_Assy) {
										echo '<td bgcolor=\'#C6EFCE\'>'.$TAT_Assy[trim($row['PLO'])].'</td>';
									} else {
										echo '<td bgcolor=\'#FFC7CE\'>'.$TAT_Assy[trim($row['PLO'])].'</td>';
									}
								} else {
									$GAP_Assy[trim($row['PLO'])] = ol($row['LineInputTime'], date('Y-m-d H:i:s'));
									if ($GAP_Assy[trim($row['PLO'])] <= $Target_TAT_Assy) {
										echo '<td bgcolor=\'#C6EFCE\'>剩余: '.($Target_TAT_Assy - $GAP_Assy[trim($row['PLO'])]).'</td>';
									} else {
										echo '<td bgcolor=\'#FFC7CE\'>超时: '.($GAP_Assy[trim($row['PLO'])] - $Target_TAT_Assy).'</td>';
									}
								}
							} else {
								echo '<td>TBD</td>';
							}
							// 装配结束时间 - Assy Pass
							if (isset($AssyPass[trim($row['PLO'])])) {
								echo '<td>'.$AssyPass[trim($row['PLO'])].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							// Test TAT
							if (isset($AssyPass[trim($row['PLO'])])) {
								if (isset($TestPass[trim($row['PLO'])])) {
									$TAT_Test[trim($row['PLO'])] = ol($AssyPass[trim($row['PLO'])], $TestPass[trim($row['PLO'])]);
									if ($TAT_Test[trim($row['PLO'])] <= $Target_TAT_Test) {
										echo '<td bgcolor=\'#C6EFCE\'>'.$TAT_Test[trim($row['PLO'])].'</td>';
									} else {
										echo '<td bgcolor=\'#FFC7CE\'>'.$TAT_Test[trim($row['PLO'])].'</td>';
									}
								} else {
									$GAP_Test[trim($row['PLO'])] = ol($AssyPass[trim($row['PLO'])], date('Y-m-d H:i:s'));
									if ($GAP_Test[trim($row['PLO'])] <= $Target_TAT_Test) {
										echo '<td bgcolor=\'#C6EFCE\'>剩余: '.($Target_TAT_Test - $GAP_Test[trim($row['PLO'])]).'</td>';
									} else {
										echo '<td bgcolor=\'#FFC7CE\'>超时: '.($GAP_Test[trim($row['PLO'])] - $Target_TAT_Test).'</td>';
									}
								}
							} else {
								echo '<td>TBD</td>';
							}
							// 测试结束时间 - Test Pass
							if (isset($TestPass[trim($row['PLO'])])) {
								echo '<td>'.$TestPass[trim($row['PLO'])].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							// HO TAT
							if (isset($TestPass[trim($row['PLO'])])) {
								if (isset($row['HandoverTime'])) {
									$TAT_HO[trim($row['PLO'])] = ol($TestPass[trim($row['PLO'])], $row['HandoverTime']);
									if ($TAT_HO[trim($row['PLO'])] <= $Target_TAT_HO) {
										echo '<td bgcolor=\'#C6EFCE\'>'.$TAT_HO[trim($row['PLO'])].'</td>';
									} else {
										echo '<td bgcolor=\'#FFC7CE\'>'.$TAT_HO[trim($row['PLO'])].'</td>';
									}
								} else {
									$GAP_HO[trim($row['PLO'])] = ol($TestPass[trim($row['PLO'])], date('Y-m-d H:i:s'));
									if ($GAP_HO[trim($row['PLO'])] <= $Target_TAT_HO) {
										echo '<td bgcolor=\'#C6EFCE\'>剩余: '.($Target_TAT_HO - $GAP_HO[trim($row['PLO'])]).'</td>';
									} else {
										echo '<td bgcolor=\'#FFC7CE\'>超时: '.($GAP_HO[trim($row['PLO'])] - $Target_TAT_HO).'</td>';
									}
								}
							} else {
								echo '<td>TBD</td>';
							}
							// Handover
							if (isset($row['HandoverTime'])) {
								echo '<td>'.$row['HandoverTime'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							// P TAT
							if (isset($row['WHUpdateTime'])) {
								if (isset($row['HandoverTime'])) {
									$TAT_P[trim($row['PLO'])] = ol($row['WHUpdateTime'], $row['HandoverTime']);
									if ($TAT_P[trim($row['PLO'])] <= $Target_TAT_P) {
										echo '<td bgcolor=\'#C6EFCE\'>'.$TAT_P[trim($row['PLO'])].'</td>';
									} else {
										echo '<td bgcolor=\'#FFC7CE\'>'.$TAT_P[trim($row['PLO'])].'</td>';
									}
								} else {
									$GAP_P[trim($row['PLO'])] = ol($row['WHUpdateTime'], date('Y-m-d H:i:s'));
									if ($GAP_P[trim($row['PLO'])] <= $Target_TAT_P) {
										echo '<td bgcolor=\'#C6EFCE\'>剩余: '.($Target_TAT_P - $GAP_P[trim($row['PLO'])]).'</td>';
									} else {
										echo '<td bgcolor=\'#FFC7CE\'>超时: '.($GAP_P[trim($row['PLO'])] - $Target_TAT_P).'</td>';
									}
								}
							} else {
								echo '<td>TBD</td>';
							}
							// 备注
							if ($TAT_P[trim($row['PLO'])] > $Target_TAT_P || $TAT_PreAssy[trim($row['PLO'])] > $Target_TAT_PreAssy || $TAT_Assy[trim($row['PLO'])] > $Target_TAT_Assy || $TAT_Test[trim($row['PLO'])] > $Target_TAT_Test || $TAT_HO[trim($row['PLO'])] > $Target_TAT_HO) {
								if (isset($row['Comment4']) && trim($row['Comment4']) <> '') {
									echo "<td bgcolor=\"#FFC7CE\"><a target = '_blank' href=\"./comment.php?PLO=".$row['PLO']."&comment=".$row['Comment4']."&comment2=".$row['Comment5']."&comment3=".$row['Comment6']."\">".$row['Comment4']." - ".$row['Comment5']."</a></td>";
									echo "<td bgcolor=\"#FFC7CE\">".$row['Comment6']."</td>";
								}  else {
									echo "<td bgcolor=\"#FFC7CE\"><a target = '_blank' href=\"./comment.php?PLO=".$row['PLO']."\">>Add<</a></td>";
									echo "<td bgcolor=\"#FFC7CE\"></td>";
								}
							} else {
								echo '<td>N/A</td>';
								echo '<td>N/A</td>';
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