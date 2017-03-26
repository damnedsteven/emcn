<?php
	date_default_timezone_set('Asia/Shanghai');
	ini_set('max_execution_time', 1200); //1200 seconds = 20 minutes

	// if (isset($PLOArr)) {		
		// foreach ($PLOArr as $PLO) {
			$PLO = '5001465113';
			// -------------------------------------------------------------------------------------------------- Parse url_Default (BKPL)
			// if ($Info[$PLO]['BKPL'] == 0) {
				if (true) {
				// To get RTP date, Product Family, Product Model, BPO, Product
				$url_Default = 'http://shopfloor-apj.sfng.int.hpe.com/sfweb/DefaultQueryReport?plannedOrderPattern='.$PLO;
				
				$info_Default = file_get_contents($url_Default);//解析网页
				
				if (isset($info_Default)) {
					$arr_Default = array(0 => 'Sales Order', 4 => 'Object Id', 5 => 'Master Product', 7 => 'Family', 8 => 'Product Model', 13 => 'Merging Group', 15 => 'Birth Stamp'); // define parsing item
				
					foreach ($arr_Default as $k => $v) {
						preg_match("/>".$v."<(.*)<\/table>/isU",$info_Default,$content_Default);//在整个网页中，抓取包含相关内容

						if (!empty($content_Default)) {
							preg_match("/<tr(.*)<\/tr>/isU",$content_Default[0],$row_Default); // 在相关内容中，抓取第一行
							
							if (!empty($row_Default)) {
								preg_match_all("/<td(.*)<\/td>/isU",$row_Default[0],$col_Default); // 在相关内容中，抓取每一列
								
								if (!empty($col_Default)) {
									if ($v <> 'Birth Stamp') {
										$Attr[$PLO][$v] = trim(strip_tags($col_Default[0][$k]));
									} else {
										$timestamp_Default = strtotime(trim(strip_tags($col_Default[0][$k])));
										$Attr[$PLO][$v] = date('Y-m-d H:i:s', $timestamp_Default); // 在项中， 去除标签得到 arr 时间戳
									}
								}
							} else {
								// echo "No ".$v." Info Yet";
								$Attr[$PLO][$v] = null;
							}
						} else {
							// echo "No ".$PLO." Info Yet";
							$Attr[$PLO][$v] = null;
						}
					}
				} else {
					echo 'SFNG_Default Page Not Found';
				}
			}
			
			// -------------------------------------------------------------------------------------------------- Use obtained WO to get FE2 Routing Info
			if (isset($Attr[$PLO]['Object Id'])) {
				// To get FE2 routing info
				$url_DisplayWorkObject = 'http://shopfloor-apj.sfng.int.hpe.com/sfweb/DisplayWorkObject?object='.$Attr[$PLO]['Object Id'];

				$info_DisplayWorkObject = file_get_contents($url_DisplayWorkObject);//解析网页
				
				if (isset($info_DisplayWorkObject)) {
					preg_match("/<th  colspan=5><font face=\"verdana, arial, helvetica\" size=\"-2\" color=\"#000000\">Routing<\/font><\/th>(.*)<\/table>/isU",$info_DisplayWorkObject,$content_DisplayWorkObject);//在整个网页中，抓取相关内容
				
					if (!empty($content_DisplayWorkObject)) {
						preg_match("/BF20_TSG_CUSINTENT/isU",$content_DisplayWorkObject[0],$BF20_TSG_CUSINTENT); // 在 相关内容中，抓取BF20_TSG_CUSINTENT

						if (!empty($BF20_TSG_CUSINTENT)) {
							$Attr[$PLO]['FEFlag'] = 1;
						} else {
							$Attr[$PLO]['FEFlag'] = 0;
						}
						if (isset($Attr[$PLO]['Family']) && $Attr[$PLO]['Family'] == '3PAR_RC_PF') {
							preg_match("/CCT_TEST/isU",$content_DisplayWorkObject[0],$CCT_TEST); // 在 相关内容中，抓取CCT_TEST
							if (!empty($CCT_TEST)) {
								$Attr[$PLO]['ConfigType'] = 'ConfigRack';
							} else {
								$Attr[$PLO]['ConfigType'] = 'PPS Option';
							}
						}
						if (isset($Attr[$PLO]['Family']) && $Attr[$PLO]['Family'] == 'EVA_FTYP_PF') {
							preg_match("/EVA_SI_BUILD/isU",$content_DisplayWorkObject[0],$EVA_SI_BUILD); // 在 相关内容中，抓取EVA_SI_BUILD
							if (!empty($EVA_SI_BUILD)) {
								$Attr[$PLO]['ConfigType'] = 'ConfigRack';
							} else {
								$Attr[$PLO]['ConfigType'] = 'PPS Option';
							}
						}
					} else {
						$Attr[$PLO]['FEFlag'] = null;
						$Attr[$PLO]['ConfigType'] = null;
					}
				} else {
					echo 'SFNG_DisplayWorkObject Page Not Found';
				}
			}
			
			// -------------------------------------------------------------------------------------------------- Parse url_handover (HO)
			// if ($Info[$PLO]['BKPL'] == 1 && $Info[$PLO]['HO'] == 0) {
			// if ($Info[$PLO]['HO'] == 0 || $Info[$PLO]['PGI'] == 0) {
				if (true) {
				// To get Handover time, putaway
				$url_handover = 'http://shopfloor-apj.sfng.int.hpe.com/sfweb/DefaultQueryReport?queryType=handover&plannedOrderPattern='.$PLO;
				
				$info_handover = file_get_contents($url_handover);//解析网页
				
				if (isset($info_handover)) {
					// $arr_handover = array(18 => 'Putaway Location', 20 => 'Last Box'); // define parsing item

					preg_match("/>Last Box<\/font>(.*)<\/table>/isU",$info_handover,$content_handover);//在整个网页中，抓取包含相关内容

					if (!empty($content_handover)) {
						preg_match_all("/<tr(.*)<\/tr>/isU",$content_handover[0],$row_handover); // 在相关内容中，抓取每一行
						
						if (!empty($row_handover)) {
							$n = 0;
							foreach ($row_handover[0] as $v) {
								preg_match_all("/<td(.*)<\/td>/isU",$v,$col_handover); // 在每一行中，抓取每一列
								
								if (!empty($col_handover)) {
									$row[$n]['LastBox'] = trim(strip_tags($col_handover[0][20]));
									$row[$n]['PutawayLocation'] = trim(strip_tags($col_handover[0][18]));
									$row[$n]['HandoverDate'] = trim(strip_tags($col_handover[0][17]));
								}
								$n++;
							}
							
							$timestamp_handover = 0;
							if (isset($row)) {
								foreach ($row as $k => $v) {
									if ($v['HandoverDate'] <> '' && $v['HandoverDate'] <> '&nbsp') {
										if ($v['LastBox'] == 'Y') { // 只要在Last Box一列中，只要遇到第一个“Y”，则抓取对应的“Handover date”
											$timestamp_handover = strtotime($v['HandoverDate']);
											$Attr[$PLO]['Handover Date'] = date('Y-m-d H:i:s', $timestamp_handover); // 在项中， 去除标签得到 arr 时间戳
											break;
										} else {
											if (strtotime($v['HandoverDate']) > $timestamp_handover) {
												$timestamp_handover = strtotime($v['HandoverDate']);
												$Attr[$PLO]['Handover Date'] = date('Y-m-d H:i:s', $timestamp_handover);
											}
										}
									} 
								}
							}
							
							if (isset($Attr[$PLO]['Handover Date'])) {
								foreach ($row as $k => $v) {
									// Is Putaway Location Empty?
									if ($v['PutawayLocation'] == '' || $v['PutawayLocation'] == '&nbsp') {
										$Attr[$PLO]['Putaway'] = 0; // If any empty then NOT putaway yet
										break;
									} else {
										$Attr[$PLO]['Putaway'] = 1;
									}
								}
							}
						} else {
							// echo "No ".$v." Info Yet";
							$Attr[$PLO]['Handover Date'] = null;
							$Attr[$PLO]['Putaway'] = '';
						}
					} else {
						// echo "No ".$PLO." Info Yet";
						$Attr[$PLO]['Handover Date'] = null;
						$Attr[$PLO]['Putaway'] = '';
					}
				} else {
					echo 'SFNG_handover Page Not Found';
				}
			}
			
			// -------------------------------------------------------------------------------------------------- Parse url_tatStatus (PGI)
			// if ($Info[$PLO]['BKPL'] == 1 && $Info[$PLO]['HO'] == 1 && $Info[$PLO]['PGI'] == 0) {
			// if ($Info[$PLO]['PGI'] == 0) {
				if (true) {
				// To get shipref, PGI time
				$url_tatStatus = 'http://shopfloor-apj.sfng.int.hpe.com/sfweb/DefaultQueryReport?queryType=tatStatus&plannedOrderPattern='.$PLO;
				
				$info_tatStatus = file_get_contents($url_tatStatus);//解析网页
				
				if (isset($info_tatStatus)) {
					$arr_tatStatus = array(4 => 'SHIPREF', 10 => 'PGI_DATE'); // define parsing item
				
					foreach ($arr_tatStatus as $k => $v) {
						preg_match("/>".$v."<\/font>(.*)<\/table>/isU",$info_tatStatus,$content_tatStatus);//在整个网页中，抓取包含相关内容

						if (!empty($content_tatStatus)) {
							preg_match("/<tr(.*)<\/tr>/isU",$content_tatStatus[0],$row_tatStatus); // 在相关内容中，抓取第一行
							
							if (!empty($row_tatStatus)) {
								preg_match_all("/<td(.*)<\/td>/isU",$row_tatStatus[0],$col_tatStatus); // 在相关内容中，抓取每一列
								
								if (!empty($col_tatStatus)) {
									if ($v <> 'PGI_DATE') {
										$Attr[$PLO][$v] = trim(strip_tags($col_tatStatus[0][$k]));
									} else {
										$timestamp_tatStatus = strtotime(strip_tags($col_tatStatus[0][$k]));
										$Attr[$PLO][$v] = date('Y-m-d H:i:s', $timestamp_tatStatus); // 在项中， 去除标签得到 arr 时间戳
									}
								}
							} else {
								// echo "No ".$v." Info Yet";
								$Attr[$PLO][$v] = null;
							}
						} else {
							// echo "No ".$PLO." Info Yet";
							$Attr[$PLO][$v] = null;
						}
					}
				} else {
					echo 'SFNG_tatStatus Page Not Found';
				}
			}
			
			// -------------------------------------------------------------------------------------------------- Parse url_GetObject (DN)
			if (isset($Attr[$PLO]['PGI_DATE'])) {
				// To get DN -- Backplane Order #
				$url_GetObject = 'http://shopfloor-apj.sfng.int.hpe.com/sfweb/GetObject?object='.$PLO;
				
				$info_GetObject = file_get_contents($url_GetObject);//解析网页
				
				if (isset($info_GetObject)) {
					$arr_GetObject = array(1 => 'Backplane Order #', 4 => 'Order Status'); // define parsing item
				
					foreach ($arr_GetObject as $k => $v) {
						preg_match("/>".$v."<\/font>(.*)<\/table>/isU",$info_GetObject,$content_GetObject);//在整个网页中，抓取包含相关内容

						if (!empty($content_GetObject)) {
							preg_match("/<tr(.*)<\/tr>/isU",$content_GetObject[0],$row_GetObject); // 在相关内容中，抓取第一行
							
							if (!empty($row_GetObject)) {
								preg_match_all("/<td(.*)<\/td>/isU",$row_GetObject[0],$col_GetObject); // 在相关内容中，抓取每一列
								
								if (!empty($col_GetObject)) {
									$Attr[$PLO][$v] = trim(strip_tags($col_GetObject[0][$k]));
								}
							} else {
								// echo "No ".$v." Info Yet";
								$Attr[$PLO][$v] = null;
							}
						} else {
							// echo "No ".$PLO." Info Yet";
							$Attr[$PLO][$v] = null;
						}
					}
				} else {
					echo 'SFNG_GetObject Page Not Found';
				}
			}
		// }
	// }
	var_dump($Attr);
?>