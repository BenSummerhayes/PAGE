<?php
//////////////////////////////////////////////////////////////////////////////////////////////
//																							//
//	THIS FILE CONTAINS SOME CLASSES THAT ARE NECCESSARY FOR YOUR API'S TO WORK 				//
//																							//
//	ONLY EDIT THIS FILE IF YOU KNOW WHAT YOU ARE DOING AND AFTER YOU HAVE MADE A BACK UP!	//
//																							//
//////////////////////////////////////////////////////////////////////////////////////////////

// if we are in dev mode then don't bother loading this
if(isset($DevMOde)){

	$apiKeyCheckReturnArray=array();
	$securityTokenCheckReturnArray=array();
	return;
}

// prevent this from happening
if($connectionRequest==1 && $api_db_connection_list=="0"){

	$JsonReturnArray['error']='ERROR! It would appear you are trying to connect to a database using the variable variable "$$db_connection_name"...this ONLY works if you select an API database from the dropdown selection menu on the API editor (PAGE) editor. Either Select an API database or remove the Database Connection code';

	echo json_encode($JsonReturnArray);

	die();	

}

// this class checks the Rest API key, it also returns the values of anything related to it in the database
class ApiKeyCheck{

	//declare these properties
	public $api_key_required_checkbox = '';
	public $api_key_db_connection_list = '';
	public $ReturnArray = array('apiKeyCheckTableRowInfo'=>'');
	public $api_key = '';
	public $table_name = 'api_keys';
	public $timestamp ='';

	// this method runs through all the neccessary checks
	public function checks(){

		//check we have a database selected
		if($this->api_key_db_connection_list=='0'){

			//add this error message and result code to the json return
			$this->ReturnArray['apiKeyCheckMsg'] = "409 ERROR! API KEY check is required but an API KEY Database has not been selected!";
			$this->ReturnArray['apiKeyCheckRsltCode'] = 0;

		//check the connection file exists
		}elseif(!file_exists("./db_connect/$this->api_key_db_connection_list")){

			//add this error message and result code to the json return
			$this->ReturnArray['apiKeyCheckMsg'] = "404 ERROR! API KEY connection script does not exist!";
			$this->ReturnArray['apiKeyCheckRsltCode'] = 0;

		//check we have an api key and get its value
		}elseif($this->api_key==''){

			//add this error message and result code to the json return
			$this->ReturnArray['apiKeyCheckMsg'] = "409 ERROR! API KEY check is required but the api_key variable has NOT been set or sent!";
			$this->ReturnArray['apiKeyCheckRsltCode'] = 0;

		}else{

			//load the DB connection
			require("./db_connect/$this->api_key_db_connection_list");

			// if the is a MYSQL db
			if($db_type=='mysql'){

				// get all the tables columns
				$this->query = $$db_connection_name->prepare("DESCRIBE api_keys");
				$this->query->execute();
				$this->tableCols = $this->query->fetchAll(PDO::FETCH_COLUMN);

			//MSSQL ********* this has NOT been tested so I doubt very much of it works!!!
			}else{

				// prepare a statement
				$this->query = $$db_connection_name->prepare("exec sp_columns @table_name = :table_name");

				// execute the statement with $this->table_name as param
				$this->query->execute(array('table_name' => $this->table_name));

				// fetch results
				$selectList = $this->query->fetchAll($sql);

			}

			// turn selectList it into a list
			$this->selectList =  implode(",", $this->tableCols);

			// this is where we connect to the database and check the api key is correct and grab all other cell info on the same row
			$this->query = $$db_connection_name->query("SELECT $this->selectList FROM $this->table_name WHERE api_key = '$this->api_key'");
			$this->row = $this->query->fetch(PDO::FETCH_ASSOC);

			//did we find a api_id?!
			if($this->row['api_id']==''){

				//add this error message and result code to the json return
				$this->ReturnArray['apiKeyCheckMsg'] = "403 ERROR! Invalid API KEY!";
				$this->ReturnArray['apiKeyCheckRsltCode'] = 0;

			//check it's not void
			}elseif($this->row['void']=='1' OR $this->row['expiry_date'] < time()){

				//add this error message and result code to the json return
				$this->ReturnArray['apiKeyCheckMsg'] = "403 ERROR! API KEY is VOID and or EXPIRED!";
				$this->ReturnArray['apiKeyCheckRsltCode'] = 0;

			}else{

				//All is good! Add this lot to the json return
				$this->ReturnArray['apiKeyCheckMsg'] = "API KEY check correct!";
				$this->ReturnArray['apiKeyCheckRsltCode'] = 1;
				$this->ReturnArray['apiKeyCheckTableRowInfo'] = $this->row;

				// update the 'last_used' column
				$$db_connection_name->exec("UPDATE $this->table_name SET last_used='$this->timestamp' WHERE api_key = '$this->api_key'");

			}

		}

		// return the result
		return $this->ReturnArray;
	}

}//end of API key class

