<?php
abstract class AtlivaDomainModeling_DataObject_DataObjectAbstract {
    protected $_dataProperties = array();
    protected $_dataPropertiesToLazyLoad = array();
    //public
    public function setPropertiesFromArray($propertyValuesArray){
        $entityProperties = $this->_dataProperties;
        foreach($entityProperties as $propertyName => $currentPropertyValue){
            if(!array_key_exists($propertyName, $propertyValuesArray)){
                continue;
            }
            $newPropertyValue = $propertyValuesArray[$propertyName];
            $setterMethodName = 'set' . $propertyName . 'FromArrayElement';
            $this->$setterMethodName($newPropertyValue);
        }
    }
    public function getPropertiesAsArray($params = array()){
        $entityProperties = $this->_dataProperties;
        $entityValue = array();
        $params = array_merge(array('arrayDepth' => 0), $params);
        foreach($entityProperties as $propertyName => $currentPropertyValue){
            $getterMethodName = 'get' . $propertyName . 'AsArrayElement';
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
        //e.g. setPropertyname()
        if (array_key_exists($propertyName, $this->_dataProperties)) {
            $this->_getAndDeleteLazyPropertyLoader($propertyName);
            $this->_dataProperties[$propertyName] = $value;
            return true;
        }
        //e.g. setPropertynameFromArrayElement()
        $propertyNameWithoutSuffix = $this->_checkIfEntityPropertyWithoutSuffixExists($propertyName, 'FromArrayElement', -16);
        if($propertyNameWithoutSuffix){
            $this->_getAndDeleteLazyPropertyLoader($propertyNameWithoutSuffix);
            $this->_dataProperties[$propertyNameWithoutSuffix] = $value;
            return true;
        }
        //e.g. setPropertynameLazyLoad()
        $propertyNameWithoutSuffix = $this->_checkIfEntityPropertyWithoutSuffixExists($propertyName, 'LazyLoad', -8);
        if($propertyNameWithoutSuffix){
            $this->_dataPropertiesToLazyLoad[$propertyNameWithoutSuffix] = $value;
            return true;
        }
    }
    protected function _getter($propertyName, $arguments){
        //e.g. getPropertyname()
        if (array_key_exists($propertyName, $this->_dataProperties)) {
            $this->_checkToLoadLazyProperty($propertyName);
            return $this->_dataProperties[$propertyName];
        }
        //e.g. getPropertynameAsArrayElement()
        $propertyNameWithoutSuffix = $this->_checkIfEntityPropertyWithoutSuffixExists($propertyName, 'AsArrayElement', -14);
        if($propertyNameWithoutSuffix){
            $params = $arguments[0];
            $this->_checkToLoadLazyProperty($propertyNameWithoutSuffix);
            $propertyToConvertToArray = $this->_dataProperties[$propertyNameWithoutSuffix];
            if($propertyToConvertToArray instanceof AtlivaDomainModeling_DataObject_DataObjectAbstract) {
                if($params['arrayDepth'] > 0){
                    return $propertyToConvertToArray->getPropertiesAsArray();
                }
            } else if($propertyToConvertToArray instanceof AtlivaDomainModeling_DataObject_Collections){
                if($params['arrayDepth'] > 0){
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
        }
        return $propertyLazyLoader;
    }
}