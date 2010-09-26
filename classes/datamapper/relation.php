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
	protected $conditions = array();
	protected $options = array();
	protected $results = array();

	public function __construct(DataMapper $mapper, array $conditions, array $options)
	{
		$this->mapper     = $mapper;
		$this->conditions = $conditions;
		$this->options    = $options;
	}

	/**
	 * Get the datamapper for this relation
	 *
	 * @return  DataMapper
	 */
	public function getMapper()
	{
		return $this->mapper;
	}

	/**
	 * Get the conditions for this relation
	 *
	 * @return  array
	 */
	public function getConditions()
	{
		return $this->options['where'];
	}

	/**
	 * Get the database results for this relation
	 *
	 * @return  array
	 */
	public function getResults()
	{
		if (!$this->results)
		{
			$this->results = $this->getAll();
		}
		return $this->results;
	}

	abstract public function getAll();

	public function __toString()
	{
		// Load related records for current row
		//$success = $this->getResults();
		$success = true;
		return $success ? '1' : '0';
	}
}