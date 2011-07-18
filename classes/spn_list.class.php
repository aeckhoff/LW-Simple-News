<?php

class spn_list extends spn_object
{
	public function __construct($instance,$pid=false)
	{
		parent::__construct($instance);
		$this->defaultCommand = 'list';
	}

	public function ac_archive()
	{
		$tpl = new lw_te($this->loadFile($this->templatePath.'archive.tpl.html'));
		$dates = $this->dh->getDates();
		$dates_array = array();

		foreach($dates as $date) {
			$d = $date['archivedate'];
			$y = substr($d,0,4);
			$m = substr($d,4,2);

			if (!empty($dates_array[$y][$m])) {
				$dates_array[$y][$m] = $dates_array[$y][$m]+1;
			} else {
				$dates_array[$y][$m] = 1;
			}
		}

		$block = $tpl->getBlock('month');
		$b_out = '';
		foreach($dates_array as $year=>$array)
		{
			foreach($array as $month=>$count) {
				$btpl = new lw_te($block);

				$btpl->reg('monthurl', $this->buildURL(array('spn_module'=>'list','spn_command'=>'list', 'spn_month'=>$year.$month)));
				$btpl->reg('description', $month.'/'.$year);
				$btpl->reg('count', $count);

				$b_out.=$btpl->parse();
			}
		}
		$tpl->putBlock('month', $b_out);

		if ($this->interncss) $tpl->setIfVar('interncss');
		$tpl->reg('backurl', $this->buildURL(array('spn_module'=>'list','spn_command'=>'list'),array('spn_month','spn_year')));

		$this->output = $tpl->parse();
	}

	public function ac_list()
	{
		$spn_month = $this->fGet->getAlNum('spn_month');
		if(!is_numeric($spn_month)) $spn_month = '';

		$items = $this->dh->getList(date(Ymd), $spn_month);

		$tpl = new lw_te($this->loadFile($this->templatePath.'list.tpl.html'));

		if (!empty($spn_month)) {
			$tpl->setIfVar('archive');
			$tpl->reg('backurl', $this->buildURL(array('spn_module'=>'list','spn_command'=>'archive'),array('spn_month','spn_year')));
		} else {
			$tpl->setIfVar('current');
		}

		if ($this->interncss) {
			$tpl->setIfVar('interncss');
			if ($this->isEditor) {
				$tpl->setIfVar('editorcss');
			} else {
				$tpl->setIfVar('readercss');
			}
		}
		$block = $tpl->getBlock('listitem');
		$b_out = '';
		$today = date('Ymd');
		$eventIds = array();
		$file_extension = new spn_file_extension($this->instance);

		foreach($items as $item)
		{
			$pdate = substr($item['archivedate'], 0, 8);
			$isPublished = ($item['published'] == 1);
			$isEvent     = ($item['itemtype'] == 'event');
			if ($isEvent) {
				// events erst nach ablauf des endtermins ins archiv:
				if (!empty($item['todate'])) {
					$pdate = substr($item['todate'], 0, 8);
				}
				$showInList = true;
				if ($this->itemtype == 'news') {
					$showInList = ($item['showinnews'] == 1);
				}
			} else {
				$showInList = ($this->itemtype == 'news');
			}
			$isArchived  = ($pdate < $today);

			if ($this->isEditor || ($isPublished && !$isArchived  && $showInList)) {
				$btpl = new lw_te($block);
				foreach($item as $curKey => $curVal) {
					if (!empty($curVal)) $btpl->setIfVar('has_'.$curKey);
					$btpl->reg($curKey, $curVal);
				}

				if ($item['itemtype'] == 'event') {
					$btpl->setIfVar('is_event');
					$btpl->reg('icalurl', $this->buildURL(array('spn_module'=>'edit','spn_command'=>'download', 'spn_id'=>$item['id'])));
					$eventIds[] = $item['id'];
				}

				$btpl->reg('archivedate', $this->numberToDateTimeWithoutTime($item['archivedate']));
				$btpl->reg('todate', $this->numberToDateTimeWithoutTime($item['todate']));
				$btpl->reg('firstdate', $this->numberToDateTimeWithoutTime($item['archivedate']));
				$btpl->reg('newstext', nl2br($item['newstext']));

				if (!empty($item['title'])) {
					$btpl->setIfVar('hastitle');
					$btpl->reg('title',$item['title']);
				}
				if (!empty($item['newslink'])) {
					$btpl->setIfVar('haslink');
					$btpl->reg('newslink',$item['newslink']);
				}
				if (!empty($item['newslinkint'])) {
					$btpl->setIfVar('haslinkint');
					if (is_int($item['newslinkint'])) {
						$link = $this->config['url']['client'].'index.php?index='.$item['newslinkint'];
					} else {
						$link = $this->config['url']['client'].$item['newslinkint'];
					}
					$btpl->reg('newslinkint',   $link);
				}
                if (!empty($item['eventtotime'])) {
                    $btpl->setIfVar('has_eventtotime');
                }
				if ($this->isEditor) {
					$btpl->setIfVar('editor');
					$btpl->reg('editurl', $this->buildURL(array('spn_module'=>'edit','spn_command'=>'edit', 'spn_id'=>$item['id'])));

					if ($this->isAdmin) {
						$btpl->setIfVar('admindelete');
						$btpl->reg('deleteurl', $this->buildURL(array('spn_module'=>'edit','spn_command'=>'delete', 'spn_id'=>$item['id'])));
					}

					if ($item['published'] != 1) $btpl->setIfVar('notpublished');
					if ($item['published'] == 1 && $item['itemtype'] != 'event' && $pdate > date('Ymd')) $btpl->setIfVar('notyetpublished');

				}

				// --------------------------------------------------------------------------------
				// FILE
				// --------------------------------------------------------------------------------
				$files = $file_extension->getDownloadLinks($item['id']);
				$btpl->reg('files', $files);
				// --------------------------------------------------------------------------------

				$b_out.=$btpl->parse();
			}
		}

		$tpl->putBlock('listitem', $b_out);

		if ($this->isEditor) {
			$tpl->setIfVar('editor');
			$tpl->reg('addurl', $this->buildURL(array('spn_module'=>'edit','spn_command'=>'edit', 'spn_id'=>'-1')));

		}
		$tpl->reg('archiveurl', $this->buildURL(array('spn_module'=>'list','spn_command'=>'archive', 'spn_year'=>'-1'),array('spn_id')));
		if (!empty($eventIds)) {
			$tpl->setIfVar('hasevents');
			$tpl->reg('icalallurl',
				$this->buildURL(
					array('spn_module'=>'edit','spn_command'=>'downloadall', 'spn_ids'=>implode(',', $eventIds)),array('spn_id')
				)
			);

		}

		$this->output = $tpl->parse();
	}

	function numberToDateTimeWithoutTime($number) {
		$year   = substr($number,0,4);
		$month  = substr($number,4,2);
		$day    = substr($number,6,2);

		return $day.'.'.$month.'.'.$year;
	}

}
