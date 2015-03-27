<?php
/**
 * query.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\qnaire;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * The base class of all query (collection-based get) services
 */
class query extends \cenozo\service\query
{
  /**
   * Processes arguments, preparing them for the service.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    // link to the previous qnaire
    if( $this->select->has_table_columns( 'prev_qnaire' ) )
      $this->modifier->left_join( 'qnaire', 'qnaire.prev_qnaire_id', 'prev_qnaire.id', 'prev_qnaire' );

    // link to the default interview method
    if( $this->select->has_table_columns( 'default_interview_method' ) )
      $this->modifier->cross_join( 'interview_method',
        'qnaire.default_interview_method_id', 'default_interview_method.id', 'default_interview_method' );

    // add the total number of phases
    if( $this->select->has_table_column( '', 'phase_count' ) )
    {
      $this->modifier->left_join( 'phase', 'qnaire.id', 'phase.qnaire_id' );
      $this->modifier->group( 'qnaire.id' );
      $this->select->add_column( 'IF( phase.id IS NULL, 0, COUNT(*) )', 'phase_count', false );
    }
  }
}
