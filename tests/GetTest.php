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
			$entity = $this->mapper->getEmpty();
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
		DB::query(NULL, 'TRUNCATE TABLE files')->execute();
		DB::query(NULL, 'TRUNCATE TABLE images')->execute();
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

		$this->assertTrue(count($items) == 15);
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

	/**
	 * Test the delete method
	 */
	public function testDelete()
	{
		$item = $this->mapper->get(5);
		$this->mapper->delete($item);

		$item = $this->mapper->get(5);
		$this->assertTrue($item === false);
	}

	public function testOtherMethods()
	{
		// Test fieldExists
		$this->assertTrue($this->mapper->fieldExists('name'));
		$this->assertTrue($this->mapper->fieldExists('title'));
		$this->assertTrue($this->mapper->fieldExists('content'));

		// Test getPrimaryKeyField
		$this->assertTrue($this->mapper->getPrimaryKeyField() == 'id');

		// Test getFields
		$this->assertEquals($this->mapper->getFields(),
			array(
				'id'      => array(),
				'name'    => array(),
				'title'   => array(),
				'content' => array(),
				'file_id' => array()
			)
		);
	}

	public function testValidate()
	{
		$item = $this->mapper->getEmpty();
		$item->name    = 'Test';
		$item->title   = 'Test title';
		$item->content = 'Test content';

		$this->assertTrue($this->mapper->validate($item));
	}

	public function testRelations()
	{
		$test = $this->mapper->getEmpty();
		$test->name    = 'Test';
		$test->title   = 'Test title';
		$test->content = 'Test content';

		$fileMapper = DataMapper::instance('file');
		$file = $fileMapper->getEmpty();
		$file->name = 'Test';
		$fileMapper->save($file);

		$imageMapper = DataMapper::instance('image');
		$image = $imageMapper->getEmpty();
		$test->images[] = $image;

		$test->file_id = $file->id;
		$this->mapper->save($test);

		$newTest = $this->mapper->get($test->id);
		$this->assertTrue($newTest->id == $test->id);
		$this->assertTrue($newTest->file->id == $file->id);
		$this->assertTrue(count($newTest->images) == 1);
	}
}

class Mapper_Test extends DataMapper
{
	protected $table = 'tests';

	public $id      = array('primary' => true);
	public $name    = array('rules' => array('not_empty' => null));
	public $title   = array();
	public $content = array();
	public $file_id = array();

	public $file = array(
		'relation' => 'hasone',
		'mapper'   => 'file',
		'where'    => array('id', '=', 'file_id')
	);

	public $images = array(
		'relation' => 'hasmany',
		'mapper'   => 'image',
		'where'    => array('test_id', '=', 'id')
	);
}

class Mapper_File extends DataMapper
{
	protected $table = 'files';

	public $id   = array('primary' => true);
	public $name = array('rules' => array('not_empty' => null));
}

class Mapper_Image extends DataMapper
{
	protected $table = 'images';

	public $id      = array('primary' => true);
	public $test_id = array();
	public $name    = array('rules' => array('not_empty' => null));
}