<?php
/**
 * callback_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget callback list
 */
class callback_list extends site_restricted_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the callback list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'callback', $args );
  }

  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();
    $this->add_column( 'participant.uid', 'string', 'UID', true );
    $this->add_column( 'phone', 'string', 'Phone number', false );
    $this->add_column( 'datetime', 'datetime', 'Date', true );
    $this->add_column( 'state', 'string', 'State', false );

    $this->extended_site_selection = true;
  }
  
  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    // don't add callbacks if this list isn't parented
    if( is_null( $this->parent ) ) $this->set_addable( false );
    else // don't add callbacks if the parent already has an unassigned callback
    {
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'participant_id', '=', $this->parent->get_record()->id );
      $modifier->where( 'assignment_id', '=', NULL );
      $class_name = lib::get_class_name( 'database\callback' );
      $this->set_addable( 0 == $class_name::count( $modifier ) );
    }

    parent::setup();

    foreach( $this->get_record_list() as $record )
    {
      $db_phone = $record->get_phone();
      $phone = is_null( $db_phone )
             ? 'not specified'
             : sprintf( '(%d) %s: %s',
                        $db_phone->rank,
                        $db_phone->type,
                        $db_phone->number );
      $this->add_row( $record->id,
        array( 'participant.first_name' => $record->get_participant()->first_name,
               'participant.last_name' => $record->get_participant()->last_name,
               'phone' => $phone,
               'datetime' => $record->datetime,
               'state' => $record->get_state() ) );
    }
  }
}
?>
