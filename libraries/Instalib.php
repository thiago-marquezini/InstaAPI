<?php defined('BASEPATH') OR exit('No direct script access allowed');

set_time_limit(0);
date_default_timezone_set('UTC');

class Instalib
{
	public $instance = false;
	public $cronmode = false;

	public $basedir  = "/home/nitrogra/dashboard/sessions/";
	public $loggined = [ "userid" 	   => "",
						 "pk" 		   => "",
						 "username"    => "",
						 "password"    => "",
						 "fullname"    => "",
						 "profile_pic" => "",
						 "isverified"  => "",
						 "isprivate"   => "",
						 "isbusinnes"  => "",
						 "followers_count" => "",
						 "following_count" => ""
					   ];




	public function __construct()
	{
	    $this->CI =& get_instance();
	}



    public function init($username, $password, $cronmode, $renewsession = false)
    {
    	$this->loggined["username"] = $username;
    	$this->loggined["password"] = $password;

    	$this->loggined = json_decode(json_encode($this->loggined));
    	
    	$this->cronmode = $cronmode;

    	if ($renewsession)
    	{ 
    		if (file_exists($this->basedir . $this->loggined->username . '/' . $this->loggined->username . '/' . $this->loggined->username . '-cookies.dat'))
    		{ unlink($this->basedir . $this->loggined->username . '/' . $this->loggined->username . '/' . $this->loggined->username . '-cookies.dat'); }
    		if (file_exists($this->basedir . $this->loggined->username . '/' . $this->loggined->username . '/' . $this->loggined->username . '-settings.dat'))
    		{ unlink($this->basedir . $this->loggined->username . '/' . $this->loggined->username . '/' . $this->loggined->username . '-settings.dat'); }
		}

    	\InstagramAPI\Instagram::$allowDangerousWebUsageAtMyOwnRisk = true;
        $storage_config = [ "storage" => "file", "basefolder" => $this->basedir . $this->loggined->username ];

		try 
		{
			$this->instance = new \InstagramAPI\Instagram(false, false, $storage_config);
			$this->instance->setVerifySSL(true);

			return true;

		} catch (\Exception $Exception) 
		{
			$this->CI->session->set_userdata('exception', $Exception->getMessage());
			return false;
		}
    }






    public function login()
	{
		try
		{
			$loggined = $this->instance->login($this->loggined->username, $this->loggined->password);

		    if ($loggined !== null && $loggined->isTwoFactorRequired()) 
		    {
		    	if (!$this->cronmode)
		    	{ $this->CI->session->set_userdata('exception', array("i2fauth" => $loggined->getTwoFactorInfo()->getTwoFactorIdentifier())); }
		    	return false;

		    } else
		    {
		    	try
				{
					$userinfo = $this->instance->people->getInfoByName($this->loggined->username);

					$this->loggined->userid = $userinfo->getUser()->getUser_id();
					$this->loggined->pk = $userinfo->getUser()->getPk();
					$this->loggined->username = $userinfo->getUser()->getUsername();
					$this->loggined->password = $this->loggined->password;
					$this->loggined->fullname = $userinfo->getUser()->getFull_name();
					$this->loggined->profile_pic = $userinfo->getUser()->getProfile_pic_url();
					$this->loggined->isverified = $userinfo->getUser()->getIs_verified();
					$this->loggined->isprivate = $userinfo->getUser()->getIs_private();
					$this->loggined->isbusinnes = $userinfo->getUser()->getIs_business();
					$this->loggined->followers_count = $userinfo->getUser()->getFollowerCount();
					$this->loggined->following_count = $userinfo->getUser()->getFollowingCount();


					if ($this->CI->instauser_model->isregistered($this->loggined->username))
			    	{
			    		$this->CI->instauser_model->update($this->loggined->username, json_decode(json_encode($this->loggined), true));

			    	} else
			    	{
			    		$this->CI->instauser_model->register(json_decode(json_encode($this->loggined), true));
			    	}

			    	return true;

				} catch (\Exception $Exception) 
				{ 
				    $this->CI->session->set_userdata('exception', "Houve uma falha ao obter as informações do perfil.");
				    return false;
				}

		    }

		} catch (\Exception $Exception) 
		{
			
			if ($Exception instanceof InstagramAPI\Exception\ChallengeRequiredException) 
			{
				if (!$this->cronmode)
				{
				
					$Challenge = $this->instance->request(substr($Exception->getResponse()->getChallenge()->getApiPath(), 1))->setNeedsAuth(false)
										->addPost('choice', 0)
										->addPost('_uuid', $this->instance->uuid)
										->addPost('guid', $this->instance->uuid)
										->addPost('device_id', $this->instance->device_id)
										->addPost('_uid', $this->instance->account_id)
										->addPost('_csrftoken', $this->instance->client->getToken())
										->getDecodedResponse();

				    if (is_array($Challenge)) 
				    {
				        if (!$Challenge['status'] == "fail")
				        {
							$this->CI->session->set_userdata('exception', array("challenge" => array("uid" => $Challenge['user_id'], 
																								 	 "cid" => $Challenge['nonce_code'])));
							return false;

				        } else
				        {
				        	$this->CI->session->set_userdata('exception', "Houve uma falha ao solicitar o código SMS. #E1");
				        	return false;
				        }
				            
				    } else
				    {
				        $this->CI->session->set_userdata('exception', "Houve uma falha ao solicitar o código SMS. #E2");
				        return false;
				    }

				}

		    } else
		    {
				$this->CI->session->set_userdata('exception', $Exception->getMessage());
				return false;
		    }
			
		}
	}




