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
		$this['dtend;value=date']  = $this->formatDateTime($aRow['todate'].$aRow['eventtotime']);
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
		$ret = trim($aValue);
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
		return $ret;
	}

}
