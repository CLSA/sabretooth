<?php
/**
 * base_report.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\pull;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * Base class for all reports.
 * 
 * Reports are built by gathering all data for the report in the constructor and building
 * the report from that data in the {@link finish} method.
 * 
 * @abstract
 * @package sabretooth\ui
 */
abstract class base_report extends \sabretooth\ui\pull
{
  /**
   * Constructor
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The subject to retrieve the primary information from.
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $subject, $args )
  {
    parent::__construct( $subject, 'report', $args );
    $this->report = new bus\report();
  }

  /**
   * Returns the report type (xls, xlsx, html, pdf or csv)
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_data_type()
  {
    return $this->get_argument( 'format' );
  }
  
  protected function add_title( $title )
  {
    array_push( $this->report_titles, $title );
  }

  protected function add_table(
    $title = NULL, $header = array(), $contents = array(), $footer = array() )
  {
    array_push( $this->report_tables,
      array( 'title' => $title,
             'header' => $header,
             'contents' => $contents,
             'footer' => $footer ) );
  }

  /**
   * Builds the report based on the tables built by child classes.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return associative array
   * @access public
   */
  public function finish()
  {
    // determine the widest table size
    $max = 1;
    foreach( $this->report_tables as $table )
    {
      if( is_array( $table['header'] ) )
      {
        $width = max(
          count( $table['header'] ),
          count( $table['contents'] ),
          count( $table['footer'] ) );
        if( $max < $width ) $max = $width;
      }
    }
    
    // add in the title(s)
    $row = 1;
    $max_col = 1 < $max ? chr( 64 + $max ) : false;
    $first = true;
    foreach( $this->report_titles as $title )
    {
      if( $first )
      {
        $this->report->set_size( 16 );
        $this->report->set_bold( true );
        $this->report->set_horizontal_alignment( 'center' );
      }
      else
      {
        $this->report->set_size( 14 );
        $this->report->set_bold( false );
      }
      
      if( $max_col ) $this->report->merge_cells( 'A'.$row.':'.$max_col.$row );
      $this->report->set_cell( 'A'.$row, $title );
      $row++;

      $first = false;
    }

    $this->report->set_size( NULL );
    
    // add in each table
    foreach( $this->report_tables as $table )
    {
      print '<h1>'.$table['title'].'</h1><br>';
      $width = max(
        count( $table['header'] ),
        count( $table['contents'] ),
        count( $table['footer'] ) );
      $max_col = 1 < $max ? chr( 64 + $width ) : false;

      // always skip a row before each table
      $row++;

      $this->report->set_horizontal_alignment( 'center' );
      $this->report->set_bold( true );

      // put in the table title
      if( !is_null( $table['title'] ) )
      {
        if( $max_col ) $this->report->merge_cells( 'A'.$row.':'.$max_col.$row );
        $this->report->set_cell( 'A'.$row, $table['title'] );
        $row++;
      }

      // put in the table header
      if( count( $table['header'] ) )
      {
        $this->report->set_background_color( 'CCCCCC' );
        $col = 'A';
        foreach( $table['header'] as $header )
        {
          $this->report->set_horizontal_alignment( 'A' == $col ? 'left' : 'center' );
          $this->report->set_cell( $col.$row, $header );
          $col++;
        }
        $row++;
      }

      $this->report->set_bold( false );
      $this->report->set_background_color( NULL );
      
      $first_content_row = $row;

      // put in the table contents
      unset( $contents_are_numeric );
      if( count( $table['contents'] ) )
      {
        foreach( $table['contents'] as $contents )
        {
          $col = 'A';
          $contents_are_numeric[$col] = false;
          foreach( $contents as $content )
          {
            $this->report->set_horizontal_alignment( 'A' == $col ? 'left' : 'center' );
            $this->report->set_cell( $col.$row, $content );
            $contents_are_numeric[$col] = $contents_are_numeric[$col] || is_numeric( $content );
            $col++;
          }
          $row++;
        }
      }
      $last_content_row = $row - 1;
      
      $this->report->set_bold( true );

      // put in the table footer
      if( count( $table['footer'] ) )
      {
        $col = 'A';
        foreach( $table['footer'] as $footer )
        {
          // the footer may be a function, convert if necessary
          if( preg_match( '/[0-9a-zA-Z_]+\(\)/', $footer ) )
          {
            if( $first_content_row == $last_content_row + 1 || !$contents_are_numeric[ $col ] )
            {
              $footer = 'N/A';
            }
            else
            {
              $coordinate = sprintf( '%s%s:%s%s',
                                     $col,
                                     $first_content_row,
                                     $col,
                                     $last_content_row );
              $footer = '='.preg_replace( '/\(\)/', '('.$coordinate.')', $footer );
            }
          }

          $this->report->set_horizontal_alignment( 'A' == $col ? 'left' : 'center' );
          $this->report->set_cell( $col.$row, $footer );
          $col++;
        }
        $row++;
      }
    }

    return $this->report->get_file( $this->get_argument( 'format' ) );
  }

  /**
   * An array of all titles to put in the report.
   * @var array $report_titles
   * @access private
   */
  private $report_titles = array();

  /**
   * An associative array of all reports to put in the report.
   * @var array $report_titles
   * @access private
   */
  private $report_tables = array();

  /**
   * An instance of the PHPExcel class used to create the report.
   * @var array $report_titles
   * @access private
   */
  protected $report;
}
?>
