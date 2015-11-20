
		<?php //DO NOT ADD or REMOVE anything within these php tags!!

		// this stops the script from being directly loaded
		if(!isset($API_Requested)){
			
			die("<h2>404 file not found</h2>");

		}

		//user defined variable(s)
		
		if(isset($_GET["test_var"])){$test_var=$_GET["test_var"];}else{$test_var=null;}
		
		//system defined variables
		$security_token_regen_checkbox=""; 
		$api_key_required_checkbox=""; 
		$security_token_req_checkbox=""; 
		$Method='GET';
		$API_Version='v1'; 
		$API_Name='basic_api_demo'; 
		$API_Description='This is a basic example of an API created using this very tool.'; 
		$api_db_connection_list="0"; 
		$db_connection_list = "0"; 
		$api_key_db_connection_list = "0"; 
		$api_sec_token_db_connection_list = "0";
		$connectionRequest="0";
		
		//declare this return array
		$JsonReturnArray = array();

		//dummy data for when in development mode
		$dummy_data_array = array('test_var'=>'here_is_some_dummy_data!');

		//load these classes
		require_once('./PAGE_CLASSES.php');

		// if we are in dev mode (using the API editior) we have no need to go any further
		if(isset($DevMOde)){

			return;
		}

		?>
		
		<?php //WRITE YOUR OWN CODE AFTER this PHP tag

		//welcome to your new API! REMEMBER that anything you want this API to return needs to be added to the "$JsonReturnArray" array
		$JsonReturnArray['welcome_message']='welcome to your new API! REMEMBER that anything you want this API to return needs to be added to the $JsonReturnArray array';

		//If you are using API Keys and Security Tokens then the following two arrays hold all the information that is generated after successful checks (Add them to the "$JsonReturnArray" if you want to view them): $securityTokenCheckReturnArray & $apiKeyCheckReturnArray
		$JsonReturnArray['SecTokenChecks'] = $securityTokenCheckReturnArray;
		$JsonReturnArray['APIKeyChecks'] = $apiKeyCheckReturnArray;

		// here is your test variable!
		$JsonReturnArray['test_var']=$test_var;

		//Here are some basic MYSQL query examples using PDO (Not yet looked into MSSQL). There are plenty of MYSQL PDO related tutorials on the internet:

		//NOTE: to make things easier use the "variable variable" (two dollar signs) "$$db_connection_name" to refer to your DB connection instance (Obviously ONLY if you are using a selected DB Connection Script from the dropdown on the PAGE editor)

		//Select example
		//$passwordCheck = $$db_connection_name->prepare("SELECT username, email FROM demo_table WHERE password=:password");
		//$passwordCheck->execute(array(':password' => 'qwerty1234'));
		//$rows = $passwordCheck->fetch(PDO::FETCH_ASSOC);
		
		// add it to the results return array (if you want to!)
		//$JsonReturnArray['userName']=$rows['username'];
		//$JsonReturnArray['emailAddress']=$rows['email'];

		//Delete example
		//$deleteDave = $$db_connection_name->prepare("DELETE FROM table WHERE id=:id");
		//$deleteDave->bindValue(':id', 'dave', PDO::PARAM_STR);
		//$deleteDave->execute();
		//$affected_rows = $deleteDave->rowCount();

		// add it to the results return array (if you want to!)
		//$JsonReturnArray['deletionCount']=$affected_rows;

		//Insert example
		//$insertStuff = $$db_connection_name->prepare("INSERT INTO table(field1,field2,field3,field4,field5) VALUES(:field1,:field2,:field3,:field4,:field5)");
		//$insertStuff->execute(array(':field1' => $field1, ':field2' => $field2, ':field3' => $field3, ':field4' => $field4, ':field5' => $field5));
		//$affected_rows = $insertStuff->rowCount();

		// add it to the results return array (if you want to!)
		//$JsonReturnArray['insertCount']=$affected_rows;

		//Update example
		//$updateStuff = $$db_connection_name->prepare("UPDATE table SET name=? WHERE id=?");
		//$updateStuff->execute(array($name, $id));
		//$affected_rows = $updateStuff->rowCount();

		// add it to the results return array (if you want to!)
		//$JsonReturnArray['updateCount']=$affected_rows;



		//happy coding ;)