	public function i2fauth($i2fauth, $i2fcode)
	{
		try 
		{
			$this->instance->finishTwoFactorLogin($this->loggined->username, $this->loggined->password, $i2fauth, $i2fcode);
			return true;

		} catch (\Exception $Exception) 
		{
			$this->CI->session->set_userdata('exception', $Exception->getMessage());
			return false;
		}
	}




	public function challenge($uid, $cid, $code)
	{
		try 
		{
		    $ChallengeResp = $this->instance->request("challenge/".$uid."/".$cid."/")
		    							->setNeedsAuth(false)
		    							->addPost("security_code", $code)
		    							->addPost('_uuid', $this->instance->uuid)->addPost('guid', $this->instance->uuid)
		    							->addPost('device_id', $this->instance->device_id)
										->addPost('_uid', $this->instance->account_id)
										->addPost('_csrftoken', $this->instance->client->getToken())
										->getDecodedResponse();

		    if (is_array($ChallengeResp)) 
		    {
		        //if ($ChallengeResp['status'] == "ok" && (int)$ChallengeResp['logged_in_user']['pk'] === (int)$uid)
		        if ($ChallengeResp['status'] == "ok")
		        { return true; }
		    } 

		} catch (Exception $Exception) 
		{
			$this->CI->session->set_userdata('exception', $Exception->getMessage());
			return false;
		}
	}















	public function read_followers($lastmaxid)
	{
		$maxid = ($lastmaxid === null) ? null : $lastmaxid;
		$rank_token = \InstagramAPI\Signatures::generateUUID();
		$followersarr = [];

		try 
		{   
			$followers = $this->instance->people->getFollowers($this->loggined->pk, $rank_token, $maxid);

			foreach($followers->getUsers() as $follower)
			{
				$followerinfo = 
				[
					"owner_pk" => $this->loggined->pk,
					"owner_username" => $this->loggined->username,
					"foundat" => date("Y-m-d H:i:s"),
					"userid" => $follower->getUser_id(),
					"pk" => $follower->getPk(),
					"username" => $follower->getUsername(),
					"full_name" => $follower->getFull_name(),
					"is_private" => $follower->getIs_private(),
					"profile_pic_url" => $follower->getProfile_pic_url(),
					"is_verified" => $follower->getIs_verified(),
				];

				$followersarr[] = $followerinfo;
				
			}

			$maxid = $followers->getNextMaxId();

			$success =
			[
				"maxid" => $maxid,
				"followers" => $followersarr
			];

			return $this->CI->instahelper->returnjson($success);

		} catch (\Exception $Exception) 
		{ return false; }
	}


