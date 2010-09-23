<?php
/**
 * DataMapper Relation
 *
 * @package    DataMapper
 * @author     R.Wilkins (rocky.wilkins@internetware.co.uk)
 */
abstract class DataMapper_Relation
{
	protected $mapper;
	protected $conditions;
	protected $results;

	public function __construct(DataMapper $mapper, array $conditions)
	{
		$this->mapper     = $mapper;
		$this->conditions = $conditions;
	}

	public function getMapper()
	{
		return $this->mapper;
	}

	public function getConditions()
	{
		return $this->conditions;
	}

	public function getResults()
	{
		if (!$this->results)
		{
			$this->results = $this->getAll();
		}
		return $this->results;
	}

	abstract public function getAll();
}