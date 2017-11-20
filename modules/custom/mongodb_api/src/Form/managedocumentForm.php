<?php
namespace Drupal\mongodb_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;

class managedocumentForm extends FormBase {
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
	  
	  if (isset($_GET['mongodb_collection'])) {
		  $document_id = $_GET['document_id'];
		  $api_endpointurl = "ec2-54-210-86-147.compute-1.amazonaws.com:3000/api/collections/" . $_GET['mongodb_collection'] ."/findByID";
		  //	$api_endpointurl = "127.0.0.1:3000/api/collections/" . $_GET['mongodb_collection'] ."/findByID";
		  $api_param = array ( "token" => $_SESSION['mongodb_token'], "id" => $document_id);
									 
		  $ch = curl_init();
		  curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
		  curl_setopt($ch, CURLOPT_POST, 1);
		  curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
		  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		  $server_output = curl_exec ($ch);		
		  curl_close ($ch);
	//	  drupal_set_message($server_output);
	  }
	
	  $name_field = $form_state->get('num_document');
	  $form['api_result'] = array (
		'#type' => 'markup',
		'#markup' => mongodb_parseJSON($server_output),
	 );
	 
      $form['#tree'] = TRUE;
	  $form['#attached']['library'][] = 'mongodb_api.customcss'; 	  
        
      $form['document'] = [
       '#type' => 'fieldset',
     //  '#title' => $this->t('  [Key - Value]  '),
       '#prefix' => "<div id='names-fieldset-wrapper'>",
       '#suffix' => '</div>',
      ];

	  $json_result = json_decode($server_output, true);	
	  $initial_state = 0;
	  if (count ($json_result) > 0 ) {	
		$i=0;	
		if (empty($name_field)) { 			
			$form_state->set('num_document', count($json_result)- 1);
			$initial_state = $form_state->get('num_document');
		}
		
		foreach($json_result as $resultkey => $resultValue):			
			if (($resultkey != "_id") && ($i < $form_state->get('num_document'))) {								
				$form['document'][$i]['key'] = array(
					'#type' => 'textfield',      
					'#required' => FALSE,
					'#default_value' => $resultkey,	 
					'#class' => 'value-field',
					'#attributes' => array('style' => 'float: left; max-width: 350px; margin: 10px;'),
					'#prefix' => '<div class="clearboth">',       					
					'#theme_wrappers' => array(),
					'#size' => 2000,
				);
				if (!is_array($resultValue)) {
					$form['document'][$i]['valuee'] = array(
						'#type' => 'textfield',      
						'#required' => FALSE,
						'#default_value' => $resultValue,	  
						'#class' => 'value-field',	  
						'#attributes' => array('style' => 'float: left; max-width: 350px; margin: 10px;'),						
						'#suffix' => '</div><br>',
						'#theme_wrappers' => array(),
						'#size' => 2000,
					);	
				} else {
					/*$form['document'][$i]['valuee'] = array(
						'#type' => 'markup',      						
				'#markup' => "<div style='float:left;'><a href='/managedocument?mongodb_collection=products&document_id=58e7bc061dae736fbb68b29d&fieldval=" . json_encode($resultValue) . "'>{" . count($resultValue) . "}</a></div>",	  						
						'#prefix' => '<td>',
						'#suffix' => '</td></tr>',
					);	*/
					
					if (count($resultValue) > 1)
						$fields_text = "fields";
					else
						$fields_text = "field";
					
					$form['document'][$i]['valuee'] = array(
 
     '#type' => 'link',
 
     '#title' => "{" . count($resultValue) . " " . $fields_text. "}",
 
     '#url' => \Drupal\Core\Url::fromRoute('mongodb_api.subdocument', ['mongodb_collection' => $_GET['mongodb_collection'], 'document_id' => $_GET['document_id'], 'editkey' => $resultkey]),
	 
     '#attributes' => [
 
       'class' => ['use-ajax'],
 
       'data-dialog-type' => 'modal',
 
       'data-dialog-options' => \Drupal\Component\Serialization\Json::encode([
 
         'width' => 900,
 
       ]),
 
     ],	
	 '#prefix' => "<div class='mongodb_subform_list'>",
	 '#suffix' => '</div></div><br>',
   );
	
				}
				$i++;
			}
		endforeach;	

		$new_field = $form_state->get('num_document') - $i;
		for($newi = 0; $newi < $new_field; $newi++){
			$form['document'][$i]['key'] = array(
				'#type' => 'textfield',      
				'#required' => FALSE,	  	  
				'#class' => 'value-field',
				'#attributes' => array('style' => 'float: left; max-width: 350px; margin: 10px;'),
				'#prefix' => '<div class="clearboth">',       				
				'#theme_wrappers' => array(),
				'#size' => 2000,
			);
			$form['document'][$i]['valuee'] = array(
				'#type' => 'textfield',      
				'#required' => FALSE,	  	  
				'#class' => 'value-field',	  
				'#attributes' => array('style' => 'float: left; max-width: 350px; margin: 10px;'),				
				'#suffix' => '</div><br>',
				'#theme_wrappers' => array(),
				'#size' => 2000,
			);	
	
			$i++;
		}				
	} else {
		$form['noelement'] = array(
			'#type' => 'markup',
			'#markup' => "No document selected. <a href='" . $base_url . "/mongodb_api/listdocument'>Select Document</a>",	
		);					
	}
	$form['document']['actions'] = [
		'#type' => 'actions',
		'#class' => 'clearboth',
	];

