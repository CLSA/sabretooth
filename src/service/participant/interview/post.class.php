<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\participant\interview;
use cenozo\lib, cenozo\log, sabretooth\util;

class post extends \cenozo\service\post
{
  /**
   * Override parent method
   */
  protected function validate()
  {
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );

    $db_participant = $this->get_parent_record();

    // make sure the participant doesn't already have an open interview
    $interview_mod = lib::create( 'database\modifier' );
    $interview_mod->where( 'end_datetime', '=', NULL );
    if( 0 < $db_participant->get_interview_count( $interview_mod ) )
    {
      $this->set_data( 'Cannot create interview since the participant already has an incomplete interview.' );
      $this->get_status()->set_code( 409 );
    }
    else
    {
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

  /**
   * We create a new interview by using the participant's get_effective_interview
   * method.
   */
  protected function create_resource( $index )
  {
    $resource = null;
    if( 'interview' == $this->get_subject( $index ) )
    {
      $resource = $this->get_parent_record()->get_effective_interview( false );
      $resource->start_datetime = util::get_datetime_object();
    }
    else
    {
      $resource = parent::create_resource( $index );
    }

    return $resource;
  }
}
