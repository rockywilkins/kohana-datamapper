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
		return $this->mapper->getAll($this->conditions);
	}

	public function __isset($key)
	{
		$row = $this->getResults();
		if (!$row)
		{
			return false;
		}
		return isset($row[0]->$key);
	}

	public function __get($key)
	{
		$row = $this->getResults();
		if ($row)
		{
			return $row[0]->$key;
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
			$row[0]->$key = $value;
		}
	}

	public function __call($name, $arguments)
	{
		$row = $this->getResults();
		if ($row)
		{
			return call_user_func_array(array($row[0], $name), $arguments);
		}
	}
}