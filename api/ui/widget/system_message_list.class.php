<?php
/**
 * system_message_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * widget system_message list
 * 
 * @package sabretooth\ui
 */
class system_message_list extends site_restricted_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the system_message list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'system_message', $args );
    
    $this->add_column( 'site.name', 'enum', 'Site', true );
    $this->add_column( 'role.name', 'enum', 'Role', true );
    $this->add_column( 'title', 'string', 'Title', true );
  }
  
  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
    
    foreach( $this->get_record_list() as $record )
    {
      $db_site = $record->get_site();
      $db_role = $record->get_role();

      // assemble the row for this record
      $this->add_row( $record->id,
        array( 'site.name' => $db_site ? $db_site->name : 'all',
               'role.name' => $db_role ? $db_role->name : 'all',
               'title' => $record->title ) );
    }

    $this->finish_setting_rows();
  }

  /**
   * Overrides the parent class method to also include system messages with no site
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  protected function determine_record_count( $modifier = NULL )
  {
    if( !is_null( $this->db_restrict_site ) )
    {
      if( NULL == $modifier ) $modifier = new db\modifier();
      $modifier->where( 'site_id', '=', $this->db_restrict_site->id );
      $modifier->or_where( 'site_id', '=', NULL );
    }
    
    // skip the parent method
    // php doesn't allow parent::parent::method() so we have to do the less safe code below
    return base_list_widget::determine_record_count( $modifier );
  }

  /**
   * Overrides the parent class method based on the restrict site member.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  protected function determine_record_list( $modifier = NULL )
  {
    if( !is_null( $this->db_restrict_site ) )
    {
      if( NULL == $modifier ) $modifier = new db\modifier();
      $modifier->where( 'site_id', '=', $this->db_restrict_site->id );
      $modifier->or_where( 'site_id', '=', NULL );
    }
    
    // skip the parent method
    // php doesn't allow parent::parent::method() so we have to do the less safe code below
    return base_list_widget::determine_record_list( $modifier );
  }
}
?>
