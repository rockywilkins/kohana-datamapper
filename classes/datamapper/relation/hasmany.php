<?php
/**
 * DataMapper Has Many Relation
 *
 * @package    DataMapper
 * @author     R.Wilkins (rocky.wilkins@internetware.co.uk)
 */
class DataMapper_Relation_HasMany extends DataMapper_Relation implements Countable, IteratorAggregate, ArrayAccess
{
	public function getAll()
	{
		return $this->mapper->getAll($this->conditions);
	}

	public function getOne()
	{
		return $this->mapper->getOne($this->conditions);
	}

	public function count()
	{
		return count($this->getResults());
	}

	public function getIterator()
	{
		$data = $this->getResults();
		return $data ? $data : array();
	}


//////////////////////////////
///// ArrayAccess Methods
//////////////////////////////

	public function offsetExists($key)
	{
		$this->getResults();
		return isset($this->results[$key]);
	}

	public function offsetGet($key)
	{
		$this->getResults();
		return $this->results[$key];
	}

	public function offsetSet($key, $value)
	{
		$this->getResults();

		if($key === null)
		{
			return $this->results[] = $value;
		}
		else
		{
			return $this->results[$key] = $value;
		}
	}

	public function offsetUnset($key)
	{
		$this->getResults();
		unset($this->results[$key]);
	}
}