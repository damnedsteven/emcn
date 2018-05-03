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
	$mail->Host       = "smtp3.hpe.com";// SMTP 服务器
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
	// set timezone
	date_default_timezone_set('Asia/Shanghai');

	// Connect to 112 DB
	require_once('connectvars.php');
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
					DATEDIFF(n,Runin_End,GETDATE()) > 60
					AND 
					Check_In IS NULL
					AND 
					HandoverTime IS NULL
					AND
					FEFlag = 1
					"; 

	$data = mssql_query($query,$dbc) or die('search db error ');

	if (!mssql_num_rows($data)) {
		echo 'Initiate Cold Start';
	}
	else {
		$WOArr = array();
		while ($row = mssql_fetch_assoc($data)){
			array_push($WOArr,trim($row['WO']));//获取超时 WO at 112DB
		}
		$WO = "'".implode("','", $WOArr)."'";
	}
	
	mssql_free_result($data);
	mssql_close($dbc);
	
//---------------------------------------------------------------------------------------------------------------------------------------------------

// Connect to 70 DB
$dbc = mssql_connect(DB_HOST_70, DB_USER_70, DB_PASSWORD_70) or die("connect db error"); 
mssql_select_db(DB_NAME_70,$dbc) or die('can not open db table');

	// 拿WO对应工位信息
	if (isset($WO)) {
		$query = "	SELECT 
						workObject as WO,
						serialNumber as SN,
						rack as R,
						bay as B,
						slot as S,
						state.createTime
					FROM 
						station
						inner join 
						state
						on station.memberID=state.memberID 
						inner join 
						member
						on state.memberID=member.memberID 
					WHERE 
						station.name='RUNIN'
						and
						state.name='\$MENU' 
						and
						member.processFlags=0 
						and
						workObject IN ({$WO})
					ORDER BY
						state.createTime";
						
		$data = mssql_query($query,$dbc) or die('search db error ');
		
		if (!mssql_num_rows($data)) {
			echo 'No records found';
		} else {
			// 开始写邮件
			$to = "yi.li5@hpe.com,";
			$to .= "cpmofe2eng@hpe.com,";
			$to .= "cic-admin.cls@hpe.com,";
			$to .= "fe-oba.cls@hpe.com,";
			$to .= "peter-chen.cls@hpe.com,";
			$to .= "egordercheck.cls@hpe.com,";
			// $to .= "jipingz@hpe.com,";
			// $to .= "steven-zhang.cls@hpe.com,";
			$to .= "huifen-cao.cls@hpe.com,";
			// $to .= "hai-chuan.zhao@hpe.com";

			$today = date("Y-m-d");
			$current = date("Y-m-d H:i:s");
			//主题
			$subject = "Run-In 结束超过 1 小时未处理订单: （每小时更新）";
			//正文
			$message = "<h1>{$current}</h1>";
			$message .= "<b>以下为 Run-In 结束超过 1 小时未处理订单： </b>";
			$message .= '</br>';
			$message .= "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />";
			$message .= '</br>';

			//------print data table
			$message .= '<table border="1">';
			$message .= '<tr bgcolor="#E6E6FA">';
			  $message .= '<th>WO</th>';
			  $message .= '<th>SN</th>';
			  $message .= '<th>R</th>';
			  $message .= '<th>B</th>';
			  $message .= '<th>S</th>';
			$message .= '</tr>';
			
			while ($row = mssql_fetch_assoc($data)){
				$message .= '<tr>';
					$message .= '<td>'.$row['WO'].'</td>';
					$message .= '<td>'.$row['SN'].'</td>';
					$message .= '<td>'.$row['R'].'</td>';
					$message .= '<td>'.$row['B'].'</td>';
					$message .= '<td>'.$row['S'].'</td>';
				$message .= '</tr>';
				}	
			$message .= '</table>';	

			$message .= '</br>';

			if (mssql_num_rows($data)) {
				$retval = mySendMail($to,$subject,$message);

				if( $retval == true ) {
				$message .= "Message sent successfully...";
				}else {
				$message .= "Message could not be sent...";
				}
			}
			
		}
		mssql_free_result($data);
		mssql_close($dbc); 
	}

	
?>