<?php
class Admin_ChartviewsController extends SmartTouchRequestProcessor
{
	
	public function indexAction()
	{   
		mysql_select_db($_SESSION['selectDbname']) or die ("Unable to select database!");
		$chartName = $this->_request->getParam('chartName');
	    $startDate = $this->_request->getParam('startDate');
	    $startDate1 = $this->_request->getParam('startDate');
	    $endDate = $this->_request->getParam('endDate');
	    $clientID = $this->_request->getParam('clientID');
	    $sqlstartDate = date("Y-m-d", strtotime($startDate));
	    $sqlendDate = date("Y-m-d", strtotime($endDate));
	    $_SESSION['startDate'] = $startDate;
	    $_SESSION['endDate'] = $endDate;
	    $_SESSION['chartName'] = $chartName;
	    $p = 0;
	    $dateRange[$p] = $sqlstartDate;
		while (strtotime($startDate1) < strtotime($endDate)) {
		    $p++;
			$dateRange[$p] = date ("Y-m-d", strtotime("+1 day", strtotime($startDate1)));
			$startDate1 = $dateRange[$p];
		}
		
	 	// logic with missing dates
        // Sommelier Selections = featured
        // dessert = pairing
        for($q=0;$q<count($dateRange);$q++)
		{ 
/*				 
			$sqlsel = "select f2.name,f2.color, IF(A.category_id is null, (select category_id from first_touch where name = f2.name), A.category_id) as category_id, IF(A.first_touch is null, '0', A.first_touch) as first_touch, A.created_at from ";
			$sqlsel .= "(select IF(count(t.by_glass) > 0, (select name from first_touch where id =2), (IF(count(t.attrib_ids) > 0, (select name from first_touch where id =3), f.name))) as name, t.category_id, ";
			$sqlsel .= "(count(t.category_id) + count(t.by_glass) + count(t.attrib_ids)) as first_touch, t.created_at from track_rank tr join track t on tr.id = t.id left join first_touch f on t.category_id = f.category_id ";
			$sqlsel .= "where t.client_id = '".$clientID."' && t.created_at = '".$dateRange[$q]."' && t.start = '1' group by t.created_at, t.category_id, t.attrib_ids) as A right join first_touch f2 on A.name = f2.name"; 
*/		
		
			$sqlsel = "select f4.name, C.color, f4.category_id, IF(C.first_touch is null, 0, C.first_touch) as first_touch, C.created_at from first_touch f4 left join (select B.name, f3.color, B.category_id, B.first_touch, B.created_at from first_touch f3 ";
			$sqlsel .= "left join (select IF(A.name is null && A.glass = 0, (select name from first_touch where category_id = 0), IF(A.name is null && A.attrib = 0, (select name from first_touch where category_id = 99), A.name)) as name, f2.color as color, "; 
			$sqlsel .= "A.category_id, (A.cat + A.glass + A.attrib) as first_touch, A.created_at from (select  IF(count(t.by_glass) > 0, (select name from first_touch where category_id = 0), (IF(count(t.attrib_ids) > 0 , (select name from first_touch where "; 
			$sqlsel .= "category_id = 99) , f.name))) as name, t.category_id, count(t.category_id) as cat ,count(t.by_glass) as glass , count(t.attrib_ids) as attrib, t.created_at from track t left join first_touch f on t.category_id = f.category_id where "; 
			$sqlsel .= "t.client_id = '".$clientID."' && t.created_at = '".$dateRange[$q]."'  && t.start = '1'  group by t.created_at, t.category_id, t.attrib_ids) as A left join first_touch f2 on A.name = f2.name) as B on f3.name = B.name) as C on f4.color = C.color order by f4.category_id";


			$zero = "0";
            $t = explode('-',$dateRange[$q]);
            $dt = $t[1]."-".$t[2];
			
	        //die_r($sqlsel);   
            //$result = mysql_query($sqlsel) or die($sqlsel."<br/><br/>".mysql_error());
            $result = mysql_query($sqlsel);

			$catmatch = array("redatt","pinkatt","yellowatt","greenatt","blueatt","cyanatt","orangeatt");
            $num = 0;
            $i = $j = $k = $l = $m = $n = 0;
            $num = mysql_num_rows($result);
            if($num > 0)
            {
            while($i < $num)
            {  
                $ftcount[$i] = mysql_result($result,$i,"first_touch");
                $cat_id[$i] = mysql_result($result,$i,"category_id");
                $created_at[$i] = mysql_result($result,$i,"created_at");
                $firsttouchName[$i] = mysql_result($result,$i,"name");
                $ftColorcode[$i] = mysql_result($result,$i,"color");
                
            	if(empty($cat_id[$i]))
            	{
            		if (strpos($firsttouchName[$i],'Glass') !== false) {
    					$cat_id[$i] = 0;
					}
            		if ((strpos($firsttouchName[$i],'Selections') !== false) || (strpos($firsttouchName[$i],'Featured') !== false)) {
    					$cat_id[$i] = 11;
					}
            	}
				
            	
            	if($ftColorcode[$i] == "redatt")
   				{
   					 $winesglassinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$i]);
   				}
   				else if($ftColorcode[$i] == "pinkatt")
   				{
   					 $winesbottleinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$i]);
   				}
				else if($ftColorcode[$i] == "yellowatt")
   				{
   			 		$beerinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$i]);
   			 	}
				else if($ftColorcode[$i] == "blueatt")
   				{
   					 $cocktailsinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$i]);
   				}
   				else if($ftColorcode[$i] == "greenatt")
   				{
   					$spiritsinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$i]);
   				}
   				else if($ftColorcode[$i] == "cyanatt")
   				{
   					 $pairinginnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$i]);
   				}
   				else if($ftColorcode[$i] == "orangeatt")
   				{
   					 $featuredinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$i]);
   				}
                                
                $i++;
            }
			
            	$missedColor = array_diff($catmatch,$ftColorcode);
            	foreach ($missedColor as $key => $value)
            	{
					if($value == "redatt")
	   				{
	   					 $winesglassinnerone[$q] = array ( 'X' => $dt, 'Y' => $zero);
	   				}
	   				else if($value == "pinkatt")
	   				{
	   					 $winesbottleinnerone[$q] = array ( 'X' => $dt, 'Y' => $zero);
	   				}
					else if($value == "yellowatt")
	   				{
	   			 		$beerinnerone[$q] = array ( 'X' => $dt, 'Y' => $zero);
	   			 	}
					else if($value == "blueatt")
	   				{
	   					 $cocktailsinnerone[$q] = array ( 'X' => $dt, 'Y' => $zero);
	   				}
	   				else if($value == "greenatt")
	   				{
	   					$spiritsinnerone[$q] = array ( 'X' => $dt, 'Y' => $zero);
	   				}
	   				else if($value == "cyanatt")
	   				{
	   					 $pairinginnerone[$q] = array ( 'X' => $dt, 'Y' => $zero);
	   				}
	   				else if($value == "orangeatt")
	   				{
	   					 $featuredinnerone[$q] = array ( 'X' => $dt, 'Y' => $zero);
	   				}
			  		
				}
            }
            else 
            {
            	$t = explode('-',$dateRange[$q]);
				$dt = $t[1]."-".$t[2];
				$ftcount[$i] = 0;
				$winesglassinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$i]);
				$winesbottleinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$i]);
				$beerinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$i]);
				$cocktailsinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$i]);
				$spiritsinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$i]);
				$pairinginnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$i]);
				$featuredinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$i]);
				$ftColorcode = array("redatt","pinkatt","yellowatt","blueatt","greenatt","cyanatt","orangeatt");
            }
	            
		}	
		
		//$moduleId = $this->getModule();
		$moduleId = $_SESSION['moduleId'];
		
		if(!empty($_SESSION['chartValues'])) {
			unset($_SESSION['chartValues']);
		}
		$chartValues = array('winesglass' => $winesglassinnerone, 'winesbottle' => $winesbottleinnerone, 'beer'  => $beerinnerone, 'spirits' => $cocktailsinnerone, 'cocktails' => $spiritsinnerone, 'pairings' => $pairinginnerone, 'featured' => $featuredinnerone,'module' => $moduleId, 'header' => $firsttouchName, 'ftColor' =>$ftColorcode);
	    $_SESSION['chartValues'] = array();
		$_SESSION['chartValues'] = $chartValues;

	    $winesglass['values'] = $winesglassinnerone;
	    $winesbottle['values'] = $winesbottleinnerone;
	    $beer['values'] = $beerinnerone;
	    $spirits['values'] = $cocktailsinnerone;
	    $cocktails['values'] = $spiritsinnerone;
	    $pairings['values'] = $pairinginnerone;
	    $featured['values'] = $featuredinnerone;
	    
