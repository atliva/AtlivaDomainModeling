<?php
/*
 * Implementation of Repository Pattern
 * Each repository is reponsible for persisting a specific type of entity
 * Retrieving existing entities or saving new ones should all be done via
 * the repository
 */
abstract class AtlivaDomainModeling_Repository_RepositoryAbstract {
    /*
     * $_entitiesDictionary
     * array of all entities that have been created or added by the repository
     */
    protected $_entitiesDictionary = array();
    /*
     * $_entitiesInfo
     * array of info for each entity that is being tracked by the repository
     * This way data that is not appropraite to be placed in the entity object
     * can still be retained for the use of the repository
     */
    protected $_entitiesInfo = array();
    //Public
    
    /*
     * Basic query to retrieve all entities without any constraints
     *
     */
    public function findAll($params = array()){
        //return new AtlivaDomainModeling_DataObject_Collections();
    }
    /*
     * findById()
     * Basic query to retrieve a single entity by id
     *
     */
    public function findById($entityId){
        //return $entity
    }
    /*
     * saveEntity
     * Takes the entity type the repository handling and puts it into the persistance layer
     * @param obj $entity The entity is a subclass of AtlivaDomainModeling_DataObject_EntityAbstract
     */
    public function saveEntity(AtlivaDomainModeling_DataObject_EntityAbstract $entity){}
    /*
     * saveEntityCollection
     * Convenience function to persist an entire collection.
     */
    public function saveEntityCollection(AtlivaDomainModeling_DataObject_Collections $collection){
        foreach($collection as $entity){
            $this->saveEntity($entity);
        }
    }
    
    //Protected
    /*
     * _createEntity
     *
     * Creates  entity using data from persistance layer
     * @param array @entityData results data from persistance
     */
    public function _createEntity($entityData){
        //use the $entityData to populate the entity
    }
    /*
     * _createEntitiesCollection()
     * Creates a collection of entities based on data from persistance layer
     */
    protected function _createEntitiesCollection($params){

    }
    /*
     * _lookupEntityInDictionary
     * Ensures that there is only a single instance of a given entity.
     * When an entity is being created, this method is invoked to check if it
     * already exists. If so, then it is returned and used, and a new instance of
     * the entity is not created.
     * @param int $entityId
     * @return mixed - Returns the entity if its Id is found
     */
    protected function _lookupEntityInDictionary($entityId){
        if(array_key_exists($entityId, $this->_entitiesDictionary)){
            return $this->_entitiesDictionary[$entityId];
        }
        return false;
    }
    /*
     * _addEntityToDictionary
     * Adds a newly created entity to internal dictionary for future use, so no more than one
     * instance of a given entity is ever created
     * @param int $entityId
     * @param obj $entity
     */
    protected function _addEntityToDictionary($entityId, $entity){

        if(!array_key_exists($entityId, $this->_entitiesDictionary)){
            $this->_entitiesDictionary[$entityId] = $entity;
        }
    }
    /*
     * Adds information about a given entity. Typically it holds non bususiness model
     * information about an entity that the repository needs to keep track of but
     * instead of polluting the entity object, it is placed inside the repository
     * @param int $entityId
     * @param obj $entity
     */
    protected function _addEntityInfo($entityId, array $entityInfo){
        if(!array_key_exists($entityId, $this->_entitiesInfo)){
            $this->_entitiesInfo[$entityId] = $entityInfo;
        } else {
            $this->_entitiesInfo[$entityId] = array_merge($this->_entitiesInfo[$entityId], $entityInfo);
        }
    }
    /*
     * _getEntityInfo
     * Gets the non business related info about an entity. Generally this holds
     * information about an entity that the repository needs but since it is not
     * related to the domain model, it does not go into the entity
     * @return mixed - The info returned is generally an associative array with the important values as the array keys
     */
    protected function _getEntityInfo($entityId){
        if(array_key_exists($entityId, $this->_entitiesInfo)){
            return $this->_entitiesInfo[$entityId];
        }
        return false;
    }
    /*
     * _getDbEntityPrimitive
     * holds base persistance query that gathers the requisite fields to populate entity
     * the constraints for the query will change depending on specific usage in the
     * finder methods
     */
    protected function _getDbEntityPrimitive() {

    }
}