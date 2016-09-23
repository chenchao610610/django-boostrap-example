<?php
$host = "127.0.0.1:53306";
$user = "main_wapka_mobi";
$pass = 'JKjk^%$lddada';
$dbname = "imdb";

$conn = mysql_connect($host, $user, $pass) or die("connect to db fail");
mysql_select_db($dbname, $conn) or die("select db fail");
mysql_query("set names utf8", $conn);

$force_id = $argv[1];
if(empty($force_id))
{   
   file_put_contents("/tmp/imdb.log", "imdbid is empty!!!\n");
}
else
{
   file_put_contents("/tmp/imdb.log", "imdbid is $force_id !!!\n");
}



if(!empty($force_id))
{
    $imdbid = $force_id;
    if(false !== strpos($imdbid, "tt"))
    {
        get_tt_info($imdbid);
    }
    else
    {
        get_bookmyshow_info($imdbid);
    }

    return;
}



function get_tt_info($imdbid, $flag, $view_order)
{
    global $conn;
    $url = "http://www.imdb.com/title/".$imdbid."/?ref_=shtt_ov_tt";

    $content = file_get_contents($url);
    if(empty($content))
    {
        return;
    }

    $title = get_title($content); 

    //更新 not yet released
    $is_not_yet_released = 0;
    $is_coming_soon = '0';
    //if(is_not_yet_released($content))
    //{
    //    $is_not_yet_released = '1';
    //    $is_coming_soon = '1';
    //}

    if(is_tvseries($title))
    {
        return ;
    }

    $year = get_year($content);
    if(!is_numeric($year) || -1 == $year)
    {
        $year = "";
    }

    $country = "USA";
    $ret = get_release_info($imdbid);
    if(!empty($ret))
    {
      $country = $ret['country'];
      if(empty($country))
      {
           $country = get_country($content);
      }

      if(empty($year) && !empty($ret['year']))
      {
           $year = $ret['year'];
           $title = $title . " ($year)";
      }

      $release_date = $ret['release_time'];
    }
    else
    {
        $release_date = get_release_date($content);
    }

    if(empty($release_date))
    {
        $release_date = get_release_date($content);
        if(empty($release_date))
        {
            $release_date = "1000-00-00";
            $is_not_yet_released = '0';
            $is_coming_soon = '0';
        }
    }

    if(!empty($release_date))
    {
      $tmp_time = strtotime($release_date);
      if($tmp_time > time())
      {
         $is_not_yet_released = '1';
         $is_coming_soon = '1';
      }
    }

    
    $summary = get_summary($content);
    $creator = get_creator($content);
    $directors = get_directors($content);
    $actors = get_actors($content);
    //$country = get_country($content);
    $genres = get_genres($content);
    $poster = get_poster($content);
    $writers = get_writers($content);
    $language = get_language($content);
    $also_known_as = get_also_known_as($content);
    $filming_locations = get_filming_locations($content);
    $storyline = get_storyline($content);
    $grade = get_grade($content);
    $duration = get_duration($content);
    $rate = get_rate($content);
    
    $data = array(
        "title"=>$title,
        "summary"=>$summary,
        "creator"=>$creator,
        "directors"=>$directors,
        "actors"=>$actors,
        "country"=>$country,
        "genres"=>$genres,
        "writers"=>$writers,
        "language"=>$language,
        "duration"=>$duration,
        "year"=>$year,
        "rate"=>$rate,
        "release_date"=>$release_date,
        "is_not_yet_released"=>$is_not_yet_released,
        "is_coming_soon"=>$is_coming_soon,
        "lastupdate"=>time(),
    );

    if(!empty($summary) && empty($data['storyline']))
    {
       $data['storyline'] = $summary;
    }
    else
    {
       $data['storyline'] = $storyline;
    }

    //应该 用title去匹配
    $tmp_title = mysql_escape_string($title);
    $sql = "select imdbid from imdb_info where title ='$tmp_title' or imdbid = '$imdbid'  limit 1";
    $res = mysql_query($sql, $conn);
    $sql1 = "";
    print_r($data);

    if($rs = mysql_fetch_array($res))
    {
       $db_imdbid = $rs['imdbid'];
       if(false !== strpos($db_imdbid, "tt"))
       {
          //echo "Update success!";
          file_put_contents("/tmp/imdb.log", "$db_imdbid Update success\n");
          $sql = "update imdb_info set ";
          $sep = "";
          foreach($data as $key=>$value)
          {
              $sql .= $sep."".$key."='".mysql_escape_string($value)."'"; 
              $sep = ", ";
          }
          $sql .= " where imdbid='".$imdbid."' limit 1";

          if($is_coming_soon == '1')
          {
            $sql1 = "replace into movie_coming_soon(imdbid, `date`, type) values('".$db_imdbid."', '".$release_date."', 'imdb')";
            mysql_query($sql1, $conn);
          }
       }
       else
       {
          $sql = null;
       }
    }
    else
    {
        //echo "Insert success!";
        file_put_contents("/tmp/imdb.log", "$imdbid Insert success\n");
        $data['imdbid'] = $imdbid;
        $data['ctime'] = time();
        $sql = "insert into imdb_info set ";
        $sep = "";
        foreach($data as $key=>$value)
        {
            $sql .= $sep."".$key."='".mysql_escape_string($value)."'"; 
            $sep = ", ";
        }
        $sql .= " ";
    
        if($is_coming_soon == '1')
        {
          $sql1 = "replace into movie_coming_soon(imdbid, `date`, type) values('".$imdbid."', '".$release_date."', 'imdb')";
          mysql_query($sql1, $conn);
        }
    }
    //mysql_free_result($res);
    if(!empty($sql))
    {
       mysql_query($sql, $conn);
    }
    else
    {
       echo "$imdbid==>$title exists in table";
    }

   //insert movie_showtime
   if($flag == 2 && $is_coming_soon == '0')
   {
     $sql = "replace into movie_showtimes(imdbid, showtime, view_order) values('".$imdbid."', '".$release_date."', '".$view_order."')"; 
     echo $sql."\n";
     mysql_query($sql, $conn);
   }
}

