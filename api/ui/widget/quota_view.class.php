<?php
/**
 * quota_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget quota view
 */
class quota_view extends \cenozo\ui\widget\quota_view
{
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

    // create the qnaire sub-list widget
    $this->qnaire_list = lib::create( 'ui\widget\qnaire_list', $this->arguments );
    $this->qnaire_list->set_parent( $this );
    $this->qnaire_list->set_heading( 'Disabled Questionnaires' );
  }

  /**
   * Defines all items in the view.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    try
    {
      $this->qnaire_list->process();
      $this->set_variable( 'qnaire_list', $this->qnaire_list->get_variables() );
    }
    catch( \cenozo\exception\permission $e ) {}
  }

  /**
   * The qnaire list widget.
   * @var qnaire_list
   * @access protected
   */
  protected $qnaire_list = NULL;
}
