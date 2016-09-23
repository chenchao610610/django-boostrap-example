<?php
require __DIR__ . '/../../vidmate.mobi/server_side/loader.php';
use base\DaoFactory;
use dal\FastDFS;
use utils\Common;
use utils\Result;

   /***************************
   封面默认图 : http://static.vidmate.mobi/images/movie/e/1/ET00005874/backdrop_large.jpg
   ET小图默认图： http://static.vidmate.mobi/images/movie/e/1/ET00005874/poster_large.jpg
   vidmate默认小图：
   http://static.vidmate.mobi/images/movie/3/a/tt1637995/poster_w185.jpg
   -rw-rw-r-- 1 webserver webserver  2172 Apr 28 09:35 poster_large.webp
   -rw-rw-r-- 1 webserver webserver  7979 Apr 28 09:35 poster_large.jpg
   -rw-rw-r-- 1 webserver webserver  9688 Apr 28 09:35 backdrop_large.webp
   -rw-rw-r-- 1 webserver webserver 39733 Apr 28 09:35 backdrop_large.jpg
   //记得删除图片
   ***************************/

$host = "127.0.0.1:53306";
$user = "main_wapka_mobi";
$pass = 'JKjk^%$lddada';
$dbname = "imdb";

$conn = mysql_connect($host, $user, $pass) or die("connect to db fail");
mysql_select_db($dbname, $conn) or die("select db fail");
mysql_query("set names utf8", $conn);
//读取imdb info


$imdbid = $argv[1];
file_put_contents("/tmp/img.log", "$imdbid\n");
if(empty($imdbid))
{
   return;
}

$thumbnail = "";
echo "=============================$imdbid==========================\n";
//handle small
//数据库存在
$flag = false;
if(!empty($thumbnail) && strlen($thumbnail) >= 10)
{
    $ret = getImage($thumbnail, $imdbid, "small");
    if($ret)
    {
       //todo
       $filename = $imdbid . "_small.jpg";
       upload_img($filename);
       $filename = $imdbid . "_small.webp";
       upload_img($filename);
       $flag = true; 
    }

}

//如果处理失败   
//需要判断是否为默认图片 ET默认图, tt默认图
//handle small
if(!$flag)
{
    //ET find site agin
    if(false !== strpos($imdbid, "ET"))
    {
      $url = "https://in.bmscdn.com/events/Large/" . $imdbid . ".jpg";
      $ret = getImage($url, $imdbid, "small");
      if(!$ret)
      {
        $md = md5($imdbid);
        $f1 = $md[0];
        $f2 = $md[1];
        $url = "http://static.vidmate.mobi/images/movie/$f1/$f2/$imdbid/poster_small.jpg";
        $ret = getImage($url, $imdbid, "small");
        if(!$ret)
        {
            clear_img($imdbid);
            continue;
        }
      }
    }
    else
    {
       //tt todo
       $imgs = get_tt_img($imdbid);
       $url = $imgs['small'];
       if(empty($url))
       {
           clear_img($imdbid);
           continue;
       }

       $ret = getImage($url, $imdbid, "small");
       if(!$ret)
       {
           clear_img($imdbid);
           continue;
       }
    }

    $ret1 = is_defualt_img($imdbid, "small");
    if($ret1)
    {
        echo "$imdbid small default pic\n";
    }
    else
    {
        //todo
        $filename = $imdbid . "_small.jpg";
        upload_img($filename);
        $filename = $imdbid . "_small.webp";
        upload_img($filename);
    }
}

//ET find site agin
if(false !== strpos($imdbid, "ET"))
{
  $url = "http://in.bmscdn.com/events/showcasesynopsis/" . $imdbid . ".jpg";
  $ret = getImage($url, $imdbid, "large");

  if(!$ret)
  {
      clear_img($imdbid);
      continue;
  }
}
else
{
   //tt todo
   $imgs = get_tt_img($imdbid);
   $url = $imgs['large'];
   if(empty($url))
   {
     clear_img($imdbid);
     continue;
   }

   $ret = getImage($large, $imdbid, "small");
   if(!$ret)
   {
       clear_img($imdbid);
       continue;
   }
}

