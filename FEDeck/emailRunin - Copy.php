<?php
//本php文件每5分钟执行一次
function mySendMail($my_addr,$my_subject,$my_msg){
    include("./PHPMailer/class.phpmailer.php");
    include("./PHPMailer/class.smtp.php");
	$mail= new PHPMailer(); //new一个PHPMailer对象出来
	$header  = "MIME-Version: 1.0\r\n";
	$header .= "Content-type: text/html; charset=utf-8\r\n";
	//$headers .= 'From: <lu.haifeng@hp.com>' . "\r\n";

	$body = $my_msg;
	$mail->Subject    = $my_subject;
	$mail->CharSet ="UTF-8";//设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
	$mail->IsSMTP(); // 设定使用SMTP服务
	//$mail->SMTPDebug  = 1;                     // 启用SMTP调试功能
	// 1 = errors and messages
	// 2 = messages only
	$mail->SMTPAuth   = false;   // 启用 SMTP 验证功能
	//$mail->SMTPSecure = "ssl";  // 安全协议
	$mail->Host       = "smtp.hpe.com";// SMTP 服务器
	$mail->Port       = 25;                   // SMTP服务器的端口号
	$mail->SetFrom('fe2_emcn@hpe.com', 'FE2_EMCN');

	$mail->MsgHTML($body);//CPMOISSengineers
	//$address = "dongmin-zhu.cls@hp.com,cpmo-essn.te@hp.com";
	$arr_addr=explode(",",$my_addr);
	foreach ($arr_addr as $addr)
	{
					$mail->AddAddress($addr, $addr);
	}

	if(!@$mail->Send())
	{
					$message .= "Mailer Error: " . $mail->ErrorInfo;
					return 0;
	}
	else
	{
					//$message .= "Message sent!恭喜，邮件发送成功！";
					return 1;
	}
}

//---------------------------------------------------------------------------------------------------------------------------------------------------

	// Connect to 112 DB
	require_once('connectvars.php');	
	$dbc = mssql_connect(DB_HOST_112, DB_USER_112, DB_PASSWORD_112) or die("connect db error");	
	mssql_select_db(DB_NAME_112,$dbc) or die('can not open db table');
	
	$query = "  IF OBJECT_ID('dbo.RuninWOEmailed', 'U') IS NULL
					BEGIN
						CREATE TABLE RuninWOEmailed
						(
						WO VARCHAR(32),
						EmailedTime DATETIME,
						);
						CREATE INDEX RuninWOEmailedIndex
						ON RuninWOEmailed (WO);
					END
	
				SELECT 
					distinct WO
				FROM 
					CICPLOEmailed
				WHERE 
					DATEDIFF(n,EmailedTime,GETDATE()) <= 60*24*7
					"; 

	$data = mssql_query($query,$dbc) or die('search db error ');

	if (!mssql_num_rows($data)) {
		echo 'Initiate Cold Start';
	}
	else {
		$WOMailedArray = array();
		while ($row = mssql_fetch_assoc($data)){
			array_push($WOMailedArray,trim($row['WO']));//获取 WO at 112DB
		}
		$PLOMailed = "'".implode("','", $WOMailedArray)."'";
	}

//---------------------------------------------------------------------------------------------------------------------------------------------------
	
// set timezone
date_default_timezone_set('Asia/Shanghai');

// from tat info page get cic plo
require_once('parseTATasp.php');

