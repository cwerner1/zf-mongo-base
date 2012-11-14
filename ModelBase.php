<?php

/**
 * Mongo Class to Connect to a Mongo Database
 * @package Mongo
 */
class Mongo_ModelBase
{

    const EXCEPTION_CNAME_REMOVED =
        "_collectionName has been removed, use collectioName instead";

    protected static $_accentStrings =
        'ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËẼÌÍÎÏĨÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëẽìíîïĩðñòóôõöøùúûüýÿ';
    protected static $_noAccentStrings =
        'SOZsozYYuAAAAAAACEEEEEIIIIIDNOOOOOOUUUUYsaaaaaaaceeeeeiiiiionoooooouuuuyy';

    /**
     * @var MongoDB
     */
    private static $_mongo = null;

    /**
     * @var MongoCollection
     */
    private static $_collection = null;

    /**
     * collectionName
     * @var string
     */
    public static $collectionName = null;
    protected $id = null;
    protected $_document = null;

    /**
     * Database Indexes
     */
    public static $indexes = array();

    /**
     * hold Connections Options
     * @var array
     */
    public static $connectOptions = null;

    /**
     * Contains all Variables and the short Mongo field
     * us as key the long name you would like to use to access the field, as
     * value use the name of the key it will store the value
     * For example array('name' => 'n')
     * now you could use $object->name to acces the field n, and vice versa for
     * saving
     * this would save space in the Database
     * be aware not to use duplicate Fields
     * @var array
     */
    public static $fieldnames = array();

    /**
     * Magic methods
     */

    /**
     * Constructor puts full object in $document variable and assigns $id
     * @param $document

     */
    public function __construct($document = null)
    {

        if (isset(static::$_collectionName)) {
            throw new Exception(Mongo_ModelBase::EXCEPTION_CNAME_REMOVED);
        }

        if (isset($document['_id'])) {
            $this->id = $document['_id'];
        }
        if ($document != null) {
            foreach ($document as $key => $value) {
                $this->__set($key, $value);
            }
        } else {
            $this->_document = array();
        }
    }

    /**
     * Return object ID
     */
    public function __toString()
    {
        return ucfirst(static::$collectionName) . "Object ID:" . $this->id;
    }

    /**
     * returns the MongoDB Object
     * @return MongoDB
     */
    public static function getMongo()
    {
        return self::$_mongo;
    }

    /**
     * Get values like an object
     * @param string $name
     */
    public function __get($name)
    {
        if ($name == "id" || $name == "_id") {
            return $this->id;
        }
        if (isset(static::$fieldnames[$name])) {
            $name = static::$fieldnames[$name];
        }


        if (false !== strpos($name, '.')) {
            return $this->_getDotNotation($name, $this->_document);
        }

        return isset($this->_document[$name]) ? $this->_document[$name] : null;
    }

    /**
     * Returns Document with Id
     * @param bool $withID    Returns Document with ID
     * @return array
     */
    public function getDocument($withID = true)
    {
        $arr = array();
        if ($this->id !== null) {
            $arr = array('_id' => $this->id);
        }
        if ($withID === true) {

            $arr = $arr + $this->_document;
        } else {

            $return = $this->_document;
            unset($return['id'], $return['_id']);

            return $return;
        }

        return $arr;
    }

    /**
     * Sets the Mongo Document without changing the ID
     * @param array $document
     */
    public function setDocument($document = null)
    {

        if ($document === null) {
            return false;
        }
        unset($document['_id']);

        $this->_document = $document;

        return $this;
    }

    /**
     * Set values like an object
     * @param string $name
     * @param mixed  $val
     */
    public function __set($name, $val)
    {

        if (isset(static::$fieldnames[$name])) {
            $name = static::$fieldnames[$name];
        }

        if (false !== strpos($name, '.')) {
            return $this->_setDotNotation($name, $val, $this->_document);
        }
        if ($val == null) {
            unset($this->_document[$name]);
        }
        $this->_document[$name] = $val;
    }

    /**
     * Check if variable is set in object
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->_document[$name]);
    }

    /**
     * Unset a variable in the object
     * @param $name
     */
    public function __unset($name)
    {
        unset($this->_document[$name]);
    }

    /**
     * Allows use of the dot notation in the __get function
     * Thanks to Ian White for this function:
     * https://github.com/ibwhite/simplemongophp
     * @param string    $fields  fields with dot notation
     * @param reference $current The current part of the array working in
     */
    protected function _getDotNotation($fields, &$current)
    {
        $i = strpos($fields, '.');
        if ($i !== false) {
            $field = substr($fields, 0, $i);
            if (!isset($current[$field])) {
                return null;
            }
            $current = & $current[$field];

            return $this->_getDotNotation(substr($fields, $i + 1), $current);
        } else {
            return isset($current[$fields]) ? $current[$fields] : null;
        }
    }

