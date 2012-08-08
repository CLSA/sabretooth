<?php
/**
 * site_restricted_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Base class for all list widgets which may be restricted by site.
 */
abstract class site_restricted_list extends \cenozo\ui\widget\site_restricted_list
{
  /**
   * Overrides the parent class method based on the restrict site member.
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
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
      $site_column = ( $this->extended_site_selection ? 'participant_site.' : '' ).'site_id';
      $modifier->where( $site_column, '=', $this->db_restrict_site->id );
    }

    // skip the parent method
    // php doesn't allow parent::parent::method() so we have to do the less safe code below
    $base_list_class_name = lib::get_class_name( 'ui\widget\base_list' );
    return $base_list_class_name::determine_record_count( $modifier );
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
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
      $site_column = ( $this->extended_site_selection ? 'participant_site.' : '' ).'site_id';
      $modifier->where( $site_column, '=', $this->db_restrict_site->id );
    }

    // skip the parent method
    // php doesn't allow parent::parent::method() so we have to do the less safe code below
    $base_list_class_name = lib::get_class_name( 'ui\widget\base_list' );
    return $base_list_class_name::determine_record_list( $modifier );
  }

  /**
   * Whether the subject is participant_site based.
   * @var boolean
   * @access protected
   */
  protected $extended_site_selection = false;
}
?>
