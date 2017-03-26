<?php
	fopen("cookies.txt", "w");
	$url="http://shopfloor-apj.sfng.int.hpe.com/sfweb";
	$ch = curl_init();

	$header=array('GET /1575051 HTTP/1.1',
    'Host: adfoc.us',
    'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    'Accept-Language:en-US,en;q=0.8',
    'Cache-Control:max-age=0',
    'Connection:keep-alive',
    'Host:adfoc.us',
    'User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.116 Safari/537.36',
    );

    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,0);
    curl_setopt($ch, CURLOPT_COOKIESESSION, true);

    curl_setopt($ch,CURLOPT_COOKIEFILE,'cookies.txt');
    curl_setopt($ch,CURLOPT_COOKIEJAR,'cookies.txt');
    curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
    $result=curl_exec($ch);
	
	
	var_dump($result);
	echo $result;

    curl_close($ch);
?>