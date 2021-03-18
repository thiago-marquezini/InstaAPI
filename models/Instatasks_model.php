<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Instatasks_model extends CI_Model 
{
	/* Tasks */
	public function get_tasks($user_pk, $filters = [])
    {
    	$taskuser = array('pk' => $user_pk);
    	$filters = array_merge($taskuser, $filters);
    	
    	$usertasks = $this->db->get_where('insta_tasks', $filters)->result();
    	
    	if (!(count($usertasks) == 0))
    	{ return $usertasks; }

    	return false;
    }

	public function createtask($user_pk, $task)
	{
		$findtasks = $this->db->get_where('insta_tasks', array('pk' => $user_pk, 
															   'task_type' => $task["task_type"], 
															   'task_paused' => 0,
															   'task_stopped' => 0,
															   'task_finished' => 0))->result();
		if (count($findtasks) >= 3)
		{ return "O número máximo de tarefas deste tipo foi excedido para esta conta do Instagram."; }

		if (strlen($task["task_ref"]) != null)
		{
			$findreftasks = $this->db->get_where('insta_tasks', array('pk' => $user_pk, 
															          'task_ref' => $task["task_ref"]))->result();
			if (count($findreftasks) >= 1)
			{ return "O valor do campo 'task_ref' está duplicado. Altere-o ou deixe-o em branco."; }
		}

		$task["task_segment"] = json_encode($task["task_segment"]);
		$task["task_progress"] = json_encode($task["task_progress"]);

		$taskhead = [ "pk" => $user_pk ];
		$taskreal = array_merge($taskhead, $task);

		$this->db->insert('insta_tasks', $taskreal);

		return null;
	}

    public function resume_task($user_pk, $task_id)
	{
		$this->db->update('insta_tasks', array("task_paused" => 0), array("pk" => $user_pk, "task_id" => $task_id));
	}

	public function pause_task($user_pk, $task_id)
	{
		$this->db->update('insta_tasks', array("task_paused" => 1), array("pk" => $user_pk, "task_id" => $task_id));
	}

	public function stop_task($user_pk, $task_id)
	{
		$this->db->update('insta_tasks', array("task_stopped" => 1), array("pk" => $user_pk, "task_id" => $task_id));
	}

	public function delete_task($user_pk, $task_id)
	{
		$this->db->delete('insta_tasks', array("pk" => $user_pk, "task_id" => $task_id));
	}

}







