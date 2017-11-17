<?php
namespace Drupal\mongodb_api\subForm;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;
use \Drupal\Core\Ajax\AjaxResponse;
use \Drupal\Core\Ajax\CloseModalDialogCommand;

class managesubdocumentForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'manage_subdocument';
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
		 // drupal_set_message($server_output);
	  }
	  
	  
	  $json_result = json_decode($server_output, true); 
	  $queryKeys = explode(".", $_GET['editkey']);
	  
	  foreach($queryKeys as $queryKey)
		$json_result = $json_result[$queryKey];
	  
	  
	//$form = parent::buildForm($form, $form_state);
	  $name_field = $form_state->get('num_subdocument');
	  $form['#prefix'] = '<div id="subdocument_wrapper">';
		$form['#suffix'] = '</div>';
    // $form['subdocument']['#tree'] = TRUE;
        
      $form['subdocument'] = [
       '#type' => 'fieldset',
     //  '#title' => $this->t('  [Key - Value]  '),
       '#prefix' => "<div id='names-fieldset-wrapper-sub'>",
       '#suffix' => '</div>',
	   '#tree' => TRUE,
      ];

	  
	  $initial_state = 0;	  
	  
	  if (count ($json_result) > 0 ) {	
		$i=0;	
		if (empty($name_field)) { 			
			$form_state->set('num_subdocument', count($json_result));
			$initial_state = $form_state->get('num_subdocument');
		}
		
		foreach($json_result as $resultkey => $resultValue):			
			if (($resultkey != "_id") && ($i < $form_state->get('num_subdocument'))) {								
				$form['subdocument'][$i]['key'] = array(
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
					$form['subdocument'][$i]['valuee'] = array(
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
					
					$form['subdocument'][$i]['valuee'] = array(
 
     '#type' => 'link',
 
     '#title' => "{" . count($resultValue) . " " . $fields_text. "}",					
 
     '#url' => \Drupal\Core\Url::fromRoute('mongodb_api.subdocument', ['mongodb_collection' => $_GET['mongodb_collection'], 'document_id' => $_GET['document_id'], 'editkey' => $_GET['editkey'] ."." . $resultkey]),
	 
	 
	 //\Drupal\Core\Link::fromTextAndUrl(t('link title'), "/managedocument?mongodb_collection=products&document_id=58e7bc061dae736fbb68b29d&fieldval=" . json_encode($resultValue)),  // \Drupal\Core\Url::fromRoute('node.add', ['node_type' => 'mongodb_information']),
 
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

		$new_field = $form_state->get('num_subdocument') - $i;
		for($newi = 0; $newi < $new_field; $newi++){
			$form['subdocument'][$i]['key'] = array(
				'#type' => 'textfield',      
				'#required' => FALSE,	  	  
				'#class' => 'value-field',
				'#attributes' => array('style' => 'float: left; max-width: 350px; margin: 10px;'),
				'#prefix' => '<div class="clearboth">',    				
				'#theme_wrappers' => array(),
				'#size' => 2000,
			);
			$form['subdocument'][$i]['valuee'] = array(
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
	$form['subdocument']['actionss'] = [
		'#type' => 'actions',
	];

	$form['subdocument']['actionss']['add_name'] = [
		'#type' => 'submit',
        '#value' => t('Add one more'),
        '#submit' => array('::addOne'),
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => "names-fieldset-wrapper-sub",		
      ],		
	  '#prefix' => '<div class="clearboth">', 
    ];
    if ($form_state->get('num_subdocument') > 1) {
        $form['subdocument']['actionss']['remove_name'] = [
          '#type' => 'submit',		 
          '#value' => t('Remove one'),
          '#submit' => array('::removeCallback'),
          '#ajax' => [
            'callback' => '::addmoreCallback',
            'wrapper' => "names-fieldset-wrapper-sub",
          ],
		  '#suffix' => '</div><br>',
        ];
	}
	$form_state->setCached(FALSE);

	$form['action']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
	 /* '#attributes' => [
        'class' => [
            'btn',
            'btn-md',
            'btn-primary',
            'use-ajax-submit'
        ]
    ],
    '#ajax' => [
        'wrapper' => 'subdocument_wrapper',
    ]*/
    ];
    return $form;
  }
  

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
		global $base_url;
	    $document_id = $_GET['document_id'];
	    $api_endpointurl = "ec2-54-210-86-147.compute-1.amazonaws.com:3000/api/collections/" . $_GET['mongodb_collection'] ."/findByID";
		$api_param = array ( "token" => $_SESSION['mongodb_token'], "id" => $document_id);
									 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $api_endpointurl);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($api_param));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec ($ch);		
		curl_close ($ch);
	  
		$json_result = json_decode($server_output, true);  
		$json_result = $json_result[$_GET['editkey']];
	  
	  $updateWith = '{';
		 $document_values = $form_state->getValues("subdocument");	
	
		 foreach($document_values['subdocument'] as $document_key => $document_value)
		 {
			 if ($document_key != 'actionss') {
				 if (isset($document_value['valuee'])) {
					 if ($document_value['valuee'] != "") {
						$updateWith .= '"' . $_GET['editkey'] . '.' . $document_value['key'] . '":"' . $document_value['valuee'] . '",';
					 } 				
				 }
			 }
		 }
		 $updateWith = substr($updateWith,0, strlen($updateWith)-1);		 
		 if ($updateWith != "" )
			$updateWith .= "}";
	   
	     if ($updateWith != "") {
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
		 } else {
		  drupal_set_message("Updated Successfully");
		 }
	  $redirect_url = $base_url . '/mongodb_api/managedocument?mongodb_collection=' . $_GET['mongodb_collection'] . '&document_id=' . $_GET['document_id'];
			  $response = new \Symfony\Component\HttpFoundation\RedirectResponse($redirect_url);
			  $response->send();
		      return;
	   //modalframe_close_dialog(array('redirect' => url('node/1')));
	   //$form_state->setRedirect('<front>');
	  
  /*$command = new CloseModalDialogCommand();
  $response = new AjaxResponse();
  $response->addCommand($command);
  return $response;*/
  }


/**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_subdocument');
    return $form['subdocument'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_subdocument');
    $add_button = $name_field + 1;
    $form_state->set('num_subdocument', $add_button);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_subdocument');
    if ($name_field > 1) {
      $remove_button = $name_field - 1;
      $form_state->set('num_subdocument', $remove_button);
    }
    $form_state->setRebuild();
  }
  

}