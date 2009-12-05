<?php
abstract class AtlivaDomainModeling_DataObject_DataObjectAbstract {
    protected $_dataProperties = array();
    protected $_dataPropertiesToLazyLoad = array();
    protected $_camelCaseToUnderScoreInflector;
    protected $_underScoreToCamelCaseInflector;
    //public
    public function setPropertiesFromArray($propertyValuesArray){
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
        //e.g. setPropertyNameLazyLoad()
        $propertyNameWithoutSuffix = $this->_checkIfEntityPropertyWithoutSuffixExists($propertyName, 'LazyLoad', -8);
        if($propertyNameWithoutSuffix){
            $propertyNameWithoutSuffix = $this->_convertCamelCaseToUnderScore($propertyNameWithoutSuffix);
            $this->_dataPropertiesToLazyLoad[$propertyNameWithoutSuffix] = $value;
            return $this;
        }
    }
    protected function _getter($propertyName, $arguments){
        //e.g. getPropertyName()
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
                    return $propertyToConvertToArray->getPropertiesAsArray();
                }
            } else if($propertyToConvertToArray instanceof AtlivaDomainModeling_DataObject_Collections){
                if($params['array_depth'] > 0){
                    return $propertyToConvertToArray->getDataListAsArray();
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