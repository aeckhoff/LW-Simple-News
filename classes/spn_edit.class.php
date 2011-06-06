<?php

class spn_edit extends spn_object
{
	public function __construct($instance, $pid=false)
	{
		parent::__construct($instance);

		$this->defaultCommand = 'edit';

		// ACHTUNG: Der erste Eintrag wird als Reply-To verwendet!
		//$this->mail_to = array('support@logic-works.de');
	}

	public function ac_edit($item = false, $error = false)
	{
		if (!$this->isEditor) die('You are no editor!');

		$id = $this->fGet->getRaw('spn_id');
		if (empty($id)) $id = -1;

		$nonews = false;

		if ($item == false) {
			if ($id == -1) {
				$item = array();
				$item['itemtype'] = $this->itemtype;
			} else {
				$item = $this->dh->getItem($id);
				if (empty($item)) {
					$nonews = true;
				}
			}
		} else {
			$id = $item['id'];
			if (empty($id)) die('Internal Error');
		}

		$tplfile = $this->templatePath.'edit';
		if ($item['itemtype'] == 'event') $tplfile.= 'event';
		$tpl = new lw_te($this->loadFile($tplfile.'.tpl.html'));
		$tpl->reg('itemtype', $this->itemtype);

		if ($nonews) {
			$block = $tpl->getBlock('nonews');
			$b_out = '';

			$btpl = new lw_te($block);

			$btpl->setIfVar('nonews');
			$btpl->reg('id',$id);
			$btpl->reg('listurl',$this->buildURL(array('spn_module'=>'list','spn_command'=>'list'),array('spn_id')));
			$this->output = $btpl->parse();
			return;
		}

		if ($this->interncss) $tpl->setIfVar('interncss');

		if (empty($item['newslink'])) $item['newslink'] = 'http://';

		if ($id == -1) {
			$tpl->setIfVar('newsadd');
		} else {
			$tpl->setIfVar('newsedit');
		}

		$tpl->reg('title'	    , $item['title']);
		$tpl->reg('newstext'	, $item['newstext']);
		$tpl->reg('newslink'	, $item['newslink']);
		$tpl->reg('newslinkint'	, $item['newslinkint']);
		$tpl->reg('archivedate'	, $this->numberToDate($item['archivedate']));
		$tpl->reg('intern_c'	, $this->checked($item['intern']));
		$tpl->reg('openpopup_c'	, $this->checked($item['openpopup']));

		$tpl->reg('firstdate'	, $this->numberToDateTime($item['firstdate']));
		$tpl->reg('lastdate'	, $this->numberToDateTime($item['lastdate']));

		$tpl->reg('itemtype'	, $item['itemtype']);
		$tpl->reg('location'	, $item['location']);
		$tpl->reg('todate'		, $this->numberToDate($item['todate']));
		$tpl->reg('contact'		, $item['contact']);
		$tpl->reg('eventtime'	, $item['eventtime']);
		$tpl->reg('showinnews'	, $this->checked($item['showinnews']));

		if ($this->isEditor) {
			$tpl->setIfVar('editor');
			$tpl->reg('actionurl', $this->buildURL(array('spn_module'=>'edit','spn_command'=>'save', 'spn_id'=>$id)));
		}

		if ($this->isAdmin) {
			$tpl->setIfVar('admin');
			$tpl->reg('published_c'	, $this->checked($item['published']));
		}

		if ($id != -1) {
			$tpl->setIfVar('notnew');
		}

		$tpl->reg('listurl', $this->buildURL(array('spn_module'=>'list','spn_command'=>'list'),array('spn_id')));

		// --------------------------------------------------------------------------------
		// FILE
		// --------------------------------------------------------------------------------
		$file_extension = new spn_file_extension($this->instance);
		//$file_extension->handleDelete($id);
		$tpl->reg('upload_include',$file_extension->getUploadControls($id,$error) );

		$this->output = $tpl->parse();
	}

	protected function checked($flag)
	{
		if ($flag == 1) {
			return 'checked="checked"';
		} else {
			return '';
		}
	}

	function numberToDateTime($number) {
		if (empty($number)) return '';
		$year = substr($number,0,4);
		$month = substr($number,4,2);
		$day = substr($number,6,2);

		$hour = substr($number,8,2);
		$minute = substr($number,10,2);

		return $day.'.'.$month.'. '.$year.' '.$hour.':'.$minute;
	}

	function dateToNumber($date)
	{
		if (empty($date)) return '0';
		$day = substr($date,0,2);
		$month = substr($date,3,2);
		$year = substr($date,7,4);

		return $year.$month.$day;
	}

	function numberToDate($number)
	{
		if (empty($number)) return '';
		$year = substr($number,0,4);
		$month = substr($number,4,2);
		$day = substr($number,6,2);

		return $day.'.'.$month.'. '.$year;
	}

