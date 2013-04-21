<?php
/**
 * @access	Public
 * @version	1.0
 * @Author	Utkarsh Singh
 * @Details	This class is responsible for CitygridReviews API Call
 **/
Class CitygridReviews {
    
    public $publisherCode;
    public $apiSearchReviewsUrl;    
    public $searchReviewsArr;
    public $flag;
    
    /*@params
     *
     *@return 
     *@constructor to initialize all the class data members
     */
    public function __construct(){
		$this->searchReviewsArr			= array();	
		$this->flag				= 0;
		$this->publisherCode			= '<use your API key here>';
		$this->apiSearchReviewsUrl		= "http://api.citygridmedia.com/content/reviews/v2/search/where?";	
    }
    
    /*@params
     *      
     *@function to reset the flag
     */
    public function __resetFlag(){	
		$this->flag				= 0;
    }
    
    /*@params
     *      
     *@function to reset the searchReviewsArr array
     */
    public function __resetSearchReviewsArr(){
		$this->searchReviewsArr			= array();		
    }
    
    /*@params
     *$what, $tag, $where, $listing_id, $customer_only, $rating, $days, $page, $rpp, $format, $placement, $callback, $i, $review_type, $sort
     *@return array
     *@Request for citygrid searchReviews
     */
    public function searchReviews($what=null, $tag=null, $where=null, $listing_id=null, $customer_only=null, $rating=null, $days=null, $page=1, $rpp=20, $format='json', $placement=null, $callback=null, $i=null, $review_type=null, $sort=null, $CitygridReviewsModelObj, $existingReviewsRecordsArray){
		try{
			$url 		= $this->apiSearchReviewsUrl;
			$publisherCode	= $this->publisherCode;
			$qStr		= "";	    
			
			if($what!=''){ $qStr .= "what=" . urlencode($what); }
			if($tag!=''){ $qStr .= "&tag=" . urlencode($tag); }
			if($where!=''){ $qStr .= "&where=" . urlencode($where); }
			if($listing_id!=''){ $qStr .= "&listing_id=" . urlencode($listing_id); }
			if($customer_only!=''){ $qStr .= "&customer_only=" . urlencode($customer_only); }
			if($rating!=''){ $qStr .= "&rating=" . urlencode($rating); }
			if($days!=''){ $qStr .= "&days=" . urlencode($days); }
			if($review_type!=''){ $qStr .= "&review_type=" . urlencode($review_type); }
			
			$qStr .= "&sort=" . urlencode($sort);
			$qStr .= "&page=" . urlencode($page);
			$qStr .= "&rpp=" . urlencode($rpp);	    
			
			if($placement!=''){ $qStr .= "&placement=" . urlencode($placement); }
			if($callback!=''){ $qStr .= "&callback=" . urlencode($callback); }	    
			if($i!=''){ $qStr .= "&i=" . urlencode($i); }
			
			$qStr .= "&format=" . $format;			
			$qStr .= "&publisher=" . $publisherCode;			
			$url .= $qStr;
			
			//echo "URL : ".$url."<br/>";
			
			$curl_handle = curl_init();  
			curl_setopt($curl_handle, CURLOPT_URL, $url);  
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			$searchResponse = curl_exec($curl_handle); 
			curl_close($curl_handle);
			
			$searchResponse = json_decode($searchResponse);
			$searchResponse = $this->objectToArray($searchResponse);
			
			if(isset($searchResponse['results']['reviews'])){
			for($i=0; $i<count($searchResponse['results']['reviews']); $i++){
				$this->flag++;
				if(isset($searchResponse['results']['reviews'][$i]['review_id'])){
					$searchResponse['results']['reviews'][$i]['api_name'] = CITY_GRID_REVIEWS;
					$searchResponse['results']['reviews'][$i]['listing_id'] = $listing_id;
					if(in_array($searchResponse['results']['reviews'][$i]['review_id'],$existingReviewsRecordsArray)){
						set_time_limit(0);
						$CitygridReviewsModelObj->update(array("review_id"=>$searchResponse['results']['reviews'][$i]['review_id']),$searchResponse['results']['reviews'][$i]);					
					}else{				    
						set_time_limit(0);
						$this->searchReviewsArr[]=$searchResponse['results']['reviews'][$i];
						if(count($this->searchReviewsArr)>=1000){
							$CitygridReviewsModelObj->batchInsert($this->searchReviewsArr);
							$this->searchReviewsArr =array();
						}
					}
				}		    
			}
			}
			
			$totalHits = (isset($searchResponse['results']['total_hits'])) ? $searchResponse['results']['total_hits'] : 0;	    
			
			if($this->flag==$totalHits){		    
				//return $this->searchReviewsArr;
				$CitygridReviewsModelObj->batchInsert($this->searchReviewsArr);
				echo "success";
			}
			else{
				$page++;
				set_time_limit(0);
				return $this->searchReviews($what, $tag, $where, $listing_id, $customer_only, $rating, $days, $page, $rpp, $format, $placement, $callback, $i, $review_type, $sort, $CitygridReviewsModelObj, $existingReviewsRecordsArray);
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
