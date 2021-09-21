<?php

namespace App\Statik;

class Statik
{
	/**
	 * The Markdown files to process
	 * 
	 * @var array
	 */
	protected $markdown_files = array();

	/**
	 * The parsedown class
	 * 
	 * @var object
	 */
	protected $parsedown;

	/**
	 * The mustache class
	 * 
	 * @var object
	 */
	protected $mustache;

	/**
	 * The Command class that is calling generateHTMLFiles
	 * 
	 * @var object
	 */
	protected $command;

	/**
	 * The source directory path
	 * 
	 * @var string
	 */
	protected $source_path;

	/**
	 * The target directory path
	 * 
	 * @var string
	 */
	protected $target_path;

	/**
	 * The template file path
	 * 
	 * @var string
	 */
	protected $template;

	/**
	 * Instantiate the Parsedown and Mustache objects
	 * 
	 * @return void
	 */
	public function __construct() {

		$this->parsedown = new \Parsedown;
		$this->mustache  = new \Mustache_Engine;

	}
	
	/**
	 * Check for errors and then generate an HTML file in the target directory
	 * for each Markdown file in the source directory
	 * 
	 * @return bool
	 */
	public function generateHTMLFiles( object $command, string $source_path, string $target_path, string $template = '' ) {

		$this->command     = $command;
		$this->source_path = $source_path;
		$this->target_path = $target_path;
		$this->template    = $template;

		$this->command->info( "\e[1;32mGenerating HTML files..." );

		return (bool) $this->checkPaths() && $this->checkTemplateFile() && $this->getSourceFiles() && $this->resetTargetDir() && $this->makeHTMLFiles();
		
	}

	/**
	 * Ensure that both the source and target paths exist
	 * 
	 * @return bool
	 */
	protected function checkPaths() {
		
		return (bool) $this->checkPath( $this->source_path ) && $this->checkPath( $this->target_path );
	
	}

	/**
	 * Ensure that a path exists and send the error to the console if not
	 * 
	 * @return bool
	 */
	protected function checkPath( string $path ) {

		$success = true;

		if ( !\File::exists( $path ) ) {
			
			$success = false;
			
			$this->command->error( "ERROR: Can't access path '" . $path . "'" );
		
		}

		return (bool) $success;

	}

	/**
	 * If $this->template is set, make sure the file exists and send the template
	 * name or the error to the console
	 * 
	 * @return bool
	 */
	public function checkTemplateFile() {

		$success = true;

		if ( $this->template != '' ) {

			if ( !\File::exists( $this->template ) ) {
				
				$success = false;
				
				$this->command->error( "ERROR: Can't access template file '" . $this->template . "'" );
			
			} else {
				
				$this->command->info( "\e[0;35mUsing template: \e[0;37m" . $this->template . "\e[0m" );
			
			}

		}

		return (bool) $success;
	}

	/**
	 * Get all of the files in the source directory
	 * 
	 * @return bool
	 */
	protected function getSourceFiles() {
		
		return (bool) ( $this->markdown_files = \File::allFiles( $this->source_path ) );
	
	}

	/**
	 * Delete and re-make the target directory
	 * 
	 * @return bool
	 */
	protected function resetTargetDir() {
		
		return (bool) \File::deleteDirectory( $this->target_path ) && \File::makeDirectory( $this->target_path );
	
	}

	/**
	 * Loop through the source files and make an HTML file from any that end
	 * with the Markdown extension set in the config
	 * 
	 * @return bool
	 */
	protected function makeHTMLFiles() {

		foreach ( $this->markdown_files as $file ) {

			if ( str_ends_with( $file, config('statik')['markdown_extension'] ) && !$this->makeHTMLFile( $file ) ) return (bool) false;

		}

		return (bool) true;

	}

	/**
	 * Create an HTML file from a Markdown file, optionally wrap it with the
	 * provided template, save it to the target directory and send the target
	 * path to the console
	 * 
	 * @return bool
	 */
	protected function makeHTMLFile( object $file ) {

		$success = false;

		$html = $this->parsedown->text( \File::get( $file ) );

		if ( $this->template ) $html = $this->mustache->render( \File::get( $this->template ), array( 'content' => $html ) );

		$out_file = $this->sourceFileToTargetFile( $file );

		if ( $this->createTargetDirectory( $out_file ) && \File::put( $out_file, $html ) ) {

			$this->command->info( "\e[0;36mWriting file: \e[0;97m" . $out_file );

			$success = true;
		
		}

		return (bool) $success;

	}

	/**
	 * Create a new directory inside the target directory
	 * 
	 * @return bool
	 */
	protected function createTargetDirectory( string $path ) {

		$out_dir  = pathinfo( $path )['dirname'];

		return (bool) \File::exists( $out_dir ) || \File::makeDirectory( $out_dir, 0777, true );
	
	}

	/**
	 * Translates a path in the source directory to a path in the target
	 * directory, including changing the extension to .html
	 * 
	 * @return string the translated path
	 */
	protected function sourceFileToTargetFile( object $file ) {
		
		return (string) str_replace( config('statik')['markdown_extension'], '.html', str_replace( $this->source_path, $this->target_path, $file ) );
	
	}
}
