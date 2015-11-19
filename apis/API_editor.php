<?php

//UN-COMMENT the die statement below or DELETE this file from your server if this is on a PRODUCTION server!!!!!!!!!!!!!!!!!!!!!!
// die('<h2>404 file not found</h2>');

//we only use a session for the API edit area log in system
session_start();

// API edit area log in password...change this to what you want
$apiEditAreaPassword = "qq";

// a VERY basic log in script (DO NOT rely on this to stop people from using this edit area) ... this is more of a deterent than anything
if(isset($_POST['pageEditAreaPW'])){

	// check the password is correct
	if($_POST['pageEditAreaPW']==$apiEditAreaPassword){

		// set this 
		$_SESSION['loggedIn']=1445; 

	}else{

		// here we tell them they have got the wrong password
		echo '<h5 style="color:red;">Wrong Password</h5>';
	}

}

// check we are logged in
if(!isset($_SESSION['loggedIn'])){

	// display a simple log in form
	echo'<form action="API_editor.php" method="post"><input type="password" placeholder="password" name="pageEditAreaPW"><input type="submit" value="submit" name="pageEditAreaSB"></form>';

	// destroy this
	session_destroy();

	// kill this!
	die();
}

//declare these variables
$error_messages = 'DO NOT allow access to this file on your PRODUCTION server!!';
$API_Requested = TRUE;
$DevMOde = TRUE;

// this class deals with the creation, editing and deletion of database connections
class DBConnections{

	public $content = '';
	public $connection_script = '';
	public $db_type = '';
	public $db_connection_name = '';
	public $db_host = '';
	public $db_name = '';
	public $db_username = '';
	public $db_password = '';
	public $connections = '';
	public $select_list_id='';
	public $select_list_connection_form_value='0';
	public $select_list_label='';
	public $db_creator_option='0';
	public $db_list = '';
	public $create_option='';
	public $db_connection_list='';
	public $error_messages = '';
	public $return_message = '';

	//this method gets the list of connection scripts in the db folder
	public function __construct(){

		$this->connections= scandir('./db_connect/');

	}

	//this method generates a connection script
	public function con_script(){

		// work out which connection script to use
		if($this->db_type=='mysql'){

			$this->connection_script = 'try{$'.$this->db_connection_name.' = new PDO("mysql:host=$db_host;dbname=$db_name", $db_username, $db_password); $'.$this->db_connection_name.'->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); $error_messages = "<span style=\"color:green\">Connected successfully!!!</span>";} catch(PDOException $e){$error_messages = "Connection failed: " . $e->getMessage();}?>';

		}else{

			$this->connection_script = '$error_messages="No msql connection script created yet";';

		}

		// here is what will be in the script
		$this->content = '<?php $db_connection_name="'.$this->db_connection_name.'"; $db_name="'.$this->db_name.'"; $db_username="'.$this->db_username.'"; $db_password="'.$this->db_password.'"; $db_host="'.$this->db_host.'"; $db_type="'.$this->db_type.'"; ';

	}

	//this method writes the connection script to file
	public function write_db_connection_to_file(){

		//add / update it to the db_connect folder
		$fp = fopen("./db_connect/$this->db_connection_name.php","wb");
		fwrite($fp,$this->content.$this->connection_script);
		fclose($fp);

	}

	//this function creates a database selection list
	public function db_connection_select_list(){

		foreach ($this->connections as $value) {

			//check it has a proper file name
			$check = explode('.', $value);

			if($check[0]=='' OR $check[1]<>'php'){

				continue;

			}

			//select the chosen option
			if($value==$this->select_list_connection_form_value){

				$selected='selected';

			}else{

				$selected='';
			}

			//build the list
			$this->db_list = $this->db_list . "<option value=\"$value\" $selected>$value</option>";

		}	

		//sort the selected stuff out
		if($this->select_list_connection_form_value=='1'){

			$selected='selected';

		}

		//allow the option to create a DB connection
		if($this->db_creator_option=='1'){

			$this->create_option = "<option value=\"1\" $selected>Create a Connection</option>";

		}

		// build the actual select list
		$this->db_list = " 
			<select id=\"$this->select_list_id\" name=\"$this->select_list_id\" style=\"width:200px\" title=\"$this->select_list_label\" onchange=\"this.form.submit()\">
				<option value=\"0\">Select</option>
				$this->create_option
				$this->db_list
			</select>";

			//return the select list
			return $this->db_list;

	}

	public function DeleteDBConnection(){

		//check it exists
		if(file_exists("./db_connect/$this->db_connection_list")){

			//delete it
			unlink("./db_connect/$this->db_connection_list");

			//return this message
			$this->return_message ="$this->db_connection_list Connection DELETED!!<br><br><a href=\"API_editor.php\">Please Reload this Page</a>";

		}else{

			//output this message instead
			$this->return_message ="$this->db_connection_list was NOT Deleted as the file does not exist or you lack the correct permissions!!<br><br><a href=\"API_editor.php\">Please Reload this Page</a>";

		}

		return $this->return_message;
		
	}

}

// this class contains all the tools to create, edit and delete an api
class APITools{

	public $API_Version = '0';
	public $API_Name = '';
	public $API_Description = '';
	public $API_List = 0;
	public $api_db_connection_list = '';
	public $api_key_db_connection_list = '';
	public $api_sec_token_db_connection_list = '';
	public $Method = '';
	public $api_key_required_checkbox='';
	public $security_token_req_checkbox='';
	public $security_token_regen_checkbox='';
	public $add_variable='';
	public $delete_variable_list = '';
	public $delete_api = '';
	public $post_radio_button = '';
	public $get_radio_button = '';
	public $put_radio_button = '';
	public $delete_radio_button = '';
	public $api_key_required_checkbox_selected='';
	public $security_token_req_checkbox_selected='';
	public $security_token_regen_checkbox_selected='';
	public $userDefinedVariables = array();
	public $connectionRequest = 0;


	//select the selected
	public function selected_option($x, $y){

		//select the chosen one
		if($x === $y){

			$selected = 'selected';

		}elseif($x=='' OR $y==''){

			$selected = '';

		}else{

			$selected = '';
		}

		return $selected;

	}

