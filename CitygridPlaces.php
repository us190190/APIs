<?php
/**
 * @access	Public
 * @version	1.0
 * @Author	Utkarsh Singh
 * @Details	This class is responsible for CitygridPlaces API Call
 **/
Class CitygridPlaces {
    
    public $publisherCode;
    public $apiSearchPlacesUrl;
    public $apiSearchPlacesDetailsUrl;
    public $searchPlacesArr;
    public $searchPlacesDetailsArr;
    public $insertedPublicIdsArr;
	public $flag;
    
    /*@params
     *
     *@return 
     *@constructor to initialize all the class data members
     */
    public function __construct(){
		ini_set('memory_limit', '4G');	
		$this->searchPlacesArr			= array();
		$this->searchPlacesDetailsArr		= array();
		$this->insertedPublicIdsArr		= array();
		$this->publisherCode			= '<use your API key here>';
		$this->apiSearchPlacesUrl		= "http://api.citygridmedia.com/content/places/v2/search/where?";
		$this->apiSearchPlacesDetailsUrl	= "http://api.citygridmedia.com/content/places/v2/detail?";
		$this->flag							=0;
    }
    
    /*@params
     *      
     *@function to reset the searchPlacesArr array
     */
    public function __resetSearchPlacesArr(){
		$this->searchPlacesArr			= array();		
    }
    
    /*@params
     *      
     *@function to reset the searchPlacesDetailsArr array
     */
    public function __resetSearchPlacesDetailsArr(){	
		$this->searchPlacesDetailsArr		= array();	
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
     *@function to get the flag count
     */
    public function __getFlagCount(){	
	    return $this->flag;
    }

    /*@params
     *      
     *@function to reset the insertedPublicIdsArr array
     */
    public function __resetInsertedPublicIdsArr(){	
		$this->insertedPublicIdsArr		= array();	
    }
    
    /*@params
     *      
     *@function to get the insertedPublicIdsArr array
     */
    public function getInsertedPublicIdsArr(){	
		return $this->insertedPublicIdsArr;
    }
    
    /*@params
     *what, type, where, page, rpp, sort, format, placement, has_offers, histograms, i
     *@return array
     *@Request for citygrid searchPlaces
     */
    public function searchPlaces($what=null, $type=null, $where=null, $page = 1, $rpp = 20, $sort = 'dist', $format='json', $placement=null, $has_offers=null, $histograms=null, $i=null,$CitygridPlacesObj,$apiRecordArray){
		try{
			$url 		= $this->apiSearchPlacesUrl;
			$publisherCode	= $this->publisherCode;
			$qStr		= "";
			$i			= null;
			
			if($what!=''){ $qStr .= "what=" . urlencode($what); }
			if($type!=''){ $qStr .= "&type=" . urlencode($type); }
			if($where!=''){ $qStr .= "&where=" . urlencode($where); }
			
			$qStr .= "&sort=" . urlencode($sort);
			$qStr .= "&page=" . urlencode($page);
			$qStr .= "&rpp=" . urlencode($rpp);
			
			if($placement!=''){ $qStr .= "&placement=" . urlencode($placement); }
			if($has_offers!=''){ $qStr .= "&has_offers=" . urlencode($has_offers); }
			if($histograms!=''){ $qStr .= "&histograms=" . urlencode($histograms); }
			if($i!=''){ $qStr .= "&i=" . urlencode($i); }
			
			$qStr .= "&format=" . $format;			
			$qStr .= "&publisher=" . $publisherCode;			
			$url .= $qStr;
			
			$curl_handle = curl_init();  
			curl_setopt($curl_handle, CURLOPT_URL, $url);  
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			$searchResponse = curl_exec($curl_handle); 
			curl_close($curl_handle);
			
			$searchResponse = json_decode($searchResponse);
			$searchResponse = $this->objectToArray($searchResponse);
			
			if(isset($searchResponse['results']['locations'])){
				for($i=0; $i<count($searchResponse['results']['locations']); $i++){
					set_time_limit(0);
					$this->flag++;
					if(isset($searchResponse['results']['locations'][$i]['id'])){
						$searchResponse['results']['locations'][$i]['api_name'] = CITY_GRID_PLACES;

						if(in_array($searchResponse['results']['locations'][$i]['public_id'],$apiRecordArray)){
							set_time_limit(0);
							$CitygridPlacesObj->update(array("public_id"=>$searchResponse['results']['locations'][$i]['public_id']),$searchResponse['results']['locations'][$i]);
						}else{
							set_time_limit(0);
							$this->searchPlacesArr[]=$searchResponse['results']['locations'][$i];
							if(count($this->searchPlacesArr)>=1000){
								$CitygridPlacesObj->batchInsert($this->searchPlacesArr);
								$this->searchPlacesArr =array();
							}
						}
					}						    
				}
			}
			
			$totalHits = (isset($searchResponse['results']['total_hits'])) ? $searchResponse['results']['total_hits'] : 0;
			
			if($this->flag==$totalHits){		    
				//return $this->searchPlacesArr;
				$CitygridPlacesObj->batchInsert($this->searchPlacesArr);
				echo "success";
			}
			else{
				$page++;
				set_time_limit(0);
				return $this->searchPlaces($what, $type, $where, $page, $rpp, $sort, $format, $placement, $has_offers, $histograms, $i,$CitygridPlacesObj,$apiRecordArray);
			}	    
		}catch(Exception $e){
			return 'Exception';
		}        
    }
    
    /*@params
     *id, id_type, phone, customer_only, all_results, review_count, placement, format, callback, i
     *@return array
     *@Request for citygrid search places detail as per id
     */
    public function placesDetail($id=array(), $id_type='cg', $phone=null, $customer_only=null, $all_results=null, $review_count=null, $placement=null, $format='json', $callback=null, $i=0,$cityGridPlacesDetails,$apiRecordArray,$apiRecordDetailsArray){		
		try{
			    
			$client_ip	= gethostbyname($_SERVER['HTTP_HOST']);
			$qStr	= "";
			$url	= $this->apiSearchPlacesDetailsUrl;	    
			
			if(count($id) && isset($id[count($this->searchPlacesDetailsArr)])){ $qStr .= "id=" . urlencode($id[count($this->searchPlacesDetailsArr)]); }
			if($id_type!=''){ $qStr .= "&id_type=" . urlencode($id_type); }
			if($phone!=''){ $qStr .= "&phone=" . urlencode($phone); }
			if($customer_only!=''){ $qStr .= "&customer_only=" . urlencode($customer_only); }
			
			if($all_results!=''){ $qStr .= "&all_results=" . urlencode($all_results); }
			if($review_count!=''){ $qStr .= "&review_count=" . urlencode($review_count); }
			if($placement!=''){ $qStr .= "&placement=" . urlencode($placement); }
			if($callback!=''){ $qStr .= "&callback=" . urlencode($callback); }
			//if($i!=''){ $qStr .= "&i=" . urlencode($i); }
			
			$qStr .= "&offer_count=0";
			$qStr .= "&format=" . $format;
			$qStr .= "&client_ip=" . $client_ip;
			$qStr .= "&publisher=" . $this->publisherCode;
			$url .= $qStr;
			
			$curl_handle = curl_init();  
			curl_setopt($curl_handle, CURLOPT_URL, $url);  
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			$searchResponse = curl_exec($curl_handle);
			curl_close($curl_handle);
			$searchResponse = json_decode($searchResponse);
			$searchResponse = $this->objectToArray($searchResponse);	
			
			$tmpResults = (isset($searchResponse['locations'][0])) ? $searchResponse['locations'][0] : array();
			if(sizeof($tmpResults)){
				$tmpResults['api_name'] = CITY_GRID_PLACES;
				$tmpResults['parent_listing_id'] = $apiRecordArray[$tmpResults['public_id']];
				if(in_array($tmpResults['public_id'],$apiRecordDetailsArray)){								
					$cityGridPlacesDetails->update(array("public_id"=>$tmpResults['public_id']),$tmpResults);
				}else{		
					$this->searchPlacesDetailsArr[]=$tmpResults;
				}			
			}
			if($i>=count($apiRecordArray)){	    
				return $this->searchPlacesDetailsArr;
			}
			else{	    
				$i++;			
				return $this->placesDetail($id, $id_type, $phone, $customer_only, $all_results, $review_count, $placement, $format, $callback, $i,$cityGridPlacesDetails,$apiRecordArray,$apiRecordDetailsArray);
			}
		}
		catch(Exception $e){
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
