<?php
/**
 * participant_status_report.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget self status
 */
class participant_status_report extends base_report
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
    parent::__construct( 'participant_status', $args );
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

    $this->add_restriction( 'qnaire' );
    $this->add_restriction( 'site_or_province' );
    $this->add_restriction( 'source' );
    $this->add_restriction( 'dates' );

    $this->set_variable( 'description',
      'This report provides totals of various status types.  Populations are broken down '.
      'by province or by site and various call, participant and consent statuses.  If set, '.
      'the start and end dates will restrict the report to participants who were synched '.
      'between the dates provided, inclusive.' );
  }
}
?>
