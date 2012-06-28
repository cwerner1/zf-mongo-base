<?php

/**
 * @group Mongo
 * 
 */

    require_once 'Mongo/ModelBase.php';

class Mongo_ModelBaseTest extends PHPUnit_Framework_TestCase {

    public function setUp(
    ) {

        $testEntrys = Mongo_ModelBase::find();
        foreach ($testEntrys as $key => $entry) {

            $entry->delete();
        }
    }

    public function testCanCreateClass() {
        $mongo = new Mongo_ModelBase();
        $this->assertInstanceOf('Mongo_ModelBase', $mongo);
    }

    public function testConnection() {
        $mongoModelBase = new Mongo_ModelBase();
        $mongoArr = $mongoModelBase::find(array('test' => '3'));
        $this->assertEquals($mongoArr, array());
    }

    /*
     * @depends testConnection
     */

    public function testSaving() {
        $mongoModelBase = new Mongo_ModelBase();
        $mongoModelBase->test = '3';
        $mongoDoc = $mongoModelBase->save();

        $this->assertTrue($mongoDoc);
        $mongoModelBaseSecond = new Mongo_ModelBase();
        $mongoRet = $mongoModelBaseSecond::findOne(array('test' => '3'));
        $testVal = $mongoRet->test;
        $this->assertEquals($mongoModelBase->test, $testVal);
    }

    /*
     * @depends testSaving
     */

    public function testLoad() {
        $mongoModelBase = new Mongo_ModelBase();
        $mongoModelBase->test = '3';
        $mongoDoc = $mongoModelBase->save();

        $mongoModelBase = new Mongo_ModelBase();
        $mongoRet = $mongoModelBase::findOne(array('test' => '3'));

        $mongoRetSecond = $mongoModelBase->load($mongoRet->id);

        $this->assertEquals($mongoRet, $mongoRetSecond);
    }

    /*
     * @depends testLoad
     */

    public function testDelete() {
        $mongoModelBase = new Mongo_ModelBase();
        $mongoModelBase->test = '3';
        $mongoDoc = $mongoModelBase->save();

        $mongoModelBase = new Mongo_ModelBase();
        $mongoRet = $mongoModelBase::find(array('test' => '3'));

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

    public function testInsert() {

        $arr = array('test' => '3');
        $mongoDoc = Mongo_ModelBase::insert($arr, false, true);


        $mongoModelBaseSecond = new Mongo_ModelBase();
        $mongoRet = $mongoModelBaseSecond::findOne(array('test' => '3'));
        $testVal = $mongoRet->test;

        $this->assertEquals('3', $testVal);
    }

    public function testAccent() {
        $input = "abcdefghijklmnopqrstuvwxyz";
        $output = "[aÁÂÃÄÅÆàáâãäåæ]b[cç][d][eÉÊË?èéêë?]fgh[iÍÎÏ?ìíîï?]jklm[nñ][o?ÒÓÔÕÖØðòóôõöø]pqr[s?ß]t[uÙÚÛÜùúûü]vwx[y¥Ýýÿ][z?]";

        $mongo = new Mongo_ModelBase();

        $this->assertEquals($output, $mongo->accentToRegex($input));
    }

    public function testConstructor() {

        $array = array('destdoc' => get_called_class());
        $mongo = new Mongo_ModelBase($array);

        $this->assertEquals($array, $mongo->getDocument());
    }

    public function testDotNotation() {
        $array = array('destdoc' => get_called_class()
        );
        $mongo = new Mongo_ModelBase($array);
        $mongo->{'a.b.c'} = 'Dotnotation';
        $array['a'] = array('b' => array('c' => 'Dotnotation'));

        $this->assertEquals($array, $mongo->getDocument());

        $this->assertEquals('Dotnotation', $mongo->{'a.b.c'});
    }

    public function testBatchInsert() {
        $users = array();
        for ($i = 0; $i < 100; $i++) {
            $users[] = array('tofind' => 't',
                'username' => 'user' . $i,
                'i' => $i);
        }
        Mongo_ModelBase::batchInsert($users);
        $findArray = array('tofind' => 't');

        $count = Mongo_ModelBase::count($findArray);
        $this->assertEquals(100, $count);
    }

    public function testFindAll() {
        $users = array();
        for ($i = 0; $i < 43; $i++) {
            $users[] = array('tofind' => 't',
                'username' => 'user' . $i,
                'i' => $i);
        }
        Mongo_ModelBase::batchInsert($users);
        $findArray = array('tofind' => 't');

        $ret = Mongo_ModelBase::findAll();
        $this->assertEquals(43, count($ret));
    }

    public function testFindSkipAndLimit() {
        $users = array();
        for ($i = 0; $i < 100; $i++) {
            $users[] = array('tofind' => 't',
                'username' => 'user' . $i,
                'i' => $i);
        }
        Mongo_ModelBase::batchInsert($users);
        $findArray = array('tofind' => 't');

        $return = Mongo_ModelBase::find($findArray, null, null, 10);
        $this->assertEquals(10, count($return));

        $return = Mongo_ModelBase::find($findArray, null, null, null, 85);
        $this->assertEquals(15, count($return));
    }

    public function testSaveAndEdit() {

        $mongo = new Mongo_ModelBase();
        $mongo->aaa = '1234';
        $mongo->abc = '12345';
        $mongo->save();
        $findArray = array('aaa' => '1234');
        $finding = Mongo_ModelBase::findOne($findArray);

        $this->assertEquals('12345', $finding->abc);

        $finding->abc = 123456;
        $finding->save();

        $findArray = array('aaa' => '1234');
        $finding = Mongo_ModelBase::findOne($findArray);


        $this->assertEquals(123456, $finding->abc);



        $finding->abcdefg = 'someValue';
        $finding->save();


        $findingNeu = Mongo_ModelBase::findOne($findArray);


        $this->assertEquals(123456, $findingNeu->abc);
        $this->assertEquals('someValue', $findingNeu->abcdefg);
    }

    public function testCreateSave() {
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
        $find = array('keyToFind' => 1);
        $mongo = Mongo_ModelBase::findOne($find);

        $id = $mongo->_id;

        $this->assertInstanceOf('Mongo_ModelBase', $mongo);

        $this->assertEquals('testValue', $mongo->testKey);
        $this->assertEquals(1, $mongo->keyToFind);


        $doc = $mongo->getDocument();
        $newMongo = new Mongo_ModelBase($doc);

        $newMongo->modifiedValue = 'Something';


        $newMongo->save();



        $find = Mongo_ModelBase::load($id);
        $this->assertEquals('testValue', $find->testKey);
        $this->assertEquals(1, $find->keyToFind);
        $this->assertEquals('Something', $find->modifiedValue);
    }

    public function testToString() {
        $array = array('sss' => 'asds', '_id' => new MongoId());

        $mongo = new Mongo_ModelBase($array);

        $this->assertEquals('Mongo_modelbaseObject ID:' . $array['_id'], $mongo->__toString());
    }

}