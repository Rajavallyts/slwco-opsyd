<?php
/**
  * @file
  * Contains \Drupal\mongodb_api\Controller\mongodb_apicontroller.
 */
     
namespace Drupal\mongodb_api\Controller;
     
use Drupal\Core\Controller\ControllerBase;
     
class mongodb_apiController extends ControllerBase {
  public function connectMongoDB()
  {
	  global $base_url;
	  if ($_SESSION['mongodb_token'] != "") {
		  $api_endpointurl = "ec2-54-210-86-147.compute-1.amazonaws.com:3000/api/close";
		$api_param = array ("token" => $_SESSION['mongodb_token']);
									 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    $server_output = curl_exec ($ch);		
		curl_close ($ch);
		$_SESSION['mongodb_token'] = "";
	  }
	  
	  //return "yes";
	 
	  if (isset($_GET['mongodb_id'])){			
		$api_endpointurl = "ec2-54-210-86-147.compute-1.amazonaws.com:3000/api/connect";
		$mongodb_nid = $_GET['mongodb_id'];
		$mongodb_node = node_load($mongodb_nid);		
		if (isset($mongodb_node->field_upload_key->entity) ) {			
			$file_uri = $mongodb_node->field_upload_key->entity->getFileUri();		
			if (trim($mongodb_node->field_username->value) != "") {
				$api_param = array (
					'ssh'  => true,
					'host' => $mongodb_node->title->value,
					'dbName' => $mongodb_node->field_db_name->value,
					'keyfile' => drupal_realpath($file_uri),
					'username' => $mongodb_node->field_username->value,
				);
			} else {
				$api_param = array (
					'ssl'  => true,
					'host' => $mongodb_node->title->value,
					'dbName' => $mongodb_node->field_db_name->value,
					'keyfile' => drupal_realpath($file_uri),					
				);
			}
		} else {
			$api_param = array (			
			'host' => $mongodb_node->title->value,
			'dbName' => $mongodb_node->field_db_name->value,			
			);
		}
		
		$headers = array("Content-Type:multipart/form-data"); // cURL headers for file uploading
		/*$postfields = array('ssl'  => true,
			'host' => $mongodb_node->title->value,
			'dbName' => $mongodb_node->field_db_name->value,
			'keyfile' => "@$filedata", "filename" => $file_uri);//array("filedata" => "@$filedata", "filename" => $file_uri);*/
		$ch = curl_init();
		$options = array(
			CURLOPT_URL => $api_endpointurl,
			CURLOPT_HEADER => true,
			CURLOPT_POST => 1,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_POSTFIELDS => $api_param,        
			CURLOPT_RETURNTRANSFER => true
		); 
		curl_setopt_array($ch, $options);
		$server_output =  curl_exec($ch);
		
		$response = $server_output;
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$body = substr($response, $header_size);
		$json_result = json_decode($body, true);
		
		if ($json_result['success'] == 1) {
			$_SESSION['mongodb_token'] = $json_result['token'];
			drupal_set_message (t('Success - Mongo DB connection establised.'));
			$redirect_url = $base_url . '/mongodb_api/listcollection';
			$response = new \Symfony\Component\HttpFoundation\RedirectResponse($redirect_url);
			$response->send();
			return;
		}
		else
		{
			//drupal_set_message(t("Invalid Database information.  No connection establised to Mongo DB."), "error");
			$errormessage = "Invalid Database information.  No connection establised with <b>IP - " . $mongodb_node->title->value . "</b> and <b> name - " . $mongodb_node->field_db_name->value . "</b>"; 
			drupal_set_message(t($errormessage), "error");
			$redirect_url = $base_url . '/mongodb-list';
			$response = new \Symfony\Component\HttpFoundation\RedirectResponse($redirect_url);
			$response->send();
			return;
		}
	  }	
	
	return array(
      '#type' => 'markup',
      '#markup' => "yes",
    );
	
  }  
	
