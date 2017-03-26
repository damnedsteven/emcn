<?php

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

// set timezone
date_default_timezone_set('Asia/Shanghai');

// Connect to 112 DB
require_once('connectvars.php');	
$dbc = mssql_connect(DB_HOST_112, DB_USER_112, DB_PASSWORD_112) or die("connect db error"); 
mssql_select_db(DB_NAME_112,$dbc) or die('can not open db table');

$strArray = array();
$arr = array('RI_END','CUSINTENT_START','FE_CUST_END');
$data = array();
foreach ($arr as $value) {
	for ($i=12; $i>0; $i--) {
		$slot = date('H', time() - 3600 * $i);
		$getstr = "count(distinct(case when (DATEDIFF(hour,{$value},GETDATE())={$i}) then WO else null end)) as '{$slot}'";
		array_push($strArray, $getstr); 
		$str = implode(',', $strArray);
	}
	//生成临时表
	$query = "		
			select 
				{$str}
			from 
				SFNGStateTime
			where
				FEFlag = 1
				and
				{$value} is NOT NULL
				and
				DATEDIFF(hour,{$value},GETDATE()) <= 24
			"; 
	$result = mssql_query($query,$dbc) or die('search db error ');
	$data[$value] = mssql_fetch_assoc($result);
}
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
$subject = "Daily FE2 Units Output Alert: {$today}";

$message = "<h1>{$current}</h1>";
$message .= "<b>In the last shift (past 12 hrs)... </b>";
$message .= '</br>';
$message .= "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />";
$message .= '</br>';

//------print data table
$message .= '<table border="1">';
$message .= '<tr bgcolor="#E6E6FA">';
  $message .= '<th>State</th>';
  foreach ($data['RI_END'] as $k => $v) {
	  $message .= '<th>'.$k.' 点</th>';
  }
  $message .= '<th>Total</th>';
  $message .= '<th>待处理</th>';
$message .= '</tr>';

foreach ($data as $k => $v){
	$message .= '<tr>';
		$message .= '<td>'.$k.'</td>';
		$n = 0;
		foreach ($v as $subk => $subv){
				$message .= '<td>'.$subv.' 台</td>';
				$n = $n + $subv;
			}
		$message .= '<td><font size="3" color="green">'.$n.' 台</font></td>';
		
		//get session data
		session_start();
		
		if ($k == 'RI_END') {
			if (!isset($_SESSION['num_checkin'])) {
				$message .= '<td>0台</td>';
			} else {
				$message .= '<td><font size="3" color="red">'.$_SESSION['num_checkin'].' 台</font></td>';
			}
		} elseif ($k == 'CUSINTENT_START') {
			if (!isset($_SESSION['num_checkout'])) {
				$message .= '<td>0台</td>';
			} else {
				$message .= '<td><font size="3" color="red">'.$_SESSION['num_checkout'].' 台</font></td>';
			}
		} else {
			$message .= '<td>N/A</td>';
		}
	$message .= '</tr>';
	}	
$message .= '</table>';	

$message .= '</br>';

$url = 'http://16.187.224.112:8080/Yi/RuninPass/';
$message .= "<a href=".$url.">click here for realtime details</a>";

// $header = "From:admin@hpe.com \r\n";
// $header .= "Cc:yuyi.li@hpe.com \r\n";
// $header .= "MIME-Version: 1.0\r\n";
// $header .= "Content-type: text/html\r\n";

// $retval = mail ($to,$subject,$message,$header);
$retval = mySendMail($to,$subject,$message);

if( $retval == true ) {
$message .= "Message sent successfully...";
}else {
$message .= "Message could not be sent...";
}

?>