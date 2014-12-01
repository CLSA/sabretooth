<?php
/**
 * callback.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\pull;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Consent form report data.
 * 
 * @abstract
 */
class callback_report extends \cenozo\ui\pull\base_report
{
  /**
   * Constructor
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The subject to retrieve the primary information from.
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'callback', $args );
  }

  /**
   * Builds the report.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function build()
  {
    $db_site = lib::create( 'business\session' )->get_site();
    $date = $this->get_argument( 'date' );
   
    $this->add_title(
      sprintf( 'Callbacks for %s on %s',
               $db_site->name,
               util::get_formatted_date( $date ) ) );

    $contents = array();
    $header = array( 'UID', 'Time', 'Reached', 'Operator' );
    
    $callback_class_name = lib::get_class_name( 'database\callback' );
    $callback_mod = lib::create( 'database\modifier' );
    $callback_mod->join(
      'participant_site', 'callback.participant_id', 'participant_site.participant_id' );
    $callback_mod->where( 'participant_site.site_id', '=', $db_site->id );
    $callback_mod->where( 'datetime', '>=', $date.' 00:00:00' );
    $callback_mod->where( 'datetime', '<=', $date.' 23:59:59' );
    $callback_mod->order( 'datetime' );
    foreach( $callback_class_name::select( $callback_mod ) as $db_callback )
    {
      $db_assignment = $db_callback->get_assignment();
      $contents[] = array(
        $db_callback->get_participant()->uid,
        util::get_formatted_time( $db_callback->datetime, false ),
        $db_callback->reached ? 'Yes' : 'No',
        is_null( $db_assignment ) ? 'none' : $db_assignment->get_user()->name );
    }

    $this->add_table( NULL, $header, $contents, NULL );
  }
}
?>
