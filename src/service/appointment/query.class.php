<?php
/**
 * query.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\appointment;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Special queue for handling the query meta-resource
 */
class query extends \cenozo\service\query
{
  /**
   * Override parent method
   */
  protected function execute()
  {
    parent::execute();

    // if the mime type is anything other than json then we need to massage it
    if( 'application/json' != $this->get_mime_type() )
    {
      foreach( $this->data as $index => $record )
      {
        if( array_key_exists( 'username', $record ) ) unset( $this->data[$index]['username'] );
        if( array_key_exists( 'assignment_user', $record ) ) unset( $this->data[$index]['assignment_user'] );
        if( array_key_exists( 'start_time', $record ) ) unset( $this->data[$index]['start_time'] );
        if( array_key_exists( 'end_time', $record ) ) unset( $this->data[$index]['end_time'] );
        if( array_key_exists( 'date', $record ) ) unset( $this->data[$index]['date'] );
      }
    }
  }
}
