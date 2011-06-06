<?php

class spn_file_extension extends spn_object
{

	function __construct($instance)
	{
		parent::__construct($instance);
		$registry 			= lw_registry::getInstance();

	    $this->db 			= $registry->getEntry("db");
	    $this->auth 	    = $registry->getEntry("auth");
	    $this->conf 		= $registry->getEntry("config");
		$this->userID 		= $this->auth->getUserData("id");

		if (empty($this->conf['simplenews']['path_rel_to_datapool'])) die("Configuration error!");

		$this->documentBaseDirectory = $this->conf['path']['datapool'].$this->conf['simplenews']['path_rel_to_datapool'];
		$this->documentBaseURL = $this->conf['url']['datapool'].$this->conf['simplenews']['path_rel_to_datapool'];
		$this->maxFileCount = 3;

		$this->instance = $instance;

		require_once(dirname(__FILE__)."/spn_logger.class.php");
    	$this->logger = new spn_logger($this->instance);


	}

	public function handleUpload($newsid)
	{
		if(!is_numeric($newsid)) die();

		$files = $this->fFiles->getRaw("simple_news_fileupload");

		$names = $files['name'];
		$sizes = $files['size'];
		$tmpnames = $files['tmp_name'];

		$error = false;

		$i = 0;
		if (empty($names)) return true;
		foreach($names as $filename) {
			if (empty($filename)) continue;
			if ($files['size'][$i] == 0) continue;

			$name = $filename;
			$type = $files['type'][$i];
			$tmp_name = $files['tmp_name'][$i];

			try {
				$this->storeFile($name, $tmp_name, $newsid);
			} catch (Exception $e) {
				$error = true;
			}

			$i++;
		}

		return !$error;
	}

	private function storeFile($name, $tmp_name, $newsid)
	{
		if(!is_numeric($newsid)) die();

		$dir = $this->documentBaseDirectory.$newsid."/";
		$path = $dir.$name;

		if (!is_dir($dir)) {
			mkdir($dir);
			chmod($dir, 0775);
		}

		if (is_file($path)) {
			$this->logger->log("Document upload failed: File '$name' already exists.");
			throw new Exception("Datei ".$name." existiert bereits auf dem Server", 1);
		}
		move_uploaded_file($tmp_name, $path);
		chmod($dir, 0775);

		if (is_file($path)) {
			$this->logger->log("Document upload succeeded: File stored, path is '$path'");
		} else {
			$this->logger->log("Document upload failed, reason is not known.");
		}

	}

	public function getDownloadLinks($newsid)
	{
		if(!is_numeric($newsid)) die();

		$files = $this->getFilesForID($newsid);
		if (count($files) == 0) return "";

		$str = "<div class='spn_files>'";
		foreach($files as $file) {
			$lastslash = strrpos($file, "/");
			$title = substr($file,$lastslash+1);
			$url = $this->documentBaseURL.$newsid."/".$title;
			$str.="<span class='simple_news_file'><a href='$url' target='_blank'>$title</a></span><br/>";
		}
		$str.="</div>";
		return $str;
	}

	public function getUploadControls($newsid, $error = false)
	{
		if(!is_numeric($newsid)) die();

		$files = $this->getFilesForID($newsid);

		$tpl = new lw_te($this->loadFile($this->templatePath."upload.tpl.html"));

		if($error) {
			$tpl->setIfVar("error");
			$tpl->reg("uploaderror","Datei existiert bereits unter gleichem Namen.");
		}

		$block = $tpl->getBlock("files");
		$b_out = "";
		for ($i=0; $i < $this->maxFileCount; $i++) {

			$btpl = new lw_te($block);

			if (empty($files[$i])) {
				$btpl->setIfVar("showupload");
			} else {
				$btpl->setIfVar("showlinkanddelete");
				$lastslash = strrpos($files[$i], "/");
				$title = substr($files[$i],$lastslash+1);
				$url = $this->documentBaseURL.$newsid."/".$title;
				$btpl->reg("titel",$title);
				$btpl->reg("url",$url);
				//$deleteurl = $this->buildURL(array("spn_module"=>"edit","spn_command"=>"edit","spn_id"=>$newsid,"spn_file_delete"=>$i,"spn_filename"=>urlencode($title)));
				//$btpl->reg("deleteurl",$deleteurl);


				$btpl->reg("entryindex",$i);
				$btpl->reg("filename",$title);
				$btpl->reg("newsid",$newsid);
			}

			$b_out.=$btpl->parse();
		}
		$tpl->putBlock("files", $b_out);

		return $tpl->parse();
	}

	// --------------------------------------------------------------------------------
	// File-Handling
	// --------------------------------------------------------------------------------

	public function getFilesForID($newsid)
	{
		if(!is_numeric($newsid)) die();

		$docdir = $this->documentBaseDirectory.$newsid."/";
		$files = array();

		if ($newsid == -1) return $files;
		if (!is_dir($docdir)) return $files;

 		$currentFolder = opendir( $docdir );
		$i=0;
		while ( $sFile = readdir( $currentFolder ) )
		{
			if ($sFile != '.' && $sFile != '..')
			{
				$files[] = $docdir.$sFile;
			}
		}
		closedir( $currentFolder );
		return $files;
	}

	public function getNumberOfPossibleUploads($newsid)
	{

	}

	public function addFile($newsid,$temppath)
	{

	}

	public function handleDelete($newsid)
	{
		if(!is_numeric($newsid)) die();

		$deletePathList = array();

		for ($i=0;$i<$this->maxFileCount;$i++) {

			$delete = $this->fPost->getInt("spn_deletebox_".$i);

			if (!is_numeric($delete)) {
				continue;
			}
			$filename = $this->fPost->getRaw("spn_filename_".$i);

			$deletePathList[] =$this->deleteFile($newsid,$filename,$i);
		}
		//print_r($deletePathList);die();

		foreach($deletePathList as $deletePath) {
			$bp = $this->documentBaseDirectory;

			if (substr($deletePath,0,strlen($bp)) == $bp) {
				unlink($deletePath);
				if (is_file($deletePath)) {
					$this->logger->log("Document delete failed: '$deletePath'");
				} else {
					$this->logger->log("Document deleted: '$deletePath'");
				}
			}
		}

		//$url = $this->buildURL(false,array("spn_file_delete","spn_filename"));
		//$this->pageReload($url);
	}

	public function deleteFile($newsid,$filename,$index)
	{
		if(!is_numeric($newsid)) die();

		$files = $this->getFilesForID($newsid);

		$filename = urldecode($filename);
		$filename = str_replace("..","_",$filename);
		$filename = str_replace("/","_",$filename);
		$filename = str_replace("\\","_",$filename);

		if (empty($files)) return;

		$file = $files[$index];



		if (empty($file)) return;
		$lastslash = strrpos($file, "/");
		$title = substr($file,$lastslash+1);

		if ($title != $filename) {

			return;
		}
		$path = $this->documentBaseDirectory.$newsid."/".$title;


		//unlink($path);
		return $path;
	}

	public function deleteAllFilesForID($newsid)
	{
		if(!is_numeric($newsid)) die();
		$files = $this->getFilesForID($newsid);

		foreach($files as $file) {
			$bp = $this->documentBaseDirectory;

			if (substr($file,0,strlen($bp)) == $bp) {
				unlink($file);
				if (is_file($file)) {
					$this->logger->log("Document delete failed: '$file'");
				} else {
					$this->logger->log("Document deleted: '$file'");
				}
			}

		}

		$dir = $this->documentBaseDirectory.$newsid."/";
		if (is_dir($dir)) {
			rmdir($dir);
		}

	}


}