<?php
/**
 * query.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\qnaire\stratum;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Special class for handling the query meta-resource
 */
class query extends \cenozo\service\query
{
  /**
   * Override parent method
   */
  protected function prepare()
  {
    parent::prepare();

    // if the application has a study-phase then only show the parent study's strata
    $db_study_phase = lib::create( 'business\session' )->get_application()->get_study_phase();
    if( !is_null( $db_study_phase ) )
    {
      $this->modifier->join( 'study', 'stratum.study_id', 'study.id' );
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'study.id', '=', 'study_phase.study_id', false );
      $join_mod->where( 'study_phase.id', '=', $db_study_phase->id );
      $this->modifier->join_modifier( 'study_phase', $join_mod );
    }
  }
}
