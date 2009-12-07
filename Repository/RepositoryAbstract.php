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
    /*
     * $_dbEntityPrimitive
     * holds base persistance query that gathers the requisite fields to populate entity
     * the constraints for the query will change depending on specific usage in the
     * finder methods
     */
    protected $_dbEntityPrimitive;
    //Public
    /*
     * Basic query to retrieve all entities without any constraints
     *
     */
    public function findAll($params = array()){
        $selectStatement = $this->_dbEntityPrimitive;
        $getEntitiesSelectStatement = $selectStatement;
        $getNumTotalSelectStatement = $selectStatement;
        /*
         * by nature this function expects to fine one OR MORE entities, it will
         * return a collection which neatly handles the possible array of entities
         * while providing additional functionalities arrays cannot
         */
        return $this->_createEntitiesCollection(array(
            'get_entities_select_statement'    =>  $getEntitiesSelectStatement,
            'get_num_total_select_statement'       =>  $getNumTotalSelectStatement
            ));
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
    protected function _createEntitiesCollection($params){
        $self =& $this;
        $defaultParams = array('limit' => null);
        $params = array_merge($defaultParams, $params);
        $getEntitiesSelectStatement = $params['get_entities_select_statement'];
        $getNumTotalSelectStatement = $params['get_num_total_select_statement'];
        /*
         * Passes information into the collection as closure functions so the data
         * can be lazy loaded
         */
        return new AtlivaDomainModeling_DataObject_Collections(
            /*
             * Closure function to lazy load all the requisite entities
             * @param mixed $self reference to current repository
             * @param mixed|string $getEntitiesSelectStatement query string or object that retrieves the necessary list of entities
             * @param array $params
             */
            function() use (&$self,$getEntitiesSelectStatement,$params) {
                /*
                 * if $params['limit'] is set, the collection will return a subset of the total entities list
                 * for example, if there are 1 million users, when calling the usersRepository->findAll(),
                 * we don't want to load all 1 million user entities, we probably just want a subset thereof. If we want to retrieve
                 * 100 user entitiess, starting from the 1000th entity, we would set limit as $limit[1000,100];
                 */
                if($params['limit']){
                    $getEntitiesSelectStatement = $getEntitiesSelectStatement
                        ->limit($params['limit'][0],$params['limit'][1]);
                }
                $entitiesDataArray = $getEntitiesSelectStatement->query()->fetchAll();
                $entitiesArray = array();
                foreach($entitiesDataArray as $entityData){
                    $entitiesArray[] = $self->_createEntity($entityData);
                }
                return $entitiesArray;
                },
            /*
             * Lets us know how many possible entities there are total. Using the previous example
             * if we just get a subset 100 entities from a possible million, this function lets us know
             * there are a million users total
             */
            array(
            'getNumTotal'   =>  function() use (&$self,$getNumTotalSelectStatement){
                $result = $getNumTotalSelectStatement->reset(Zend_Db_Select::COLUMNS)
                    ->columns(array('numRows' => 'COUNT(*)'))->query()->fetch();
                return $result['numRows'];
                }
        ));
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
}