<?php
/**
 * mailout_required_report.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * widget mailout required report
 * 
 * @package sabretooth\ui
 */
class mailout_required_report extends base_report
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'mailout_required', $args );

    $this->add_restriction( 'site' );
    $this->add_restriction( 'mailout' );
    $this->add_restriction( 'qnaire' );

    $this->set_variable( 'description',
      'This report lists all participants (or proxies) who require an information package'.
      ' to be mailed out to them.  The report generates the participant\'s id,'. 
      ' name, address and last date they were successfully contacted.' );
  }

  /**
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
  
    $this->finish_setting_parameters();
  }
}
?>
