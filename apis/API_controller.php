<?php //this script controls all API requests
// die('here');
class ApiController{
  
  	//this stops an API script from being directly requested/loaded
	public $API_Requested = true;

	// go straight into this
	public function __construct() {

		// set these properties
	    $this->Request_Method = $_SERVER['REQUEST_METHOD'];
	    $this->Requested_Url = explode('?', $_SERVER['REQUEST_URI']);
	    $this->Requested_Url_Components=array_reverse(explode('/', $this->Requested_Url[0]));
	
		//set the version number
		if(isset($this->Requested_Url_Components[1])){

		  $this->version_number=$this->Requested_Url_Components[1];

		}else{

		  $this->version_number='version number missing';

		}

		//set the API name
		if(isset($this->Requested_Url_Components[0])){

		  $this->Requested_API=$this->Requested_Url_Components[0];

		}else{

		  $this->Requested_API='API name missing';

		}

		//now load it!
		$this->loadAPI();

	}

	// this method loads the API
	public function loadAPI(){
	  
		//load the required API (if it exists)
		if(file_exists("$this->version_number/$this->Requested_API.php")){

			// set this so we do not get our "404 file not found!" error message (we know that this is a genuine API call this handler)
			$API_Requested=$this->API_Requested;

			// now we load the required API
			include("$this->version_number/$this->Requested_API.php");

			// return the API result
			echo json_encode($JsonReturnArray);
			return;

		}else{

		  $JsonReturnArray = array("error 400","invalid api name and or version number (Version: $this->version_number. API Requested: $this->Requested_API)");

		  //return the error
		  echo json_encode($JsonReturnArray);

		  die();
		  
		}

	}

}

//create an instance of this
$API = new ApiController;


?>