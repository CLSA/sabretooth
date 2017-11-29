<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\participant\interview;
use cenozo\lib, cenozo\log, sabretooth\util;

class post extends \cenozo\service\participant\interview\post
{
  /**
   * Replace parent method
   */
  protected function validate()
  {
    parent::validate();

    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );

    if( 300 > $this->get_status()->get_code() )
    {
      $db_participant = $this->get_parent_record();

      // make sure the participant has a qnaire left to complete
      $qnaire_sel = lib::create( 'database\select' );
      $qnaire_sel->add_column( 'MAX( rank )', 'max_rank', false );
      $results = $qnaire_class_name::select( $qnaire_sel );
      if( 0 == count( $results ) )
      {
        $this->set_data( 'There are no questionnaires available.' );
        $this->get_status()->set_code( 409 );
      }
      else
      {
        $max_rank = $results[0]['max_rank'];

        $select = lib::create( 'database\select' );
        $select->add_column( 'MAX( qnaire.rank )', 'last_rank', false );
        $modifier = lib::create( 'database\modifier' );
        $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
        $results = $db_participant->get_interview_list( $select, $modifier );

        if( 1 == count( $results ) )
        {
          $last_rank = $results[0]['last_rank'];
          if( $last_rank >= $max_rank )
          {
            $this->set_data( 'There are no remaining questionnaires available.' );
            $this->get_status()->set_code( 409 );
          }
        }
      }
    }
  }
}
