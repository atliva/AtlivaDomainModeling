<?php
abstract class AtlivaDomainModeling_DataObject_DataObjectAbstract {
    /*
     * $_dataProperties
     * Holds the business values for the data object
     */
    protected $_dataProperties = array();
    /*
     * $_dataPropertiesToLazyLoad
     * Holds functions that will be called to lazy load values for $_dataProperties
     */
    protected $_dataPropertiesToLazyLoad = array();

    protected $_camelCaseToUnderScoreInflector;

    protected $_underScoreToCamelCaseInflector;
    //public

    /*
     * __construct
     * @param array $propertyValues set business values for data object
     */
    public function __construct($propertyValues = null){
        if($propertyValues){
            $this->setPropertiesFromArray($propertyValues);
        }
    }
    /*
     * setPropertiesFromArray
     *
     * @param array $propertyValuesArray
     */
    public function setPropertiesFromArray(array $propertyValuesArray){
        $entityProperties = $this->_dataProperties;
        foreach($entityProperties as $propertyName => $currentPropertyValue){
            if(!array_key_exists($propertyName, $propertyValuesArray)){
                continue;
            }
            $newPropertyValue = $propertyValuesArray[$propertyName];
            $setterMethodName = 'set' . $this->_convertUnderScoreToCamelCase($propertyName) . 'FromArrayElement';
            $this->$setterMethodName($newPropertyValue);
        }
    }
    /*
     * getPropertiesAsArray
     *
     * @param array $params
     *              int $params['array_depth'] - determines the number of levels to show the array. If array has subproperties, they will also be converted to arrays.
     *
     */
    public function getPropertiesAsArray($params = array()){
        $entityProperties = $this->_dataProperties;
        $entityValue = array();
        $params = array_merge(array('array_depth' => 0), $params);
        foreach($entityProperties as $propertyName => $currentPropertyValue){
            $getterMethodName = 'get' . $this->_convertUnderScoreToCamelCase($propertyName) . 'AsArrayElement';
            $entityValue[$propertyName] = $this->$getterMethodName($params);
        }
        return $entityValue;
    }
    /*
     * __call
     * calls automatic getters and setters for the values in $_dataProperties
     */
    public function __call($methodName, $arguments){
        $prefix = substr($methodName,0,3);
        $propertyName = substr($methodName,3);
        
        switch($prefix){
            case 'get':
                return $this->_getter($propertyName, $arguments);
                break;
            case 'set':
                return $this->_setter($propertyName,$arguments);
                break;
        }
    }

