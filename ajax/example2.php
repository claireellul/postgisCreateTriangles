<?php

/***************************************************************
* Copyright notice
*
* (c) 2014 Chi Hoang (info@chihoang.de)
*  All rights reserved
*
***************************************************************/
 require_once("main.php");
 
 // Turn off all error reporting
error_reporting(0);

// example 1
$set=array();
$tree=array(145,136,103,141,305,209,127,65,502,80,310,942,206,316,300,259,222,651,3,305,698,355,387,324,175,133,503,232,386,247,118,289,857,42,341,225,65,149,964,324,208,714,386,77,107);
for ($i=0,$end=count($tree);$i<$end;$i+=3)
{
//    $set[]=array($tree[$i],$tree[$i+1],$tree[$i+2]);    
}
$tri=new voronoi();
$set=$tri->main();
list($tree,$size)=$tri->buildtree($set); 

$nearest=new nearestneighbor();
//$p=new Point(12,322);
$p=new Point(60,60);
//$p=new Point(26,229);
$find=$nearest->main($tri,$tree,$p);
$nearest->show($tri,$find,0,$p);

//example2
//$set=array();
//$tree=array(172,31,238,106,233,397,118,206,58,28,268,382,10,380,342,26,67,371,380,14,382,200,24,200,194,190,10,88,276,331);
//for ($i=0,$end=count($tree);$i<$end;$i+=2)
//{
//    $set[]=array($tree[$i],$tree[$i+1]);    
//}
//$tri=new voronoi();
//list($tree,$size)=$tri->buildtree($set); 

//$nearest=new nearestneighbor();
//$p=new Point(12,322);
//$p=new Point(160,160);
//$p=new Point(326,229);
//$p=new Point(188,298);

//$find=$nearest->main($tri,$tree,$p);
//$nearest->show($tri,$find,0,$p);

// experimental point-in-polygon
//=$nearest->polytest($tri,0,$p);
//$find=$nearest->polytest($tri,$size-4,$p);
?>