// this class checks (and regenerates) the security token, it also returns the values of anything related to it in the database
class SecurityTokenCheck{

	//declare these properties
	public $api_sec_token_db_connection_list="";
	public $security_token_regen_checkbox="";
	public $ReturnArray = array('secTokenTableRowInfo'=>'', 'newSecToken'=>'');
	public $security_token = '';
	public $table_name = 'security_tokens';
	public $timestamp ='';
	public $new_token = '';

	public function RegenerateSecurityToken(){

		//regenerate the token
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$new_token = '';
		for ($i = 0; $i < 20; $i++){
			$new_token .= $characters[rand(0, strlen($characters) - 1)];
		}

		return $new_token;
	}

	// this method runs through all the neccessary checks
	public function checks(){

		//check we have a database selected
		if($this->api_sec_token_db_connection_list=='0'){

			//add this error message and result code to the json return
			$this->ReturnArray['secTokenCheckMsg'] = "409 ERROR! Security token check is required but a Security token Database has not been selected!";
			$this->ReturnArray['secTokenCheckRsltCode'] = 0;

		//check the connection file exists
		}elseif(!file_exists("./db_connect/$this->api_sec_token_db_connection_list")){

			//add this error message and result code to the json return
			$this->ReturnArray['secTokenCheckMsg'] = "403 ERROR! Security token connection script does not exist!";
			$this->ReturnArray['secTokenCheckRsltCode'] = 0;

		//check we have an Security token and get its value
		}elseif($this->security_token==''){

			//add this error message and result code to the json return
			$this->ReturnArray['secTokenCheckMsg'] = "409 ERROR! Security token check is required but the security_token variable has NOT been set or sent!";
			$this->ReturnArray['secTokenCheckRsltCode'] = 0;

		}else{

			//load the DB connection
			require("./db_connect/$this->api_sec_token_db_connection_list");

			// if the is a MYSQL db
			if($db_type=='mysql'){

				// get all the tables columns
				$this->query = $$db_connection_name->prepare("DESCRIBE security_tokens");
				$this->query->execute();
				$this->tableCols = $this->query->fetchAll(PDO::FETCH_COLUMN);

			//MSSQL ********* this has NOT been tested so I doubt very much of it works!!!
			}else{

				// prepare a statement
				$this->query = $$db_connection_name->prepare("exec sp_columns @table_name = :table_name");

				// execute the statement with $this->table_name as param
				$this->query->execute(array('table_name' => $this->table_name));

				// fetch results
				$selectList = $this->query->fetchAll($sql);

			}

			// turn selectList it into a list
			$this->selectList =  implode(",", $this->tableCols);

			// this is where we connect to the database and check the Security token is correct and grab all other cell info on the same row
			$this->query = $$db_connection_name->query("SELECT $this->selectList FROM $this->table_name WHERE token = '$this->security_token'");
			$this->row = $this->query->fetch(PDO::FETCH_ASSOC);

			//did we find a username?!
			if($this->row['username']==''){

				//add this error message and result code to the json return
				$this->ReturnArray['secTokenCheckMsg'] = "403 ERROR! Invalid Security Token!";
				$this->ReturnArray['secTokenCheckRsltCode'] = 0;

			//check it's not expired
			}elseif($this->row['expiry_date'] < time()){

				// DELETE IT FROM THE TABLE!!!!!!!!!!!!!!!!!!!!!!!!!!!
				$$db_connection_name->exec("DELETE FROM $this->table_name WHERE token = '$this->security_token'");

				//add this error message and result code to the json return
				$this->ReturnArray['secTokenCheckMsg'] = "403 ERROR! Security Token has EXPIRED!";
				$this->ReturnArray['secTokenCheckRsltCode'] = 0;

			}else{

				// if we need to regenerate the token
				if($this->security_token_regen_checkbox=='1'){

					// call this method
					$this->new_token = $this->row['username'].'_'.$this->RegenerateSecurityToken();
					$this->ReturnArray['newSecToken'] = $this->new_token;

					// update the db
					$$db_connection_name->exec("UPDATE $this->table_name SET token='$this->new_token', date_created = '$this->timestamp' WHERE token = '$this->security_token'");

				}

				//All is good! Add this lot to the json return
				$this->ReturnArray['secTokenCheckMsg'] = "Security token check correct!";
				$this->ReturnArray['secTokenCheckRsltCode'] = 1;
				$this->ReturnArray['secTokenTableRowInfo'] = $this->row;

				// update the 'last_used' column
				$$db_connection_name->exec("UPDATE $this->table_name SET last_used='$this->timestamp' WHERE token = '$this->security_token'");

			}

		}

		// return the result
		return $this->ReturnArray;
	}

}

