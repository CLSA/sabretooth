<?php
/**
 * activity_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget activity list
 * 
 * @package sabretooth\ui
 */
class activity_list extends base_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the activity list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'activity', $args );
    
    $session = \sabretooth\session::self();

    // define all template variables for this list
    $this->set_heading( 'Activity list' );
    
    $this->columns = array(
      array( 'id' => 'user.name',
             'heading' => 'user',
             'sortable' => true ),
      array( 'id' => 'site.name',
             'heading' => 'site',
             'sortable' => true ),
      array( 'id' => 'role.name',
             'heading' => 'role',
             'sortable' => true ),
      array( 'id' => 'operation.type',
             'heading' => 'type',
             'sortable' => true ),
      array( 'id' => 'operation.subject',
             'heading' => 'subject',
             'sortable' => true ),
      array( 'id' => 'operation.name',
             'heading' => 'name',
             'sortable' => true ),
      array( 'id' => 'elapsed_time',
             'heading' => 'elapsed',
             'sortable' => true ),
      array( 'id' => 'date',
             'heading' => 'date',
             'sortable' => true ) );
  }

  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function set_rows()
  {
    // reset the array
    $this->rows = array();
    
    foreach( $this->get_record_list() as $record )
    {
      array_push( $this->rows, 
        array( 'id' => $record->id,
               'columns' => array( 'user.name' => $record->get_user()->name,
                                   'site.name' => $record->get_site()->name,
                                   'role.name' => $record->get_role()->name,
                                   'operation.type' => $record->get_operation()->type,
                                   'operation.subject' => $record->get_operation()->subject,
                                   'operation.name' =>$record->get_operation()->name,
                                   'elapsed_time' => sprintf( '%0.2fs', $record->elapsed_time ),
                                   'date' => $record->date ) ) );
    }
  }
}
?>
