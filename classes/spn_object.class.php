<?php

class spn_object extends lw_object
{

	const DEFAULT_TYPE = 'news';

	/**
	 * Konstruktor, instanziert die Parent-Klasse und befüllt
	 * die grundlegenden Variablen
	 */

	public function __construct($instance, $pid=false)
	{
		$reg 	 		= lw_registry::getInstance();
		$this->config 	= $reg->getEntry('config');
		include_once($this->config['path']['plugins'].'lw_simple_news/classes/spn_dh.class.php');
		$this->reqVars  = $reg->getEntry('requestVars');
		$this->db  		= $reg->getEntry('db');
		$this->auth  	= $reg->getEntry('auth');
		$this->pid 		= $reg->getEntry('pid');

		$this->fPost 	= $reg->getEntry('fPost');
		$this->fGet     = $reg->getEntry('fGet');
		$this->fFiles   = $reg->getEntry('fFiles');


		$this->templatePath	= $this->config['path']['plugins'].'lw_simple_news/templates/';

		$this->instance = $instance;

		//$this->dh = new spn_dh($this->instance);

		$this->isAdmin = false;
		$this->isEditor = false;



		if ($this->auth->isAllowed('news_edit')) {
			if ($this->auth->isInPages($this->pid)) {
				$this->isEditor = true;
			}
		}

		if ($this->auth->isAllowed('news_admin')) {
			if ($this->auth->isInPages($this->pid)) {
				$this->isEditor = true;
				$this->isAdmin = true;
			}
		}

		$this->output = '';

		$this->interncss = false;
		$this->itemtype  = self::DEFAULT_TYPE;
	}

	public function setParams($aParams) {
		$this->params = $aParams;
		$this->interncss = isset($aParams['interncss']) ? $aParams['interncss'] : false;
		$this->itemtype  = isset($aParams['type']) ? $aParams['type'] : self::DEFAULT_TYPE;
		$this->dh = new spn_dh($this->instance, $this->itemtype);
	}

	public function execute()
	{
		$command = $this->fGet->getRaw('spn_command');
		$command = substr($command,0,64);
		$command = urlencode($command);

		if ($command == 'install') {
			//die('in spn_object ausgeschaltet');
			$this->dh->install();
		}

		if (empty($command)) $command = $this->defaultCommand;

		if (is_callable(array($this,'ac_'.$command))) {
			$cmd = 'ac_'.$command;
			$this->$cmd();
		} else {
			//$this->output = '<p>Unknown Command</p>';
			$command = $this->defaultCommand;
			$cmd = 'ac_'.$command;
			$this->$cmd();
		}
	}

	public function getOutput()
	{
		return $this->output;
	}

}