  public function listcollection() {
$_SESSION['json_text'] = "";	  
		global $base_url;
		$output_html = "<BR><BR>MongoDB connection does not exist. <a href='" . $base_url . "/mongodb-list' alt='Connect MongoDB' title='Connect MongoDB'>Connect MongoDB</a>";
		if ($_SESSION['mongodb_token'] != ""){					
			$api_endpointurl = "ec2-54-210-86-147.compute-1.amazonaws.com:3000/api/collections";
		//	$api_endpointurl = "127.0.0.1:3000/api/collections";
			$api_param = array ( "token" => $_SESSION['mongodb_token']);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$server_output = curl_exec ($ch);		

			curl_close ($ch);
			$json_result = json_decode($server_output, true);	
						
			$output_html = '';
			if (count ($json_result) > 0 ) {				
				foreach($json_result as $result):			
					$output_html .= "<div><a href='" . $base_url . "/mongodb_api/listdocument?mongodb_collection=".$result['name']."'>" . $result['name'] . "</a></div>";					
				endforeach;				
			} else {
		        $output_html = 'No collection found!';			
			}
		}	
		$tablerows = array ('#markup' => $output_html);
			
		$output_html = [
			'#prefix'=> "<a class='use-ajax' data-dialog-type='modal' data-dialog-options='{\"width\":500}' href='" . $base_url . "/mongodb_api/addCollection'>Add Collection</a><BR><BR>" . mongodb_parseJSON($server_output),
		//	'#prefix' => mongodb_parseJSON($server_output),
			'#type' => 'table',
			'#header' => [t('List of Collections')],
			//'#rows' => $html
			'#rows' => [
				[render($tablerows)]
			]
		];
		
	return $output_html;
  } 

 public function listdocument() {
		global $base_url;
		
		$output_html = "<BR><BR>MongoDB connection does not exist. <a href='" . $base_url . "/mongodb-list' alt='Connect MongoDB' title='Connect MongoDB'>Connect MongoDB</a>";
		if ($_SESSION['mongodb_token'] != ""){
			if (isset($_GET['mongodb_collection'])) {				
				$api_endpointurl = "ec2-54-210-86-147.compute-1.amazonaws.com:3000/api//collections/" . $_GET['mongodb_collection'] ."/find";
				//$api_endpointurl = "127.0.0.1:3000/api/collections/" . $_GET['mongodb_collection'] ."/find";
				$api_param = array ( "token" => $_SESSION['mongodb_token']);
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$server_output = curl_exec ($ch);		
				curl_close ($ch);
			
				$json_result = json_decode($server_output, true);							
				$output_html = '';
				$dcount = 0;
				if (count ($json_result) > 0 ) {				
					foreach($json_result as $result):			
						$output_html .= "<div><a href='" . $base_url . "/mongodb_api/managedocument?mongodb_collection=".$_GET['mongodb_collection'];
						$inner_html = "";
						$inner_id = "";
						$fieldcount = count($result);
						foreach ($result as $resultkey => $resultValue):							
							if ($resultkey == "_id") {
							$dcount++;
							$inner_id = $resultValue;
							$inner_html = "(" . $dcount . ")&nbsp;&nbsp;{ObjectId('" . $resultValue . "')}";
							}
						//	if ($resultkey != "_id")
							//$inner_html .= "'" . $resultkey . "':'" . $resultValue . "',";
						endforeach;
						$inner_html .= "{" . $fieldcount . " fields}";
						//$inner_html .= substr($inner_html, 0, strlen($inner_html)-2);
						$output_html .= "&document_id=". $inner_id ."'>". $inner_html . "</a></div>";
					endforeach;				
				} else {
					$output_html = 'No document found!';			
				}
			} else {
				$output_html = "<BR><BR>No collection selected. <a href='" . $base_url . "/mongodb_api/listcollection' alt='Collection list' title='Collection list'>Collection List</a>";
			}
		}	
		$tablerows = array ('#markup' => $output_html);
		$output_html = [
			'#prefix'=> isset($_GET['mongodb_collection']) ? "<a class='use-ajax' data-dialog-type='modal' data-dialog-options='{\"width\":900}' href='" . $base_url . "/mongodb_api/addDocument?mongodb_collection=".$_GET['mongodb_collection']."'>Add Document</a>&nbsp;&nbsp;&nbsp;<a class='use-ajax' data-dialog-type='modal' data-dialog-options='{\"width\":900}' href='" . $base_url . "/mongodb_api/addJSON?mongodb_collection=".$_GET['mongodb_collection']."'>Add JSON</a><BR><BR>" .mongodb_parseJSON($server_output) : "" . mongodb_parseJSON($server_output),
			'#type' => 'table',
			'#header' => [t('List of Collections')],
			//'#rows' => $html
			'#rows' => [
				[render($tablerows)]
			]
		];
		
	return $output_html;
  } 
}


