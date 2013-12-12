<?php
/**
 * participant_search.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget participant search
 */
class participant_search extends \cenozo\ui\widget\participant_search
{
  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    // restrict to the service's participants
    $db_service = lib::create( 'business\session' )->get_service();
    if( $db_service->release_based )
    { // make sure the participant has been released
      $this->modifier->where(
        'participant.id', '=', 'service_has_participant.participant_id', false );
      $this->modifier->where( 'service_has_participant.datetime', '!=', NULL );
      $this->modifier->where( 'service_has_participant.service_id', '=', $db_service->id );
    }
    else
    { // make sure the participant is in one of the service's cohorts
      $this->modifier->where( 'participant.cohort_id', '=', 'service_has_cohort.cohort_id', false );
      $this->modifier->where( 'service_has_cohort.service_id', '=', $db_service->id );
    }
  }
}
