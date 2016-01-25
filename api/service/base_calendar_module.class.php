<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Performs operations which effect how this module is used in a service
 */
abstract class base_calendar_module extends \cenozo\service\site_restricted_module
{
  /**
   * Extend parent method
   */
  public function validate()
  {
    parent::validate();

    if( !is_null( $this->get_argument( 'min_date', NULL ) ) && is_null( $this->lower_date ) )
    {
      $this->set_data( 'This module cannot be restricting by min_date.' );
      $this->get_status()->set_code( 400 );
    }
    else if( !is_null( $this->get_argument( 'max_date', NULL ) ) && is_null( $this->upper_date ) )
    {
      $this->set_data( 'This module cannot be restricting by max_date.' );
      $this->get_status()->set_code( 400 );
    }
  }

  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    // restrict by date, if requested
    // note: 
    $min_date = $this->get_argument( 'min_date', NULL );
    $max_date = $this->get_argument( 'max_date', NULL );

    if( !is_null( $min_date ) )
    {
      if( $this->lower_date['null'] )
      {
        $modifier->where(
          sprintf( 'IF( %s IS NULL, true, %s >= "%s" )',
                   $this->lower_date['column'],
                   $this->lower_date['column'],
                   $min_date ),
          '=',
          true );
      }
      else
      {
        $modifier->where( $this->lower_date['column'], '>=', $min_date );
      }
    }

    if( !is_null( $max_date ) )
    {
      if( $this->upper_date['null'] )
      {
        $modifier->where(
          sprintf( 'IF( %s IS NULL, true, %s <= "%s" )',
                   $this->upper_date['column'],
                   $this->upper_date['column'],
                   $max_date ),
          '=',
          true );
      }
      else
      {
        $modifier->where( $this->upper_date['column'], '<=', $max_date );
      }
    }
  }

  /**
   * An date-formatted sql representation of the record's earliest date
   * @var array( 'null' => whether the value can be null, 'column' => the sql specification )
   * @access protected
   */
  protected $lower_date = NULL;

  /**
   * An date-formatted sql representation of the record's latest date
   * @var array( 'null' => whether the value can be null, 'column' => the sql specification )
   * @access protected
   */
  protected $upper_date = NULL;
}
