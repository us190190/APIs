<?php
/**
 * @access	Public
 * @version	1.0
 * @Author	Utkarsh Singh
 * @Details	This class is responsible for NyTimesNews API Call
 **/
Class NyTimesNews {
    
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
		$this->apiBaseUrl	= "http://api.nytimes.com/svc/news/v3/content/all/all.json?";	
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
     *@Request for NyTimes news as per the resource specified
     */
    public function getNews($limit=20, $offset=0, $NewsModelObj, $existingNewsRecordsArray){
		try{
			$url 	= $this->apiBaseUrl;
			$apiKey	= $this->apiKey;
			$qStr	= "";
			
			$qStr .= "api-key=" . urlencode($apiKey);
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
			
			if(isset($searchResponse['results'])){
			for($i=0; $i<count($searchResponse['results']); $i++){
				$this->flag++;
				if(isset($searchResponse['results'][$i]['title'])){
					$searchResponse['results'][$i]['api_name'] = NYTIMES;
					if(in_array($searchResponse['results'][$i]['title'],$existingNewsRecordsArray)){
						set_time_limit(0);
						$NewsModelObj->update(array("title"=>$searchResponse['results'][$i]['title']),$searchResponse['results'][$i]);					
					}else{				    
						set_time_limit(0);
						$this->newsArr[]=$searchResponse['results'][$i];
						if(count($this->newsArr)>=1000){
							$NewsModelObj->batchInsert($this->newsArr);
							$this->newsArr =array();
						}
					}
				}		    
			}
			}
			
			$totalHits = (isset($searchResponse['num_results'])) ? $searchResponse['num_results'] : 0;
			
			if($this->flag==$totalHits){		    
				//return $this->newsArr;
				$NewsModelObj->batchInsert($this->newsArr);
				echo "success";
			}
			else{
				set_time_limit(0);
				$offset = $offset+$limit;
				return $this->getNews($limit, $offset, $NewsModelObj, $existingNewsRecordsArray);
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
