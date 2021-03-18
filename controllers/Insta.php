<?php
defined('BASEPATH') OR exit('No direct script access allowed');

set_time_limit(0);
date_default_timezone_set('UTC');

class Insta extends CI_Controller 
{

	// search() - Search for people, hashtags and locations.
	//
	// $input =
	//[
	//	"username" => "",
	//	"password" => "",
	//	"type"     => "",
	//	"hashtag"  => "",
	//	"location" => "",
	//	"people"   => ""
	//]
	public function search()
	{
		$this->load->library('instalib');
    	$this->load->library('instahelper');
    	$this->load->model('instauser_model');

    	$params = json_decode($this->input->raw_input_stream);

    	// Check parameters
		if (!$this->instahelper->checkparams("username,password,type", $params))
		{ return $this->instahelper->failure($this->session->exception); }

		// Initialize Instagram Library
		if (!$this->instalib->init($params->username, $params->password, false))
		{ return $this->instahelper->failure($this->session->exception); }

		// Login to Instagram Account
		if (!$this->instalib->login())
		{ return $this->instahelper->failure($this->session->exception); }

		$searchtype = $params->type;
		$rank_token = \InstagramAPI\Signatures::generateUUID();
		$found = [];
		$results = [];

		/* Search for Hashtag */
		if ($searchtype == "hashtag") 
		{
			if (!$this->instahelper->checkparams("hashtag", $params))
			{ return $this->instahelper->failure($this->session->exception); }

			$feed = false;
			try 
			{ 
				$feed = $this->instalib->instance->hashtag->search(str_replace("#", "", $params->hashtag), [], $rank_token);

			} catch (\Exception $Exception) 
			{ return $this->instahelper->failure($Exception->getMessage()); }

			if (count($feed->getResults()) <= 0) 
			{ return $this->instahelper->outputjson([ "status" => "success", "count" => 0 ]); }

			foreach ($feed->getResults() as $item) 
			{
				$found[] = array("id" => $item->getId(),
								 "name" => $item->getName(), 
								 "mediacount" => $item->getMediaCount());
			}

			$results = [ "status" => "success", 
						 "count"  => count($found), 
						 "items"  => $found ];

			return $this->instahelper->outputjson($results);


		/* Search for Location */
		} else if ($searchtype == "location") 
		{

			if (!$this->instahelper->checkparams("location", $params))
			{ return $this->instahelper->failure($this->session->exception); }

			$location = false;
			try 
			{ 
				$location = $this->instalib->instance->location->search(0, 0, $params->location)->getVenues(); 

			} catch (\Exception $Exception) 
			{  return $this->instahelper->failure($Exception->getMessage()); }

			if (count($location) <= 0) 
			{ return $this->instahelper->outputjson([ "status" => "success", "count" => 0 ]); }

			foreach ($location as $loc) 
			{
				$found[] = array("id" => $loc->getExternalId(), 
								 "name" => $loc->getName(),
								 "address" => $loc->getAddress(),
								 "lat" => $loc->getLat(), 
								 "lng" => $loc->getLng());
			}

			$results = [ "status" => "success", 
						 "count"  => count($found), 
						 "items"  => $found ];

			return $this->instahelper->outputjson($results);


		/* Search for People */
		} else if ($searchtype == "person") 
		{
			if (!$this->instahelper->checkparams("person", $params))
			{ return $this->instahelper->failure($this->session->exception); }

			$feed = false;
			try 
			{
				$feed = $this->instalib->instance->people->search($params->person, [], $rank_token);

			} catch (\Exception $Exception) 
			{ return $this->instahelper->failure($Exception->getMessage()); }

			if (count($feed->getUsers()) <= 0) 
			{ return $this->instahelper->outputjson([ "status" => "success", "count" => 0 ]); }

			foreach ($feed->getUsers() as $User) 
			{
				$found[] = array("username" => $User->getUsername(), 
							 	 "full_name" => $User->getFullName(),
								 "pk" => $User->getPk(), 
								 "profile_pic" => $User->getProfilePicUrl(), 
								 "isverified" => ($User->getIsVerified() == false) ? 0 : 1, 
								 "isbusinnes" => ($User->getIsBusiness() == false) ? 0 : 1, 
								 "isprivate" => ($User->getIsPrivate() == false) ? 0 : 1
				);
			}

			$results = [ "status" => "success", 
						 "count"  => count($found), 
						 "people"  => $found ];

			return $this->instahelper->outputjson($results);

		}
	}