function get_imdbid_by_url($url)
{
    $url .= "/";
    $imdbid = cutstr($url, "/title/", "/");
    return $imdbid;
}

function get_title($content)
{
    //<meta property='og:title' content="Ant-Man (2015)" />
    //$content = str_replace("'", "\"", $content);
    $tmp = cutstr($content, "property='og:title' content=\"", "\"");
    if(empty($tmp))
    {
        return "";
    }

    preg_match_all("/\((.+?)\)/", $tmp, $arr);
    $year = ""; 

    if(count($arr[0]) >= 1)
    {
       $flag = 0;
       foreach($arr[0] as $t) 
       {   
           $flag = $flag  + 1;
           $tmp = str_replace($t, "", $tmp);
       }   
  
       $year = $arr[1][$flag - 1]; 
    }
    $tmp = trim($tmp);
    if(!empty($year) && is_numeric($year) && strlen($year) == 4)
    {
       $tmp .= " ($year)";
    }

    return $tmp;
}

function get_summary($content)
{   
    $tmp = cutstr($content, "<div class=\"summary_text\" itemprop=\"description\">", "</div>");

    if(false !== strpos($tmp, "..."))
    {   
        $tmp = ">".$tmp; 
        $tmp = cutstr($tmp, ">", "...");
        $tmp .= "...";
    }   

    $tmp = trim($tmp);

    if(false !== strpos($tmp, "Add a Plot"))
    {   
        $tmp = ""; 
    }   

    return $tmp;
}   
 


function get_creator($content)
{
    $tmp = cutstr($content, "Production Co:", "</div>");

    $tmp = strip_tags($tmp);

    if(false !== strpos($tmp, "See more"))
    {
        $arr = explode("See more", $tmp); 
        $tmp = $arr[0];
    }
    $tmp = one_line($tmp);
    $tmp = trim($tmp);
    return $tmp;
}

function get_directors($content)
{
    $tmp = cutstr($content, "Director:", "</div>");
    $tmp = strip_tags($tmp);
    $tmp = merge_space($tmp);
    $tmp = trim($tmp);
    return $tmp;
}

function get_actors($content)
{
    $tmp = cutstr($content, "Stars:", "</div>");
    $tmp = strip_tags($tmp);
    $tmp = one_line($tmp);
    if(false !== strpos($tmp, "|"))
    {
        $arr = explode("|", $tmp); 
        $tmp = $arr[0];
    }

    $tmp = merge_space($tmp);
    $tmp = trim($tmp);
    return $tmp;

}

