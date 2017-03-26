<?php
//---------------------------------
	// Insert the page header
	$page_title = 'Main';
	require_once('header.php');
	
	$page = $_SERVER['PHP_SELF'];
	$sec = "99999";
	header("Refresh: $sec; url=$page");
	
	require_once('navmenu.php');
	
	require_once('connectvars.php');
	
	date_default_timezone_set("Asia/Shanghai");
	
	require_once('overlap.php');//调用overlap算法
	
	$Target_TAT_RM = 6;
	
	$Target_TAT_RM_PCT = 24;
	
	$Target_TAT_PGI_PCT = 6;
	
	$Target_TAT_OP_PCT = 24;
	
	$Target_TAT_OP_PCT_Real = 48;
//---------------------------------------------------------------------------------------------------------------------------------------------------

	echo '<div class="input">';
	
		echo '<form method="post" action="'.$_SERVER['PHP_SELF'].'">';
			//search by Model, Client, PLO, SO ...
			echo '<select name="identity" id="identity">';
				// echo '<option value="Model">Model</option>';
				// echo '<option value="Client">Client Name</option>';
				echo '<option value="PLO" selected>PLO</option>';
				echo '<option value="SO">SO</option>';
				echo '<option value="DN">DN</option>';
				echo '<option value="ProductFamily.Picklist_PL">生产车间</option>';
				echo '<option value="OnlineNo">备料批次</option>';
				echo '<option value="Comment8">Short P/N</option>';
			echo '</select> &nbsp';
			
			if (isset($_POST['identity'])) {
				echo '<script type="text/javascript">';
					echo 'document.getElementById(\'identity\').value = "'.$_POST['identity'].'"';
				echo '</script>';
			}
			
			echo '<input id="entry" name="entry" value="'.htmlspecialchars($_POST['entry']).'"/> &nbsp';

			// echo '<input type="checkbox" name="nonpgiflag" id="nonpgiflag" value="1"'; if(isset($_POST['nonpgiflag'])) echo "checked='checked'"; echo '> Show All Non-PGI &nbsp';
			
			//select time base
			echo '<select name="base" id="base">';
				echo '<option value="BirthDate" selected>BKPL</option>';
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
			
			echo '<input type="submit" name="submit" value="Search" />';
			
			echo '&nbsp&nbsp&nbsp&nbsp|&nbsp&nbsp&nbsp&nbsp';
			
			echo '<input type="submit" name="unconfirmed" value="Unconfirmed Picklists" />';
			
			echo '&nbsp&nbsp&nbsp&nbsp|&nbsp&nbsp&nbsp&nbsp';
			
			echo '<input type="submit" name="nonpgi" value="Show All Handovered Non-PGI" />';

			echo '&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp (Unit: hour) &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';

			echo '<iframe id="txtArea1" style="display:none"></iframe>';
			echo '<button id="btnExport" onclick="fnExcelReport();"> EXPORT </button>';
		echo '</form>';
	echo '</div>';
	
	echo '</br>';
	
	// Get post data
	$submit = $_POST['submit'];
	$unconfirmed = $_POST['unconfirmed'];
	$startdate = $_POST['startdate'];
	$enddate = $_POST['enddate'];
	$base = $_POST['base'];
	$nonpgi = $_POST['nonpgi'];
	$identity = $_POST['identity'];
	// enable multiple PLO/SO search
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
	
		$query = "SELECT 
					PCTMaster.*,
					CASE 
						WHEN PCTMaster.PLO IS NULL THEN Picklist.PLO 
						WHEN Picklist.PLO IS NULL THEN PCTMaster.PLO 
						ELSE PCTMaster.PLO END PLO,
					Picklist.Picklist_PL,
					ProductFamily.Picklist_PL AS PF_PL,
					Picklist_Time,
					Comment7,
					Comment7_2,
					Comment8,
					Comment9,
					Comment10,
					Comment11,
					Comment12
				  FROM 
					PCTMaster
					FULL OUTER JOIN
					Picklist
					ON PCTMaster.PLO=Picklist.PLO
					LEFT JOIN ProductFamily
					ON PCTMaster.Family=ProductFamily.ProductFamily AND (PCTMaster.ConfigType=ProductFamily.ConfigType OR PCTMaster.ConfigType is NULL)
				  WHERE
					(SO NOT LIKE 'PRP%' OR SO IS NULL)
					AND
					";
	if (isset($submit)) {
		if (isset($entry) && !empty($entry) && $identity <> 'ProductFamily.Picklist_PL') {
			if (in_array($identity, array('SO', 'DN'))) {
				$query .="  Picklist.PLO IS NOT NULL
							AND
							PCTMaster.{$identity} IN ({$entry})
				";
				} elseif ($identity == 'PLO') {
					$query .="  Picklist.PLO IS NOT NULL
								AND
								Picklist.{$identity} IN ({$entry})
					";
				} else {
				$query .="  Picklist.PLO IS NOT NULL
							AND
							{$identity} LIKE '%{$entry}%'
				";
				}
		} else {
			if (isset($base) && isset($startdate) && isset($enddate)) {
				if ($identity == 'ProductFamily.Picklist_PL') {
					$query .="  Picklist.PLO IS NOT NULL
							AND
							{$identity} LIKE '%{$entry}%'
							AND
							{$base} BETWEEN '{$startdate}' and '{$enddate}'
							ORDER BY
							{$base} DESC 
					";
				} else{
					$query .="  Picklist.PLO IS NOT NULL
							AND
							{$base} BETWEEN '{$startdate}' and '{$enddate}'
							ORDER BY
							{$base} DESC 
					";
				}
			}
		}
	} elseif (isset($nonpgi)) { // Show All Handovered Non-PGI
		$query .="  Picklist.PLO IS NOT NULL
					AND
					HandoverTime IS NOT NULL
					AND
					PGITime IS NULL
					AND
					(Comment10 <> 'CANCEL' OR Comment10 IS NULL)
				    ORDER BY
				    {$base} DESC 
				";
	} elseif (isset($unconfirmed)) { // Show Not-Yet-Picklisted PLOs of today	
		echo '<h1>2016-10-01 后未确认的 PICKLIST </h1>';
		
		$query .=" Picklist.PLO IS NULL
				   AND
				   BirthDate >= '2016-10-01 00:00:00'
				   ORDER BY
				   BirthDate DESC
		";	
	  // by default
	} else {
		echo '<h1>两天内所有新订单 </h1>';
		
		$query .="  Picklist.PLO IS NOT NULL
					AND
					BirthDate >= dateadd(day,datediff(day,1,GETDATE()),0) OR Picklist_Time >= dateadd(day,datediff(day,1,GETDATE()),0)
				    ORDER BY
				    BirthDate DESC
			"; 
	}
	// print_r($query);

	if (true) {		
		echo '<table width=100%>';
		echo '<tr>';
			echo '<td valign="top">';
				//------print data table
				echo '<table id="major" border="1" cellpadding=2 style="color:navy;font-weight:bold;font-family:Calibri;font-size:9pt;">';
				echo '<thead>';
				echo '<tr bgcolor=navy style=color:white>';
					echo '<th>备料批次</th>';
					echo '<th>BKPL</th>';
					echo '<th>Family</th>';
					echo '<th>生产车间</th>';
					echo '<th>Picklist</th>';
					echo '<th>SO</th>';
					echo '<th>PLO</th>';
					echo '<th>BPO</th>';
					echo '<th>ShipRef</th>';
					echo '<th>Product</th>';
					echo '<th>QTY</th>';
					echo '<th>PL</th>';
					echo '<th>备料开始时间</th>';
					echo '<th>备料结束时间</th>';
					echo '<th>生产收料时间</th>';
					echo '<th data-tsorter="numeric">备料 TAT</th>';
					echo '<th>备料 or not 6H</th>';
					echo '<th>RM 备注</th>';
					echo '<th>short P/N</th>';
					echo '<th>short 到料时间</th>';
					echo '<th data-tsorter="numeric">RM PCT TAT</th>';
					echo '<th>RM PCT or not 24H</th>';
					echo '<th data-tsorter="numeric">RTP-收到时间 TAT</th>';
					echo '<th>DN</th>';
					echo '<th>Putaway</th>';
					echo '<th>Handover</th>';
					echo '<th>PGI</th>';
					echo '<th data-tsorter="numeric">HO-PGI TAT</th>';
					echo '<th>PGI or not 4h</th>';
					echo '<th>FG备注</th>';
					echo '<th data-tsorter="numeric">OP PCT</th>';
				echo '</tr>';
				echo '</thead>';
				
				$Priority = array('Failed' => 0, 'Passed' => 0);
				
				$Sum_QTY = 0;

				$data = mssql_query($query,$dbc) or die('search db error ');
				
				while ($row = mssql_fetch_assoc($data)){
					// calc dev
					if (isset($row['BirthDate'])) {
						$row['BirthDate'] = date_format(date_create_from_format('M d Y  h:i:s:ua', $row['BirthDate']), 'Y-m-d H:i:s');
					}
					if (isset($row['WHInputTime'])) {
						$row['WHInputTime'] = date_format(date_create_from_format('M d Y  h:i:s:ua', $row['WHInputTime']), 'Y-m-d H:i:s');
					}
					if (isset($row['WHUpdateTime'])) {
						$row['WHUpdateTime'] = date_format(date_create_from_format('M d Y  h:i:s:ua', $row['WHUpdateTime']), 'Y-m-d H:i:s');
					}
					if (isset($row['LineInputTime'])) {
						$row['LineInputTime'] = date_format(date_create_from_format('M d Y  h:i:s:ua', $row['LineInputTime']), 'Y-m-d H:i:s');
					}
					if (isset($row['HandoverTime'])) {
						$row['HandoverTime'] = date_format(date_create_from_format('M d Y  h:i:s:ua', $row['HandoverTime']), 'Y-m-d H:i:s');
					}
					if (isset($row['PGITime'])) {
						$row['PGITime'] = date_format(date_create_from_format('M d Y  h:i:s:ua', $row['PGITime']), 'Y-m-d H:i:s');
					}
					if (isset($row['Picklist_Time'])) {
						$row['Picklist_Time'] = date_format(date_create_from_format('M d Y  h:i:s:ua', $row['Picklist_Time']), 'Y-m-d H:i:s');
					}
					if (isset($row['Comment9'])) {
						$row['Comment9'] = date_format(date_create_from_format('M d Y  h:i:s:ua', $row['Comment9']), 'Y-m-d H:i:s');
					}
					
					// Begin to print
					if ($dev[trim($row['PLO'])] > 0 && !stristr($row['SO'], '4251')) { // Excl. Japan Orders
						echo '<tr bgcolor="#FFC7CE">';
						$Priority['Failed']++;
					} else {
						echo '<tr>';
						$Priority['Passed']++;
						$Sum_QTY += $row['PLOQTY'];
					}
							if (isset($row['PF_PL']) && $row['PF_PL'] == 'OP') {
								echo '<td>N/A</td>';
							} else {
								if (isset($row['OnlineNo'])) {
									echo '<td>'.$row['OnlineNo'].'</td>';
								} else {
									echo '<td>TBD</td>';
								}
							}
							if (isset($row['BirthDate'])) {
								echo '<td>'.$row['BirthDate'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							if (isset($row['Family'])) {
								echo '<td>'.$row['Family'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							if (isset($row['PF_PL'])) {
								echo '<td>'.$row['PF_PL'].'</td>'; //-----------------------------------------生产车间
							} else {
								if (isset($row['Picklist_PL'])) {
									echo '<td>'.$row['Picklist_PL'].'</td>';
								} else {
									echo '<td>TBD</td>';
								}
							}
							if (isset($row['Picklist_Time'])) {
								echo '<td>'.$row['Picklist_Time'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							if (isset($row['SO'])) {
								echo '<td>'.$row['SO'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							if (isset($row['PLO'])) {
								echo '<td>'.$row['PLO'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							if (isset($row['BPO'])) {
								echo '<td>'.$row['BPO'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							if (isset($row['ShipRef'])) {
								echo '<td>'.$row['ShipRef'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							if (isset($row['Product'])) {
								echo '<td>'.$row['Product'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							if (isset($row['PLOQTY'])) {
								echo '<td>'.$row['PLOQTY'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							if (isset($row['PL'])) {
								echo '<td>'.$row['PL'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							//备料开始时间等
							if (isset($row['PF_PL']) && $row['PF_PL'] == 'OP') {
								echo '<td>N/A</td>';
								echo '<td>N/A</td>';
								echo '<td>N/A</td>';
							} else {
								if (isset($row['WHInputTime'])) {
									echo '<td>'.$row['WHInputTime'].'</td>';
								} else {
									echo '<td>TBD</td>';
								}
								if (isset($row['WHUpdateTime'])) {
									echo '<td>'.$row['WHUpdateTime'].'</td>';
								} else {
									echo '<td>TBD</td>';
								}
								if (isset($row['LineInputTime'])) {
									echo '<td>'.$row['LineInputTime'].'</td>';
								} else {
									echo '<td>TBD</td>';
								}
							}
							// 备料TAT
							if (isset($row['PF_PL'])) {
								if ($row['PF_PL'] == 'OP') {
									echo '<td>N/A</td>';
									echo '<td>N/A</td>';
								} else {
									if (isset($row['WHUpdateTime'])) {
										$TAT_RM[trim($row['PLO'])] = ol_wh($row['Picklist_Time'], $row['WHUpdateTime']);										
										if ($TAT_RM[trim($row['PLO'])] <= $Target_TAT_RM) {
											echo '<td bgcolor=\'#C6EFCE\'>'.$TAT_RM[trim($row['PLO'])].'</td>';
											echo '<td bgcolor=\'#C6EFCE\'>Pass</td>';
										} else {
											echo '<td bgcolor=\'#FFC7CE\'>'.$TAT_RM[trim($row['PLO'])].'</td>';
											echo '<td bgcolor=\'#FFC7CE\'>Fail</td>';
										}
									} else {										
										$GAP_RM[trim($row['PLO'])] = ol_wh($row['Picklist_Time'], date('Y-m-d H:i:s'));
										if ($GAP_RM[trim($row['PLO'])] <= $Target_TAT_RM) {
											echo '<td bgcolor=\'#C6EFCE\'>TBD</td>';
											echo '<td bgcolor=\'#C6EFCE\'>TBD</td>';
										} else {
											echo '<td bgcolor=\'#FFC7CE\'>TBD</td>';
											echo "<td bgcolor=\"#FFC7CE\">Fail</td>";
										}
									}
								}
							} else {
								echo '<td>TBD</td>';
								echo '<td>TBD</td>';
							}
							// RM 备注
							if (isset($row['Comment7']) && trim($row['Comment7']) <> '') {
								if ($row['Comment7'] <> 'short') {
									echo "<td bgcolor=\"#FFC7CE\"><a target = '_blank' href=\"./comment.php?PLO=".$row['PLO']."&comment=".$row['Comment7']."&comment2=".$row['Comment7_2']."\">".$row['Comment7']." - ".$row['Comment7_2']."</a></td>";
								} else {
									echo "<td bgcolor=\"#FFC7CE\"><a target = '_blank' href=\"./comment.php?PLO=".$row['PLO']."&comment=".$row['Comment7']."&comment2=".$row['Comment8']."&eta=".$row['Comment9']."\">".$row['Comment7']."</a></td>";
								}
							} elseif (isset($row['Comment']) && trim($row['Comment']) <> '') {
								echo "<td bgcolor=\"#FFC7CE\"><a target = '_blank' href=\"./comment.php?PLO=".$row['PLO']."&comment=".$row['Comment']."&comment2=".$row['Comment3']."&comment3=".$row['Comment2']."\">".$row['Comment']." - ".$row['Comment2']."</a></td>";
							} else {
								echo "<td><a target = '_blank' href=\"./comment.php?PLO=".$row['PLO']."\">>Add<</a></td>";
							}
							// short 
							if (isset($row['Comment8']) && trim($row['Comment8']) <> '') {
								echo "<td><a target = '_blank' href=\"./comment.php?PLO=".$row['PLO']."&comment=".$row['Comment7']."&comment2=".$row['Comment8']."&eta=".$row['Comment9']."\">".$row['Comment8']."</a></td>";
								echo "<td><a target = '_blank' href=\"./comment.php?PLO=".$row['PLO']."&comment=".$row['Comment7']."&comment2=".$row['Comment8']."&eta=".$row['Comment9']."\">".$row['Comment9']."</a></td>";
							} else {
								echo "<td><a target = '_blank' href=\"./comment.php?PLO=".$row['PLO']."&comment=short\">>Add<</a></td>";
								echo "<td><a target = '_blank' href=\"./comment.php?PLO=".$row['PLO']."&comment=short\">>Add<</a></td>";
							}
							
							// RM PCT TAT
							if (isset($row['PF_PL']) && $row['PF_PL'] == 'OP') {
								echo '<td>N/A</td>';
								echo '<td>N/A</td>';
							} else {
								if (isset($row['BirthDate'])) {
									if (isset($row['WHUpdateTime'])) {
										$TAT_RM_PCT[trim($row['PLO'])] = ol_wh($row['BirthDate'], $row['WHUpdateTime']);											
										if ($TAT_RM_PCT[trim($row['PLO'])] <= $Target_TAT_RM_PCT) {
											echo '<td bgcolor=\'#C6EFCE\'>'.$TAT_RM_PCT[trim($row['PLO'])].'</td>';
											echo '<td bgcolor=\'#C6EFCE\'>Pass</td>';
										} else {
											echo '<td bgcolor=\'#FFC7CE\'>'.$TAT_RM_PCT[trim($row['PLO'])].'</td>';
											echo '<td bgcolor=\'#FFC7CE\'>Fail</td>';
										}
									} else {											
										$GAP_RM_PCT[trim($row['PLO'])] = ol_wh($row['BirthDate'], date('Y-m-d H:i:s'));
										if ($GAP_RM_PCT[trim($row['PLO'])] <= $Target_TAT_RM_PCT) {
											echo '<td>TBD</td>';
											echo '<td>TBD</td>';
										} else {
											echo '<td bgcolor=\'#FFC7CE\'>TBD</td>';
											echo '<td bgcolor=\'#FFC7CE\'>Fail</td>';
										}
									}
								} else {
									echo '<td>TBD</td>';
									echo '<td>TBD</td>';
								}
							}
							//RTP-收到时间 TAT
							if (isset($row['BirthDate']) && isset($row['Picklist_Time'])) {
								$TAT_Picklist[trim($row['PLO'])] = ol_wh($row['BirthDate'], $row['Picklist_Time']);
								echo '<td>'.$TAT_Picklist[trim($row['PLO'])].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							//DN, Putaway, etc.
							if (isset($row['DN'])) {
								echo '<td>'.$row['DN'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							if (isset($row['Putaway'])) {
								if ($row['Putaway'] == 0) {
									echo '<td>N</td>';
								} else {
									echo '<td>Y</td>';
								}
							} else {
								echo '<td>TBD</td>';
							}
							if (isset($row['HandoverTime'])) {
								echo '<td>'.$row['HandoverTime'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							if (isset($row['PGITime'])) {
								echo '<td>'.$row['PGITime'].'</td>';
							} else {
								echo '<td>TBD</td>';
							}
							// HO-PGI or not 4h
							if (isset($row['HandoverTime'])) {
								if (isset($row['PGITime'])) {
									$TAT_PGI_PCT[trim($row['PLO'])] = ol_wh($row['HandoverTime'], $row['PGITime']);										
									if ($TAT_PGI_PCT[trim($row['PLO'])] <= $Target_TAT_PGI_PCT) {
										echo '<td bgcolor=\'#C6EFCE\'>'.$TAT_PGI_PCT[trim($row['PLO'])].'</td>';
										echo '<td bgcolor=\'#C6EFCE\'>Pass</td>';
									} else {
										echo '<td bgcolor=\'#FFC7CE\'>'.$TAT_PGI_PCT[trim($row['PLO'])].'</td>';
										echo '<td bgcolor=\'#FFC7CE\'>Fail</td>';
									}
								} else {										
									$GAP_PGI_PCT[trim($row['PLO'])] = ol_wh($row['HandoverTime'], date('Y-m-d H:i:s'));
									if ($GAP_PGI_PCT[trim($row['PLO'])] <= $Target_TAT_PGI_PCT) {
										echo '<td bgcolor=\'#C6EFCE\'>TBD</td>';
										echo '<td bgcolor=\'#C6EFCE\'>TBD</td>';
									} else {
										echo '<td bgcolor=\'#FFC7CE\'>TBD</td>';
										echo "<td bgcolor=\"#FFC7CE\">Fail</td>";
									}
								}
							} else {
								echo '<td>TBD</td>';
								echo '<td>TBD</td>';
							}
							// FG备注
							if (isset($row['Comment10']) && trim($row['Comment10']) <> '') {
								echo "<td bgcolor=\"#FFC7CE\"><a target = '_blank' href=\"./comment3.php?PLO=".$row['PLO']."&comment=".$row['Comment10']."&comment2=".$row['Comment12']."&comment3=".$row['Comment11']."\">".$row['Comment10']." - ".$row['Comment11']." - ".$row['Comment12']."</a></td>";
							} elseif (isset($row['Comment']) && trim($row['Comment']) <> '') {
								echo "<td bgcolor=\"#FFC7CE\"><a target = '_blank' href=\"./comment3.php?PLO=".$row['PLO']."&comment=".$row['Comment']."&comment2=".$row['Comment3']."&comment3=".$row['Comment2']."\">".$row['Comment']." - ".$row['Comment2']."</a></td>";
							} else {
								echo "<td><a target = '_blank' href=\"./comment3.php?PLO=".$row['PLO']."\">>Add<</a></td>";
							}
							// OP PCT 
							if (isset($row['PF_PL'])) {
								if ($row['PF_PL'] == 'OP') {
									if (isset($row['BirthDate'])) {
										if (isset($row['PGITime'])) {
											$TAT_OP_PCT[trim($row['PLO'])] = ol_wh($row['BirthDate'], $row['PGITime']);											
											if ($TAT_OP_PCT[trim($row['PLO'])] <= $Target_TAT_OP_PCT_Real) {
												if ($TAT_OP_PCT[trim($row['PLO'])] <= $Target_TAT_OP_PCT) {
													echo '<td bgcolor=\'#C6EFCE\'>'.$TAT_OP_PCT[trim($row['PLO'])].'</td>';
												} else {
													echo '<td bgcolor=\'#FFFF99\'>'.$TAT_OP_PCT[trim($row['PLO'])].'</td>';
												}
											} else {
												echo '<td bgcolor=\'#FFC7CE\'>'.$TAT_OP_PCT[trim($row['PLO'])].'</td>';
											}
										} else {											
											$GAP_OP_PCT[trim($row['PLO'])] = ol_wh($row['BirthDate'], date('Y-m-d H:i:s'));
											if ($GAP_OP_PCT[trim($row['PLO'])] <= $Target_TAT_OP_PCT_Real) {
												if ($GAP_OP_PCT[trim($row['PLO'])] <= $Target_TAT_OP_PCT_Real) {
													echo '<td bgcolor=\'#C6EFCE\'>TBD</td>';
												} else {
													echo '<td bgcolor=\'#FFFF99\'>TBD</td>';
												}	
											} else {
												echo '<td bgcolor=\'#FFC7CE\'>TBD</td>';
											}
										}
									} else {
										echo '<td>TBD</td>';
									}
								} else {
									echo '<td>N/A</td>';
								}
							} else {
								echo '<td>N/A</td>';
							}
						echo '</tr>';
				}
				echo '</table>';
				echo '</td>';	
				echo '<td valign="top">';
				//---------print num table
				echo '<table border="0" cellpadding=2 style="color:navy;font-weight:bold;font-family:Calibri;font-size:9pt;">';
				
				// foreach($Priority as $k => $v) {
					// if ($k == 'Failed') {
						// echo '<tr bgcolor="#FFC7CE">';
					// }
					// else {
						// echo '<tr>';
					// }
							// echo '<td>'.$k.'</td>';
							// echo '<td>'.$v.'</td>';
					// echo '</tr>';
				// }
				
				echo '<tr>';
					echo '<td>QTY 合计</td>';
					echo '<td>'.$Sum_QTY.'</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>PLO 总数</td>';
					echo '<td>'.$Priority['Passed'].'</td>';
				echo '</tr>';
				
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