	// this method compiles a list of user created variables
	public function variables_list(){

		//declare these
		$list_of_variables='';
		$list_of_variables_array = array();
		$delete_var = $this->delete_variable_list;

		//check to see if this is a new file
		if(file_exists("./$this->API_Version/$this->API_Name.php")){

			$filecontents = file_get_contents("./$this->API_Version/$this->API_Name.php");
			$variable_list_array = explode('if(isset($_'.$this->Method.'["', $filecontents);
			array_shift($variable_list_array);

			//loop through this array and extract the existing variables
			foreach ($variable_list_array as $key => $value) {

				//seperate it out from anything else
				$var = explode(')){', $value);
				
				//re-compile variable the list
				if($var[0]<>''){

					$var = '$_'.$this->Method.'["'.$var[0];

					preg_match('/"([^"]+)"/', $var, $m);

					//if it's up for deletion or it's a security token or api key then skip this loop
					if($m[1]==$this->delete_variable_list OR $m[1]=='sec_token' OR $m[1]=='api_key'){

						continue;
					}

					array_push($list_of_variables_array, $m[1]);

					$var = "if(isset($var)){\$$m[1]=$var;}else{\$$m[1]=null;}
		";

					$list_of_variables = $list_of_variables.$var;

				}

			}

			//if there is a new one then add it
			$add_variable = $this->add_variable;
			if($add_variable<>''){

				//remove any spaces
				$add_variable = str_replace(' ', '_', $add_variable);

				//remove single quotes
				$add_variable = str_replace('\'', '', $add_variable);

				//remove double quotes
				$add_variable = str_replace('"', '', $add_variable);

				// check it does not start with a number
				if(is_numeric(trim($add_variable[0]))){

					$add_variable ="_$add_variable";
				}


				// add it to the list
				$list_of_variables = $list_of_variables."if(isset(\$_".$this->Method."[\"$add_variable\"])){\$$add_variable=\$_".$this->Method."[\"$add_variable\"];}else{\$$add_variable=null;}
				";

				// add it to the array
				array_push($list_of_variables_array, $add_variable);

			}

			//security token variable
			if($this->security_token_req_checkbox<>''){

				// add it to the list
				$list_of_variables = $list_of_variables."if(isset(\$_".$this->Method."[\"sec_token\"])){\$sec_token=\$_".$this->Method."[\"sec_token\"];}else{\$sec_token=null;}
				";

				// add it to the array
				array_push($list_of_variables_array, 'sec_token');
			}

			//API key variable
			if($this->api_key_required_checkbox<>''){

				// add it to the list
				$list_of_variables = $list_of_variables."if(isset(\$_".$this->Method."[\"api_key\"])){\$api_key=\$_".$this->Method."[\"api_key\"];}else{\$api_key=null;}
				";

				// add it to the array
				array_push($list_of_variables_array, 'api_key');
			}

		}

		return array($list_of_variables, $list_of_variables_array);

	}