    //Protected
    /*
     * _setter
     * handles setter methods based on data properties
     * e.g. setPropertyName() and setPropertyNameFromArrayElement() will load property into $_dataProperties['property_name']
     * e.g. setPropertyNameLazyLoad() will load closure function into $_dataPropertiesToLazyLoad['property_name']
     */
    protected function _setter($propertyName, $valueArray){
        $value = $valueArray[0];
        //e.g. setPropertyName()
        $underscored_property_name = $this->_convertCamelCaseToUnderScore($propertyName);
        if (array_key_exists($underscored_property_name, $this->_dataProperties)) {
            $this->_getAndDeleteLazyPropertyLoader($underscored_property_name);
            $this->_dataProperties[$underscored_property_name] = $value;
            return $this;
        }
        //e.g. setPropertyNameFromArrayElement()
        $propertyNameWithoutSuffix = $this->_checkIfEntityPropertyWithoutSuffixExists($propertyName, 'FromArrayElement', -16);
        if($propertyNameWithoutSuffix){
            $propertyNameWithoutSuffix = $this->_convertCamelCaseToUnderScore($propertyNameWithoutSuffix);
            $this->_getAndDeleteLazyPropertyLoader($propertyNameWithoutSuffix);
            $this->_dataProperties[$propertyNameWithoutSuffix] = $value;
            return $this;
        }
        /*
         * setPropertyNameLazyLoad()
         * Sets lazy loading closure function to set the values when getPropertyName() is called
         */
        $propertyNameWithoutSuffix = $this->_checkIfEntityPropertyWithoutSuffixExists($propertyName, 'LazyLoad', -8);
        if($propertyNameWithoutSuffix){
            $propertyNameWithoutSuffix = $this->_convertCamelCaseToUnderScore($propertyNameWithoutSuffix);
            $this->_dataPropertiesToLazyLoad[$propertyNameWithoutSuffix] = $value;
            return $this;
        }
    }
    /*
     * _getter
     * handles getter methods based on data properties
     * e.g. getPropertyName() and getPropertyNameAsArrayElement() returns value from $_dataProperties['property_name']
     */
    protected function _getter($propertyName, $arguments){
        /* getPropertyName()
         * returns value in $_dataProperties['property_name']
         * First checks if closure function in $_dataPropertiesToLazyLoad['property_name'].
         * If it is set, it will be invoked and its value will be set to $_dataProperties['property_name']
         * and then the value will be returned
         */
        $underscored_property_name = $this->_convertCamelCaseToUnderScore($propertyName);
        if (array_key_exists($underscored_property_name, $this->_dataProperties)) {
            $this->_checkToLoadLazyProperty($underscored_property_name);
            return $this->_dataProperties[$underscored_property_name];
        }
        //e.g. getPropertyNameAsArrayElement()
        $propertyNameWithoutSuffix = $this->_checkIfEntityPropertyWithoutSuffixExists($propertyName, 'AsArrayElement', -14);
        if($propertyNameWithoutSuffix){
            $propertyNameWithoutSuffix = $this->_convertCamelCaseToUnderScore($propertyNameWithoutSuffix);
            $params = $arguments[0];
            $this->_checkToLoadLazyProperty($propertyNameWithoutSuffix);
            $propertyToConvertToArray = $this->_dataProperties[$propertyNameWithoutSuffix];
            if($propertyToConvertToArray instanceof AtlivaDomainModeling_DataObject_DataObjectAbstract) {
                if($params['array_depth'] > 0){
                    $params['array_depth']--;
                    return $propertyToConvertToArray->getPropertiesAsArray($params);
                }
            } else if($propertyToConvertToArray instanceof AtlivaDomainModeling_DataObject_Collections){
                if($params['array_depth'] > 0){
                    $params['array_depth']--;
                    return $propertyToConvertToArray->getDataListAsArray($params);
                }
            } else {
                return $propertyToConvertToArray;
            }
        }
    }
    protected function _checkIfEntityPropertyWithoutSuffixExists($propertyNameWithPossibleSuffix, $suffixName, $lengthOfSuffixFromEnd){
        $endsInSuffix = ( substr($propertyNameWithPossibleSuffix, $lengthOfSuffixFromEnd) == $suffixName );
        if($endsInSuffix){
            $propertyNameWithoutSuffix = substr($propertyNameWithPossibleSuffix, 0, $lengthOfSuffixFromEnd);
            $propertyNameWithoutSuffix = $this->_convertCamelCaseToUnderScore($propertyNameWithoutSuffix);
            if(array_key_exists($propertyNameWithoutSuffix, $this->_dataProperties)){
                return $propertyNameWithoutSuffix;
            }
        }
        return false;
    }
    protected function _checkToLoadLazyProperty($propertyName){
        if ($propertyLazyLoader = $this->_getAndDeleteLazyPropertyLoader($propertyName)) {
            $this->_dataProperties[$propertyName] = $propertyLazyLoader();
        }
    }
    protected function _getAndDeleteLazyPropertyLoader($propertyName){
        $propertyLazyLoader = false;
        if (array_key_exists($propertyName, $this->_dataPropertiesToLazyLoad)) {
            $propertyLazyLoader = $this->_dataPropertiesToLazyLoad[$propertyName];
            unset($this->_dataPropertiesToLazyLoad[$propertyName]);
            return $propertyLazyLoader;
        }
        return $propertyLazyLoader;
    }
    /*
     * $_camelCaseToUnderScoreInflector()
     * method to convert camelCase text to under_scored_text
     * @param string $CamelCasedString
     */
    protected function _convertCamelCaseToUnderScore($CamelCasedString){
        if(!$this->_camelCaseToUnderScoreInflector){
            $inflector = new Zend_Filter_Inflector(':CamelCasedPropertyName');
            $inflector->setRules(array(
                ':CamelCasedPropertyName'  => array('Word_CamelCaseToUnderscore','StringToLower')
            ));
            $this->_camelCaseToUnderScoreInflector = $inflector;
        }
        return $this->_camelCaseToUnderScoreInflector->filter(array('CamelCasedPropertyName' => $CamelCasedString));
    }
    /*
     * $_underScoreToCamelCaseInflector()
     * method to convert under_scored_text text to camelCase
     * @param string $under_scored_string
     */
    protected function _convertUnderScoreToCamelCase($under_scored_string){
        if(!$this->_underScoreToCamelCaseInflector){
            $inflector = new Zend_Filter_Inflector(':underscored_property_name');
            $inflector->setRules(array(
                ':underscored_property_name'  => array('Word_UnderscoreToCamelCase')
            ));
            $this->_underScoreToCamelCaseInflector = $inflector;
        }
        return $this->_underScoreToCamelCaseInflector->filter(array('underscored_property_name' => $under_scored_string));
    }
}