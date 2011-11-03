<?php
/**
 * user_list.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\pull;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * Class for user list pull operations.
 * 
 * @abstract
 * @package sabretooth\ui
 */
class user_list extends base_list
{
  /**
   * Constructor
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'user', $args );
  }

  /**
   * Overrides the base list by allowing restrictions to refer to site and role tables
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return array
   * @access public
   */
  public function finish()
  {
    $site_mod = NULL;
    $role_mod = NULL;
    $new_restrictions = array();

    // go through the restriction array and convert references to site and role tables
    foreach( $this->restrictions as $restriction )
    {
      $table = strstr( $restriction['column'], '.', true );
      $col_name = substr( strstr( $restriction['column'], '.' ), 1 );
      if( 'site' == $table )
      {
        if( is_null( $site_mod ) ) $site_mod = new db\modifier();
        $site_mod->where( $col_name, $restriction['operator'], $restriction['value'] );
      }
      else if( 'role' == $table )
      {
        if( is_null( $role_mod ) ) $role_mod = new db\modifier();
        $role_mod->where( $col_name, $restriction['operator'], $restriction['value'] );
      }
      else $new_restrictions[] = $restriction;
    }

    // add the site id to the new restrictions array (if needed)
    if( !is_null( $site_mod ) )
    {
      $site_ids = array();
      foreach( db\site::select( $site_mod ) as $db_site ) $site_ids[] = $db_site->id;

      if( 1 == count( $site_ids ) )
        $new_restrictions[] = array(
          'column' => 'site_id',
          'operator' => '=',
          'value' => current( $site_ids ) );
      else if( 1 < count( $site_ids ) )
        $new_restrictions[] = array(
          'column' => 'site_id',
          'operator' => 'in',
          'value' => $site_ids );
    }

    // add the role id to the new restrictions array (if needed)
    if( !is_null( $role_mod ) )
    {
      $role_ids = array();
      foreach( db\role::select( $role_mod ) as $db_role ) $role_ids[] = $db_role->id;

      if( 1 == count( $role_ids ) )
        $new_restrictions[] = array(
          'column' => 'role_id',
          'operator' => '=',
          'value' => current( $role_ids ) );
      else if( 1 < count( $role_ids ) )
        $new_restrictions[] = array(
          'column' => 'role_id',
          'operator' => 'in',
          'value' => $role_ids );
    }

    // only bother to update the restrictions if at least one of the mods has been created
    if( !is_null( $site_mod ) || !is_null( $role_mod ) )
      $this->restrictions = $new_restrictions;

    // now finish as usual
    return parent::finish();
  }
}
?>
