<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Instauser_model extends CI_Model 
{

    /* User */
    public function isregistered($username)
    {
    	$user = $this->db->get_where('insta_users', array('username' => $username), 1)->result();
    	if (count($user) == 1)
    	{ return true; }
    	return false;
    }

    public function register($user)
    {
    	$userinfo = array(
		        'userid' => ($user["userid"] == null) ? "0" : $user["userid"],
		        'pk' => $user["pk"],
		        'username' => $user["username"],
                'password' => $user["password"],
		        'fullname' => $user["fullname"],
		        'profile_pic' => $user["profile_pic"],
		        'isverified' => $user["isverified"],
		        'isprivate' => $user["isprivate"],
		        'isbusinnes' => $user["isbusinnes"],
		        'last_login' => date("Y-m-d H:i:s"),
		        'challenge_required' => '0',
                'i2fauth_required' => '0',
                'last_exception' => '0',
                'followers_list_pos' => '0',
                'followers_count' => $user["followers_count"],
                'following_count' => $user["following_count"]
	    );

		$this->db->insert('insta_users', $userinfo);
    }

    public function getactiveusers()
    {
        $user = $this->db->get_where('insta_users', array('id >=' => 1))->result();
        if (count($user) > 0)
        { return $user; }

        return false;
    }

    public function getuserinfo($username)
    {
        $user = $this->db->get_where('insta_users', array('username' => $username), 1)->result()[0];
        if (count($user) == 1)
        { 
            return $user;
        }

        return null;
    }

    public function getuserfrompk($pk)
    {
        $user = $this->db->get_where('insta_users', array('pk' => $pk), 1)->row();
        if (count($user) == 1)
        { 
            return $user;
        }

        return false;
    }

    public function update($username, $user)
    {
        $this->db->update('insta_users', 
                          array('userid' => ($user["userid"] == null) ? "0" : $user["userid"],
                                'pk' => $user["pk"],
                                'username' => $user["username"],
                                'password' => $user["password"],
                                'fullname' => $user["fullname"],
                                'profile_pic' => $user["profile_pic"],
                                'isverified' => $user["isverified"],
                                'isprivate' => $user["isprivate"],
                                'isbusinnes' => $user["isbusinnes"],
                                'last_login' => date("Y-m-d H:i:s"), 
                                'challenge_required' => '0',
                                'i2fauth_required' => '0',
                                'followers_count' => $user["followers_count"],
                                'following_count' => $user["following_count"]), 
                          array("username" => $username));
    }

    public function seti2fauth($username, $required)
    {
        $this->db->update('insta_users', array("i2fauth_required" => $required), array("username" => $username));
    }

    public function setchallenge($username, $required)
    {
        $this->db->update('insta_users', array("challenge_required" => $required), array("username" => $username));
    }

    public function setlastexception($username, $exception)
    {
        $this->db->update('insta_users', array("last_exception" => $exception), array("username" => $username));
    }
}