	// discover() - Find hashtags (#), locations and people (@) related to a search query.
	//
	// $input =
	//[
	//	"username" => "",
	//	"password" => "",
	//	"query"	   => "" // 
	//]
	public function discover($latitude = null, $longitude = null)
	{
		$this->load->library('instalib');
    	$this->load->library('instahelper');
    	$this->load->model('instauser_model');

    	$params = json_decode($this->input->raw_input_stream);

    	// Check parameters
		if (!$this->instahelper->checkparams("username,password,query", $params))
		{ return $this->instahelper->failure($this->session->exception); }

		// Initialize Instagram Library
		if (!$this->instalib->init($params->username, $params->password, false))
		{ return $this->instahelper->failure($this->session->exception); }

		// Login to Instagram Account
		if (!$this->instalib->login())
		{ return $this->instahelper->failure($this->session->exception); }


		$hashtags = [];
		$locations = [];
		$people = [];

		$rank_token = \InstagramAPI\Signatures::generateUUID();

		try
		{
			$discover = $this->instalib->instance->discover->search($params->query, $latitude, $longitude, [], $rank_token);

		} catch (\Exception $Exception)
		{ return $this->instahelper->failure($Exception->getMessage()); }

		$discovered = $discover->getList();
		$hasmore = ($discover->getHasMore()) ? 1 : 0;
		$discovercount = count($discovered);

		if ($discovercount <= 0)
		{ return $this->instahelper->outputjson([ "status" => "success", "count" => 0 ]); }

		foreach ($discovered as $suggestion) 
		{ 
			$suggestion = json_decode(json_encode($suggestion));
			if (isset($suggestion->hashtag))
			{
				$hashtag = 
				[
					"id" => $suggestion->hashtag->id,
					"name" => $suggestion->hashtag->name,
					"media_count" => $suggestion->hashtag->media_count
				];

				$hashtags[] = $hashtag;
				$nextId[] = $suggestion->hashtag->id;
			}


			if (isset($suggestion->place))
			{
				$location = 
				[
					"pk"		 => $suggestion->place->location->pk,
					"source" 	 => $suggestion->place->location->external_source,
					"name" 		 => $suggestion->place->location->name,
					"title"   	 => $suggestion->place->title,
					"subtitle"   => $suggestion->place->subtitle,
					"lat"   	 => $suggestion->place->location->lat,
					"lng"   	 => $suggestion->place->location->lng,
					"address" 	 => $suggestion->place->location->address,
					"city"       => $suggestion->place->location->city,
					"short_name" => $suggestion->place->location->short_name,

				];

				$locations[] = $location;
				$nextId[] = $suggestion->place->location->pk;
			}

			if (isset($suggestion->user))
			{

				$person = 
				[
					"pk" => $suggestion->user->pk,
					"username" => $suggestion->user->username,
					"full_name" => $suggestion->user->full_name,
					"is_private" => $suggestion->user->is_private,
					"is_verified" => $suggestion->user->is_verified,
					"profile_pic_url" => $suggestion->user->profile_pic_url,
					"follower_count" => $suggestion->user->follower_count,
					"mutual_followers_count" => $suggestion->user->mutual_followers_count,
					"is_following" => $suggestion->user->friendship_status->following
				];

				$people[] = $person;
				$nextId[] = $suggestion->user->pk;
			}

		}

		$result =
		[
			"status" => "success",
			"count" => $discovercount,
			"hasmore" => $hasmore,
			"next" => $nextId,
			"hashtags" => $hashtags,
			"locations" => $locations,
			"people" => $people
		];

		$this->instahelper->outputjson($result);

	}


	// related() - Get users related to another user
	//
	// $input =
	//[
	//	"username" => "",
	//	"password" => "",
	//	"user"	   => ""
	//]
	public function related()
	{

		$this->load->library('instalib');
    	$this->load->library('instahelper');
    	$this->load->model('instauser_model');

    	$params = json_decode($this->input->raw_input_stream);

    	// Check parameters
		if (!$this->instahelper->checkparams("username,password,user", $params))
		{ return $this->instahelper->failure($this->session->exception); }

		// Initialize Instagram Library
		if (!$this->instalib->init($params->username, $params->password, false))
		{ return $this->instahelper->failure($this->session->exception); }

		// Login to Instagram Account
		if (!$this->instalib->login())
		{ return $this->instahelper->failure($this->session->exception); }

		$people = [];

		try
		{
			$suggestions = $this->instalib->instance->people->getSuggestedUsers($params->user)->getUsers();

		} catch (\Exception $Exception)
		{ return $this->instahelper->failure($Exception->getMessage()); }

		$suggestioncount = count($suggestions);
		if ($suggestioncount <= 0)
		{ return $this->instahelper->outputjson([ "status" => "success", "count" => 0 ]); }

		foreach ($suggestions as $User) 
		{
			$person = 
			[

				"pk" => $User->getPk(),
				"username" => $User->getUsername(),
				"full_name" =>  $User->getFullName(),
				"profile_pic_url" => $User->getProfilePicUrl(),
				"is_private" =>  $User->getIsPrivate(),
				"is_verified" =>  $User->getIsVerified()

			];

			$people[] = $person;
		}

		$result =
		[
			"status" => "success",
			"count" => $suggestioncount,
			"people" => $people
		];

		$this->instahelper->outputjson($result);

	}