function get_country($content)
{
    $tmp = cutstr($content, "Country:", "</div>");
    $tmp = strip_tags($tmp);
    $tmp = one_line($tmp);
    $tmp = merge_space($tmp);
    $tmp = trim($tmp);
    return $tmp;

}

function get_genres($content)
{
    $tmp = cutstr($content, "Genres:", "</div>");
    $tmp = strip_tags($tmp);
    $tmp = one_line($tmp);
    $tmp = html_decode($tmp);

    $tmp = str_replace(" | ", ", ", $tmp);

    $tmp = merge_space($tmp);
    $tmp = trim($tmp);
   
    return $tmp;

}

function get_poster($content)
{
    //<meta property='og:title' content="Ant-Man (2015)" />
    $content = str_replace("'", "\"", $content);
    $tmp = cutstr($content, "property=\"og:image\"", ">");
    if(empty($tmp))
    {
        return "";
    }
    return cutstr($tmp, "content=\"", "\"");

}

function get_writers($content)
{
    $tmp = cutstr($content, "Writers:", "</div>");
    if(empty($tmp))
    {
        $tmp = cutstr($content, "Writer:", "</div>");
    }
    if(empty($tmp))
    {
        return ""; 
    }
    $tmp = strip_tags($tmp);
    $tmp = one_line($tmp);
    $tmp = html_decode($tmp);

    $tmp = trim($tmp);

    if(false !== strpos($tmp, "more credit"))
    {
        $arr = explode(",", $tmp); 
        array_pop($arr);
        $tmp = implode(",", $arr);
    }
    $tmp = merge_space($tmp);
    return $tmp;
}

function get_language($content)
{
    $tmp = cutstr($content, "Language:", "</div>");
    $tmp = strip_tags($tmp);
    $tmp = one_line($tmp);
    $tmp = html_decode($tmp);

    $tmp = merge_space($tmp);
    $tmp = trim($tmp);
    return $tmp;

}

function get_also_known_as($content)
{
    $tmp = cutstr($content, "Also Known As:", "</div>");

    $tmp = strip_tags($tmp);

    if(false !== strpos($tmp, "See more"))
    {
        $arr = explode("See more", $tmp); 
        $tmp = $arr[0];
    }
    $tmp = merge_space($tmp);
    $tmp = trim($tmp);
    return $tmp;
}

function get_filming_locations($content)
{
    $tmp = cutstr($content, "Filming Locations:", "</div>");

    $tmp = strip_tags($tmp);

    if(false !== strpos($tmp, "See more"))
    {
        $arr = explode("See more", $tmp); 
        $tmp = $arr[0];
    }
    $tmp = merge_space($tmp);
    $tmp = trim($tmp);
    return $tmp;
}

function get_storyline($content)
{
    $tmp = cutstr($content, "<h2>Storyline</h2>", "</div>");

    $tmp = strip_tags($tmp);

    if(false !== strpos($tmp, "Written by"))
    {
        $arr = explode("Written by", $tmp); 
        $tmp = $arr[0];
    }
    $tmp = trim($tmp);
    if(false !== strpos($tmp, "Add Full Plot"))
    {
        $tmp = ""; 
    }
    return $tmp;

}

function get_grade($content)
{
    $content = str_replace("'", "\"", $content);
    $tmp = cutstr($content, "<meta itemprop=\"contentRating\"", ">");
    if(empty($tmp))
    {
        return "";
    }
    $tmp = cutstr($tmp, "content=\"", "\"");
    $tmp = merge_space($tmp);
    return $tmp;
}

function get_duration($content)
{
    $tmp = cutstr($content, "<time itemprop=\"duration\" datetime=\"PT", "M\"");
    if(empty($tmp))
    {
      $tmp = 0;
    }
    return $tmp;
}

function get_year($content)
{
    $year = -1;
    $title = get_title($content);
    if($title)
    {
        $tmp = cutstr($title, "(", ")");
        if(empty($tmp))
        {
           return $year;
        }

        if(is_numeric($tmp))
        {
            $year = $tmp; 
        }
        else
        {
            /*
            $tmp = trim($tmp);
            $arr =  explode(" ", $tmp);
            $tmp = end($arr);
            if(is_numeric($tmp))
            {
                $year = $tmp; 
            }
            */
        }
    }

    return $year;
}