    /**
     * Allows use of the not notation in __set function
     * Thanks to Ian White for this function:
     *  https://github.com/ibwhite/simplemongophp
     * @param string    $fields
     * @param mixed     $value
     * @param reference $current
     */
    protected function _setDotNotation($fields, $value, &$current)
    {
        $i = strpos($fields, '.');
        if ($i !== false) {
            $field = substr($fields, 0, $i);
            if (!isset($current[$field])) {
                $current[$field] = array();
            }
            $current = & $current[$field];

            $shortField = substr($fields, $i + 1);

            return $this->_setDotNotation($shortField, $value, $current);
        } else {
            if ($value === null) {
                unset($current[$fields]);
            } else {
                $current[$fields] = $value;
            }
        }
    }

    /**
     * Object Methods
     */

    /**
     * Delete the object
     */
    public function delete()
    {
        if ($this->id != null) {

            self::$_collection->remove(array("_id" => $this->id));

            return true;
        }

        return false;
    }

    /**
     * Save the object with all variables that have been set
     */
    public function save()
    {


        if ($this->id == null) {
            static::insert($this->_document, true);
            $this->id = $this->_document['_id'];
            unset($this->_document['_id']);

            return true;
        } else {

            return static::update(array("_id" => $this->id), $this->_document);
        }
    }

    /**
     * Do special updates to the object (incrementing, etc...)
     * @param array $modifier
     * @param array $options
     * @return type
     */
    public function specialUpdate($modifier, $options)
    {
        return static::update(array("_id" => $this->id), $modifier, $options);
    }

    /**
     * Static methods
     */

    /**
     * Connect to mongo...
     * @param string $calledClass   Name of the Calling Class
     * @return MongoDb
     */
    public static function connect($calledClass)
    {
        if (self::$_mongo !== null) {
            return self::$_mongo;
        }

        if (class_exists('Zend_Registry', false)
            && Zend_Registry::isRegistered('config')
        ) {
            $options = Zend_Registry::get('config')->mongodb;
        } elseif ($calledClass::$connectOptions != array()) {

            $options =
                static::connectArrayToClass($calledClass::$connectOptions);
        } else {
            $options = static::connectDefault();
        }
        $mongoDns =
            sprintf('mongodb://%s:%s@%s:%s/%s', $options->username, $options->password, $options->hostname, $options->port, $options->databasename);

        $mongoOptions = array("persist" => "x");


        $connection   = new Mongo($mongoDns, $mongoOptions);
        self::$_mongo = $connection->selectDB($options->databasename);
    }

    public static function connectArrayToClass($connectOptions)
    {
        $options = new stdClass();

        if (isset($connectOptions['username'])) {
            $options->username = $connectOptions['username'];
        }
        if (isset($connectOptions['password'])) {
            $options->password = $connectOptions['password'];
        }
        if (isset($connectOptions['hostname'])) {
            $options->hostname = $connectOptions['hostname'];
        }
        if (isset($connectOptions['port'])) {
            $options->port = $connectOptions['port'];
        }
        if (isset($connectOptions['databasename'])) {
            $options->databasename
                = $connectOptions['databasename'];
        }

        return $options;
    }

    /**
     * Returns Default Connection Params
     * @return \stdclass
     */
    public static function connectDefault()
    {
        $options               = new stdclass();
        $options->username     = 'test';
        $options->password     = 'test';
        $options->hostname     = 'localhost';
        $options->port         = '27017';
        $options->databasename = 'MongoTestDatabase';

        return $options;
    }

    public static function disconnect()
    {
        self::$_mongo = null;
    }

    /**
     * Setup db connection and init mongo collection
     * @param $called_class     ClassName
     */
    public static function init($calledClass)
    {

        if (self::$_mongo == null) {
            self::connect($calledClass);
        }
        if (static::$collectionName == null) {
            /*
             * Get collection name based on the class name. 
             * To do this, we take the class name and strip off the
             * beginning "Model_" and the rest is the collection name.
             */

            $rCN                    = array('model_'); // $replaceableClassNameparts
            static::$collectionName =
                str_replace($rCN, '', strtolower(get_called_class()));
        }

        $collectionName = static::$collectionName;

        self::$_collection = self::$_mongo->{$collectionName};
    }

    /**
     * Load object by ID
     * @param $_id
     */
    public static function load($_id)
    {
        $object = static::findOne(array("_id" => new MongoId($_id)));
        if ($object === null) {
            return false;
        } else {
            return $object;
        }
    }

    /**
     * Find all records in a collection
     * @return $this
     */
    public static function findAll()
    {
        return static::find();
    }

    /**
     * Get one record
     * sort param for compatibility to find
     */
// @codingStandardsIgnoreStart
    public static function findOne($conditionalArray = null
        , $fieldsArray = null
        , $sort = null)
    {
// @codingStandardsIgnoreEnd

        $className = get_called_class();

        $document =
            static::getCursor($conditionalArray, $fieldsArray, true);
        if ($document == null) {
            return null;
        }
        $object = new $className($document);

        return $object;
    }