$ret1 = is_defualt_img($imdbid, "large");
if($ret1)
{
    echo "$imdbid large default pic\n";
}
else
{
    //todo
    $filename = $imdbid . "_large.jpg";
    upload_img($filename);
    $filename = $imdbid . "_large.webp";
    upload_img($filename);
}

//clear pic
clear_img($imdbid);
sleep(1);


function clear_img($imdbid)
{
   $img1 = $imdbid . "_" . "small.jpg";
   $img2 = $imdbid . "_" . "small.webp";
   $img3 = $imdbid . "_" . "large.jpg";
   $img4 = $imdbid . "_" . "large.webp";
   unlink($img1);
   unlink($img2);
   unlink($img3);
   unlink($img4);
}

function get_img_url($imdbid, $type)
{
    $md = md5($imdbid);
    $f1 = $md[0];
    $f2 = $md[1];
    $url = "";
    if($type == 'small')
    {
       //ET
       if(false !== strpos($imdbid, "ET"))
       {
           $url = "http://static.vidmate.mobi/images/movie/$f1/$f2/$imdbid/poster_large.jpg";
       }
       else
       {
           $url = "http://static.vidmate.mobi/images/movie/$f1/$f2/$imdbid/poster_w185.jpg";
       }
    }

    if($type == 'large')
    {
       //ET
       if(false !== strpos($imdbid, "ET"))
       {
           $url = "http://static.vidmate.mobi/images/movie/$f1/$f2/$imdbid/backdrop_large.jpg";
       }
       else
       {
           $url = "http://static.vidmate.mobi/images/movie/$f1/$f2/$imdbid/backdrop_w1280.jpg";
       }
    }

    return $url;
}


function is_defualt_img($imdbid, $type)
{
   //ET 大图 小图 都有默认图 （官网默认图 vidmate默认图 大图默认图）
   //tt 只有小图 根据大小判断
   $jpg_file = $imdbid . "_" .$type. ".jpg";
   $web_file = $imdbid . "_" .$type. ".webp";
   $jpg_size = filesize($jpg_file);
   $web_size = filesize($web_file);

   if($type == 'small')
   {
      if(false !== strpos($imdbid, "ET"))
      {
         if($jpg_size == 8780 && $web_size == 3110)
         {
            return true;
         }

         if($jpg_size == 29649 && $web_size == 2396)
         {
             return true;
         }


         if($jpg_size == 7979 && $web_size == 2172)
         {
            return true;
         }
      }
      else
      {
          if($jpg_size == 8780 && $web_size == 3110)
          {
              return true;
          }
      }
   }

   if($type == 'large')
   {
      if(false !== strpos($imdbid, "ET"))
      {
          if($jpg_size == 39733 && $web_size == 9688)
          {
              return true;
          }

          if($jpg_size == 29649 && $web_size == 2396)
          {
              return true;
          }

          if($jpg_size == 17745 && $web_size == 6414)
          {
              return true;
          }
      }
   }

   return false;
}

