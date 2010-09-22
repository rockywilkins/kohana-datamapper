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

	public function __construct(DataMapper $mapper)
	{
		$this->mapper = $mapper;
	}
	
	public function getAll()
	{
	}
}