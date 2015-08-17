<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\user\assignment;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * The base class of all post services.
 */
class post extends \cenozo\service\post
{
  /**
   * TODO: document
   */
  protected function setup()
  {
    parent::setup();

    $relationship_class_name = lib::get_class_name( 'database\relationship' );

    if( $relationship_class_name::MANY_TO_MANY !== $this->get_leaf_parent_relationship() )
    {
      $record = $this->get_leaf_record();
      $parent_record = $this->get_parent_record();

      if( !is_null( $parent_record ) )
      { // add the parent relationship
        $parent_column = sprintf( '%s_id', $parent_record::get_table_name() );
        $record->$parent_column = $parent_record->id;
      }

      // add record column data
      $post_object = $this->get_file_as_object();
      foreach( $record->get_column_names() as $column_name )
        if( 'id' != $column_name && property_exists( $post_object, $column_name ) )
          $record->$column_name = $post_object->$column_name;
    }
  }
}
