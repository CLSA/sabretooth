<?php
/**
 * setting_manager.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\business
 * @filesource
 */

namespace sabretooth\business;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Manages software settings
 * 
 * @package sabretooth\business
 */
class setting_manager extends \cenozo\business\setting_manager
{
  /**
   * Constructor.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\argument
   * @access protected
   */
  protected function __construct( $arguments )
  {
    parent::__construct( $arguments );
    
    $static_settings = $arguments[0];

    // copy the setting one category at a time, ignore any unknown categories
    $categories = array( 'db',
                         'audit_db',
                         'survey_db',
                         'general',
                         'interface',
                         'version',
                         'ldap',
                         'voip' );
    foreach( $categories as $category )
    {
      // make sure the category exists
      if( !array_key_exists( $category, $static_settings ) )
        throw new exc\argument( 'static_settings['.$category.']', NULL, __METHOD__ );
      
      $this->static_settings[ $category ] = $static_settings[ $category ];
    }

    // have the audit settings mirror limesurvey, if necessary
    foreach( $this->static_settings[ 'audit_db' ] as $key => $value )
    {
      if( false === $value && 'enabled' != $key )
        $this->static_settings[ 'audit_db' ][ $key ] =
          $this->static_settings[ 'survey_db' ][ $key ];
    }
  }
}
