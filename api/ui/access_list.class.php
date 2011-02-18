<?php
/**
 * access_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget access list
 * 
 * @package sabretooth\ui
 */
class access_list extends base_list_widget
{
  /**
   * Constructor
   * 
   * Defines all variables required by the access list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'access', $args );
    
    $session = \sabretooth\session::self();

    $this->columns = array(
      array( 'id' => 'user.name',
             'heading' => 'User',
             'sortable' => true ),
      array( 'id' => 'role.name',
             'heading' => 'Role',
             'sortable' => true ),
      array( 'id' => 'site.name',
             'heading' => 'Site',
             'sortable' => true ),
      array( 'id' => 'date',
             'heading' => 'Granted',
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
    
    // get all sites
    foreach( $this->get_record_list() as $record )
    {
      array_push(
        $this->rows,
        array( 'id' => $record->id,
               'columns' =>
                 array( 'user.name' => $record->get_user()->name,
                        'role.name' => $record->get_role()->name,
                        'site.name' => $record->get_site()->name,
                        'date' => \sabretooth\util::get_fuzzy_time_ago( $record->date ) ) ) );
    }
  }
}
?>
