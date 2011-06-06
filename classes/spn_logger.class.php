<?php

class spn_logger extends lw_object
{
	public function __construct($instance)
	{

	    $registry 			= lw_registry::getInstance();
    	$this->auth 	    = $registry->getEntry("auth");
    	$this->conf 		= $registry->getEntry("config");
		$this->userID 		= $this->auth->getUserData("id");

		$admintype		= $this->auth->getUserData("admintype");
    	$this->logfile			= $this->conf['simplenews']['logfile'];

    	$this->instance = $instance;

    	if (empty($this->logfile)) $this->logfile = false;

	}

	public function log($text)
	{
		if ($this->logfile == false) return;

		$date = date("Y-m-d H:i:s");

		if (empty($this->instance)) {
			$instance = "_default_";
		} else {
			$instance = $this->instance;
		}

		$logline = $date."\t[".$this->userID."][".$instance."]\t".$text."\n";

		$this->appendFile($this->logfile,$logline);
	}

}