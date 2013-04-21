<?php
/**
 * @access	Public
 * @version	1.0
 * @Author	Utkarsh Singh
 * @Details	This class is responsible for EspnNews API Call
 **/
Class EspnNews {
    
    public $apiKey;
    public $apiBaseUrl;    
    public $newsArr;
    public $flag;
    
    /*@params
     *
     *@return 
     *@constructor to initialize all the class data members
     */
    public function __construct(){
		$this->newsArr		= array();
		$this->flag		= 0;
		$this->apiKey		= '<use your API key here>';
		$this->apiBaseUrl	= "http://api.espn.com/v1/";	
    }
    
    /*@params
     *      
     *@function to reset the flag
     */
    public function __resetFlag(){	
		$this->flag		= 0;
    }
    
    /*@params
     *      
     *@function to reset the newsArr array
     */
    public function __resetNewsArr(){
		$this->newsArr		= array();		
    }
    
    /*@params
     *$resource
     *@return array
     *@Request for espn news as per the resource specified
     */
    public function getNews($resource='sports', $limit=50, $offset=0, $SportsModelObj, $existingSportsRecordsArray){
		try{
			$url 	= $this->apiBaseUrl.$resource."/news?";
			$apiKey	= $this->apiKey;
			$qStr	= "";
			
			$qStr .= "apiKey=" . urlencode($apiKey);
			if($limit!=''){ $qStr .= "&limit=" . urlencode($limit); }
			if($offset!=''){ $qStr .= "&offset=" . urlencode($offset); }
			$url .= $qStr;
			
			$curl_handle = curl_init();  
			curl_setopt($curl_handle, CURLOPT_URL, $url);  
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			$searchResponse = curl_exec($curl_handle); 
			curl_close($curl_handle);
			
			$searchResponse = json_decode($searchResponse);
			$searchResponse = $this->objectToArray($searchResponse);
			
			if(isset($searchResponse['headlines'])){
			for($i=0; $i<count($searchResponse['headlines']); $i++){
				$this->flag++;
				if(isset($searchResponse['headlines'][$i]['id'])){
					$searchResponse['headlines'][$i]['api_name'] = ESPN_NEWS;
					$searchResponse['headlines'][$i]['news_id'] = $searchResponse['headlines'][$i]['id'];
					if(in_array($searchResponse['headlines'][$i]['news_id'],$existingSportsRecordsArray)){
						set_time_limit(0);
						$SportsModelObj->update(array("news_id"=>$searchResponse['headlines'][$i]['news_id']),$searchResponse['headlines'][$i]);					
					}else{				    
						set_time_limit(0);
						$this->newsArr[]=$searchResponse['headlines'][$i];
						if(count($this->newsArr)>=1000){
							$SportsModelObj->batchInsert($this->newsArr);
							$this->newsArr =array();
						}
					}
				}		    
			}
			}
			
			$totalHits = (isset($searchResponse['resultsCount'])) ? $searchResponse['resultsCount'] : 0;	    
			
			if($this->flag==$totalHits){		    
				//return $this->newsArr;
				$SportsModelObj->batchInsert($this->newsArr);
				echo "success";
			}
			else{
				set_time_limit(0);
				$offset = $offset+$limit;
				return $this->getNews($resource, $limit, $offset, $SportsModelObj, $existingSportsRecordsArray);
			}	    
		}catch(Exception $e){
			return 'Exception';
		}        
    }    

    /*@params
     *object
     *@return array
     *@convert object into array.
     */
    function objectToArray($object){
		return @json_decode(@json_encode($object),1); 
    }
}
