<?php

// SQL:

/*
 CREATE TABLE lw_simplenews (
 id bigint(20) NOT NULL AUTO_INCREMENT,
 itemtype varchar(5) DEFAULT NULL,
 newstext text NOT NULL,
 newslink varchar(255) NOT NULL,
 newslinkint varchar(255) NOT NULL,
 archivedate int(8) NOT NULL,
 todate int(8) DEFAULT NULL,
 eventtime varchar(25) DEFAULT NULL,
 intern int(1) NOT NULL,
 openpopup int(1) NOT NULL,
 lastdate bigint(14) NOT NULL,
 firstdate bigint(14) NOT NULL,
 published int(1) NOT NULL,
 dirty int(1) NOT NULL,
 showinnews int(1) DEFAULT NULL,
 sn_instance varchar(255) DEFAULT NULL,
 title varchar(255) DEFAULT NULL,
 location varchar(255) DEFAULT NULL,
 contact varchar(255) DEFAULT NULL,
 PRIMARY KEY (id)
 );
 */


class spn_dh extends lw_object
{
	public function __construct($sn_instance, $itemtype)
	{
		$registry 			= lw_registry::getInstance($instance);
		$this->db 			= $registry->getEntry('db');
		$this->auth 	    = $registry->getEntry('auth');
		$this->conf 		= $registry->getEntry('config');
		$this->userID 		= $this->auth->getUserData('id');

		$this->fGet 		= $registry->getEntry('fGet');

		$admintype		= $this->auth->getUserData('admintype');

		if ($admintype == 'main'||$admintype=='godmode') {
			$this->isAdmin = true;
		} else {
			$this->isAdmin = false;
		}

		$this->instance = $sn_instance;
		$this->itemtype = $itemtype;

		$this->table 			= $this->conf['dbt']['simplenews'];
		//$this->table 			= $this->conf['dbt']['simplenews']."_dev";

		require_once(dirname(__FILE__).'/spn_logger.class.php');
		$this->logger = new spn_logger($this->instance);


		$dba = $this->fGet->getRaw('dbaction');
		$code = $this->fGet->getRaw('code');
	}

	private function getFilterClause() {
		if (empty($this->instance)) {
			$sn = "(sn_instance = '' OR sn_instance IS NULL)";
		} else {
			$sn = "sn_instance = '".$this->sql($this->instance)."'";
		}
		if ($this->itemtype == 'event') {
			$sn.= " AND itemtype='event'";
		} else {
			$sn.= " AND (itemtype='' OR itemtype IS NULL OR showinnews=1)";
		}
		return $sn;
	}

	public function getList($currentdate = false, $month = '')
	{
		if (!empty($month)) {
			$start = $month.'01';
			$end = $month.'31';
			return $this->getListForArchive($start, $end);
		}

		$t = mktime(0,0,0,date('m'),date('d')-14,date('Y'));
		$nowminus14 = date('Ymd',$t);

		$sn = $this->getFilterClause();

		if ($currentdate !== false) {
			$sql = 'SELECT * FROM '.$this->table.' WHERE '
			.'(archivedate > '.$nowminus14.') '
			.'AND '.$sn.' '
			.'ORDER BY archivedate DESC, firstdate DESC';
		} else {
			$sql = 'SELECT * FROM '.$this->table.' WHERE '.$sn;
		}
		$results = $this->db->select($sql);
		return $results;
	}

	public function getListForArchive($start, $end)
	{
		$sn = $this->getFilterClause();
		$sql = 'SELECT * FROM '.$this->table.' WHERE '
		.'archivedate >= '.$start.' '
		.'AND archivedate <= '.$end.' '
		.'AND '.$sn.' '
		.'ORDER BY archivedate DESC';
		$results = $this->db->select($sql);
		return $results;
	}

	public function getItem($id)
	{
		if(!is_numeric($id)) die();
		$sn = $this->getFilterClause();
		$sql = 'SELECT * FROM '.$this->table.' WHERE id='.$id.' AND '.$sn;
		$result = $this->db->select1($sql);
		return $result;
	}

	public function getItems($ids)
	{
		if (!is_array($ids)) die();
		foreach ($ids as $val) {
			$clean[] = intval($val);
		}
		$sn = $this->getFilterClause();
		$sql = 'SELECT * FROM '.$this->table
			.' WHERE id IN ('.implode(',', $clean).') AND '.$sn;
		$result = $this->db->select($sql);
		return $result;
	}

