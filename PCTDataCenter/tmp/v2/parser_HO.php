<?php
	date_default_timezone_set('Asia/Shanghai');
	ini_set('max_execution_time', 12000); //12000 seconds = 200 minutes
	ini_set('pcre.backtrack_limit', '10485760'); // 10mb limit

	if (isset($PLOArr)) {		
		foreach ($PLOArr as $PLO) {
			// $PLO = '5001901512';
			
			// -------------------------------------------------------------------------------------------------- Parse url_handover (HO)
			if ($Info[$PLO]['HO'] == 0 || $Info[$PLO]['PGI'] == 0) {
				// if (true) {
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
								
								if (!empty($col_handover[0])) {
									$row_ho[$PLO][$n]['LastBox'] = trim(strip_tags($col_handover[0][20]));
									$row_ho[$PLO][$n]['PutawayLocation'] = trim(strip_tags($col_handover[0][18]));
									$row_ho[$PLO][$n]['HandoverDate'] = trim(strip_tags($col_handover[0][17]));
								}
								$n++;
							}
							
							$timestamp_handover = 0;
							if (isset($row_ho)) {
								foreach ($row_ho[$PLO] as $k => $v) {
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
								foreach ($row_ho[$PLO] as $k => $v) {
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
			if ($Info[$PLO]['PGI'] == 0) {
				// if (true) {
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
		}
	}
	// var_dump($Attr);
?>