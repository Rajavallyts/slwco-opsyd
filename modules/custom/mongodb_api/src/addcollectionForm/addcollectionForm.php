<?php
namespace Drupal\mongodb_api\addcollectionForm;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;

class addcollectionForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'manage_document';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	  global $base_url;	  
	  $server_output = "";
	  
	  if ($_SESSION['mongodb_token'] != "") {
		$form['collection_name'] = [
		  '#type' => 'textfield',
		  '#required' => TRUE,
		  '#title' => $this->t('Collection Name'),
		];
		$form['submit'] = [
		  '#type' => 'submit',
		  '#value' => t('Add Collection'),
		];
	  } else {
		  $form['description'] = [
			'#type' => 'markup',
			'#markup' => 'No MongoDB Connection available.',
		  ];		  
	  }
    return $form;
  }
  

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
	     $updateWith = "{";
		 $collection_name = $form_state->getValues("collection_name");		 
		 global $base_url;
		 
		 if (isset($collection_name)) {
		  $api_endpointurl = "ec2-54-210-86-147.compute-1.amazonaws.com:3000/api/createCollection";		  
		  $api_param = array ( 		    
			"token" => $_SESSION['mongodb_token'], 
			"collectionName" => $collection_name['collection_name']);
									 
		  $ch = curl_init();
		  curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
		  curl_setopt($ch, CURLOPT_POST, 1);
		  curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
		  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		  $server_output = curl_exec ($ch);		
		  curl_close ($ch);
		  drupal_set_message($server_output);
		  
		  $json_result = json_decode($server_output, true);
		  if (isset($json_result['success'])) {
			if ($json_result['success'] == 1) {
			  drupal_set_message ("Added collection successfully");
			  $redirect_url = $base_url . '/mongodb_api/listcollection';
			  $response = new \Symfony\Component\HttpFoundation\RedirectResponse($redirect_url);
			  $response->send();
		      return;
			}
		  }	
		}
	}
}