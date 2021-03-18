<?php
defined('BASEPATH') OR exit('No direct script access allowed');

set_time_limit(0);
date_default_timezone_set('UTC');

class Instatasks extends CI_Controller 
{
	public $basedir = "/home/nitrogra/dashboard/sessions/";


    public function run()
    {
    	$this->load->library('instalib');
    	$this->load->library('instahelper');

    	$this->load->model('instauser_model');
    	$this->load->model('instatasks_model');
    	$this->load->model('instafollowers_model');

		$params = json_decode($this->input->raw_input_stream);

		// Check parameters
		if (!$this->instahelper->checkparams("username,password", $params))
		{ return $this->instahelper->failure($this->session->exception); }

		// Initialize Instagram Library
		if (!$this->instalib->init($params->username, $params->password, false))
		{ return $this->instahelper->failure($this->session->exception); }

		// Login to Instagram Account
		if (!$this->instalib->login())
		{ return $this->instahelper->failure($this->session->exception); }
	
		echo 'success';

    	
    }








    /* 			        */
	/* Public Functions */
	/* 			        */


	// newtask() - Create a new task and enqueue for execution.
	//
	//$input =
	//[
	//	"username"  	=> "",
	//	"password"  	=> "",
	//	"task_type"		=> "", // current_followers_list, find_new_followers, send_direct_campaign
	//	"task_ref"  	=> "",

	//	"current_followers_list" => array("fetch_verifiedonly" => "0",
	//  								  "fetch_businessonly" => "0",
	//  								  "fetch_privateonly"  => "0",
	//  								  "fetch_speed"  	   => "0",
	//  								  "fetch_count"   	   => "10"), // -1 = a tarefa é executada até que todos
	//																		     os seguidores atuais sejam lidos.

	//	"follow_new_people"      => array("segment_ref"         => "0",
	//  								  "segment_targets"     => "0",
	//  								  "segment_speed" 	    => "0",
	//  								  "segment_list" 	    => "0",
	//  								  "segment_hashtags"    => "0",
	//  								  "segment_locations"   => "0",
	//  								  "segment_people" 	    => "0",
	//  								  "segment_count"       => "10"), // -1 = a tarefa é executada constantemente.

	//	"send_direct_campaign"   => array("campaign_ref"       => "0",
	//  								  "campaign_speed" 	   => "0",
	//  								  "campaign_targets"   => "0",
	//  								  "campaign_hashtags"  => "0",
	//  								  "campaign_locations" => "0",
	//  								  "campaign_people"    => "0",
	//  								  "campaign_message"   => "0",
	//  								  "campaign_count"     => "10"), // -1 = a tarefa é executada até que todos
	//																			 os seguidores receberam a mensagem.