	$form['document']['actions']['add_name'] = [
		'#type' => 'submit',
        '#value' => t('Add one more'),
        '#submit' => array('::addOne'),
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => "names-fieldset-wrapper",		
		],		
		'#prefix' => '<div class="clearboth">',       
    ];
    if ($form_state->get('num_document') > 1) {
        $form['document']['actions']['remove_name'] = [
          '#type' => 'submit',		 
          '#value' => t('Remove one'),
          '#submit' => array('::removeCallback'),
          '#ajax' => [
            'callback' => '::addmoreCallback',
            'wrapper' => "names-fieldset-wrapper",
          ],		  
		  '#suffix' => '</div><br>',
        ];
	}
	$form_state->setCached(FALSE);

	$form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    return $form;
  }
  

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
	     $updateWith = "{";
		 $document_values = $form_state->getValues("document");
		 
		 foreach($document_values['document'] as $document_value)
		 {
			 if (isset($document_value['valuee'])) {
				 if ($document_value['valuee'] != "") {
					$updateWith .= '"' . $document_value['key'] . '":"' . $document_value['valuee'] . '",';
				 }
			 }
		 }
		 $updateWith = substr($updateWith,0, strlen($updateWith)-1) . "}";		 
	   
		  $api_endpointurl = "ec2-54-210-86-147.compute-1.amazonaws.com:3000/api/collections/" . $_GET['mongodb_collection'] ."/update";
		  //	$api_endpointurl = "127.0.0.1:3000/api/collections/" . $_GET['mongodb_collection'] ."/findByID";
		  $api_param = array ( 
		    "query" => '{"_id":"'.$_GET['document_id'].'"}', 
			"token" => $_SESSION['mongodb_token'], 
			"updateWith" => $updateWith);
									 
		  $ch = curl_init();
		  curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
		  curl_setopt($ch, CURLOPT_POST, 1);
		  curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
		  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		  $server_output = curl_exec ($ch);		
		  curl_close ($ch);
		 
	  drupal_set_message($server_output);
  }


/**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_document');
    return $form['document'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_document');
    $add_button = $name_field + 1;
    $form_state->set('num_document', $add_button);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_document');
    if ($name_field > 1) {
      $remove_button = $name_field - 1;
      $form_state->set('num_document', $remove_button);
    }
    $form_state->setRebuild();
  }
  

}