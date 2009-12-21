<?php
abstract class AtlivaDomainModeling_DataObject_EntityAbstract extends AtlivaDomainModeling_DataObject_DataObjectAbstract{
    public function getId(){
        return $this->_getData('id');
    }
    public function __construct(){
        parent::__construct();
        $this->_dataProperties['id'] = null;
        $this->_toArrayPropertyMethodMap['id'] = 'getId';
    }
}