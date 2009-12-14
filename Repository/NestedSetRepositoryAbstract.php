<?php
/*
 * Persists nested trees using a slight modification of Rolf Brugger's Nested Set Tree Library
 */
abstract class AtlivaDomainModeling_Repository_NestedSetRepositoryAbstract extends AtlivaDomainModeling_Repository_ZendRepositoryAbstract {
    protected $_lftColName;
    protected $_rgtColName;
    protected $_nestSetTableName;
    protected $_db;

    public function findById($id){
        $dbResultData = $this->_getDbEntityPrimitive()->where('id = ?', $id)->query()->fetch();
        if($dbResultData){
            return $this->_createEntity($dbResultData);
        } else {
            return null;
        }
    }
    public function findAllParents($entityOrId){
        if(is_numeric($entityOrId)){
            $entityId = $entityOrId;
            $contextNode = $this->findById($entityId);
        } else {
            $contextNode = $entityOrId;
        }

        $ancestorsData = $this->_nstAncestor($contextNode);
        return $this->_createEntitiesCollection(array(
            'get_entities_select_statement'     => null,
            'get_entities'                      => $ancestorsData,
            'get_num_total_select_statement'    => null,
            'get_num_total'                     => count($ancestorsData)
            ));
    }
    public function findImmediateParent($entityOrId){
        if(is_numeric($entityOrId)){
            $entityId = $entityOrId;
            $contextNode = $this->findById($entityId);
        } else {
            $contextNode = $entityOrId;
        }

        $immediateParentData = $this->_immediateAncestor($contextNode);
        if($immediateParentData){
            return $this->_createEntity($immediateParentData);
        } else {
            return null;
        }
    }
    public function findImmediateChildren($nodeId){
        $loadImmediateChildrenSqlStatement = $this->_db->quoteInto("
        SELECT node.*, (COUNT(parent.id) - (sub_tree.depth + 1)) AS depth
        FROM $this->_nestSetTableName AS node,
                $this->_nestSetTableName AS parent,
                $this->_nestSetTableName AS sub_parent,
                (
                        SELECT node.id, (COUNT(parent.id) - 1) AS depth
                        FROM $this->_nestSetTableName AS node,
                        $this->_nestSetTableName AS parent
                        WHERE node.$this->_lftColName BETWEEN parent.$this->_lftColName AND parent.$this->_rgtColName
                        AND node.id = ?
                        GROUP BY node.id
                        ORDER BY node.$this->_lftColName
                )AS sub_tree
        WHERE node.lft BETWEEN parent.$this->_lftColName AND parent.$this->_rgtColName
                AND node.$this->_lftColName BETWEEN sub_parent.$this->_lftColName AND sub_parent.$this->_rgtColName
                AND sub_parent.id = sub_tree.id
        GROUP BY node.id
        HAVING depth = 1
        ORDER BY node.$this->_lftColName;", $nodeId);
        $immediateChildCategoriesData = $this->_db->query($loadImmediateChildrenSqlStatement)->fetchAll();
        return $this->_createEntitiesCollection(array(
            'get_entities_select_statement'     => null,
            'get_entities'                      => $immediateChildCategoriesData,
            'get_num_total_select_statement'    => null,
            'get_num_total'                     => count($immediateChildCategoriesData)
            ));

    }
    public function findAllChildren($nodeId){
        $loadImmediateChildrenSqlStatement = $this->_db->quoteInto("
        SELECT node.*, (COUNT(parent.id) - (sub_tree.depth + 1)) AS depth
        FROM $this->_nestSetTableName AS node,
                $this->_nestSetTableName AS parent,
                $this->_nestSetTableName AS sub_parent,
                (
                        SELECT node.id, (COUNT(parent.id) - 1) AS depth
                        FROM $this->_nestSetTableName AS node,
                        $this->_nestSetTableName AS parent
                        WHERE node.$this->_lftColName BETWEEN parent.$this->_lftColName AND parent.$this->_rgtColName
                        AND node.id = ?
                        GROUP BY node.id
                        ORDER BY node.$this->_lftColName
                )AS sub_tree
        WHERE node.lft BETWEEN parent.$this->_lftColName AND parent.$this->_rgtColName
                AND node.$this->_lftColName BETWEEN sub_parent.$this->_lftColName AND sub_parent.$this->_rgtColName
                AND sub_parent.id = sub_tree.id
        GROUP BY node.id
        HAVING depth > 0
        ORDER BY node.$this->_lftColName;", $nodeId);
        $immediateChildCategoriesData = $this->_db->query($loadImmediateChildrenSqlStatement)->fetchAll();
        return $this->_createEntitiesCollection(array(
            'get_entities_select_statement'     => null,
            'get_entities'                      => $immediateChildCategoriesData,
            'get_num_total_select_statement'    => null,
            'get_num_total'                     => count($immediateChildCategoriesData)
            ));
    }
    public function saveEntity(AtlivaDomainModeling_DataObject_NestedSetEntityAbstract $nodeEntity){
//        $isNew = !$nodeEntity->getId();
//        $this->_processNodeSave($currentNodeData, $positionChangeInfo, $isNew);
    }
    /*
     * _processNodeSave
     * Saves the node entry into the database
     * @param array $currentNodeData column name and value pairs for the node we are saving
     * @param array|null $positionChangeInfo details on where the node will be placed in the tree
     * @param bool $isNew
     */
    protected function _processNodeSave($currentNodeData, $positionChangeInfo, $isNew,$currentNodeId){

        if(!$positionChangeInfo){
            if($isNew){
                //should throw exception as new nodes need to have a position in the node tree
            } else {
                $where = $this->_db->quoteInto('id = ?', $currentNodeId);
                $this->_db->update($this->_nestSetTableName, $currentNodeData, $where);
            }
            return false;
        }

        $contextIdOrNodeEntity = $positionChangeInfo['context'];
        if(is_numeric($contextIdOrNodeEntity)){
            $contextId = $contextIdOrNodeEntity;
            $contextNode = $this->findById($contextId);
            if(!$contextNode){
                //should throw exception as we would have no way to position the current node
            }
        } else {
            $contextNode = $contextIdOrNodeEntity;
        }
        switch($positionChangeInfo['type']){
            case 'firstChild':
                if($isNew){
                    $this->_nstNewFirstChild($contextNode,$currentNodeData);
                } else {
                    $this->_nstMoveToFirstChild($contextNode,$currentNodeData);
                }

                break;
            case 'lastChild':
                if($isNew){
                    $this->_nstNewLastChild($contextNode,$currentNodeData);
                } else {
                    $this->_nstMoveToLastChild($contextNode,$currentNodeData);
                }
                break;
            case 'nextSibbling':
                if($isNew){
                    $this->_nstNewNextSibling($contextNode,$currentNodeData);
                } else {
                    $this->_nstMoveToNextSibling($contextNode,$currentNodeData);
                }
                break;
            case 'previousSibbling':
                if($isNew){
                    $this->_nstNewPrevSibling($contextNode,$currentNodeData);
                } else {
                    $this->_nstMoveToPrevSibling($contextNode,$currentNodeData);
                }
                break;
        }
        return true;
    }

    /*
      Nested tree logic borrowed from Rolf Brugger's Nested Set Tree Library
      Nested Set Tree Library

      Author:  Rolf Brugger, edutech
      Version: 0.02, 5. April 2005
      URL:     http://www.edutech.ch/contribution/nstrees

      DB-Model by Joe Celko (http://www.celko.com/)

      References:
        http://www.sitepoint.com/article/1105/2
        http://searchdatabase.techtarget.com/tip/1,289483,sid13_gci537290,00.html
        http://dbforums.com/arch/57/2002/6/400142

     */
    /* ******************************************************************* */
    /* Tree Constructors */
    /* ******************************************************************* */

    protected function _nstNewFirstChild ($contextNode,$currentNodeData)
    /* creates a new first child of 'node'. */
    {
      $lftColVal = $contextNode->getLft() + 1;
      $currentNodeData[$this->_lftColName] = $lftColVal;
      $currentNodeData[$this->_rgtColName] = $contextNode->getLft() + 2;
      $this->_shiftRLValues($lftColVal, 2);
      $this->_db->insert($this->_nestSetTableName, $currentNodeData);
//      return $newnode;
    }

    protected function _nstNewLastChild ($contextNode,$currentNodeData)
    /* creates a new last child of 'node'. */
    {
      $lftColVal = $contextNode->getRgt();
      $currentNodeData[$this->_lftColName] = $lftColVal;
      $currentNodeData[$this->_rgtColName] = $contextNode->getRgt() + 1;
      $this->_shiftRLValues($lftColVal, 2);
      $this->_db->insert($this->_nestSetTableName, $currentNodeData);
//      return $newnode;
    }

    protected function _nstNewPrevSibling ($contextNode,$currentNodeData)
    {
      $lftColVal = $contextNode->getLft();
      $currentNodeData[$this->_lftColName] = $lftColVal;
      $currentNodeData[$this->_rgtColName] = $lftColVal + 1;
      $this->_shiftRLValues($lftColVal, 2);
      $this->_db->insert($this->_nestSetTableName, $currentNodeData);
//      return $newnode;
    }

    protected function _nstNewNextSibling ($contextNode,$currentNodeData)
    {
      $lftColVal = $contextNode->getRgt() + 1;
      $currentNodeData[$this->_lftColName] = $lftColVal;
      $currentNodeData[$this->_rgtColName] = $contextNode->getRgt() + 2;
      $this->_shiftRLValues($lftColVal, 2);
      $this->_db->insert($this->_nestSetTableName, $currentNodeData);
//      return $newnode;
    }


    /* *** internal routines *** */

    protected function _shiftRLValues ($first, $delta)
    /* adds '$delta' to all L and R values that are >= '$first'. '$delta' can also be negative. */
    { //print("SHIFT: add $delta to gr-eq than $first <br/>");
      $queryStatement = "UPDATE $this->_nestSetTableName SET $this->_lftColName = $this->_lftColName + $delta  WHERE $this->_lftColName >= $first;
      UPDATE $this->_nestSetTableName SET $this->_rgtColName = $this->_rgtColName + $delta WHERE $this->_rgtColName >= $first;";
      $this->_db->query($queryStatement);
    }
    protected function _shiftRLRange ($first, $last, $delta)
    /* adds '$delta' to all L and R values that are >= '$first' and <= '$last'. '$delta' can also be negative.
       returns the shifted first/last values as node array.
     */
    {
      $queryStatement = "UPDATE $this->_nestSetTableName SET $this->_lftColName = $this->_lftColName + $delta WHERE $this->_lftColName >= $first AND $this->_lftColName <= $last;
        UPDATE $this->_nestSetTableName SET $this->_rgtColName = $this->_rgtColName + $delta WHERE $this->_rgtColName >= $first AND $this->_rgtColName <= $last;";
      $this->_db->query($queryStatement);
      return array('l'=>$first+$delta, 'r'=>$last+$delta);
    }


    /* ******************************************************************* */
    /* Tree Reorganization */
    /* ******************************************************************* */

    /* all nstMove... functions return the new position of the moved subtree. */
    protected function _nstMoveToNextSibling ($contextNode,$currentNodeData)
    /* moves the node '$currentNodeData' and all its children (subtree) that it is the next sibling of '$contextNode'. */
    {
      return $this->_moveSubtree ($currentNodeData, $contextNode->getRgt() + 1);
    }

    protected function _nstMoveToPrevSibling ($contextNode,$currentNodeData)
    /* moves the node '$currentNodeData' and all its children (subtree) that it is the prev sibling of '$contextNode'. */
    {
      return $this->_moveSubtree ($currentNodeData, $contextNode->getLft());
    }

    protected function _nstMoveToFirstChild ($contextNode,$currentNodeData)
    /* moves the node '$currentNodeData' and all its children (subtree) that it is the first child of '$contextNode'. */
    {
      return $this->_moveSubtree ($currentNodeData, $contextNode->getLft()+1);
    }

    protected function _nstMoveToLastChild ($contextNode,$currentNodeData)
    /* moves the node '$src' and all its children (subtree) that it is the last child of '$dst'. */
    {
      return $this->_moveSubtree ($currentNodeData, $contextNode->getRgt());
    }

    protected function _moveSubtree ($currentNodeData, $to)
    /* '$currentNodeData' is the node/subtree, '$to' is its destination l-value */
    {
      $treesize = $currentNodeData[$this->_rgtColName] - $currentNodeData[$this->_lftColName] + 1;
      $this->_shiftRLValues($to, $treesize);
      if($currentNodeData[$this->_lftColName] >= $to){ // src was shifted too?
            $currentNodeData[$this->_lftColName] += $treesize;
        $currentNodeData[$this->_rgtColName] += $treesize;
      }
      /* now there's enough room next to target to move the subtree*/
      $newpos =
      $this->_shiftRLRange($currentNodeData[$this->_lftColName], $currentNodeData[$this->_rgtColName], $to - $currentNodeData[$this->_lftColName]);
      /* correct values after source */
      $this->_shiftRLValues($currentNodeData[$this->_rgtColName] + 1, -$treesize);
      if($currentNodeData[$this->_lftColName] <= $to){ // dst was shifted too?
            $newpos['l'] -= $treesize;
        $newpos['r'] -= $treesize;
      }
      //return $newpos;
    }

    /* ******************************************************************* */
    /* Tree Destructors */
    /* ******************************************************************* */

    protected function _nstDelete ($node)
    /* deletes the node '$node' and all its children (subtree). */
    {
      $lft = $node->getLft();
      $rgt = $node->getRgt();
      $leftanchor = $lft;
      
      $queryStatement = "DELETE FROM $this->_nestSetTableName WHERE
             $this->_lftColName >= $lft AND $this->_rgtColName <= $rgt";
      $this->_db->query($queryStatement);
      _shiftRLValues($rgt + 1, $lft - $rgt -1);
//      if (!$res) {_prtError();}
//      return nstGetNodeWhere ($thandle,
//                        $thandle['lvalname']."<".$leftanchor
//                       ." ORDER BY ".$thandle['lvalname']." DESC"
//                     );
    }



    /* ******************************************************************* */
    /* Tree Queries */
    /*
     * the following functions return a valid node (L and R-value),
     * or L=0,R=0 if the result doesn't exist.
     */
    /* ******************************************************************* */

    function _nstGetNodeWhere ($whereclause)
    /* returns the first node that matches the '$whereclause'.
       The WHERE-caluse can optionally contain ORDER BY or LIMIT clauses too.
     */
    {
      return $this->_db->query("SELECT * FROM $this->_nestSetTableName WHERE $whereclause");
      
    }
//
//    function nstGetNodeWhereLeft ($thandle, $leftval)
//    /* returns the node that matches the left value 'leftval'.
//     */
//    { return nstGetNodeWhere($thandle, $thandle['lvalname']."=".$leftval);
//    }
//    function nstGetNodeWhereRight ($thandle, $rightval)
//    /* returns the node that matches the right value 'rightval'.
//     */
//    { return nstGetNodeWhere($thandle, $thandle['rvalname']."=".$rightval);
//    }
//
//    function nstRoot ($thandle)
//    /* returns the first node that matches the '$whereclause' */
//    { return nstGetNodeWhere ($thandle, $thandle['lvalname']."=1");
//    }
//
//    function nstFirstChild ($thandle, $node)
//    { return nstGetNodeWhere ($thandle, $thandle['lvalname']."=".($node['l']+1));
//    }
//    function nstLastChild ($thandle, $node)
//    { return nstGetNodeWhere ($thandle, $thandle['rvalname']."=".($node['r']-1));
//    }
//    function nstPrevSibling ($thandle, $node)
//    { return nstGetNodeWhere ($thandle, $thandle['rvalname']."=".($node['l']-1));
//    }
//    function nstNextSibling ($thandle, $node)
//    { return nstGetNodeWhere ($thandle, $thandle['lvalname']."=".($node['r']+1));
//    }
    protected function _nstAncestor ($contextNode)
    { 
        return $this->_nstGetNodeWhere (
                        "$this->_lftColName < ".$contextNode->getLft()
                       ." AND $this->_rgtColName > " . $contextNode->getRgt()
                       ." ORDER BY ".$this->_rgtColName
                     )->fetchAll();
    }
    protected function _immediateAncestor ($contextNode)
    {
        return $this->_nstGetNodeWhere (
                        "$this->_lftColName < ".$contextNode->getLft()
                       ." AND $this->_rgtColName > " . $contextNode->getRgt()
                       ." ORDER BY ($this->_rgtColName - $this->_lftColName)
                        LIMIT 1"
                     )->fetch();
    }
//
//
//    /* ******************************************************************* */
//    /* Tree Functions */
//    /*
//     * the following functions return a boolean value
//     */
//    /* ******************************************************************* */
//
//    function nstValidNode ($thandle, $node)
//    /* only checks, if L-value < R-value (does no db-query)*/
//    { return ($node['l'] < $node['r']);
//    }
//    function nstHasAncestor ($thandle, $node)
//    { return nstValidNode($thandle, nstAncestor($thandle, $node));
//    }
//    function nstHasPrevSibling ($thandle, $node)
//    { return nstValidNode($thandle, nstPrevSibling($thandle, $node));
//    }
//    function nstHasNextSibling ($thandle, $node)
//    { return nstValidNode($thandle, nstNextSibling($thandle, $node));
//    }
//    function nstHasChildren ($thandle, $node)
//    { return (($node['r']-$node['l'])>1);
//    }
//    function nstIsRoot ($thandle, $node)
//    { return ($node['l']==1);
//    }
//    function nstIsLeaf ($thandle, $node)
//    { return (($node['r']-$node['l'])==1);
//    }
//    function nstIsChild ($node1, $node2)
//    /* returns true, if 'node1' is a direct child or in the subtree of 'node2' */
//    { return (($node1['l']>$node2['l']) and ($node1['r']<$node2['r']));
//    }
//    function nstIsChildOrEqual ($node1, $node2)
//    { return (($node1['l']>=$node2['l']) and ($node1['r']<=$node2['r']));
//    }
//    function nstEqual ($node1, $node2)
//    { return (($node1['l']==$node2['l']) and ($node1['r']==$node2['r']));
//    }
//
//
//    /* ******************************************************************* */
//    /* Tree Functions */
//    /*
//     * the following functions return an integer value
//     */
//    /* ******************************************************************* */
//
//    function nstNbChildren ($thandle, $node)
//    { return (($node['r']-$node['l']-1)/2);
//    }
//
//    function nstLevel ($thandle, $node)
//    /* returns node level. (root level = 0)*/
//    {
//      $res = mysql_query("SELECT COUNT(*) AS level FROM ".$thandle['table']." WHERE "
//                       .$thandle['lvalname']."<".($node['l'])
//                       ." AND ".$thandle['rvalname'].">".($node['r'])
//                     );
//
//      if ($row = mysql_fetch_array ($res)) {
//        return $row["level"];
//      }else{
//        return 0;
//      }
//    }
//
//    /* ******************************************************************* */
//    /* Tree Walks  */
//    /* ******************************************************************* */
//
//    function nstWalkPreorder ($thandle, $node)
//    /* initializes preorder walk and returns a walk handle */
//    {
//      $res = mysql_query("SELECT * FROM ".$thandle['table']
//             ." WHERE ".$thandle['lvalname'].">=".$node['l']
//             ."   AND ".$thandle['rvalname']."<=".$node['r']
//             ." ORDER BY ".$thandle['lvalname']);
//
//      return array('recset'=>$res,
//                   'prevl'=>$node['l'], 'prevr'=>$node['r'], // needed to efficiently calculate the level
//                   'level'=>-2 );
//    }
//
//    function nstWalkNext($thandle, &$walkhand)
//    {
//      if ($row = mysql_fetch_array ($walkhand['recset'], MYSQL_ASSOC)){
//        // calc level
//            $walkhand['level']+= $walkhand['prevl'] - $row[$thandle['lvalname']] +2;
//            // store current node
//        $walkhand['prevl'] = $row[$thandle['lvalname']];
//        $walkhand['prevr'] = $row[$thandle['rvalname']];
//        $walkhand['row']   = $row;
//        return array('l'=>$row[$thandle['lvalname']], 'r'=>$row[$thandle['rvalname']]);
//      } else{
//        return FALSE;
//      }
//    }
//
//    function nstWalkAttribute($thandle, $walkhand, $attribute)
//    {
//      return $walkhand['row'][$attribute];
//    }
//
//    function nstWalkCurrent($thandle, $walkhand)
//    {
//      return array('l'=>$walkhand['prevl'], 'r'=>$walkhand['prevr']);
//    }
//    function nstWalkLevel($thandle, $walkhand)
//    {
//      return $walkhand['level'];
//    }
//
//
//
//    /* ******************************************************************* */
//    /* Printing Tools */
//    /* ******************************************************************* */
//
//    function nstNodeAttribute ($thandle, $node, $attribute)
//    /* returns the attribute of the specified node */
//    {
//      $res = mysql_query("SELECT * FROM ".$thandle['table']." WHERE ".$thandle['lvalname']."=".$node['l']);
//      if ($row = mysql_fetch_array ($res)) {
//        return $row[$attribute];
//      }else{
//        return "";
//      }
//    }
//
//    function nstPrintSubtree ($thandle, $node, $attributes)
//    /*  */
//    {
//      $wlk = nstWalkPreorder($thandle, $node);
//      while ($curr = nstWalkNext($thandle, $wlk)) {
//            // print indentation
//            print (str_repeat("&nbsp;", nstWalkLevel($thandle, $wlk)*4));
//            // print attributes
//            $att = reset($attributes);
//            while($att){
//          // next line is more efficient:  print ($att.":".nstWalkAttribute($thandle, $wlk, $att));
//              print ($wlk['row'][$att]);
//              $att = next($attributes);
//            }
//            print ("<br/>");
//      }
//    }
//
//    function nstPrintSubtreeOLD ($thandle, $node, $attributes)
//    /*  */
//    {
//      $res = mysql_query("SELECT * FROM ".$thandle['table']." ORDER BY ".$thandle['lvalname']);
//      if (!$res) {_prtError();}
//      else{
//        $level = -1;
//            $prevl = 0;
//        while ($row = mysql_fetch_array ($res)) {
//              // calc level
//              if      ($row[$thandle['lvalname']] == ($prevl+1)) {
//                $level+=1;
//              }elseif ($row[$thandle['lvalname']] != ($prevr+1)) {
//                $level-=1;
//              }
//              // print indentation
//              print (str_repeat("&nbsp;", $level*4));
//              // print attributes
//              $att = reset($attributes);
//              while($att){
//            print ($att.":".$row[$att]);
//                    $att = next($attributes);
//              }
//              print ("<br/>");
//              $prevl = $row[$thandle['lvalname']];
//              $prevr = $row[$thandle['rvalname']];
//            }
//      }
//    }
//
//    function nstPrintTree ($thandle, $attributes)
//    /* Prints attributes of the entire tree. */
//    {
//      nstPrintSubtree ($thandle, nstRoot($thandle), $attributes);
//    }
//
//
//    function nstBreadcrumbsString ($thandle, $node)
//    /* returns a string representing the breadcrumbs from $node to $root
//       Example: "root > a-node > another-node > current-node"
//
//       Contributed by Nick Luethi
//     */
//    {
//      // current node
//      $ret = nstNodeAttribute ($thandle, $node, "name");
//      // treat ancestor nodes
//      while(nstAncestor ($thandle, $node) != array("l"=>0,"r"=>0)){
//        $ret = "".nstNodeAttribute($thandle, nstAncestor($thandle, $node), "name")." &gt; ".$ret;
//        $node = nstAncestor ($thandle, $node);
//      }
//      return $ret;
//      //return "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;breadcrumb: <font size='1'>".$ret."</font>";
//    }

}