	public function isDirty($id)
	{
		if(!is_numeric($id)) die();
		$sql = 'SELECT dirty FROM '.$this->table.' WHERE id='.$id;
		$result = $this->db->select1($sql);
		if ($result['dirty'] == 1) {
			return true;
		}
		return false;
	}

	public function getListForPeriod($startdate, $enddate)
	{
		$sn = $this->getFilterClause();
		$sql = 'SELECT * FROM '.$this->table.' WHERE '
		.'archivedate > '.$startdate.' '
		.'AND archivedate < '.$enddate.' AND '.$sn;
		$results = $this->db->select($sql);
		return $results;
	}

	public function getDates()
	{
		$sn = $this->getFilterClause();
		$now = date('Ymd');
		$sql = 'SELECT * FROM '.$this->table.' WHERE '.$sn.' '
		.'AND (archivedate < '.$now.') '
		.'AND (published > 0 AND published IS NOT NULL) '
		.'ORDER BY archivedate DESC';

		$results = $this->db->select($sql);

		return $results;
	}

	public function setPublished($flag)
	{
		//$sql = 'UPDATE '.$this->table.' SET published = $flag, dirty = 0';
		//$this->db->dbquery($sql);
		//$this->logger->log('Published.');
	}

	public function save($id, $data)
	{
		if ($id == -1) {
			$id = $this->insert($data);
		} else {
			$this->update($id,$data);
		}

		return $id;
	}

	protected function getFieldKeys() {
		$keys =  explode(',', 'newstext,newslink,newslinkint,archivedate,intern,'
		.'openpopup,lastdate,firstdate,published,sn_instance,dirty,title,'
		.'location,todate,eventtime,eventtotime,itemtype,showinnews,contact');
		return $keys;
	}

	public function insert($data) {

		$keys =  $this->getFieldKeys();
		$vals = array();
		foreach ($keys as $curKey) {
			if (isset($data[$curKey])) {
				$vals[$curKey] = $data[$curKey];
			}
		}

		$vals['sn_instance']	= $this->instance;
		$vals['firstdate']		= date(YmdHi);
		$vals['lastdate']		= date(YmdHi);
		$vals['dirty']			= 1;

		$sql = 'INSERT INTO '.$this->table.' (';
		$sql.= implode(',', array_keys($vals)).') VALUES (';
		foreach ($vals as $curKey => $curVal) {
			$sql.= "'".$this->sql($curVal)."',";
		}
		$sql = substr($sql, 0, -1).')';

		$id = $this->db->dbinsert($sql, $this->table);

		if ($data['published'] == '') {
			$p = 'no';
		} else {
			$p = 'yes';
		}

		if ($id) {
			$this->logger->log('News created (published: '.$p.'), News ID='.$id);
		} else {
			$this->logger->log('News creation failed.');
		}
		return $id;
	}

	public function getFirstYear()
	{
		$sn = $this->getFilterClause();
		$sql = 'SELECT firstdate FROM '.$this->table.' WHERE '.$sn.' ORDER BY firstdate DESC';
		$results = $this->db->select($sql);
		if (empty($results)) return 1950;
		$year = substr($results[0]['firstdate'],0,4);

		return $year;
	}

	public function update($id,$data)
	{
		$keys =  $this->getFieldKeys();
		$vals = array();
		foreach ($keys as $curKey) {
			if (isset($data[$curKey])) {
				$vals[$curKey] = $data[$curKey];
			}
		}

		if ($data['published'] == 1) {
			$vals['dirty'] = 0;
		} else {
			$vals['dirty'] = 1;
		}

		$sql = 'UPDATE '.$this->table.' SET ';
		foreach ($vals as $curKey => $curVal) {
			$sql.= $curKey."='".$this->sql($curVal)."',";
		}
		$sql = substr($sql, 0, -1).' WHERE id='.intval($id);

		$ok = $this->db->dbquery($sql);

		if ($data['published'] == '') {
			$p = 'no';
		} else {
			$p = 'yes';
		}

		if ($ok) {
			$this->logger->log("News updated (published: $p), News ID=$id");
		} else {
			$this->logger->log('News update failed.');
		}
	}

	public function delete($id)
	{
		$sn = $this->getFilterClause();
		$sql = 'DELETE FROM '.$this->table.' WHERE id = '.intval($id).' AND '.$sn;
		$ok = $this->db->dbquery($sql);
		if ($ok) {
			$this->logger->log("News deleted, former News ID=$id.");
		} else {
			$this->logger->log('News delete failed.');
		}
	}

}
