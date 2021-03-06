<?php
namespace Drupal\mongodb_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class DBConnectForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'DBConnectForm';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

	$form['database_ip'] = array(
      '#type' => 'textfield',
      '#title' => t('Enter IP of DB:'),
      '#required' => TRUE,
    );

    $form['database_name'] = array(
      '#type' => 'textfield',
      '#title' => t('DB Name:'),
      '#required' => TRUE,
    );

    $form['database_key'] = array (
      '#type' => 'file',
      '#title' => t('Upload Key'),
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Connect'),
      '#button_type' => 'primary',
    );
    return $form;
  }
  

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
	$_SESSION['mongodb_token'] = "";
    $api_param = array (
		'host' => $form['database_ip']['#value'],
		'dbName' => $form['database_name']['#value']
	);
	
	//$api_endpointurl = "ec2-54-210-86-147.compute-1.amazonaws.com:3000/api/connect";
	$api_endpointurl = "127.0.0.1:3000/api/connect";
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$server_output = curl_exec ($ch);		
	$json_result = json_decode($server_output, true);
	curl_close ($ch);
	if ($json_result['success'] == 1) {
		$_SESSION['mongodb_token'] = $json_result['token'];
		drupal_set_message (t('Success - Mongo DB connection establised.'));
		//drupal_goto('mongodb_api/collections');
		$form_state->setRedirect('mongodb_api.listcollection');
	}
	else
		drupal_set_message(t("Invalid Database information.  No connection establised to Mongo DB."), "error");
	
	
	
	
	
/*	$api_endpointurl = "ec2-54-210-86-147.compute-1.amazonaws.com:3000/api/collections";
	$api_param = array ( "token" => "56045c0a4e66e7ca");
	//$api_param = array ( "token" => $token);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$server_output = curl_exec ($ch);	
	$json_result = json_decode($server_output);
	//$form_state['values']['collections_list'] = array ( "#markup" => "testtttt");
	for($jcount = 0; $jcount < count($json_result); $jcount++)
	drupal_set_message($json_result[$jcount]->name);
	//$form_state->setRebuild();*/
	}


}