<?php

class SimpleGallery extends Plugin
{
	function __get( $name )
	{
		switch ($name) {
			case 'base':
				$base = Options::get('simplegallery_base');
				if ( null == $base ) {
					$base = 'gallery';
				}
				return $base;
			default:
				return NULL;
		}
	}

	/**
	 * Do some checking and setting up.
	 */
	public function action_plugin_activation( $file )
	{
		// Don't bother loading if the gd library isn't active
		if ( !function_exists( 'imagecreatefromjpeg' ) ) {
			Session::error( _t( "Simple Gallery activation failed. PHP has not loaded the gd imaging library." ) );
			Plugins::deactivate_plugin( __FILE__ );
		}
		else {
			/*
			$this->silo = new HabariSilo();
			$this->silo->action_init();
			$this->silo->mkdir('simplegallery');
			*/
		}
	}

	/**
	 * Add the necessary template and create the silo to access files and directories
	 *
	 **/
	function action_init()
	{
		$this->add_template( 'simplegallery', dirname(__FILE__) . '/simplegallery.php' );
		$this->silo = new HabariSilo();
		$this->silo->action_init();
	}


	public function filter_rewrite_rules( $rules )
	{
		$rules[]= new RewriteRule( array(
			'name' => 'simplegallery',
			'parse_regex' => '%^' . $this->base . '(?:/?$|/(?P<gallerypath>.*))/?$%i',
			'build_str' => $this->base . '/({$gallerypath})',
			'handler' => 'PluginHandler',
			'action' => 'display_gallery',
			'priority' => 6,
			'is_active' => 1,
			'description' => 'Respond to requests for the simple gallery.',
			'parameters' => serialize( array( 'require_match' => array('SimpleGallery', 'rewrite_match_gallery') ) )
		) );

		return $rules;
	 }

	/**
	 * Check the requested gallery exists
	 * @param RewriteRule $rule The matched rewrite rule
	 * @param string The URL stub requested
	 * @param array $params Some stuff
	 * @todo Find a nicer way to assign thumbnails
	 **/
	public static function rewrite_match_gallery( $rule, $stub, $params )
	{
		// TODO It would be better to use the silo, but there's no way to check if a path is valid
		// $silo->get_dir() always returns at least an empty array, even for invalid paths
		$base = Site::get_dir('user') . '/files/simplegallery/';
		// Strip the base URL from the front of the stub, and add it to the base to get the full path.
		$sg = new SimpleGallery();
		$path = $base . substr($stub, strlen($sg->base));
		return file_exists($path);
	}

	public function action_plugin_act_display_gallery( $handler )
	{
		$gallery_path = $handler->handler_vars['gallerypath'];
		// Check if an image file is being requested, and if so return it
		$image = $this->silo->silo_get('simplegallery/' . $gallery_path);
		if ( $image && in_array($image->filetype, array('image_gif', 'image_png', 'image_jpeg') ) ) {
			header('Content-type: ' . $image->filetype);
			echo $image->content;
			exit;
		}

		// It must be a directory
		$assets = $this->silo->silo_dir('simplegallery/' . $gallery_path);

		$theme = $handler->theme;
		$theme->css = $this->get_url() . '/simplegallery.css';
		$dirs = array();
		$images = array();
		$thumbnails = array();

		if ( 0 != count($assets) ) {

			foreach ( $assets as $asset ) {
				// Need to decode twice to keep the /, because URL::get() callse RewriteRule::build() which urlencodes.
				$asset->url = urldecode(urldecode(
					URL::get( 'simplegallery', array( 'gallerypath' => $gallery_path . '/'. ($asset->title) ) )
				));
				$asset->pretty_title = $this->pretty_title($asset->title);
				if ( $asset->is_dir ) {
					$asset->thumbnail = null;
					$dirs[] = $asset;
				}
				else if ( in_array($asset->filetype, array('image_gif', 'image_png', 'image_jpeg') ) ) {
					if ( strpos($asset->title, 'thumbnail') === FALSE ) {
						$images[] = $asset;
					}
					else {
						$thumbnails[] = $asset;
					}
				}
			}

			// Assign manual thumbnails appropriately
			foreach ( $thumbnails as $thumbnail ) {
				$base = basename($thumbnail->title, pathinfo($thumbnail->title, PATHINFO_EXTENSION));
				foreach ( $dirs as $dir ) {
					if ( $dir->title . '.thumbnail.' == $base ) {
						$dir->thumbnail = $thumbnail;
						break;
					}
				}
			}

		}
		// Make a breadcrumb array
		// TODO Just horrible. There must be a nicer way to do this.
		$breadcrumbs = array($this->base);
		if ( '' != $gallery_path ) {
			$breadcrumbs = array_merge($breadcrumbs, explode('/', trim($this->pretty_title($gallery_path), '/')));
		}
		$theme->breadcrumbs = $breadcrumbs;
		$theme->title = $breadcrumbs[count($breadcrumbs) - 1];

		$theme->dirs = $dirs;
		$theme->images = $images;

		return $theme->display('simplegallery');

	}

	public function filter_plugin_config( $actions, $plugin_id )
	{
		if ( $plugin_id == $this->plugin_id() ) {
			$actions[] = _t( 'Configure' );
		}

		return $actions;
	}

	/**
	* Respond to the user selecting an action on the plugin page
	*
	* @param string $plugin_id The string id of the acted-upon plugin
	* @param string $action The action string supplied via the filter_plugin_config hook
	*/
	public function action_plugin_ui( $plugin_id, $action )
	{
		if ( $plugin_id == $this->plugin_id() ) {
			switch ( $action ) {
				case 'Configure' :
					$form = new FormUI( 'simplegallery' );

					$form->append( 'text', 'base', 'option:simplegallery_base', _t( 'Gallery location: ', 'simplegallery' ) . Site::get_url('habari') . '/' );
					$form->base->value = $this->base;
					$form->append( 'submit', 'submit', _t( 'Submit' ) );

					$form->out();
					break;
			}
		}
	}

	public function help()
	{
		return "The Simple Gallery plugin looks for directories and files in the <code>user/files/simplegallery</code> directory. You must create this directory with permissions that let the web server read it. You can then configure the location of the gallery on your site. You can associate a thumbnail to be displayed for a directory by naming it the same as the directory and adding '.thumbnail.jpg'. For exampe, you can use a thumbnail for a directory named 'Kittens' by naming it 'Kittens.thumbnail.jpg'. Note that png and gif images are also supported.";
	}

	private function pretty_title($title)
	{
		// Strip any extension
		$pretty_title = basename($title, '.' . pathinfo($title, PATHINFO_EXTENSION) );
		// Turn underscores or hyphens into spaces
		$pretty_title = strtr($pretty_title, '_-', '  ');
		return $pretty_title;
	}
}

?>
