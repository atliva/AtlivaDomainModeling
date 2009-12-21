<?php
abstract class AtlivaDomainModeling_DataObject_NestedSetEntityAbstract extends AtlivaDomainModeling_DataObject_EntityAbstract{
    /*
     * $_nodePositionChangeType
     * Position of node relative to a source node
     */
    protected $_nodePositionChangeType;
    /*
     * $_nodePositionChangeContext
     * Node or node id to serve as source for positioning current node
     */
    protected $_nodePositionChangeContext;
    /*
     * setAsFirstChildOf
     * set current node as first child of source node
     * @param int|AtlivaDomainModeling_DataObject_NestedSetEntityAbstract $sourceNodeOrSourceNodeId
     */
    public function setAsFirstChildOf($sourceNodeOrSourceNodeId){
        $this->_nodePositionChangeType = 'firstChild';
        $this->_nodePositionChangeContext = $sourceNodeOrSourceNodeId;
    }
    /*
     * setAsLastChildOf
     * set current node as last child of source node
     * @param int|AtlivaDomainModeling_DataObject_NestedSetEntityAbstract $sourceNodeOrSourceNodeId
     */
    public function setAsLastChildOf($contextNodeOrSourceNodeId){
        $this->_nodePositionChangeType = 'lastChild';
        $this->_nodePositionChangeContext = $contextNodeOrSourceNodeId;
    }
    /*
     * setAsNextSibblingOf
     * set current node as the next sibbling of source node
     * @param int|AtlivaDomainModeling_DataObject_NestedSetEntityAbstract $sourceNodeOrSourceNodeId
     */
    public function setAsNextSibblingOf($contextNodeOrSourceNodeId){
        $this->_nodePositionChangeType = 'nextSibbling';
        $this->_nodePositionChangeContext = $contextNodeOrSourceNodeId;
    }
    /*
     * setAsPreviousSibblingOf
     * set current node as the previous sibbling of source node
     * @param int|AtlivaDomainModeling_DataObject_NestedSetEntityAbstract $sourceNodeOrSourceNodeId
     */
    public function setAsPreviousSibblingOf($contextNodeOrSourceNodeId){
        $this->_nodePositionChangeType = 'previousSibbling';
        $this->_nodePositionChangeContext = $contextNodeOrSourceNodeId;
    }
    /*
     * _exportPositionChangeInfo
     * retrieves information regarding whether or not position has change and needs to be
     * updated in the persistance layer
     */
    public function _exportPositionChangeInfo(){
        if($this->_nodePositionChangeType === null || $this->_nodePositionChangeContext === null){
            return null;
        }
        return array(
            'type'    =>  $this->_nodePositionChangeType,
            'context'  =>  $this->_nodePositionChangeContext
            );
    }
    public function getLft(){
        return $this->_getData('lft');
    }
    public function getRgt(){
        return $this->_getData('rgt');
    }
    public function getImmediateParent(){
        return $this->_getData('immediate_parent');
    }
    public function getAllParents(){
        return $this->_getData('all_parents');
    }
    public function getImmediateChildren(){
        return $this->_getData('immediate_children');
    }
    public function __construct(){
        parent::__construct();
        $self = $this;
        $this->_dataProperties['lft'] = null;
        $this->_dataProperties['rgt'] = null;
        $this->_dataProperties['immediate_parent'] = null;
        $this->_dataProperties['all_parents'] = null;
        $this->_dataProperties['immediate_children'] = null;

        $this->_toArrayPropertyMethodMap['lft'] = 'getLft';
        $this->_toArrayPropertyMethodMap['rgt'] = 'getRgt';
    }
}