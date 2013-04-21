<?php
/**
 * @access	Public
 * @version	1.0
 * @Author	Utkarsh Singh
 * @Details	This class is responsible to wunderground Weathers API Call
 **/
Class WundergroundWeather{
	
	private $app_key;
	private $apiUrl;
	private $weatherArray;
	private $features;
	
	/**
	* Constructor will set protected variables of class
	*/
	public function __construct($viewObj=''){
		$this->weatherArray	= array();
		$this->app_key	= '<use your API key here>';
		$this->apiUrl	= 'http://api.wunderground.com/';
		ini_set('memory_limit', '2G');
	}
    
	/*@params
     *      
     *@function to reset the weatherArray array
     */
    public function __resetWeatherArray(){	
		$this->weatherArray	= array();	
    }

    /*@params array
     *@return array
     *@Request for get weather details
     */
	public function fetchWeather($features,$zipcode,$format){
		set_time_limit(0);
		try{                		
			$url 		= $this->apiUrl;
			$app_key	= $this->app_key;
			$qStr		= "";
			
			$qStr.='api/'.$app_key;
			foreach($features as $feature){
					$qStr.='/'.$feature;
			}

			$qStr.="/q/".$zipcode;
			$qStr.=".".$format;
			$url .= $qStr;
			$curl_handle = curl_init();  
			curl_setopt($curl_handle, CURLOPT_URL, $url);  
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);                   
			$searchResponse = curl_exec($curl_handle);  
			curl_close($curl_handle);

			$searchResponse = json_decode($searchResponse);

			//convert object to array
			$searchResponse =  $this->objectToArray($searchResponse);

			unset($searchResponse['response']);
			$this->weatherArray = $searchResponse;

			return $this->weatherArray;

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
