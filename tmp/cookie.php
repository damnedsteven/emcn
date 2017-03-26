<?php

$url='http://shopfloor-apj.sfng.int.hpe.com/sfweb/OpenOrdersReport?operations_m=none&fromBirthStamp=2016-12-12+10%3A55&toBirthStamp=2016-12-12+11%3A55&coaStatus=All&shipPoint=BF40&&queryType=openOrders&sortBy=Sales+Order';

// Create a stream
$opts = array(
  'http'=>array(
    'method'=>"GET",
    'header'=>"Accept-language: en\r\n" .
              "Cookie: timezoneOffset=480\r\n"
  )
);

$context = stream_context_create($opts);

// Open the file using the HTTP headers set above
$file = file_get_contents($url, false, $context);

var_dump($file);
?>