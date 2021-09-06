<?php

require_once( 'base.class.php' );
require_once( 'log.class.php' );
require_once( 'markdown.php' );

/**
 * Generator CMS
 * Ian Leckie, Somewhere in 2013-2014
 * 
 * A Bare-Bones and opinionated CMS designed to turn a directory of markdown-formatted posts
 * into a directory of HTML files plus an index file of the most recent posts. And that's it.
 * 
 * Current Assumptions:
 * - for the default filename -> injected content feature to work, source files should contain at least one atx-style markdown header.
 *   the default settings assume - but don't require - that this is an h1 at the beginning of the file.
 * - safe mode is off
 * 
**/
class DeGenerator extends DeJenerator {}

class DeJenerator extends Base {

	/** Settable options & defaults */
	
	/**
	 * a date format string that PHP's DateTimeZone class accepts
	 * example of default: 201302281634
	 * @var string
	 */
	public $datestamp_format = 'YmdHi';
	
	/**
	 * a date format string that PHP's DateTimeZone class accepts
	 * example of default: January 2nd, 2013
	 * @var string
	 */
	public $date_output_format = 'F jS, Y';
	
	/**
	 * a timezone that PHP's DateTimeZone class accepts
	 * @var string
	 */
	public $timezone = 'CDT';
	
	/**
	 * a method of the class or a callable function
	 * used to format the filename of each source file to be injected into
	 * the post when use_source_filename_as_injected_content is true
	 * @var string
	 */
	public $injected_content_formatter = 'datestamp_to_h2';
	
	/**
	 * the PRCE regex used to find the injection position when injecting formatted filename into a post
	 * default is the regex markdown uses to find atx-style headers
	 * @var string
	 */
	protected $injected_content_regex = '{\#{1,6}[ ]*.+?[ ]*\#*\n+}';
	
	/**
	 * a method of the Generator class or a callable function
	 * used to sort the source files array before processing, affecting which posts end up on the index page
	 * @var string
	 */
	public $sort_function = 'arsort';
	
	/**
	 * directory within the target directory to use for temp files
	 * @var string
	 */
	protected $temp_directory = 'tmp';

	/**
	 * directory within the source directory to use for archive files
	 * @var string
	 */
	protected $archive_directory = 'archive';

	/**
	 * directory within the source directory to use for log files
	 * @var string
	 */
	protected $log_directory = 'log';

	/**
	 * the name of the target directory's index page
	 * @var string
	 */
	protected $index_name = 'index.html';

	/**
	 * the name of the error log file
	 * @var string
	 */
	protected $error_name = 'error.log';

	/**
	 * the name of the Savant template to use for each HTML block
	 * @var string
	 */
	protected $block_template = 'block.tpl.php';

	/**
	 * the name of the Savant template to use for each HTML block
	 * @var string
	 */
	protected $content_template = 'content.tpl.php';

	/**
	 * the name of the Savant template to use for the index page's HTML
	 * @var string
	 */
	protected $outer_template = 'outer.tpl.php';

	/**
	 * the name of the class to use for templating & display
	 * the class must implement the GeneratorAbstractDisplay interface
	 * @var string
	 */
	protected $display_class = 'GeneratorHTMLDisplay';
	

	/** Internal variables */
	
	public $source_path;  // directory to find markdown source files in.
	public $target_path;  // directory to output html files to.
	public $temp_path;    // directory to use for temporary files.
	public $archive_path; // directory to use for archive files.
	public $last_generated_date; // modification date of the index page, or 0 if there is no index
	public $index_page; // the full path to the site's index page

	public $additional_tpl_vars; // an array of additional variables to set on all savant templates
	
	protected $current_content_block = ''; // stores the current content block that the generator is working on
	public $display; // the display class