function get_rate($content)
{
    $tmp = cutstr($content, "<span itemprop=\"ratingValue\">", "</span>");    
    $tmp = merge_space($tmp);
    return $tmp;
}

function get_release_date($content)
{
    $content = str_replace("'", "\"", $content);
    $tmp = cutstr($content, "<meta itemprop=\"datePublished\"", ">");
    if(empty($tmp))
    {
        return "";
    }
    
    $tmp = cutstr($tmp, "content=\"", "\"");
    $tmp = merge_space($tmp);
    return $tmp;
}

function get_std($content)
{

}

///////////////////////////////////////////////////////////////////////////
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

function one_line($content)
{
    $content = str_replace(array("\r","\n", "\t"), array(" ", " ", " "), $content);
    $content = merge_space($content);
    return $content;
}

function merge_space($content)
{
    while(false !== strpos($content, "  "))
    {
        $content = str_replace("  ", " ", $content); 
    }
    return $content;
}

function html_decode($content)
{
    $content = str_replace(
        array(
            "&nbsp;",
        ),
        array(
            " ",
        ),
        $content
    );
    return $content;
}

function is_tvseries($title)
{
    if(false !== stripos($title, "series"))
    {
        return true; 
    }
    else
    {
        return false;
    }
}

function is_not_yet_released($content)
{
    if(false !== stripos($content, "Not yet released"))
    {
        return true; 
    }
    else
    {
        return false;
    }
}

