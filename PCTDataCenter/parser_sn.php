<?php
	date_default_timezone_set('Asia/Shanghai');
	ini_set('max_execution_time', 12000); //12000 seconds = 200 minutes
	ini_set('pcre.backtrack_limit', '10485760'); // 10mb limit

	if (isset($PLOArr)) {		
		foreach ($PLOArr as $PLO) {
			// $PLO = '5001659581';
			
			// -------------------------------------------------------------------------------------------------- Parse url (Cycle Time Report)
			// if ($Info[$PLO]['BKPL'] == 0) {
				if (true) {
				// To get Object ID,	Operation,	Operation Start,	Operation End
				$url = 'http://shopfloor-apj.sfng.int.hpe.com/sfweb/DefaultQueryReport?operations_m=none&queryType=serialNoTrackingReport&sortBy=Sales+Order&consolidated=wo&plannedOrderPattern='.$PLO;
				
				$info = file_get_contents($url);//解析网页
				
				if (isset($info)) {
					preg_match("/>Unit Serial Number<\/font>(.*)<\/table>/isU",$info,$content);//在整个网页中，抓取包含相关内容

					if (!empty($content)) {
						preg_match_all("/<tr(.*)<\/tr>/isU",$content[0],$rows); // 在相关内容中，抓取每一行
						
						if (!empty($rows)) {
							foreach ($rows[0] as $row) {
								preg_match_all("/<td(.*)<\/td>/isU",$row,$col); // 在相关内容中，抓取每一列
								
								if (!empty($col[0])) {
									if (isset($col[0][7])) {
										$SerialNumber[trim(strip_tags($col[0][4]))] = trim(strip_tags($col[0][7])); // 在项中， 去除标签得到 Unit Serial Number
										
									} else {
										echo 'Columns Not Available... ';
									}	
								} else {
									echo 'Row Not Available... ';
								}
							}	
						} else {
							echo 'Rows Not Available... ';
						}
					} else {
						echo 'Contents Not Available... ';
					}
				} else {
					echo 'SFNG Page Not Found... ';
				}
			}
			
		}
	}
	// var_dump($SerialNumber);
?>