	//this method checks for and builds a security token or rest API key table in the selected database
	public function table_checker_builder($table_name, $api_key_db_connection_list, $api_sec_token_db_connection_list){

		//work out the connection script
		if($table_name=='api_keys'){

			//stop if she's not set
			if($api_key_db_connection_list=='' OR $api_key_db_connection_list=='0'){

				return;

			}else{

				$DB_connection_script = $api_key_db_connection_list;

				//table creation script
				$table_creation = "CREATE TABLE IF NOT EXISTS api_keys (
									id int(55) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
									api_key varchar(100) NOT NULL,
									api_id varchar(55) NOT NULL,
									api_label varchar(55) NOT NULL,
									user_group varchar(250) NOT NULL DEFAULT '0',
									sub_user_group varchar(250) NOT NULL DEFAULT '0',
									last_used varchar(20) NOT NULL,
									expiry_date varchar(55) NOT NULL DEFAULT '10000000000000',
									users_ip_address varchar(55) NOT NULL,
									void varchar(1) NOT NULL DEFAULT '0'
									)";

			}

		}else{

			//stop if she's not set
			if($api_sec_token_db_connection_list=='' OR $api_sec_token_db_connection_list=='0'){

				return;

			}else{

				$DB_connection_script = $api_sec_token_db_connection_list;

				//table creation script
				$table_creation = "CREATE TABLE IF NOT EXISTS security_tokens (
									id int(55) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
									token varchar(100) NOT NULL,
									username varchar(100) NOT NULL,
									user_id varchar(55) NOT NULL,
									date_created varchar(20) NOT NULL,
									last_used varchar(20) NOT NULL,
									expiry_date varchar(55) NOT NULL DEFAULT '10000000000000',
									users_ip_address varchar(55) NOT NULL DEFAULT '0',
									user_group varchar(250) NOT NULL DEFAULT '0'
									)";
			}

		}

		//connect to the db
		require("./db_connect/$DB_connection_script");

		//if this is set then create a new table
		if(isset($_POST['create_'.$table_name])){

			try {

				//run this query to build the table
			    $build=$$db_connection_name->query($table_creation);

				$error_messages = "API key table built successfully";

			}catch(PDOException $e){

				$error_messages = "Table Build Failed: " . $e->getMessage();

			}

		}

		//check to see if the table is there
		try {

	    	$check=$$db_connection_name->query("SELECT 1 FROM $table_name LIMIT 1");

	    	$result = '<span style="color:green">&nbsp; SUCCESS! \''.$table_name.'\' table detected in database: \'' . $db_name . '\'</span>';

		}catch(PDOException $e){

			$result = '<span style="color:red">&nbsp; WARNING! \''.$table_name.'\' table does NOT exist in database: \'' . $db_name . '\'...&nbsp;&nbsp;<input id="create_'.$table_name.'" name="create_'.$table_name.'" type="submit" value="click to create a \''.$table_name.'\' table"></span>';

		}

		//close the connection
		$$db_connection_name = null;

		return $result;

	}

	//this method retrieves the users code
	public function get_users_code(){

		//check it exists
		if(file_exists("./$this->API_Version/$this->API_Name.php")){

			//get the users code and isolate it from the rest of the code
			$filecontents = file_get_contents("./$this->API_Version/$this->API_Name.php");
			$filecontents = explode('<?php', $filecontents);
			$users_code = $filecontents[2];
			$users_code = explode('?>', $users_code);
			$users_code = $users_code[0];

			//remove the php tags
			$users_code = str_replace('?>', '', $users_code);

			$users_code=trim(str_replace('//WRITE YOUR OWN CODE AFTER this PHP tag',"",$users_code));

			//if there is no code then return a blank space else the php tags will touch each other and cause an error
			if($users_code==''){

				$users_code='

		//welcome to your new API! REMEMBER that anything you want this API to return needs to be added to the "$JsonReturnArray" array
		$JsonReturnArray[\'welcome_message\']=\'welcome to your new API! REMEMBER that anything you want this API to return needs to be added to the $JsonReturnArray array\';

		//If you are using API Keys and Security Tokens then the following two arrays hold all the information that is generated after successful checks (Add them to the "$JsonReturnArray" if you want to view them): $securityTokenCheckReturnArray & $apiKeyCheckReturnArray
		//$JsonReturnArray[\'SecTokenChecks\'] = $securityTokenCheckReturnArray;
		//$JsonReturnArray[\'APIKeyChecks\'] = $apiKeyCheckReturnArray;

		//Here are some basic MYSQL query examples using PDO (Not yet looked into MSSQL). There are plenty of MYSQL PDO related tutorials on the internet:

		//NOTE: to make things easier use the "variable variable" (two dollar signs) "$$db_connection_name" to refer to your DB connection instance (Obviously ONLY if you are using a selected DB Connection Script from the dropdown on the PAGE editor)

		//Select example
		//$passwordCheck = $$db_connection_name->prepare("SELECT username, email FROM demo_table WHERE password=:password");
		//$passwordCheck->execute(array(\':password\' => \'qwerty1234\'));
		//$rows = $passwordCheck->fetch(PDO::FETCH_ASSOC);
		
		// add it to the results return array (if you want to!)
		//$JsonReturnArray[\'userName\']=$rows[\'username\'];
		//$JsonReturnArray[\'emailAddress\']=$rows[\'email\'];

		//Delete example
		//$deleteDave = $$db_connection_name->prepare("DELETE FROM table WHERE id=:id");
		//$deleteDave->bindValue(\':id\', \'dave\', PDO::PARAM_STR);
		//$deleteDave->execute();
		//$affected_rows = $deleteDave->rowCount();

		// add it to the results return array (if you want to!)
		//$JsonReturnArray[\'deletionCount\']=$affected_rows;

		//Insert example
		//$insertStuff = $$db_connection_name->prepare("INSERT INTO table(field1,field2,field3,field4,field5) VALUES(:field1,:field2,:field3,:field4,:field5)");
		//$insertStuff->execute(array(\':field1\' => $field1, \':field2\' => $field2, \':field3\' => $field3, \':field4\' => $field4, \':field5\' => $field5));
		//$affected_rows = $insertStuff->rowCount();

		// add it to the results return array (if you want to!)
		//$JsonReturnArray[\'insertCount\']=$affected_rows;

		//Update example
		//$updateStuff = $$db_connection_name->prepare("UPDATE table SET name=? WHERE id=?");
		//$updateStuff->execute(array($name, $id));
		//$affected_rows = $updateStuff->rowCount();

		// add it to the results return array (if you want to!)
		//$JsonReturnArray[\'updateCount\']=$affected_rows;



		//happy coding ;)


				';
			}
			
			//now we run a quick check to make sure they are not trying to connect to a DB with having selected a database connection
			$dbConnectionCheck = explode("\n", $users_code);

			// loop through each line and check
			foreach ($dbConnectionCheck as $key => $value) {

				// check its not connecting
				if (strpos($dbConnectionCheck[$key],'$$db_connection_name') == true) {

					// now check it is commented out...if not then flag it up
					if(substr(trim($dbConnectionCheck[$key]) , 0, 2)<>'//'){

						$this->connectionRequest = 1;

					}

				}

			}	

			//return it
			return $users_code;

		}else{

			$error_messages = "file: $this->API_Name.php is missing";
		}
	}

	//this method creates and or updates an API
	public function create_update_api(){

		//sort out the variables
		$variables = $this->variables_list();
		$variables_list = $variables[1];
		$variables = $variables[0];
		$users_code = $this->get_users_code();
		$this->userDefinedVariables = $variables_list;

		// do this if this is a PUT or DELETE method
		if($this->Method=='PUT' OR $this->Method=='DELETE'){

			$parse_str='parse_str(file_get_contents("php://input"),$_'.$this->Method.');';

		}else{

			$parse_str='';
		}

		//sort out the dummy data array
		$dummy_data_array = '';

		//loop through it and add values to the "array"
		foreach ($variables_list as $value) {

		    if(isset($_POST["dummy_$value"])){

				$_POST["dummy_$value"] = str_replace(' ', '_', $_POST["dummy_$value"]);
				$_POST["dummy_$value"] = str_replace('\'', '', $_POST["dummy_$value"]);
				$_POST["dummy_$value"] = str_replace('"', '', $_POST["dummy_$value"]);

		    	$dummy_data_array = $dummy_data_array.' \''. $value .'\'=>'.'\''. $_POST["dummy_$value"] .'\',';
		    }

		}	

		//remove the last comma
		$dummy_data_array=trim(rtrim($dummy_data_array,','));

		//here is what will be in the script
		$content = '
		<?php //DO NOT ADD or REMOVE anything within these php tags!!

		// this stops the script from being directly loaded
		if(!isset($API_Requested)){
			
			die("<h2>404 file not found</h2>");

		}

		//user defined variable(s)
		'.$parse_str.'
		'.$variables.'
		//system defined variables
		$security_token_regen_checkbox="'.$this->security_token_regen_checkbox.'"; 
		$api_key_required_checkbox="'.$this->api_key_required_checkbox.'"; 
		$security_token_req_checkbox="'.$this->security_token_req_checkbox.'"; 
		$Method=\''.$this->Method.'\';
		$API_Version=\''.$this->API_Version.'\'; 
		$API_Name=\''.$this->API_Name.'\'; 
		$API_Description=\''.$this->API_Description.'\'; 
		$api_db_connection_list="'.$this->api_db_connection_list.'"; 
		$db_connection_list = "'.$this->api_db_connection_list.'"; 
		$api_key_db_connection_list = "'.$this->api_key_db_connection_list.'"; 
		$api_sec_token_db_connection_list = "'.$this->api_sec_token_db_connection_list.'";
		$connectionRequest="'.$this->connectionRequest.'";
		
		//declare this return array
		$JsonReturnArray = array();

		//dummy data for when in development mode
		$dummy_data_array = array('.$dummy_data_array.');

		//load these classes
		require_once(\'./PAGE_CLASSES.php\');

		// if we are in dev mode (using the API editior) we have no need to go any further
		if(isset($DevMOde)){

			return;
		}

		?>
		
		<?php //WRITE YOUR OWN CODE AFTER this PHP tag

		'
		.$users_code.
		'';

		//add / update it to the api folder
		$fp = fopen("./$this->API_Version/$this->API_Name.php","wb");
		fwrite($fp,$content);
		fclose($fp);
		// chmod("$this->API_Version/$this->API_Name.php", 0777); //this cause issues in some setups (not enough permissions to allow chmoding)

	}

}

//if this for the DataBaseCconnection form submit
if(isset($_POST['this_form']) && $_POST['this_form'] == 'DBC_FORM_SUBMIT'){

	//create a new instance of this class
	$dbConnections = new DBConnections;

	// set these properties
	$_POST['db_connection_name'] = str_replace(' ', '_', $_POST['db_connection_name']);
	$_POST['db_connection_name'] = str_replace('\'', '', $_POST['db_connection_name']);
	$_POST['db_connection_name'] = str_replace('"', '', $_POST['db_connection_name']);
	$dbConnections->db_connection_name=$_POST['db_connection_name'];
	$dbConnections->db_connection_list=$_POST['db_connection_list'];
	$dbConnections->db_type=$_POST['db_type'];
	$_POST['db_host'] = str_replace(' ', '_', $_POST['db_host']);
	$_POST['db_host'] = str_replace('\'', '', $_POST['db_host']);
	$_POST['db_host'] = str_replace('"', '', $_POST['db_host']);
	$dbConnections->db_host=$_POST['db_host'];
	$_POST['db_name'] = str_replace(' ', '_', $_POST['db_name']);
	$_POST['db_name'] = str_replace('\'', '', $_POST['db_name']);
	$_POST['db_name'] = str_replace('"', '', $_POST['db_name']);
	$dbConnections->db_name=$_POST['db_name'];
	$_POST['db_username'] = str_replace(' ', '_', $_POST['db_username']);
	$_POST['db_username'] = str_replace('\'', '', $_POST['db_username']);
	$_POST['db_username'] = str_replace('"', '', $_POST['db_username']);
	$dbConnections->db_username=$_POST['db_username'];
	$_POST['db_password'] = str_replace(' ', '_', $_POST['db_password']);
	$_POST['db_password'] = str_replace('\'', '', $_POST['db_password']);
	$_POST['db_password'] = str_replace('"', '', $_POST['db_password']);
	$dbConnections->db_password=$_POST['db_password'];

	//change the "select_list_connection_form_value" property (this denotes which connection is shown as selected in the select menu)
	$dbConnections->select_list_connection_form_value=$_POST['db_connection_list'];

	//is this a DATABASE CONNECTION DELETION request?
	if($_POST['delete_db_connection']==1){

		// call this method
		echo $dbConnections->DeleteDBConnection();

		//echo out this message
		$dbConnections->return_message;

		//stop the rest of the script
		return;
	}

	// this means we are creating a new one
	if($_POST['db_connection_list']=='1'){

		if($dbConnections->db_connection_name==''){

			//return an error message..and do nothing else!
			$error_messages ='Please Enter the Database Connection Name and then press the "Save / Test" button';

		}elseif($dbConnections->db_host==''){

			//return an error message..and do nothing else!
			$error_messages ='Please Enter the Database Host and then press the "Save / Test" button';

		}elseif($dbConnections->db_name==''){

			//return an error message..and do nothing else!
			$error_messages ='Please Enter a Database Name and then press the "Save / Test" button';

		}elseif($dbConnections->db_username==''){

			//return an error message..and do nothing else!
			$error_messages ='Please Enter the Database Username and then press the "Save / Test" button';

		}elseif($dbConnections->db_password==''){

			//return an error message..and do nothing else!
			$error_messages ='Please Enter the Database Password and then press the "Save / Test" button';

		}else{

			//check it doesn't already exist
			if(file_exists("./db_connect/$dbConnections->db_connection_name.php")){

				//return an error message..and do nothing else!
				$error_messages ='This Database Connection Already Exists!';

			}else{

				//Generate the connection script
				$dbConnections->con_script();

				//build it!
				$dbConnections->write_db_connection_to_file();

				//change this so the db list shows the correct connection
				$db_connection_list = "$dbConnections->db_connection_name.php";
				// $dbConnections->select_list_connection_form_value=$_POST['db_connection_list'];

				//now load it
				require("./db_connect/$dbConnections->db_connection_name.php");

			}

		}

	}else{//not creating a new connection but loading or updating an existing one

		//check it exists
		if(!file_exists("./db_connect/$dbConnections->select_list_connection_form_value")){

			//return an error message..and do nothing else!
			$error_messages ="ERROR! This Database Connection File ($dbConnections->select_list_connection_form_value) is Missing!";				

		}else{

			//update the DB Connection by firstly generating the connection script
			$dbConnections->con_script();

			// and then by writing it to file
			$dbConnections->write_db_connection_to_file();

			//now load it
			require("./db_connect/$dbConnections->select_list_connection_form_value");

			// set these properties
			$dbConnections->db_connection_name=$db_connection_name;
			$dbConnections->db_type=$db_type;
			$dbConnections->db_host=$db_host;
			$dbConnections->db_name=$db_name;
			$dbConnections->db_username=$db_username;
			$dbConnections->db_password=$db_password;

		}

	}

}else{

	//create an instance of this class
	$dbConnections = new DBConnections;

}//end of DBC form submit

//if this for the API form submit
if(isset($_POST['this_form']) && $_POST['this_form'] == 'API_FORM_SUBMIT'){

	// create an instance of this class
	$APITools = new APITools;	

	// set these properties
	if(isset($_POST['API_Version']))$APITools->API_Version = $_POST['API_Version'];
	if(isset($_POST['API_Name'])){

		//remove any spaces
		$_POST['API_Name'] = str_replace(' ', '_', $_POST['API_Name']);

		//remove single quotes
		$_POST['API_Name'] = str_replace('\'', '', $_POST['API_Name']);

		//remove double quotes
		$_POST['API_Name'] = str_replace('"', '', $_POST['API_Name']);

		$APITools->API_Name = $_POST['API_Name'];

	}

	if(isset($_POST['delete_api']))$APITools->delete_api = $_POST['delete_api'];
	if(isset($_POST['API_List']))$APITools->API_List = $_POST['API_List'];
	if(isset($_POST['API_Description'])){

		//remove single quotes
		$_POST['API_Description'] = str_replace('\'', '', $_POST['API_Description']);

		//remove double quotes
		$_POST['API_Description'] = str_replace('"', '', $_POST['API_Description']);
				
		$APITools->API_Description = $_POST['API_Description'];

	}
	if(isset($_POST['api_db_connection_list']))$APITools->api_db_connection_list = $_POST['api_db_connection_list'];
	if(isset($_POST['api_key_db_connection_list']))$APITools->api_key_db_connection_list = $_POST['api_key_db_connection_list'];
	if(isset($_POST['api_sec_token_db_connection_list']))$APITools->api_sec_token_db_connection_list = $_POST['api_sec_token_db_connection_list'];
	if(isset($_POST['Method']))$APITools->Method = $_POST['Method'];
	if(isset($_POST['api_key_required_checkbox']))$APITools->api_key_required_checkbox=$_POST['api_key_required_checkbox'];
	if(isset($_POST['security_token_req_checkbox']))$APITools->security_token_req_checkbox=$_POST['security_token_req_checkbox'];
	if(isset($_POST['security_token_regen_checkbox']))$APITools->security_token_regen_checkbox=$_POST['security_token_regen_checkbox'];
	if(isset($_POST['add_variable']))$APITools->add_variable=$_POST['add_variable'];
	if(isset($_POST['delete_variable_list']))$APITools->delete_variable_list = $_POST['delete_variable_list'];

	//is this an API DELETION request?
	if($APITools->delete_api==1){

		//check it exists
		if(file_exists("./$APITools->API_Version/$APITools->API_List")){

			//delete it
			unlink("./$APITools->API_Version/$APITools->API_List");

			//output this message
			echo "$APITools->API_List API DELETED!!<br><br><a href=\"API_editor.php\">Please Reload this Page</a>";

		}else{

			//output this message instead
			echo "$APITools->API_List was NOT Deleted as the file does not exist or you lack the correct permissions!!<br><br><a href=\"API_editor.php\">Please Reload this Page</a>";

		}
		
		//stop the rest of the script
		return;
	}

	//load the api...
	if($APITools->API_List=='0'){

		//return an error message..and do nothing else!
		$error_messages ='&nbsp;';

	// if its this option then the user is wanting to create a new one
	}elseif($APITools->API_List=='1'){

		//Check we have a version selected
		if($APITools->API_Version=='0'){

			//return an error message..and do nothing else!
			$error_messages ='Please select a version (to create a new version manually add a folder (with the required version as its name) into the "apis" folder';

		//if the API name empty then return an error
		}elseif($APITools->API_Name==''){

			//return an error message..and do nothing else!
			$error_messages ='Please Enter an API Name and then press the "Save & Run" button';

		//check there is a description
		}elseif($APITools->API_Description==''){

			//return an error message..and do nothing else!
			$error_messages ='Please Enter a Description for this API and then press the "Save & Run" button';

		}else{

			//look for the api file...if it doesn't exist then create it
			if(!file_exists("./$APITools->API_Version/$APITools->API_Name.php")){

				//create the API
				$APITools->create_update_api();

				//now load it
				require("./$APITools->API_Version/$APITools->API_Name.php");

				$APITools->security_token_regen_checkbox=$security_token_regen_checkbox; 
				$APITools->api_key_required_checkbox=$api_key_required_checkbox; 
				$APITools->security_token_req_checkbox=$security_token_req_checkbox; 
				$APITools->Method=$Method;
				$APITools->API_Version=$API_Version; 
				$APITools->API_Name=$API_Name; 
				$APITools->API_Description=$API_Description; 
				$APITools->api_db_connection_list=$api_db_connection_list; 
				$APITools->db_connection_list=$db_connection_list; 
				$APITools->api_key_db_connection_list=$api_key_db_connection_list; 
				$APITools->api_sec_token_db_connection_list=$api_sec_token_db_connection_list;

				//reset the error message
				$error_messages = 'Your New API has been CREATED! Please press the "Save & Run" Button to Load it...';

				//change this so the correct API is show in the select list
				$APITools->API_List = "$APITools->API_Name.php";

			}else{

				//return this error message
				$error_messages ='This API already exists, please choose another name';

			}

		}

	}else{

		//check to see if this is a save/run by comparing what API is already loaded with what it's being replaced with (if the same then update it)
		if("$APITools->API_Name.php"==$APITools->API_List){

			//update the API
			$APITools->create_update_api();

		}

		//load the selected api
		if(file_exists("./$APITools->API_Version/$APITools->API_List")){

			//get the selected API
			require("./$APITools->API_Version/$APITools->API_List");

			$APITools->security_token_regen_checkbox=$security_token_regen_checkbox; 
			$APITools->api_key_required_checkbox=$api_key_required_checkbox; 
			$APITools->security_token_req_checkbox=$security_token_req_checkbox; 
			$APITools->Method=$Method;
			$APITools->API_Version=$API_Version; 
			$APITools->API_Name=$API_Name; 
			$APITools->API_Description=$API_Description; 
			$APITools->api_db_connection_list=$api_db_connection_list; 
			$APITools->db_connection_list=$db_connection_list; 
			$APITools->api_key_db_connection_list=$api_key_db_connection_list; 
			$APITools->api_sec_token_db_connection_list=$api_sec_token_db_connection_list;

			//reset the error message
			$error_messages = '&nbsp;';

		}else{

			//return this error message
			$error_messages ="API File cannot be found!! (./$APITools->API_Version/$APITools->API_List)";

		}

	}

	//work out which GET, POST, DELETE or PUT radio button needs to be selected
	if($APITools->Method=='POST'){

		$APITools->post_radio_button = 'checked="checked"';

	}else if($APITools->Method=='GET'){

		$APITools->get_radio_button = 'checked="checked"';

	}else if($APITools->Method=='DELETE'){

		$APITools->delete_radio_button = 'checked="checked"';

	}else{

		$APITools->put_radio_button = 'checked="checked"';
	}

	//work out if the api_key_required_checkbox is checked or not
	if(isset($APITools->api_key_required_checkbox) AND $APITools->api_key_required_checkbox =='1'){

		$APITools->api_key_required_checkbox_selected = 'checked';

	}

	//work out if the security_token_req_checkbox is checked or not
	if(isset($APITools->security_token_req_checkbox) AND $APITools->security_token_req_checkbox =='1'){

		$APITools->security_token_req_checkbox_selected = 'checked';

	}

	//work out if the security_token_regen_checkbox is checked or not
	if(isset($APITools->security_token_regen_checkbox) AND $APITools->security_token_regen_checkbox =='1'){

		$APITools->security_token_regen_checkbox_selected = 'checked';

	}

}else{

	$APITools = new APITools;

}//end of API form submit

?>

<html>

<title>PHP API Generator & Editor</title>
<body>

<!-- page header -->
<h4>PHP API Generator & Editor</h4>

<!-- error messages -->
<h5 style="color:red;"><?php echo $error_messages; ?></h5>

<h2>DataBase Connection Section - Create/Edit Database Connections</h2>

<!-- Database Connection Section -->
<form action="API_editor.php" method="post">

	<!-- so we know what form is being submitted -->
	<input type="hidden" name="this_form" value="DBC_FORM_SUBMIT">

	<div>Create or Select Database Connection</div>
	<!-- Get the Database Connection List-->
	<?php

		//change the "select_list_id" property (the element ID)
		$dbConnections->select_list_id='db_connection_list';

		//change the "select_list_label" property (the title which appears if you hover over it)
		$dbConnections->select_list_label='Select DB Option';

		//change the "db_creator_option" property (1 will add the "create new connection" option)
		$dbConnections->db_creator_option='1';

		//now call this method and echo out the result (should be a select list!)
		echo $dbConnections->db_connection_select_list();

		//work out which db_type to select
		if($dbConnections->db_type=='' OR $dbConnections->db_type=='mysql'){

			$db_type_select_mssql = '';
			$db_type_select_mysql = 'selected';

		}else{

			$db_type_select_mssql = 'selected';
			$db_type_select_mysql = '';

		}

	?>

	<!-- Type of database and connection details -->
	<br><br>
	<div>Database Type</div>
	<select id="db_type" name="db_type"><option value="mysql" <?php echo $db_type_select_mysql; ?>>mysql</option><option value="mssql" <?php echo $db_type_select_mssql; ?>>mssql</option></select>
	<br><br>
	<div>Database Connection Name</div>
	<input type="text" id="db_connection_name" name="db_connection_name" placeholder="Database Connection Name" value="<?php echo $dbConnections->db_connection_name;?>">
	<br><br>
	<div>Database Host Name</div>
	<input type="text" id="db_host" name="db_host" placeholder="Database Host" value="<?php echo $dbConnections->db_host;?>">
	<br><br>
	<div>Database Name</div>
	<input type="text" id="db_name" name="db_name" placeholder="Database Name" value="<?php echo $dbConnections->db_name;?>">
	<br><br>
	<div>Database Username</div>
	<input type="text" id="db_username" name="db_username" placeholder="Database Username" value="<?php echo $dbConnections->db_username;?>">
	<br><br>
	<div>Database Password</div>
	<input type="password" id="db_password" name="db_password" placeholder="Database Password" value="<?php echo $dbConnections->db_password;?>">
	<br><br>
	<div>Database Deletion</div>
	<select id="delete_db_connection" name="delete_db_connection" onchange='this.form.submit()' style="width:150px;">
	<option value="0">Delete This Connection</option>
	<option value="0">---------</option>
	<option value="0">---------</option>
	<option value="0">WARNING!</option>
	<option value="0">INSTANT DELETION!</option>
	<option value="0">DOUBLE CHECK WHAT YOU'RE DELETING! (<?php echo $dbConnections->db_connection_list; ?>)</option>
	<option value="0">---------</option>
	<option value="0">---------</option>
	<option value="1" style="color:red">DELETE This (<?php echo $dbConnections->db_connection_list; ?>) Connection</option>
	</select>
	<br><br>
	<div>Save & Test DB Connection</div>
	<input type="submit" value="Save / Test This Database Connection">
	<br><br><br><br>

</form><!-- End of Database Connection Section -->

<hr>

<h2>API Section - Create/Edit Your API's Here</h2>

<!-- API SECTION -->
<form action="API_editor.php#pageBottom" method="post">

	<!-- so we know what form is being submitted -->
	<input type="hidden" name="this_form" value="API_FORM_SUBMIT">

	<!-- API Version Select List -->
	<div>Select API Version</div>
	<select id="API_Version" name="API_Version" onchange='this.form.submit()' style="width:200px">
		<option value="0">Select API Version</option>
				
		<?php

			//loop through the rest API folder
			$services = scandir("./");

			foreach ($services as $value) {

				//only select folders
				if (preg_match('/[^A-Za-z0-9]/', $value)) // '/[^a-z\d]/i' should also work.
				{
			  		continue;
				}

				//select the chosen option
				$selected = $APITools->selected_option($APITools->API_Version, $value);

				echo '<option value="'.$value.'" '.$selected.'>'.$value.'</option>';
			}	

		?>

	</select>

	<br><br>

	<?php

		if($APITools->API_Version=='0'){

			die('<span style="color:red;"><p>Please Select an API Version</p><p>To create a new version manually add a folder (with the required version as its name) into the "apis" folder</p></span>');

		}

	?>

	<!-- API Select List -->
	<div>API List</div>
	<select id="API_List" name="API_List" onchange='this.form.submit()' style="width:200px">
		<option value="0">Select an API</option>
		<option value="1" <?php if($APITools->API_List=='1'){echo "selected";} ?>>Create a New API</option>
		
		<?php

			//loop through the rest API folder
			$services = scandir("./$APITools->API_Version/");

			foreach ($services as $value) {

				//check it has a proper file name or IGNORE it if it is the api controller
				$check = explode('.', $value);

				if($check[0]=='' OR $check[1]<>'php' OR $check[0]=='API_controller'){

					continue;
				}

				//select the chosen option
				$selected = $APITools->selected_option($APITools->API_List, $value);

				echo '<option value="'.$value.'" '.$selected.'>'.$value.'</option>';
			}	

		?>

	</select>

	<!-- API name and deletion-->
	<input type="text" id="API_Name" name="API_Name" placeholder="API Name" value="<?php echo $APITools->API_Name; ?>">
	<select id="delete_api" name="delete_api" onchange='this.form.submit()' style="width:150px;">
		<option value="0">Delete This API</option>
		<option value="0">---------</option>
		<option value="0">---------</option>
		<option value="0">WARNING!</option>
		<option value="0">INSTANT DELETION!</option>
		<option value="0">DOUBLE CHECK WHAT YOU'RE DELETING! (<?php echo $APITools->API_List; ?>)</option>
		<option value="0">---------</option>
		<option value="0">---------</option>
		<option value="1" style="color:red">DELETE This (<?php echo $APITools->API_List; ?>) API</option>
	</select>

	<br><br>

	<?php

		if($APITools->API_List=='0'){

			die('<span style="color:red;"><p>Please Select or Create a new API</p></span>');

		}

	?>

	<!-- API Select Database Connection List-->
	<div>This API's Database (optional)</div>
	<?php 

		//create an instance of this class
		$apiDBConnections = new DBConnections;

		//change the "select_list_id" property (the element ID)
		$apiDBConnections->select_list_id='api_db_connection_list';

		//change the "select_list_label" property (the title which appears if you hover over it)
		$apiDBConnections->select_list_label='Select Database for this API';

		//change the "select_list_connection_form_value" property (this denotes which connection is shown as selected in the select menu)
		$apiDBConnections->select_list_connection_form_value=$APITools->api_db_connection_list;

		//now call this method and echo out the result (should be a select list!)
		echo $apiDBConnections->db_connection_select_list();

	?>

	<br><br>

	<!-- API description -->
	<textarea id="API_Description" name="API_Description" placeholder="API Description" rows="5" cols="110"><?php echo $APITools->API_Description; ?></textarea>

	<br><br>

	<!-- method radio buttons -->
	<div>Method</div>
	<label>POST&nbsp;&nbsp;<input name="Method" type="radio" value="POST" onchange='this.form.submit()' <?php echo $APITools->post_radio_button; ?>></label>
	<br><br>
	<label>GET&nbsp;&nbsp;<input name="Method" type="radio" value="GET" onchange='this.form.submit()' <?php echo $APITools->get_radio_button; ?>></label>
	<br><br>
	<label>PUT&nbsp;&nbsp;<input name="Method" type="radio" value="PUT" onchange='this.form.submit()' <?php echo $APITools->put_radio_button; ?>></label>
	<br><br>
	<label>DELETE&nbsp;&nbsp;<input name="Method" type="radio" value="DELETE" onchange='this.form.submit()' <?php echo $APITools->delete_radio_button; ?>></label>
	<br><br><br>

	<!-- API KEY requirment -->
	<label>API KEY Required <input id="api_key_required_checkbox" name="api_key_required_checkbox" type="checkbox" value="1" onchange='this.form.submit()' <?php echo $APITools->api_key_required_checkbox_selected; ?>></label>
	<br><br>

	<!-- API KEY Database selection -->
	<div>API KEY Database</div>
	<?php 

		//create an instance of this class
		$apiKEYDBConnections = new DBConnections;
		
		//change the "select_list_id" property (the element ID)
		$apiKEYDBConnections->select_list_id='api_key_db_connection_list';

		//change the "select_list_label" property (the title which appears if you hover over it)
		$apiKEYDBConnections->select_list_label='Select Database for the API KEY';

		//change the "select_list_connection_form_value" property (this denotes which connection is shown as selected in the select menu)
		$apiKEYDBConnections->select_list_connection_form_value=$APITools->api_key_db_connection_list;

		//now call this method and echo out the result (should be a select list!)
		echo $apiKEYDBConnections->db_connection_select_list();

		echo $APITools->table_checker_builder('api_keys', $APITools->api_key_db_connection_list, $APITools->api_sec_token_db_connection_list);

	?>

	<br><br>

	<!-- Security Token requirment -->
	<label>Security Token Required <input id="security_token_req_checkbox" name="security_token_req_checkbox" type="checkbox" value="1"  onchange='this.form.submit()' <?php echo $APITools->security_token_req_checkbox_selected; ?>></label>&nbsp;&nbsp;<label>Regenerate Security Token? <input id="security_token_regen_checkbox" name="security_token_regen_checkbox" type="checkbox" value="1" onchange='this.form.submit()' <?php echo $APITools->security_token_regen_checkbox_selected; ?>></label>
	<br><br>

	<!-- Security Token Database Selection -->
	<div>Security Token Database</div>
	<?php 

		//create an instance of this class
		$apiSecTokenDBConnections = new DBConnections;
		
		//change the "select_list_id" property (the element ID)
		$apiSecTokenDBConnections->select_list_id='api_sec_token_db_connection_list';

		//change the "select_list_label" property (the title which appears if you hover over it)
		$apiSecTokenDBConnections->select_list_label='Select Database for Security Token';

		//change the "select_list_connection_form_value" property (this denotes which connection is shown as selected in the select menu)
		$apiSecTokenDBConnections->select_list_connection_form_value=$APITools->api_sec_token_db_connection_list;

		//now call this method and echo out the result (should be a select list!)
		echo $apiSecTokenDBConnections->db_connection_select_list();

		echo $APITools->table_checker_builder('security_tokens', $APITools->api_key_db_connection_list, $APITools->api_sec_token_db_connection_list);?>

	<br><br>

	<!-- variables list-->
	<?php

		//get the list of variables
		$variables_list = $APITools->userDefinedVariables;

		// check its not empty
		if(empty($variables_list)){

			$variables_list = $APITools->variables_list();
			$variables_list = $variables_list[1];

		}

	?>
	<div>Add Variables</div>
	<input id="add_variable" name="add_variable" type="text">&nbsp;<input type="submit" value="add variable">
	<br><br>
	<div>Dummy data (add dummy values to your variables) - optional</div>
	<br>
	<?php

		//loop through and display the dummy data values and inputs
		foreach ($variables_list as $value) {
		    
		    //get the dummy data
		    if(isset($dummy_data_array) && isset($dummy_data_array[$value])){

		    	$dummy_data = $dummy_data_array[$value];
		    	$dummy_data_value = $dummy_data_array[$value];

		    }else{

		    	$dummy_data = '';
		    	$dummy_data_value = '';
		    }
	    	

	    	//now display it
		    echo "'<strong>$value</strong>' variable value: <input id=\"dummy_$value\" name=\"dummy_$value\" type=\"text\" value=\"$dummy_data_value\"><br><br>";

		}

	?>
	<br><br>
	<div>Delete Variables</div>
	<select multiple name="delete_variable_list" id="delete_variable_list" style="width:200px">
	
	<?php

		//loop through and show the list of variables
		foreach ($variables_list as $value) {
		    
		    echo "<option value=\"$value\">$value</option>";
		}		

	?>

	</select>
	<br>
	<input type="submit" value="Delete Selected Variable">

	<br><br>

	<!-- scroll to this position -->
	<a id="pageBottom"></a>

	<br><br>

	<input type="submit" value="Save & Run" style="width:300px">

</form><!-- End of API SECTION -->

<br><br>

<!-- json output area -->
<strong>API URL: </strong>&nbsp;<span style="color:green">
	<?php

	// build the variable list
	$var_list = '';

	// loop through the list
	foreach ($variables_list as $value) {
	    
	    //get the dummy data
	    if(isset($dummy_data_array) && isset($dummy_data_array[$value])){

	    	$dummy_data = $dummy_data_array[$value];
	    	$dummy_data_value = $dummy_data_array[$value];

	    }else{

	    	$dummy_data = '';
	    	$dummy_data_value = '';
	    }
		
	    $var_list = $var_list . "$value=$dummy_data_value&";

	}

	// remove the last ampersand
	$var_list =rtrim($var_list, "&");

	// sort out if its a GET or POST type Variable list
	if($APITools->Method=='GET'){

		$GET_VARS = "?$var_list";
		$var_list = "";

	}else{

		$GET_VARS = "";
	}

	// API path
	$URL = substr($_SERVER['HTTP_REFERER'], 0, strrpos($_SERVER['HTTP_REFERER'], "/"))."/$APITools->API_Version/$APITools->API_Name$GET_VARS";

	//echo the path
	echo '<a href="'.$URL.'" target="_blank">'.$URL.'</a>';

	?>
</span>
<br><br>
<div><strong>Json Output:</strong>&nbsp;<?php echo '<span style="color:red;">'.$error_messages."</span>"; ?></div>
<br>
<pre id="jsonOutPut" style="min-height:1000px;"></pre>

</body>
</html>

<!-- some styling! -->
<style>

	/*json output styling*/
	#jsonOutPut {outline: 1px solid #ccc; padding: 5px; margin: 5px; }
	.string { color: green; }
	.number { color: darkorange; }
	.boolean { color: blue; }
	.null { color: magenta; }
	.key { color: red; }

</style>

<!-- some js for testing the api calls -->
<script type="text/javascript">

	function testAPI(){

		var xhr = new XMLHttpRequest();
		xhr.open(<?php echo "'$APITools->Method'"; ?>, <?php echo "'$URL'"; ?>);
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
		xhr.onload = function() {

			// hopefully we have a 200 response
		    if (xhr.status === 200) {

		    	// check it is correctly formatted json
		    	if(validJson(xhr.responseText)){

			    	// parse the json
			    	var responseText = JSON.parse(xhr.responseText)

			    	// inser it into the console (you could comment this out if you wanted to)
			        console.log(responseText);

			        // if this API regenerates a security toekn we need to deal with it like this
			        if(typeof responseText.newSecToken !== 'undefined' && responseText.newSecToken!=''){

			        	// update the old security code with this new one
			        	document.getElementById('dummy_sec_token').value = responseText.newSecToken;

			        }
			        
			        // this is where we output the json return onto the screen by doing this....
			        var str = JSON.stringify(responseText, undefined, 6);
			        output(syntaxHighlight(str));

		    	}else{

		    		// just output what we have (raw)
		    		document.getElementById("jsonOutPut").innerHTML = '<h3 style="color:red;">PHP Script Errors!!</h3>'+xhr.responseText;

		    		// add it to the consiole for good measure
		    		console.log(xhr.responseText);

		    	}

		    }else{

		    	// output the response code
		    	document.getElementById("jsonOutPut").innerHTML = 'error! the server responded with a "'+xhr.status+'" status (needs to be 200)...this either means that the API you are testing does not exist or your .htacces file is missing or corrupt or there is an issue with your servers re-write settings or there are file/folder permission problems (you may need to chmod this parent folder and everything within it)';
		    	
		    }
		};

		// if this is not a GET method then we need to send the variables here
		xhr.send("<?php echo $var_list; ?>");

	}

	// this function physically outputs the json response onto the screen
	function output(inp) {

		document.getElementById("jsonOutPut").innerHTML = inp;

	}

	// this function beautifies and formats the json output
	function syntaxHighlight(json) {
	    json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
	    return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
	        var cls = 'number';
	        if (/^"/.test(match)) {
	            if (/:$/.test(match)) {
	                cls = 'key';
	            } else {
	                cls = 'string';
	            }
	        } else if (/true|false/.test(match)) {
	            cls = 'boolean';
	        } else if (/null/.test(match)) {
	            cls = 'null';
	        }
	        // document.getElementById("jsonOutPut").innerHTML = '<span class="' + cls + '">' + match + '</span>';
	        return '<span class="' + cls + '">' + match + '</span>';
	    });
	}

	// this function checks that the return value is a valid json string
	function validJson(data) {

	    try {

	        JSON.parse(data);

	    } catch (e) {

	        return false;

	    }

	    return true;
	}


	//auto run the API
	testAPI();

</script>







