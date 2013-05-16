<?php
/**
 * participant_secondary.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget participant secondary
 */
class participant_secondary extends \cenozo\ui\widget\base_record
{
  /**
   * Constructor
   * 
   * Defines all variables required by the participant secondary widget.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'participant', 'secondary', $args );
  }

  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    $this->set_heading( 'Secondary Contact List' );
  }

  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $alternate_list = array();
    foreach( $this->get_record()->get_alternate_list() as $db_alternate )
    {
      if( $db_alternate->alternate )
      { // only add alternates (not proxies or informants)
        $phone_list = array();
        foreach( $db_alternate->get_phone_list() as $db_phone )
        {
          if( $db_phone->active )
            $phone_list[$db_phone->rank] = array(
              'type' => $db_phone->type,
              'number' => $db_phone->number,
              'clean_number' => preg_replace( '/[^0-9]/', '', $db_phone->number ),
              'note' => $db_phone->note ? $db_phone->note : '' );
        }

        if( count( $phone_list ) )
        {
          ksort( $phone_list );

          $alternate_list[] = array(
            'id' => $db_alternate->id,
            'first_name' => $db_alternate->first_name,
            'last_name' => $db_alternate->last_name,
            'association' => $db_alternate->association ? $db_alternate->association : 'unknown',
            'phone_list' => $phone_list );
        }
      }
    }

    $this->set_variable( 'alternate_list', $alternate_list );
    $this->set_variable( 'participant_name',
      sprintf( $this->get_record()->first_name.' '.$this->get_record()->last_name ) );
    $this->set_variable( 'secondary_id',
      array_key_exists( 'secondary_id', $_COOKIE ) ?  $_COOKIE['secondary_id'] : 0 );
  }
}