	//];
	public function newtask()
	{
		$this->load->library('instalib');
    	$this->load->library('instahelper');
    	
		$this->load->model('instauser_model');
		$this->load->model('instatasks_model');

		if (!$this->_validate_postfields('username,password,task_type,task_segment')) 
		{
			$exception = $this->_set_exception("É necessário preencher todas os campos (username,password,task_type,task_segment).");
		    return $this->instahelper->outputjson($exception);
		}

		if (!$this->instauser_model->isregistered($this->input->post('username')))
		{
			$exception = $this->_set_exception("A conta do Instagram não está cadastrada no sistema.");
		    return $this->instahelper->outputjson($exception);
		}

		$this->userinfo = $this->instauser_model->getuserinfo($this->input->post('username'));
		if (!($this->userinfo->password == md5($this->input->post('password'))))
		{
			$exception = $this->_set_exception("A senha da conta do Instagram difere da senha cadastrada no sistema.");
		    return $this->instahelper->outputjson($exception);
		}

		$task_type = $this->input->post('task_type');
		$task_ref  = ($this->input->post('task_ref') != "") ? $this->input->post('task_ref') : null;

		$task_segment = $this->input->post('task_segment');
		$task_segment = json_decode($task_segment);

		$task_createdat = date("Y-m-d H:i:s");
		$task_id = md5($this->input->post('username') . rand(10, 1000) . $task_createdat);

		$task_hashtags = array();
		$task_locations = array();
		$task_people = array();


		switch ($task_type) 
		{
			/* Get current followers list */
			case 'current_followers_list':
			{
				break;
			}



			/* Follow new people based on hashtags, locations and people */
			case 'follow_new_people':
			{
				if ((!isset($task_segment->segment_targets)) || ($task_segment->segment_targets == "") 
				 || (!isset($task_segment->segment_speed)) 	 || ($task_segment->segment_speed   == "")
				 || (!isset($task_segment->segment_list))    || ($task_segment->segment_list    == ""))
				{
					$exception = $this->_set_exception("Segmento inválido, verifique os campos segment_targets, segment_speed e segment_list.");
				    return $this->instahelper->outputjson($exception);
				}

				$task_segment_info =
				[
					"segment_targets" => $task_segment->segment_targets,
					"segment_speed" => $task_segment->segment_speed,
					"segment_list" => $task_segment->segment_list,
					"segment_count" => $task_segment->segment_count
				];

				$task_segment_targets = explode(',', $task_segment->segment_targets);
				foreach ($task_segment_targets as $Target) 
				{
					if ($Target == "hashtag")
					{
						if (strlen($task_segment->segment_hashtags) <= 0)
						{ return $this->instahelper->outputjson($this->_set_exception("O campo 'segment_hashtags' está vazio.")); }

						$task_segment->segment_hashtags = explode(',', $task_segment->segment_hashtags);
						if (count($task_segment->segment_hashtags) == 0)
						{ return $this->instahelper->outputjson($this->_set_exception("O campo 'segment_hashtags' está vazio.")); }

						foreach ($task_segment->segment_hashtags as $Hashtag) 
						{ $task_hashtags[] = $Hashtag; }

						if (count($task_hashtags) > 0) { $task_segment_info["segment_hashtags"] = $task_hashtags; }
					}

					if ($Target == "location")
					{
						if (strlen($task_segment->segment_locations) <= 0)
						{ return $this->instahelper->outputjson($this->_set_exception("O campo 'segment_locations' está vazio.")); }

						$task_segment->segment_locations = explode(',', $task_segment->segment_locations);
						if (count($task_segment->segment_locations) == 0)
						{ return $this->instahelper->outputjson($this->_set_exception("O campo 'segment_locations' está vazio.")); }

						foreach ($task_segment->segment_locations as $Location) 
						{ $task_locations[] = $Location; }

						if (count($task_locations) > 0) { $task_segment_info["segment_locations"] = $task_locations; }

					}

					if ($Target == "people")
					{
						if (strlen($task_segment->segment_people) <= 0)
						{ return $this->instahelper->outputjson($this->_set_exception("O campo 'segment_people' está vazio.")); }

						$task_segment->segment_people = explode(',', $task_segment->segment_people);
						if (count($task_segment->segment_people) == 0)
						{ return $this->instahelper->outputjson($this->_set_exception("O campo 'segment_people' está vazio.")); }

						foreach ($task_segment->segment_people as $Person) 
						{ $task_people[] = $Person; }

						if (count($task_people) > 0) { $task_segment_info["segment_people"] = $task_people; }
					}
				}
				

				$task_progress_info =
				[
					"maximum"      => $task_segment->segment_count,
					"current" 	   => 0,
					"percent"	   => 0,
					"followed" 	   => ""
				];

				$task =  
				array(
					"task_id" => $task_id,
					"task_createdat" => $task_createdat,
					"task_type" => $task_type,
					"task_ref" => $task_ref,
					"task_segment" => $task_segment_info,
					"task_progress" => $task_progress_info,
					"task_paused" => 0, 
					"task_stopped" => 0, 
					"task_finished" => 0
				);
				
				$createtask = $this->instatasks_model->createtask($this->userinfo->pk, $task);
				if ($createtask != null)
				{
					$exception = $this->_set_exception($createtask);
				    return $this->instahelper->outputjson($exception);
				}

				$results =
				[
					"status" => "success",
					"task" => $task
				];

				return $this->instahelper->outputjson($results);

				break;
			}



			/* Fetch news follwers from the feed and check if the new follower came from 'follow_new_people'. */
			case 'fetch_new_followers':
			{
				break;
			}

			/* Send direct messages to a specified follower list. */
			case 'send_direct_campaign':
			{
				break;
			}
				
			
			default:
			{
				$results =  
				[ 
					"status" => "no-action"
				];

				return $this->instahelper->outputjson($results);

				break;
			}
		}

	}



	// updatetask() - Change task state (running, paused or stopped) and information (targets, hashtags, locations etc).
	//
	// $input =
	//[
	//	"username" => "",
	//	"password" => "",
	//	"task_id"     => "",
	//	"task_update"  => ""
	//]
	public function updatetask()
	{
		$this->load->library('instalib');
    	$this->load->library('instahelper');

		$this->load->model('instauser_model');
		$this->load->model('instatasks_model');

		if (!$this->_validate_postfields('username,password,task_action,task_id')) 
		{
			$exception = $this->_set_exception("É necessário preencher todas os campos (username,password,task_id).");
		    return $this->instahelper->outputjson($exception);
		}

		if (!$this->instauser_model->isregistered($this->input->post('username')))
		{
			$exception = $this->_set_exception("A conta do Instagram não está cadastrada no sistema.");
		    return $this->instahelper->outputjson($exception);
		}

		$this->userinfo = $this->instauser_model->getuserinfo($this->input->post('username'));
		if (!($this->userinfo->password == md5($this->input->post('password'))))
		{
			$exception = $this->_set_exception("A senha da conta do Instagram difere da senha cadastrada no sistema.");
		    return $this->instahelper->outputjson($exception);
		}

		switch ($this->input->post('task_action')) 
		{
			case 'resume':
			{
				$resume = $this->instatasks_model->resume_task($this->userinfo->pk, $this->input->post('task_id'));
				break;
			}

			case 'pause':
			{
				$pause = $this->instatasks_model->pause_task($this->userinfo->pk, $this->input->post('task_id'));
				break;
			}

			case 'stop':
			{
				$stop = $this->instatasks_model->stop_task($this->userinfo->pk, $this->input->post('task_id'));
				break;
			}

			case 'update':
			{
				if (!$this->_validate_postfields('task_update')) 
				{
					$exception = $this->_set_exception("É necessário preencher todos os campos (task_update).");
				    return $this->instahelper->outputjson($exception);
				}

				break;
			}
			
			default:
			{
				$exception = $this->_set_exception("no-action");
				return $this->instahelper->outputjson($exception);

				break;
			}
		}

		$results =  
		[ 
			"status" => "success"
		];

		return $this->instahelper->outputjson($results);
	}

}