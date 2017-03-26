<?php
//本php文件每5分钟执行一次
function mySendMail($my_addr,$my_subject,$my_msg){
	require_once('../lib/PHPMailer/PHPMailerAutoload.php');
	require_once('../lib/PHPMailer/class.phpmailer.php');
	include("../lib/PHPMailer/class.smtp.php");
	$mail= new PHPMailer(); 
	$header  = "MIME-Version: 1.0\r\n";
	$header .= "Content-type: text/html; charset=utf-8\r\n";

	$body = $my_msg;
	$mail->Subject    = $my_subject;
	$mail->CharSet ="UTF-8";//设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
	$mail->IsSMTP(); // 设定使用SMTP服务
	$mail->SMTPDebug  = 1;                     // 启用SMTP调试功能
	// 1 = errors and messages
	// 2 = messages only
	$mail->SMTPAuth   = false;   // 启用 SMTP 验证功能
	// $mail->SMTPSecure = "ssl";  // 安全协议
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
var_dump($mail);
	if(!$mail->Send())
	{
					echo "Mailer Error: " . $mail->ErrorInfo;
					return 0;
	}
	else
	{
					echo "Message sent!恭喜，邮件发送成功！";
					return 1;
	}

}


			// 开始写邮件
			$to = "yi.li5@hpe.com";


			$today = date("Y-m-d");
			$current = date("Y-m-d H:i:s");
			//主题
			$subject = "Run-In 结束超过 1 小时未处理订单: （每10分钟更新）";
			//正文
			$message = "<h1>test</h1>";

			if (true) {
				$retval = mySendMail($to,$subject,$message);

				if( $retval == true ) {
				$message .= "Message sent successfully...";
				}else {
				$message .= "Message could not be sent...";
				}
			}
			


	
?>