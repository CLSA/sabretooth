<?php
/**
 * consent_required_report.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget consent required report
 */
class consent_required_report extends base_report
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'consent_required', $args );
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

    if( lib::create( 'business\session' )->get_role()->all_sites )
      $this->add_restriction( 'site' );
    $this->add_restriction( 'dates' );

    $this->set_variable( 'description',
      'This report lists all participants who have completed their interview but have not '.
      'provided written consent.  The report generates the participant\'s uid, name and the '.
      'date their interview was completed.  Selecting a start or end date will restrict the '.
      'list to those participant\'s whose interview was completed after or before the given '.
      'dates, respectively.' );
  }
}
