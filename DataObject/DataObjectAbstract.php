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
    protected $_dataPropertyLazyLoaders = array();

    protected $_toArrayPropertyMethodMap = array();
    //public

    /*
     * __construct
     * @param array $propertyValues set business values for data object
     */
    public function __construct(){

    }

    /*
     * toArray
     *
     * @param array $params
     *              int $params['array_depth'] - determines the number of levels to show the array. If array has subproperties, they will also be converted to arrays.
     *
     */
    public function toArray($params = array(), $gettersParams = array()){
        $toArrayMethodList = $this->_toArrayPropertyMethodMap;
        $toArrayValues = array();
        $params = array_merge(array('array_depth' => 0), $params);
        $currentArrayDepth = $params['array_depth'];
        $nextArrayDepth = $currentArrayDepth - 1;
        $canGoDeeper = ($currentArrayDepth > 0);
        foreach($toArrayMethodList as $arrayPropertyName => $getterMethod){
            if(is_string($getterMethod)){
                $getterMethodName = $getterMethod;
                $isClosure = false;
            } else {
               $getterFunction = $getterMethod;
                $isClosure = true;
            }
            if(isset($gettersParams[$arrayPropertyName])){
                $getterParams = $gettersParams[$arrayPropertyName];
                if(count($getterParams) == 1){
                    if($isClosure) {
                        $getterFunction($getterParams[0]);
                    } else {
                        $arrayPropertyValue = $this->$getterMethodName($getterParams[0]);
                    }
                } else {
                    if($isClosure) {
                        $arrayPropertyValue = call_user_func_array ($getterFunction,$getterParams);
                    } else {
                        $arrayPropertyValue = call_user_func_array (array($this,$getterMethodName),$getterParams);
                    }

                }
            } else {
                if($isClosure) {
                    $arrayPropertyValue = $getterFunction();
                } else {
                    $arrayPropertyValue = $this->$getterMethodName();
                }
            }
            if($canGoDeeper){
                if(
                    is_a($arrayPropertyValue, 'AtlivaDomainModeling_DataObject_DataObjectAbstract') ||
                    is_a($arrayPropertyValue, 'AtlivaDomainModeling_DataObject_Collections')
                ) {
                    $arrayPropertyValue = $arrayPropertyValue->toArray(array('array_depth' => $nextArrayDepth));
                }
            }
            $toArrayValues[$arrayPropertyName] = $arrayPropertyValue;
        }
        return $toArrayValues;
    }
    //Protected
    protected function _getData($propertyName){
        if(isset($this->_dataPropertyLazyLoaders[$propertyName])){
            $this->_dataProperties[$propertyName] = $this->_dataPropertyLazyLoaders[$propertyName]();
            unset($this->_dataPropertyLazyLoaders[$propertyName]);
        }
        return $this->_dataProperties[$propertyName];
    }
    protected function _setData($propertyName, $value){
        if(isset($this->_dataPropertyLazyLoaders[$propertyName])){
            unset($this->_dataPropertyLazyLoaders[$propertyName]);
        }
        $this->_dataProperties[$propertyName] = $value;
        return $this;
    }

    /*
     * _exportData
     * returns the available data properties to the repository
     * This function is underscored meaning it should not be used
     * for anything other than in the repository to persist data
     */
    public function _exportData(){
        return $this->_dataProperties;
    }

    /*
     * _importData
     * sets the properties from data provided by repository
     * This function is underscored meaning it should not be used
     * for anything other than in the repository populate persisted data
     */
    public function _importData($dataProperties = null, $dataPropertyLazyLoaders = null){
        if($dataProperties){
            $this->_dataProperties = array_merge($this->_dataProperties, $dataProperties);
        }
        if($dataPropertyLazyLoaders){
            $this->_dataPropertyLazyLoaders = array_intersect_key($dataPropertyLazyLoaders, $this->_dataProperties);
        }
    }
}