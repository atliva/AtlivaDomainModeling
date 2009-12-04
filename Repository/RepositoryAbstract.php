<?php
abstract class AtlivaDomainModeling_Repository_RepositoryAbstract {
    protected $_entitiesDictionary = array();
    protected $_entitiesInfo = array();
    //Public
    public function saveEntity(AtlivaDomainModeling_DataObject_EntityAbstract $entity){}

    public function saveEntityCollection(AtlivaDomainModeling_DataObject_Collections $collection){
        foreach($collection as $entity){
            $this->saveEntity();
        }
    }
    
    //Protected
    public function _createEntity($entityData){
        //use the $entityData to populate the entity
    }
    public function _createEntitiesArray($entityDataArray){
        $entitiesArray = array();
        foreach($entityDataArray as $entityData){
            $entitiesArray[] = $this->_createEntity($entityData);
        }
        return $entitiesArray;
    }
    public function _getEntitiesForLazyCollection(){}
    
    public function _getNumTotalForLazyCollection(){}

    protected function _lookupEntityInDictionary($entityId){
        if(array_key_exists($entityId, $this->_entitiesDictionary)){
            return $this->_entitiesDictionary[$entityId];
        }
        return false;
    }

    protected function _addEntityToDictionary($entityId, $entity){

        if(!array_key_exists($entityId, $this->_entitiesDictionary)){
            $this->_entitiesDictionary[$entityId] = $entity;
        }
    }

    protected function _addEntityInfo($entityId, array $entityInfo){
        if(!array_key_exists($entityId, $this->_entitiesInfo)){
            $this->_entitiesInfo[$entityId] = $entityInfo;
        } else {
            $this->_entitiesInfo[$entityId] = array_merge($this->_entitiesInfo[$entityId], $entityInfo);
        }
    }

    protected function _getEntityInfo($entityId){
        if(array_key_exists($entityId, $this->_entitiesInfo)){
            return $this->_entitiesInfo[$entityId];
        }
        return false;
    }
}