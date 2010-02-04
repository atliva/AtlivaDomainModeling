<?php
/*
 * AtlivaDomainModeling_Repository_ZendRepositoryAbstract
 * Implements repository pattern using the Zend DB to interact with persistance layer
 */
abstract class AtlivaDomainModeling_Repository_ZendRepositoryAbstract extends AtlivaDomainModeling_Repository_RepositoryAbstract {
    /*
     * AtlivaDomainModeling_Repository_ZendRepositoryAbstract::findAll()
     * Basic query to retrieve all entities without any constraints
     * @param array $param
     *               //limit lets you choose a subset of matching results
     *               $param['limit'] = array($sql_limit_offset, $num_entries to display)
     *
     */
    public function findAll($params = array()){
        $selectStatement = $this->_getDbEntityPrimitive();
        $params['get_entities_select_statement'] = $selectStatement;
        $params['get_num_total_select_statement'] = $selectStatement;
        /*
         * by nature this function expects to fine one OR MORE entities, it will
         * return a collection which neatly handles the possible array of entities
         * while providing additional functionalities arrays cannot
         */
        return $this->_createEntitiesCollection($params);
    }
    /*
     * AtlivaDomainModeling_Repository_ZendRepositoryAbstract::findById()
     *
     * searches persistence layer for enity with given id, then returns entity if found or null
     * @param int $entityId
     * @return null | AtlivaDomainModeling_DataObject_EntityAbstract object
     */
    public function findById($entityId) {
        //Check to see if entity is already in dictionary, this way there would be no more need to query database
        if($entity = $this->_lookupEntityInDictionary($entityId)){
            return $entity;
        }
        $dbResultData = $this->_getDbEntityPrimitive()->where('id = ?', $entityId)->query()->fetch();
        if($dbResultData){
            return $this->_createEntity($dbResultData);
        } else {
            return null;
        }
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
                        ->limit($params['limit'][1],$params['limit'][0]);
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
}