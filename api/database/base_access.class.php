<?php
/**
 * base_access.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;

/**
 * A base class for all classes joined together by the access table.
 * 
 * @package sabretooth\database
 */
abstract class base_access extends active_record
{
   /**
   * Returns whether the user has the role for the given site.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\site $db_site
   * @param database\role $db_role
   * @return bool
   * @access public
   */
  public function has_access( $db_site, $db_role )
  {
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to determine access for user with no id.' );
      return 0;
    } 
    
    $modifier = new modifier();
    $modifier->where( 'user_id', '=', $this->id );
    $modifier->where( 'site_id', '=', $db_site->id );
    $modifier->where( 'role_id', '=', $db_role->id );
    
    $rows = self::get_one(
      sprintf( 'SELECT user_id FROM access %s',
               $modifier->get_sql() ) );
               
    return count( $rows );
  } 
 
  /**
   * Count the total number of rows in the table.
   * 
   * Overrides the parent method since this class is related to others through the access table.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the count.
   * @return int
   * @static
   * @access public
   */
  public static function count( $modifier )
  {
    $subject_name = self::get_table_name();

    // check to see if the modifier is sorting a value in the access table
    if( !is_null( $modifier ) )
    {
      foreach( array( 'user', 'role', 'site' ) as $access_table )
      {
        if( $subject_name != $access_table )
        {
          if( $modifier->has_where( $access_table.'_id' ) )
          {
            $modifier->where( $subject_name.'.id', '=', 'access.'.$subject_name.'_id', false );
            return self::get_one(
              sprintf( 'SELECT COUNT( DISTINCT access.%s_id ) FROM %s, access %s',
                       $subject_name,
                       $subject_name,
                       $modifier->get_sql() ) );
          }
        }
      }
    }
    
    // if we get here then the regular parent method is fine
    return parent::count( $modifier );
  }            

  /**
   * Select a number of records.
   * 
   * Overrides the parent method since this class is related to others through the access table.
   * Warning, the functionality in the parent class' select method searches for foreign keys in
   * the order clauses of the modifier to link to the related tables if necessary.  Currently the
   * access-related tables (user, role and site) do not have any foreign keys in them so that
   * functionality has been left out of this method.  Should that change then this method will
   * need to be expanded.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the selection.
   * @return array( active_record )
   * @static
   * @access public
   */
  public static function select( $modifier = NULL )
  {
    $subject_name = self::get_table_name();

    // check to see if the modifier is sorting a value in the access table
    if( !is_null( $modifier ) )
    {
      foreach( array( 'user', 'role', 'site' ) as $access_table )
      {
        if( $subject_name != $access_table )
        {
          if( $modifier->has_where( $access_table.'_id' ) )
          {
            $modifier->where( $subject_name.'.id', '=', 'access.'.$subject_name.'_id', false );
            $modifier->group( 'access.'.$subject_name.'_id' );
    
            $id_list = self::get_col(
              sprintf( 'SELECT %s.id FROM %s, access %s',
                       $subject_name,
                       $subject_name,
                       $modifier->get_sql() ) );
        
            $records = array();
            foreach( $id_list as $id ) array_push( $records, new static( $id ) );
            return $records;
          }
        }
      }
    }

    // if we get here then the regular parent method is fine
    return parent::select( $modifier );
  }

  /**
   * Returns the most recent activity performed by this access-based record.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\activity
   * @access public
   */
  public function get_last_activity()
  {
    $subject_name = self::get_table_name();
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to query '.$subject_name.' with no id.' );
      return NULL;
    }
    
    $modifier = new modifier();
    $modifier->where( $subject_name.'_id', '=', $this->id );
    $activity_id = self::get_one(
      sprintf( 'SELECT activity_id FROM %s_last_activity %s',
               $subject_name,
               $modifier->get_sql() ) );
    
    return is_null( $activity_id ) ? NULL : new activity( $activity_id );
  }

  /**
   * Get the number of activity entries for this access-based record.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the count.
   * @return int
   * @access public
   */
  public function get_activity_count( $modifier = NULL)
  {
    $subject_name = self::get_table_name();
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to query '.$subject_name.' with no id.' );
      return 0;
    }
    
    if( is_null( $modifier ) ) $modifier = new modifier();
    $modifier->where( $subject_name.'_id', '=', $this->id );
    return activity::count( $modifier );
  }

  /**
   * Get an activity list for this access-based record.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( database\activity )
   * @access public
   */
  public function get_activity_list( $modifier = NULL )
  {
    $subject_name = self::get_table_name();
    $activity_list = array();
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to query '.$subject_name.' with no id.' );
      return $activity_list;
    }
    
    if( is_null( $modifier ) ) $modifier = new modifier();
    $modifier->where( $subject_name.'_id', '=', $this->id );
    return activity::select( $modifier );
  }

  /**
   * Get the number of related access-based records.
   * 
   * This method expands on the active_record magic call method by allowing access-based records
   * select their related lists.
   * For instance:
   *   user has get_role_<count|list>() and get_site_<count|list>()
   *   role has get_user_<count|list>() and get_site_<count|list>()
   *   site has get_user_<count|list>() and get_role_<count|list>()
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the count.
   * @return int
   * @access public
   * @method int get_<record>_count() Returns the number of related access-based records.
   * @method int get_<record>_list() Returns an array of related access-based records.
   */
  public function __call( $name, $args )
  {
    $subject_name = self::get_table_name();
    
    // parse the function call name
    $name_parts = explode( '_', $name );
    $related_name = $name_parts[1];
    $action = $name_parts[2];
    
    // make sure the method name is one which we want to process
    if( 3 != count( $name_parts ) ||
        'get' != $name_parts[0] ||
        ( 'user' != $related_name && 'role' != $related_name && 'site' != $related_name ) ||
        ( 'count' != $action && 'list' != $action ) ||
        $subject_name == $related_name )
    {
      return parent::__call( $name, $args );
    }

    // now that we are relatively sure the method name is valid, make sure we have a valid record
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to query user with no id.' );
      return 0;
    }
    
    // define the modifier
    $modifier = 1 == count( $args ) &&
                'sabretooth\\database\\modifier' == get_class( $args[0] )
              ? $args[0]
              : new modifier();

    $modifier->where( $subject_name.'_id', '=', $this->id );
    
    $class_name = '\\sabretooth\\database\\'.$related_name;
    return 'list' == $action
           ? $class_name::select( $modifier )
           : $class_name::count( $modifier );
  }
}
?>
