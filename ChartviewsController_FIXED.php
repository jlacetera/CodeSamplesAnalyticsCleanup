<?php

/*  Revision History

    JL 12/2014 - Updated for mysqli, fixed some sql queries that were including table that weren't needed.
        Updated several functions based on comments below.
        Updated each function to return chartArray in %SESSION variable to be used by pdf/excel functions.
        ANALYTICSSC-20, ANALYTICSSC-22.  
        
   JL  3/2015  BALISEA-10 testing -  fixed issue in leastwinesnewAction() so that it wouldn't crash when there wasn't any data returned from query, and other issues with leastwines* when no data returned.
   
   JL 4/2015 - ANALYTICS-24 - fixed pieChartAction and attributeAction to only select attributes for wines. Removed adding in custom wines.  This is handled in track_gdb correctly.
   
   JL 5/2015 - ANALYTICSSC-28 - fixes to leastwinesnewAction and leastdowntblAction to support date ranges and being called for all bev types.  Removed unused functions, and
                                                     added supporting functions.
                                                     
  JL 6/2015 - ANALYTICSSC-29 - fixes to navigation analytics.
  
  JL  6/2015 - ANALYTICSSC-31 - updated navigation analytics to support path wine->by glass-> selection, without requiring a type after selecting by glass.
                                                    Fixed bug in indexAction - first touch analytics, where spirits and cocktails were displaying in wrong columns.
                                                    Updated leastdowntblAction() to take bevType id as parameter instead of name.  Name was causing issues on clients that had a space in the bev type name.
                                                    Also did some cleanup.
   
*/
    
/**
* Class Name:  Admin_ChartviewsController
* Class Description:   contains the functions that generate the data to display the analytics charts.
* Class Properties:  none
*/
 
