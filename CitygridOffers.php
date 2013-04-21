<?php
/**
 * @access	Public
 * @version	1.0
 * @Author	Utkarsh Singh
 * @Details	This class is responsible to CitygridOffers API Call
 **/
Class CitygridOffers {
	
	public $publisherCode;
	public $offerListApiUrl;
	public $offerDetailApiUrl;
	public $offerListingArray;
	public $offerDetailArray;
	
    /*@params
     *
     *@return 
     *@constructor to initialize all the class data members
     */
	public function __construct($viewObj=''){
		$this->offerListingArray	= array();
		$this->offerDetailArray		= array();
		$this->offerListApiUrl		= 'http://api.citygridmedia.com/content/offers/v2/search/where?';
		$this->publisherCode		= '<use your API key here>';
		$this->offerDetailApiUrl	= 'http://api.citygridmedia.com/content/offers/v2/detail?';
		ini_set('memory_limit', '2G');
	}

	/*@params
     *      
     *@function to reset the offerListingArray array
     */
    public function __resetOfferListingArray(){
		$this->offerListingArray = array();		
    }
    
    /*@params
     *      
     *@function to reset the offerDetailArray array
     */
    public function __resetOfferDetailArray(){	
		$this->offerDetailArray = array();	
    }
        
    /*@params
     *what, where, tag, rpp, start_date, expires_before, has_budget, sort, source, type, popularity, page, format, placement, has_offers, histograms, i, callback, exclude_tag
     *@return array
     *@Request for citygrid searchOffers
     */
	public function searchOffers($what=null,$where=null,$tag=null,$rpp=20,$start_date=null,$expires_before=null,$has_budget=null,$sort='dist',$source=null,$type=null,$popularity=null,$histograms=null,$page=1,$format='json',$callback=null,$placement=null,$exclude_tag=null,$i=null,$tag_op=null){

		set_time_limit(0);
		try{                		
		
			$url 		= $this->offerListApiUrl;
			$publisherCode	= $this->publisherCode;
			$qStr		= "";
			
			if($what!=''){ $qStr .= "what=" . urlencode($what); }
			if($where!=''){ $qStr .= "&where=" . urlencode($where); }
			if($tag!=''){ $qStr .= "&tag=" . urlencode($tag); }
			if($rpp!=''){ $qStr .= "&rpp=" . urlencode($rpp); }
			if($start_date!=''){ $qStr .= "&start_date=" . urlencode($start_date); }
			if($expires_before!=''){ $qStr .= "&expires_before=" . urlencode($expires_before); }
			if($has_budget!=''){ $qStr .= "&has_budget=" . urlencode($has_budget); }
			if($sort!=''){ $qStr .= "&sort=" . urlencode($sort); }
			if($source!=''){ $qStr .= "&source=" . urlencode($source); }
			if($type!=''){ $qStr .= "&type=" . urlencode($type); }
			if($popularity!=''){ $qStr .= "&popularity=" . urlencode($popularity); }
			if($histograms!=''){ $qStr .= "&histograms=" . urlencode($histograms); }
			if($page!=''){ $qStr .= "&page=" . urlencode($page); }
			if($format!=''){ $qStr .= "&format=" . urlencode($format); }
			if($callback!=''){ $qStr .= "&callback=" . urlencode($callback); }
			if($placement!=''){ $qStr .= "&placement=" . urlencode($placement); }
			if($exclude_tag!=''){ $qStr .= "&exclude_tag=" . urlencode($exclude_tag); }
			if($i!=''){ $qStr .= "&i=" . urlencode($i); }
			if($tag_op!=''){ $qStr .= "&tag_op=" . urlencode($tag_op); }
					
			$qStr .= "&publisher=" . $publisherCode;			
			$url .= $qStr;
			
			$curl_handle = curl_init();  
			curl_setopt($curl_handle, CURLOPT_URL, $url);  
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			$searchResponse = curl_exec($curl_handle);  
			curl_close($curl_handle);
			
			$searchResponse = json_decode($searchResponse);
			$searchResponse = $this->objectToArray($searchResponse);

			$totalHits = (isset($searchResponse['results']['total_hits'])) ? $searchResponse['results']['total_hits'] : 0;
			$tmpResults = (isset($searchResponse['results']['offers'])) ? $searchResponse['results']['offers'] : array();
			
			$results = array_merge($this->offerListingArray,$tmpResults);		
			$this->offerListingArray = $results;

			if(count($this->offerListingArray)>=$totalHits){
				return $this->offerListingArray;
			}
			else{
				$page++;
				unset($tmpResults);
				unset($results);
				set_time_limit(0);
				return $this->searchOffers($what,$where,$tag,$rpp,$start_date,$expires_before,$has_budget,$sort,$source,$type,$popularity,$histograms,$page,$format,$callback,$placement,$exclude_tag,$i,$tag_op);
			}
		}catch(Exception $e){
				return $e->getMessage();
		}
	}
	
	/* @params array of offerlist,format,callback,placement,location_id,i
	 * @return array of offer details. 
     * @Request for citygrid offer details
     */
	public function fetchOfferDetails($offerListingArray,$format='json',$callback=null,$placement=null,$location_id=null,$i=null){
		try{
			$url_part		= $this->offerDetailApiUrl;
			$publisherCode	= $this->publisherCode;

			$url_part .= "&publisher=" . $publisherCode;

			foreach($offerListingArray as $offer){
				set_time_limit(0);
				ob_start();
				$url = '';
				$qStr = "";
				$id = $offer['id'];
				$qStr.='&id='.$id;
				if($format!=''){ $qStr .= "&format=" . urlencode($format); }
				if($callback!=''){ $qStr .= "&callback=" . urlencode($callback); }
				if($placement!=''){ $qStr .= "&placement=" . urlencode($placement); }
				if($i!=''){ $qStr .= "&i=" . urlencode($i); }
							
				$url .= $url_part.$qStr;
				
				$curl_handle = curl_init();  
				curl_setopt($curl_handle, CURLOPT_URL, $url);  
				curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
				$response = curl_exec($curl_handle);  
				curl_close($curl_handle);
				
				$response = json_decode($response);
				$responseArray[]= $this->objectToArray($response);

				$results = array_merge($this->offerDetailArray,$responseArray);		
				$this->offerDetailArray = $results;
				unset($responseArray);
				unset($results);
				ob_end_flush();
			}
			// Return events details list array
			return $this->offerDetailArray;

		}catch(Exception $e){
				return $e->getMessage();
		}
	}
	
	/* @params object
	 * @return array. 
     * @convert object into array.
     */
	function objectToArray($object){
		return @json_decode(@json_encode($object),1); 
	}
}
