<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\qnaire\participant;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * The base class of all post services.
 */
class post extends \cenozo\service\write
{
  /**
   * Extends parent constructor
   */
  public function __construct( $path, $args, $file )
  {
    parent::__construct( 'POST', $path, $args, $file );
  }

  /**
   * Extends parent method
   */
  protected function validate()
  {
    parent::validate();

    if( $this->may_continue() )
    {
      $file = $this->get_file_as_object();
      if( !property_exists( $file, 'mode' ) ||
          !in_array( $file->mode, ['confirm', 'update'] ) ||
          !property_exists( $file, 'identifier_list' ) ) $this->status->set_code( 400 );
    }
  }

  /**
   * Extends parent method
   */
  protected function execute()
  {
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $db_qnaire = $this->get_parent_record();
    $file = $this->get_file_as_object();

    // This is a special service since participants cannot be added to the system through the web interface.
    // Instead, this service provides participant-based utility functions.

    $modifier = lib::create( 'database\modifier' );
    if( 'phone' == $file->method )
    {
      // make sure a web interview already exists
      $modifier->join( 'interview', 'participant.id', 'interview.participant_id' );
      $modifier->where( 'interview.method', '=', 'web' );
    }
    else // if( 'web' == $file->method )
    {
      // make sure the interview is eligible and the participant has email
      $modifier->join( 'queue_has_participant', 'participant.id', 'queue_has_participant.participant_id' );
      $modifier->join( 'queue', 'queue_has_participant.queue_id', 'queue.id' );
      $modifier->where( 'queue.name', '=', 'eligible' );
      $modifier->where( 'queue_has_participant.qnaire_id', '=', $db_qnaire->id );
      $modifier->where( 'participant.email', '!=', NULL );
    }

    $identifier_id = property_exists( $file, 'identifier_id' ) ? $file->identifier_id : NULL;
    $db_identifier = is_null( $identifier_id ) ? NULL : lib::create( 'database\identifier', $identifier_id );
    $identifier_list = $participant_class_name::get_valid_identifier_list( $db_identifier, $file->identifier_list, $modifier );
    if( 'update' == $file->mode )
    { // update interview methods
      if( 0 < count( $identifier_list ) )
      {
        $db_qnaire->mass_set_method( $db_identifier, $identifier_list, $file->method );

        // repopulate the queues immediately
        $queue_class_name = lib::get_class_name( 'database\queue' );
        $queue_class_name::delayed_repopulate();
      }
    }
    else // 'confirm' == $file->mode
    { // return a list of all valid identifiers
      $this->set_data( $identifier_list );
    }
  }

  /**
   * Overrides the parent method (this service not meant for creating resources)
   */
  protected function create_resource( $index )
  {
    return 0 == $index ? parent::create_resource( $index ) : NULL;
  }
}
