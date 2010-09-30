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
			'primary'  => false,
			'relation' => false
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

			// Is this a relation?
			if ($options['relation'] !== false)
			{
				$this->relations[$name] = $options;
				continue;
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
		if ($this->primaryKeyField === null)
		{
			throw new DataMapper_Exception('No primary key field set for ' . __CLASS__);
		}
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
	public function getEmpty()
	{
		$entity = new $this->entityClass();
		return $this->loadRelations($entity);
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
	public function getOne($query = array())
	{
		// Check if database query has been given
		if (!$query instanceof Kohana_Database_Query)
		{
			// Query not given so create one
			$query = $this->createQuery($query);
		}
		$query->from($this->table);            // Use the specified entity table
		$query->limit(1);                      // Limit to only 1 record
		$query->as_object($this->entityClass); // Use the defined entity class
		$result = $query->execute();           // Execute the query

		// Did we get a result?
		if ($result->count() === 0)
		{
			// No result
			return false;
		}
		else
		{
			// Return the first (and only) row
			return $this->loadRelations($result[0]);
		}
	}

	/**
	 * Get all matching records
	 *
	 * @param   Database_Query|array  where condition or database query
	 * @return  Database_Result
	 */
	public function getAll($query = array())
	{
		// Check if database query has been given
		if (!$query instanceof Kohana_Database_Query)
		{
			// Query not given so create one
			$query = $this->createQuery($query);
		}
		$query->from($this->table);            // Use the specified entity table
		$query->as_object($this->entityClass); // Use the defined entity class
		$result = $query->execute();           // Execute the query

		if ($result->count() > 0)
		{
			// Load the relations for each entity
			foreach ($result as $entity)
			{
				$this->loadRelations($entity);
			}
		}

		// Return all the results
		return $result;
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
		$entityData = $entity->getData();
		// Make sure we have some data to insert
		if (count($entityData))
		{
			// Get the data for the defined fields only
			$data = array();
			foreach ($entityData as $field => $value)
			{
				if ($this->fieldExists($field))
				{
					$data[$field] = $value;
				}
			}

			if (count($data))
			{
				// Create the database query and execute
				$query = DB::insert($this->table, array_keys($data));
				$query->values(array_values($data));
				$result = $query->execute();

				// Set the primary key
				$primaryKeyField = $this->getPrimaryKeyField();
				$entity->$primaryKeyField = $result[0];

				// Get the result
				$result = (bool)count($result);

				// Update all the where values for relations
				foreach ($this->relations as $name => $options)
				{
					if (isset($options['where']))
					{
						$where = $this->getRelationWhere($entity, $options['where']);
						$entity->$name->setConditions($where);
					}
				}

				// Save relations
				if ($result)
				{
					$this->saveRelations($entity);
				}

				return $result;
			}
		}

		// Nothing got inserted
		return false;
	}

	/**
	 * Update a record
	 *
	 * @param   DataMapper_Entity  entity to update
	 * @return  bool
	 */
	public function update($entity)
	{
		$entityData = $entity->getModifiedData();
		// Make sure we have some data to update
		if (count($entityData))
		{
			$data = array();
			foreach ($entityData as $field => $value)
			{
				if ($this->fieldExists($field))
				{
					$data[$field] = $value;
				}
			}

			if (count($data))
			{
				// Create the database query and execute
				$query = DB::update($this->table)->set($data)->where($this->getPrimaryKeyField(), '=', $this->getPrimaryKey($entity));
				$result = $query->execute();

				// Get the result
				$result = (bool)count($result);

				$this->saveRelations($entity);

				return $result;
			}
		}

		return true;
		throw new DataMapper_Exception('No data to update');
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

	/**
	 * Create a database query from where conditions
	 *
	 * @param   array  where conditions
	 * @return  Database_Query_Builder_Select
	 */
	protected function createQuery(array $conditions)
	{
		// Create new query
		$query = DB::select();

		if (isset($conditions[0]))
		{
			if (is_array($conditions[0]))
			{
				// Array of arrays
				foreach ($conditions as $condition)
				{
					$query->where($condition[0], $condition[1], $condition[2]);
				}
			}
			else
			{
				// Single array
				$query->where($conditions[0], $conditions[1], $conditions[2]);
			}
		}
		else
		{
			// Associative array
			foreach ($conditions as $field => $value)
			{
				$query->where($field, '=', $value);
			}
		}

		return $query;
	}

//////////////////////////////
///// Relation Methods
//////////////////////////////

	/**
	 * Get all the defined relations
	 *
	 * @return  array
	 */
	public function getRelations()
	{
		return $this->relations;
	}

	/**
	 * Enter entity values into given where conditions
	 *
	 * @param   DataMapper_Entity  entity to get values from
	 * @param   array  where conditions to use
	 * @return  array
	 */
	public function getRelationWhere(DataMapper_Entity $entity, $where)
	{
		if (isset($where[0]))
		{
			if (is_array($where[0]))
			{
				// Array of arrays
				foreach ($where as $condition)
				{
					$field        = $condition[2];
					$condition[2] = $entity->$field;
				}
			}
			else
			{
				// Single array
				$field = $where[2];
				$where[2] = $entity->$field;
			}
		}
		else
		{
			// Associative array
			foreach ($where as $field => $value)
			{
				$where[$field] = $entity->$value;
			}
		}
		return $where;
	}

	/**
	 * Load all related fields with relation classes
	 *
	 * @param   DataMapper_Entity  entity to load relations for
	 * @return  DataMapper_Entity
	 */
	public function loadRelations(DataMapper_Entity $entity)
	{
		// Go through each relation
		foreach ($this->relations as $name => $options)
		{
			// Get the name of the related mapper
			$mapper = isset($options['mapper']) ? $options['mapper'] : false;
			if (!$mapper)
			{
				throw new DataMapper_Exception('Relationship mapper for ' . $name . ' has not been defined');
			}
			$mapper = new $mapper();

			// Get relation class name
			$relationClass = 'DataMapper_Relation_' . $options['relation'];

			// Remove unneeded options
			unset($options['mapper']);
			unset($options['relation']);

			// Load the values into the relation wheres
			$where = array();
			if (isset($options['where']))
			{
				$where = $options['where'];
				unset($options['where']);

				$where = $this->getRelationWhere($entity, $where);
			}

			// Create instance of relation
			$entity->$name = new $relationClass($mapper, $where, $options);
		}

		return $entity;
	}

	/**
	 * Save all the relations for an entity
	 *
	 * @param   DataMapper_Entity  entity to save relations for
	 * @return  DataMapper_Entity
	 */
	public function saveRelations(DataMapper_Entity $entity)
	{
		// Go through each relation
		foreach ($this->relations as $name => $options)
		{
			$relation        = $entity->$name;
			$relatedMapper   = $relation->getMapper();
			$relatedEntities = $relation->getResults();

			// Save each related entity
			foreach ($relatedEntities as $relatedEntity)
			{
				$relatedMapper->save($relatedEntity);
			}
		}

		return $entity;
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