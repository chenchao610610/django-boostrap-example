<?php
$url = $argv[1];
$imdbid = $argv[2];
if(empty($url))
{
  return ;
}


$content = get_url_content($url);
if(empty($content))
{
   return;
}

$content = strtolower($content);
$urls = cutall($content, "<iframe src=\"", "\"");
$speed_url  = "";
$youlol_url = "";
foreach($urls as $url)
{
  if(false !== strpos($url, "http://youlol"))
  {
     $youlol_url = $url;
  }

  if(false !== strpos($url, "http://speedplay"))
  {
     $speed_url = $url;
  }
}

$host = "127.0.0.1:53306";
$user = "main_wapka_mobi";
$pass = 'JKjk^%$lddada';
$dbname = "imdb";

$conn = mysql_connect($host, $user, $pass) or die("connect to db fail");
mysql_select_db($dbname, $conn) or die("select db fail");
mysql_query("set names utf8", $conn);


echo $speed_url . "\n";
echo $youlol_url . "\n";

$sql = "select * from movie_info where imdb_id = '$imdbid'";
$rs = mysql_query($sql);
$result = mysql_fetch_row($rs);

if($result)
{
   print_r($result);
}
else
{
   echo "error\n";
}

function cutstr($content, $start, $end)
{
    $p1 = 0;
    $p2 = 0;
    $len = strlen($start);

    if(false === ($p1 = strpos($content, $start)))
    {
        return "";
    }

    if(false === ($p2 = strpos($content, $end, $p1 + $len)))
    {
         return "";
    }

    return substr($content, $p1 + $len, $p2 - $p1 - $len);
}

function get_url_content($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $output = curl_exec($ch);
    return $output;
}

function rcutstr($content, $start, $end)
{
    $p1 = 0;
    $p2 = 0;
    $len = strlen($start);
    if(false === ($p1 = strpos($content, $end)))
    {   
        return ""; 
    }   
    $content = substr($content, 0, $p1);
    if(false === ($p2 = strrpos($content, $start)))
    {   
        return ""; 
    }   
    return substr($content, $p2 + $len);
}


function cutall($content, $start, $end)
{
    $lens = strlen($content) ;
    $all = Array();

    while(true)
    {   
        $p1 = 0;
        $p2 = 0;
        $len = strlen($start);
        $endlen = strlen($end);

        if(false === ($p1 = strpos($content, $start)))
        {   
            break;
        }   

        if(false === ($p2 = strpos($content, $end, $p1 + $len)))
        {   
            break;
        }   

        $result = substr($content, $p1 + $len, $p2 - $p1 - $len);
        $content  = substr($content, $p2 + $endlen);
        array_push($all, $result);
    }   
    return $all;
}
