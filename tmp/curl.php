<?php
$url='http://shopfloor-apj.sfng.int.hpe.com/sfweb/OpenOrdersReport?operations_m=none&fromBirthStamp=2016-12-12+10%3A55&toBirthStamp=2016-12-12+11%3A55&coaStatus=All&shipPoint=BF40&&queryType=openOrders&sortBy=Sales+Order';
$ch=curl_init();
$timeout=5;

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

// Get URL content
$lines_string=curl_exec($ch);
// close handle to release resources
curl_close($ch);
//output, you can also save it locally on the server
echo $lines_string;
?>