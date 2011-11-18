<?php
if (file_exists('../ModelBase.php')) {
    require_once '../ModelBase.php';
}
class ModelBaseTest extends PHPUnit_Framework_TestCase
{

	
	
	public function testConnection() {
		$mongoModelBase= new Mongo_ModelBase();
	
		$mongoArr = $mongoModelBase::find(array('test' => '3'));
		$this->assertEquals($mongoArr,array());
		
	
	}
	
	/*
	* @depends testConnection
	*/
	public function testSaving() {
		
		$mongoModelBase= new Mongo_ModelBase();
		$mongoModelBase->test='3';
		$mongoDoc = $mongoModelBase->save();
		$this->assertTrue($mongoDoc);
		
		$mongoModelBase2= new Mongo_ModelBase();
		$mongoRet = $mongoModelBase2::findOne(array('test' => '3'));
		$testVal	=	$mongoRet->test;
		
		$this->assertEquals($mongoModelBase->test,	$testVal);
	}
	
	
	/*
	* @depends testSaving
	*/
	public function testDelete() {
		$mongoModelBase= new Mongo_ModelBase();
		$mongoRet = $mongoModelBase::findOne(array('test' => '3'));
		$this->assertEquals($mongoRet->test,'3');
		$mongoRet->delete();
		
		$mongoRet = $mongoModelBase::findOne(array('test' => '3'));
		
		$this->AssertNull($mongoRet);
	}

}