	public function like_from_target($target, $params, $maxlike)
	{
	    $search = json_decode($params);
	    $rank_token = \InstagramAPI\Signatures::generateUUID();
	    
	    $liked_medias = [];

	    $like_module = "feed_timeline";
	    $like_extra_data = [];
	    $feed = false;

	    if ($target == "hashtag") 
	    {
	        $hashtag = str_replace("#", "", trim($search->hashtag));

	        $like_module = "feed_contextual_hashtag";
	        $like_extra_data["hashtag"] = $hashtag;

	        try 
	        { $feed = $this->instance->hashtag->getFeed($hashtag, $rank_token);
	        } catch (\Exception $e) 
	        { return false; }

	        $items = $feed->getItems();

	    } else if ($target == "location") 
	    {
	        $like_module = "feed_contextual_location";
	        $like_extra_data['location_id'] = $search->location;

	        try 
	        { $feed = $this->instance->location->getFeed($search->location, $rank_token);
	        } catch (\Exception $e) 
	        { return false; }

	        $items = $feed->getItems();

	    } else if ($target == "person") 
	    {
	        $like_module = "profile";
	        $like_extra_data['username'] = $search->username;
	        $like_extra_data['user_id'] = $search->person;

	        try 
	        { $feed = $this->instance->timeline->getUserFeed($search->person);
	        } catch (\Exception $e) 
	        { return false; }

	        $items = $feed->getItems();
	    }

	    if (count($items) == 0) 
	    { return false; }
	    
	    shuffle($items);
	    $count = 0;
		
		foreach ($items as $item) 
	    {
	    	if ($count >= $maxlike)
	    		break;

	        if ($item->getId() && !$item->getHasLiked())  
	        {
	            if ($item->getCode())
	            {
	                $media_id = $item->getId();
	                
	                if (!$media_id == null) 
	                {
	                	$like = false;
	                	try 
					    { 
					    	$like = $this->instance->media->like($media_id, $like_module, $like_extra_data);

					    	$liked_medias[] = array("media_id" => $media_id,
				        							"media_code" => $item->getCode(),
				        							"media_type" => $item->getMediaType(),
				        							"media_thumb" => $this->mediathumburl($item),
				        							"media_user" => array("pk" => $item->getUser()->getPk(), 
				        												  "username" => $item->getUser()->getUsername(), 
				        												  "full_name" => $item->getUser()->getFullName())
				        					   	   );

					    } catch (\Exception $Exception) { return false; }

					    if ($like)
					    { if (!$like->isOk()) { return false; } }

						$count++;
	                }
	            }
	        }
	    }

	    return $liked_medias;
	    
	}


	public function follow_from_target($target, $params, $maxfollow)
	{
		$followed = [];
        $search = json_decode($params);
        $rank_token = \InstagramAPI\Signatures::generateUUID();

        if ($target == "hashtag") 
        {
            try 
            { $feed = $this->instance->hashtag->getFeed(str_replace("#", "", trim($search->hashtag)), $rank_token); } 
            catch (\Exception $e) { return false; }

            if (count($feed->getItems()) <= 0) 
            { return false; }

        	$itemcount = 0;
            foreach ($feed->getItems() as $item) 
            {
                if (empty($item->getUser()->getFriendshipStatus()->getFollowing()) && 
                    empty($item->getUser()->getFriendshipStatus()->getOutgoingRequest()) &&
                    $item->getUser()->getPk() != $this->loggined->pk) 
                {
                	if ($itemcount >= $maxfollow) { break; }

                    $follow_pk = $item->getUser()->getPk();

                    if (!$this->is_following($follow_pk))
					{
	                    $resp = false;
				        try 
				        { 
				        	$resp = $this->instance->people->follow($follow_pk);

				        	$followed[] = array("pk" => $follow_pk,
				        						"username" => $item->getUser()->getUsername(),
				        						"full_name" => $item->getUser()->getFullName(),
				        						"profile_pic_url" => $item->getUser()->getProfilePicUrl(),
				        						"is_private" => $item->getUser()->getIs_private()
				        					   );

				        } catch (\Exception $e) 
				        { return false; }

				        if (!$resp->isOk()) 
					    { return false; }

						$itemcount++;
					}

                }
            }


        } else if ($target == "location")
        {

			$feed = false;
            try 
            { $feed = $this->instance->location->getFeed($search->location, $rank_token); } 
            catch (\Exception $e) { return false; }

            if (count($feed->getItems()) <= 0) 
            { return false; }

        	$itemcount = 0;
            foreach ($feed->getItems() as $item) 
            {
                if (empty($item->getUser()->getFriendshipStatus()->getFollowing()) && 
                    empty($item->getUser()->getFriendshipStatus()->getOutgoingRequest()) &&
                    $item->getUser()->getPk() != $this->loggined->pk)
                {
                	if ($itemcount >= $maxfollow) { break; }

                    $follow_pk = $item->getUser()->getPk();
                   	
                   	if (!$this->is_following($follow_pk))
					{
	                   	$resp = false;
				        try 
				        { 
				        	$resp = $this->instance->people->follow($follow_pk);

				        	$followed[] = array("pk" => $follow_pk,
				        						"username" => $item->getUser()->getUsername(),
				        						"full_name" => $item->getUser()->getFullName(),
				        						"profile_pic_url" => $item->getUser()->getProfilePicUrl(),
				        						"is_private" => $item->getUser()->getIs_private()
				        					   );

				        } catch (\Exception $e) 
				        { return false; }

				        if (!$resp->isOk()) 
					    { return false; }

	                   	$itemcount++;

	                }
                }
            }


        } else if ($target == "person") 
        {
        	$feed = false;
			try 
			{
				$feed = $this->instance->people->search($search->person, [], $rank_token);

			} catch (\Exception $Exception) 
			{ return false; }

			if (count($feed->getUsers()) <= 0) 
			{ return false; }

			$itemcount = 0;
			foreach ($feed->getUsers() as $User) 
			{
				if ($itemcount >= $maxfollow) { break; }

				$follow_pk = $User->getPk();

				if (!$this->is_following($follow_pk))
				{
					$resp = false;
					try 
					{ 	
					    $resp = $this->instance->people->follow($follow_pk);

						$followed[] = array("pk" => $User->getPk(), 
								 	 		"username" => $User->getUsername(),
									 		"full_name" => $User->getFullName(), 
									 		"profile_pic_url" => $User->getProfilePicUrl(), 
									 		"is_private" => $User->getIsPrivate()
					);

					} catch (\Exception $e) 
					{ return false; }

					if (!$resp->isOk()) 
					{ return false; }

					$itemcount++;
				}
				
			}
        }

        return $followed;
        
	}




