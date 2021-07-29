<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\vacancy;
use cenozo\lib, cenozo\log, sabretooth\util;

class post extends \cenozo\service\post
{
  /**
   * Override parent method
   */
  protected function prepare()
  {
    parent::prepare();

    // create multiple vacancies at once
    $post_object = $this->get_file_as_object();
    if( property_exists( $post_object, 'start_datetime' ) )
      $this->start_datetime = util::get_datetime_object( $post_object->start_datetime );
    if( property_exists( $post_object, 'end_datetime' ) )
      $this->end_datetime = util::get_datetime_object( $post_object->end_datetime );
    if( property_exists( $post_object, 'delete_ids' ) )
      $this->delete_ids = $post_object->delete_ids;
  }

  /**
   * Override parent method
   */
  protected function validate()
  {
    parent::validate();

    if( 300 > $this->status->get_code() )
    {
      if( !is_null( $this->start_datetime ) || !is_null( $this->end_datetime ) )
      { // validate the start/end datetimes
        if( is_null( $this->start_datetime ) || is_null( $this->end_datetime ) || $this->start_datetime >= $this->end_datetime )
        {
          $this->status->set_code( 400 );
        }
      }
      else if( !is_null( $this->delete_ids ) )
      {
        if( !is_array( $this->delete_ids ) ) $this->status->set_code( 400 );
      }
    }
  }

  /**
   * Extends parent method
   */
  protected function execute()
  {
    if( !is_null( $this->start_datetime ) )
    {
      // create a list of vacancies and return an array, in order, of the new ids
      $site_id = lib::create( 'business\session' )->get_site()->id;
      $vacancy_size = lib::create( 'business\setting_manager' )->get_setting( 'general', 'vacancy_size' );
      $post_object = $this->get_file_as_object();
      $id_array = array();

      $from_datetime = clone $this->start_datetime;
      $to_datetime = clone $this->start_datetime;
      $to_datetime->add( new \DateInterval( sprintf( 'PT%dM', $vacancy_size ) ) );

      while( $to_datetime <= $this->end_datetime )
      {
        try
        {
          $db_vacancy = lib::create( 'database\vacancy' );
          foreach( $db_vacancy->get_column_names() as $column_name )
            if( 'id' != $column_name && property_exists( $post_object, $column_name ) )
              $db_vacancy->$column_name = $post_object->$column_name;
          $db_vacancy->site_id = $site_id;
          $db_vacancy->datetime = $from_datetime;
          $db_vacancy->save();
          $id_array[] = (int)$db_vacancy->id;
        }
        catch( \cenozo\exception\database $e )
        {
          if( $e->is_duplicate_entry() )
          {
            // ignore duplicates
          }
          else
          {
            $this->status->set_code( $e->is_missing_data() ? 400 : 500 );
            throw $e;
          }
        }

        $from_datetime->add( new \DateInterval( sprintf( 'PT%dM', $vacancy_size ) ) );
        $to_datetime->add( new \DateInterval( sprintf( 'PT%dM', $vacancy_size ) ) );
      }

      if( 0 < count( $id_array ) )
      {
        $this->status->set_code( 201 );
        $this->set_data( $id_array );
      }
    }
    else if( !is_null( $this->delete_ids ) )
    {
      foreach( $this->delete_ids as $id )
      {
        $db_vacancy = lib::create( 'database\vacancy', $id );
        if( !is_null( $db_vacancy ) ) $db_vacancy->delete();
      }
    }
    else parent::execute();
  }

  /**
   * The start datetime when defining multiple vacancies at once
   */
  private $start_datetime = NULL;

  /**
   * The end datetime when defining multiple vacancies at once
   */
  private $end_datetime = NULL;

  /**
   * A list of vacancy ids to delete in a single batch
   */
  private $delete_ids = NULL;
}
