<?php
/**
 * mailout_required_report.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget mailout required report
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
  }

  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    $this->add_restriction( 'site' );
    $this->add_restriction( 'qnaire' );
    $this->add_restriction( 'source' );
    $this->add_parameter( 'mailout_type', 'enum', 'Mailout' );

    $this->set_variable( 'description',
      'This report lists all participants (or proxies) who require an information package'.
      ' to be mailed out to them.  The report generates the participant\'s id,'. 
      ' name, address and last date they were successfully contacted.' );
  }

  /**
   * Extending the parent setup method with extra restrictions.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $mailout_type_list = array( 'Participant information package',
                                'Proxy information package' );
    $mailout_type_list = array_combine( $mailout_type_list, $mailout_type_list );

    $this->set_parameter( 'mailout_type', key( $mailout_type_list ), true, $mailout_type_list );
  }
}
