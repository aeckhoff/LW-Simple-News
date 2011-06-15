<?php

require_once 'spn_ical_item.class.php';

class spn_ical
{
	protected $items;
	const EOL = "\n";

	public function __construct()
	{
		$this->items = array();
	}

	public function getAsString()
	{
		$ret = 'BEGIN:VCALENDAR'.self::EOL
		.'METHOD:PUBLISH'.self::EOL
		.'PRODID:-//LogicWorks GmbH//NONSGML Contentory//EN'.self::EOL
		.'VERSION:2.0'.self::EOL;

		if (!empty($this->items)) {
			foreach ($this->items as $item) {
				$ret.= $item->getAsString();
			}
		}
		$ret.= 'END:VCALENDAR';

		return $ret;
	}

	public function sendAsFile($aFilename = 'calendar.ics')
	{
		header('Content-Type: text/calendar');
		header('Content-Disposition: inline; filename="'.$aFilename.'"');
		echo $this->getAsString();
	}

	public function add(spn_ical_item $item)
	{
		$this->items[] = $item;
	}

	public function createAndAddItem() {
		$item = new spn_ical_item();
		$this->add($item);
		return $item;
	}

}