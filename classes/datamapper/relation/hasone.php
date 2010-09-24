<?php
/**
 * DataMapper Has One Relation
 *
 * @package    DataMapper
 * @author     R.Wilkins (rocky.wilkins@internetware.co.uk)
 */
class DataMapper_Relation_HasOne extends DataMapper_Relation
{
	public function getAll()
	{
		$this->mapper->getAll();
	}

	public function __isset($key)
	{
		$row = $this->getResults();
		if (!$row)
		{
			return false;
		}
		return isset($row->$key);
	}

	public function __get($key)
	{
		$row = $this->getResults();
		if ($row)
		{
			return $row->$key;
		}
		else
		{
			throw new DataMapper_Exception('Key does not exist.');
		}
	}

	public function __set($key, $value)
	{
		$row = $this->getResults();
		if ($row)
		{
			$row->$key = $value;
		}
	}
}