	/**
	 * @param string $source_directory used to set $this->source_path. either a full path or relative to the document root.
	 * @param string $target_directory used to set $this->target_path. either a full path or relative to the document root.
	 * @param string $temp_directory   optionally used to override $this->temp_directory
	 * @param string $log_directory    optionaly used to override $this->log_directory
	 */
	public function __construct( $source_directory, $target_directory, $additional_tpl_vars = null, $temp_directory = null, $archive_directory = null, $log_directory = null ) {
		
		parent::__construct();

		$this->additional_tpl_vars = is_array( $additional_tpl_vars ) ? $additional_tpl_vars : array();
		
		// Set paths
		$this->temp_directory    = is_null( $temp_directory )    ? $this->temp_directory    : $temp_directory;
		$this->archive_directory = is_null( $archive_directory ) ? $this->archive_directory : $archive_directory;
		$this->log_directory     = is_null( $log_directory )     ? $this->log_directory     : $log_directory;
		
		$this->set_path_info( $source_directory, $target_directory );

		// Setup error_log class
		$this->error_log = new Log( $this->make_full_path( $this->source_path . $this->log_directory ). $this->error_name ) ;

		$this->directory_filter = array_merge( $this->directory_filter, array( $this->temp_directory, $this->archive_directory, $this->log_directory ) );

		$this->index_page = $this->target_path . $this->index_name;
	
		// if there's an index page, set the last generated date to it's modification date. otherwise set it to 0 to get everything.
		$this->last_generated_date = ( file_exists( $this->index_page ) ) ? filemtime( $this->index_page ) : 0;

		// atempt to instantiate the display class, and check that it is an instance of the abstract display class
		if ( !$this->display = new $this->display_class( $this->index_name, $this->outer_template, $this->content_template, $this->target_path, $this->additional_tpl_vars ) OR !( $this->display instanceof GeneratorAbstractDisplay ) ) {

			throw new Exception( 'Error: invalid display class - ' . $this->display_class );

		}
	
	}

	public function check_for_updated_files() {

		$files_updated = false;
		
		// build arrays of source and target files
		// TODO: get_files_array method
		if ( ( $source_files_array = scandir( $this->source_path ) ) === false ) throw new Exception( 'Error: could not read from source directory at ' . $this->source_path );
		
		$source_files_array = array_values( array_diff( $source_files_array, $this->directory_filter ) );

		foreach( $source_files_array AS $key => $source_file ) {

			$files_updated = ( $files_updated || ( filemtime( $this->source_path . '/' . $source_file ) > $this->last_generated_date ) );

		}

		return $files_updated;

	}
	
	/**
	 * $collate_to_index	'all' or an integer. number of recent entries to collate into each static index page. 'all' has additional effect of skipping generation of individual output files per input file.
	**/
	public function generate_files( $collate_to_index = 'all', $use_source_filename_as_injected_content = true, $alt_target_directory = null ) {
		
		// alt target directory handling.
		if ( !is_null( $alt_target_directory ) ) {

			$original_target_path = $this->target_path;

			$this->set_path_info( $this->source_path, $alt_target_directory );

		}
		
		// build arrays of source and target files
		// TODO: get_files_array method
		if ( ( $source_files_array = scandir( $this->source_path ) ) === false ) throw new Exception( 'Error: could not read from source directory at ' . $this->source_path );
		if ( ( $target_files_array = scandir( $this->target_path ) ) === false ) throw new Exception( 'Error: could not read from target directory at ' . $this->target_path );

		$source_files_array = array_values( array_diff( $source_files_array, $this->directory_filter ) );
		$target_files_array = array_values( array_diff( $target_files_array, $this->directory_filter ) );

		// set array keys to the basenames so we can use that for comparison
		foreach ( $source_files_array AS $key => $val ) {
			$source_files_array[$this->strip_file_extension($val)] = $val;
			unset( $source_files_array[$key] );
		}
		
		foreach ( $target_files_array AS $key => $val ) {
			$target_files_array[$this->strip_file_extension($val)] = $val;
			unset( $target_files_array[$key] );
		}

		// build array of html files that aren't in the source directory and need to be deleted from the target directory
		$files_to_delete = array_values( array_diff_key( $target_files_array, $source_files_array ) );
		
		// run source files array through sort function
		$this->function_method_adaptor( $this->sort_function, array( &$source_files_array ) );
		
		// generate html files in temp dir for files needed for index & any others that have last modified dates since the last generated date
		// possible future optimization: use existing non-temp files to build index instead of rebuilding all files needed for the index each time
		// limit defaults to 0 for non-integer values other than 'all' so nothing happens
		// TODO: generate_blocks & generate_indexes methods:
		// $html_blocks_array = $this->generate_blocks( $source_files_array, &$new_files );
		// $this->generate_indexes( $collate_to_index ); -> index-1.html, index-2.html...
		$limit     = ( $collate_to_index === 'all' ) ? count( $source_files_array ) : ( is_int( $collate_to_index ) ? $collate_to_index : 0 );
		$new_files = false;
		$index_html_blocks = '';
		$source_files_data = array();

		$current_index = 0;
		
		foreach( $source_files_array AS $source_file ) {

			$current_index++;
			
			$source_basename = basename( $source_file );

			$source_files_data[str_replace( '.md', '', $source_basename )] = str_replace( array( '.md', '-', '_' ), array( '', ' ', ' ' ), $source_basename );
			
			$source_path = $this->source_path . $source_file;
			
			$file_updated = filemtime( $source_path ) > $this->last_generated_date;
			
			if ( $current_index < $limit OR $file_updated ) {
			
				if ( ( $this->current_content_block = file_get_contents( $source_path ) ) === false ) {

					throw new Exception( 'Error: could not read source file at ' . $source_path );

				}
				
				// optional ability to set a function to use to process the source filename and insert the result into the source based on a regex
				if ( $use_source_filename_as_injected_content === true ) {

					// format the filename using $this->injected_content_formatter
					$content_to_inject = $this->function_method_adaptor( $this->injected_content_formatter, $source_basename );

					$this->inject_content( $content_to_inject );
					
				}

				$this->display->additional_tpl_vars['id'] = str_replace( '.md', '', $source_basename );
				
				$html = $this->display->fetch_template( $this->block_template, $this->current_content_block_to_html() );
		
				// collate appropriate files into index
				if ( $current_index < $limit ) {

					$index_html_blocks .= $html;
					
				}
				
				// if this file isn't new or updated, we only need to append it to the index
				if ( $file_updated ) {
					
					$new_files = true;

					$source_file_info = pathinfo( $source_basename );
			
					$output_path = $this->temp_path . basename( $source_basename, '.' . $source_file_info['extension'] ) . '.html';
				
					$this->write_to_file( $output_path, $html );
					
				}
				
			}
				
		}

		// if nothing new was found, we don't need to do anything else
		if ( $new_files === true ) {
		
			// save index in temp directory if it's been updated
			if ( !empty( $index_html_blocks ) ) {

				$this->display->additional_tpl_vars['toc'] = $source_files_data;

				$content_html = $this->display->fetch_template( $this->content_template, $index_html_blocks );

				$index_html = $this->display->fetch_template( $this->outer_template, $content_html );

				$this->write_to_file( $this->temp_path . $this->index_name, $index_html );

			}
		
			// delete files that need to be deleted from target directory
			$this->delete_files( $this->target_path, $files_to_delete );
		
			// move all files from temp to target directory
			$this->move_temp_files();
			
		}
		
		// reset target directory from alt
		if ( !is_null( $alt_target_directory ) ) {

			$this->set_path_info( $this->source_path, $original_target_path );

		}
		
		// TODO: log success
		
		return true;
		
	}
	