function getImage($url, $imdbid='', $type)
{
    echo $url . "===" . $type . "\n";
    if(empty($url) || empty($imdbid) || empty($type))
    {   
       return false;
    }   

    $jpg_file = $imdbid . "_" . $type . ".jpg";
    $web_file = $imdbid . "_" . $type . ".webp"; //文件保存路径 
    $ch=curl_init();
    $timeout=5;
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
    $img=curl_exec($ch);

    if(false !== strpos($img, "404 Not Found"))
    {
        echo "$imdbid $type not found\n";
        return false;
    }

    if(strlen($img) == 0)
    {
        echo "$imdbid $type size 0\n";
        return false;
    }

    curl_close($ch);
    $size=strlen($img); //文件大小 
    $fp2=@fopen($jpg_file, 'a');
    fwrite($fp2,$img);
    fclose($fp2);

    if(is_file($jpg_file))
    {   
       echo "$imdbid $type jpg ok\n";
       system("/home/webserver/local/cwebp/bin/cwebp -q 80 $jpg_file -o  $web_file"); 
       if(is_file($web_file))
       {   
           echo "$imdbid $type webp ok\n";
       }   
       else
       {   
           unlink($jpg_file);
           echo "$imdbid $type webp fail\n";
           return false;
       }   
    }   
    else
    {   
       echo "$imdbid $type jpg fail\n";
       return false;
    }   


    return  true;
}
//$filename = getImage("http://static.vidmate.mobi/images/upload/9fd3a640ab405627077b4a35fbf9ae9b.jpeg", 'ET0001');


  
function upload_img($filename)
{
  echo "$filename ======= \n";
  //TODO 上传到服务器
  if(!is_file($filename))
  {
      echo "$filename not exists\n"; 
      return ;
  }

  //如果是jpg格式
  //需要插入数据库
  //tt0024069_small.jpg
  $id = "";
  if(false !== strpos($filename, "_small.jpg"))
  {
      $arr = split("_",  $filename);
      $id = $arr[0];
  }


  $type   = 'movie';
  $imgid = $filename;
  $dao =  DaoFactory::getDao("FastdfsImg");
  $sql = "select id from fastdfs_img where imgid = '$imgid'";
  echo $sql . "\n";
  $data = $dao->query($sql);
  $flag = false;
  if(!empty($data) && count($data) > 0)
  {
      echo "img exists\n";
      $flag = true;
      if(!empty($id))
      {
         $now = time();
         $url = "http://8.37.229.36:8999/Fastdfsimg.php?type=movie&imgid=$filename&s=$now";
         $sql = "update imdb_info set thumbnail = '$url' where imdbid = '$id'";
         file_put_contents("/tmp/img.log", "$sql\n");
         mysql_query($sql);
         echo $sql . "\n";
      }
      //return;
  }

  //upload to fastdfs
  $fileInfo = FastDFS::getInstance()->addFile($filename);
  if(empty($fileInfo))
  {
      unlink($tmpFile);
      echo "FastDFS return error\n";
      return false;
  }

  $groupname = $fileInfo['groupName'];
  $fileid = $fileInfo['fileId'];
  echo $groupname . "==" . $fileid . "\n";
  $dao =  DaoFactory::getDao("FastdfsImg");

  if(!$flag)
  {
    $sql =  "insert into fastdfs_img (`type`, imgid, `group`, fileid) values ('$type','$imgid','$groupname','$fileid')";
  }
  else
  {
    $sql =  "update fastdfs_img  set type = 'movie', `group`='$groupname', fileid='$fileid' where imgid = '$imgid'";
  }

  echo $sql . "\n";
  $dao->query($sql);
  if(!empty($id))
  {
     $now = time();
     $url = "http://8.37.229.36:8999/Fastdfsimg.php?type=movie&imgid=$filename&s=$now";
     $sql = "update imdb_info set thumbnail = '$url' where imdbid = '$id'";
     file_put_contents("/tmp/img.log", "$sql\n");
     mysql_query($sql);
     echo $sql . "\n";
  }

  //clear cache
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

function get_tt_img($imdbid)
{
   $url = "http://www.imdb.com/title/$imdbid/";
   $content = file_get_contents($url);
   $img1 = cutstr($content, "<div class=\"poster\">", "itemprop=\"image\" />");
   $img2 = cutstr($content, "<div class=\"slate\">", "itemprop=\"image\" />");
   
   $small = ""; 
   $large = ""; 
   if(!empty($img1))
   {   
      $small = cutstr($img1, "src=\"", "\"");
   }   
   
   if(!empty($img2))
   {   
      $large = cutstr($img2, "src=\"", "\"");
   }   
   
   $imgs = array(
        "small" => $small,
        "large" => $large,
   );  

   return $imgs;
}

