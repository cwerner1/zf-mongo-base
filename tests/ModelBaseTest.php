<?php

/**
 * @group Mongo
 * 
 */
require_once realpath(dirname(__FILE__) . '/../ModelBase.php');

class Mongo_ModelBaseTest
    extends PHPUnit_Framework_TestCase
{

    public function setUp(
    )
    {

        $testEntrys = Mongo_ModelBase::find();
        foreach ($testEntrys as $key => $entry) {

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
        $mongoModelBase = new Mongo_ModelBase();
        $mongoArr       = $mongoModelBase::find(array('test' => '3'));
        $this->assertEquals($mongoArr, array());


        $mongoModelBase->disconnect();
        $mongoArr = $mongoModelBase::find(array('test' => '3'));
        $this->assertEquals($mongoArr, array());
    }

    /*
     * @depends testConnection
     */

    public function testSaving()
    {
        $mongoModelBase = new Mongo_ModelBase();
        $mongoModelBase->test = '3';
        $mongoDoc       = $mongoModelBase->save();

        $this->assertTrue($mongoDoc);
        $mongoModelBaseSecond = new Mongo_ModelBase();
        $mongoRet             = $mongoModelBaseSecond::findOne(array('test'   => '3'));
        $testVal = $mongoRet->test;
        $this->assertEquals($mongoModelBase->test, $testVal);
    }

    /*
     * @depends testSaving
     */

    public function testLoad()
    {
        $mongoModelBase = new Mongo_ModelBase();
        $mongoModelBase->test = '3';
        $mongoDoc       = $mongoModelBase->save();

        $mongoModelBase = new Mongo_ModelBase();
        $mongoRet       = $mongoModelBase::findOne(array('test' => '3'));

        $mongoRetSecond = $mongoModelBase->load($mongoRet->id);

        $this->assertEquals($mongoRet, $mongoRetSecond);

        $this->assertFalse(Mongo_ModelBase::load('123a'));
    }

    /*
     * @depends testLoad
     */

    public function testDelete()
    {
        $mongoModelBase = new Mongo_ModelBase();
        $mongoModelBase->test = '3';
        $mongoDoc       = $mongoModelBase->save();

        $mongoModelBase = new Mongo_ModelBase();
        $mongoRet       = $mongoModelBase::find(array('test' => '3'));

        $this->assertGreaterThan(0, count($mongoRet));
        $this->assertEquals($mongoRet[0]->test, '3');

        foreach ($mongoRet as $ret) {
            $ret->delete();
        }
        $mongoRet = $mongoModelBase::findOne(array('test' => '3'));
        $this->assertNull($mongoRet);



        $arr = new Mongo_ModelBase();
        $this->assertFalse($arr->delete());
    }

    public function testInsert()
    {

        $arr = array('test'    => '350');
        $mongoDoc = Mongo_ModelBase::insert($arr, false, true);


        $mongoModelBaseSecond = new Mongo_ModelBase();
        $mongoRet             = $mongoModelBaseSecond::findOne(array('test'   => '350'));
        $testVal = $mongoRet->test;

        $this->assertEquals('350', $testVal);
    }

    public function testAccent()
    {
        $input  = "abcdefghijklmnopqrstuvwxyz";
        $output = "[aÁÂÃÄÅÆàáâãäåæ]b[cç][d][eÉÊË?èéêë?]fgh[iÍÎÏ?ìíîï?]jklm[nñ]" .
            "[o?ÒÓÔÕÖØðòóôõöø]pqr[s?ß]t[uÙÚÛÜùúûü]vwx[y¥Ýýÿ][z?]";

        $mongo = new Mongo_ModelBase();

        $this->assertEquals($output, $mongo->accentToRegex($input));
    }

    public function testConstructor()
    {

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
        $mongo      = new Mongo_ModelBase();
        $mongo->setDocument($array);
        $this->assertEquals($array, $mongo->getDocument());
    }

    public function testDotNotation()
    {
        $array = array('destdoc'   => get_called_class()
        );
        $mongo      = new Mongo_ModelBase($array);
        $mongo->{'a.b.c'} = 'Dotnotation';
        $array['a'] = array('b' => array('c' => 'Dotnotation'));

        $this->assertEquals($array, $mongo->getDocument());

        $this->assertEquals('Dotnotation', $mongo->{'a.b.c'});


        $method = new ReflectionMethod(
                'Mongo_ModelBase', '_getDotNotation'
        );

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
            $users[] = array('tofind'   => 't',
                'username' => 'user' . $i,
                'i'        => $i);
        }
        Mongo_ModelBase::batchInsert($users);
        $findArray = array('tofind' => 't');

        $ret = Mongo_ModelBase::findAll();
        $this->assertEquals(43, count($ret));


        $ret = Mongo_ModelBase::find(
                array('i' => array('$gte' => 4))
                , null, array('i' => -1)
        );

        $this->assertEquals(39, count($ret));
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

        $return = Mongo_ModelBase::find($findArray, null, null, null, 85);
        $this->assertEquals(15, count($return));
    }

    public function testSaveAndEdit()
    {

        $mongo     = new Mongo_ModelBase();
        $mongo->aaa = '1234';
        $mongo->abc = '12345';
        $mongo->save();
        $findArray = array('aaa'    => '1234');
        $finding = Mongo_ModelBase::findOne($findArray);

        $this->assertEquals('12345', $finding->abc);

        $finding->abc = 123456;
        $finding->save();

        $findArray = array('aaa'    => '1234');
        $finding = Mongo_ModelBase::findOne($findArray);


        $this->assertEquals(123456, $finding->abc);



        $finding->abcdefg = 'someValue';
        $finding->save();


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
        $mongo = new Mongo_ModelBase();
        $mongo->testKey = "testValue";
        $mongo->desc = get_called_class();
        $mongo->keyToFind = 1;
        $mongo->save();


        $mongo = null;
        $find  = array('keyToFind' => 1);
        $mongo      = Mongo_ModelBase::findOne($find);

        $id = $mongo->_id;

        $this->assertInstanceOf('Mongo_ModelBase', $mongo);

        $this->assertEquals('testValue', $mongo->testKey);
        $this->assertEquals(1, $mongo->keyToFind);


        $doc      = $mongo->getDocument();
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

        $mongo = new Mongo_ModelBase($array);
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
        $mongoModelBase = new Mongo_ModelBase();
        $mongoModelBase->tt = 4;
        $mongoModelBase->specialfield = 57;
        $mongoDoc       = $mongoModelBase->save();

        $this->assertTrue($mongoDoc);

        $mongoRet =
            Mongo_ModelBase::findOne(array('tt' => 4));


        $updateArray = array(
            '$set' => array('ta'   => 1),
            '$inc' => array('specialfield' => 2)
        );
        $mongoRet->specialUpdate($updateArray, array());


        $mongoRet =
            Mongo_ModelBase::findOne(array('tt' => 4));
        $this->assertEquals(1, $mongoRet->ta);
        $this->assertEquals(59, $mongoRet->specialfield);
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
        $classToTest->name = 'somevalue';
        $classToTest->{'some.Field.withAnLong.Name'} = 'has also an Value';
        $classToTest->asf = 'something';

        $this->assertEquals('somevalue', $classToTest->name);
          $this->assertEquals('somevalue', $classToTest->n);
        $this->assertEquals('has also an Value', $classToTest->{'some.Field.withAnLong.Name'});
        $this->assertEquals('has also an Value', $classToTest->{'s'});
        $this->assertEquals('something', $classToTest->{'anotherSpecialField'});
        $this->assertEquals('something', $classToTest->{'asf'});
    }

}

class TestMongoClass
    extends Mongo_ModelBase
{

    public static $_collectionName = "TestMongoClass";
    public static $fieldnames      = array(
        'name'                       => 'n',
        'some.Field.withAnLong.Name' => 's',
        'anotherSpecialField'        => 'asf');

}
