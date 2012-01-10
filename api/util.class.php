<?php
/**
 * util.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth
 * @filesource
 */

namespace sabretooth;

/**
 * util: utility class of static methods
 *
* Extends cenozo's util class with additional functionality.
 * @package sabretooth
 */
class util extends \cenozo\util
{
  /**
   * Returns whether the system is in development mode.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @static
   * @return boolean
   * @access public
   */
  public static function in_devel_mode()
  {
    return true == business\setting_manager::self()->get_setting( 'general', 'development_mode' );
  }

  /**
   * Returns whether the system is in pull mode.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @static
   * @return boolean
   * @access public
   */
  public static function in_pull_mode()
  {
    if( is_null( self::$pull_mode ) )
      self::$pull_mode =
        'pull' == business\setting_manager::self()->get_setting( 'general', 'operation_type' );
    
    return self::$pull_mode;
  }
  
  /**
   * Returns whether the system is in push mode.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @static
   * @return boolean
   * @access public
   */
  public static function in_push_mode()
  {
    if( is_null( self::$push_mode ) )
      self::$push_mode =
        'push' == business\setting_manager::self()->get_setting( 'general', 'operation_type' );
    
    return self::$push_mode;
  }
  
  /**
   * Returns whether the system is in widget mode.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @static
   * @return boolean
   * @access public
   */
  public static function in_widget_mode()
  {
    if( is_null( self::$widget_mode ) )
      self::$widget_mode =
        'widget' == business\setting_manager::self()->get_setting( 'general', 'operation_type' );
    
    return self::$widget_mode;
  }
  
  /**
   * Attempts to convert a word into its plural form.
   * 
   * Warning: this method by no means returns the correct answer in every case.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $word
   * @return string
   * @static
   * @access public
   */
  public static function pluralize( $word )
  {
    // special cases
    if( 'qnaire' == $word ) return 'questionnaires';
    if( 'survey' == $word ) return 'surveys';
    return parent::pluralize( $word );
  }

  /**
   * Cache for pull_mode method.
   * @var bool
   * @access private
   */
  private static $pull_mode = NULL;

  /**
   * Cache for push_mode method.
   * @var bool
   * @access private
   */
  private static $push_mode = NULL;

  /**
   * Cache for widget_mode method.
   * @var bool
   * @access private
   */
  private static $widget_mode = NULL;
}
?>
