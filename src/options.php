<?php
/**
 * BetterImageLoading Options class
 * @link              http://wp.mooschmedia.com/plugins/better-image-loading/
 * @since             0.3.4
 * @package           BetterImageLoading
 */

if(!class_exists('BIL_Options')) {
  class BIL_Options {
		private $sections;
		private $settings;
    private $checkboxes;

		public function __construct() {
			$this->checkboxes = array();
      $this->settings = array();
			$this->get_settings();

			$this->sections['options']			= __( 'General' );
			$this->sections['styling']			= __( 'Styling' );

			add_action( 'admin_menu', array( &$this, 'add_pages' ) );
			add_action( 'admin_init', array( &$this, 'register_settings' ) );

			if ( ! get_option( 'bil_options' ) )
				$this->initialize_settings();

		}

		public function add_pages() {
			$admin_page = add_theme_page( __( 'BIL Options' ), __( 'BIL Options' ), 'manage_options', 'bil-options', array( &$this, 'display_page' ) );
		}

		public function create_setting( $args = array() ) {
			$defaults = array(
				'id'      => 'default_field',
				'title'   => __( '' ),
				'desc'    => __( '' ),
				'std'     => '',
				'type'    => 'text',
				'section' => 'general',
				'choices' => array(),
				'class'   => ''
			);

			extract( wp_parse_args( $args, $defaults ) );

			$field_args = array(
				'type'      => $type,
				'id'        => $id,
				'desc'      => $desc,
				'std'       => $std,
				'choices'   => $choices,
				'label_for' => $id,
				'class'     => $class
			);

			if ( $type == 'checkbox' )
				$this->checkboxes[] = $id;

			add_settings_field( $id, $title, array( $this, 'display_setting' ), 'bil-options', $section, $field_args );
		}

		public function display_page() {
			if (!current_user_can('manage_options'))
				wp_die( __('You do not have sufficient permissions to access this page.') );


			echo '<div class="wrap">
		<div class="icon32" id="icon-options-general"></div>
		<h2>' . __( 'Theme Options', 'collective' ) . '</h2>';

			echo '<style>
				.postbox h2 {display:none;}
				.sortable-item {
					cursor:move;
					padding:10px;
					background-color:#fff;
					margin:2px 0;
					box-shadow: 0px 0px 1px rgba(0,0,0,0.2);
					border: 1px solid #ddd;
				}
				.sortable-item:first-child .remove-sortable { display:none; }
			</style>';

			if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] == true )
				echo '<br/><div id="message" class="updated below-h2"><p>BIL options updated.</p></div>';

			echo '<form action="options.php" method="post">
					<div id="post-body" class="metabox-holder columns-2">
						<div id="postbox-container-2" class="settings-panels">
							<div id="normal-sortables" class="meta-box-sortables ui-sortable">';
								settings_fields( 'bil_options' );
								do_settings_sections( $_GET['page'] );
				echo '</div>
						</div>
					</div>
					<div style="float:left;width:100%;"><p><input name="Submit" type="submit" class="button-primary" value="' . __( 'Save Changes' ) . '" /></p></div>
				</form>
			</div>';

		echo '<style>
		.position-container {width:150px;}
		.position-container .position {float: left; border: solid 1px #333; padding: 8px 12px; margin: 1px;}
		.position-container .position:last-child {width: 92px; text-align: center;}

		/* select, option {-webkit-appearance: none;} */
		select {
			color: #586970;
			height: 32px;
			padding: 7px;
			width: 260px;
			background: #fff;
			position: relative;
			z-index: 10;
			cursor: pointer;
		}</style>';

		}

		/*====================================================
		 * Description for section
		 ====================================================*/
		public function display_section( $args ) {
			// code
			// echo '<pre>'.print_r($args, true).'</pre>';

			echo '<h3>'.$args['title'].'</h3>';

			// echo '<div class="settings-section">';

			// echo '</div>';
			// Note the ID and the name attribute of the element should match that of the ID in the call to add_settings_field
			// $html = '<input type="checkbox" id="show_header" name="show_header" value="1" ' . checked(1, get_option('show_header'), false) . '/>';

			// // Here, we will take the first argument of the array and add it to a label next to the checkbox
			// $html .= '<label for="show_header"> '  . $args[0] . '</label>';

			// echo $html;
		}

		/*====================================================
		 * Description for About section
		 ====================================================*/
		public function display_about_section() {

			// This displays on the "About" tab. Echo regular HTML here, like so:
			// echo '<p>Copyright 2011 me@example.com</p>';

		}

		/*====================================================
		 * HTML output for text field
		 ====================================================*/
		public function display_setting( $args = array() ) {

			extract( $args );

			$options = get_option( 'bil_options' );

			if ( ! isset( $options[$id] ) && $type != 'checkbox' )
				$options[$id] = $std;
			elseif ( ! isset( $options[$id] ) )
				$options[$id] = 0;

			$field_class = '';
			if ( $class != '' )
				$field_class = ' ' . $class;

			switch ( $type ) {
				case 'heading':
					echo '</td></tr><tr valign="top"><td colspan="2"><h4>' . $desc . '</h4>';
					break;

				case 'checkbox':
					echo '<input class="checkbox' . $field_class . '" type="checkbox" id="' . $id . '" name="bil_options[' . $id . ']" value="1" ' . checked( $options[$id], 1, false ) . ' /> <label for="' . $id . '">' . $desc . '</label>';
					break;

				case 'select':
					echo '<select class="select' . $field_class . '" name="bil_options[' . $id . ']">';

					foreach ( $choices as $value => $label )
						echo '<option value="' . esc_attr( $value ) . '"' . selected( $options[$id], $value, false ) . '>' . $label . '</option>';

					echo '</select>';

					if ( $desc != '' )
						echo '<br /><span class="description">' . $desc . '</span>';

					break;

				case 'radio':
					$i = 0;
					foreach ( $choices as $value => $label ) {
						echo '<input class="radio' . $field_class . '" type="radio" name="bil_options[' . $id . ']" id="' . $id . $i . '" value="' . esc_attr( $value ) . '" ' . checked( $options[$id], $value, false ) . '> <label for="' . $id . $i . '">' . $label . '</label>';
						if ( $i < count( $options ) - 1 )
							echo '<br />';
						$i++;
					}

					if ( $desc != '' )
						echo '<br /><span class="description">' . $desc . '</span>';

					break;

				case 'text':
				default:
			 		echo '<input class="regular-text' . $field_class . '" type="text" id="' . $id . '" name="bil_options[' . $id . ']" placeholder="' . $std . '" value="' . esc_attr( $options[$id] ) . '" />';

			 		if ( $desc != '' )
			 			echo '<br /><span class="description">' . $desc . '</span>';

			 		break;

			 	case 'link':
			 		$pages = get_posts( array( 'posts_per_page' => -1, 'post_type' => 'page' ) );
			 		$posts = get_posts( array( 'posts_per_page' => -1, 'post_type' => 'post' ) );
			 		$products = get_posts( array( 'posts_per_page' => -1, 'post_type' => 'product' ) );
			 		echo '<select class="select' . $field_class . '" name="bil_options[' . $id . ']">';

						echo '<option value="null">Select link</option>';

						echo '<option value="null">--- Pages ---</option>';
						foreach ( $pages as $page )
							echo '<option value="' . $page->ID . '"' . selected( $options[$id], $page->ID, false ) . '>' . $page->post_title . '</option>';

						echo '<option value="null">--- Posts ---</option>';
						foreach ( $posts as $post )
							echo '<option value="' . $post->ID . '"' . selected( $options[$id], $post->ID, false ) . '>' . $post->post_title . '</option>';

						echo '<option value="null">--- Products ---</option>';
						foreach ( $products as $product )
							echo '<option value="' . $product->ID . '"' . selected( $options[$id], $product->ID, false ) . '>' . $product->post_title . '</option>';

					echo '</select>';

			 		if ( $desc != '' )
			 			echo '<br /><span class="description">' . $desc . '</span>';

			 		break;

			 	case 'textsmall':
				default:
			 		echo '<input style="width:100px;" class="regular-text' . $field_class . '" type="text" id="' . $id . '" name="bil_options[' . $id . ']" placeholder="' . $std . '" value="' . esc_attr( $options[$id] ) . '" />';

			 		if ( $desc != '' )
			 			echo '<br /><span class="description">' . $desc . '</span>';

			 	break;

			 	case 'wysiwyg':
				default:
					// wp_editor('', $id, $options['settings']);
					wp_editor( $options[$id], $id, array('media_buttons'=>false, 'textarea_name'=>'bil_options[' . $id . ']','editor_height'=>100) );

			 		// echo '<input class="regular-text' . $field_class . '" type="text" id="' . $id . '" name="bil_options[' . $id . ']" placeholder="' . $std . '" value="' . esc_attr( $options[$id] ) . '" />';

			 		if ( $desc != '' )
			 			echo '<br /><span class="description">' . $desc . '</span>';

			 		break;

				default:
					echo '</tr><tr><td colspan="2" style="border-bottom:solid 1px #ddd;"></td></tr>';
				break;

			}

		}

		/*====================================================
		 * Settings and defaults
		 ====================================================*/
		public function get_settings() {

			$theme_dir = get_bloginfo('template_directory');
			$pages = get_pages();
			$posts = get_posts(array('post_type' => 'event'));
			// $posts = get_posts(array(
			// 	'posts_per_page'  => 5,
			// 	'numberposts'     => 5,
			// 	'offset'          => 0,
			// 	'category'        => '',
			// 	'orderby'         => 'post_date',
			// 	'order'           => 'DESC',
			// 	'include'         => '',
			// 	'exclude'         => '',
			// 	'meta_key'        => '',
			// 	'meta_value'      => '',
			// 	'post_type'       => 'post',
			// 	'post_mime_type'  => '',
			// 	'post_parent'     => '',
			// 	'post_status'     => 'publish',
			// 	'suppress_filters' => true ));

			/* General
			===========================================*/
			$this->settings['contact_phone'] = array(
				'title' 	=> __( 'Contact Telephone' ),
				'desc' 		=> __( '' ),
				'type' 		=> 'text',
				'std'		=> '',
				'section' 	=> 'options'
			);
			$this->settings['contact_email'] = array(
				'title' 	=> __( 'Contact Email Address' ),
				'desc' 		=> __( '' ),
				'type' 		=> 'text',
				'std'		=> '',
				'section' 	=> 'options'
			);
			$this->settings['tiwtter'] = array(
				'title' 	=> __( 'Twitter handle' ),
				'desc' 		=> __( 'e.g username (exclude \'@\')' ),
				'type' 		=> 'text',
				'std'		=> '',
				'section' 	=> 'options'
			);
			$this->settings['linkedin'] = array(
				'title' 	=> __( 'LinkedIn link' ),
				'desc' 		=> __( '' ),
				'type' 		=> 'text',
				'std'		=> '',
				'section' 	=> 'options'
			);
			$this->settings['gplus'] = array(
				'title' 	=> __( 'Google Plus link' ),
				'desc' 		=> __( '' ),
				'type' 		=> 'text',
				'std'		=> '',
				'section' 	=> 'options'
			);
			$this->settings['blab'] = array(
				'title' 	=> __( 'Blab link' ),
				'desc' 		=> __( '' ),
				'type' 		=> 'text',
				'std'		=> '',
				'section' 	=> 'options'
			);
			$this->settings['periscope'] = array(
				'title' 	=> __( 'Periscope link' ),
				'desc' 		=> __( '' ),
				'type' 		=> 'text',
				'std'		=> '',
				'section' 	=> 'options'
			);
		}

		/*====================================================
		 * Initialize settings to their default values
		 ====================================================*/
		public function initialize_settings() {

			$default_settings = array();
			foreach ( $this->settings as $id => $setting ) {
				if ( $setting['type'] != 'heading' ){
					if( $setting['std'] ){
						$default_settings[$id] = $setting['std'];
					} else {
						$default_settings[$id] = '';
					}
				}
			}

			update_option( 'bil_options', $default_settings );

		}

		/*====================================================
		* Register settings
		====================================================*/
		public function register_settings() {

			register_setting( 'bil_options', 'bil_options', array ( &$this, 'validate_settings' ) );

			foreach ( $this->sections as $slug => $title )
				add_settings_section( $slug, $title, array( &$this, 'display_section' ), 'bil-options' );

			$this->get_settings();

			foreach ( $this->settings as $id => $setting ) {
				$setting['id'] = $id;
				$this->create_setting( $setting );
			}

		}

		/*====================================================
		* jQuery Tabs
		====================================================*/
		public function scripts() {
			// wp_print_scripts( 'jquery-ui-tabs' );
			// wp_enqueue_script('admin-scripts');
		}

		public function validate_settings( $input ) {

			if ( ! isset( $input['reset_theme'] ) ) {
				$options = get_option( 'bil_options' );

				foreach ( $this->checkboxes as $id ) {
					if ( isset( $options[$id] ) && ! isset( $input[$id] ) )
						unset( $options[$id] );
				}

				return $input;
			}
			return false;

		}

	}
}
