<?php

/**
 * @group Mongo
 * 
 */
require_once realpath(dirname(__FILE__) . '/../ModelBase.php');

class Mongo_ModelBaseTest
    extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {

        $testEntrysBase = Mongo_ModelBase::find();
        foreach ($testEntrysBase as $key => $entry) {

            $entry->delete();
        }

        $testEntrysTest = TestMongoClass::find();
        foreach ($testEntrysTest as $key => $entry) {

            $entry->delete();
        }
    }

    public function testCanCreateClass()
    {
        $mongo = new Mongo_ModelBase();
        $this->assertInstanceOf('Mongo_ModelBase', $mongo);
    }

    public function testConnection()
    {

        $mongoArr = Mongo_ModelBase::find(array('test' => '3'));
        $this->assertEquals($mongoArr, array());

        Mongo_ModelBase::disconnect();
    }

    /*
     * @depends testConnection
     */

    public function testSaving()
    {
        $mongoModelBase       = new Mongo_ModelBase();
        $mongoModelBase->test = '3';
        $mongoDoc             = $mongoModelBase->save();

        $this->assertTrue($mongoDoc);

        $find = array('test'    => '3');
        $mongoRet = Mongo_ModelBase::findOne($find);
        $testVal  = $mongoRet->test;
        $this->assertEquals($mongoModelBase->test, $testVal);
    }

    /*
     * @depends testSaving
     */

    public function testLoad()
    {
        $mongoModelBase       = new Mongo_ModelBase();
        $mongoModelBase->test = '3';
        $mongoModelBase->save();


        $mongoRet = Mongo_ModelBase::findOne(array('test' => '3'));

        $mongoRetSecond = $mongoModelBase->load($mongoRet->id);

        $this->assertEquals($mongoRet, $mongoRetSecond);

        $this->assertFalse(Mongo_ModelBase::load('123a'));
    }

    /*
     * @depends testLoad
     */

    public function testDelete()
    {
        $mongoModelBase       = new Mongo_ModelBase();
        $mongoModelBase->test = '3';
        $mongoModelBase->save();

        $mongoRet = Mongo_ModelBase::find(array('test' => '3'));

        $this->assertGreaterThan(0, count($mongoRet));
        $this->assertEquals($mongoRet[0]->test, '3');

        foreach ($mongoRet as $ret) {
            $ret->delete();
        }
        $mongoRetNull = Mongo_ModelBase::findOne(array('test' => '3'));
        $this->assertNull($mongoRetNull);



        $arr = new Mongo_ModelBase();
        $this->assertFalse($arr->delete());
    }

    public function testInsert()
    {

        $arr = array('test' => '350');
        Mongo_ModelBase::insert($arr, false, true);

        $find = array('test'    => '350');
        $mongoRet = Mongo_ModelBase::findOne($find);
        $testVal  = $mongoRet->test;

        $this->assertEquals('350', $testVal);
    }

    public function testAccent()
    {
        $input       = "abcdefghijklmnopqrstuvwxyz";
        $outputArr[] = "[aÁÂÃÄÅÆàáâãäåæ]b[cç][d][eÉÊË?èéêë?]fgh[iÍÎÏ?ìíîï?]jkl";
        $outputArr[] = "m[nñ][o?ÒÓÔÕÖØðòóôõöø]pqr[s?ß]t[uÙÚÛÜùúûü]";
        $outputArr[] = "vwx[y¥Ýýÿ][z?]";


        $output = implode('', $outputArr);
        $mongo  = new Mongo_ModelBase();

        $this->assertEquals($output, $mongo->accentToRegex($input));
    }

    public function testConstructor()
    {

        try
        {
            new TestMongoConstructThrowExcpectionClass();
            $this->fail('Error not Trapped');
        } catch (Exception $e)
        {
            $this->assertEquals(Mongo_ModelBase::EXCEPTION_CNAME_REMOVED, $e->getMessage());
        }

        $array = array('destdoc' => get_called_class());
        $mongo    = new Mongo_ModelBase($array);

        $this->assertEquals($array, $mongo->getDocument());
    }

    public function testSetDocument()
    {
        $mongo = new Mongo_ModelBase();
        $this->assertFalse($mongo->setDocument());

        $array = array('destdoc'   => get_called_class(),
            'somevalue' => 'a122');
        $mongoa     = new Mongo_ModelBase();
        $mongoa->setDocument($array);
        $this->assertEquals($array, $mongoa->getDocument());
    }

    public function testDotNotation()
    {
        $array = array('destdoc'         => get_called_class()
        );
        $mongo            = new Mongo_ModelBase($array);
        $mongo->{'a.b.c'} = 'Dotnotation';
        $array['a']       = array('b' => array('c' => 'Dotnotation'));

        $this->assertEquals($array, $mongo->getDocument());

        $this->assertEquals('Dotnotation', $mongo->{'a.b.c'});


        $method = new ReflectionMethod('Mongo_ModelBase', '_getDotNotation');

        $method->setAccessible(TRUE);

        $this->assertNull($method->invoke(new Mongo_ModelBase, 'a.b.c', null));
    }

    public function testBatchInsert()
    {
        $users = array();
        for ($i = 0; $i < 100; $i++) {
            $users[] = array('tofind'   => 't',
                'username' => 'user' . $i,
                'i'        => $i);
        }
        Mongo_ModelBase::batchInsert($users);
        $findArray = array('tofind' => 't');

        $count = Mongo_ModelBase::count($findArray);
        $this->assertEquals(100, $count);
    }

    public function testFindAll()
    {
        $users = array();
        for ($i = 0; $i < 43; $i++) {
            $users[] = array(
                'tofind'   => 't',
                'username' => 'user' . $i,
                'i'        => $i
            );
        }
        Mongo_ModelBase::batchInsert($users);


        $ret = Mongo_ModelBase::findAll();
        $this->assertEquals(43, count($ret));

        $findArrayGte = array('i' => array('$gte' => 4));

        $retGte = Mongo_ModelBase::find($findArrayGte, null, array('i' => -1));

        $this->assertEquals(39, count($retGte));
    }

    public function testFindSkipAndLimit()
    {
        $users = array();
        for ($i = 0; $i < 100; $i++) {
            $users[] = array('tofind'   => 't',
                'username' => 'user' . $i,
                'i'        => $i);
        }
        Mongo_ModelBase::batchInsert($users);
        $findArray = array('tofind' => 't');

        $return = Mongo_ModelBase::find($findArray, null, null, 10);
        $this->assertEquals(10, count($return));

        $returnMax = Mongo_ModelBase::find($findArray, null, null, null, 85);
        $this->assertEquals(15, count($returnMax));
    }

    public function testSaveAndEdit()
    {

        $mongo      = new Mongo_ModelBase();
        $mongo->aaa = '1234';
        $mongo->abc = '12345';
        $mongo->save();
        $findArray  = array('aaa'    => '1234');
        $finding = Mongo_ModelBase::findOne($findArray);

        $this->assertEquals('12345', $finding->abc);

        $finding->abc = 123456;
        $finding->save();

        $findArraySecond = array('aaa'          => '1234');
        $findingSecond = Mongo_ModelBase::findOne($findArraySecond);


        $this->assertEquals(123456, $findingSecond->abc);



        $findingSecond->abcdefg = 'someValue';
        $findingSecond->save();


        $findingNeu = Mongo_ModelBase::findOne($findArray);


        $this->assertEquals(123456, $findingNeu->abc);
        $this->assertEquals('someValue', $findingNeu->abcdefg);
    }

    public function testCreateSave()
    {
        /**
         * 1. Create Mongo Doc
         * 2. Save it
         * 3. Find It
         * 4. Create new Doc with the searched Doc
         * 5. modify the new Doc
         * 6. Find the updated Doc
         * 7. Verify update
         */
        $mongo            = new Mongo_ModelBase();
        $mongo->testKey   = "testValue";
        $mongo->desc      = get_called_class();
        $mongo->keyToFind = 1;
        $mongo->save();


        $findArr = array('keyToFind'  => 1);
        $mongoSecond = Mongo_ModelBase::findOne($findArr);

        $id = $mongoSecond->_id;

        $this->assertInstanceOf('Mongo_ModelBase', $mongoSecond);

        $this->assertEquals('testValue', $mongoSecond->testKey);
        $this->assertEquals(1, $mongoSecond->keyToFind);


        $doc      = $mongoSecond->getDocument();
        $newMongo = new Mongo_ModelBase($doc);

        $newMongo->modifiedValue = 'Something';


        $newMongo->save();



        $find = Mongo_ModelBase::load($id);
        $this->assertEquals('testValue', $find->testKey);
        $this->assertEquals(1, $find->keyToFind);
        $this->assertEquals('Something', $find->modifiedValue);
    }

    public function testToString()
    {
        $array = array('sss' => 'asds', '_id' => new MongoId());

        $mongo = new Mongo_ModelBase($array);

        $this->assertEquals('Mongo_modelbaseObject ID:' . $array['_id'], $mongo->__toString());
    }

    public function testSet()
    {
        $array = array('sss' => 'asds', '_id' => new MongoId());

        $mongo       = new Mongo_ModelBase($array);
        $mongo->test = "someValue";
        $this->assertEquals("someValue", $mongo->test);

        $this->assertTrue($mongo->__isset('test'));

        $mongo->test = null;
        $this->assertNull($mongo->test);
        $this->assertFalse($mongo->__isset('test'));
    }

    public function testUnSet()
    {
        $array = array('sss' => 'asds', '_id' => new MongoId());

        $mongo = new Mongo_ModelBase($array);

        $this->assertTrue($mongo->__isset('sss'));
        $mongo->__unset('sss');
        $this->assertFalse($mongo->__isset('sss'));
    }

    public function testSpecialUpdate()
    {
        $mongoModelBase               = new Mongo_ModelBase();
        $mongoModelBase->tt           = 4;
        $mongoModelBase->specialfield = 57;
        $mongoDoc                     = $mongoModelBase->save();

        $this->assertTrue($mongoDoc);

        $mongoRet =
            Mongo_ModelBase::findOne(array('tt' => 4));


        $updateArray = array(
            '$set' => array('ta'   => 1),
            '$inc' => array('specialfield' => 2)
        );
        $mongoRet->specialUpdate($updateArray, array());


        $mongoRetSecond =
            Mongo_ModelBase::findOne(array('tt' => 4));
        $this->assertEquals(1, $mongoRetSecond->ta);
        $this->assertEquals(59, $mongoRetSecond->specialfield);
    }

    public function testDistinct()
    {
        $testMongo       = new Mongo_ModelBase();
        $testMongo->name = 'Joe';
        $testMongo->age  = 4;
        $testMongo->save();

        $testMongoSec       = new Mongo_ModelBase();
        $testMongoSec->name = 'Sally';
        $testMongoSec->age  = 22;
        $testMongoSec->save();

        $testMongoThi       = new Mongo_ModelBase();
        $testMongoThi->name = 'Dave';
        $testMongoThi->age  = 22;
        $testMongoThi->save();

        $testMongoFou       = new Mongo_ModelBase();
        $testMongoFou->name = 'Moly';
        $testMongoFou->age  = 87;
        $testMongoFou->save();


        $control = array(4, 22, 87);

        $return = Mongo_ModelBase::distinct('age');

        $this->assertEquals($control, $return['values']);

        $controlSecond = array(22, 87);
        $query = array('age' => array('$gte' => 18));

        $returnSecond = Mongo_ModelBase::distinct('age', $query);

        $this->assertEquals($controlSecond, $returnSecond['values']);
    }

    public function testProfiling()
    {

        Mongo_ModelBase::setProfilingLevel(1);

        $return = Mongo_ModelBase::getProfilingLevel();

        $this->assertEquals(1, $return['was']);
        $this->assertEquals(1, $return['ok']);

        Mongo_ModelBase::setProfilingLevel(2);

        $returnArr = Mongo_ModelBase::getProfilingLevel();

        $this->assertEquals(2, $returnArr['was']);
        $this->assertEquals(1, $returnArr['ok']);
    }

    public function testGetDocument()
    {

        $control = array(
            'someVal'      => 1234412,
            'specialField' => 'we have a winner'
        );
        $controlArr    = array(
            'someVal'      => 1234412,
            'specialField' => 'we have a winner'
        );

        $mongoModelBase = new Mongo_ModelBase($control);

        $mongoModelBase->save();

        $return = Mongo_ModelBase::findOne(array('someVal'       => 1234412));
        $control['_id'] = $return->id;

        $this->assertEquals($controlArr, $return->getDocument(false));
        $this->assertEquals($control, $return->getDocument());
        $this->assertEquals($control, $return->getDocument(true));
    }

    public function testSetterGetterAdvanced()
    {

        $classToTest = new TestMongoClass();

        $classToTest->name                           = 'somevalue';
        $classToTest->{'some.Field.withAnLong.Name'} = 'has also an Value';
        $classToTest->asf                            = 'something';
        $classToTest->save();
        $this->assertEquals('somevalue', $classToTest->name);
        $this->assertEquals('somevalue', $classToTest->n);
        $this->assertEquals('has also an Value', $classToTest->{'some.Field.withAnLong.Name'});
        $this->assertEquals('has also an Value', $classToTest->{'s'});
        $this->assertEquals('something', $classToTest->{'anotherSpecialField'});
        $this->assertEquals('something', $classToTest->{'asf'});
    }

    public function testConnect()
    {

        TestMongoClass::find();

        $this->assertNotEquals(NULL, TestMongoClass::getMongo());
        $this->assertEquals(TestMongoClass::getMongo(), TestMongoClass::connect('TestMongoClass'));
    }

    public function testIndexes()
    {
        $docOne = array('a'     => '1');
        $ab     = new Mongo_ModelBase($docOne);
        $ab->save();
        $docTwo = array('a'       => '2');
        $ac       = new Mongo_ModelBase($docTwo);
        $ac->save();
        $docThree = array('a'      => '3');
        $ad      = new Mongo_ModelBase($docThree);
        $ad->save();
        $docFour = array('a' => '4');
        $ae = new Mongo_ModelBase($docFour);
        $ae->save();

        /**
         * No Index Set
         * only _id by default
         */
        $indexs = Mongo_ModelBase::getIndexInfo();
        $this->assertEquals('_id_', $indexs[0]['name']);


        $this->assertEquals(array(), Mongo_ModelBase::$indexes);
        Mongo_ModelBase::$indexes = array('a' => 1);

        $this->assertEquals(true, Mongo_ModelBase::setUpIndexes());



        $indexsSecond = Mongo_ModelBase::getIndexInfo();
        $this->assertEquals('_id_', $indexsSecond[0]['name']);

        $this->assertEquals('a_1', $indexsSecond[1]['name']);
    }

}

class TestMongoConstructThrowExcpectionClass
    extends Mongo_ModelBase
{

// @codingStandardsIgnoreStart

    public static $_collectionName = "TestMongoClass";

// @codingStandardsIgnoreEnd
}

class TestMongoClass
    extends Mongo_ModelBase
{

    public static $connectOptions = array(
        'username'      => 'test',
        'password'      => 'testing',
        'databasename'  => 'MongoAdvancedTestConnectionDatabaseName',
        'hostname'      => 'localhost',
        'port'          => '27017'
    );
    public static $collectionName = "TestMongoClass";
    public static $fieldnames     = array(
        'name'                       => 'n',
        'some.Field.withAnLong.Name' => 's',
        'anotherSpecialField'        => 'asf');

    public function __construct($document = null)
    {
        parent::disconnect();
        parent::__construct($document);
    }

}

