<?php
/*
 * Stores groups of entities.
 * Instead of storing groups of entities in simple arrays, repositories store them
 * in collections as they are more flexible and can lazy load the entities as well as
 * invoke other methods provided to it via closure functions provided through its constructor
 */
class AtlivaDomainModeling_DataObject_Collections implements Iterator {

    /*
     * array of closure functions to lazy load values
     */
    protected $_repositoryGetterMethods = array();
    /*
     * array of values called from $_repositoryGetterMethods
     */
    protected $_repositoryGetterMethodsReturnValues = array();
    /*
     * holds collection of entity objects
     */
    protected $_entitiesList = false;
    protected $_entitiesListGetter;
    //public
    /*
     * __construct
     * @param func $entitiesListGetter closure function called to lazy load collection of entities
     * @param array $repositoryGetterMethods array of closure functions that can be dynamically
     *              loaded based on the array key name.
     *              e.g. $repositoryGetterMethods['doTest'] will be invoked when the doTest() is called
     */
    public function __construct($entitiesListGetter,$repositoryGetterMethods = array()){
        $this->_entitiesListGetter['method'] = $entitiesListGetter;
        $this->_repositoryGetterMethods = $repositoryGetterMethods;
    }
    /*
     * getNumTotal
     * @return int total number of entity results possible for given current type of collection
     * e.g. UsersRepo->findAll() finds a total of 1000 user entities, the getNumTotal from the
     * collection it returns will be 1000. This number may be different from the
     *
     */
    public function getNumTotal(){
        return $this->_callRepositoryGetterMethod('getNumTotal');
    }
    /*
     * toArray()
     * Converts the current collection into an array
     * @return array 
     */
    public function toArray($params = array()){
        $params = array_merge(array('array_depth' => 0), $params);
        $currentArrayDepth = $params['array_depth'];
        $toArrayValues = array();
        $entities = array();
        foreach($this as $entity){
            $entities[] = $entity->toArray(array('array_depth' => $currentArrayDepth));
        }
        $toArrayValues['entities'] = $entities;
        $toArrayValues['num_total'] = $this->getNumTotal();
        return $toArrayValues;
    }
    /*
     * Dynamically calls methods based on array keys in $_callRepositoryGetterMethod
     * e.g. $_callRepositoryGetterMethod['doTest']() will be invoked when $this->doTest() is called
     */
    public function __call($methodName,$arguments){
        return $this->_callRepositoryGetterMethod($methodName);
    }
    /*
     * _confirmEntitiesListIsLoaded
     * checks to see if entities list is loaded, otherwise invoke its lazy loader to retrieve the entities list
     */
    protected function _confirmEntitiesListIsLoaded(){
        if(!$this->_entitiesList){
            $this->_entitiesList = $this->_entitiesListGetter['method']();
        }
    }
    /*
     * _callRepositoryGetterMethod
     * Call the closure functions in $_callRepositoryGetterMethod array and
     * store its values in to $_repositoryGetterMethodsReturnValues for future use.
     * This way the closure function will only need to be called once
     */
    protected function _callRepositoryGetterMethod($methodName){
        if(array_key_exists($methodName, $this->_repositoryGetterMethodsReturnValues)){
            return $this->_repositoryGetterMethodsReturnValues[$methodName];
        } else if(array_key_exists($methodName, $this->_repositoryGetterMethods)){
            $methodReturnValue = $this->_repositoryGetterMethods[$methodName]();
            $this->_repositoryGetterMethodsReturnValues[$methodName] = $methodReturnValue;
            unset($this->_repositoryGetterMethods[$methodName]);
            return $methodReturnValue;
        }
        return false;
    }

    //Iteration Methods
    public function rewind() {
        $this->_confirmEntitiesListIsLoaded();
        reset($this->_entitiesList);
    }

    public function current() {
        $this->_confirmEntitiesListIsLoaded();
        return current($this->_entitiesList);
    }

    public function key() {
        $this->_confirmEntitiesListIsLoaded();
        return key($this->_entitiesList);
    }

    public function next() {
        $this->_confirmEntitiesListIsLoaded();
        return next($this->_entitiesList);
    }

    public function valid() {
        return $this->current() !== false;
    }
}