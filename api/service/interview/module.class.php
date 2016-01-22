<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\interview;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\module
{
  /**
   * Extend parent method
   */
  public function validate()
  {
    parent::validate();

    $session = lib::create( 'business\session' );
    $db_application = lib::create( 'business\session' )->get_application();
    $db_role = $session->get_role();
    $db_site = $session->get_site();

    // make sure the application has access to the participant
    $db_interview = $this->get_resource();
    if( !is_null( $db_interview ) )
    {
      $db_participant = $this->get_resource()->get_participant();
      if( $db_application->release_based && !is_null( $db_participant ) )
      {
        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'participant_id', '=', $db_participant->id );
        if( 0 == $db_application->get_participant_count( $modifier ) ) $this->get_status()->set_code( 404 );
      }

      // make sure the user has access ot the participant
      if( !is_null( $db_participant ) && !$db_role->all_sites )
      {
        $db_effective_site = $db_participant->get_effective_site();
        if( is_null( $db_effective_site ) || $db_site->id != $db_effective_site->id )
          $this->get_status()->set_code( 403 );
      }
    }
  }

  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    $session = lib::create( 'business\session' );

    if( $select->has_table_columns( 'participant' ) || !$session->get_role()->all_sites )
    {
      $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );

      // restrict to participants in this site (for some roles)
      if( !$session->get_role()->all_sites )
      {
        $sub_mod = lib::create( 'database\modifier' );
        $sub_mod->where( 'participant.id', '=', 'participant_site.participant_id', false );
        $sub_mod->where( 'participant_site.application_id', '=', $session->get_application()->id );

        $modifier->join_modifier( 'participant_site', $sub_mod );
        $modifier->where( 'participant_site.site_id', '=', $session->get_site()->id );
      }
    }

    if( $select->has_table_columns( 'site' ) )
      $modifier->left_join( 'site', 'interview.site_id', 'site.id' );

    if( $select->has_table_columns( 'qnaire' ) || $select->has_table_columns( 'script' ) )
    {
      $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
      if( $select->has_table_columns( 'script' ) )
        $modifier->join( 'script', 'qnaire.script_id', 'script.id' );
    }

    if( $select->has_column( 'open_appointment_count' ) )
    {
      $join_sel = lib::create( 'database\select' );
      $join_sel->from( 'appointment' );
      $join_sel->add_column( 'interview_id' );
      $join_sel->add_column( 'COUNT( * )', 'open_appointment_count', false );

      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'assignment_id', '=', NULL );
      $join_mod->group( 'interview_id' );

      $modifier->left_join(
        sprintf( '( %s %s ) AS interview_join_appointment', $join_sel->get_sql(), $join_mod->get_sql() ),
        'interview.id',
        'interview_join_appointment.interview_id' );
      $select->add_column( 'IFNULL( open_appointment_count, 0 )', 'open_appointment_count', false );
    }

    if( $select->has_column( 'open_callback_count' ) )
    {
      $join_sel = lib::create( 'database\select' );
      $join_sel->from( 'callback' );
      $join_sel->add_column( 'interview_id' );
      $join_sel->add_column( 'COUNT( * )', 'open_callback_count', false );

      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'assignment_id', '=', NULL );
      $join_mod->group( 'interview_id' );

      $modifier->left_join(
        sprintf( '( %s %s ) AS interview_join_callback', $join_sel->get_sql(), $join_mod->get_sql() ),
        'interview.id',
        'interview_join_callback.interview_id' );
      $select->add_column( 'IFNULL( open_callback_count, 0 )', 'open_callback_count', false );
    }
  }
}