// Connect to 111 DB
$dbc = mssql_connect(DB_HOST_111, DB_USER_111, DB_PASSWORD_111) or die("connect db error"); 
mssql_select_db(DB_NAME_111,$dbc) or die('can not open db table');

	//拿过去24小时的PLO aggregated 数据
	if (isset($PLO)) {
		$query = "		
			SELECT 
				BPO,
				PLO,
				COUNT (DISTINCT WO) AS Units
			FROM 
				PLOTable
			WHERE
				DATEDIFF(n,CreateTime,GETDATE()) <= 60*24*7
				AND
				PLO IN ({$PLO}) ";
		if (isset($PLOMailed)) {
			$query .= "
				AND
				PLO NOT IN ({$PLOMailed})
			";
		}
			$query .= "	
			GROUP BY
				BPO,
				PLO
			ORDER BY
				PLO
			"; 
		$data = mssql_query($query,$dbc) or die('search db error ');
		
		if (!mssql_num_rows($data)) {
			echo 'No records found';
		} else {
			$BPOArray = array();
			$PLOArray = array();
			
			while ($row = mssql_fetch_assoc($data)){
				array_push($BPOArray,trim($row['BPO']));//获取 BPO at 111DB
				array_push($PLOArray,trim($row['PLO']));//获取 PLO at 111DB
			}
			require_once('parseSFNGcusName.php');//-----------------------------------------For Getting CusName

			// var_dump($data);

			// 开始写邮件
			$to = "yi.li5@hpe.com,";
			$to .= "cpmofe2eng@hpe.com,";
			$to .= "cic-admin.cls@hpe.com,";
			$to .= "fe-oba.cls@hpe.com,";
			$to .= "peter-chen.cls@hpe.com,";
			$to .= "egordercheck.cls@hpe.com,";
			$to .= "jipingz@hpe.com,";
			$to .= "steven-zhang.cls@hpe.com,";
			$to .= "huifen-cao.cls@hpe.com,";
			$to .= "hai-chuan.zhao@hpe.com";

			$today = date("Y-m-d");
			$current = date("Y-m-d H:i:s");
			//主题
			$subject = "New FE2 Orders Alert: {$today}";
			//正文
			$message = "<h1>{$current}</h1>";
			$message .= "<b>Act fast as we have new orders! </b>";
			$message .= '</br>';
			$message .= "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />";
			$message .= '</br>';

			//------print data table
			$message .= '<table border="1">';
			$message .= '<tr bgcolor="#E6E6FA">';
			  $message .= '<th>客户</th>';
			  $message .= '<th>BPO</th>';
			  $message .= '<th>PLO</th>';
			  $message .= '<th>Units</th>';
			$message .= '</tr>';
			
			$data = mssql_query($query,$dbc) or die('search db error ');
			
			while ($row = mssql_fetch_assoc($data)){
				$message .= '<tr>';
					if (isset($BPOCus[$row['BPO']]['EndCusName'])) {
						if (trim($BPOCus[$row['BPO']]['EndCusName']) <> 'n/a') {
							$message .= '<td>'.$BPOCus[$row['BPO']]['EndCusName'].'</td>';
						}
						else {
							if (isset($BPOCus[$row['BPO']]['ShippingName'])) {
								$message .= '<td>'.$BPOCus[$row['BPO']]['ShippingName'].'</td>';
							}
							else {
								$message .= '<td>N/A</td>';
							}
						}
					}
					else {
						$message .= '<td>N/A</td>';//-----------------------------------------For Getting CusName
					}
					$message .= '<td>'.$row['BPO'].'</td>';
					// $message .= '<td>'.$row['PLO'].'</td>';
					$message .= '<td><a href=\'http://16.187.224.44:8080/PLOSys/PLOdetails.jsp?PLO='.$row['PLO'].'\' target=\'_blank\'>'.$row['PLO'].'</a></td>';
					$message .= '<td>'.$row['Units'].'</td>';
				$message .= '</tr>';
				}	
			$message .= '</table>';	

			$message .= '</br>';
			
			$message .= '(This alert is sent hourly whenever there\'s an order.)';

			// $url = 'http://16.187.224.112:8080/Yi/RuninPass/';
			// $message .= "<a href=".$url.">click here for realtime details</a>";

			// $header = "From:admin@hpe.com \r\n";
			// $header .= "Cc:yuyi.li@hpe.com \r\n";
			// $header .= "MIME-Version: 1.0\r\n";
			// $header .= "Content-type: text/html\r\n";

			// $retval = mail ($to,$subject,$message,$header);
			if (mssql_num_rows($data)) {
				$retval = mySendMail($to,$subject,$message);

				if( $retval == true ) {
				$message .= "Message sent successfully...";
				}else {
				$message .= "Message could not be sent...";
				}
			}
			
		}
	}
	
mssql_free_result($data);
mssql_close($dbc); 

if (isset($PLOArray)) {
	$strArray = array();
	foreach ($PLOArray as $v) {
		array_push($strArray, "IF NOT EXISTS (SELECT PLO FROM CICPLOEmailed WHERE PLO = '{$v}') INSERT INTO CICPLOEmailed (PLO, EmailedTime) VALUES ('{$v}', GETDATE()) "); 
	}
	$query = implode(' ', $strArray);

	// Connect to 112 DB
	$dbc = mssql_connect(DB_HOST_112, DB_USER_112, DB_PASSWORD_112) or die("connect db error");	
	mssql_select_db(DB_NAME_112,$dbc) or die('can not open db table');

	mssql_query($query,$dbc) or die('search db error ');

	mssql_close($dbc);
}
?>