    /**
     * Query the database for documents in the collection
     * @param array $conditionalArray
     * @param array $fieldsArray
     * @param array $sort
     * @param int   $limit
     * @return this
     */
    public static function find(
        $conditionalArray = null
        , $fieldsArray = null
        , $sort = null
        , $limit = null
        , $skip = null)
    {
        $className            = get_called_class();
        $cursor               =
            static::getCursor($conditionalArray, $fieldsArray, null, $className);
        static::$getLastCount = $cursor->count();
        if ($skip != null) {
            $cursor = $cursor->skip($skip);
        }
        if ($limit != null) {
            $cursor = $cursor->limit($limit);
        }
        if ($sort != null) {
            $cursor = $cursor->sort($sort);
        }

        $objectArray = array();
        foreach ($cursor as $document) {
            $objectArray[] = new $className($document);
        }

        return $objectArray;
    }

    public static $getLastCount = 0;

    /*
     * Count by query array
     * @param array $conditionalArray
     */

    public static function count($conditionalArray = null)
    {
        $cursor = static::getCursor($conditionalArray);

        return $cursor->count();
    }

    /**
     * Create cursor by query document
     * @param array $conditionalArray
     * @param array $fieldsArray
     */
    protected static function getCursor($conditionalArray = null
        , $fieldsArray = null
        , $one = false)
    {

        $calledClass = get_called_class();

        static::init($calledClass);
        if ($conditionalArray == null) {
            $conditionalArray = array();
        }
        if ($fieldsArray == null) {
            $fieldsArray = array();
        }
        if ($one) {
            return
                self::$_collection->findOne($conditionalArray, $fieldsArray);
        }

        $cursor = self::$_collection->find($conditionalArray, $fieldsArray);

        return $cursor;
    }

    /**
     * Enter description here ...
     * @param array $data
     * @param bool  $safe // Set true if you want to wait
     *                    for database response...
     * @param bool  $fsync
     */
    public static function insert($data, $safe = false, $fsync = false)
    {


        $calledClass = get_called_class();
        static::init($calledClass);

        $options = array();
        if ($safe) {
            $options['safe'] = true;
        }
        if ($fsync) {
            $options['fsync'] = true;
        }

        return self::$_collection->insert($data, $options);
    }

    /**
     * Do a batch insert into the collection
     * @param array $data
     */
    public static function batchInsert($data)
    {

        $calledClass = get_called_class();
        static::init($calledClass);

        return self::$_collection->batchInsert($data);
    }

    /**
     * Updates an entry
     * @param array $criteria
     * @param array $update
     * @param array $options
     * @return type
     */
    public static function update($criteria, $update, $options = array())
    {
        $calledClass = get_called_class();
        static::init($calledClass);

        return self::$_collection->update($criteria, $update, $options);
    }

    /**
     * Returns a string with accent to REGEX expression to find any combinations
     * in accent insentive way
     * @param string $text The text.
     * @return string The REGEX text.
     */
    public static function accentToRegex($text)
    {


        $from = str_split(utf8_decode(static::$_accentStrings));

        $to = str_split(strtolower(static::$_noAccentStrings));

        $text = utf8_decode($text);

        $regex = array();

        foreach ($to as $key => $value) {

            if (isset($regex[$value])) {

                $regex[$value] .= $from[$key];
            } else {

                $regex[$value] = $value;
            }
        }

        foreach ($regex as $rgKey => $rg) {

            $text = preg_replace("/[$rg]/", "_{$rgKey}_", $text);
        }

        foreach ($regex as $rgKey => $rg) {

            $text = preg_replace("/_{$rgKey}_/", "[$rg]", $text);
        }

        return utf8_encode($text);
    }

    /**
     * Finding all of the distinct values for a key.
     * @param string $key
     * @param array  $query
     * @return $self
     */
    public static function distinct($key = null, $query = null)
    {


        //static::$_collection = self::$_mongo->$collectionName;
        $command = array(
            'distinct' => static::$collectionName,
            'key'      => $key,
            'query'    => $query
        );

        return self::$_mongo->command($command);
    }

    /**
     * This changes the current database profiling level.
     * Profiled queries will appear in the system.profile collection of
     * this database.
     * @see http://www.php.net/manual/de/mongodb.setprofilinglevel.php
     * @param int $level
     * <ul>
     * <li>0 off</li>
     * <li>1 queries > 100ms</li>
     * <li>2 all queries</li>
     * </ul>
     * @param int $slowms
     * @return type
     */
    public function setProfilingLevel($level, $slowms = null)
    {

        $command = array(
            'profile' => $level,
            'slowms'  => $slowms
        );

        return self::$_mongo->command($command);
    }

    public function getProfilingLevel()
    {
        return self::$_mongo->command(array('profile' => -1));
    }

    /**
     * Setup Collection Index
     * @return bool
     */
    public static function setUpIndexes()
    {
        return self::$_collection->ensureIndex(static::$indexes);
    }

    /**
     * Returns an Array of the Set Index
     * @return array
     */
    public static function getIndexInfo()
    {
        return self::$_collection->getIndexInfo();
    }

    /**
     * Returns the  Document
     * return array
     */
    public function toJsonArr()
    {

        return $this->getDocument();
    }

    /**
     * return an Json String of the Document
     * @return Json
     */
    public function toJson()
    {

        return json_encode($this->toJsonArr());
    }

}

