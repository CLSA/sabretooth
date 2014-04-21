<?php
/**
 * prerecruit_select.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: prerecruit select
 */
class prerecruit_select extends \cenozo\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'prerecruit', 'select', $args );
  }
  
  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @abstract
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    foreach( $this->get_argument( 'totals' ) as $quota => $total )
    {
      $quota_id = str_replace( 'quota', '', $quota );
      $this->totals[$quota_id] = $total;
    }
  }

  /**
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    $prerecruit_class_name = lib::get_class_name( 'database\prerecruit' );
    $participant_id = $this->get_argument( 'participant_id' );

    // write all prerecruit totals to the database (keeping a count as we go)
    $count = 0;
    foreach( $this->totals as $quota_id => $total )
    {
      // for the purpose of determining prerecruitment, test any qnaire is disabled for this quota
      $db_quota = lib::create( 'database\quota', $quota_id );
      if( 0 == $db_quota->get_qnaire_count() ) $count += $total;

      $db_prerecruit = $prerecruit_class_name::get_unique_record(
        array( 'participant_id', 'quota_id' ),
        array( $participant_id, $quota_id ) );
      
      // if the prerecruit record doesn't exist yet, create it
      if( is_null( $db_prerecruit ) )
      {
        $db_prerecruit = lib::create( 'database\prerecruit' );
        $db_prerecruit->participant_id = $participant_id;
        $db_prerecruit->quota_id = $quota_id;
      }

      $db_prerecruit->total = $total;
      $db_prerecruit->selected = 0;
      $db_prerecruit->save();
    }

    // now select a random individual whose quota is not closed
    $selection = mt_rand( 1, $count );
    $count = 0;
    foreach( $this->totals as $quota_id => $total )
    {
      // for the purpose of determining prerecruitment, test any qnaire is disabled for this quota
      $db_quota = lib::create( 'database\quota', $quota_id );
      if( 0 == $db_quota->get_qnaire_count() )
      {
        $count += $total;
        if( $count >= $selection )
        {
          $db_prerecruit = $prerecruit_class_name::get_unique_record(
            array( 'participant_id', 'quota_id' ),
            array( $participant_id, $quota_id ) );
        
          $db_prerecruit->selected = $count - $selection + 1;
          $db_prerecruit->save();
          break;
        }
      }
    }
  }

  /**
   * An associative array of quota id to individual total passed in the arguments
   */
  private $totals;
}