	/**
	 * Moves files from the temp directory to the target directory, overwriting any that already exist
	 *
	 * @return null
	 */
	protected function move_temp_files() {

		return $this->move_file( $this->temp_path . '* ', $this->target_path );
		
	}
	
	/**
	 * Returns Markdown's HTML output of $this->current_content_block
	 * 
	 * @return string         the HTML-formatted output
	 */
	protected function current_content_block_to_html() {

		return Markdown( $this->current_content_block );
	
	}

	/**
	 * Injects a markdown-formatted string into $this->current_content_block
	 * directly after the first match made by $this->injected_content_regex
	 * 
	 * @param  string $content a markdown-formatted content string
	 *
	 * @return null
	 */
	protected function inject_content( $content ) {

		// find the injection position in the source file contents
		preg_match( $this->injected_content_regex , $this->current_content_block, $matches, PREG_OFFSET_CAPTURE );

		if ( !empty( $matches ) ) {

			// find the offset of the end of the first header
			$header_offset  = $matches[0][1] + strlen( $matches[0][0] );

			// inject the formatted content into the source file contents
			$this->current_content_block = substr( $this->current_content_block, 0, $header_offset ) . $content . substr( $this->current_content_block, $header_offset );

		}

	}
	
	/**
	 * Sets the source path, target path and temp path, and attempts to create a directory at temp path if it doesn't exist
	 * 
	 * @param  string $source_directory the source directory. either a full path or a path relative to the document root.
	 * @param  string $target_directory the target directiry. either a full path or a path relative to the document root.
	 *
	 * @return null
	 */
	private function set_path_info( $source_directory, $target_directory ) {
		
		$this->source_path  = $this->make_full_path( $source_directory );
		$this->target_path  = $this->make_full_path( $target_directory );
		$this->temp_path    = $this->make_full_path( $this->target_path . $this->temp_directory );
		$this->archive_path = $this->make_full_path( $this->source_path . $this->archive_directory );
		
		// attempt to create temp directory if it doesn't exist
		if ( !$this->make_directory( $this->temp_path ) ) throw new Exception( 'Error: could not create temp directory at ' . $this->temp_path );
		
		// attempt to create archive directory if it doesn't exist
		if ( !$this->make_directory( $this->archive_path ) ) throw new Exception( 'Error: could not create archive directory at ' . $this->archive_path );
	
	}
	