function get_release_info($tt)
{   
    $url = "http://www.imdb.com/title/" . $tt . "/releaseinfo?ref_=tt_ov_inf";
    $detail = file_get_contents($url);
    $release = cutstr($detail, "<table", "</table>");
    $rels = cutall($release, "<tr", "</tr>");

    $flag = 0;
    foreach($rels as $rel)
    {   
      $country = cutstr($rel, "&ref_=ttrel_rel_", "</td>");
      $country = cutstr($country, "\"\n>", "</a");
      $md      = cutstr($rel, "release_date\">", " <a href=");
      $year    = cutstr($rel, "/?ref_=ttrel_rel_", "</td");
      $year    = cutstr($year, "\"\n>", "</a>");

      $release_time = $md . " " . $year;
      $release_time = date("Y-m-d", strtotime($release_time));
      if($flag == 0)
      {   
          $first = array(
              "country" => $country,
              "release_time" => $release_time,
              "year" => $year,
          );  
 
         $flag = 1;
      }

      if($country == "India")
      {
          $india = array(
              "country" => $country,
              "release_time" => $release_time,
              "year" => $year,
          );
          return $india;
      }

      if($country == "USA" && empty($usa))
      {
          $usa = array(
              "country" => $country,
              "release_time" => $release_time,
              "year" => $year,
          );
      }
    }

    if(!empty($usa))
    {
       return $usa;
    }
    else
    {
       return $first;
    }
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

  function  is_exists_imdb($imdbid)
  {
       $sql = "SELECT * FROM  imdb_info WHERE imdbid = '$imdbid'";
       $res = mysql_query($sql);
       $result = mysql_fetch_array($res);
       return $result;
  }

  function data_into_db($movie_detail)
  {
      $imdbid = $movie_detail['imdbid'];
      $tt_info = is_exists_imdb($imdbid);
      $lastupdate = time();
      if(empty($tt_info))
      {
         //inert
         $keys = "";
         $values = "";
         $movie_detail['ctime'] = time();
         foreach($movie_detail as $key=>$value)
         {
            if($value === "" || $value == null)
            {
               continue;
            }

            $keys = $keys . $key . "," ;
            $value = mysql_escape_string($value);
                       $values = $values . "'$value'" . ",";
         }

         $keys = $keys . "lastupdate";
         $values = $values . "'$lastupdate'";

         $sql = "insert into imdb_info($keys) values ($values)";
         file_put_contents("/tmp/imdb.log", "sql === $sql\n");
         echo $sql . "\n";
         mysql_query($sql);
      }
      else
      {
         //update
         $values = "";
         foreach($movie_detail as $key=>$value)
         {
            if($key == "imdbid")
            {
               continue;
            }

            if($value === "" || $value == null)
            {
               continue;
            }

            if($value === "" || $value == null)
            {
               continue;
            }

            $value = mysql_escape_string($value);
            $values = $values . $key . "=" . "'$value'" . ",";
         }

         $values = $values . "lastupdate= '$lastupdate'";
         $sql = "update imdb_info set $values where imdbid = '$imdbid'";
         file_put_contents("/tmp/imdb.log", "sql === $sql\n");
         echo $sql . "\n";
         mysql_query($sql);
      }
  }


function get_bookmyshow_info($id)
{
   file_put_contents("/tmp/imdb.log", "get bookmyshow start:$id !!!\n");
   $detail_url = "https://in.bookmyshow.com/banga/movies/aaa/$id";
   $detail = file_get_contents($detail_url);
   if(empty($detail))
   {   
     continue;
   }   

   $title = cutstr($detail, "__name\" title=\"", "\"");
   if(empty($title))
   {
      $title = cutstr($detail, "og:title\" content=\"", "\"");
      if(!empty($title))
      {
         $title = str_replace(" (U)", "", $title); 
         $title = str_replace(" (A)", "", $title); 
         $title = str_replace(" (U/A)", "", $title); 
      }
      else
      {
         continue;
      }
   }
   $release_time = ""; 
   $genre = ""; 
   $actor = ""; 
   $writer = ""; 
   $musician = ""; 
   $description = ""; 
   $release_time = cutstr($detail, "__release-date\">", "</span>");
   $release_time = trim($release_time);
   $release_time = str_replace(",", "", $release_time);
   $genre = cutstr($detail, "Genre\":\"", "\""); 
   $director = cutstr($detail, "Director:", "/a>");

   if($director)
   {   
       $director = cutstr($director, "\">", "<");
   }   

   $writer = cutstr($detail, "Writer:", "/a>");
   if($writer)
   {
       $writer = cutstr($writer, "\">", "<");
   }

   $musician = cutstr($detail, "Musician:", "/a>");
   if($musician)
   {
       $musician = cutstr($musician, "\">", "<");
   }

   $description = cutstr($detail, "=\"og:description\" content=\"", "\"");
   if($description)
   {
      $description = trim($description);
   }

   //handle release_time
   $release_time = preg_replace('/\([A-Za-z0-9\.\_\-– \\\"]+\)/', '', $release_time);
   $year = "";
   $month = "Dec";
   $day = "31";
   $date_arr = split(" ", $release_time);
   if(count($date_arr)>=3)
   {
      $day = intval($date_arr[0]);
      $month =  $date_arr[1];
      $year = intval($date_arr[2]);
   }
   elseif(count($date_arr)==2)
   {
      $month =  $date_arr[0];
      $year = intval($date_arr[1]);
   }
   else
   {
      $year = intval($date_arr[0]);
   }

   $release_time = "$day $month $year";

   $release_time = date("Y-m-d", strtotime($release_time));
   // handle title
   $title  = preg_replace('/\([A-Za-z0-9\.\_\-– \\\"]+\)/', '', $title);
   $title = trim($title);
   if(!empty($year))
   {
      $title = $title . " ($year)";
   }

   $is_coming_soon = '0';
   $is_not_yet_released = '0';
   if(strtotime($release_time) > time())
   {
     $is_coming_soon = '1';
     $is_not_yet_released = '1';

     //插入记录
     $sql = "replace into movie_coming_soon(imdbid, `date`, type) values('".$id."', '".$release_time."', 'bookmyshow')";
     mysql_query($sql, $conn);
   }

   $actor = cutstr($detail, "__cast-member", ">");
   if($actor)
   {
      $actor = cutstr($actor, "content=\"", "\"");
   }

   $movie_detail = array
   (
      "title"=> $title,
      "release_date"=> $release_time,
      "year"=> $year,
      "country" => "india",
      "language"=> $lang,
      "imdbid"=> $id,
      "actors"=> $actor,
      "directors"=> $director,
      "writers"=> $writer,
      "genres"=> $genre,
      "summary"=> $description,
      "is_coming_soon" => $is_coming_soon,
      "is_not_yet_released" => $is_not_yet_released,
   );

   if(!empty($description) &&  false === ($p1 = strpos($description, "Coming soon!")))
   {
       $movie_detail['storyline'] = $description;
   }

   print_r($movie_detail);
   data_into_db($movie_detail);   
}

