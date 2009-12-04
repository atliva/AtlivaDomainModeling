<?php
class AtlivaDomainModeling_Repository_Accessor {
    private static $_repositories = array();

    public static function getInstance( $repositoryClassName ){
        if(!isset(self::$_repositories[$repositoryClassName])){
            $newRepositoryInstance = new $repositoryClassName();
            if(!is_subclass_of($newRepositoryInstance, 'AtlivaDomainModeling_Repository_RepositoryAbstract')){
                return false;
            }
            self::$_repositories[$repositoryClassName] = $newRepositoryInstance;
        }
        return self::$_repositories[$repositoryClassName];
    }
}