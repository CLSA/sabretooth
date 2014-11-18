<?php
/**
 * surveys.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database\limesurvey;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * surveys: record
 */
class surveys extends record
{
  /**
   * Gets the survey's title in the base language.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_title()
  {
    $modifier = lib::create( 'database\modifier' );
    $modifier->join( 'surveys_languagesettings', $join_mod );
    $modifier->where( 'sid', '=', $this->sid );

    // get the title from the survey's main language
    return static::db()->get_one(
      sprintf( 'SELECT surveyls_title FROM %s %s',
               static::get_table_name(),
               $modifier->get_sql() ) );
  }

  /**
   * Returns an associative array describing this survey's token attributes
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return array( string => string )
   * @access public
   */
  public function get_token_attribute_names()
  {
    // attribute descriptions are storred differently in limesurvey 1 and 2
    $attribute_list = array();
    if( is_null( $this->attributedescriptions ) )
    {
      // there are no attributes...
    }
    else if( false !== strpos( $this->attributedescriptions, "\n" ) )
    { // limesurvey 1 separates attributes with \n
      foreach( explode( "\n", $this->attributedescriptions ) as $attribute )
      {
        if( 10 < strlen( $attribute ) )
        {
          $key = 'attribute_'.substr( $attribute, 10, strpos( $attribute, '=' ) - 10 );
          $value = substr( $attribute, strpos( $attribute, '=' ) + 1 );
          $attribute_list[$key] = $value;
        }
      }
    }
    else
    { // limesurvey 2 serializes attributes
      $attribute_descriptions = unserialize( $this->attributedescriptions );
      if( false === $attribute_descriptions )
      {
        throw lib::create( 'exception\runtime',
          'Unable to interpret limesurvey token attributes.', __METHOD__ );
      }

      foreach( $attribute_descriptions as $key => $attribute )
        $attribute_list[$key] = $attribute['description'];
    }

    return $attribute_list;
  }

  /**
   * The name of the table's primary key column.
   * @var string
   * @access protected
   */
  protected static $primary_key_name = 'sid';
}