	/**
	 * Turns a datestamp into a markdown-formatted h2
	 * 
	 * @param  string $datestamp the datestamp
	 * 
	 * @return string            a markdown-formatted string
	 */
	protected function datestamp_to_h2( $file_name, $exclude_markdown = false ) {

		$datestamp = $this->strip_file_extension( $file_name );
	
		$date = DateTime::createFromFormat( $this->datestamp_format, $datestamp, new DateTimeZone( $this->timezone ) );

		$return_val = $date->format( $this->date_output_format );

		if ( $exclude_markdown === false ) {

			$return_val = '<h2>' . $return_val . '</h2>' . PHP_EOL . PHP_EOL;

		}
		
		return $return_val;
		
	}

}

interface GeneratorAbstractDisplay {
	
	public function __construct( $index_name, $outer_template, $content_template, $target_path, $additional_tpl_vars );

	public function filename_to_url( $filename );

	public function fetch_template( $template, $content );

}

class GeneratorHTMLDisplay extends Base implements GeneratorAbstractDisplay {

	// TODO: implement logging for 404s

	private $index_name;
	public $outer_template;
	private $content_template;
	private $target_path;
	public $additional_tpl_vars;

	public function __construct( $index_name, $outer_template, $content_template, $target_path, $additional_tpl_vars ) {

		parent::__construct();
		
		$this->index_name          = $index_name;
		$this->outer_template      = $outer_template;
		$this->content_template    = $content_template;
		$this->target_path         = $target_path;
		$this->additional_tpl_vars = $additional_tpl_vars;

	}

	// if the request url is '/target_directory/20131015/test-url', $url will be '20131015/test-url'
	public function fetch_by_url( $url, $wrap_in_outer_template = true ) {

		$return_val = false;

		// if $url is empty, fetch the index
		$is_index = preg_match( '{^index-([0-9]+)$}', $url, $matches );

		if ( $is_index === 1 OR empty( $url ) ) {

			$page_number = empty( $matches[1] ) ? 1 : $matches[1]; 

			$return_val = $this->fetch_index( $page_number );

		} else {

			$filename = $this->url_to_filename( $url );

			$contents = $this->fetch_file( $filename );

			if ( $wrap_in_outer_template === true && $contents !== false ) {

				$content_html = $this->fetch_template( $this->content_template, $this->fetch_file( $filename ) );

				$return_val = $this->fetch_template( $this->outer_template, $content_html );

			} else {

				$return_val = $contents;

			}

			// $return_val = ( $wrap_in_outer_template === true && $contents !== false ) ? $this->fetch_template( $this->outer_template, $this->fetch_file( $filename ) ) : $contents;

		} 

		if ( $return_val === false ) {

			$this->throw_404();

		}

		return $return_val;

	}

	// turn '20131015/test-url' into '20131015_test-url.html', etc.
	public function url_to_filename( $url ) {

		return preg_replace( '{/}', '_', $url, 1 ) . '.html';
	
	}

	// turn '20131015_test-url.html' into '20131015/test-url', etc.
	public function filename_to_url( $filename ) {

		return preg_replace( '{_}', '/', str_replace( '.html', '', $url ), 1 );

	}

	protected function fetch_index( $page_number ) {

		// setting page_number to 0 will cause the fetch call to return false, throwing a 404
		$page_number = is_int( $page_number ) ? $page_number : 0;

		if ( $page_number == 1  ) {

			$index_name = 'index.html';

		} else {
		
			$index_name = implode( '-' . $page_number . '.', explode( '.', $this->index_name ) );

		}

		return $this->fetch_file( $index_name );

	}

	protected function fetch_file( $filename ) {

		$filename = basename( $filename );

		return @file_get_contents( $this->target_path . $filename );

	}

	/**
	 * Fetches the HTML output of a given template and content string
	 * 
	 * @param  string $template the markdown template to use
	 * @param  string $content  the content string
	 * 
	 * @return string           the HTML-formatted output of the template
	 */
	public function fetch_template( $template, $content ) {
	
		foreach ( $this->additional_tpl_vars AS $var_name => $value ) {

			$this->templater->$var_name = $value;

		}

		return parent::fetch_template( $template, $content );
	
	}

}

?>