/*	    
	    $wines['values'] = array ( 0 => array ( 'X' => 'Jan', 'Y' => 12, ), 1 => array ( 'X' => 'Feb', 'Y' => 28, ), 2 => array ( 'X' => 'Mar', 'Y' => 18, ), 3 => array ( 'X' => 'Apr', 'Y' => 60, ), 4 => array ( 'X' => 'May', 'Y' => 40, ));
	    $beer['values'] = array ( 0 => array ( 'X' => 'Jan', 'Y' => 22, ), 1 => array ( 'X' => 'Feb', 'Y' => 38, ), 2 => array ( 'X' => 'Mar', 'Y' => 28, ), 3 => array ( 'X' => 'Apr', 'Y' => 44, ), 4 => array ( 'X' => 'May', 'Y' => 32, ));
	    $spirits['values'] = array ( 0 => array ( 'X' => 'Jan', 'Y' => 60, ), 1 => array ( 'X' => 'Feb', 'Y' => 50, ), 2 => array ( 'X' => 'Mar', 'Y' => 40, ), 3 => array ( 'X' => 'Apr', 'Y' => 30, ), 4 => array ( 'X' => 'May', 'Y' => 20, ));
	    $cocktails['values'] = array ( 0 => array ( 'X' => 'Jan', 'Y' => 35, ), 1 => array ( 'X' => 'Feb', 'Y' => 5, ), 2 => array ( 'X' => 'Mar', 'Y' => 10, ), 3 => array ( 'X' => 'Apr', 'Y' => 15, ), 4 => array ( 'X' => 'May', 'Y' => 25, ));
*/	    
	    
	    $allVisits1 = array ( 'winesglass' => $winesglass,
	    					  'winesbottle' => $winesbottle, 
	                          'beer' =>  $beer, 
	                          'spirits' => $spirits, 
	                          'cocktails' => $cocktails,
	                          'pairings' => $pairings, 
	                          'featured' => $featured,
	    					  'module' => $moduleId,
	    					  'header' => $firsttouchName,
	    					  'ftColor' =>$ftColorcode);
	    
	   	$this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        echo json_encode($allVisits1);

		
	}
	
	public function lineAction()
	{
		mysql_select_db($_SESSION['selectDbname']) or die ("Unable to select database!"); 
		$chartName = $this->_request->getParam('chartName');
	    $startDate = $this->_request->getParam('startDate');
	    $startDate1 = $this->_request->getParam('startDate');
	    $endDate = $this->_request->getParam('endDate');
	    $clientID = $this->_request->getParam('clientID');
	    $sqlstartDate = date("Y-m-d", strtotime($startDate));
	    $sqlendDate = date("Y-m-d", strtotime($endDate));
	    $_SESSION['startDate'] = $startDate;
	    $_SESSION['endDate'] = $endDate;
	    $_SESSION['chartName'] = $chartName;
	    
	    $p = 0;
	    $dateRange[$p] = $sqlstartDate;
		while (strtotime($startDate1) < strtotime($endDate)) {
		    $p++;
			$dateRange[$p] = date ("Y-m-d", strtotime("+1 day", strtotime($startDate1)));
			$startDate1 = $dateRange[$p];
		}
		
		
	// logic with missing dates
                
        for($q=0;$q<count($dateRange);$q++)
		{  
			$sqlsel = "select t.category_id, (count(t.category_id) + count(t.by_glass)) as first_touch, t.created_at from track_rank tr ";
			$sqlsel .= "join track t on tr.id = t.id where t.client_id = '".$clientID."' && t.created_at = '".$dateRange[$q]."' group by t.created_at, t.category_id";
	        //die_r($sqlsel);   
            $result = mysql_query($sqlsel);
			$catmatch = array("0","1","2","3","4","10","11");
            $num = 0;
            $i = $j = $k = $l = $m = $n = 0;
            $num = mysql_num_rows($result);
            if($num > 0)
            {
            while($i < $num)
            {  
                $ftcount[$i] = mysql_result($result,$i,"first_touch");
                $cat_id[$i] = mysql_result($result,$i,"category_id");
                $created_at[$i] = mysql_result($result,$i,"created_at");
                if(empty($cat_id[$i]))
            	{
            		$cat_id[$i] = 0;
            	}
            	             
                $i++;
            }
            
            $zero = 0;
            $t = explode('-',$dateRange[$q]);
            $dt = $t[1]."-".$t[2];
            for($m=0;$m<count($catmatch);$m++)
			{  
				
                if($catmatch[$m] == 0)
                {	
                	if (isset($cat_id[$m]))
                	{
   					 	if($cat_id[$m] == 0)
   					 	{
   					 		$winesglass = $ftcount[$m];
   					 	}
   					 	else 
   					 	{
   					 		$winesglass = $zero;
   					 	}
					}
                	else 
   				 	{
   				 		$winesglass = $zero;
   				 	}
                	
                }
                else if($catmatch[$m] == 1)
                {
                	if (isset($cat_id[$m]))
                	{
   					 	if($cat_id[$m] == 1)
   					 	{
   					 		$winesinnerone[$dt] =  $ftcount[$m] + $winesglass;
   					 	}
   					 	else 
   					 	{
   					 		$winesinnerone[$dt] = $zero;
   					 	}
					}
                	else 
   				 	{
   				 		$winesinnerone[$dt] = $zero;
   				 	}
                }
                else if($catmatch[$m] == 2)
                {
                	if (isset($cat_id[$m]))
                	{
   					 	if($cat_id[$m] == 2)
   					 	{
   					 		$beerinnerone[$dt] = $ftcount[$m] + $ftcount[$m] - $ftcount[$m];
   					 	}
   					 	else 
   					 	{
   					 		$beerinnerone[$dt] = $zero;
   					 	}
					}
                	else 
   				 	{
   				 		$beerinnerone[$dt] = $zero;
   				 	}
                }
                else if($catmatch[$m] == 3)
                {
                	if (isset($cat_id[$m]))
                	{
   					 	if($cat_id[$m] == 3)
   					 	{
   					 		$cocktailsinnerone[$dt] = $ftcount[$m] + $ftcount[$m] - $ftcount[$m];
   					 	}
   					 	else 
   					 	{
   					 		$cocktailsinnerone[$dt] = $zero;
   					 	}
					}
                	else 
   				 	{
   				 		$cocktailsinnerone[$dt] = $zero;
   				 	}
                }
            	else if($catmatch[$m] == 4)
                {
                	if (isset($cat_id[$m]))
                	{
   					 	if($cat_id[$m] == 4)
   					 	{
   					 		$spiritsinnerone[$dt] = $ftcount[$m] + $ftcount[$m] - $ftcount[$m];
   					 	}
   					 	else 
   					 	{
   					 		$spiritsinnerone[$dt] = $zero;
   					 	}
					}
                	else 
   				 	{
   				 		$spiritsinnerone[$dt] = $zero;
   				 	}
                }
				
			}          
                          
            
            }
            else 
            {
            	$t = explode('-',$dateRange[$q]);
				$dt = $t[1]."-".$t[2];
				$winesinnerone[$dt] = 0;
				$beerinnerone[$dt] = 0;
				$cocktailsinnerone[$dt] = 0;
				$spiritsinnerone[$dt] = 0;
            }
	            
            
		}
		
		if(!empty($_SESSION['chartValues'])) {
			unset($_SESSION['chartValues']);
		}
		$chartValues = array('wines' => $winesinnerone, 'beer'  => $beerinnerone, 'spirits' => $spiritsinnerone, 'cocktails' => $cocktailsinnerone);
	    $_SESSION['chartValues'] = array();
		$_SESSION['chartValues'] = $chartValues;
	    
/*	    
	    $wines['values'] = array ( 0 => array ( 'X' => 'Jan', 'Y' => 12, ), 1 => array ( 'X' => 'Feb', 'Y' => 28, ), 2 => array ( 'X' => 'Mar', 'Y' => 18, ), 3 => array ( 'X' => 'Apr', 'Y' => 60, ), 4 => array ( 'X' => 'May', 'Y' => 40, ));
	    $beer['values'] = array ( 0 => array ( 'X' => 'Jan', 'Y' => 22, ), 1 => array ( 'X' => 'Feb', 'Y' => 38, ), 2 => array ( 'X' => 'Mar', 'Y' => 28, ), 3 => array ( 'X' => 'Apr', 'Y' => 44, ), 4 => array ( 'X' => 'May', 'Y' => 32, ));
	    $spirits['values'] = array ( 0 => array ( 'X' => 'Jan', 'Y' => 60, ), 1 => array ( 'X' => 'Feb', 'Y' => 50, ), 2 => array ( 'X' => 'Mar', 'Y' => 40, ), 3 => array ( 'X' => 'Apr', 'Y' => 30, ), 4 => array ( 'X' => 'May', 'Y' => 20, ));
	    $cocktails['values'] = array ( 0 => array ( 'X' => 'Jan', 'Y' => 35, ), 1 => array ( 'X' => 'Feb', 'Y' => 5, ), 2 => array ( 'X' => 'Mar', 'Y' => 10, ), 3 => array ( 'X' => 'Apr', 'Y' => 15, ), 4 => array ( 'X' => 'May', 'Y' => 25, ));
*/   


		$wines['values'] = $winesinnerone;
	    $beer['values'] = $beerinnerone;
	    $spirits['values'] = $spiritsinnerone;
	    $cocktails['values'] = $cocktailsinnerone;

	    $allVisits1 = array ( 'wines' => $wines, 
	                          'beer' =>  $beer, 
	                          'spirits' => $spirits, 
	                          'cocktails' => $cocktails);

	   	$this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        echo json_encode($allVisits1);
	}
	
	public function attributeAction()
	{  
		mysql_select_db($_SESSION['selectDbname']) or die ("Unable to select database!"); 
		$chartName = $this->_request->getParam('chartName');
	    $startDate = $this->_request->getParam('startDate');
	    $endDate = $this->_request->getParam('endDate');
	    $clientID = $this->_request->getParam('clientID');
	    $sqlstartDate = date("Y-m-d", strtotime($startDate));
	    $sqlendDate = date("Y-m-d", strtotime($endDate));
	    $_SESSION['startDate'] = $startDate;
	    $_SESSION['endDate'] = $endDate;
	    $_SESSION['chartName'] = $chartName;
	    $_SESSION['clientID'] = $clientID;
	    
	    if((strtotime($endDate) - strtotime($startDate)) >= 0)
	    {
            //$allVisits = array("a_val" => "16","b_val" => "25","c_val" => "45","d_val" => "37","e_val" => "22");
            $sqlsel = "select count(type_id) as type_count,count(country_id) as country_count, count(region_id) as region_count, count(appellation_id) as appellation_count, ";
			$sqlsel .= "count(producer_id) as producer_count, count(grape_id) as grape_count from track_gdb ";
			$sqlsel .= "where client_id = '".$clientID."' && created_at between '".$sqlstartDate."' and '".$sqlendDate."'";
	        //die_r($sqlsel);   
            $result = mysql_query($sqlsel);
            //$result = mysql_query($sqlsel) or die($sqlsel."<br/><br/>".mysql_error());
            $num = 0;
            $i = 0;
            $num = mysql_numrows($result);
            while($i < $num)
            {  
				$type = mysql_result($result,$i,"type_count");
            	$country = mysql_result($result,$i,"country_count");
                $region = mysql_result($result,$i,"region_count");
                $appellation = mysql_result($result,$i,"appellation_count");
                $producer = mysql_result($result,$i,"producer_count");
                $grape = mysql_result($result,$i,"grape_count");
                $i++;
            }
            $allVisits = array("f_val" => $type,"a_val" => $country,"b_val" => $region,"c_val" => $appellation,"d_val" => $producer,"e_val" => $grape);
        }
	    else 
	    {  
	    	$allVisits = array("a_val" => "error");
	    	
	    }  
	    $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        echo json_encode($allVisits);

		
	}
	
	public function piechartAction()
	{
		mysql_select_db($_SESSION['selectDbname']) or die ("Unable to select database!");   
		$chartName = $this->_request->getParam('chartName');
	    $startDate = $this->_request->getParam('startDate');
	    $endDate = $this->_request->getParam('endDate');
	    $clientID = $this->_request->getParam('clientID');
	    $divName = $this->_request->getParam('divName');
	    $sqlstartDate = date("Y-m-d", strtotime($startDate));
	    $sqlendDate = date("Y-m-d", strtotime($endDate));
    
	    if((strtotime($endDate) - strtotime($startDate)) >= 0)
	    {
	    	if($divName == "graphattr2")
	    	{
            	$sqlsel = "select t.description, count(g.country_id) as total_count from track_gdb g ";
				$sqlsel .= "join ".$_SESSION['thisDbname'].".inventory_wine i on g.country_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id  ";
				$sqlsel .= "where client_id = '".$clientID."' && g.country_id is not null && g.created_at between '".$sqlstartDate."' and '".$sqlendDate."' group by g.country_id";
	    	}
	    	else if($divName == "graphattr3")
	    	{
	    		$sqlsel = "select t.description, count(g.region_id) as total_count from track_gdb g ";
				$sqlsel .= "join ".$_SESSION['thisDbname'].".inventory_wine i on g.region_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id ";
				$sqlsel .= "where client_id = '".$clientID."' && g.region_id is not null && g.created_at between '".$sqlstartDate."' and '".$sqlendDate."' group by g.region_id";
	    	}
	    	else if($divName == "graphattr4")
	    	{
	    		$sqlsel = "select t.description, count(g.appellation_id) as total_count from track_gdb g ";
				$sqlsel .= "join ".$_SESSION['thisDbname'].".inventory_wine i on g.appellation_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id ";
				$sqlsel .= "where client_id = '".$clientID."' && g.appellation_id is not null && g.created_at between '".$sqlstartDate."' and '".$sqlendDate."' group by g.appellation_id";
	    	}
	    	else if($divName == "graphattr5")
	    	{
	    		$sqlsel = "select t.description, count(g.producer_id) as total_count from track_gdb g ";
				$sqlsel .= "join ".$_SESSION['thisDbname'].".inventory_wine i on g.producer_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id ";
				$sqlsel .= "where client_id = '".$clientID."' && g.producer_id is not null && g.created_at between '".$sqlstartDate."' and '".$sqlendDate."' group by g.producer_id";	
	    	}
	    	else if($divName == "graphattr6")
	    	{
	    		$sqlsel = "select t.description, count(g.grape_id) as total_count from track_gdb g ";
				$sqlsel .= "join ".$_SESSION['thisDbname'].".inventory_wine i on g.grape_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id ";
				$sqlsel .= "where client_id = '".$clientID."' && g.grape_id is not null && g.created_at between '".$sqlstartDate."' and '".$sqlendDate."' group by g.grape_id";
	    	}
	    	else if($divName == "graphattr7")
	    	{
	    		$sqlsel = "select t.description, count(g.type_id) as total_count from track_gdb g ";
				$sqlsel .= "join ".$_SESSION['thisDbname'].".inventory_wine i on g.type_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id ";
				$sqlsel .= "where client_id = '".$clientID."' && g.type_id is not null && g.created_at between '".$sqlstartDate."' and '".$sqlendDate."' group by g.type_id";
	    	}
	    	
	        //die_r($sqlsel);   
            $result = mysql_query($sqlsel);
            $num = 0;
            $i = 0;
            $num = mysql_numrows($result);
            while($i < $num)
            {  
                $country = mysql_result($result,$i,"description");
                $totcount = mysql_result($result,$i,"total_count");
                $allVisits2[$country] = $totcount;
                $i++;
            }

        }
	    else 
	    {  
	    	$allVisits = array("a_val" => "error");
	    	
	    }  
	    
	    //$allVisits2 = array('USA' => '200', 'France' => '300', 'Itly' => '100', 'Germany' => '400');
	    $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        echo json_encode($allVisits2);

		
	}	
	
	public function beverageAction()
	{
		mysql_select_db($_SESSION['selectDbname']) or die ("Unable to select database!");   
		$chartName = $this->_request->getParam('chartName');
	    $startDate = $this->_request->getParam('startDate');
	    $endDate = $this->_request->getParam('endDate');
	    $clientID = $this->_request->getParam('clientID');
	    $sqlstartDate = date("Y-m-d", strtotime($startDate));
	    $sqlendDate = date("Y-m-d", strtotime($endDate));
	    $_SESSION['chartName'] = $chartName;
    
	    if((strtotime($endDate) - strtotime($startDate)) >= 0)
	    {
	    	
            $sqlsel[0] = "select t.description, count(g.country_id) as total_count from track_gdb g ";
			$sqlsel[0] .= "join ".$_SESSION['thisDbname'].".inventory_wine i on g.country_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id ";
			$sqlsel[0] .= "where g.client_id = '".$clientID."' && g.wine_id is not null && g.country_id is not null && g.created_at between '".$sqlstartDate."' and '".$sqlendDate."' group by g.country_id";
	    	
    		$sqlsel[1] = "select t.description, count(g.region_id) as total_count from track_gdb g ";
			$sqlsel[1] .= "join ".$_SESSION['thisDbname'].".inventory_wine i on g.region_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id ";
			$sqlsel[1] .= "where g.client_id = '".$clientID."' && g.wine_id is not null && g.region_id is not null && g.created_at between '".$sqlstartDate."' and '".$sqlendDate."' group by g.region_id";
/*	    	
	   		$sqlsel[2] = "select t.description, count(g.appellation_id) as total_count from track_gdb g ";
			$sqlsel[2] .= "join ".$_SESSION['thisDbname'].".inventory_wine i on g.appellation_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id ";
			$sqlsel[2] .= "where g.client_id = '".$clientID."' && g.wine_id is not null && g.appellation_id is not null && g.created_at between '".$sqlstartDate."' and '".$sqlendDate."' group by g.appellation_id; ";
	    	
	    	$sqlsel[3] = "select t.description, count(g.producer_id) as total_count from track_gdb g ";
			$sqlsel[3] .= "join ".$_SESSION['thisDbname'].".inventory_wine i on g.producer_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id ";
			$sqlsel[3] .= "where g.client_id = '".$clientID."' && g.wine_id is not null && g.producer_id is not null && g.created_at between '".$sqlstartDate."' and '".$sqlendDate."' group by g.producer_id";	

			$sqlsel[4] = "select t.description, count(g.grape_id) as total_count from track_gdb g ";
			$sqlsel[4] .= "join ".$_SESSION['thisDbname'].".inventory_wine i on g.grape_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id ";
			$sqlsel[4] .= "where g.client_id = '".$clientID."' && g.wine_id is not null && g.grape_id is not null && g.created_at between '".$sqlstartDate."' and '".$sqlendDate."' group by g.grape_id";
*/			
			$sqlsel[2] = "select t.description, count(g.grape_id) as total_count from track_gdb g ";
			$sqlsel[2] .= "join ".$_SESSION['thisDbname'].".inventory_wine i on g.grape_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id ";
			$sqlsel[2] .= "where g.client_id = '".$clientID."' && g.wine_id is not null && g.grape_id is not null && g.created_at between '".$sqlstartDate."' and '".$sqlendDate."' group by g.grape_id";


	    	for($k=0;$k < count($sqlsel);$k++)
	    	{
	        //die_r($sqlsel);   
	            $result = mysql_query($sqlsel[$k]);
	            $num = 0;
	            $i = 0;
	            $allVisits2 = array();
	            $num = mysql_numrows($result);
	            if($num > 0)
				{ 
		            while($i < $num)
		            {  
	            		$country = mysql_result($result,$i,"description");
	                	$totcount = mysql_result($result,$i,"total_count");
	                	$allVisits2[$country] = $totcount;
		                $i++;
		            }
		            
		            $allVisits[$k] = $allVisits2;
				}
				else 
				{
					$allVisits[$k] = "";
				}
	    	}

	    	if(!empty($_SESSION['chartValues'])) {
			unset($_SESSION['chartValues']);
			}
			//$chartValues = array('country' => $allVisits[0], 'region'  => $allVisits[1], 'appellation' => $allVisits[2], 'producer' => $allVisits[3], 'grape' => $allVisits[4]);
			$chartValues = array('country' => $allVisits[0], 'region'  => $allVisits[1], 'grape' => $allVisits[2]);
	    	$_SESSION['chartValues'] = array();
			$_SESSION['chartValues'] = $chartValues;
        }
	    else 
	    {  
	    	$allVisits = array("a_val" => "error");
	    	
	    }  
	    
	    //$allVisits2 = array('USA' => '200', 'France' => '300', 'Itly' => '100', 'Germany' => '400');
	    $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        echo json_encode($allVisits);

		
	}	
	
	
	public function navigationAction()
	{  
		mysql_select_db($_SESSION['selectDbname']) or die ("Unable to select database!"); 
		$chartName = $this->_request->getParam('chartName');
	    $startDate = $this->_request->getParam('startDate');
	    $endDate = $this->_request->getParam('endDate');
	    $clientID = $this->_request->getParam('clientID');
	    $sqlstartDate = date("Y-m-d", strtotime($startDate));
	    $sqlendDate = date("Y-m-d", strtotime($endDate));
	    $_SESSION['startDate'] = $startDate;
	    $_SESSION['endDate'] = $endDate;
	    $_SESSION['chartName'] = $chartName;
    	
	    $sqlsel = "(select count(concat_ws(',', ifnull(category_id_rank,'0'), ifnull(by_glass_rank,'0'), ifnull(collection_id_rank,'0'), ifnull(attrib_id_rank,'0'), ifnull(type_id_rank,'0'), ifnull(country_id_rank,'0'), ifnull(region_id_rank,'0'), ifnull(appellation_id_rank,'0'), ifnull(producer_id_rank, '0'), ifnull(grape_id_rank,'0'), ifnull(wine_id_rank,'0'))) as count,";
		$sqlsel .= "concat(if(o.category_id is null,'', 'By Bottle'), if(o.by_glass is null,'', 'By Glass'), if(o.collection_id is null,'', ' => Pairings'), if(o.attrib_id is null,'', ' => Size'), if(o.type_id is null,'', ' => Type'), if(o.country_id is null,'', ' => Country'), if(o.region_id is null,'', ' => Region'), if(o.appellation_id is null,'', ' => Appellation'), if(o.producer_id is null, '', ' => Producer'), if(o.grape_id is null,'', ' => Grape'), if(o.wine_id is null,'', ' => Wine')) as track_categories,";
		$sqlsel .= "concat(if(o.category_id is null,'0', '1'), if(o.by_glass is null,'0', '1'), if(o.collection_id is null,'0', '1'), if(o.attrib_id is null,'0', '1'), if(o.type_id is null,'0', '1'), if(o.country_id is null,'0', '1'), if(o.region_id is null,'0', '1'), if(o.appellation_id is null,'0', '1'), if(o.producer_id is null, '0', '1'), if(o.grape_id is null,'0', '1'), if(o.wine_id is null,'0', '1')) as track_code from track_rank r ";
		$sqlsel .= "join track t on r.id = t.id ";
		$sqlsel .= "join track_gdb_onclick o on t.track_id = o.track_id ";
		$sqlsel .= "where t.client_id = '".$clientID."' && r.client_id = '".$clientID."' && o.client_id = '".$clientID."' && o.wine_id is not null && (o.category_id || o.by_glass) is not null && ";
		$sqlsel .= "t.created_at between '".$sqlstartDate."' and '".$sqlendDate."' && o.created_at between '".$sqlstartDate."' and '".$sqlendDate."' ";
		$sqlsel .= "group by track_code order by count desc) ";
		$sqlsel .= "union (select count(concat(ifnull(type_id_rank,'0'), ifnull(wine_id_rank,'0'))) as count, ";
		$sqlsel .= "concat(if(o.type_id is null,'', 'Type'), if(o.wine_id is null,'', ' => Beer')) as track_categories, ";
		$sqlsel .= "concat(if(o.type_id is null,'0', '1'), if(o.wine_id is null,'0', '1')) as track_code from track_rank r ";
		$sqlsel .= "join track t on r.id = t.id ";
		$sqlsel .= "join track_gdb_onclick o on t.track_id = o.track_id ";
		$sqlsel .= "where t.client_id = '".$clientID."' && r.client_id = '".$clientID."' && o.client_id = '".$clientID."' && o.wine_id is not null && o.type_id is not null && r.type_id_rank is not null && t.category_id = 2 && ";
		$sqlsel .= "t.created_at between '".$sqlstartDate."' and '".$sqlendDate."' && o.created_at between '".$sqlstartDate."' and '".$sqlendDate."' ";
		$sqlsel .= "group by track_code order by count desc) ";
		$sqlsel .= "union (select count(concat(ifnull(type_id_rank,'0'), ifnull(wine_id_rank,'0'))) as count, ";
		$sqlsel .= "concat(if(o.type_id is null,'', 'Type'), if(o.wine_id is null,'', ' => Spirits')) as track_categories, ";
		$sqlsel .= "concat(if(o.type_id is null,'0', '1'), if(o.wine_id is null,'0', '1')) as track_code from track_rank r ";
		$sqlsel .= "join track t on r.id = t.id ";
		$sqlsel .= "join track_gdb_onclick o on t.track_id = o.track_id ";
		$sqlsel .= "where t.client_id = '".$clientID."' && r.client_id = '".$clientID."' && o.client_id = '".$clientID."' && o.wine_id is not null && o.type_id is not null && r.type_id_rank is not null && t.category_id = 4 && ";
		$sqlsel .= "t.created_at between '".$sqlstartDate."' and '".$sqlendDate."' && o.created_at between '".$sqlstartDate."' and '".$sqlendDate."' ";
		$sqlsel .= "group by track_code order by count desc) ";

		$result = mysql_query($sqlsel);
        $num = 0;
        $i = 0;
        $tot = 0;
        $num = mysql_numrows($result);
        while($i < $num)
        {  
        	$tot = $tot + mysql_result($result,$i,"count");
	        $countinnerone[$i] = mysql_result($result,$i,"count");
	        $pathinnerone[$i] = mysql_result($result,$i,"track_categories");
	        $codeinnerone[$i] = mysql_result($result,$i,"track_code");
	        $i++;
        }   
        
        for($q=0;$q<count($countinnerone);$q++)
		{  
			$percentinnerone[$q] = ($countinnerone[$q] * 100) / $tot;
			$percentinnerone[$q] = round($percentinnerone[$q],2);
		 		 	
		} 
	    
		if(!empty($_SESSION['chartValues'])) {
			unset($_SESSION['chartValues']);
		}
		$chartValues = array('path' => $pathinnerone, 'count'  => $countinnerone, 'percent' => $percentinnerone, 'code' => $codeinnerone);
	    $_SESSION['chartValues'] = array();
		$_SESSION['chartValues'] = $chartValues;
		
//	    $pathinnerone = array("by bottle => type => wine","by bottle => type => country => wine","by bottle => type => grape => wine","by bottle => type => all => wine","by bottle => type => half bottles => wine","by bottle => type => large format => wine","by glass => wine");
//	    $countinnerone = array('10','20','30','40','50','60','70');
	    
	    $path['values'] = $pathinnerone;
	    $count['values'] = $countinnerone;
	    $code['values'] = $codeinnerone;
	    $percent['values'] = $percentinnerone;


	    $allVisits1 = array ( 'path' => $path, 
	                          'count' =>  $count,
	    					  'code' =>  $code,
	    						'percent' =>  $percent);
	    
	    //$allVisits2 = array('USA' => '200', 'France' => '300', 'Itly' => '100', 'Germany' => '400');
	    $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        echo json_encode($allVisits1);

		
	}
	
	
	
	public function navigationpopupAction()
	{ 
		mysql_select_db($_SESSION['selectDbname']) or die ("Unable to select database!");  
		$codeId = $this->_request->getParam('codeId');
	    $startDate = $this->_request->getParam('startDate');
	    $endDate = $this->_request->getParam('endDate');
	    $clientID = $this->_request->getParam('clientID');
	    $sqlstartDate = date("Y-m-d", strtotime($startDate));
	    $sqlendDate = date("Y-m-d", strtotime($endDate));
    	
	    $sqlsel = "select count(concat_ws(',', ifnull(category_id_rank,'0'), ifnull(by_glass_rank,'0'), ifnull(collection_id_rank,'0'), ifnull(attrib_id_rank,'0'), ifnull(type_id_rank,'0'), ifnull(country_id_rank,'0'), ifnull(region_id_rank,'0'), ifnull(appellation_id_rank,'0'), ifnull(producer_id_rank, '0'), ifnull(grape_id_rank,'0'), ifnull(wine_id_rank,'0'))) as count, ";
		$sqlsel .= "concat(if(o.category_id is null,'0', '1'), if(o.by_glass is null,'0', '1'), if(o.collection_id is null,'0', '1'), if(o.attrib_id is null,'0', '1'), if(o.type_id is null,'0', '1'), if(o.country_id is null,'0', '1'), if(o.region_id is null,'0', '1'), if(o.appellation_id is null,'0', '1'), if(o.producer_id is null, '0', '1'), if(o.grape_id is null,'0', '1'), if(o.wine_id is null,'0', '1')) as track_code, ";
		$sqlsel .= "concat_ws(' => ', if(o.category_id = 1, 'By Bottle', NULL), if(o.by_glass = 1, 'By Glass', NULL), if(o.collection_id is null, NULL, o.collection_id), if(o.attrib_id is null, NULL, 'Featured'), nullif(t2.description, NULL), nullif(t3.description, NULL), nullif(t4.description, NULL), nullif(t5.description, NULL), nullif(t6.description, NULL), nullif(t7.description, NULL), t8.description) as path ";
		$sqlsel .= "from track_rank r join track t on r.id = t.id join track_gdb_onclick o on t.track_id = o.track_id ";
		$sqlsel .= "left join ".$_SESSION['thisDbname'].".inventory_wine i2 on o.type_id = i2.id left join ".$_SESSION['thisDbname'].".tag_wine t2 on i2.tag_id = t2.id left join ".$_SESSION['thisDbname'].".inventory_wine i3 on o.country_id = i3.id left join ".$_SESSION['thisDbname'].".tag_wine t3 on i3.tag_id = t3.id ";
		$sqlsel .= "left join ".$_SESSION['thisDbname'].".inventory_wine i4 on o.region_id = i4.id left join ".$_SESSION['thisDbname'].".tag_wine t4 on i4.tag_id = t4.id left join ".$_SESSION['thisDbname'].".inventory_wine i5 on o.appellation_id = i5.id left join ".$_SESSION['thisDbname'].".tag_wine t5 on i5.tag_id = t5.id ";
		$sqlsel .= "left join ".$_SESSION['thisDbname'].".inventory_wine i6 on o.producer_id = i6.id left join ".$_SESSION['thisDbname'].".tag_wine t6 on i6.tag_id = t6.id left join ".$_SESSION['thisDbname'].".inventory_wine i7 on o.grape_id = i7.id left join ".$_SESSION['thisDbname'].".tag_wine t7 on i7.tag_id = t7.id ";
		$sqlsel .= "left join ".$_SESSION['thisDbname'].".inventory_wine i8 on o.wine_id = i8.id left join ".$_SESSION['thisDbname'].".tag_wine t8 on i8.tag_id = t8.id ";
		$sqlsel .= "where t.client_id = '".$clientID."' && r.client_id = '".$clientID."' && o.client_id = '".$clientID."' && o.wine_id is not null && t.created_at between '".$sqlstartDate."' and '".$sqlendDate."' && o.created_at between '".$sqlstartDate."' and '".$sqlendDate."' ";
		$sqlsel .= "group by path order by count desc";
		//die_r($sqlsel);
		$result = mysql_query($sqlsel);
        $num = 0;
        $i = 0;
        $j = 0;
        $num = mysql_numrows($result);
        while($i < $num)
        {  
	        $codeinnerone1[$i] = mysql_result($result,$i,"track_code");
	        
	        if($codeinnerone1[$i] == $codeId)
	        {
	        	$countinnerone[$j] = mysql_result($result,$i,"count");
	        	$pathinnerone[$j] = mysql_result($result,$i,"path");
	        	$codeinnerone[$j] = mysql_result($result,$i,"track_code");
	        	$j++;
	        }
	        
	        $i++;
        }   

	    
//	    $pathinnerone = array("by bottle => type => wine","by bottle => type => country => wine","by bottle => type => grape => wine","by bottle => type => all => wine","by bottle => type => half bottles => wine","by bottle => type => large format => wine","by glass => wine");
//	    $countinnerone = array('10','20','30','40','50','60','70');
	    
	    $path['values'] = $pathinnerone;
	    $count['values'] = $countinnerone;
	    $code['values'] = $codeinnerone;


	    $allVisits1 = array ( 'path' => $path, 
	                          'count' =>  $count,
	    					  'code' =>  $code);
	    
	    //$allVisits2 = array('USA' => '200', 'France' => '300', 'Itly' => '100', 'Germany' => '400');
	    $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        echo json_encode($allVisits1);

		
	}
	
	public function leastwinesAction()
	{
		mysql_select_db($_SESSION['selectDbname']) or die ("Unable to select database!");
	    $catName = $this->_request->getParam('catName');
	    $sortName = $this->_request->getParam('sortName');
	    $clientID = $this->_request->getParam('clientID');
		if(!empty($_SESSION['catName'])) {unset($_SESSION['catName']);}
		if(!empty($_SESSION['sortName'])) {unset($_SESSION['sortName']);}
	    $_SESSION['catName'] = $catName;
	    $_SESSION['sortName'] = $sortName;
	    
	    if(($sortName == "wType") || ($sortName == "wList"))
	    {
	    	if($sortName == "wType"){
	    		$sqlsel = "select distinct e.wine_id, e.name, e.bev_type, t.description, e.type_elim, ROUND(100*(e.type_elim/(select sum(type_elim) from track_elim where client_id = '".$clientID."')),2) as percent from track_elim e join ".$_SESSION['thisDbname'].".inventory_wine i on e.country_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id ";
				$sqlsel .= "where e.client_id = '".$clientID."' && e.category_id = 1 order by e.type_elim desc limit 20";
				$colnm = "type_elim";
	    	}else if($sortName == "wList"){
	    		$sqlsel = "select distinct e.wine_id, e.name, e.bev_type, t.description, e.bottle_elim, ROUND(100*(e.bottle_elim/(select sum(bottle_elim) from track_elim where client_id = '".$clientID."')),2) as percent from track_elim e join ".$_SESSION['thisDbname'].".inventory_wine i on e.country_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id ";
				$sqlsel .= "where e.client_id = '".$clientID."' && e.category_id = 1 order by e.bottle_elim desc limit 20";
				$colnm = "bottle_elim";
	    	}
			$result = mysql_query($sqlsel);
	        $num = 0;
	        $i = 0;
	        $num = mysql_numrows($result);
	        while($i < $num)
	        {
	        	$wineinnerone[$i] = mysql_result($result,$i,"name");
	        	$typeinnerone[$i] = mysql_result($result,$i,"bev_type");
	        	$countryinnerone[$i] = mysql_result($result,$i,"description");
	        	$countinnerone[$i] = mysql_result($result,$i,$colnm);
	        	$percentinnerone[$i] = mysql_result($result,$i,"percent");
	        	$i++;
	        }
	        $wine['values'] = $wineinnerone;
	        $type['values'] = $typeinnerone;
		    $country['values'] = $countryinnerone;
		    $count['values'] = $countinnerone;
		    $percent['values'] = $percentinnerone;
	
		    $allVisits1 = array ( 'wine' => $wine,
		    					  'type' => $type, 
		                          'country' =>  $country,
		    					  'count' =>  $count,
		    					  'percent' =>  $percent);
		    
		    //$allVisits2 = array('USA' => '200', 'France' => '300', 'Itly' => '100', 'Germany' => '400');
		    $this->_helper->layout()->disableLayout();
	        $this->_helper->viewRenderer->setNoRender(true);
	        echo json_encode($allVisits1);
	    }
	    else if($sortName == "wCountry")
	    {
	    	$sqlsel = "select distinct e.wine_id, e.name, e.country_id, t.description as country, e.country_elim, ROUND(100*(e.country_elim/(select sum(country_elim) from track_elim where client_id = '".$clientID."')),2) as percent from track_elim e join ".$_SESSION['thisDbname'].".inventory_wine i on e.country_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id ";
			$sqlsel .= "where e.client_id = '".$clientID."' && e.category_id = 1 order by e.country_elim desc limit 20";

			$result = mysql_query($sqlsel);
	        $num = 0;
	        $i = 0;
	        $num = mysql_numrows($result);
	        while($i < $num)
	        {
	        	$wineinnerone[$i] = mysql_result($result,$i,"name");
	        	$countryinnerone[$i] = mysql_result($result,$i,"country");
	        	$countinnerone[$i] = mysql_result($result,$i,"country_elim");
	        	$percentinnerone[$i] = mysql_result($result,$i,"percent");
	        	$i++;
	        }
	        $wine['values'] = $wineinnerone;
		    $country['values'] = $countryinnerone;
		    $count['values'] = $countinnerone;
		    $percent['values'] = $percentinnerone;
	
		    $allVisits1 = array ( 'wine' => $wine, 
		                          'country' =>  $country,
		    					  'count' =>  $count,
		    					  'percent' =>  $percent);
		    
		    //$allVisits2 = array('USA' => '200', 'France' => '300', 'Itly' => '100', 'Germany' => '400');
		    $this->_helper->layout()->disableLayout();
	        $this->_helper->viewRenderer->setNoRender(true);
	        echo json_encode($allVisits1);
	    }
		else if($sortName == "wGrape") 
	    {
	    	$sqlsel = "select distinct e.wine_id, e.name, e.grape_id, t.description as grape, e.grape_elim, ROUND(100*(e.grape_elim/(select sum(grape_elim) from track_elim where client_id = '".$clientID."')),2) as percent from track_elim e join ".$_SESSION['thisDbname'].".inventory_wine i on e.grape_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id ";
			$sqlsel .= "where e.client_id = '".$clientID."' && e.category_id = 1 order by e.grape_elim desc limit 20";

			$result = mysql_query($sqlsel);
	        $num = 0;
	        $i = 0;
	        $num = mysql_numrows($result);
	        while($i < $num)
	        {
	        	$wineinnerone[$i] = mysql_result($result,$i,"name");
	        	$grapeinnerone[$i] = mysql_result($result,$i,"grape");
	        	$countinnerone[$i] = mysql_result($result,$i,"grape_elim");
	        	$percentinnerone[$i] = mysql_result($result,$i,"percent");
	        	$i++;
	        }
	        $wine['values'] = $wineinnerone;
		    $grape['values'] = $grapeinnerone;
		    $count['values'] = $countinnerone;
		    $percent['values'] = $percentinnerone;
	
		    $allVisits1 = array ( 'wine' => $wine, 
		                          'grape' =>  $grape,
		    					  'count' =>  $count,
		    					  'percent' =>  $percent);
		    
		    //$allVisits2 = array('USA' => '200', 'France' => '300', 'Itly' => '100', 'Germany' => '400');
		    $this->_helper->layout()->disableLayout();
	        $this->_helper->viewRenderer->setNoRender(true);
	        echo json_encode($allVisits1);
	    }
	}
	
	public function leastwinesnewAction()
	{
		mysql_select_db($_SESSION['selectDbname']) or die ("Unable to select database!");
		$_SESSION['chartName'] = $this->_request->getParam('chartName');
	    $catName = $this->_request->getParam('catName');
	    $sortName = $this->_request->getParam('sortName');
	    $clientID = $this->_request->getParam('clientID');
	    $_SESSION['clientID'] = $clientID;
	    if(!empty($_SESSION['catName'])) {unset($_SESSION['catName']);}
		if(!empty($_SESSION['sortName'])) {unset($_SESSION['sortName']);}
	    $_SESSION['catName'] = $catName;
	    $_SESSION['sortName'] = $sortName;
	    
	    if($sortName == "wType")
	    {
	    	$sqlsel = "select g.type_id , t.description as type, count(g.type_id) as selections, ROUND(100*(count(g.type_id)/(select count(g.type_id) from track_gdb g where g.client_id = '".$clientID."' && g.type_id in (10,11,12,13,14, 4695))),2) as selection_percent from track_gdb g ";
			$sqlsel .= "join ".$_SESSION['thisDbname'].".inventory_wine i on g.type_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id ";
			$sqlsel .= "where g.client_id = '".$clientID."' && g.type_id in (10,11,12,13,14, 4695) group by g.type_id order by selections";
				    	
			$result = mysql_query($sqlsel);
	        $num = 0;
	        $i = 0;
	        $num = mysql_numrows($result);
	        while($i < $num)
	        {
	        	$typeinnerone[$i] = mysql_result($result,$i,"type");
	        	$selectionsinnerone[$i] = mysql_result($result,$i,"selections");
	        	$percentinnerone[$i] = mysql_result($result,$i,"selection_percent");
	        	$i++;
	        }
	        $type['values'] = $typeinnerone;
	        $selections['values'] = $selectionsinnerone;
		    $percent['values'] = $percentinnerone;
	
		    $allVisits1 = array ( 'type' => $type,
		    					  'selections' => $selections, 
		    					  'percent' =>  $percent);
		    
		    //$allVisits2 = array('USA' => '200', 'France' => '300', 'Itly' => '100', 'Germany' => '400');
		    $this->_helper->layout()->disableLayout();
	        $this->_helper->viewRenderer->setNoRender(true);
	        echo json_encode($allVisits1);
	    }else if($sortName == "wList")
	    {
	    	$sqlsel = "select e.bev_type, sum(e.bottle_elim) as elimination, count(e.bev_type) as count, ROUND(100*(sum(e.bottle_elim))/(select sum(bottle_elim) from track_elim where client_id = '".$clientID."' && category_id = 1),2) as percent ";
			$sqlsel .= "from track_elim e where e.client_id = '".$clientID."' && e.category_id = 1 group by e.bev_type order by elimination desc";
			 
			$result = mysql_query($sqlsel);
	        $num = 0;
	        $i = 0;
	        $num = mysql_numrows($result);
	        while($i < $num)
	        {
	        	$typeinnerone[$i] = mysql_result($result,$i,"bev_type");
	        	$eliminnerone[$i] = mysql_result($result,$i,"elimination");
	        	$countinnerone[$i] = mysql_result($result,$i,"count");
	        	$percentinnerone[$i] = mysql_result($result,$i,"percent");
	        	$i++;
	        }
	        $type['values'] = $typeinnerone;
	        $elimination['values'] = $eliminnerone;
	        $count['values'] = $countinnerone;
		    $percent['values'] = $percentinnerone;
	
		    $allVisits1 = array ( 'type' => $type,
		    					  'elimination' => $elimination,
		    					  'count' => $count, 
		    					  'percent' =>  $percent);
		    
		    //$allVisits2 = array('USA' => '200', 'France' => '300', 'Itly' => '100', 'Germany' => '400');
		    $this->_helper->layout()->disableLayout();
	        $this->_helper->viewRenderer->setNoRender(true);
	        echo json_encode($allVisits1);
	    }
	    
	}
	
	public function leastbcsAction()
	{
		mysql_select_db($_SESSION['selectDbname']) or die ("Unable to select database!");
		$catName = $this->_request->getParam('catName');
	    $sortName = $this->_request->getParam('sortName');
	    $clientID = $this->_request->getParam('clientID');
	    if(!empty($_SESSION['catName'])) {unset($_SESSION['catName']);}
		if(!empty($_SESSION['sortName'])) {unset($_SESSION['sortName']);}
	    $_SESSION['catName'] = $catName;
	    $_SESSION['sortName'] = $sortName;
	    
	    if($catName == "ddbeer")
	    {
			if($sortName == "wType"){
	    		$colnm = "beer_type_elim";
				$sqlsel = "select distinct wine_id, name, bev_type, beer_type_elim, ROUND(100*(beer_type_elim/(select sum(beer_type_elim) from track_elim where client_id = '".$clientID."')),2) as percent from track_elim where client_id = '".$clientID."' && category_id = 2 order by beer_type_elim desc limit 20";		
	    	}else if($sortName == "wList"){
	    		$colnm = "beer_list_elim";
	    		$sqlsel = "select distinct wine_id, name, bev_type, beer_list_elim, ROUND(100*(beer_list_elim/(select sum(beer_list_elim) from track_elim where client_id = '".$clientID."')),2) as percent from track_elim where client_id = '".$clientID."' && category_id = 2 order by beer_list_elim desc limit 20";
	    	}
	    }
	    else if($catName == "ddcocktail")
	    {
			if($sortName == "wType"){
				$colnm = "cocktails_type_elim";
	    		$sqlsel = "select distinct wine_id, name, bev_type, cocktails_type_elim, ROUND(100*(cocktails_type_elim/(select sum(cocktails_type_elim) from track_elim where client_id = '".$clientID."')),2) as percent from track_elim where client_id = '".$clientID."' && category_id = 3 order by cocktails_type_elim desc limit 20";		
	    	}else if($sortName == "wList"){
	    		$colnm = "cocktails_list_elim";
	    		$sqlsel = "select distinct wine_id, name, bev_type, cocktails_list_elim, ROUND(100*(cocktails_list_elim/(select sum(cocktails_list_elim) from track_elim where client_id = '".$clientID."')),2) as percent from track_elim where client_id = '".$clientID."' && category_id = 3 order by cocktails_list_elim desc limit 20";
	    	}
	    }else if($catName == "ddspirit")
	    {
			if($sortName == "wType"){
				$colnm = "spirits_type_elim";
	    		$sqlsel = "select distinct wine_id, name, bev_type, spirits_type_elim, ROUND(100*(spirits_type_elim/(select sum(spirits_type_elim) from track_elim where client_id = '".$clientID."')),2) as percent from track_elim where client_id = '".$clientID."' && category_id = 4 order by spirits_type_elim desc limit 20";		
	    	}else if($sortName == "wList"){
	    		$colnm = "spirits_list_elim";
	    		$sqlsel = "select distinct wine_id, name, bev_type, spirits_list_elim, ROUND(100*(spirits_list_elim/(select sum(spirits_list_elim) from track_elim where client_id = '".$clientID."')),2) as percent from track_elim where client_id = '".$clientID."' && category_id = 4 order by spirits_list_elim desc limit 20";
	    	}
	    }
		$result = mysql_query($sqlsel);
        $num = 0;
        $i = 0;
        $num = mysql_numrows($result);
        while($i < $num)
        {
        	$beerinnerone[$i] = mysql_result($result,$i,"name");
        	$typeinnerone[$i] = mysql_result($result,$i,"bev_type");
        	$countinnerone[$i] = mysql_result($result,$i,$colnm);
	       	$percentinnerone[$i] = mysql_result($result,$i,"percent");
        	$i++;
        }
        $beer['values'] = $beerinnerone;
        $type['values'] = $typeinnerone;
        $count['values'] = $countinnerone;
		$percent['values'] = $percentinnerone;

	    $allVisits1 = array ( 'beer' => $beer,
	    					  'type' => $type,
	    					  'count' =>  $count,
		    				  'percent' =>  $percent);
	    
	    //$allVisits2 = array('USA' => '200', 'France' => '300', 'Itly' => '100', 'Germany' => '400');
	    $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        echo json_encode($allVisits1);
	}
	
	public function leastbcsnewAction()
	{
		mysql_select_db($_SESSION['selectDbname']) or die ("Unable to select database!");
		$_SESSION['chartName'] = $this->_request->getParam('chartName');
		$catName = $this->_request->getParam('catName');
	    $sortName = $this->_request->getParam('sortName');
	    $clientID = $this->_request->getParam('clientID');
	    $_SESSION['clientID'] = $clientID;
	    if(!empty($_SESSION['catName'])) {unset($_SESSION['catName']);}
		if(!empty($_SESSION['sortName'])) {unset($_SESSION['sortName']);}
	    $_SESSION['catName'] = $catName;
	    $_SESSION['sortName'] = $sortName;
	    
	    if($catName == "ddbeer"){$catID = 2;}else if($catName == "ddcocktail"){$catID = 3;}else if($catName == "ddspirit"){$catID = 4;}
	    
		if($sortName == "wType"){			
			$sqlsel = "select t.type_id as type, ss.bev_type, count(t.type_id) as count, ROUND(100*(count(t.type_id)/(select count(type_id) from track where client_id = '".$clientID."' && category_id = '".$catID."' && type_id in (select distinct wine_type_id from track_elim where client_id = '".$clientID."' && category_id = '".$catID."'))),2) as percent from track t ";
			$sqlsel .= "join (select distinct t.bev_type, t.wine_type_id from track_elim t where t.client_id = '".$clientID."' && t.category_id = '".$catID."') as ss on t.type_id = ss.wine_type_id where t.client_id = '".$clientID."' && t.category_id = '".$catID."' group by type order by count asc";	
			//echo $sqlsel;			
    	}else if($sortName == "wList"){
    		$sqlsel = "select e.bev_type, count(e.bev_type) as count, ROUND(100*(count(e.bev_type)/(select count(bev_type) from track t1 join track_elim e1 on t1.wine_id = e1.wine_id where t1.client_id = '".$clientID."' && e1.client_id = '".$clientID."' && t1.category_id = '".$catID."' && e1.category_id = '".$catID."')),2) as percent from track t ";
			$sqlsel .= "join track_elim e on t.wine_id = e.wine_id where t.client_id = '".$clientID."' && e.client_id = '".$clientID."' && e.category_id = '".$catID."' && t.category_id = '".$catID."' && t.wine_id is not null group by e.bev_type order by count";
    	}
	    
		$result = mysql_query($sqlsel);
		
	        $num = 0;
	        $i = 0;
	        $num = mysql_numrows($result);
	    if($num > 0)
		{    
	        while($i < $num)
	        {
	        	$typeinnerone[$i] = mysql_result($result,$i,"bev_type");
	        	$countinnerone[$i] = mysql_result($result,$i,"count");
		       	$percentinnerone[$i] = mysql_result($result,$i,"percent");
		       	$i++;
	        }
	        $type['values'] = $typeinnerone;
	        $count['values'] = $countinnerone;
			$percent['values'] = $percentinnerone;
	
		    $allVisits1 = array ( 'type' => $type,
		    					  'count' =>  $count,
			    				  'percent' =>  $percent);
		}
		else 
		{
			$type['values'] = "";
	        $count['values'] = "";
			$percent['values'] = "";
		    $allVisits1 = array ( 'type' => $type,
		    					  'count' =>  $count,
			    				  'percent' =>  $percent);
		}
	    //$allVisits2 = array('USA' => '200', 'France' => '300', 'Itly' => '100', 'Germany' => '400');
	    $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        echo json_encode($allVisits1);
	}
	
	public function leastdowntblAction()
	{
		mysql_select_db($_SESSION['selectDbname']) or die ("Unable to select database!");
	    $codeId = $this->_request->getParam('codeId');
	    $clientID = $this->_request->getParam('clientID');
	    
    	$sqlsel = "select distinct e.wine_id, e.name, e.bev_type, t.description, e.bottle_elim, ROUND(100*(e.bottle_elim/(select sum(bottle_elim) from track_elim where client_id = '".$clientID."' && bev_type = '".$codeId."')),2) as percent ";
		$sqlsel .= "from track_elim e join ".$_SESSION['thisDbname'].".inventory_wine i on e.country_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id ";
		$sqlsel .= "where e.client_id = '".$clientID."' && e.bev_type = '".$codeId."' && e.category_id = 1 order by e.bottle_elim desc limit 20;";
		
		$result = mysql_query($sqlsel);
        $num = 0;
        $i = 0;
        $num = mysql_numrows($result);
        while($i < $num)
        {
        	$wineinnerone[$i] = mysql_result($result,$i,"name");
        	$typeinnerone[$i] = mysql_result($result,$i,"bev_type");
        	$countryinnerone[$i] = mysql_result($result,$i,"description");
        	$countinnerone[$i] = mysql_result($result,$i,"bottle_elim");
        	$percentinnerone[$i] = mysql_result($result,$i,"percent");
        	$i++;
        }
        $wine['values'] = $wineinnerone;
        $type['values'] = $typeinnerone;
	    $country['values'] = $countryinnerone;
	    $count['values'] = $countinnerone;
	    $percent['values'] = $percentinnerone;

	    $allVisits1 = array ( 'wine' => $wine,
	    					  'type' => $type, 
	                          'country' =>  $country,
	    					  'count' =>  $count,
	    					  'percent' =>  $percent);
	    
	    //$allVisits2 = array('USA' => '200', 'France' => '300', 'Itly' => '100', 'Germany' => '400');
	    $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        echo json_encode($allVisits1);
	    
	}
	
	public function leastdowntblbcsAction()
	{
		mysql_select_db($_SESSION['selectDbname']) or die ("Unable to select database!");
	    $codeId = $this->_request->getParam('codeId');
	    $catName = $this->_request->getParam('catName');
	    $clientID = $this->_request->getParam('clientID');
	    
    	$sqlsel = "select distinct e.wine_id, e.name, e.bev_type, t.description, e.bottle_elim, ROUND(100*(e.bottle_elim/(select sum(bottle_elim) from track_elim where client_id = '".$clientID."' && bev_type = '".$codeId."')),2) as percent ";
		$sqlsel .= "from track_elim e join ".$_SESSION['thisDbname'].".inventory_wine i on e.country_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id ";
		$sqlsel .= "where e.client_id = '".$clientID."' && e.bev_type = '".$codeId."' && e.category_id = 1 order by e.bottle_elim desc limit 20;";
		
		
		if($catName == "ddbeer")
	    {
			$sqlsel = "select name, bev_type, beer_list_elim as count, ROUND(100*((beer_list_elim)/(select sum(beer_list_elim) from track_elim where client_id = '".$clientID."' && category_id = 2 && bev_type = '".$codeId."')),2) as percent from track_elim ";
			$sqlsel .= "where client_id = '".$clientID."' && bev_type = '".$codeId."' && category_id = 2 order by beer_list_elim desc limit 20";
	    }
	    else if($catName == "ddcocktail")
	    {
			$sqlsel = "select name, bev_type, cocktails_list_elim as count, ROUND(100*((cocktails_list_elim)/(select sum(cocktails_list_elim) from track_elim where client_id = '".$clientID."' && category_id = 3 && bev_type = '".$codeId."')),2) as percent from track_elim ";
			$sqlsel .= "where client_id = '".$clientID."' && bev_type = '".$codeId."' && category_id = 3 order by cocktails_list_elim desc limit 20";	
	    }
	    else if($catName == "ddspirit")
	    {
			$sqlsel = "select name, bev_type, spirits_list_elim as count, ROUND(100*((spirits_list_elim)/(select sum(spirits_list_elim) from track_elim where client_id = '".$clientID."' && category_id = 4 && bev_type = '".$codeId."')),2) as percent from track_elim ";
			$sqlsel .= "where client_id = '".$clientID."' && bev_type = '".$codeId."' && category_id = 4 order by spirits_list_elim desc limit 20";	
	    }
		//die_r($sqlsel);
		$result = mysql_query($sqlsel);
        $num = 0;
        $i = 0;
        $num = mysql_numrows($result);
        while($i < $num)
        {
        	$wineinnerone[$i] = mysql_result($result,$i,"name");
        	$typeinnerone[$i] = mysql_result($result,$i,"bev_type");
        	$countinnerone[$i] = mysql_result($result,$i,"count");
        	$percentinnerone[$i] = mysql_result($result,$i,"percent");
        	$i++;
        }
        
        if(!empty($_SESSION['chartValues'])) {
			unset($_SESSION['chartValues']);
		}
		$chartValues = array('wine' => $wineinnerone, 'type' => $typeinnerone, 'count'  => $countinnerone, 'percent' => $percentinnerone);
	    $_SESSION['chartValues'] = array();
		$_SESSION['chartValues'] = $chartValues;
		
        $wine['values'] = $wineinnerone;
        $type['values'] = $typeinnerone;
	    $count['values'] = $countinnerone;
	    $percent['values'] = $percentinnerone;

	    $allVisits1 = array ( 'wine' => $wine,
	    					  'type' => $type, 
	    					  'count' =>  $count,
	    					  'percent' =>  $percent);
	    
	    //$allVisits2 = array('USA' => '200', 'France' => '300', 'Itly' => '100', 'Germany' => '400');
	    $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        echo json_encode($allVisits1);
	    
	}
	
	public function priceAction()
	{  
		mysql_select_db($_SESSION['selectDbname']) or die ("Unable to select database!"); 
		$chartName = $this->_request->getParam('chartName');
	    $startDate = $this->_request->getParam('startDate');
	    $startDate1 = $this->_request->getParam('startDate');
	    $endDate = $this->_request->getParam('endDate');
	    $clientID = $this->_request->getParam('clientID');
	    $sqlstartDate = date("Y-m-d", strtotime($startDate));
	    $sqlendDate = date("Y-m-d", strtotime($endDate));
	    $checkval = $this->_request->getParam('checkval');
	    $_SESSION['startDate'] = $startDate;
	    $_SESSION['endDate'] = $endDate;
	    $_SESSION['chartName'] = $chartName;
	    
	    $p = 0;
	    $dateRange[$p] = $sqlstartDate;
		while (strtotime($startDate1) < strtotime($endDate)) {
		    $p++;
			$dateRange[$p] = date ("Y-m-d", strtotime("+1 day", strtotime($startDate1)));
			$startDate1 = $dateRange[$p];
		}

	 // logic with missing dates
        $cat_id = array("0","1","2","3","4"); 

       for($p=0;$p<count($cat_id);$p++)
	   {  
	        for($q=0;$q<count($dateRange);$q++)    
			{  
				if($cat_id[$p] == 0){
					$sqlsel = "select sum(glass_price) as glass_price, t.created_at from track_gdb t ";
					$sqlsel .= "where t.client_id = '".$clientID."' && t.by_glass = 1 && (t.glass_price is not null) && (t.wine_id is not null || t.wine_id != 0) && t.created_at = '".$dateRange[$q]."' group by t.created_at";
				}else{
					$sqlsel = "select sum(bottle_price) as bottle_price , sum(glass_price) as glass_price, t.created_at from track_gdb t ";
					$sqlsel .= "where t.client_id = '".$clientID."' && t.category_id = '".$cat_id[$p]."' && ((t.bottle_price is not null) || (t.glass_price is not null)) && (t.wine_id is not null || t.wine_id != 0) && t.created_at = '".$dateRange[$q]."' group by t.created_at";
				}
		        //die_r($sqlsel);   
	            $result = mysql_query($sqlsel);
				
	            $num = 0;
	            $i = 0;
	            $num = mysql_num_rows($result);
	            if($num > 0)
	            {
	            while($i < $num)
	            {  
	            	if($cat_id[$p] == 0){
	            		$glpr = mysql_result($result,$i,"glass_price");
	            	}else{
	            		$botpr = mysql_result($result,$i,"bottle_price");
	            	}
	            	if(empty($botpr)){
	            		$bprice[$i] = "0";
	            	}else{
	                	$bprice[$i] = mysql_result($result,$i,"bottle_price");
	            	}
	            	if(empty($glpr)){
	            		$gprice[$i] = "0";
	            	}else{
	                	$gprice[$i] = mysql_result($result,$i,"glass_price");
	            	}
	            		 				
	                $t = explode('-',$dateRange[$q]);
					$dt = $t[1]."-".$t[2];
					
					if($checkval == "")
	            	{
	            		if($cat_id[$p] == 0){
							$winesglassinnerone[$q] = array ( 'X' => $dt, 'Y' => $gprice[$i]);
						}else if($cat_id[$p] == 1){
							$winesbottleinnerone[$q] = array ( 'X' => $dt, 'Y' => $bprice[$i]);
						}else if($cat_id[$p] == 2){
							$beerinnerone[$q] = array ( 'X' => $dt, 'Y' => $bprice[$i]);
						}else if($cat_id[$p] == 3){
							$cocktailsinnerone[$q] = array ( 'X' => $dt, 'Y' => $bprice[$i]);
						}else if($cat_id[$p] == 4){
							$spiritsinnerone[$q] = array ( 'X' => $dt, 'Y' => $bprice[$i]);
						} 
					
	            	}
	            	else if($checkval == "0")
	            	{
	            		$zero[$i] = 0;
	            		if($cat_id[$p] == 0){
	            		$winesglassinnerone[$q] = array ( 'X' => $dt, 'Y' => $gprice[$i]);
	            		$winesbottleinnerone[$q] = array ( 'X' => $dt, 'Y' => $zero[$i]);
	            		$beerinnerone[$q] = array ( 'X' => $dt, 'Y' => $zero[$i]);
	            		$cocktailsinnerone[$q] = array ( 'X' => $dt, 'Y' => $zero[$i]);
	            		$spiritsinnerone[$q] = array ( 'X' => $dt, 'Y' => $zero[$i]);
	            		}
	            	}
	            	else if($checkval == "1")
	            	{
	            		$zero[$i] = 0;
	            		if($cat_id[$p] == 1){
	            		$winesglassinnerone[$q] = array ( 'X' => $dt, 'Y' => $zero[$i]);
	            		$winesbottleinnerone[$q] = array ( 'X' => $dt, 'Y' => $bprice[$i]);
	            		$beerinnerone[$q] = array ( 'X' => $dt, 'Y' => $zero[$i]);
	            		$cocktailsinnerone[$q] = array ( 'X' => $dt, 'Y' => $zero[$i]);
	            		$spiritsinnerone[$q] = array ( 'X' => $dt, 'Y' => $zero[$i]);
	            		}
	            	}
	            	else if($checkval == "2")
	            	{
	            		$zero[$i] = 0;
	            		if($cat_id[$p] == 2){
	            		$winesglassinnerone[$q] = array ( 'X' => $dt, 'Y' => $zero[$i]);
	            		$winesbottleinnerone[$q] = array ( 'X' => $dt, 'Y' => $zero[$i]);
	            		$beerinnerone[$q] = array ( 'X' => $dt, 'Y' => $bprice[$i]);
	            		$cocktailsinnerone[$q] = array ( 'X' => $dt, 'Y' => $zero[$i]);
	            		$spiritsinnerone[$q] = array ( 'X' => $dt, 'Y' => $zero[$i]);
	            		}
	            	}
	            	else if($checkval == "3")
	            	{
	            		$zero[$i] = 0;
	            		if($cat_id[$p] == 3){
	            		$winesglassinnerone[$q] = array ( 'X' => $dt, 'Y' => $zero[$i]);
	            		$winesbottleinnerone[$q] = array ( 'X' => $dt, 'Y' => $zero[$i]);
	            		$beerinnerone[$q] = array ( 'X' => $dt, 'Y' => $zero[$i]);
	            		$cocktailsinnerone[$q] = array ( 'X' => $dt, 'Y' => $bprice[$i]);
	            		$spiritsinnerone[$q] = array ( 'X' => $dt, 'Y' => $zero[$i]);
	            		}
	            	}
	            	else if($checkval == "4")
	            	{
	            		$zero[$i] = 0;
	            		if($cat_id[$p] == 4){
	            		$winesglassinnerone[$q] = array ( 'X' => $dt, 'Y' => $zero[$i]);
	            		$winesbottleinnerone[$q] = array ( 'X' => $dt, 'Y' => $zero[$i]);
	            		$beerinnerone[$q] = array ( 'X' => $dt, 'Y' => $zero[$i]);
	            		$cocktailsinnerone[$q] = array ( 'X' => $dt, 'Y' => $zero[$i]);
	            		$spiritsinnerone[$q] = array ( 'X' => $dt, 'Y' => $bprice[$i]);
	            		}
	            	}
	            	
					
					
	                $i++;
	            }
	            
	            
	            }
	            else 
	            {
	            	$j = 0;
	            	$t = explode('-',$dateRange[$q]);
					$dt = $t[1]."-".$t[2];
					$ftcount[$j] = 0;
					if($checkval == "")
	            	{
		            	if($cat_id[$p] == 0){
							$winesglassinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
						}else if($cat_id[$p] == 1){
							$winesbottleinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
						}else  if($cat_id[$p] == 2){
							$beerinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
						}else if($cat_id[$p] == 3){
							$cocktailsinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
						}else if($cat_id[$p] == 4){
							$spiritsinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
						}
	            	}
	            	else if($checkval == "0")
	            	{
	            		if($cat_id[$p] == 0){
	            		$winesglassinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
	            		$winesbottleinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
	            		$beerinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
	            		$cocktailsinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
	            		$spiritsinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
	            		}
	            	}
	            	else if($checkval == "1")
	            	{	if($cat_id[$p] == 1){
	            		$winesglassinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
	            		$winesbottleinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
	            		$beerinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
	            		$cocktailsinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
	            		$spiritsinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
	            		}            		
	            	}
	            	else if($checkval == "2")
	            	{
	            		if($cat_id[$p] == 2){
	            		$winesglassinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
	            		$winesbottleinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
	            		$beerinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
	            		$cocktailsinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
	            		$spiritsinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
	            		}
	            	}
	            	else if($checkval == "3")
	            	{
	            		if($cat_id[$p] == 3){
	            		$winesglassinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
	            		$winesbottleinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
	            		$beerinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
	            		$cocktailsinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
	            		$spiritsinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
	            		}
	            	}
	            	else if($checkval == "4")
	            	{
	            		if($cat_id[$p] == 4){
	            		$winesglassinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
	            		$winesbottleinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
	            		$beerinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
	            		$cocktailsinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
	            		$spiritsinnerone[$q] = array ( 'X' => $dt, 'Y' => $ftcount[$j]);
	            		}
	            	}
	
	            }
		            
	            
			}	
	   }

	   if(!empty($_SESSION['chartValues'])) {
			unset($_SESSION['chartValues']);
		}
		$chartValues = array('winesglass' => $winesglassinnerone, 'winesbottle' => $winesbottleinnerone, 'beer'  => $beerinnerone, 'spirits' => $spiritsinnerone, 'cocktails' => $cocktailsinnerone);
	    $_SESSION['chartValues'] = array();
		$_SESSION['chartValues'] = $chartValues;
		
	    $winesglass['values'] = $winesglassinnerone;
	    $winesbottle['values'] = $winesbottleinnerone;
	    $beer['values'] = $beerinnerone;
	    $spirits['values'] = $spiritsinnerone;
	    $cocktails['values'] = $cocktailsinnerone;
	    
	    $allVisits1 = array ( 'winesglass' => $winesglass,
	    					  'winesbottle' => $winesbottle, 
	                          'beer' =>  $beer, 
	                          'spirits' => $spirits, 
	                          'cocktails' => $cocktails
	    					);
	    
	   	$this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        echo json_encode($allVisits1);

		
	}
	
	public function pricingpopupAction()
	{
		mysql_select_db($_SESSION['selectDbname']) or die ("Unable to select database!");
		$codeId = $this->_request->getParam('id_dt');
	 	$startDate = $this->_request->getParam('startDate');
	    $startDate1 = $this->_request->getParam('startDate');
	    $endDate = $this->_request->getParam('endDate');
	    $clientID = $this->_request->getParam('clientID');
	    $sqlstartDate = date("Y-m-d", strtotime($startDate));
	    $sqlendDate = date("Y-m-d", strtotime($endDate));
	    list($catid,$dt,$dotcount) = explode('_',$codeId);
	    
	    $p = 0;
	    $dateRange[$p] = $sqlstartDate;
		while (strtotime($startDate1) < strtotime($endDate)) {
		    
		    list($yr,$mo,$day) = explode('-',$dateRange[$p]);
		    $p++;
		    $modt = $mo."-".$day;
		    if($dt == $modt){
		    	$created_at = $yr."-".$mo."-".$day;
		    }
			$dateRange[$p] = date ("Y-m-d", strtotime("+1 day", strtotime($startDate1)));
			$startDate1 = $dateRange[$p];
		}
		
		if($catid == 0){
			$checkcol = "glass_price";
			$sqlsel = "select t.wine_id, t3.Name, count(t.wine_id) as count, t.glass_price, t.created_at from track_gdb t join track t2 on t.track_id = t2.track_id join track_elim t3 on t2.wine_id = t3.wine_id ";
			$sqlsel .= "where t.client_id = ".$clientID." && t2.client_id = ".$clientID." && t3.client_id = ".$clientID." && t.by_glass = 1 && (t.wine_id is not null || t.wine_id != 0) && t2.created_at = '".$created_at."' && t.created_at = '".$created_at."' group by t.wine_id";
		}else if($catid == 1){
			$checkcol = "bottle_price";
			$sqlsel = "select t.wine_id, t3.Name, count(t.wine_id) as count, t.bottle_price, t.created_at from track_gdb t join track t2 on t.track_id = t2.track_id join track_elim t3 on t2.wine_id = t3.wine_id ";
			$sqlsel .= "where t.client_id = ".$clientID." && t2.client_id = ".$clientID." && t3.client_id = ".$clientID." && t.category_id = 1 && (t.wine_id is not null || t.wine_id != 0) && t2.created_at = '".$created_at."' && t.created_at = '".$created_at."' group by t.wine_id";
		}else if($catid == 2){
			$checkcol = "bottle_price";
			$sqlsel = "select t.wine_id, t3.Name, count(t.wine_id) as count, t.bottle_price, t.created_at from track_gdb t join track t2 on t.track_id = t2.track_id join track_elim t3 on t2.wine_id = t3.wine_id ";
 			$sqlsel .= "where t.client_id = ".$clientID." && t2.client_id = ".$clientID." && t3.client_id = ".$clientID." && t.category_id = 2 && (t.wine_id is not null || t.wine_id != 0) && t2.created_at = '".$created_at."' && t.created_at = '".$created_at."' group by t2.wine_id"; 
		}else if($catid == 3){
			$checkcol = "bottle_price";
			$sqlsel = "select t.wine_id, t3.Name, count(t.wine_id) as count, t.bottle_price, t.created_at from track_gdb t join track t2 on t.track_id = t2.track_id join track_elim t3 on t2.wine_id = t3.wine_id ";
 			$sqlsel .= "where t.client_id = ".$clientID." && t2.client_id = ".$clientID." && t3.client_id = ".$clientID." && t.category_id = 3 && (t.wine_id is not null || t.wine_id != 0) && t2.created_at = '".$created_at."' && t.created_at = '".$created_at."' group by t2.wine_id"; 
		}else if($catid == 4){
			$checkcol = "bottle_price";
			$sqlsel = "select t.wine_id, t3.Name, count(t.wine_id) as count, t.bottle_price, t.created_at from track_gdb t join track t2 on t.track_id = t2.track_id join track_elim t3 on t2.wine_id = t3.wine_id ";
 			$sqlsel .= "where t.client_id = ".$clientID." && t2.client_id = ".$clientID." && t3.client_id = ".$clientID." && t.category_id = 4 && (t.wine_id is not null || t.wine_id != 0) && t2.created_at = '".$created_at."' && t.created_at = '".$created_at."' group by t2.wine_id"; 
		}
	        		
		$result = mysql_query($sqlsel);
        $num = 0;
        $i = 0;
        if(!empty($result))
        {
	        $num = mysql_num_rows($result);
	        while($i < $num)
	        {  
		        $nameinnerone[$i] = mysql_result($result,$i,"Name"); 
		        $countinnerone[$i] = mysql_result($result,$i,"count"); 
		        $priceinnerone[$i] = mysql_result($result,$i,$checkcol); 
		        $totpriceinnerone[$i] = $countinnerone[$i] * $priceinnerone[$i];
		        $i++;
	        }   
        }
	    
//	    $pathinnerone = array("by bottle => type => wine","by bottle => type => country => wine","by bottle => type => grape => wine","by bottle => type => all => wine","by bottle => type => half bottles => wine","by bottle => type => large format => wine","by glass => wine");
//	    $countinnerone = array('10','20','30','40','50','60','70');
	    
	    $name['values'] = $nameinnerone;
	    $count['values'] = $countinnerone;
	    $price['values'] = $priceinnerone;
	    $totprice['values'] = $totpriceinnerone;

	    $allVisits1 = array ( 'name' => $name,
	    					  'count' => $count, 
	    					  'price' =>  $price,
	    					  'totprice' => $totprice,);
	    
	    //$allVisits2 = array('USA' => '200', 'France' => '300', 'Itly' => '100', 'Germany' => '400');
	    $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        echo json_encode($allVisits1);
		
	}
	
	public function getModule()
	{
		mysql_select_db($_SESSION['thisDbname']) or die ("Unable to select database!");
		$sql = "select * from module where retired = '1'";
    	//die_r($sql);
	    $result = mysql_query($sql);
		$i = $num = 0;
	   	$num = mysql_numrows($result);
	   	$hotels = array();
		while($i < $num)
		{  
			$module = mysql_result($result,$i,"id");
			$i++;
	    }
	    mysql_select_db($_SESSION['selectDbname']) or die ("Unable to select database!");
	    return $module;
	}


}
