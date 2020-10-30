<?php
/**
 * data_manager.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\business;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * A manager to provide various data to external sources based on string-based keys
 */
class data_manager extends \cenozo\business\data_manager
{
  /**
   * Get participant-based data
   * 
   * @param database\participant
   * @param string $key The key string defining which data to return
   * @return string
   * @access public
   */
  public function get_participant_value( $db_participant, $key )
  {
    // make sure the db_participant object is valid
    if( is_null( $db_participant ) ||
        false === strpos( get_class( $db_participant ), 'database\participant' ) )
      throw lib::create( 'exception\argument', 'db_participant', $db_participant, __METHOD__ );

    // parse the key
    $parts = $this->parse_key( $key, true );
    $subject = $parts[0];

    $value = NULL;
    if( 'qnaire' == $subject )
    {
      $event_class_name = lib::get_class_name( 'database\event' );
      $qnaire_class_name = lib::get_class_name( 'database\qnaire' );

      // participant.qnaire.<rank>.started_event.<column> or
      // qnaire.<rank>.started_event.<column>
      // participant.qnaire.<rank>.finished_event.<column> or
      // qnaire.<rank>.finished_event.<column>
      // NOTE: response will be 'DATE UNKNOWN' if null
      if( 4 != count( $parts ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );

      $qnaire_rank = $parts[1];
      if( !util::string_matches_int( $qnaire_rank ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );

      $event_type_name = $parts[2];
      if( 'started_event' != $event_type_name &&
          'finished_event' != $event_type_name )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );

      $column = $parts[3];

      $event_class_name = lib::get_class_name( 'database\event' );
      if( !$event_class_name::column_exists( $column ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );

      $db_qnaire = $qnaire_class_name::get_unique_record( 'rank', $qnaire_rank );
      if( !is_null( $db_qnaire ) )
      {
        $db_event_type = NULL;
        $method = sprintf( 'get_%s_type', $event_type_name );
        $db_event_type = $db_qnaire->get_script()->$method();

        if( !is_null( $db_event_type ) )
        {
          $event_mod = lib::create( 'database\modifier' );
          $event_mod->where( 'event_type_id', '=', $db_event_type->id );
          $event_mod->order_desc( 'datetime' );
          $event_list = $db_participant->get_event_list( NULL, $event_mod );
          if( 0 < count( $event_list ) )
          {
            if( !array_key_exists( $column, $event_list ) )
              throw lib::create( 'exception\argument', 'column', $column, __METHOD__ );
            $value = $event_list[0][$column];
          }
        }
      }
    }
    else
    {
      // to be handled by the base class
      $value = parent::get_participant_value( $db_participant, $key );
    }

    return $value;
  }
}
