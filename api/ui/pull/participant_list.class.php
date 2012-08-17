<?php
/**
 * participant_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\pull;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Class for participant list pull operations.
 * 
 * @abstract
 */
class participant_list extends \cenozo\ui\pull\base_list
{
  /**
   * Constructor
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'participant', $args );
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

    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );

    // get the qnaire if one was provided
    $qnaire_rank = $this->get_argument( 'qnaire_rank', NULL );
    $qnaire_name = $this->get_argument( 'qnaire_name', NULL );
    if( !is_null( $qnaire_rank ) )
      $this->db_qnaire = $qnaire_class_name::get_unique_record( 'rank', $qnaire_rank );
    else if( !is_null( $qnaire_name ) )
      $this->db_qnaire = $qnaire_class_name::get_unique_record( 'name', $qnaire_name );
  }

  /**
   * Validate the operation.  If validation fails this method will throw a notice exception.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws excpetion\argument, exception\permission
   * @access protected
   */
  protected function validate()
  {
    parent::validate();

    // if are using a special state restriction then we need to make sure
    // we also have a qnaire rank or name
    $state = $this->get_argument( 'state', NULL );
    if( !is_null( $state ) && is_null( $this->db_qnaire ) )
      throw lib::create( 'exception\argument', 'qnaire_*', NULL, __METHOD__ );
  }

  /**
   * Sets up the operation with any pre-execution instructions that may be necessary.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    // an array of all negative consent types
    $neg_consent_list = array( 'written deny', 'retract', 'withdraw' );

    // see if we are restricting by region
    $region_key = $this->get_argument( 'region', NULL );
    if( !is_null( $region_key ) )
    {
      $region_class_name = lib::get_class_name( 'database\region' );
      $db_region = lib::create( 'database\region',
        $region_class_name::get_primary_from_unique_key( $region_key ) );
      if( is_null( $this->modifier ) ) $this->modifier = lib::create( 'database\modifier' );
      $this->modifier->where( 'address.region_id', '=', $db_region->id );
    }

    $state = $this->get_argument( 'state', NULL );
    if( !is_null( $state ) )
    {
      if( is_null( $this->modifier ) ) $this->modifier = lib::create( 'database\modifier' );
      $this->modifier->where( 'interview.qnaire_id', '=', $this->db_qnaire->id );

      if( 'contacted' == $state )
      {
        // if the participant has an interview then they have been assigned at least once, which
        // means they have been called at least once, therefore we don't need to do anything else
      }
      else if( 'reached' == $state )
      {
        // the participant is eligible
        $this->modifier->where( 'participant.status', '=', NULL );
        $this->modifier->where_bracket( true );
        $this->modifier->where( 'participant_last_consent.event', '=', NULL );
        $this->modifier->or_where( 'participant_last_consent.event', 'NOT IN', $neg_consent_list );
        $this->modifier->where_bracket( false );

        // and the participant has been contacted at least once
        $this->modifier->where(
          'interview.id', '=', 'interview_phone_call_status_count.interview_id', false );
        $this->modifier->where( 'interview_phone_call_status_count.status', '=', 'contacted' );
        $this->modifier->where( 'interview_phone_call_status_count.total', '>', 0 );
      }
      else if( 'appointment' == $state )
      {
        // the participant is eligible
        $this->modifier->where( 'participant.status', '=', NULL );
        $this->modifier->where_bracket( true );
        $this->modifier->where( 'participant_last_consent.event', '=', NULL );
        $this->modifier->or_where( 'participant_last_consent.event', 'NOT IN', $neg_consent_list );
        $this->modifier->where_bracket( false );

        // and either there is an upcomming appointment (reached is null) or the interview is complete
        $this->modifier->where_bracket( true );

        $this->modifier->where_bracket( true );
        $this->modifier->where( 'participant_last_appointment.appointment_id', '!=', NULL );
        $this->modifier->where( 'participant_last_appointment.reached', '=', NULL );
        $this->modifier->where_bracket( false );
        
        $this->modifier->where_bracket( true, true );
        $this->modifier->where( 'interview.completed', '=', true );
        $this->modifier->where_bracket( false );
        
        $this->modifier->where_bracket( false );
      }
      else if( 'completed' == $state )
      {
        // the participant is eligible
        $this->modifier->where( 'participant.status', '=', NULL );
        $this->modifier->where_bracket( true );
        $this->modifier->where( 'participant_last_consent.event', '=', NULL );
        $this->modifier->or_where( 'participant_last_consent.event', 'NOT IN', $neg_consent_list );
        $this->modifier->where_bracket( false );

        // and the interview is complete
        $this->modifier->where( 'interview.completed', '=', true );
      }
      else if( 'consented' == $state )
      {
        // the interview must be complete and signed consent received
        // (it doesn't matter if the participant is still eligible)
        $this->modifier->where( 'interview.completed', '=', true );
        $this->modifier->where(
          'participant_last_written_consent.consent_id', '=', 'consent.id', false );
        $this->modifier->where( 'consent.event', '=', 'written accept' );
      }
      else throw lib::create( 'exception\argument', 'state', $state, __METHOD__ );
    }
  }

  /**
   * Overrides the parent method to add participant address, phone and consent details.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\record $record
   * @return array
   * @access public
   */
  public function process_record( $record )
  {
    $source_class_name = lib::get_class_name( 'database\source' );
    $site_class_name = lib::get_class_name( 'database\site' );

    $item = parent::process_record( $record );

    // convert primary ids to unique
    $item['source_id'] = $source_class_name::get_unique_from_primary_key( $item['source_id'] );
    $item['site_id'] = $site_class_name::get_unique_from_primary_key( $item['site_id'] );

    // add full participant information if requested
    if( $this->get_argument( 'full', false ) )
    {
      $item['address_list'] = $this->prepare_list( $record->get_address_list() );
      $item['phone_list'] = $this->prepare_list( $record->get_phone_list() );
      $item['consent_list'] = $this->prepare_list( $record->get_consent_list() );
      $item['availability_list'] = $this->prepare_list( $record->get_availability_list() );
      $item['note_list'] = $this->prepare_list( $record->get_note_list() );
    }
    else
    {
      // add the primary address
      $db_address = $record->get_primary_address();
      if( !is_null( $db_address ) )
      {
        $item['street'] = is_null( $db_address->address2 )
                        ? $db_address->address1
                        : $db_address->address1.', '.$db_address->address2;
        $item['city'] = $db_address->city;
        $item['region'] = $db_address->get_region()->name;
        $item['postcode'] = $db_address->postcode;
      }
    }

    return $item;
  }

