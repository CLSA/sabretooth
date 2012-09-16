<?php
/**
 * queue_restriction_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget queue_restriction list
 */
class queue_restriction_list extends site_restricted_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the queue_restriction list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'queue_restriction', $args );
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
    
    $this->add_column( 'site.name', 'string', 'Site', true );
    $this->add_column( 'city', 'string', 'City', true );
    $this->add_column( 'region.name', 'string', 'Region', false );
    $this->add_column( 'postcode', 'string', 'Postcode', true );
  }
  
  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();
    
    foreach( $this->get_record_list() as $record )
    {
      $db_site = $record->get_site();
      $db_region = $record->get_region();

      // assemble the row for this record
      $this->add_row( $record->id,
        array( 'site.name' => $db_site ? $db_site->name : 'any',
               'city' => $record->city ? $record->city : 'any',
               'region.name' => $db_region ? $db_region->name : 'any',
               'postcode' => $record->postcode ? $record->postcode : 'any' ) );
    }
  }

  /**
   * Overrides the parent class method to also include queue restrictions with no site
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_record_count( $modifier = NULL )
  {
    if( !is_null( $this->db_restrict_site ) )
    {
      if( NULL == $modifier ) $modifier = lib::create( 'database\modifier' );
      $modifier->where_bracket( true );
      $modifier->where( 'queue_restriction.site_id', '=', $this->db_restrict_site->id );
      $modifier->or_where( 'queue_restriction.site_id', '=', NULL );
      $modifier->where_bracket( false );
    }
    
    // skip the parent method
    // php doesn't allow parent::parent::method() so we have to do the less safe code below
    $class_name = lib::get_class_name( 'ui\widget\base_list' );
    return $class_name::determine_record_count( $modifier );
  }

  /**
   * Overrides the parent class method based on the restrict site member.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  public function determine_record_list( $modifier = NULL )
  {
    if( !is_null( $this->db_restrict_site ) )
    {
      if( NULL == $modifier ) $modifier = lib::create( 'database\modifier' );
      $modifier->where_bracket( true );
      $modifier->where( 'queue_restriction.site_id', '=', $this->db_restrict_site->id );
      $modifier->or_where( 'queue_restriction.site_id', '=', NULL );
      $modifier->where_bracket( false );
    }
    
    // skip the parent method
    // php doesn't allow parent::parent::method() so we have to do the less safe code below
    $class_name = lib::get_class_name( 'ui\widget\base_list' );
    return $class_name::determine_record_list( $modifier );
  }
}
?>