	// suggestions() - Get suggestions of users to follow based on interests, location etc
	//
	// $input =
	//[
	//	"username" => "",
	//	"password" => ""
	//]
	public function suggestions()
	{
		$this->load->library('instalib');
    	$this->load->library('instahelper');
    	$this->load->model('instauser_model');

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

		$suggestionlist = [];
		$maxId = null;

		$nextId = (isset($params->next)) ? $params->next : null;
		$maxId = (strlen($nextId) > 0) ? $nextId : null;

		try
		{
			$discover = $this->instalib->instance->people->discoverPeople($maxId);

		} catch (\Exception $Exception)
		{ return $this->instahelper->failure($Exception->getMessage()); }

		$maxId = $discover->getMaxId();
		$suggestions = $discover->getSuggestedUsers()->getSuggestions();
		$suggestioncount = count($suggestions);

		if ($suggestioncount <= 0)
		{ return $this->instahelper->outputjson([ "status" => "success", "count" => 0 ]); }

		foreach ($suggestions as $suggestion) 
		{
			$suggestion_user = $suggestion->getUser();

			$suggestiondata =
			[
				"algorithm" => $suggestion->getAlgorithm(),
				"context" => $suggestion->getSocialContext(),
				"user" => array(
								"pk" => $suggestion_user->getPk(),
								"username" => $suggestion_user->getUsername(),
								"full_name" =>  $suggestion_user->getFullName(),
								"profile_pic_url" => $suggestion_user->getProfilePicUrl(),
								"is_private" =>  $suggestion_user->getIsPrivate(),
								"is_verified" =>  $suggestion_user->getIsVerified()
								)
			];

			$suggestionlist[] = $suggestiondata;
		}

		$result =
		[
			"status" => "success",
			"next" => $maxId,
			"count" => $suggestioncount,
			"people" => $suggestionlist
		];

		$this->instahelper->outputjson($result);

	}


	// login() - Login to Instagram account and update its session.
	//
	// $input =
	//[
	//	"username" => "",
	//	"password" => ""
	//]
	public function login()
	{
		$this->load->library('instalib');
    	$this->load->library('instahelper');
    	$this->load->model('instauser_model');

    	$params = json_decode($this->input->raw_input_stream);

    	// Check parameters
		if (!$this->instahelper->checkparams("username,password", $params))
		{ return $this->instahelper->failure($this->session->exception); }

		// Check if renew session is requested
		$refresh = false;
		if (isset($params->refresh))
		{ $refresh = ($params->refresh == true) ? true : false; }

		// Initialize Instagram Library
		if (!$this->instalib->init($params->username, $params->password, false, $refresh))
		{ return $this->instahelper->failure($this->session->exception); }

		// Login to Instagram Account
		if (!$this->instalib->login())
		{ return $this->instahelper->failure($this->session->exception); }

		$return = 
		[
			"status"  => "success",
			"user" 	  => $this->instalib->loggined
		];

		return $this->instahelper->outputjson($return);

	}



	// i2fauth() - Finish Two Factor Authentication
	//
	// $input =
	//[
	//	"username" => "",
	//	"password" => "",
	//	"i2fauth"     => "",
	//	"i2fcode"  => ""
	//]
	public function i2fauth()
	{
		$this->load->library('instalib');
    	$this->load->library('instahelper');
    	$this->load->model('instauser_model');

    	$params = json_decode($this->input->raw_input_stream);

    	// Check parameters
		if (!$this->instahelper->checkparams("username,password,i2fauth,i2fcode", $params))
		{ return $this->instahelper->failure($this->session->exception); }

		// Initialize Instagram Library
		if (!$this->instalib->init($params->username, $params->password, false))
		{ return $this->instahelper->failure($this->session->exception); }

		// Login to Instagram Account
		if (!$this->instalib->i2fauth($params->i2fauth, $params->i2fcode))
		{ return $this->instahelper->failure($this->session->exception); }

		$return = 
		[
			"status" => "success",
			"action" => "resend_login_request"
		];

		return $this->instahelper->outputjson($return);
	
	}



	// challenge() - Finish Challenge (0 = SMS, 1 = Email)
	//
	// $input =
	//[
	//	"username" => "",
	//	"password" => "",
	//	"userid"     => "",
	//	"id"  => ""
	//	"code"  => ""
	//]
	public function challenge()
	{

		$this->load->library('instalib');
    	$this->load->library('instahelper');
    	$this->load->model('instauser_model');

    	$params = json_decode($this->input->raw_input_stream);

    	// Check parameters
		if (!$this->instahelper->checkparams("username,password,userid,id,code", $params))
		{ return $this->instahelper->failure($this->session->exception); }

		// Initialize Instagram Library
		if (!$this->instalib->init($params->username, $params->password, false))
		{ return $this->instahelper->failure($this->session->exception); }

		// Login to Instagram Account
		if (!$this->instalib->challenge($params->userid, $params->id, $params->code))
		{ return $this->instahelper->failure($this->session->exception); }

		$return = 
		[
			"status" => "success",
			"action" => "resend_login_request"
		];

		return $this->instahelper->outputjson($return);

	}


	public function index()
	{
		$this->load->library('instahelper');
		
		$return = 
		[
			"return" => "No path to follow."
		];

		return $this->instahelper->outputjson($return);
	}

}