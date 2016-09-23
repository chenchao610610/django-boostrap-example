<?php 
   $url = $argv[1];
   if(empty($url))
   {
      echo "url is empty!!!\n";
      return;
   }


   $cmd = "source ~/local/env_cc/bin/activate; cd /home/webserver/movie_spider; scrapy parse --pipeline --spider=onlinemoviespeed  --callback=parse_item $url > /tmp/crawler_movie.log";
   system($cmd);





  // $imdbid = $argv[2];
  // if(empty($imdbid))
  // {
  //     return;     
  // }


  // $title = $argv[3];
  // if(empty($title))
  // {
  //     return;
  // }

   //查找imdb库中的信息，检查movie_info表中是否有记录
   //如果有则直接爬去,并入库download_source表

   //如果imdb_info表不存在 ==> 则爬取imdb_info ==> movie_info
   //如果imdb_info有记录， ==> 则需movie_info
   //入库download_source表

