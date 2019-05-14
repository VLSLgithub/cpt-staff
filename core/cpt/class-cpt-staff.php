<?php
/**
 * Class CPT_Staff_CPT
 *
 * Creates the post type.
 *
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class CPT_Staff_CPT extends RBM_CPT {

	public $post_type = 'staff';
	public $label_singular = null;
	public $label_plural = null;
	public $labels = array();
	public $icon = 'groups';
	public $p2p = 'facility';
	public $post_args = array(
		'hierarchical' => false,
		'supports' => array( 'title', 'editor', 'author', 'thumbnail' ),
		'has_archive' => true,
		'rewrite' => array(
			'slug' => 'staff',
			'with_front' => false,
			'feeds' => true,
			'pages' => true
		),
		'menu_position' => 11,
		'capability_type' => 'post',
	);

	/**
	 * CPT_Staff_CPT constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {

		// This allows us to Localize the Labels
		$this->label_singular = __( 'Staff', 'cpt-staff' );
		$this->label_plural = __( 'Staff', 'cpt-staff' );

		$this->labels = array(
			'menu_name' => __( 'Staff', 'cpt-staff' ),
			'all_items' => __( 'All Staff', 'cpt-staff' ),
		);

		parent::__construct();

		add_filter( 'rbm_cpts_p2p_select_args', array( $this, 'p2p_select_args' ), 10, 3 );

		add_action( 'init', array( $this, 'register_taxonomy' ) );

		add_action( 'init', array( $this, 'add_rewrite_rules' ) );

		add_filter( 'query_vars', array( $this, 'add_query_var' ) );

		add_action( 'pre_get_posts', array( $this, 'modify_archive_query' ) );

		// This isn't actually needed, but just in case I need it later I'm going to leave it here
		//add_action( 'pre_get_posts', array( $this, 'modify_single_query' ) );

		add_filter( 'template_include', array( $this, 'disable_default_staff_archive' ) );

		add_filter( 'the_permalink', array( $this, 'the_permalink' ) );	
		add_filter( 'post_type_link', array( $this, 'get_permalink' ), 10, 4 );

		add_filter( 'template_include', array( $this, 'redirect_to_permastruct' ) );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		
		add_action( 'after_setup_theme', function() {
		
			//add_action( 'do_meta_boxes', array( $this, 'remove_meta_boxes' ) );
			
		} );

		add_filter( 'manage_' . $this->post_type . '_posts_columns', array( $this, 'admin_column_add' ) );

		add_action( 'manage_' . $this->post_type . '_posts_custom_column', array( $this, 'admin_column_display' ), 10, 2 );

		add_filter( 'the_title', array( $this, 'the_title' ), 10, 2 );

	}

	/**
	 * Make our Relationships show Multiple results
	 *
	 * @param   array  $args          Field Args
	 * @param   string $post_type     Child Post Type
	 * @param   string $relationship  Parent Post Type
	 *
	 * @access	public
	 * @since	{{VERSION}}
	 * @return  array                 Field Args
	 */
	public function p2p_select_args( $args, $post_type, $relationship ) {

		if ( $post_type !== 'staff' || 
		$relationship !== 'facility' ) return $args;

		// Remove the default since it is being auto-selected and we don't want that
		$index = array_search( '- None -', $args['options'] );
		unset( $args['options'][ $index ] );

		$args['multiple'] = true;

		return $args;

	}

	public function register_taxonomy() {

		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name'              => __( 'Positions', 'cpt-staff' ),
			'singular_name'     => __( 'Position', 'cpt-staff' ),
			'search_items'      => __( 'Search Positions', 'cpt-staff' ),
			'all_items'         => __( 'All Positions', 'cpt-staff' ),
			'parent_item'       => __( 'Parent Position', 'cpt-staff' ),
			'parent_item_colon' => __( 'Parent Position:', 'cpt-staff' ),
			'edit_item'         => __( 'Edit Position', 'cpt-staff' ),
			'update_item'       => __( 'Update Position', 'cpt-staff' ),
			'add_new_item'      => __( 'Add New Position', 'cpt-staff' ),
			'new_item_name'     => __( 'New Position Name', 'cpt-staff' ),
			'menu_name'         => __( 'Position', 'cpt-staff' ),
		);

		$args = array(
			'hierarchical'      => true,
			'public'			=> true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'position' ),
			'description' => apply_filters( 'vibrant_life_staff_position_description', 'i.i.' ),
		);

		register_taxonomy( 'position', array( 'staff' ), $args );

		if ( ! term_exists( 'executive-director', 'position' ) ) {
			$test = wp_insert_term(
				__( 'Executive Director', 'cpt-staff' ),
				'position'
			);
		}

		if ( ! term_exists( 'sales-marketing', 'position' ) ) {
			$test = wp_insert_term(
				__( 'Sales & Marketing', 'cpt-staff' ),
				'position'
			);
		}

		if ( ! term_exists( 'department-heads', 'position' ) ) {
			$test = wp_insert_term(
				__( 'Department Heads', 'cpt-staff' ),
				'position'
			);
		}

	}

	/**
	 * Add Rewrite Rules to put the Staff Archive and Single pages under each Location
	 *
	 * @access	public
	 * @since	{{VERSION}}
	 * @return  void
	 */
	public function add_rewrite_rules() {

		add_rewrite_rule( 'location/([^/]*)/staff/?$', 'index.php?post_type=staff&vibrant_life_location=$matches[1]', 'top' );
		add_rewrite_rule( 'location/([^/]*)/staff/([^/]*)/?$', 'index.php?post_type=staff&name=$matches[2]&vibrant_life_location=$matches[1]', 'top' );

	}

	/**
	 * Add a Query Var for us to manipulate in pre_get_posts
	 *
	 * @param   array  $query_vars  Accepted/Recognized Query Vars
	 *
	 * @access	public
	 * @since	{{VERSION}}
	 * @return  array               Accepted/Recognized Query Vars
	 */
	public function add_query_var( $query_vars ) {
		
		$query_vars[] = 'vibrant_life_location';
		
		return $query_vars;
		
	}

	/**
	 * Ensure our Archives only show results for the current Location
	 *
	 * @param   object  $query  WP_Query Object
	 *
	 * @access	public
	 * @since	{{VERSION}}
	 * @return  void
	 */
	public function modify_archive_query( $query ) {

		if ( ! $query->get( 'vibrant_life_location' ) ) return;

		if ( ! is_archive() ) return;

		$location_id = $this->get_location_id_by_slug( $query->get( 'vibrant_life_location' ) );

		$this->add_location_to_meta_query( $location_id, $query );

	}

	/**
	 * Similarly to Archives, make sure our Single pages are associated with the Location
	 *
	 * @param   object  $query  WP_Query Object
	 *
	 * @access	public
	 * @since	{{VERSION}}
	 * @return  void
	 */
	public function modify_single_query( $query ) {

		if ( ! $query->get( 'vibrant_life_location' ) ) return;

		if ( ! is_single() ) return;

		$location_id = $this->get_location_id_by_slug( $query->get( 'vibrant_life_location' ) );

		$this->add_location_to_meta_query( $location_id, $query );

	}

	/**
	 * We pass our Query Var the Location Slug, but we store the association via Post ID
	 *
	 * @param   string  $slug  Location Slug
	 *
	 * @access	private
	 * @since	{{VERSION}}
	 * @return  integer        Location ID
	 */
	private function get_location_id_by_slug( $slug ) {

		$post_ids = get_posts( array(
			'name' => $slug,
			'post_type' => 'facility',
			'numberposts' => 1,
			'fields' => 'ids'
		) );

		$location_id = array_shift( $post_ids );

		return $location_id;

	}

	/**
	 * Add our Location ID to the Meta Query by Reference
	 *
	 * @param   integer  $location_id  Location ID
	 * @param   object   &$query       WP_Query Object
	 *
	 * @access	private
	 * @since	{{VERSION}}
	 * @return  void
	 */
	private function add_location_to_meta_query( $location_id, &$query ) {

		$meta_query = array(
			array(
				'key' => 'rbm_cpts_p2p_facility',
				'value' => $location_id,
				'compare' => 'LIKE',
			),
		);

		if ( $query->get( 'meta_query' ) ) {

			$old_meta_query = $query->get( 'meta_query' );

			$query->set( 'meta_query', $old_meta_query + $meta_query );

		}
		else {
			$query->set( 'meta_query', array( 'relation' => 'AND' ) + $meta_query );
		}

	}

	/**
	 * Returns a 404 if someone were to try to visit the default /staff archive
	 *
	 * @param   string  $template  WordPress Theme Template
	 *
	 * @access	public
	 * @since	{{VERSION}}
	 * @return  string             WordPress Theme Template
	 */
	public function disable_default_staff_archive( $template ) {

		if ( ! is_archive() ) return $template;

		if ( get_post_type() !== 'staff' ) return $template;

		global $wp_query;

		// We are already addressing the Location Staff archives above
		if ( $wp_query->get( 'vibrant_life_location' ) ) return $template;

		$wp_query->is_404 = true;
		$wp_query->post_type = false;
		$wp_query->is_archive = false;

		return locate_template( '404.php', false, false );

	}
	
	/**
	 * Replace the_permalink() calls on the Frontend with the new Permastruct
	 * 
	 * @param		string $url The Post URL
	 *                
	 * @access		public
	 * @since		1.0.0
	 * @return		string Modified URL
	 */
	public function the_permalink( $url ) {
		
		global $post;
		
		if ( $post->post_type !== 'staff' ) return $url;
		
		$location_ids = rbm_cpts_get_p2p_parent( 'facility', $post->ID );
		
		if ( ! $location_ids || count( $location_ids ) > 1 ) return $url;
		
		$url = str_replace( '/staff/', '/location/' . get_post_field( 'post_name', $location_ids[0] ) . '/staff/', $url );
		
		return $url;
		
	}
	
	/**
	 * Replace get_peramlink() calls on the Frontend with the new Permastruct
	 * 
	 * @param		string  $url       The Post URL
	 * @param		object  $post      WP Post Object
	 * @param		boolean $leavename Whether to leave the Post Name
	 * @param		boolean $sample    Is it a sample permalink?
	 *     
	 * @access		public
	 * @since		1.0.0
	 * @return		string  Modified URL
	 */
	public function get_permalink( $url, $post, $leavename = false, $sample = false ) {
		
		if ( $post->post_type !== 'staff' ) return $url;
		
		$location_ids = rbm_cpts_get_p2p_parent( 'facility', $post->ID );
		
		if ( ! $location_ids || count( $location_ids ) > 1 ) return $url;
		
		$url = str_replace( '/staff/', '/location/' . get_post_field( 'post_name', $location_ids[0] ) . '/staff/', $url );
		
		return $url;
		
	}

	/**
	 * Force a redirect to the Location-version of the Staff URL if appropriate
	 * 
	 * @param       string $template Path to Template File
	 *                                                
	 * @since       1.0.0
	 * @return      string Modified Template File Path
	 */
	public function redirect_to_permastruct( $template ) {
		
		global $post;
		
		if ( ! is_single() || $post->post_type !== 'staff' ) return $template;
		
		$location_ids = rbm_cpts_get_p2p_parent( 'facility', $post->ID );
		
		if ( ! $location_ids || count( $location_ids ) > 1 ) return $template;
		
		// Ensure we don't accidentally redirect infinitely
		$url = $_SERVER['REQUEST_URI'];
		if ( strpos( $url, '/location/' ) !== false ) return $template;
		
		$url = str_replace( '/staff/', '/location/' . get_post_field( 'post_name', $location_ids[0] ) . '/staff/', $url );
		
		header( "Location: $url", true, 301 );
		
	}

	/**
	 * Add Meta Box
	 * 
	 * @since 1.0.0
	 */
	public function add_meta_boxes() {

		global $post;
		
		add_meta_box(
			'vibrant-life-staff-meta',
			sprintf( __( '%s Meta', 'cpt-staff' ), $this->label_singular ),
			array( $this, 'meta_metabox_content' ),
			$this->post_type,
			'normal'
		);

	}
	
	public function remove_meta_boxes() {
		
		remove_post_type_support( 'staff', 'editor' );
		
	}
	
	public function meta_metabox_content( $post_id ) {
		
		rbm_cpts_do_field_text( array(
			'label' => '<strong>' . __( 'Prefix', 'cpt-staff' ) . '</strong>',
			'name' => 'prefix',
			'group' => 'staff_meta',
			'input_class' => '',
			'description' => '<p class="description">' . __( "This is placed before the Staff Member's name. It is followed by a space automatically.", 'cpt-staff' ) . '</p>',
			'description_tip' => false,
			'description_placement' => 'after_label',
			'input_atts' => array(
				'placeholder' => __( 'Example: "Dr."', 'cpt-staff' ),
			),
		) );
		
		rbm_cpts_do_field_text( array(
			'label' => '<strong>' . __( 'Suffix', 'cpt-staff' ) . '</strong>',
			'name' => 'suffix',
			'group' => 'staff_meta',
			'input_class' => '',
			'description' => '<p class="description">' . __( "This is placed after the Staff Member's name. It is preceeded by a comma automatically.", 'cpt-staff' ) . '</p>',
			'description_tip' => false,
			'description_placement' => 'after_label',
			'input_atts' => array(
				'placeholder' => __( 'Example: "M.D."', 'cpt-staff' ),
			),
		) );
		
		rbm_cpts_do_field_repeater( array(
			'label' => '<strong>' . __( 'Phone Numbers', 'cpt-staff' ) . '</strong>',
			'name' => 'phone_numbers',
			'group' => 'staff_meta',
			'description' => '<p class="description">' . __( "Some staff may have more than one phone number.", 'cpt-staff' ) . '</p>',
			'description_tip' => false,
			'description_placement' => 'after_label',
			'fields' => array(
				'phone_number' => array(
					'type' => 'text',
					'args' => array(
						'label' => '<strong>' . __( 'Phone Number', 'cpt-staff' ) . '</strong>',
					),
				),
			),
		) );

		rbm_cpts_do_field_repeater( array(
			'label' => '<strong>' . __( 'Email Addresses', 'cpt-staff' ) . '</strong>',
			'name' => 'email_addresses',
			'group' => 'staff_meta',
			'description' => '<p class="description">' . __( "Some staff may have more than one email address.", 'cpt-staff' ) . '</p>',
			'description_tip' => false,
			'description_placement' => 'after_label',
			'fields' => array(
				'email_address' => array(
					'type' => 'text',
					'args' => array(
						'label' => '<strong>' . __( 'Email Address', 'cpt-staff' ) . '</strong>',
					),
				),
			),
		) );

		rbm_cpts_do_field_repeater( array(
			'label' => '<strong>' . __( 'Fax Numbers', 'cpt-staff' ) . '</strong>',
			'name' => 'fax_numbers',
			'group' => 'staff_meta',
			'description' => '<p class="description">' . __( "Some staff may have more than one fax number.", 'cpt-staff' ) . '</p>',
			'description_tip' => false,
			'description_placement' => 'after_label',
			'fields' => array(
				'fax_number' => array(
					'type' => 'text',
					'args' => array(
						'label' => '<strong>' . __( 'Fax Number', 'cpt-staff' ) . '</strong>',
					),
				),
			),
		) );

		rbm_cpts_do_field_repeater( array(
			'label' => '<strong>' . __( 'Certifications', 'cpt-staff' ) . '</strong>',
			'name' => 'certifications',
			'group' => 'staff_meta',
			'description' => '<p class="description">' . __( "Each Certification goes in its own line.", 'cpt-staff' ) . '</p>',
			'description_tip' => false,
			'description_placement' => 'after_label',
			'fields' => array(
				'certification' => array(
					'type' => 'text',
					'args' => array(
						'label' => '<strong>' . __( 'Certification', 'cpt-staff' ) . '</strong>',
					),
				),
			),
		) );
		
		rbm_cpts_init_field_group( 'staff_meta' );
		
	}

	/**
	 * Adds an Admin Column
	 * @param  array $columns Array of Admin Columns
	 * @return array Modified Admin Column Array
	 */
	public function admin_column_add( $columns ) {

		$columns['staff_location'] = __( 'Location(s)', 'cpt-staff' );

		return $columns;

	}

	/**
	 * Displays data within Admin Columns
	 * @param string $column  Admin Column ID
	 * @param integer $post_id Post ID
	 */
	public function admin_column_display( $column, $post_id ) {

		switch ( $column ) {

			case 'staff_location' :

				$location_ids = rbm_cpts_get_p2p_parent( 'facility', $post_id );

				if ( ! $location_ids ) :
					echo __( 'None', 'cpt-staff' );
				else : ?>

					<ul style="margin: 0; list-style: disc;">
						<?php foreach ( $location_ids as $location_id ) : ?>
							<li>
								<a href="<?php echo get_the_permalink( $location_id ); ?>">
									<?php echo get_the_title( $location_id ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>

				<?php endif;

				break;

		}

	}

	/**
	 * Add Prefix and Suffix to the Title wherever it is output
	 *
	 * @param   string  $title    Post Title
	 * @param   integer $post_id  Post ID
	 *
	 * @access	public
	 * @since	{{VERSION}}
	 * @return  string            Post Title
	 */
	public function the_title( $title, $post_id ) {

		if ( is_admin() ) return $title;

		if ( get_post_type( $post_id ) !== 'staff' ) return $title;

		$title = trim( $title );

		if ( $prefix = rbm_cpts_get_field( 'prefix', $post_id ) ) {

			$title = trim( $prefix ) . ' ' . $title;

		}

		if ( $suffix = rbm_cpts_get_field( 'suffix', $post_id ) ) {

			$title = $title . ', ' . trim( ltrim( trim( $suffix ), ',' ) );

		}

		return $title;

	}

}

$instance = new CPT_Staff_CPT();