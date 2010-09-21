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
		// Get only the fields from the class instance, not its descendants
		$getFields = create_function('$obj', 'return get_object_vars($obj);');
		$fields = $getFields($this);

		// Field defaults
		$defaults = array(
			'primary' => false
		);

		// Go through and set up each field
		foreach ($fields as $name => $options)
		{
			// Merge the defaults
			$options = array_merge($defaults, $options);
			
			// Is this the primary field?
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
		// Get only one record using the primary key
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
		// Check if database query has been given
		if (!$query instanceof Kohana_Database_Query)
		{
			$condition = $query;

			// Query not given so create one
			$query = DB::select();
			$query->where($condition[0], $condition[1], $condition[2]);
		}
		$query->from($this->table);            // Use the specified entity table
		$query->limit(1);                      // Limit to only 1 record
		$query->as_object($this->entityClass); // Use the defined entity class
		$result = $query->execute();           // Execute the query

		// Did we get a result?
		if ($result->count() === 0)
		{
			// No results
			throw new DataMapper_Exception('No records found');
		}
		else
		{
			// Return the first (and only) row
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
		// Check if database query has been given
		if (!$query instanceof Kohana_Database_Query)
		{
			$conditions = $query;

			// Query not given so create one
			$query = DB::select();
			if ($conditions !== null)
			{
				$query->where($conditions[0], $conditions[1], $conditions[2]);
			}
		}
		$query->from($this->table);            // Use the specified entity table
		$query->as_object($this->entityClass); // Use the defined entity class
		$result = $query->execute();           // Execute the query

		if ($result->count() === 0)
		{
			// No results
			throw new DataMapper_Exception('No records found');
		}
		else
		{
			// Return all the results
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
			// Convert the array to a new entity
			if (is_array($entity))
			{
				$data = $entity;

				$entity = $this->getEntity();
				$entity->setData($data);
			}
			else
			{
				throw new InvalidArgumentException('Argument must be instance of DataMapper_Entity or an array');
			}
		}

		// Find out whether to insert or update this entity
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
		// Make sure we have some data to insert
		if (count($data))
		{
			// Create the database query and execute
			$query = DB::insert($this->table, array_keys($data));
			$query->values(array_values($data));
			$result = $query->execute();

			// Return the result
			return (bool)count($result);
		}

		throw new DataMapper_Exception('No data to insert');
	}

	/**
	 * Update a record
	 *
	 * @param   DataMapper_Entity  entity to update
	 * @return  bool
	 */
	public function update($entity)
	{
		$data = $entity->getModifiedData();
		// Make sure we have some data to update
		if (count($data))
		{
			// Create the database query and execute
			$query = DB::update($this->table);
			$query->set($data);
			$result = $query->execute();

			// Return the result
			return (bool)count($result);
		}

		throw new DataMapper_Exception('No data to insert');
	}

	/**
	 * Delete records
	 *
	 * @param   DataMapper_Entity|array  entity to delete or condition to match
	 * @return  bool
	 */
	public function delete($condition)
	{
		// Create the database query
		$query = DB::delete($this->table);

		if ($condition instanceof DataMapper_Entity)
		{
			// Use the primary key field and value
			$query->where($this->getPrimaryKeyField(), '=', $this->getPrimaryKey($condition));
		}
		else if (is_array($condition))
		{
			// Use the specified where condition
			$query->where($condition[0], $condition[1], $condition[2]);
		}
		else
		{
			throw new InvalidArgumentException('Argument must be instance of DataMapper_Entity or an array');
		}

		// Execute the query
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
		// Create new validate using entity data
		$val = new Validate($entity->getData());

		// Go through each field and add rules to validator
		$count = 0;
		foreach ($this->fields as $field => $options)
		{
			if (isset($options['rules']))
			{
				$val->rules($options['rules']);
				$count++;
			}
		}

		// No rules so no point trying to validate
		if ($count == 0)
		{
			return true;
		}

		// Save the result
		$result = $val->check();

		if (!$result)
		{
			// Save the errors
			$this->errors = $val->errors();
		}

		return $result;
	}

	/**
	 * Get validation errors
	 *
	 * @return  array
	 */
	public function getErrors()
	{
		return $this->errors;
	}
}