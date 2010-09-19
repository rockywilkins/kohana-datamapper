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
		if ($data !== null)
		{
			$this->setData($data);
		}

		$this->loaded = true;
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
		foreach ($data as $key => $value)
		{
			$this->$key = $value;
		}
	}

	/**
	 * Get the data of this entity
	 *
	 * @return  array
	 */
	public function getData()
	{
		return array_merge($this->data, $this->modified);
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
	 * Get the data of this entity as a JSON string
	 *
	 * @return  string
	 */
	public function toJSON()
	{
		return json_encode($this->getData());
	}

//////////////////////////////
///// Magic Setter/Getter Methods
//////////////////////////////

	public function __isset($key)
	{
		return ($this->$key !== null) ? true : false;
	}

	/**
	 * Set entity data
	 */
	public function __set($key, $value)
	{
		if ($this->loaded)
		{
			$this->modified[$key] = $value;
		}
		else
		{
			$this->data[$key] = $value;
		}
	}

	/**
	 * Get entity data
	 */
	public function __get($key)
	{
		if (array_key_exists($key, $this->modified))
		{
			return $this->modified[$key];
		}
		else if (array_key_exists($key, $this->data))
		{
			return $this->data[$key];
		}
		else
		{
			return null;
		}
	}
}