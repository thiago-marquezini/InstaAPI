<?php defined('BASEPATH') OR exit('No direct script access allowed');

set_time_limit(0);
date_default_timezone_set('UTC');

class Instahelper
{
	public function __construct()
	{
	    $this->CI =& get_instance();
	}

	public function checkparams($params, $input)
    {
    	$eparams = explode(',', $params);
		foreach($eparams as $param)
		{
     		if (!isset($input->$param))
     		{ 
     			$this->CI->session->set_userdata("exception", "É necessário preencher todos os campos (" . $params . ").");
     			return false; 
     		}

            if ($input->$param == "")
            { 
                $this->CI->session->set_userdata("exception", "É necessário preencher todos os campos (" . $params . ").");
                return false; 
            }
		}

		return true;
    }

    public function returnjson($data)
    {
        return json_decode(json_encode($data));
    }

    public function outputjson($data)
	{
        return $this->CI->output->set_content_type('application/json')
        						->set_output(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    public function failure($exception)
    {
        $this->CI->instauser_model->setlastexception($this->CI->instalib->loggined->username, $exception);

    	$failure = [ "status" => "failure", "exception" => $exception ];
    	$this->outputjson($failure);
    }

}