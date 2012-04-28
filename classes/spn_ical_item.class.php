<?php

class spn_ical_item implements ArrayAccess
{
	protected $vals;
	const EOL = "\n";

	public function __construct(){
		$this->resetValues();
	}

	protected function resetValues()
	{
		$this->vals = array();
		$this['dtstamp'] = date('Ymd').'T'.date('His').'Z';
	}

	public function assignDbRow($aRow)
	{
		$this->resetValues();
		$this['summary']		= $aRow['title'];
		$this['description']	= $aRow['newstext'];
		$this['location']		= $aRow['location'];
		$this['contact']		= $aRow['contact'];
		$this['created']		= $this->formatDateTime($aRow['firstdate']);
		$this['last-modified']	= $this->formatDateTime($aRow['lastdate']);

		$this['dtstart;value=date']	= $this->formatDateTime($aRow['archivedate'].$aRow['eventtime']);
		$endDate = $aRow['todate'];
		if (empty($endDate)) {
			$endDate = $aRow['archivedate'];
		}
		if (!$aRow['eventtotime'] && $aRow['eventtime']>0) {
		    $aRow['eventtotime'] = $aRow['eventtime'];
		}
		
		$this['dtend;value=date']  = $this->formatDateTime($endDate.$aRow['eventtotime']);

		$this['uid']				= 'sn_'.$aRow['id'].'@logic-works.net';
	}

	protected function formatDateTime($aString)
	{
		$tmp = strtr($aString, array(':' => '', ' ' => ''));
		$ret = substr($tmp, 0, 8);
		if (strlen($tmp) > 8) {
			$ret.= 'T'.substr($tmp, -4).'00 +0100';
		}
		return $ret;
	}

	protected function normalizeKey($aKey)
	{
		return strtoupper($aKey);
	}

	protected function escapeValue($aValue)
	{
		$ret = utf8_encode($aValue);
		//$ret = $aValue;
		//$ret = html_entity_decode($ret, ENT_QUOTES, 'UTF-8');
		if (function_exists('mb_convert_encoding')) {
			//$ret = mb_convert_encoding($ret, 'ISO-8859-15');
		}
		$ret = trim($ret);
		$ret = addcslashes($ret, "\;,\r\n");
		$ret = chunk_split($ret, 74, "\r\n ");
		return trim($ret);
	}

	public function offsetExists($aKey)
	{
		return isset($this->vals[$this->normalizeKey($aKey)]);
	}

	public function offsetGet($aKey)
	{
		return $this->vals[$this->normalizeKey($aKey)];
	}

	public function offsetSet($aKey, $aValue)
	{
		$lVal = trim($aValue);
		$lKey = $this->normalizeKey($aKey);
		if ('' == $lVal) {
			unset($this->vals[$lKey]);
		} else {
			$this->vals[$lKey] = trim($aValue);
		}
	}

	public function offsetUnset($aKey)
	{
		unset($this->vals[$this->normalizeKey($aKey)]);
	}

	public function getAsString()
	{
		$ret = 'BEGIN:VEVENT'.self::EOL;
		foreach ($this->vals as $lKey => $lVal) {
			$ret.= $lKey.':'.$this->escapeValue($lVal).self::EOL;
		}
		$ret.= 'END:VEVENT'.self::EOL;
		
		$ret = str_replace("&#45\;", "-", $ret);
		
		return $ret;
	}

}