  /**
   * Converts a list of records into an array which can be transmitted without primary IDs
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array( record ) $record_list
   * @return array( array )
   * @access protected
   */
  protected function prepare_list( $record_list )
  {
    $participant_class_name = lib::get_class_name( 'database\participant' );

    $prepared_list = array();
    foreach( $record_list as $record )
    {
      $data = array();
      foreach( $record->get_column_names() as $column_name )
      {
        // ignore id, person_id and participant_id columns
        if( 'id' != $column_name &&
            'person_id' != $column_name &&
            'participant_id' != $column_name )
        {
          if( '_id' == substr( $column_name, -3 ) )
          {
            $subject = substr( $column_name, 0, -3 );
            $class_name = lib::get_class_name( 'database\\'.$subject );
            $key = $class_name::get_unique_from_primary_key( $record->$column_name );

            // convert person keys to participant keys
            if( array_key_exists( 'person_id', $key ) )
            {
              // replace person key with participant key
              $participant_id = $record->get_person()->get_participant()->id;
              unset( $key['person_id'] );
              $key['participant_id'] =
                $participant_class_name::get_unique_from_primary_key( $participant_id );
            }

            $data[$column_name] = $key;
          }
          else $data[$column_name] = $record->$column_name;
        }
      }
      $prepared_list[] = $data;
    }

    return $prepared_list;
  }

  /**
   * The qnaire to restrict to.
   * @var database\qnaire
   * @access protected
   */
  protected $db_qnaire = NULL;
}
?>
