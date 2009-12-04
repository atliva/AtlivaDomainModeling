<?php
abstract class AtlivaDomainModeling_DataObject_ValueObjectAbstract extends AtlivaDomainModeling_DataObject_DataObjectAbstract{
    public function __construct($propertyValues = null){
        if($propertyValues){
            $this->setPropertiesFromArray($propertyValues);
        }
    }
}