	public function ac_save()
	{
		if (!$this->isEditor) die('You are no editor!');

		$id = $this->fGet->getRaw('spn_id');
		if (empty($id)) {
			$this->pageReload($this->buildURL(array('spn_module'=>'list', 'spn_command'=>'list'), array('spn_id')));
		}

		$data = array();

		$data['title']			= $this->lwStringClean($this->fPost->getRaw('title'));
		$data['newstext']		= $this->lwStringClean($this->fPost->getRaw('newstext'));
		$data['newslink']		= $this->lwStringClean($this->fPost->getRaw('newslink'));
		$data['newslinkint']	= $this->lwStringClean($this->fPost->getRaw('newslinkint'));
		$data['archivedate']	= $this->dateToNumber($this->lwStringClean($this->fPost->getRaw('archivedate')));
		$data['intern']			= $this->lwStringClean($this->fPost->getRaw('intern'));
		$data['openpopup']		= $this->lwStringClean($this->fPost->getRaw('openpopup'));
		$data['published']		= $this->lwStringClean($this->fPost->getRaw('published'));

		// neue felder. news werden mit leerem itemtype eingetragen
		$datatype               = $this->lwStringClean($this->fPost->getRaw('itemtype'));
		if ($datatype == 'event') {
			$data['itemtype']		= 'event';
			$data['location']		= $this->lwStringClean($this->fPost->getRaw('location'));
			$data['todate']			= $this->dateToNumber($this->lwStringClean($this->fPost->getRaw('todate')));
			$data['showinnews']		= $this->lwStringClean($this->fPost->getRaw('showinnews'));
			$data['eventtime']		= $this->lwStringClean($this->fPost->getRaw('eventtime'));
			$data['contact']		= $this->lwStringClean($this->fPost->getRaw('contact'));
		}
		if ($data['newslink'] == 'http://') {
			$data['newslink'] = '';
		} else {
			$lLink = $data['newslink'];
			if ((substr($lLink,0,7) != 'http://') && (substr($lLink,0,8) != 'https://') && (!empty($lLink))) {
				$data['newslink'] = 'http://'.$lLink;
			}
		}
		//die('D:'.$this->dh->isDirty($id));

		// check required fields
		// already done in javascript!
		/*
		$required = array('archivedate');
		$error = array();
		foreach ($required as $field) {
		if (empty($data[$field])) {
		$error[] = $field;
		}
		}
		if (!empty($error)) $this->ac_edit($data);
		*/

		$ido = $id;
		$id = $this->dh->save($id, $data);

		if ($ido==-1) {
			$this->mailToAdmin($id);
		}

		// --------------------------------------------------------------------------------
		// FILE
		// --------------------------------------------------------------------------------
		$file_extension = new spn_file_extension($this->instance);
		$file_extension->handleDelete($id);
		$success = $file_extension->handleUpload($id);

		if ($success == false) {
			$data['id'] = $id;
			$this->ac_edit($data, true);
			return;
		}
		// --------------------------------------------------------------------------------

		$this->pageReload($this->buildURL(array('spn_module'=>'list', 'spn_command'=>'list'), array('spn_id')));
	}

	public function mailToAdmin($id)
	{
		if (empty($this->mail_to)) return;

		$tpl = new lw_te($this->loadFile($this->templatePath.'mail.tpl.txt'));

		$link = $this->config['url']['client'].'index.php?index='.$this->fGet->getRaw('index').'&spn_module=edit&spn_command=edit&spn_id='.$id;

		$tpl->reg('newsid',$id);
		$tpl->reg('newslink',$link);
		$tpl->reg('date',date('Y-m-d H:i:s'));
		$tpl->reg('user',$this->auth->getUserdata('name'));


		$text = $tpl->parse();

		$header = 'From: SimpleNews Plugin' . '\r\n' .
		    'Reply-To: '.$this->mail_to[0] . '\r\n' .
		    'X-Mailer: PHP/' . phpversion();


		$subject = 'SimpleNews: Eine Meldung wurde erstellt';
		foreach($this->mail_to as $mt) {
			mail($mt, $subject, $text, $header);
		}

	}
	public function ac_delete()
	{
		$id = $this->fGet->getRaw('spn_id');
		if (empty($id)) {
			$this->pageReload($this->buildURL(array('spn_module'=>'list', 'spn_command'=>'list'), array('spn_id')));
		}
		if ($this->isAdmin) {
			$this->dh->delete($id);
			// --------------------------------------------------------------------------------
			// FILE
			// --------------------------------------------------------------------------------
			$file_extension = new spn_file_extension($this->instance);
			$file_extension->deleteAllFilesForID($id);
			// --------------------------------------------------------------------------------
		} else {
			//die('NOT ALLOWED');
			// just do a page reload ...
		}
		$this->pageReload($this->buildURL(array('spn_module'=>'list', 'spn_command'=>'list'), array('spn_id')));
	}

}