// if we are in dev mode then don't bother doing the API checks
if(isset($DevMOde)){

	$apiKeyCheckReturnArray=array();

//API KEY checks
}elseif($api_key_required_checkbox=='1'){

	// create an instance of this class
	$apiKeyCheck=new ApiKeyCheck;

	//set these properties
	$apiKeyCheck->api_key_required_checkbox=$api_key_required_checkbox;
	$apiKeyCheck->api_key_db_connection_list=$api_key_db_connection_list;
	$apiKeyCheck->timestamp=time();
	$apiKeyCheck->api_key=$api_key;

	// call this method to validate the key
	$apiKeyCheckReturnArray = $apiKeyCheck->checks();

	// deal with the result (zero means error!)
	if($apiKeyCheckReturnArray['apiKeyCheckRsltCode']==0){

		// reset the return array
		$JsonReturnArray = array();

		// return this and stop the rest of the script from running
		$JsonReturnArray['error']=$apiKeyCheckReturnArray['apiKeyCheckMsg'];

		echo json_encode($JsonReturnArray);
		
		die();

	}


}else{

	// declare this just incase you need it
	$apiKeyCheckReturnArray['apiKeyCheckMsg']='API Key Check Not Required, results code defaulted to 1 (check correct)';
	$apiKeyCheckReturnArray['apiKeyCheckRsltCode']=1;
	
}

// if we are in dev mode then don't bother doing the SECURITY TOKEN checks
if(isset($DevMOde)){

	$securityTokenCheckReturnArray=array();

//security token checks
}elseif($security_token_req_checkbox=="1"){

	//create an instance of this class
	$securityTokenCheck = new SecurityTokenCheck;

	//set these properties
	$securityTokenCheck->api_sec_token_db_connection_list=$api_sec_token_db_connection_list;
	$securityTokenCheck->security_token_regen_checkbox=$security_token_regen_checkbox;
	$securityTokenCheck->security_token = $sec_token;
	$securityTokenCheck->timestamp =time();

	// call this method to validate the token
	$securityTokenCheckReturnArray = $securityTokenCheck->checks();

	if($security_token_regen_checkbox==1){

		$JsonReturnArray['newSecToken']=$securityTokenCheckReturnArray['newSecToken'];
	}

	// deal with the result (zero means error!)
	if($securityTokenCheckReturnArray['secTokenCheckRsltCode']==0){

		// return this
		$JsonReturnArray['error']=$securityTokenCheckReturnArray['secTokenCheckMsg'];

		echo json_encode($JsonReturnArray);
		
		die();

	}

}else{

	// declare this just incase you need it
	$securityTokenCheckReturnArray['secTokenCheckMsg']='Security Token Check Not Required, results code defaulted to 1 (check correct)';
	$securityTokenCheckReturnArray['secTokenCheckRsltCode']=1;	
}

if($api_db_connection_list<>"0"){

	//connect to the database
	if(file_exists("./db_connect/$api_db_connection_list")){

		// load this file
		require("./db_connect/$api_db_connection_list");

	}else{

		// return this
		$JsonReturnArray['error']="database connection file missing ($api_db_connection_list)";

		echo json_encode($JsonReturnArray);
		
		die();

	}

}


?>



