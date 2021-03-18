<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Instafollowers_model extends CI_Model
{

	/* Targeted Follow */
	public function set_targeted_follow($owner, $follower, $reason, $identifier, $taskid)
	{
		$targeted = array(
		        'pk' => $owner["pk"],
		        'username' => $owner["username"],
		        'followat' => date("Y-m-d H:i:s"),
                'reason' => $reason,
		        'identifier' => $identifier,
		        'follow_pk' => $follower["pk"],
		        'follow_username' => $follower["username"],
		        'full_name' => $follower["full_name"],
		        'is_private' => $follower["is_private"],
		        'profile_pic_url' => $follower["profile_pic_url"],
                'task_id' => $taskid,
                'target_converted' => '0'
	    );

		$this->db->insert('insta_target_follow', $targeted);
	}

	public function is_targeted_follow($owner_pk, $follower_pk)
	{
		$targetedfollow = $this->db->get_where('insta_target_follow', array('pk' => $owner_pk, 'follow_pk' => $follower_pk), 1)->result();
		return (count($targetedfollow) == 0) ? false : true;
	}

	public function set_converted_follower($owner_pk, $follower_pk)
	{
		$this->db->update('insta_target_follow', array("target_converted" => 1), array('pk' => $owner_pk, 'follow_pk' => $follower_pk));
	}


	/* Untargeted Follower */
	public function is_untargeted_follower($owner_pk, $follower_pk)
	{
		$untargetedfollower = $this->db->get_where('insta_untargeted_follower', array('pk' => $owner_pk, 'follow_pk' => $follower_pk), 1)->result();
		return (count($untargetedfollower) == 0) ? false : true;
	}

	public function set_untargeted_follower($owner, $follower, $taskid)
	{
		$unargeted = array(
		        'pk' => $owner["pk"],
		        'username' => $owner["username"],
		        'followat' => date("Y-m-d H:i:s"),
		        'follow_pk' => $follower["id"],
		        'follow_username' => $follower["username"],
		        'full_name' => $follower["fullname"],
		        'profile_pic_url' => $follower["profile_pic"],
                'task_id' => $taskid
	    );

		$this->db->insert('insta_untargeted_follower', $unargeted);
	}








	/* Followers Lists */
	public function list_insert($owner_pk, $follower)
	{
		$followerexist = $this->db->get_where('insta_followers', array('owner_pk' => $owner_pk, "pk" => $follower["pk"]), 1)->result();
    	if (count($followerexist) == 0)
    	{
    		$this->db->insert('insta_followers', $follower);
    	}
	}

	public function list_remove($owner_pk, $follower_pk)
	{
		$followerexist = $this->db->get_where('insta_followers', array('owner_pk' => $owner_pk, "pk" => $follower_pk), 1)->result();
    	if (count($followerexist) == 1)
    	{
    		$this->db->delete('insta_followers', array('owner_pk' => $owner_pk, 'pk' => $follower_pk));
    	}
	}

	public function list_get_pos($owner_pk)
	{
		$userlistpos = $this->db->get_where('insta_users', array('pk' => $owner_pk), 1)->row();
		if (count($userlistpos) == 1)
    	{
    		return ($userlistpos->followers_list_pos != '0') ? $userlistpos->followers_list_pos : null;

    	} else { return null; }
	}

	public function list_set_pos($owner_pk, $position)
	{
		$this->db->update('insta_users', array("followers_list_pos" => $position), array("pk" => $owner_pk));
	}

}