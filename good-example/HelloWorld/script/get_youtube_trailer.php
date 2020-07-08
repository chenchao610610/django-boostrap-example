<?php
/**
 * @desc 更新电影的预告片,支持指定一个更新
 * 一天跑一次，更新最近的2000个
 */
$host = "127.0.0.1:53306";
$user = "xxxx";
$passwd = 'yyyy';
$dbname = "imdb";

//连接数据库
$conn = mysql_pconnect($host, $user, $passwd) or die("can not connect to db");
mysql_select_db($dbname, $conn) or die("select db fail");
mysql_query("set names utf8", $conn);

$curr_year = date("Y");
$curr_year = intval($curr_year);
$curr_offset = intval(date("z"));
$curr_offset = $curr_offset%3;

$curr_year += $curr_offset;

echo "=curr_year=".$curr_year."=\n";

$target_id = $argv[1];
if(empty($target_id))
{
    //一天有没有增加100部电影呢
    $sql = "select imdbid, title, lockstatus, release_date from imdb_info where parent_imdbid='' and release_date >='".date("Y-m-d")."' and year='".$curr_year."' order by release_date asc limit 2000";
}
else
{
    $sql = "select imdbid, title, lockstatus, release_date  from imdb_info where imdbid='".$target_id."' limit 1";
}

$offset = 0;


$res = mysql_query($sql, $conn);

echo "total=".mysql_num_rows($res)."\n";
//sleep(10);

while($rs = mysql_fetch_array($res))
{
    $lockstatus = intval($rs['lockstatus']);
    $imdbid = trim($rs['imdbid']);
    $title = trim($rs['title']);
    $release_date = trim($rs['release_date']);

    if(empty($release_date))
    {
        echo "no release_date\n";
        continue; 
    }

    echo "======================================================================================".$release_date."=======\n";
    $title = format_title($title);
    $title = html_replace($title); 
    echo "title=".$title."\n";

    //过滤那些已经删除的
    if(0 != $lockstatus)
    {
        continue;
    }

    if(empty($imdbid))
    {
        continue; 
    }

    if(empty($title))
    {
        continue; 
    }

    ++$offset;
    echo "offset=".$offset."\n";

    //判断7天内的 trailer 是否够数
    $t = time() - 7 * 86400;

    if(!empty($target_id))
    {
        $sql = "delete from movie_source where imdb_id='".$imdbid."' and source_type='2' limit 20";
        mysql_query($sql, $conn);
    }
    else
    {
        $num = 0;
        $sql = "select count(1) as num from movie_source where imdb_id='".$imdbid."' and lockstate='0' and source_type='2' and create_time > ".$t."";
        echo $sql."\n";
        $res1 = mysql_query($sql, $conn);
        $rs1 = mysql_fetch_array($res1);
        $num = intval($rs1['num']);
        mysql_free_result($res1);

        if($num >= 3)
        {
            echo "skip....\n";
            continue; 
        }
    }

    $trailer_num = get_trailer($imdbid, $title);

    if($trailer_num > 3)
    {
        //删除老的 trailer
        $sql = "delete from movie_source where imdb_id='".$imdbid."' and create_time <= '".$t."' and source_type='2'";
        mysql_query($sql, $conn);

        //清缓存
        $url = "http://xapi.test.vidmate.net/movie_info?imdb_id=".$imdbid."&debug=fei&appver=2.0";
        echo $url."\n";
        file_get_contents($url);
    }
    sleep(1);
}
mysql_free_result($res);

function get_trailer($imdbid, $title)
{
    echo "imdbid=".$imdbid.", title=".$title."\n";
    global $conn;
    $kw = $title;
    $title = urlencode($title." trailer");
    $url = "https://www.youtube.com/results?q=".$title."&sm=3&app=desktop";
    echo $url."\n";
    $content = file_get_contents($url);
    file_put_contents("/tmp/trailer.log", $content);
    if(empty($content))
    {
        echo "http get content is empty\n";
        return 0;
    }
    //echo $content."\n";
    $arr = explode("<div id=\"results\">", $content);
    array_shift($arr);
    $content = implode("<div id=\"results\">", $arr);

    $arr = explode("<li><div class=\"yt-lockup yt-lockup-tile yt-lockup-video vve-check clearfix\"", $content);
    array_shift($arr);

    echo "item num=".count($arr)."\n";

    $weight = 999;
    $trailer_num = 0;
    foreach($arr as $item)
    {
        --$weight;
        $item = trim($item); 
        if(empty($item))
        {
            continue;
        }
        $arr1 = explode("</h3>", $item);
        $item = $arr1[0];

        $video_id = cutstr($item, "/watch?v=", "\"");
        $video_title = cutstr($item, "describedby=\"description-id", "</a>");
        if($video_title)
        {
            $video_title .= "</a>";
            $video_title = cutstr($video_title, ">", "</a>");
        }
        else
        {
            echo "get video title fail\n";
            die;
        }

        $video_title = html_replace($video_title);

        if(false === stripos($video_title, "trailer"))
        //if(false === stripos($video_title, "teaser") && false === stripos($video_title, "trailer"))
        {
            echo "title no trailer, skip...., title=".$video_title."\n";
            continue; 
        }
        if(empty($video_id))
        {
            echo "empty video id\n";
            continue; 
        }

        $domain = "www.youtube.com";

        $page_url = "https://www.youtube.com/watch?v=".$video_id."";
        $page_md5 = md5($page_url);
        $sql = "select id from movie_source where page_md5='".$page_md5."' and imdb_id='".$imdbid."' limit 1";
        $res = mysql_query($sql, $conn);
        if($rs = mysql_fetch_array($res))
        {
            //已经存在,更新插入时间
            $id = $rs['id'];
            $sql = "update movie_source set create_time='".time()."', weight='".$weight."' where id='".$id."' limit 1";
            echo $sql."\n";
            mysql_query($sql, $conn);

            $trailer_num++;
            if($trailer_num > 5)
            {
                break;
            }
        
        }
        else
        {
            //echo "id=".$video_id.", title=".$video_title."\n";
            $sql = "insert into movie_source(imdb_id, keyword, lockstate, title, domain, source_type, create_time, page_url, page_md5, src_type, weight) values ('".$imdbid."', '".mysql_escape_string($kw)."', '0', '".mysql_escape_string($video_title)."', '".$domain."', '2', '".time()."', '".$page_url."', '".$page_md5."', 'youtube', '".$weight."')";
            echo $sql."\n";
            mysql_query($sql, $conn);

            $trailer_num++;
            if($trailer_num > 5)
            {
                break;
            }
        }
        mysql_free_result($res);
    }
    return $trailer_num;
}

function format_title($title)
{
    if(false !== ($p =strpos($title, "(")))
    {
        $title = substr($title, 0, $p); 
        $title = trim($title);
    }
    return $title;
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

function html_replace($string)
{
    $string = str_replace('&quot;','"',$string);
    $string = str_replace('&lt;','<',$string);
    $string = str_replace('&gt;','>',$string);
    $string = str_replace('&amp;','&',$string);
    $string = str_replace("&nbsp;",' ',$string);
    $string = str_replace("&#039;","'",$string);
    $string = str_replace("&#39;","'",$string);

    return $string;
}

