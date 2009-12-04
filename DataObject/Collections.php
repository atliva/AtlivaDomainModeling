<?php
class AtlivaDomainModeling_DataObject_Collections implements Iterator{
    protected $_repositoryGetterMethods = array();
    protected $_repositoryGetterMethodsReturnValues = array();
    protected $_entitiesList = false;
    protected $_entitiesListGetter;
    //public
    public function __construct($entitiesListGetter,$repositoryGetterMethods = array()){
        $this->_entitiesListGetter['method'] = $entitiesListGetter;
        $this->_repositoryGetterMethods = $repositoryGetterMethods;
    }
    public function getNumTotal(){
        return $this->_callRepositoryGetterMethod('getNumTotal');
    }

    public function __call($methodName,$arguments){
        return $this->_callRepositoryGetterMethod($methodName);
    }

    protected function _confirmEntitiesListIsLoaded(){
        if(!$this->_entitiesList){
            $this->_entitiesList = $this->_entitiesListGetter['method']();
        }
    }

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