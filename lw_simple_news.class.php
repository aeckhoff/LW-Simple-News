<?php
//
// +------------------------------------------------------------------------+
// | LW Plugin :: simplenews                                                |
// +------------------------------------------------------------------------+
// | Copyright (c) 2007 Logic Works GmbH                                    |
// +------------------------------------------------------------------------+
//

/**
 * Das lw_form Plugin
 *
 * @author
 * @copyright   Copyright &copy; 2007 Logic Works GmbH
 * @package     LW Contentory
 */
class lw_simple_news extends lw_object
{

	/**
	 * Konstruktor, instanziert die Parent-Klasse und befüllt
	 * die grundlegenden Variablen
	 */
	public function __construct($pid=false)
	{
		$reg 	 		= lw_registry::getInstance();
		$this->config 	= $reg->getEntry("config");
		$this->reqVars  = $reg->getEntry("requestVars");
		$this->db  		= $reg->getEntry("db");
		$this->auth  	= $reg->getEntry("auth");
		$this->pid 		= $pid;

		$this->fPost 	= $reg->getEntry("fPost");
		$this->fGet     = $reg->getEntry("fGet");

		$this->templatePath	= $this->config['path']['plugins']."lw_simple_news/templates/";
		$this->objectPath	= $this->config['path']['plugins']."lw_simple_news/classes/";

		$this->output = '';
		//$this->switchCache(false);

	}

	public function setParameter($param)
	{
		$parts = explode("&", $param);
		foreach($parts as $part)
		{
			$sub = explode("=", $part);
			$this->params[$sub[0]] = $sub[1];
		}
	}

	public function buildPageOutput()
	{
		$obj = $this->fGet->getRaw("spn_module");

		$instance = $this->params['instance'];

		if (strlen($instance) > 255) {
			return "<div style='color:red;'>Simple News:<br/> Configuration error: instance name is too long (max 255).</div>";
		}

		if(empty($obj)) $obj = "list";

		$obj = substr($obj,0,64);
		$obj = str_replace("..","_",$obj);
		$obj = str_replace("\\","_",$obj);
		$obj = str_replace("/","_",$obj);
		$obj = urlencode($obj);

		$oc  = "spn_".$obj;
		$of = $this->objectPath.$oc.".class.php";

		//die($obj);
		require_once($this->objectPath."spn_object.class.php");
		require_once($this->objectPath."spn_file_extension.class.php");
		//die($of);
		if (is_file($of)) {
			require_once($of);
			$object = new $oc($instance);
		} else {

			require_once($this->objectPath."spn_list.class.php");
			$object = new spn_list($instance);
		}
		$object->setParams($this->params);

		$object->execute();
		return $object->getOutput();

	}
}
