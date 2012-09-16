<?php
/**
 * util.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth;
use cenozo\lib;

/**
 * util: utility class of static methods
 *
 * Extends cenozo's util class with additional functionality.
 */
class util extends \cenozo\util
{
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
    if( 'source survey' == $word ) return 'source surveys';
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
