<?php
/**
 * setting_manager.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\business
 * @filesource
 */

namespace sabretooth\business;
use sabretooth\log, sabretooth\util;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * Manages software settings
 * 
 * @package sabretooth\business
 */
class setting_manager extends \sabretooth\singleton
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
  }

  /**
   * Get a setting's value
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $category The category the setting belongs to.
   * @param string $name The name of the setting.
   * @access public
   */
  public function get_setting( $category, $name )
  {
    // first check for the setting in static settings
    if( isset( $this->static_settings[ $category ] ) &&
        isset( $this->static_settings[ $category ][ $name ] ) )
    {
      return $this->static_settings[ $category ][ $name ];
    }

    // now check in dynamic settings 
    if( isset( $this->dynamic_settings[ $category ] ) &&
        isset( $this->dynamic_settings[ $category ][ $name ] ) )
    {
      return $this->dynamic_settings[ $category ][ $name ];
    }
    else // check if the setting exists in the database
    {
      $db_setting = db\setting::get_setting( $category, $name );
      if( !is_null( $db_setting ) )
      {
        $modifier = new db\modifier();
        $modifier->where( 'site_id', '=', session::self()->get_site()->id );
        $setting_value_list = $db_setting->get_setting_value_list( $modifier );
        
        $string_value = count( $setting_value_list )
                      ? $setting_value_list[0]->value
                      : $db_setting->value;
        if( 'boolean' == $db_setting->type ) $value = "true" == $string_value;
        else if( 'integer' == $db_setting->type ) $value = intval( $string_value );
        else if( 'float' == $db_setting->type ) $value = floatval( $string_value );
        else $value = $string_value;

        // store the value in case we need it again
        $this->dynamic_settings[ $category ][ $name ] = $value;
        return $value;
      }
    }
    
    // if we get here then the setting doesn't exist
    log::err( "Tried getting value for setting [$category][$name] which doesn't exist." );
    
    return NULL;
  }

  /**
   * An array which holds static (non database) settings
   * @var array( mixed )
   * @access private
   */
  private $static_settings = array();

  /**
   * An array which holds dynamic (database) settings
   * @var array( mixed )
   * @access private
   */
  private $dynamic_settings = array();
}
