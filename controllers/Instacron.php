<?php
defined('BASEPATH') OR exit('No direct script access allowed');

set_time_limit(0);
date_default_timezone_set('UTC');

class Instacron extends CI_Controller 
{
	public $task_id = 0;

	private function sync_followers()
	{
		$lastmaxid = $this->instafollowers_model->list_get_pos($this->instalib->loggined->pk);
		$followers = $this->instalib->read_followers($lastmaxid);
		if (!$followers)
		{ return false; }

		$maxid = $followers->maxid;
		$followers = $followers->followers;

		if (count($followers) == 0) { return false; }

		foreach ($followers as $follower) 
		{ $this->instafollowers_model->list_insert($this->instalib->loggined->pk, json_decode(json_encode($follower), true)); }

		$this->instafollowers_model->list_set_pos($this->instalib->loggined->pk, ($maxid === null) ? (($lastmaxid == null) ? '0' : $lastmaxid) : $maxid);
	}

	private function sync_conversions()
	{
		$inlinefollowers = $this->instalib->inline_followers();

		if (count($inlinefollowers) == 0) { return false; }

		foreach ($inlinefollowers as $inlinefollower) 
		{
			if ($this->instafollowers_model->is_targeted_follow($this->instalib->loggined->pk, $inlinefollower["id"]))
			{
				$this->instafollowers_model->set_converted_follower($this->instalib->loggined->pk, $inlinefollower["id"]);

			} else
			{
				if (!$this->instafollowers_model->is_untargeted_follower($this->instalib->loggined->pk, $inlinefollower["id"]))
				{
					$this->instafollowers_model->set_untargeted_follower(json_decode(json_encode($this->instalib->loggined), true), $inlinefollower, $this->task_id);
				}
			}
		}
	}

	private function follow_targeted($target_type, $search_query, $count, $likefeed = false)
	{
		$follow_params =
		[ $target_type  => $search_query ];

		$followed_user = $this->instalib->follow_from_target($target_type, json_encode($follow_params), $count);
		if (!$followed_user)
		{ return false; }

		foreach ($followed_user as $user) 
		{
			if (!$this->instafollowers_model->is_targeted_follow($this->instalib->loggined->pk, $user["pk"]))
			{
				$this->instafollowers_model->set_targeted_follow(json_decode(json_encode($this->instalib->loggined), true), $user, $target_type, $search_query, $this->task_id);

				echo 'Followed: ' . $user["pk"] . ': ' . $user["username"] . '<br>';

				if ($likefeed)
				{
					$target = [ "person" => $user["pk"], "username" => $user["username"] ];
					$likedmedias = $this->instalib->like_from_target("person", json_encode($target), 1);

					if (!(count($likedmedias) == 0)) 
					{ 
						//echo '<pre>';
						//print_r($likedmedias);
						//echo '</pre><br>';
						//foreach ($likedmedias as $media) 
						//{ }
					}
				}
			}
		}
	}

	





	public function taskloop($task_user)
	{
		$filters =
		[
			"task_paused" => "0",
			"task_stopped" => "0",
			"task_finished" => "0"
		];

		$activetasks = $this->instatasks_model->get_tasks($this->instalib->loggined->pk, $filters);

		foreach ($activetasks as $activetask) 
		{
			$this->task_id = $activetask->task_id;

			switch ($activetask->task_type) 
			{
				case 'sync_followers':
				{
					$this->sync_followers();
					break;
				}
				case 'sync_conversions':
				{
					$this->sync_conversions();
					break;
				}

				case 'follow_targeted':
				{
					$task_segment = json_decode($activetask->task_segment);
					$task_progress = json_decode($activetask->task_progress);

					if (isset($task_segment->follow_targets) && is_array($task_segment->follow_targets))
					{
						foreach ($task_segment->follow_targets as $Target) 
						{
							switch ($Target) 
							{
								case 'hashtag':
								{
									if (isset($task_segment->follow_hashtags) && is_array($task_segment->follow_hashtags))
									{
										foreach ($task_segment->follow_hashtags as $Hashtag) 
										{
											//echo 'Follow Hashtag: ' . $Hashtag . '<br>';
											//$this->follow_targeted("hashtag", $Hashtag, 1, false);
										}
									}
									
									break;
								}
								case 'location':
								{
									if (isset($task_segment->follow_locations) && is_array($task_segment->follow_locations))
									{
										foreach ($task_segment->follow_locations as $Location) 
										{
											//echo 'Follow Location: ' . $Location . '<br>';
											$this->follow_targeted("location", $Location, 1, false);
										}
									}

									break;
								}
								case 'people':
								{
									if (isset($task_segment->follow_people) && is_array($task_segment->follow_people))
									{
										foreach ($task_segment->follow_people as $Person) 
										{
											//echo 'Follow Person: ' . $Person . '<br>';
											//$this->follow_targeted("person", $Person, 1, false);
										}
									}

									break;
								}
								
								default:
									break;
							}
						}
					}
					/*
					{"follow_targets":"hashtag,location,person",
					 "follow_speed":"slow",
					 "follow_list":"default",
					 "follow_count":"-1",
					 "follow_hashtags":["instagram","barbertech"],
					 "follow_locations":["Belo Horizonte","S\u00e3o Paulo"],
					 "follow_people":["Brad Pitt","Angelina Jolie"]}
					 */

					break;
				}

				default:
				break;
			}

			echo 'Task executed: ' . $activetask->task_type . '<br>';
			//echo $activetask->task_id . ' -> ' . $activetask->pk . ' -> ' . $task_user->password . '<br>';
			
		}
	}


	public function run()
	{

		$this->load->library('instalib');
    	$this->load->library('instahelper');
		$this->load->model('instauser_model');
    	$this->load->model('instatasks_model');
    	$this->load->model('instafollowers_model');

    	$active_users = $this->instauser_model->getactiveusers();

    	foreach ($active_users as $active_user) 
    	{
    		// Initialize Instagram Library
			if (!$this->instalib->init($active_user->username, $active_user->password, true))
			{ return $this->instahelper->failure($this->session->exception); }

			// Login to Instagram Account
			if (!$this->instalib->login())
			{ return $this->instahelper->failure($this->session->exception); }
			
			echo 'Followed by: ' . $this->instalib->loggined->followers_count . '.<br>';
			echo 'Following: ' . $this->instalib->loggined->following_count . '.<br>';

			$this->taskloop($active_user);

			//echo ($this->instalib->directmessage("146246658", "Bons sonhos bb :p Te amo <3")) ? 'Success.' : 'Failed.';	
			//$this->instahelper->outputjson(array("status" => "success"));
    	}

	}

	public function index()
	{
		
	}

}








