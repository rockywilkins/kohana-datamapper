<?php
/**
 * DataMapper
 *
 * @package    DataMapper
 * @author     R.Wilkins (rocky.wilkins@internetware.co.uk)
 */
class DataMapper
{
	protected $entityClass = 'DataMapper_Entity';
	protected $fields      = array();
	protected $relations   = array();
	protected $primaryKeyField;
	protected $table;
	protected $errors      = array();

	public static function instance($mapperName)
	{
		$className = 'Mapper_' . $mapperName;
		return new $className();
	}

	public function __construct()
	{
		$this->loadFields();
	}

//////////////////////////////
///// Field Methods
//////////////////////////////

	/**
	 * Load all the defined fields
	 *
	 * @return  void
	 */
	public function loadFields()
	{
		$getFields = create_function('$obj', 'return get_object_vars($obj);');
		$fields = $getFields($this);

		$defaults = array(
			'primary' => false
		);

		foreach ($fields as $name => $options)
		{
			$options = array_merge($defaults, $options);
			if ($options['primary'] === true)
			{
				$this->primaryKeyField = $name;
			}

			$this->fields[$name] = array();
		}
	}

	/**
	 * Get all the defined fields
	 *
	 * @return  array
	 */
	public function getFields()
	{
		return $this->fields;
	}

	/**
	 * Check if a field exists
	 *
	 * @param   string  field name
	 * @return  bool
	 */
	public function fieldExists($field)
	{
		return array_key_exists($field, $this->fields);
	}

	/**
	 * Get the value of the primary key
	 *
	 * @param   DataMapper_Entity  entity to get value from
	 * @return  mixed
	 */
	public function getPrimaryKey($entity)
	{
		return $entity->{$this->primaryKeyField};
	}

	/**
	 * Get the name of the primary key field
	 *
	 * @return  string
	 */
	public function getPrimaryKeyField()
	{
		return $this->primaryKeyField;
	}

//////////////////////////////
///// Data fetch/push Methods
//////////////////////////////

	/**
	 * Get an empty entity
	 *
	 * @return  DataMapper_Entity
	 */
	public function getEntity()
	{
		return new $this->entityClass();
	}

	/**
	 * Get record from primary key
	 *
	 * @param   int  primary key value
	 * @return  DataMapper_Entity
	 */
	public function get($primaryKeyValue)
	{
		return $this->getOne(array($this->getPrimaryKeyField(), '=', $primaryKeyValue));
	}

	/**
	 * Get a single record
	 *
	 * @param   Database_Query|array  where condition or database query
	 * @return  DataMapper_Entity
	 */
	public function getOne($query)
	{
		if (!$query instanceof Kohana_Database_Query)
		{
			$condition = $query;

			$query = DB::select();
			$query->where($condition[0], $condition[1], $condition[2]);
		}
		$query->from($this->table);
		$query->limit(1);
		$query->as_object($this->entityClass);

		$result = $query->execute();

		if ($result->count() === 0)
		{
			throw new Kohana_Exception('No records found');
		}
		else
		{
			return $result[0];
		}
	}

	/**
	 * Get all matching records
	 *
	 * @param   Database_Query|array  where condition or database query
	 * @return  Database_Result
	 */
	public function getAll($query = null)
	{
		if (!$query instanceof Kohana_Database_Query)
		{
			$conditions = $query;

			$query = DB::select();

			if ($conditions !== null)
			{
				$query->where($conditions[0], $conditions[1], $conditions[2]);
			}
		}
		$query->from($this->table);
		$query->as_object($this->entityClass);

		$result = $query->execute();

		if ($result->count() === 0)
		{
			throw new Kohana_Exception('No records found');
		}
		else
		{
			return $result;
		}
	}

	/**
	 * Save a record
	 *
	 * @param   DataMapper_Entity  entity to save
	 * @return  bool
	 */
	public function save($entity)
	{
		if (!$entity instanceof DataMapper_Entity)
		{
			throw new Kohana_Exception('First argument must be an entity object');
		}

		$pk = $this->getPrimaryKey($entity);
		if ($pk === null)
		{
			$result = $this->insert($entity);
		}
		else
		{
			$result = $this->update($entity);
		}

		return $result;
	}

	/**
	 * Insert a new record
	 *
	 * @param   DataMapper_Entity  entity to insert
	 * @return  bool
	 */
	public function insert($entity)
	{
		$data = $entity->getData();
		if (count($data))
		{
			$query = DB::insert($this->table, array_keys($data));
			$query->values(array_values($data));
			$result = $query->execute();

			return (bool)count($result);
		}

		throw new Kohana_Exception('No data to insert');
	}

	/**
	 * Update a record
	 *
	 * @param   DataMapper_Entity  entity to update
	 * @return  bool
	 */
	public function update($entity)
	{
		$data = $entity->getData();
		if (count($data))
		{
			$query = DB::update($this->table);
			$query->set($data);
			$result = $query->execute();

			return (bool)count($result);
		}

		throw new Kohana_Exception('No data to insert');
	}

	/**
	 * Delete records
	 *
	 * @param   DataMapper_Entity|array  entity to delete or condition to match
	 * @return  bool
	 */
	public function delete($condition)
	{
		$query = DB::delete($this->table);

		if ($condition instanceof DataMapper_Entity)
		{
			$condition->where($condition->getPrimaryKeyField(), '=', $condition->getPrimaryKey());
		}
		else
		{
			$query->where($condition[0], $condition[1], $condition[2]);
		}

		return (bool)$query->execute();
	}

//////////////////////////////
///// Validation Methods
//////////////////////////////

	/**
	 * Validate an entity's data
	 *
	 * @param   DataMapper_Entity  entity to validate
	 * @return  bool
	 */
	public function validate($entity)
	{
		return true;
	}

	/**
	 * Get validation errors
	 *
	 * @return  array
	 */
	public function getErrors()
	{
	}
}