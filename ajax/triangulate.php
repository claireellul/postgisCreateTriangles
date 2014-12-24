<?php
	set_time_limit(0);
	include "dbconnect.php";
	include "delaunay.php";
echo(pg_dbname($db));
triangulate('temp');

function triangulate($tablename) {
ini_set('pgsql.allow_persistent',0);
global $dbparams;

$db = pg_connect($dbparams,  PGSQL_CONNECT_FORCE_NEW);
/*	$sql = "SELECT a.polyid, a.globalid, b.n,
	  	  st_x(st_pointn(st_exteriorring(a.geom),b.n)) AS x,
	  	  st_y(st_pointn(st_exteriorring(a.geom),b.n)) AS y,
	  	  st_z(st_pointn(st_exteriorring(a.geom),b.n)) as z
	  	FROM
	(SELECT globalid,  geom, nextval('coordlist') as polyid
	  FROM  trianglepolys where st_astext(triangles) = 'GEOMETRYCOLLECTION EMPTY') AS a, public.num_seq AS b
	  	WHERE b.n <= st_npoints(st_exteriorring(a.geom))
	  	and GeometryType(geom) = 'POLYGON'
ORDER BY globalid, polyid, n";
*/

$sql = "SELECT ab.id, string_agg(concat(CAST( x AS text ),',',CAST( y AS text ),',',CAST( z AS text )),',') as coordlistz
FROM (
		SELECT a.id, a.polyid, a.globalid, b.n,
			  st_x(st_pointn(st_exteriorring(a.geom),b.n)) AS x,
			  st_y(st_pointn(st_exteriorring(a.geom),b.n)) AS y,
			  st_z(st_pointn(st_exteriorring(a.geom),b.n)) as z
			FROM
		(SELECT id, globalid,  geom, nextval('coordlist') as polyid
		  FROM  trianglepolys where st_astext(triangles) = 'GEOMETRYCOLLECTION EMPTY' ) AS a, public.num_seq AS b
			WHERE b.n <= st_npoints(st_exteriorring(a.geom))
			and GeometryType(geom) = 'POLYGON'
	ORDER BY globalid, polyid, n) ab group by id";

/*	$sql = "SELECT a.id, string_agg(concat(CAST( x AS text ),',',CAST( y AS text ),',',CAST( z AS text )),',') as coordlistz
FROM (
		SELECT a.polyid, a.globalid, b.n,
			  st_x(st_pointn(st_exteriorring(a.geom),b.n)) AS x,
			  st_y(st_pointn(st_exteriorring(a.geom),b.n)) AS y,
			  st_z(st_pointn(st_exteriorring(a.geom),b.n)) as z
			FROM
		(SELECT globalid,  geom, nextval('coordlist') as polyid
		  FROM  trianglepolys where st_astext(triangles) = 'GEOMETRYCOLLECTION EMPTY' ) AS a, public.num_seq AS b
			WHERE b.n <= st_npoints(st_exteriorring(a.geom))
			and GeometryType(geom) = 'POLYGON'
	ORDER BY globalid, polyid, n) ab group by polyid ";*/
echo($sql);

	$query = pg_query($db, $sql);
	while ($row = pg_fetch_array($query,NULL, PGSQL_ASSOC)) {
		$triangle=new DelaunayTriangulation();
		//	pg_result_seek($query, 0);
		//	$row = pg_fetch_array($query,NULL, PGSQL_ASSOC);
		//$coords = pg_fetch_result($query, 0, 0);
			$coords = $row["coordlistz"];
			$id = $row["id"];
				echo("<br>");
				//$coords = "529535.100045126,14.0915426124362,529535.100045126,14.9855389188814,529535.543095986,14.9855389188814,529536.046684122,14.0915426124362,529535.100045126,14.0915426124362";
				echo($coords);
				$set=array();
				$tree=explode(',',$coords);
				// remove the last coords which are duplicated
				for ($i=0,$end=count($tree) - 3;$i<$end;$i+=3)
				{
					$set[]=array($tree[$i],$tree[$i+1],$tree[$i+2]);
				}
				echo("<br><Br>");
				print_r($set);
				$triangle->main($set);
				print_r($triangle);

				// now insert the result into the database
				echo("<br><BR>pointset");
				print_r($triangle->pointset);
				echo("<br><BR>");
		      // $key is the key, $array is the value for each item in array $x
		      // $x is the array that contains all the x values PLUS the three great circle values of -1000000000, 0 and 1000000000
		      // i.e. 7 values in total for our test iteration
		      $geometrystring = "GEOMETRYCOLLECTION (";
		      foreach ($triangle->indices as $triangleindex => $trianglearray)
		      {
		        	print_r($indexkey);
		        	echo(" value ");
		        	print_r($indexarr);
		        	$polygonstring = "POLYGON ((";
		        	$nodecount = 0;
				    foreach ($trianglearray as $pointkey => $pointid){
				    	$nodecount = $nodecount + 1;
						// these are the point ids - to get the coordinates required, query the pointset array
						echo("point id ".$pointid);
						print_r($triangle->pointset[$pointid]);
						$x = $triangle->pointset[$pointid][0];
						$y = $triangle->pointset[$pointid][1];
						$z = $triangle->pointset[$pointid][2];
						$polygonstring = $polygonstring.$x." ".$y." ".$z.",";
						if ($nodecount == 1) {
							$firstx = $x;
							$firsty = $y;
							$firstz = $z;
						}
				    }
				    // complete the polygon by repeating the first pointset
				    $polygonstring = $polygonstring.$firstx." ".$firsty." ".$firstz.")),";
					$geometrystring = $geometrystring.$polygonstring;
				    // column to insert into is triangles
				    // table is: trianglepolys
				    // a polygon looks like this:
				    /* "GEOMETRYCOLLECTION Z (POLYGON Z ((529521.376878298 182238.700127146 1.02523780691187,
				    529525.511197145 182233.04278369 1.02523780691187,529522.188326634 182238.572759444 1.02523780691187,
				    529521.376878298 182238.700127146 1.02523780691187)),POLYGON Z ((529522.188326634 182238.572759444
				    1.02523780691187,529525.511197145 182233.04278369 1.02523780691187,529525.979479803 182233.384999049
				    1.02523780691187,529522.188326634 182238.572759444 1.02523780691187)))" */
			}
			// strip off the final , and add the final bracket
			$geometrystring = rtrim($geometrystring,',');
			$geometrystring = $geometrystring.")";
			echo($geometrystring);
			$updatesql = "update trianglepolys set triangles = st_geomfromtext('".$geometrystring."') where id= ".$id;
			echo($updatesql);
			$query = pg_query($db, $updatesql);

	} // end of loop for the missing polygons
pg_close($db);
}
?>