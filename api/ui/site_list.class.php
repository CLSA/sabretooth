<?php
/**
 * site_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
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
  public function __construct( $args = NULL )
  {
    parent::__construct( 'site', $args );
    
    $session = \sabretooth\session::self();

    // define all template variables for this list
    $this->heading =  "Site list";
    $this->checkable =  false;
    $this->viewable =  true; // TODO: should be based on role
    $this->editable =  false;
    $this->removable =  false;
    $this->number_of_items = \sabretooth\database\site::count();

    $this->columns = array(
      array( "id" => "name",
             "name" => "name",
             "sortable" => true ),
      array( "id" => "users",
             "name" => "users",
             "sortable" => false ),
      array( "id" => "last",
             "name" => "last activity",
             "sortable" => true ) ); 
  }

  /**
   * Set the details of each site as a row.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param int $limit_count The number of rows to include.
   * @param int $limit_count The offset to start rows at.
   * @access protected
   */
  protected function set_rows( $limit_count, $limit_offset )
  {
    // reset the array
    $this->rows = array();
    
    // get all sites
    $session = \sabretooth\session::self();
    $sort = 'name' == $this->sort_column ? 'name' : NULL;
    $desc = $this->sort_desc;
    $db_site_list = \sabretooth\database\site::select( $limit_count, $limit_offset, $sort, $desc );
    foreach( $db_site_list as $db_site )
    {
      array_push( $this->rows, 
        array( 'id' => $db_site->id,
               'columns' => array( $db_site->name, $db_site->get_user_count(), 'TODO' ) ) );
    }
  }
}
?>
