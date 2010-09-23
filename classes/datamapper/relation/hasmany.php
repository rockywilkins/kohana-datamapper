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
		return $this->getOne();
	}

	public function getOne()
	{
		return $this->mapper->getOne();
	}

	public function count()
	{
	}

	public function getIterator()
	{
	}


//////////////////////////////
///// ArrayAccess Methods
//////////////////////////////

	public function offsetExists($key)
	{
	}

	public function offsetGet($key)
	{
	}

	public function offsetSet($key, $value)
	{
	}

	public function offsetUnset($key)
	{
	}
}