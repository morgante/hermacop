<?php

class Hermacop extends Plugin
{

	/**
	 * Register content type
	 */
	public function action_plugin_activation( $plugin_file )
	{
		self::install();
	}
	
	/**
	 * install various stuff we need
	 */
	static public function install() 
	{
		Post::add_new_type( 'redirect' );

		// Give anonymous users access
		$group = UserGroup::get_by_name( 'anonymous' );
		$group->grant( 'post_redirect', 'read' );
	}

	public function action_plugin_deactivation( $plugin_file )
	{
		Post::deactivate_post_type( 'redirect' );
	}

	/**
	 * Create name string
	 */
	public function filter_post_type_display( $type, $foruse )
	{
		$names = array(
			'redirect' => array(
				'singular' => _t( 'Redirect' ),
				'plural' => _t( 'Redirects' ),
			)
		);
	 		return isset( $names[$type][$foruse] ) ? $names[$type][$foruse] : $type;
	}

	/**
	 * Modify publish form
	 */
	public function action_form_publish( $form, $post )
	{
		if ( $post->content_type == Post::type( 'redirect' ) ) {
			// Utils::debug( $form );
			$form->title->caption = _t( 'Location to redirect from (ex. from/here for http://yourblog.com/from/here )' );
			$form->content->caption = _t( 'URL to redirect to' );
			$form->content->template = 'admincontrol_text';
			$form->content->class = array();
		}
	}

	/**
	 * Redirect a link to it proper
	 */
	public function action_plugin_act_redirect( $handler )
	{
		$rule = URL::get_matched_rule();
		
		$to = $rule->build_str;
		
		Utils::redirect( $to );
	}

	public static function get_rules()
	{
		$rules = array();
		
		$posts = Posts::get( array( 'content_type' => Post::type('redirect'), 'ignore_permissions' => true ) );
		
		foreach( $posts as $post )
		{
			$rules[$post->slug] = array(
				'from' => $post->title,
				'to' => $post->content
			);
		}
		
		return $rules;
	}
	
	/**
	 * Add needed rewrite rules
	 */
	public function filter_rewrite_rules( $rules )
	{
		$redirects = self::get_rules();
				
		foreach( $redirects as $slug => $redirect )
		{
			$rules[] = new RewriteRule( array(
				'name' => $slug,
				'parse_regex' => '%' . $redirect['from'] . '/?$%i',
				'build_str' => $redirect['to'],
				'handler' => 'PluginHandler',
				'action' => 'redirect',
				'priority' => 7,
				'is_active' => 1,
				'description' => 'Redirect for ' . $slug,
			) );
		}
						
		return $rules;
	}

}

?>