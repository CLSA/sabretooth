<?php
/**
 * site_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * site.list widget
 * 
 * @package sabretooth\ui
 */
class site_list extends base_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the site list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'site', $args );
    
    $session = \sabretooth\session::self();

    // define all template variables for this list
    $this->set_heading( 'Site list' );
    $this->checkable = false;
    $this->viewable = true; // TODO: should be based on role
    $this->editable = false;
    $this->removable = false;

    $this->columns = array(
      array( 'id' => 'name',
             'name' => 'name',
             'sortable' => true ),
      array( 'id' => 'users',
             'name' => 'users',
             'sortable' => false ),
      array( 'id' => 'last',
             'name' => 'last activity',
             'sortable' => true ) ); 
  }

  /**
   * Overrides the parent class method since the list can be sorted by a column outside of the user
   * table.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access protected
   */
  protected function determine_record_sort_column( $sort_name )
  {
    if( 'last' == $sort_name )
    { // column in activity, see user::select() for details
      $sort = 'activity.date';
    }
    else
    {
      $sort = parent::determine_record_sort_column( $sort_name );
    }

    return $sort;
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
      // determine the last activity
      $db_activity = $record->get_last_activity();
      $last = is_null( $db_activity )
            ? 'never'
            : \sabretooth\util::get_fuzzy_time_ago( $db_activity->date );

      array_push( $this->rows, array( 'id' => $record->id,
               'columns' => array( $record->name, $record->get_user_count(), $last ) ) );
    }
  }
}
?>
