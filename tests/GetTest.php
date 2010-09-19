<?php
class GetTest extends PHPUnit_Framework_TestCase
{
	protected $mapper;

	/**
	 * Setup
	 */
	public function setUp()
	{
		$this->mapper = DataMapper::instance('test');

		for ($i = 1; $i <= 100; $i++)
		{
			$entity = $this->mapper->getEntity();
			$entity->name = 'Name ' . $i;
			$entity->title = 'Title ' . $i;
			$entity->content = 'Content ' . $i;

			$this->mapper->save($entity);
		}
	}

	/**
	 * Teardown
	 */
	public function tearDown()
	{
		DB::query(NULL, 'TRUNCATE TABLE tests')->execute();
	}

	/**
	 * Test the get method
	 */
	public function testGet()
	{
		$item = $this->mapper->get(5);

		$this->assertTrue($item instanceof DataMapper_Entity);
		$this->assertTrue($item->name == 'Name 5');
	}

	/**
	 * Test the getOne method
	 */
	public function testGetOne()
	{
		$item = $this->mapper->getOne(array('name', '=', 'Name 15'));

		$this->assertTrue($item->title == 'Title 15');
	}

	/**
	 * Test the getAll method
	 */
	public function testGetAll()
	{
		$items = $this->mapper->getAll(array('id', '<=', 15));

		$this->assertTrue($items->count() == 15);
	}
	
	/**
	 * Test the save method
	 */
	public function testSave()
	{
		$item = $this->mapper->get(5);
		$item->title = 'Title 5 changed';
		$this->mapper->save($item);
		
		$item = $this->mapper->get(5);
		$this->assertTrue($item->title == 'Title 5 changed');
	}
}

class Mapper_Test extends DataMapper
{
	protected $table = 'tests';

	public $id      = array('primary' => true);
	public $name    = array();
	public $title   = array();
	public $content = array();
}