	public function inline_followers()
	{
		$inlinefollowers = [];
		try
		{ $ai = $this->instance->people->getRecentActivityInbox();
        } catch (\Exception $Exception)
        { return false; }

        $stories = array_merge($ai->getNewStories(), $ai->getOldStories());
        $stories = array_reverse($stories);

        foreach ($stories as $s) 
        {
            if ($s->getType() == 3) 
            {
                if ($s->getArgs()->getProfileId())
                {
                	$follower_id = $s->getArgs()->getProfileId();

                	if ($s->getArgs()->getInlineFollow()) 
                	{
                    	$follower_username = $s->getArgs()->getInlineFollow()->getUserInfo()->getUsername();
                    	$follower_fullname = $s->getArgs()->getInlineFollow()->getUserInfo()->getFullName();
                    	$follower_profile_pic = $s->getArgs()->getInlineFollow()->getUserInfo()->getProfilePicUrl();

                    	$inlinefollowers[] = array("id" => $follower_id,
                    							   "username" => $follower_username, 
                    							   "fullname" => $follower_fullname, 
                    							   "profile_pic" => $follower_profile_pic
                    							  );
                	}

                } else
                {
                	if ($s->getArgs()->getSecondProfileId()) 
		            {
		                try 
		                {
		                    $second_profile_info = $this->instance->people->getInfoById($s->getArgs()->getSecondProfileId());

		                } catch (\Exception $e) { }

		                $follower_id = $s->getArgs()->getSecondProfileId();
		                $follower_username = $second_profile_info->getUser()->getUsername();
		                $follower_fullname = $second_profile_info->getUser()->getFullName();
		                $follower_profile_pic = $s->getArgs()->getSecondProfileImage();

		                $inlinefollowers[] = array("id" => $follower_id,
                    							   "username" => $follower_username, 
                    							   "fullname" => $follower_fullname, 
                    							   "profile_pic" => $follower_profile_pic
                    							  );
	            	}
                }
            }
        }

        return $inlinefollowers;

	}


	public function is_following($user_pk)
	{
		try
		{
           $friendships = $this->instance->people->getFriendships([$user_pk]);
		
        } catch (\Exception $Exception)
        { return false; }

        foreach ($friendships->getFriendshipStatuses()->getData() as $pk => $fs) 
        {
        	if ($fs->getOutgoingRequest() || $fs->getFollowing()) 
        	{ return true; } else { return false; }
        }

	}




	public function directmessage($follower_pk, $message)
	{
		try 
		{
            $this->instance->direct->sendText(["users" => [$follower_pk]], $message);

            return true;

        } catch (\Exception $Exception) 
        {
        	return false;
        }
	}


	public function mediathumburl($item)
	{
	    $media_thumb = null;

	    $media_type = empty($item->getMediaType()) ? null : $item->getMediaType();

	    if ($media_type == 1 || $media_type == 2) {
	        // Photo (1) OR Video (2)
	        $media_thumb = $item->getImageVersions2()->getCandidates()[0]->getUrl();
	    } else if ($media_type == 8) {
	        // ALbum
	        $media_thumb = $item->getCarouselMedia()[0]->getImageVersions2()->getCandidates()[0]->getUrl();
	    }    

	    return $media_thumb;
	}


}