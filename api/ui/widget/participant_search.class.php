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

    // restrict to the application's participants
    $db_application = lib::create( 'business\session' )->get_application();
    if( $db_application->release_based )
    { // make sure the participant has been released
      $this->modifier->join(
        'application_has_participant',
        'participant.id',
        'application_has_participant.participant_id' );
      $this->modifier->where( 'application_has_participant.datetime', '!=', NULL );
      $this->modifier->where( 'application_has_participant.application_id', '=', $db_application->id );
    }
    else
    { // make sure the participant is in one of the application's cohorts
      $this->modifier->join(
        'application_has_cohort',
        'participant.cohort_id',
        'application_has_cohort.cohort_id' );
      $this->modifier->where( 'application_has_cohort.application_id', '=', $db_application->id );
    }
  }
}