class Admin_ChartviewsController extends SmartTouchRequestProcessor
{
	
	
	//JL - 12/14 - Changed this function - simplified processing because the correct data was not being retrieved.  
	//  Changed to select from track table and process category_id when start=1.
        //This function used to calculate First Touch analytics chart.
	public function indexAction()
	{   
		
        DBUtilities::selectDatabase($_SESSION['selectDbname']);
	
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
	    
	    /*  JL 12/14 - replaced this with code below to initialize chart arrays for each date in the date range.
	    $dateRange[$p] = $sqlstartDate;
		while (strtotime($startDate1) < strtotime($endDate)) {
		    $p++;
			$dateRange[$p] = date ("Y-m-d", strtotime("+1 day", strtotime($startDate1)));
			$startDate1 = $dateRange[$p];
            }
	*/
	       
        /* JL - 1/15 - not sure what the original sql was  doing - but this is the logic:
            select from track where start=1.
            For each row in track where start=1 - if category_id=1, and by_glass is set - then wine by glass was selected, otherwise wine by bottles.
            For all other category_ids - get count of beverages selected as first touch.
                                            
       */
		
       //JL - 12/14 - initialize arrays for all dates in date range.
       $initDateArray=array();
       $cnt=0;
	    $startDate1 = date ("Y-m-d", strtotime("-1 day", strtotime($startDate)));
		while (strtotime($startDate1) < strtotime($endDate)) {
			$dateRange= date ("Y-m-d", strtotime("+1 day", strtotime($startDate1)));
			$startDate1 = $dateRange;		
			$t = explode('-',$dateRange);
          $dt = $t[1]."-".$t[2];		
          $initDateArray[$cnt]['X']=$dt;
          $initDateArray[$cnt]['Y']=0;
          $dateIndexArray[$dt]=$cnt;
          $cnt++;	
		}
   
        //first we want to select from first_touch table to get all of the category_ids we should be selecting for.
        //this will give us category_id, name, and color for this chart.
        $categoryIdCnt=0;
        $cnt=0;
         $sql='select * from first_touch order by category_id';
         $returnArray=DBUtilities::getTableData($sql,false,true);  
         if (count($returnArray)>0) {
            $sql1="select count(category_id) as categoryCount, created_at from track where start = 1 and created_at between '".$sqlstartDate."' and '".$sqlendDate."' and ";
            foreach ($returnArray As $index=>$valx) {
         			$row=$returnArray[$index];
        			
         			if (isset($row["category_id"]) && isset($row["name"]) && (isset($row["color"]))) {
         				//initialize date/counts for this index of graph.
                    //$chartArray[$categoryIdCnt]=$initDateArray;    
                    $chartName=$row["name"];
                    $chartArray[$chartName]=$initDateArray;     
                    //setup array with name and color
                    $headerArray[$categoryIdCnt]=$row["name"];
                    $colorArray[$categoryIdCnt]=$row["color"];
                    $categoryId=$row["category_id"];
                    $chartNameIndex[$cnt]=$chartName;
                    $cnt++;
                	
                    //select count for category id.  if category_id=0 - then by glass - select for category_id=1 and by_glass=1, if category_id=1 - then select by_glass <>1;
                    if ($categoryId == 0) {
                  		$where=' and by_glass=1 ';
                  		$catId=1;              
                    }
                    else  if ($categoryId == 1) {
                  		$catId=1;
                  		$where=' and by_glass is null ';               
                    }  
                    else {
                        $where='';
                        $catId=$categoryId;   
                    }
                    //build sql
                    $sql2=$sql1."category_id=".$catId.$where." group by created_at";
                    //execute sql query to get counts for this category id
                    $returnCountArray=DBUtilities::getTableData($sql2,false,true);  
                    //if data returned - update $chartArray[] for this date/count.
                    if (count($returnCountArray)>0) {
                 	 	foreach ($returnCountArray As $dtIndex=>$dtValue) {
                            $dtRow=$returnCountArray[$dtIndex];
                            $count=$dtRow["categoryCount"];
                            $date=$dtRow["created_at"];
                            //get date in format MM-DD
                            $t = explode('-',$date);
                            $dt = $t[1]."-".$t[2];
                            $index=$dateIndexArray[$dt];                  
                            $chartArray[$chartName][$index]["Y"]=$chartArray[$chartName][$index]["Y"]+$count;    
                                      			
                  	}						                    
                  }                   
                 //finished processing this category_id from first_touch table.  	   	
         	$categoryIdCnt++;
         	}  //end if isset all values needed to select data       		
            }  //end foreach row in return Array
         	        
         }  //end of - if count returnArray > 0
         
         
        $moduleId = $_SESSION['moduleId'];
		
	    if (!empty($_SESSION['chartValues'])) {
            unset($_SESSION['chartValues']);
    	}
		
  		
	
    	/* JL - 1/2015 - this is re-written table driven, except for the fact that the client app expects these array indexes to always be hardcoded to the values below, otherwise
	    the front end will crash with jquery error.  Not sure why.  pdf export does not expect these to be hardcoded.  For now - leaving hardcoded.  
	    */
        /* set up chartArray for display, and sessionArray for pdf/excel exports. */
        //ANALYTICSSC-31 - fixed because cocktails and spirits were being dipslayed under the wrong columns. category=3, spirits=4.
        $indexArray=array('winesglass','winesbottle','beer','cocktails','spirits','pairings','featured');
        $sessionArray=array();
        $newArray=array();
        foreach ($chartNameIndex As $index=>$value) {
				//echo 'index: '.$index." $value: ".$value.'<br>';	
				$sessionArray[$indexArray[$index]]=$chartArray[$value];
				$thisArray['values']=array();
				$thisArray['values']=$chartArray[$value];	
				$newArray[$indexArray[$index]]=$thisArray;
			}	
			      
        $sessionArray['module']=$moduleId;
        $sessionArray['header']=$headerArray;
        $sessionArray['ftColor']=$colorArray;    
        $newArray['module'] = $moduleId;
	   	 $newArray['header']=$headerArray;
	 	 $newArray['ftColor']=$colorArray;
				     
	    $_SESSION['chartValues'] = array();
	    $_SESSION['chartValues'] = $sessionArray;
	    
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        
        echo json_encode($newArray);
  		
	}
	
	
	//JL 12/14
	//This is called to display Total Category View.
	// Removed track_rank from sql select statement.  Not needed - no data for chart is taken from track_rank table.  
        // Fixed some incorrect links in sql query that was causing a lot of data to be missing from chart.
	//Updated processing to fill in blank chart array for each date in date range, and then just execute 1 sql query.  This should be more efficient.
	// Note - that if date range > year - not sure what will happen with results because we don't use the year - just MM/DD in chart array.
	public function lineAction()
	{
		
            DBUtilities::selectDatabase($_SESSION['selectDbname']);
		
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
	    
	    
	    //JL - 12/14 - initialize arrays for all dates in date range.
	    $startDate1 = date ("Y-m-d", strtotime("-1 day", strtotime($startDate)));
		while (strtotime($startDate1) < strtotime($endDate)) {
			$dateRange= date ("Y-m-d", strtotime("+1 day", strtotime($startDate1)));
			$startDate1 = $dateRange;		
			$t = explode('-',$dateRange);
                        $dt = $t[1]."-".$t[2];			
                        $winesinnerone[$dt] = 0;
			$beerinnerone[$dt] = 0;
			$cocktailsinnerone[$dt] = 0;
			$spiritsinnerone[$dt] = 0;			
		}
		
		 //JL - 12/14 - removed track_rank table - not used for anything and old links were not correct and all data was not displaying.
		 //added extra logic to handle null category_id - it should only be counted if there is a wine_id and by_glass is set.
		 //otherwise all rows that are index/mainmenu, etc would be counted as wines.
		
            $sqlsel = "select t.category_id, (count(t.category_id) + count(t.by_glass)) as first_touch, t.created_at from track t ";
            $sqlsel .= " where t.client_id = '".$clientID."' && t.created_at between '".$sqlstartDate."' and '".$sqlendDate."' and (t.category_id is NOT null or (t.category_id is null and t.by_glass is NOT null and t.wine_id is NOT null))";
            $sqlsel.= " group by t.created_at, t.category_id";			
		
            $returnArray=DBUtilities::getTableData($sqlsel,false,true);
			
            if (count($returnArray) > 0) {
                foreach ($returnArray As $rowIndex => $rowValue) {
                    $row=$returnArray[$rowIndex];           
                     $ftcount = $row["first_touch"];
                     $cat_id = $row["category_id"];
                     $created_at = $row["created_at"];
                     $t=explode('-',$created_at);
                     $dt=$t[1].'-'.$t[2];
                     
                     //update date arrays based on category id for each category id displayed on this chart.
                   	if (($cat_id == null)  || ($cat_id == 1)) {
                            $winesinnerone[$dt] = $winesinnerone[$dt] +$ftcount;	                   	
                   	}   
                   	else if ($cat_id == 2) {  
                            $beerinnerone[$dt] = $beerinnerone[$dt] + $ftcount;
                   	}
                   	else if ($cat_id == 3) {
                            $cocktailsinnerone[$dt] = $cocktailsinnerone[$dt] + $ftcount;
                   	}
                   	else if ($cat_id == 4) {
                            $spiritsinnerone[$dt] = $spiritsinnerone[$dt] + $ftcount;
                   	}         	 	             
                    }   //end foreach loop		
            }  //end if return array count > 0        	
		
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
	
	//this is called to display attributes selected when viewing a wine.  
        //It does not include any wines that are not in the global wine database.
	//JL 12/2014 - fixed to include attributes for wines that are not in the wine global database, 
        //but the attributes need to be defined in the global database.  Did not change original processing,
        //just adding function call to include custom wine attributes.
        
/**
* Function Name:  attributeAction
* Function Description:    generates data for attribute analytics charts.  This data is only for wines.
* Parameters:  request parameters - chartName, startDate, endDate,clientId
* Return Values:  sets up data and arrays to generate charts.
*/        
	
public function attributeAction()
	{  
	
        DBUtilities::selectDatabase($_SESSION['selectDbname']);
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
	    
	    //make sure child chart data is cleared.
	    $_SESSION['attrGrapeData']=null;
	    $_SESSION['attrCountryData']=null;
	    $_SESSION['attrTypeData']=null;   
	    
	    //added category_id=1 - to only get attribute values for wine.  This graph is only intended for wines.
	    if ((strtotime($endDate) - strtotime($startDate)) >= 0)
	    {
            $sqlsel = "select count(type_id) as type_count,count(country_id) as country_count, count(region_id) as region_count, count(appellation_id) as appellation_count, ";
			$sqlsel .= "count(producer_id) as producer_count, count(grape_id) as grape_count from track_gdb ";
			$sqlsel .= "where client_id = '".$clientID."' && category_id=1 && created_at between '".$sqlstartDate."' and '".$sqlendDate."'";
	        
            $returnArray=DBUtilities::getTableData($sqlsel,false,true);	         
            $i = 0;
       
            foreach ($returnArray As $rowIndex => $rowValue) {
                $row=$returnArray[$rowIndex];
                $type = $row["type_count"];
              	$country = $row["country_count"];
                $region = $row["region_count"];
                $appellation = $row["appellation_count"];
                $producer = $row["producer_count"];
                $grape = $row["grape_count"];
                $i++;
            } //end rows loop
                      
            $allVisits = array("f_val" => $type,"a_val" => $country,"b_val" => $region,"c_val" => $appellation,"d_val" => $producer,"e_val" => $grape);
            
            $temp['allVisits']=$allVisits;
           
            
        }  //end if
	else 
	{  
            $allVisits = array("a_val" => "error");
	    	
	}  
	        
	    
	//JL 12/14 - added for pdf/excel exports.
    if(!empty($_SESSION['chartValues'])) {
		unset($_SESSION['chartValues']);
	}	    
	$_SESSION['chartValues'] = $allVisits;	
	
	 //set child chart data - for export and drill-down charts.
	 $_SESSION['attrGrapeData']=   $this->getAttributePieChartData("graphattr6",$clientID,$sqlstartDate,$sqlendDate);
	 $_SESSION['attrCountryData']=$this->getAttributePieChartData("graphattr2",$clientID,$sqlstartDate,$sqlendDate);
	 $_SESSION['attrTypeData']=     $this->getAttributePieChartData("graphattr7",$clientID,$sqlstartDate,$sqlendDate);
		
	$this->_helper->layout()->disableLayout();
    $this->_helper->viewRenderer->setNoRender(true);
    echo json_encode($allVisits);
		
	}	
	
	
	//JL 1/2015 - this function accepts an array as $countArray, and returns the sum of the values of the array elements.
private function sumAttributes($countArray) {
	$returnCount=0;
	
	if (count($countArray) > 0) {
		foreach ($countArray As $index => $value) {
			$returnCount=$returnCount+$value;		
		}	
	}
	return $returnCount;
}

/**
* Function Name:  piechartAction
* Function Description:    This function is called to display the pie chart values for attribute analytics when a bar graph is selected.
                                        Chart data should already be set in $_SESSION.  IF not - it is regenerated.
                                        This is only called for grape, country, and type.
                                        
* Parameters:  request parameters - chartName, startDate, endDate,clientId,divName
* Return Values:  sets up data and arrays to generate charts.
*/     	
	
//JL - 1/2015 - Updated to add in values that are calculated from attributeAction for global wine attributes for wines that are not in the global wine database.
	
	public function piechartAction()
	{
        DBUtilities::selectDatabase($_SESSION['selectDbname']);
        $chartName = $this->_request->getParam('chartName');
	    $startDate = $this->_request->getParam('startDate');
	    $endDate = $this->_request->getParam('endDate');
	    $clientID = $this->_request->getParam('clientID');
	    $divName = $this->_request->getParam('divName');
	    $sqlstartDate = date("Y-m-d", strtotime($startDate));
	    $sqlendDate = date("Y-m-d", strtotime($endDate));
	 
	 $chartDivIndex=array( "graphattr6" => "attrGrapeData",
	                                       "graphattr2" => "attrCountryData",
	                                       "graphattr7" => "attrTypeData");
	                                       
	  $sessionIndex=$chartDivIndex[$divName];
	  
	  if (isset($_SESSION[$sessionIndex]) && count($_SESSION[$sessionIndex])) {
	       $chartArray=$_SESSION[$sessionIndex];
	  }
	  else {
	       $chartArray=$this->getAttributePieChartData($divName,$clientID,$sqlstartDate,$sqlendDate);
	       $_SESSION[$sessionIndex]=$chartArray;
	  }
	     
	 
	$this->_helper->layout()->disableLayout();
    $this->_helper->viewRenderer->setNoRender(true);
    echo json_encode($chartArray);
		
}	

/**
* Function Name:  getAttributePieChartData
* Function Description:    This function is called to display the pie chart values for attribute analytics when a bar graph is selected.  This can be called
                                  when the bar chart is selected on the front end, or when the main attribute graph is displayed, so that the chart
                                  values are available for exporting pdf/excel formats.
* Parameters:  $divName - this is used to determine what type of attribute to report on (grape, country, type, etc)
                        clientID - this is needed to select the data correctly.
                        sqlstartDate - start date for chart.
                        sqlendDate - end date for chart.
* Return Values:  array - chart data in the correct format to display the chart.
*/     	
	
private function getAttributePieChartData($divName,$clientID,$sqlstartDate,$sqlendDate) 
{

    $chartArray=array();
    $sqlsel='';
    
 	if($divName == "graphattr2")
	    	{
            	$sqlsel = "select t.description, count(g.country_id) as total_count from track_gdb g ";
				$sqlsel .= "join ".$_SESSION['thisDbname'].".inventory_wine i on g.country_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id  ";
				$sqlsel .= "where client_id = '".$clientID."' && g.country_id is not null && g.category_id=1 && g.created_at between '".$sqlstartDate."' and '".$sqlendDate."' group by g.country_id";
	    	}
	    	else if($divName == "graphattr3")
	    	{
	    		$sqlsel = "select t.description, count(g.region_id) as total_count from track_gdb g ";
				$sqlsel .= "join ".$_SESSION['thisDbname'].".inventory_wine i on g.region_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id ";
				$sqlsel .= "where client_id = '".$clientID."' && g.region_id is not null && g.category_id=1 && g.created_at between '".$sqlstartDate."' and '".$sqlendDate."' group by g.region_id";
	    	}
	    	else if($divName == "graphattr4")
	    	{
	    		$sqlsel = "select t.description, count(g.appellation_id) as total_count from track_gdb g ";
				$sqlsel .= "join ".$_SESSION['thisDbname'].".inventory_wine i on g.appellation_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id ";
				$sqlsel .= "where client_id = '".$clientID."' && g.appellation_id is not null && g.category_id=1 && g.created_at between '".$sqlstartDate."' and '".$sqlendDate."' group by g.appellation_id";
	    	}
	    	else if($divName == "graphattr5")
	    	{
	    		$sqlsel = "select t.description, count(g.producer_id) as total_count from track_gdb g ";
				$sqlsel .= "join ".$_SESSION['thisDbname'].".inventory_wine i on g.producer_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id ";
				$sqlsel .= "where client_id = '".$clientID."' && g.producer_id is not null && g.category_id=1 && g.created_at between '".$sqlstartDate."' and '".$sqlendDate."' group by g.producer_id";	
	    	}
	    	else if($divName == "graphattr6")
	    	{
	    		$sqlsel = "select t.description, count(g.grape_id) as total_count from track_gdb g ";
				$sqlsel .= "join ".$_SESSION['thisDbname'].".inventory_wine i on g.grape_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id ";
				$sqlsel .= "where client_id = '".$clientID."' && g.grape_id is not null && g.category_id=1 && g.created_at between '".$sqlstartDate."' and '".$sqlendDate."' group by g.grape_id";
	    	}
	    	else if($divName == "graphattr7")
	    	{
	    	    //ANALYTICSSC-24 JL  - add category_id=1 to select.  This attribute graph/pie chart is only for wines.
	    		$sqlsel = "select t.description, count(g.type_id) as total_count from track_gdb g ";
				$sqlsel .= "join ".$_SESSION['thisDbname'].".inventory_wine i on g.type_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id ";
				$sqlsel .= "where client_id = '".$clientID."' && g.type_id is not null  && g.category_id=1 && g.created_at between '".$sqlstartDate."' and '".$sqlendDate."' group by g.type_id";
	    	}
	    	
	    	if ($sqlsel != '') {
            $returnArray=DBUtilities::getTableData($sqlsel,false,true);	         
        
            if (count($returnArray) > 0) {
                foreach ($returnArray As $rowIndex => $rowValue) {
			     $row=$returnArray[$rowIndex];
                    $country = $row["description"];
                    $totcount = $row["total_count"];
                    $chartArray[$country] = $totcount;
                }
            }
        }
          

    return $chartArray;


}
	//JL - 12/14 - ANALYTICSSC-20 
	//
	//This function generates the data for 'Selections' pie charts.
	//Updated to include data from wines that are not in the wine global database.
	//******  ANALYTICS-24 - this chart is removed from front end.
	
	public function beverageAction()
	{
		
            DBUtilities::selectDatabase($_SESSION['selectDbname']);
            $chartName = $this->_request->getParam('chartName');
	    $startDate = $this->_request->getParam('startDate');
	    $endDate = $this->_request->getParam('endDate');
	    $clientID = $this->_request->getParam('clientID');
	    $sqlstartDate = date("Y-m-d", strtotime($startDate));
	    $sqlendDate = date("Y-m-d", strtotime($endDate));
	    $_SESSION['chartName'] = $chartName;
	    
	    //JL 1/2015 - added for pdf/excel functions.
	    $_SESSION['startDate'] = $startDate;
	    $_SESSION['endDate'] = $endDate;
	       
	    if((strtotime($endDate) - strtotime($startDate)) >= 0)
	    {
	    	
         $sqlsel[0] = "select t.description, count(g.country_id) as total_count from track_gdb g ";
			$sqlsel[0] .= "join ".$_SESSION['thisDbname'].".inventory_wine i on g.country_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id ";
			$sqlsel[0] .= "where g.client_id = '".$clientID."' && g.wine_id is not null && g.country_id is not null && g.created_at between '".$sqlstartDate."' and '".$sqlendDate."' group by g.country_id";
			//$sqlsel[0] .= "where g.client_id = '".$clientID."' && g.country_id is not null && g.created_at between '".$sqlstartDate."' and '".$sqlendDate."' group by g.country_id";
	    	
    		$sqlsel[1] = "select t.description, count(g.region_id) as total_count from track_gdb g ";
			$sqlsel[1] .= "join ".$_SESSION['thisDbname'].".inventory_wine i on g.region_id = i.id join ".$_SESSION['thisDbname'].".tag_wine t on i.tag_id = t.id ";
			$sqlsel[1] .= "where g.client_id = '".$clientID."' && g.wine_id is not null && g.region_id is not null && g.created_at between '".$sqlstartDate."' and '".$sqlendDate."' group by g.region_id";
			//$sqlsel[1] .= "where g.client_id = '".$clientID."' && g.region_id is not null && g.created_at between '".$sqlstartDate."' and '".$sqlendDate."' group by g.region_id";
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
			//$sqlsel[2] .= "where g.client_id = '".$clientID."' && g.grape_id is not null && g.created_at between '".$sqlstartDate."' and '".$sqlendDate."' group by g.grape_id";


	    	for($k=0;$k < count($sqlsel);$k++)
	    	{
	            $i = 0;
	            $allVisits2 = array();
	            
              $returnArray=DBUtilities::getTableData($sqlsel[$k],false,true);	         
      
	            if (count($returnArray) > 0)
				   {       
				  
		          foreach ($returnArray As $rowIndex => $rowValue) {	
		              $row=$returnArray[$rowIndex];            
	            	     $country = $row["description"];
	                  $totcount = $row["total_count"];
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

	//array[0] = country, array[1]= region, array[2]=grapes		
    //add wines that don't have global wine_id to the counts above.
        
         $this->addCustomWineSelections($allVisits,$clientID,$_SESSION['thisDbname'],$sqlstartDate,$sqlendDate,false);    
             
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
		
		
	/* JL - Jan 2015 - This is called form AttributeAction and beverageAction (selections analytics) to return the count of attributes for wines that are not in the global wine database.  The attributes must
	                             be in the global wine database, but the wine_id is not in the global wine database.  Wines that are in the global wine database are already accounted for the in the track_gdb table.
	                             Updates the parameter countArray with the attribute counts.  
	*/
 private function addCustomWineSelections(&$countArray,$clientId,$globalDatabase,$startDate,$endDate,$allAttributes=false) {
 	
   //we need the client smartcellar database - to get to their wines table.
   $smartcellarDB=$this->getSmartcellarDB($clientId);
   
   if ($smartcellarDB != "") {
   	 //select all wine ids that are in the track table for the date and do not have global wine ids in the client wine table (which would mean that they aren't in the track_gdb table)
    $sql="select t.*, w.id from track t, ".$smartcellarDB.".wines w where t.created_at between ";
    $sql=$sql."'".$startDate."' and '".$endDate."' and w.id=t.wine_id and (w.gwdb_id is null or w.gwdb_id = 0)";   	
   		
    $returnArray=DBUtilities::getTableData($sql,false,true);
   		
    //set up arrays with counts for each grape_id, region_id, country_id
    if (count($returnArray) > 0) {
   			
   	$grapes=array();
   	$countries=array();
   	$regions=array();
   	$appellations=array();
   	$producers=array();
   	$types=array();
   			
   	foreach ($returnArray As $index=>$val) {
            $row=$returnArray[$index];
             $this->updateCount($grapes,$row['grape_id']);
             $this->updateCount($countries,$row['country_id']);
             $this->updateCount($regions,$row['region_id']);	
            if ($allAttributes === true) {
		$this->updateCount($appellations,$row['appellation_id']);
		$this->updateCount($producers,$row['producer_id']);
		$this->updateCount($types,$row['type_id']);				
            }                      			 			
   	}
   		
    	//for each array - convert id to global description to match other array.   		
    
   	$descArray[0]=$this->convertIdToGlobalDescription($countries,"en_countries",$smartcellarDB,$globalDatabase,'category_id',1);
 	$descArray[1]=$this->convertIdToGlobalDescription($regions,"en_regions",$smartcellarDB,$globalDatabase,'category_id',1);
  	$descArray[2]=$this->convertIdToGlobalDescription($grapes,"en_grapes",$smartcellarDB,$globalDatabase,'category_id',1);
  	if ($allAttributes === true) {
  		$countArray["countries"] = $descArray[0];
  		$countArray["regions"] = $descArray[1];
  		$countArray["grapes"] = $descArray[2];
  		$countArray["producers"] = $this->convertIdToGlobalDescription($producers,"en_producers",$smartcellarDB,$globalDatabase,'category_id',1);
  		$countArray["appellations"] = $this->convertIdToGlobalDescription($appellations,"en_appellations",$smartcellarDB,$globalDatabase,'category_id',1);
  		$countArray["types"] = $this->convertIdToGlobalDescription($types,"en_wine_types",$smartcellarDB,$globalDatabase,'bev_category',1);
  	}
  		  
    
        //If calling for selections (allAttributes == false - for each array - merge with values in countArray passed in.   
         if ($allAttributes === false) {
   		   for ($j=0; $j<3; $j++) {
    		//for each descArray - see if exists in allVisits array
    			if (count($descArray[$j])>0) {
    				foreach ($descArray[$j] As $index=>$value) {
    					//if this description exists - add values to it.
						if (isset($countArray[$j][$index])) {
							$countArray[$j][$index]=$countArray[$j][$index]+$value;
						}
						else { //if doesn't exist - create index in allVisits.
							$countArray[$j][$index]=$value;				
						}    		
    				} //end foreach 	
    			}
            }	    //end for $j loop
   		}
   	} //end if count(returnArray)>0 - first select statement
  }   //end if smartcellarDB exists.  
 }		
 
 /* JL - 12/2014 - this function takes an array of ids - array[id] = count (country, grape, region), and returns an array[global description]=count.
     The attribute must trace to the global description in the global wines database.  If it doesn't - then the id/count will be dropped and not returned in the returnArray.
*/
     
 private function convertIdToGlobalDescription($idArray,$idTable,$smartcellarDB,$globalDatabase,$categoryIdFieldName,$categoryId) {
     
	$returnArray=array();
	
	$sql1="select g.Global_Id as global_id, t.description as description, iw.id, t.id from ".$smartcellarDB.".".$idTable." g, ".$globalDatabase.".tag_wine t, ";
	
	$sql1=$sql1.$globalDatabase.".inventory_wine iw where  iw.id=global_id and iw.tag_id=t.id and g.".$categoryIdFieldName."=".$categoryId." and g.Id=";
	
	if (count($idArray)>0) {
		foreach ($idArray As $index=>$value) {
			$sql=$sql1.$index;
			$row=DBUtilities::getTableData($sql,true,true);
			if (isset($row['description'])) {
				$returnArray[$row["description"]]=$value;			
			}
		}
	}
	return $returnArray;
 }

 
/* JL - 1/2015 - this function updates a count array by either incrementing the count
 * if the array index exists, or setting the array index to 1 if the index doesn't exist.
 */
 
private function updateCount(&$countArray,$arrayIndex) {
	//make sure id is set, and is a valid number.
	if (isset($arrayIndex) && $arrayIndex>0) {
   		//If array index already set - then increment.
    	if (isset($countArray[$arrayIndex])) {
			  $countArray[$arrayIndex]++;    
    	}
    	else {  //initialize count to 1
			$countArray[$arrayIndex]=1;    
    	}
    }
}

// JL - 12/2014 - this function returns the client_name table to return the smartcellar client database for this client id.
//
private function getSmartcellarDB($clientId) {
	
	$returnVal='';
	$sql='select db_name from client_name where id='.$clientId;
	
	$row=DBUtilities::getTableData($sql,true,true);
	
	if (count($row)>0) {
		$returnVal=$row['db_name'];	
	}
	
	return $returnVal;
}
		
/**
* Function Name:  navigationAction
* Function Description:  This is called to display the navigation/path statistics main chart.

Updated this chart to support data for the following paths:
	    Category->Type->Selection
	    Category->Selection (beers, spirits, cocktails)
	    Category (beers, spirits, cocktails that don't have a type, and display details without making a selection.)
	    Category ->By Bottle/By Glass -> Type -> Selection (for wines)
        
* Parameters:  Input parameters from the request processor are:
                       startDate - start date for report.
                       endDate - end date for report.
                       
* Return Values:    Array of chart values - returns the following columns for each type in the category - 
                             path, path count, detail view count, path percent.                     
*/
	    
public function navigationAction()
	{  

		DBUtilities::selectDatabase($_SESSION['selectDbname']);
		$chartName = $this->_request->getParam('chartName');
	    $startDate = $this->_request->getParam('startDate');
	    $endDate = $this->_request->getParam('endDate');
	    $clientID = $this->_request->getParam('clientID');
	    $sqlstartDate = date("Y-m-d", strtotime($startDate));
	    $sqlendDate = date("Y-m-d", strtotime($endDate));
	    $_SESSION['startDate'] = $startDate;
	    $_SESSION['endDate'] = $endDate;
	    $_SESSION['chartName'] = $chartName;
        
        /* select track records for date range so that we can build the paths */
        $sqlsel='select category_id, wine_id, type_id, by_glass from track where created_at between "'.$sqlstartDate.'" and "'.$sqlendDate.'"';
        $sqlsel .= ' and category_id > 0';
        
       $returnArray=DBUtilities::getTableData($sqlsel,false,true);
       
       $pathArray = array();
       $pathSelectedArray = array();     
       $percentArray=array(); 
       $totalCount=0;
       
       //set up arrays based on paths 
       if (count($returnArray)) {
           //if we have data - get category and type arrays - so that we can fill in ids with actual names (Wine, beer, etc,)
            $smartcellarDB=$this->getSmartcellarDB($clientID);
            $categoryNameArray=array();
            $typeNameArray=array();

            $sql='select id, name from '.$smartcellarDB.'.bev_categories';
            $categoryNameArray=DBUtilities::getTableData($sql,false,true);
            $categoryNameArray=$this->indexArrayByField($categoryNameArray,"id",'','name');
            
             $sql='select Id as id, Name as name, bev_category from '.$smartcellarDB.'.en_wine_types'; 
             $typeNameArray=DBUtilities::getTableData($sql,false,true);
        
             //maybe send in flag to clean up name field, or handle this how other report is handled for translations.
             $typeNameArray=$this->indexArrayByField($typeNameArray,'bev_category','id','name');  
             
              //get item names from wines table to display names instead of id.
            $sql='select id, Name as name, category_id from '.$smartcellarDB.'.wines';
            $wineNameArray=DBUtilities::getTableData($sql,false,true);
            $wineNameArray=$this->indexArrayByField($wineNameArray,'id','','name'); 
             
            foreach ($returnArray as $index=>$row) {
                $path='';
                $catName='';
                $typeName='';
                $catId=$row['category_id'];
                $typeId=isset($row['type_id']) && $row['type_id'] != '' ? $row['type_id'] : '';
                $wineId=isset($row['wine_id']) && $row['wine_id'] != '' ? $row['wine_id'] : '';
                if ($catId != '' && isset($categoryNameArray[$catId]['name'])) {
                    $catName=$categoryNameArray[$catId]['name'];     
                }
                     
                if ($typeId != '' && isset($typeNameArray[$catId][$typeId])) {
                    $typeName=$typeNameArray[$catId][$typeId]['name'];                
                }   
            
                if ($catName != '') {
                    $path='';
                    //for wines - must set by bottle or by glass before type id.
                    if ($catId == 1) {
                        if (isset($row['by_glass']) && $row['by_glass'] == 1) {
                            $path.=' -> By Glass';                        
                        }
                        else {
                            $path.=' -> By Bottle';                        
                        }
                        if ($typeId != '') {
                            $path.='-> '.$typeName;
                        }
                    }
                    else {  //set path to typeName if it is set.
                        if ($typeName != '') {
                            $path.='-> '.$typeName;
                        }
                    }
                    //Removing if below - we are going to include paths that are just selecting a category - without  a type - to accommodate
                    //front ends that don't require type selection.
                    //if (($path != '') || ($path == '' && $catName != '')) {
                    $path = $catName.' '.$path;
                    //added extra check by category _id because it seems there is bad data in the track table that doesn't make sense.  So this check will catch it.
                    if ($wineId != '' && isset($wineNameArray[$wineId]) && $catId==$wineNameArray[$wineId]['category_id'])  {  
                        //increment count array for this selection in this path.
                        isset($pathSelectedArray[$path]["SELECTED"][$wineId]) ? $pathSelectedArray[$path]["SELECTED"][$wineId] += 1 : $pathSelectedArray[$path]["SELECTED"][$wineId] = 1;
                        isset($pathSelectedArray[$path]["SELECTED"]['COUNT']) ? $pathSelectedArray[$path]["SELECTED"]['COUNT'] += 1 : $pathSelectedArray[$path]["SELECTED"]['COUNT'] = 1;
                    }
                       
                       //increment array for this path
                    isset($pathArray[$path]) ? $pathArray[$path] += 1 : $pathArray[$path] = 1;
                    //increment total count for figuring out percent.
                    $totalCount++;  
                }  //end if catName != ''      
            }       
       }
       

       
       //calculate percent if there is data.
       if ($totalCount > 0) {
            foreach ($pathArray as $index=>$val) {
                $perc=0;
                if ($val>0) {
                    $perc=($val * 100)/$totalCount;  
                    $perc=number_format((float)$perc, 2, '.', '');          
                }
                $percentArray[$index]=$perc;       
            }
        }
     
       //JL 12/14 - adding initializing variables in case nothing returned from sql statement.
       $pathCount=array();  //this is the path to display - which is index.
       $pathIndex=array(); //this is the count to display
       $pathPercent=array();  //this is the percent to display
       $pathSelectedCount=array();  //this is the index to the arrays - so when selected can get the detailed info.
       
       //reorder by percent values, they should display least to most selected.
         $i=0;
         if (count($percentArray) > 0) {
              arsort($percentArray,SORT_NUMERIC);
              foreach ($percentArray As $rowIndex => $percent) {
                  $pathCount[$i] = $pathArray[$rowIndex];
                  $pathIndex[$i] = $rowIndex;
                  $pathPercent[$i]=$percent;
                  $pathSelectedCount[$i]=0;
                  if (isset($pathSelectedArray[$rowIndex]["SELECTED"]['COUNT'] )) {
                      $pathSelectedCount[$i]=$pathSelectedArray[$rowIndex]["SELECTED"]['COUNT'];    
	              }
	              $i++;              
              }      
         }
	    
		if(!empty($_SESSION['chartValues'])) {
			unset($_SESSION['chartValues']);
		}
		$chartValues = array('path' => $pathIndex, 'count'  => $pathCount, 'percent' => $pathPercent, 'detailViewCount' => $pathSelectedCount);
	   
		$_SESSION['chartValues'] = $chartValues;
		//used by navigation popup
        $_SESSION['chartNavigationDetails']=$pathSelectedArray;	    
	    
	    $pathValues["values"] = $pathIndex;
	    $countValues['values'] = $pathCount;
	    $codeValues['values'] = $pathSelectedCount;
	    $percentValues['values'] = $pathPercent;

	    $allVisits1 = array ( 'path' => $pathValues, 
	                          'count' =>  $countValues,
	    					  'detailViewCount' =>  $codeValues,
	    						'percent' =>  $percentValues);
	   
	    $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        
        echo json_encode($allVisits1);
}
	
/**
* Function Name:  navigationpopupAction
* Function Description:  This is called to display the navigation/path detail chart - which is detail views for the path.
                                        This is called when a row from path statistics is selected, and displays the items, and the count selected.
                                         All data from this chart is calculated in navigationAction, and passed to this function in $_SESSION['chartNavigationDetails'];

    Function Parameters - from request processor - 
                    codeId - index of path selected.
                     clientID - client id used to get smatcellar database.
                       
* Return Values:    Array of chart values - returns the following columns  Name, Count.
                                                 
*/	
	
	
public function navigationpopupAction()
{ 
		
    DBUtilities::selectDatabase($_SESSION['selectDbname']);
    $codeId = $this->_request->getParam('codeId');
    $clientID = $this->_request->getParam('clientID');
    //the parameters below or not used anymore.	    
	//$startDate = $this->_request->getParam('startDate');
	//$endDate = $this->_request->getParam('endDate');
	//$sqlstartDate = date("Y-m-d", strtotime($startDate));
	//$sqlendDate = date("Y-m-d", strtotime($endDate));
	    
    $pathSelectedArray=$_SESSION['chartNavigationDetails'];

    $selectedArray=array();		
    if (isset($pathSelectedArray[$codeId]['SELECTED'])) {
        $selectedArray=$pathSelectedArray[$codeId]['SELECTED'];
    }
	    
    $itemSelectedArray=array();
    $itemCountArray=array();
    $totalCountArray=array();   
      
    //get item names from wines table to display names instead of id.
    $smartcellarDB=$this->getSmartcellarDB($clientID);
    $sql='select id, Name as name from '.$smartcellarDB.'.wines';
             
    $nameArray=DBUtilities::getTableData($sql,false,true);
        
     $nameArray=$this->indexArrayByField($nameArray,'id','','name');      
      
    foreach ($selectedArray as $index=>$value) {
        if ($index == 'COUNT') {
            $totalCountArray[]=$value;
        }
        else {
            $itemCountArray[]=$value;
            $itemSelectedArray[]=isset($nameArray[$index]['name']) ? ($nameArray[$index]['name']) : $index;  
        }
    }
	    
	//re-order by selected count
	arsort($itemCountArray, SORT_NUMERIC);
	    
	$itemSelectedSorted=array();
	$itemCountSorted=array();
	if (count($itemCountArray)) {
        foreach ($itemCountArray as $index=>$value) {
            $itemSelectedSorted[]=$itemSelectedArray[$index];
            $itemCountSorted[]=$value;            
        }	    
    }
	    	    
	$path['values'] = $itemSelectedSorted;
	$count['values'] = $totalCountArray;
	$code['values'] = $itemCountSorted;

	$chartDetailValues = array ( 'selectedPath' => $path, 
	                          'totalCount' =>  $count,
	    					  'selectedCount' =>  $code);
	    					  
    if (!empty($_SESSION['chartDetailValues'])) {
        unset($_SESSION['chartDetailValues']);
		}	    
    $_SESSION['chartDetailValues'] = $chartDetailValues;	  	    					  
    $this->_helper->layout()->disableLayout();
    $this->_helper->viewRenderer->setNoRender(true);
    echo json_encode($chartDetailValues);
}
	
	
/**
* Function Name:  leastwinesnewAction
* Function Description:  This is called for all category types to display the information on the least popular chart.
* Parameters:  Input parameters from the request processor are:
                       catName - category name from request processor to determine the category_id.
                       startDate - start date for report.
                       endDate - end date for report.
                       
* Return Values:    Array of chart values - returns the following columns for each type in the category - 
                             Type Name,  Total Types In Inventory,  Total Times Selected,  Percent Selected.
*/

	
	public function leastwinesnewAction()
	{
	
		DBUtilities::selectDatabase($_SESSION['selectDbname']);
		$_SESSION['chartName'] = $this->_request->getParam('chartName');
	    $catName = $this->_request->getParam('catName');
	    $startDate = $this->_request->getParam('startDate');
	    $endDate = $this->_request->getParam('endDate');
	    
	    $clientID = $this->_request->getParam('clientID');
	    $_SESSION['clientID'] = $clientID;
	    if(!empty($_SESSION['catName'])) {unset($_SESSION['catName']);}
		if(!empty($_SESSION['sortName'])) {unset($_SESSION['sortName']);}
	    $_SESSION['catName'] = $catName;
	    
	    $sqlstartDate=$sqlendDate='';
	    if ($startDate != '' && $endDate != '') {
	        $sqlstartDate = date("Y-m-d", strtotime($startDate));
	       $sqlendDate = date("Y-m-d", strtotime($endDate));
	    }
	    
	    $_SESSION['startDate'] = $startDate;
	    $_SESSION['endDate'] = $endDate;
	    
	     $smartcellarDB=$this->getSmartcellarDB($clientID);
	     
	     $categoryId=$this->getCategoryIdForName($catName);
	    
	    	//Total count in smartcellar database by type
	    	$sql = "SELECT count(*) as typeCount, w.Type_Id, en.Name FROM ".$smartcellarDB.".wines w,  ".$smartcellarDB.".en_wine_types en where w.category_id=$categoryId and w.Type_Id=en.id  and w.In_Stock=1 and w.active=1 group by w.Type_Id";
	    
	    $totalInvCountByTypeArray=DBUtilities::getTableData($sql,false,true);
	 
	     //Get the Count each type was selected from track table.
	    	  $sql="SELECT count(*) as selectedCount,  t.wine_id, w.id, w.Type_Id   FROM track t, ".$smartcellarDB.".wines w  where t.wine_id=w.id and w.category_id=$categoryId";
          if ($sqlstartDate != '') {
             $sql=$sql." and t.created_at between '$sqlstartDate' and '$sqlendDate'" ;
          }	    	  
	    	  $sql.=" and w.In_Stock=1 and w.active=1  ";
	    	  $sql=$sql." group by w.Type_Id order by selectedCount";
	    	 
          $totalSelectedByTypeArray=DBUtilities::getTableData($sql,false,true);
	    
	    //reindex array by type - so that it is easy to loop thru and setup chart arrays. 
	     $totalSelectedByTypeArray=$this->indexArrayByField($totalSelectedByTypeArray,"Type_Id");

		$totalSelections=0;
        $index=0;	
        			     
	     foreach ($totalInvCountByTypeArray as $index=>$value) {
	         $thisType=$value["Type_Id"];
	         $thisTypeCount=$value["typeCount"]; 
	        $thisSelectedCount= (isset($totalSelectedByTypeArray[$thisType])) ? $totalSelectedByTypeArray[$thisType]['selectedCount'] : 0;
            //This is used to calculate the percent.
            $totalSelections=$totalSelections+$thisSelectedCount;
            //setup arrays by index, with index/translation type.     
            $typeIndex[$index]=$thisType;
            $chartDisplayName[$index]=$this->fixName($value["Name"]);
            $chartTotalType[$index]=$value["typeCount"];
            $chartSelectedCount[$index]=$thisSelectedCount;
            //will do percentages when we know total - percent selected.
            $chartPercentValues[$index]=0;
            $index++;          
	     }

         //initialize arrays in case there is no data.   
         $chartDisplayNameSorted=array();
         $chartSelectedCountSorted=array();
         $chartTotalTypeSorted=array();
         $chartPercentValuesSorted=array();
         $chartTypeSorted=array();
         
         //calculate percentages, now that we have totalSelections, only if totalSelections >0
        if ($totalSelections > 0) {
            foreach ($chartDisplayName As $index=>$value) {
                $perc=($chartSelectedCount[$index] * 100)/$totalSelections;
                $perc=number_format((float)$perc, 2, '.', '');
                $chartPercentTemp[$index]=$perc;
            } 
            
            //reorder by percent values, they should display least to most selected.
          asort($chartPercentTemp);
          //change array indexes so that return arrays are in the correct order.
          $index=0;
          foreach ($chartPercentTemp As $ind => $val) {
                $chartDisplayNameSorted[$index] = $chartDisplayName[$ind];
                $chartSelectedCountSorted[$index]=$chartSelectedCount[$ind];
                $chartTotalTypeSorted[$index]=$chartTotalType[$ind];
                $chartPercentValuesSorted[$index]=$val;
                $chartTypeSorted[$index]=$typeIndex[$ind];
                $index++;          
          }
        }
         
         //set up arrays for display on the chart.
            $type['values'] = $chartDisplayNameSorted;
	        $elimination['values'] = $chartSelectedCountSorted;
	        $count['values'] = $chartTotalTypeSorted;
		    $percent['values'] = $chartPercentValuesSorted;   
		    $typeId['values'] = $chartTypeSorted;
		    
		//ANALYTICSSC-31 - updated to send front end type_id as well as type name, needed to process details report.
		     $chartValuesArray = array ( 'type' => $type,
		    					                'elimination' => $elimination,
		    					                'count' => $count, 
		    					                 'percent' =>  $percent,
		    					                 'typeId' => $typeId);  
		    					                 
	  
   //output chart values
	if (!empty($_SESSION['chartValues'])) {
       unset($_SESSION['chartValues']);
   }	    
   $_SESSION['chartValues'] = $chartValuesArray;	  
		   
	$this->_helper->layout()->disableLayout();
	$this->_helper->viewRenderer->setNoRender(true);     
	echo json_encode($chartValuesArray);		    
	}
	
/**
* Function Name:  indexArrayByField
* Function Description:  takes input array and returns an array that is indexed by the fieldName input parameter.  This is useful is you have a lookup table that you want to keep in a local array and lookup field values based on
                                     a specific type, instead of continually querying the database.
                                     if fieldValue is not set then that row in the array won't be returned.
                                     
* Parameters:    inputArray:  array that will be re-indexed.  Should be an array of associative arrays.
                        fieldName = field in associative array to index return array by.
                        fieldName2 - field name to return associative array with 2 index fields.
                        fixFieldName - field name to fix for translations.
                        
* Return Values:  array that is re-indexed by field field value instead of array index.
*/

public function indexArrayByField($inputArray,$fieldName,$fieldName2='',$fixFieldName=null) 
{    
    //some quick parameter checking - 
    if ($fieldName == ''  || $fieldName == null) {
        return $inputArray;    
    }
    
    $returnArray=array();
    
    if (count($inputArray)>0) {
        foreach ($inputArray As $index => $val) {
            if (isset($val[$fieldName]) && strlen($val[$fieldName])>0) {
                if ($fixFieldName != null and isset($val[$fixFieldName])) {
                    $val[$fixFieldName]=$this->fixName($val[$fixFieldName]);             
                }
                if ($fieldName2 && isset($val[$fieldName2]) && strlen($val[$fieldName2]))  {
                    $returnArray[$val[$fieldName]][$val[$fieldName2]]=$val;
                }
                else {
                    $returnArray[$val[$fieldName]]=$val;
                }      
           }       
        }        
    }
    
    return $returnArray;  
}
	
/**
* Function Name: getCategoryIdForName
* Function Description: returns the smartcellar category_id based on the request processor input parameters - catName for Least/Most popular charts.
* Parameters:  catName - this is the category name that is selected from the front end drop-down.
* Return Values:  the smartcellar category id.
*/

 private function getCategoryIdForName($catName) 
 {
 
  //set category_id based on catName passed in.
  $categoryId=1;
  if (isset($catName)) {
        switch($catName) {
            case 'ddwine':
                $categoryId=1;
                break;
            case 'ddbeer':
                $categoryId=2;
                break;
            case 'ddspirit':
                $categoryId=4;
                break;
            case 'ddcocktail':
                $categoryId=3;
                break;
             default:
                 $categoryId=1;
                break;
	     }	
    }
    return $categoryId; 
 }
 
 
 /* 
 *  Function Name:  getBevTypeName
 * Function Description: Returns the smartcellar beverage type name based on the beverae type id passed in.
                                             
* Parameters:  bevTypeId - the id of the beverage type
                       smartcellarDB - the name of the smartcellar database.
* Return Values:  returns the beverage type Name from the en_wine_types table.
 */
 
 private function getBevTypeName($bevTypeId,$smartcellarDB) 
{

    $bevTypeName='';

    $sql="select Name from ".$smartcellarDB.".en_wine_types where Id = $bevTypeId";
    
    $returnRow=DBUtilities::getTableData($sql,true,true);
    if (isset($returnRow['Name'])) {
        $bevTypeName=$returnRow['Name'];    
    }
    
    return $bevTypeName;
}	  
 
 
 /**
* Function Name: getBevTypeIdFromName
* Function Description: Returns the smartcellar beverage type id based on the beverae typename passed in.
                                    This needs to support translations, which is why 'like' is used, example - balisea.
                                    
* Parameters:  bevTypeName - the name of the beverage type displayed on the charts.
                       smartcellarDB - the name of the smartcellar database.
* Return Values:  returns the beverageTypeId from the smartcellar database.
*/

private function getBevTypeIdFromName($bevTypeName,$smartcellarDB) 
{

    $bevTypeId='';
    $bevTypeName=$bevTypeName.'%';
    $sql="select Id from ".$smartcellarDB.".en_wine_types where Name like '$bevTypeName'";
    
    $returnRow=DBUtilities::getTableData($sql,true,true);
    if (isset($returnRow['Id'])) {
        $bevTypeId=$returnRow['Id'];    
    }
    
    return $bevTypeId;

}	  
	  
	  	
/**
* Function Name:  leastdowntblAction
* Function Description:  This is called for all category types to display the detail information on the least popular chart when a beverate type is selected.

  The locig to generate the chart data:
        1.  Select all active rows from wine table for the category id and beverage type.
        2.   Select all beverages selected in track table for the date range.
        3.  Remove from the array of all beverages the beverages that were selected in the date range.
        4.  return in chart arrays all beverages not selected, followed by all beverages selected ordered by least to most.
        
* Parameters:  Input parameters from the request processor are:
                       catName - category name from request processor to determine the category_id.
                       codeId  - beverage type id from request processor.  Indicates the beverage type to generate details for.
                       startDate - start date for report.
                       endDate - end date for report.
                       
* Return Values:    Array of chart values - returns the following columns for each type in the category - 
                             Beverage Name,  Country Name (for wines only),  Total Times Selected,  Percent Selected.
*/
	public function leastdowntblAction()
	{

		DBUtilities::selectDatabase($_SESSION['selectDbname']);
	    
	    $bevTypeId=$this->_request->getParam('codeId');
	    $clientID = $this->_request->getParam('clientID');
	    
        $catName=$_SESSION['catName'];    
	    $startDate=$_SESSION['startDate'];
	    $endDate=$_SESSION['endDate'];	    
	    
	    $sqlstartDate=$sqlendDate='';
	    if ($startDate != '' && $endDate != '') {
	        $sqlstartDate = date("Y-m-d", strtotime($startDate));
	       $sqlendDate = date("Y-m-d", strtotime($endDate));
	    }
	    
	    $categoryId=$this->getCategoryIdForName($catName);
	    $smartcellarDB=$this->getSmartcellarDB($clientID);
        
        $bevTypeName=$this->getBevTypeName($bevTypeId,$smartcellarDB);
        
        //Selecting all active wines/bevs currently in inventory.
        $sql="SELECT w.id, w.Name, w.Country_Id as countryName  from ".$smartcellarDB.".wines w  where ";        
        $sql.="w.category_id=$categoryId and w.Type_Id= $bevTypeId  ";
        $sql.=" and w.In_Stock=1 and w.active=1 order by w.Name";
           
	    $allWinesArray=DBUtilities::getTableData($sql,false,true);
	    //get array of all inventory indexed by wine_id.
        $allWinesById=$this->indexArrayByField($allWinesArray,'id'); 	    
	    
	    //selectedArray is list of all bevs that were selected during the date range - from the track table.
	    //Not necessary to check wine.in_stock or wine.active - because if the beverage was selected, it must have been active at that time.
         $selectedArray=array();
	     $sql="SELECT count(*) as selectedCount,  t.wine_id, w.id, w.Type_Id, w.Name, w.Country_Id as countryName    FROM track t, ".$smartcellarDB.".wines w  where t.wine_id=w.id and w.category_id=$categoryId and w.Type_Id=$bevTypeId  "; 
          if ($sqlstartDate != '') {
             $sql=$sql." and created_at between '$sqlstartDate' and '$sqlendDate'" ;
          }	    	  
          
	  $sql=$sql." group by w.id order by selectedCount desc" ;	 
	  $selectedArray=DBUtilities::getTableData($sql,false,true);
	   
	//get countries array for this category type.
	$sql="SELECT Id,Name from ".$smartcellarDB.".en_countries where category_id=".$categoryId;
	$countryArray=DBUtilities::getTableData($sql,false,true);
	
	$countryArray=$this->indexArrayByField($countryArray,'Id');
	 
    //process the selectedArray 
    //initialize arrays in case there is no data.
	$countrySorted=array();
	$countSorted=array();
	$percentSorted=array();
	$wineNameSorted=array();
	
	   $i=0;
	   $totalCount=0;
	   if (count($selectedArray)>0) {
	     foreach ($selectedArray As $rowIndex => $rowValue) {
		     $row=$selectedArray[$rowIndex];	
		      $wineName[$i] = $this->fixName($row["Name"]);
		     
          	/*
          	$countryName='Not Currently Active';
          	if (isset($allWinesById[$row['id']])) {
          	    $countryName=$allWinesById[$row['id']]['countryName'];
          	}
          	*/
          	$countryName='';
          	if (isset($countryArray[$row['countryName']])) {
                $countryName=$countryArray[$row['countryName']]['Name'];
          	}
          	$country[$i] = $this->fixName($countryName);
        	    $count[$i] = $row['selectedCount'];
        	    $percent[$i] = 0;
        	    $totalCount+=$count[$i];
        	    $i++;
        	    //unset value in all wines array - to indicate that it was selected, so that this wine/beverage doesn't display in unselected part of chart.
        	    unset($allWinesById[$row['id']]);
        }  
    }
    //calculate percentages, now that we have totalSelections, only if totalSelections >0
    if ($totalCount > 0) {
            foreach ($wineName As $index=>$value) {
                $perc=($count[$index] * 100)/$totalCount;
                $perc=number_format((float)$perc, 2, '.', '');
                $percentTemp[$index]=$perc;
            } 
            //reorder by percent values, they should display least to most selected.
          asort($percentTemp);
          //change array indexes so that return arrays are in the correct order.
          $index=0;
          foreach ($percentTemp As $ind => $val) {
                $wineNameSorted[$index] = $wineName[$ind];
                $countSorted[$index]=$count[$ind];
                 $countrySorted[$index]=$country[$ind];
                $percentSorted[$index]=$val;
                $index++;          
          }
        }

	//Not selected wines are what is left in the allWinesById array.
	//created sorted1 array, and then will merge the 2 arrays.
	
	$wineNameSorted1=array();
	$countrySorted1=array();
	$countSorted1=array();
	$percentSorted1=array();
	
	$i=0;
    if (count($allWinesById)>0) {
	      foreach ($allWinesById As $rowIndex => $rowValue) {
		     $row=$allWinesById[$rowIndex];	  	        
          	$wineNameSorted1[$i] = $this->fixName($row["Name"]);
          	$countryName='';
          	if (isset($countryArray[$row['countryName']])) {
                $countryName=$countryArray[$row['countryName']]['Name'];
          	}
          	$countrySorted1[$i] = $this->fixName($countryName);
        	    $countSorted1[$i] = 0;
        	    $percentSorted1[$i] = 0;
        	    $i++;
        }
    }
    
    //merge arrays - put not selected first.
    $wineNameSorted=array_merge($wineNameSorted1,$wineNameSorted);
    $countrySorted=array_merge($countrySorted1,$countrySorted);
    $countSorted=array_merge($countSorted1,$countSorted);
    $percentSorted=array_merge($percentSorted1,$percentSorted);
        
   //output final arrays to chart arrays. 
    $wine['values'] = $wineNameSorted;
	$country['values'] = $countrySorted;
	$count['values'] = $countSorted;
	$percent['values'] = $percentSorted;

	$chartArray = array ( 'wine' => $wine,
	                          'country' =>  $country,
	    					  'count' =>  $count,
	    					  'percent' =>  $percent,
	    					  'bevTypeName' => $bevTypeName);
	    
	if (!empty($_SESSION['chartValuesDetail'])) {
			unset($_SESSION['chartValuesDetail']);
	}	    
	$_SESSION['chartValuesDetail'] = $chartArray;	  	   	    
	    
	$this->_helper->layout()->disableLayout();
    $this->_helper->viewRenderer->setNoRender(true);
    echo json_encode($chartArray);
	    
	}
	
	
/**
* Function Name:  fixName
* Function Description:  removes translations if multiple languages are being used, only returning the english values (which are first in the delimited string)
* Parameters:    inputName - string with the name to be fixed.
* Return Values:   Returns the cleaned up name, which only has the first language if translations are being used.
*/

private function fixName($inputName) 
	{
	    $returnVal='';
	    if (isset($inputName)) {
	       $temp=split ("\^",$inputName);
	       $returnVal = $temp[0];
	   }
	return $returnVal;
	}

	
	//JL - 1/2015 - adding wines that are not in the global wine database to these numbers.  This is not required for any other category id type except for wines.
	//The current code is written extremely inefficently, but not rewriting at this time.  Just adding hook at end to add in additional wine bottle/glass prices.
	
	public function priceAction()
	{  

		DBUtilities::selectDatabase($_SESSION['selectDbname']);
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
		
		$dateIndexArray=array();

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
	            $i = 0;
	         
					$returnArray=DBUtilities::getTableData($sqlsel,false,true); 
                 if (count($returnArray) > 0) {
                      foreach ($returnArray As $rowIndex => $rowValue) {
                      $row=$returnArray[$rowIndex];	                 
	 
	            	if($cat_id[$p] == 0){
	            		$glpr = $row["glass_price"];
	            	}else{
	            		$botpr = $row["bottle_price"];
	            	}
	            	if(empty($botpr)){
	            		$bprice[$i] = "0";
	            	}else{
	                	$bprice[$i] = $row["bottle_price"];
	            	}
	            	if(empty($glpr)){
	            		$gprice[$i] = "0";
	            	}else{
	                	$gprice[$i] = $row["glass_price"];
	            	}
	            		 				
	                $t = explode('-',$dateRange[$q]);
					$dt = $t[1]."-".$t[2];
					//JL - 1/2015 - adding array to keep track of index in arrays of each date.  This will make it easier to add wine bottles/glasses to arrays by date.
					$dateIndexArray[$dt]=$q;
					
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
	            } //end of while rows loop
	                   
	            }  //end of if  numrows > 0
	            else 
	            {
	            	$j = 0;
	            	$t = explode('-',$dateRange[$q]);
					$dt = $t[1]."-".$t[2];
					//added so that custom wine values can be added easily.
					$dateIndexArray[$dt]=$q;
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
			}	//end of for loop
	   }

     //JL - 1/2015 - adding wines that are not included in the wine global database - bottle and glass prices
     //returns array[ByGlass][dt]=amount.
     //            array[ByBottle][dt]=amount.
     $customWineArray=$this->getCustomWinePrices($sqlstartDate,$sqlendDate,$clientID);
     
     //add custom wine values to wine glass and bottle values for chart.
    if (isset($customWineArray['ByGlass'])) {
     foreach ($customWineArray['ByGlass'] As $date=>$price) {
     		$t = explode('-',$date);
			$dt = $t[1]."-".$t[2];
			$index=$dateIndexArray[$dt];
			$winesglassinnerone[$index]["Y"]=$winesglassinnerone[$index]["Y"]+$price;
     }
  }
  
   if (isset($customWineArray['ByBottle'])) {
		foreach ($customWineArray['ByBottle'] As $date=>$price) {
     		$t = explode('-',$date);
			$dt = $t[1]."-".$t[2];
			$index=$dateIndexArray[$dt];
			$winesbottleinnerone[$index]["Y"]=$winesbottleinnerone[$index]["Y"]+$price;
     	}     
  }
  /* end of code to add prices for custom wines */
     
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
	
/* this function returns an array of wine prices by glass and by bottle for wines that are not  
 * in the global wine database. Returns:  returnArray[ByBottle][date]=totalPrice, returnArray[ByGlass][date]=totalPrice.
 */

 private function getCustomWinePrices($startDate,$endDate,$clientId) {
 
 	//we need the client smartcellar database - to get to their wines table.
   $smartcellarDB=$this->getSmartcellarDB($clientId);
   $returnPriceArray=array();
   
   //need to select all wines that are byglass, and all wines that are by bottle.
     if ($smartcellarDB != "") {
   	   //select all wine ids that are in the track table for the date and do not have global wine ids in the client wine table (which would mean that they aren't in the track_gdb table)
   	   //and are tracked by_glass.
      $sql="select t.by_glass, t.category_id, t.created_at, t.wine_id, sum(p.price) as totalPrice, w.id from track t, ".$smartcellarDB.".wines w,  ".$smartcellarDB.".size_to_price p where t.created_at between ";
   		$sql=$sql."'".$startDate."' and '".$endDate."' and w.id=t.wine_id and (w.gwdb_id is null or w.gwdb_id = 0) and t.by_glass=1 and t.client_id=".$clientId." and t.category_id=1 ";
   		$sql=$sql." and p.wine_id=w.id group by t.created_at";   	
   		
      /* get total sums for by glass wines */
      $returnArray=DBUtilities::getTableData($sql,false,true);
      foreach ($returnArray As $rowIndex=>$rowVal) {
			$row=$returnArray[$rowIndex];
			$createdAt=$row['created_at'];
			$totalPrice=$row['totalPrice'];
			$returnPriceArray['ByGlass'][$createdAt]=$totalPrice;
      }
   		
		//select all wine ids that are in the track table for the date and do not have global wine ids in the client wine table (which would mean that they aren't in the track_gdb table)
   	   //and are tracked by bottle
      $sql="select t.created_at, t.by_glass, t.category_id, t.wine_id,  w.id,sum(w.price) as totalPrice from track t, ".$smartcellarDB.".wines w where t.created_at between ";
   		$sql=$sql."'".$startDate."' and '".$endDate."' and w.id=t.wine_id and (w.gwdb_id is null or w.gwdb_id = 0) and t.by_glass is null and t.client_id=".$clientId." and t.category_id=1 ";
   		$sql=$sql." group by t.created_at";   	   		
   		
   		  /* get total sums for wines by bottle */
      $returnArray=DBUtilities::getTableData($sql,false,true);
      foreach ($returnArray As $rowIndex=>$rowVal) {
			$row=$returnArray[$rowIndex];
			$createdAt=$row['created_at'];
			$totalPrice=$row['totalPrice'];
			$returnPriceArray['ByBottle'][$createdAt]=$totalPrice;
      }
   }
   return $returnPriceArray;
}	
	
public function pricingpopupAction()
	{
	
		DBUtilities::selectDatabase($_SESSION['selectDbname']);
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
	        		
	
        $i = 0;
        
       $returnArray=DBUtilities::getTableData($sqlsel,false,true); 
       if (count($returnArray) > 0) {
            foreach ($returnArray As $rowIndex => $rowValue) {
            $row=$returnArray[$rowIndex];        
		        $nameinnerone[$i] = $row["Name"]; 
		        $countinnerone[$i] = $row["count"]; 
		        $priceinnerone[$i] = $row[$checkcol]; 
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
	    
		if (!empty($_SESSION['chartValues'])) {
			unset($_SESSION['chartValues']);
		}	    
		$_SESSION['chartValues'] = $allVisits1;	  	   	    
	    
	    //$allVisits2 = array('USA' => '200', 'France' => '300', 'Itly' => '100', 'Germany' => '400');
	    $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        echo json_encode($allVisits1);
		
	}
	
	public function getModule()
	{

		DBUtilities::selectDatabase($_SESSION["thisDbname"]);
		$sql = "select * from module where retired = '1'";
    	//die_r($sql);
    	$i=0;
     $returnArray=DBUtilities::getTableData($sql,false,true); 
      foreach ($returnArray As $rowIndex => $rowValue) {
        $row=$returnArray[$rowIndex];    	  	
	     $module = $row["id"];
    		$i++;
	    }
	
	    DBUtilities::selectDatabase($_SESSION['selectDbname']);
	    return $module;
	}


}
