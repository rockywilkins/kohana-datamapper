<?php
/**
 * DataMapper Entity
 *
 * @package    DataMapper
 * @author     R.Wilkins (rocky.wilkins@internetware.co.uk)
 */
class DataMapper_Entity
{
	protected $loaded;
	protected $data     = array();
	protected $modified = array();

	public function __construct($data = null)
	{
	}

//////////////////////////////
///// Data Methods
//////////////////////////////

	/**
	 * Set the data of this entity
	 *
	 * @param   array  data to set
	 * @return  void
	 */
	public function setData($data)
	{
	}

	/**
	 * Get the data of this entity
	 *
	 * @return  array
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * Get the data that has been modified since entity was loaded
	 *
	 * @return  array
	 */
	public function getModifiedData()
	{
		return $this->modified;
	}

	/**
	 * Get the data of this entity as an array
	 */
	public function toArray()
	{
	}

	/**
	 * Get the data of this entity as a JSON string
	 *
	 * @return  string
	 */
	public function toJSON()
	{
	}

//////////////////////////////
///// Magic Setter/Getter Methods
//////////////////////////////

	public function __isset($key)
	{
		return ($this->$key !== null) ? true : false;
	}

	public function __set($key, $value)
	{
		$this->data[$key] = $value;
	}

	public function __get($key)
	{
		return array_key_exists($key, $this->data) ? $this->data[$key] : null;
	}
}