<?php
$url = 'http://shopfloor-apj.sfng.int.hpe.com/sfweb/OpenOrdersReport?operations_m=none&fromBirthStamp=2016-12-12+10%3A55&toBirthStamp=2016-12-12+11%3A55&coaStatus=All&shipPoint=BF40&&queryType=openOrders&sortBy=Sales+Order';

ini_set('user_agent','Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.2; SV1; .NET CLR 1.1.4322)');

$cookie_jar = tempnam('/tmp','cookie');

// log in
$c = curl_init('http://shopfloor-apj.sfng.int.hpe.com/sfweb');
curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($c, CURLOPT_COOKIEJAR, $cookie_jar);
$page = curl_exec($c);
curl_close($c);

// retrieve query page
$c2 = curl_init('http://shopfloor-apj.sfng.int.hpe.com/sfweb/OpenOrdersReport?fromBirthStamp=2016-12-12+10%3A55&toBirthStamp=2016-12-12+11%3A55');
curl_setopt($c2, CURLOPT_POST, 1);
curl_setopt($c2, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($c2, CURLOPT_COOKIEFILE, $cookie_jar);
$page2 = curl_exec($c2);
curl_close($c2);

echo $page2;

// remove the cookie jar
unlink($cookie_jar) or die("Can't unlink $cookie_jar");

?>