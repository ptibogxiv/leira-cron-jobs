<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @link       https://github.com/arielhr1987
 * @since      1.0.0
 * @package    Leira_Cron_Jobs
 * @subpackage Leira_Cron_Jobs/admin
 * @author     Ariel <arielhr1987@gmail.com>
 */
class Leira_Cron_Jobs_Admin{

	/**
	 * @var string
	 */
	protected $capability = 'manage_options';

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * The admin list table instance
	 *
	 * @var null
	 */
	protected $list_table;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->list_table  = null;

		//register the manager instance
		if ( ! class_exists( 'Leira_Cron_Jobs_Manager' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'class-leira-cron-jobs-manager.php';
		}
		Leira_Cron_Jobs::instance()->get_loader()->set( 'manager', new Leira_Cron_Jobs_Manager() );

	}

	/**
	 * Returns the admin list table instance
	 *
	 * @return Leira_Cron_Jobs_List_Table|null
	 */
	public function get_list_table() {
		if ( $this->list_table === null ) {
			if ( ! class_exists( 'Leira_Cron_Jobs_List_Table' ) ) {
				require_once plugin_dir_path( __FILE__ ) . 'class-leira-cron-jobs-list-table.php';
			}
			$this->list_table = new Leira_Cron_Jobs_List_Table( array(
				'screen' => get_current_screen()
			) );
		}

		return $this->list_table;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

	}

	/**
	 * Add the admin menu item
	 */
	public function admin_menu() {
		$hook = add_management_page(
			__( 'Cron Jobs', 'leira-cron-jobs' ),
			__( 'Cron Jobs', 'leira-cron-jobs' ),
			$this->capability,
			'leira-cron-jobs',
			array( $this, 'render_admin_page' ) );

		if ( ! empty( $hook ) ) {
			add_action( "load-$hook", array( $this, 'admin_page_load' ) );
		}
	}

	/**
	 * Render the admin page
	 */
	public function render_admin_page() {
		if ( ! current_user_can( $this->capability ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'leira-cron-jobs' ) );
		}

		?>

        <div class="wrap">
            <h1 class="wp-heading-inline">Cron Jobs</h1>
            <!--<a href="#" class="page-title-action">--><?php //_e( 'Add New', 'leira-cron-jobs' ) ?><!--</a>-->
			<?php
			if ( isset( $_REQUEST['s'] ) && $search = esc_attr( wp_unslash( $_REQUEST['s'] ) ) ) {
				/* translators: %s: search keywords */
				printf( ' <span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;', 'leira-cron-jobs' ) . '</span>', $search );
			}

			//the cron job table instance
			$table = $this->get_list_table();
			$table->prepare_items();

			?>
            <hr class="wp-header-end">
            <h2 class="screen-reader-text"><?php _e( 'Filter cron jobs list', 'leira-cron-jobs' ) ?></h2>
            <form action="<?php echo add_query_arg( '', '' ) ?>" method="post">
				<?php
				$table->search_box( __( 'Search Events', 'leira-cron-jobs' ), 'event' );
				$table->views();
				$table->display(); //Display the table
				?>
            </form>
			<?php if ( $table->has_items() ): ?>
                <form method="get">
					<?php $table->inline_edit() ?>
                </form>
			<?php endif; ?>
        </div>

		<?php
	}

	/**
	 * On admin page load. Add content to the page
	 */
	public function admin_page_load() {
		if ( ! current_user_can( $this->capability ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'leira-cron-jobs' ) );
		}

		//enqueue styles
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/leira-cron-jobs-admin.css', array(), $this->version, 'all' );

		//enqueue scripts
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/leira-cron-jobs-admin.js', array(
			'jquery',
			'wp-a11y'
		), $this->version, false );

		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		//initialize table here to be able to register default WP_List_Table screen options
		$this->get_list_table();

		//handle bulk ans simple actions
		$this->handle_actions();

		//add modal thickbox js
		add_thickbox();

		//Add screen options
		add_screen_option( 'per_page', array( 'default' => 999 ) );

		//Add screen help
		get_current_screen()->add_help_tab(
			array(
				'id'      => 'overview',
				'title'   => __( 'Overview', 'leira-cron-jobs' ),
				'content' =>
					'<p>' . __( 'Cron is the time-based task scheduling system that is available on UNIX systems. WP-Cron is how WordPress handles scheduling time-based tasks in WordPress. Several WordPress core features, such as checking for updates and publishing scheduled post, utilize WP-Cron.', 'leira-cron-jobs' ) . '</p>' .
					'<p>' . __( 'WP-Cron works by: on every page load, a list of scheduled tasks is checked to see what needs to be run. Any tasks scheduled to be run will be run during that page load. WP-Cron does not run constantly as the system cron does; it is only triggered on page load. Scheduling errors could occur if you schedule a task for 2:00PM and no page loads occur until 5:00PM.', 'leira-cron-jobs' ) . '</p>' .
					'<p>' . __( 'In the scenario where a site may not receive enough visits to execute scheduled tasks in a timely manner, you can call directly or via a server CRON daemon for X number of times the file <strong>wp-cron.php</strong> located in your Wordpress installation root folder.', 'leira-cron-jobs' ) . '</p>' .
					'',
			)
		);
		get_current_screen()->add_help_tab(
			array(
				'id'      => 'screen-content',
				'title'   => __( 'Screen Content', 'leira-cron-jobs' ),
				'content' =>
					'<p>' . __( 'You can customize the display of this screen&#8217;s contents in a number of ways:', 'leira-cron-jobs' ) . '</p>' .
					'<ul>' .
					'<li>' . __( 'You can hide/display columns based on your needs and decide how many cron jobs to list per screen using the <strong>Screen Options</strong> tab.', 'leira-cron-jobs' ) . '</li>' .
					'<li>' . __( 'You can filter the list of cron jobs by schedule using the text links above the list to only show those with that status. The default view is to show all.', 'leira-cron-jobs' ) . '</li>' .
					'<li>' . __( 'The <strong>Search Events</strong> button will search for crons containing the text you type in the box.', 'leira-cron-jobs' ) . '</li>' .
					'</ul>'
			)
		);

		$status         = '<p>' . __( 'Your Wordpress Cron Jobs status is:', 'leira-cron-jobs' ) . '</p>';
		$disable_cron   = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
		$alternate_cron = defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON;
		if ( $disable_cron ) {
			//$status .= __( '<div class="notice notice-error notice-alt inline"><p class="error">The <strong>DISABLE_WP_CRON</strong> constant is set to <strong>TRUE</strong>.</p></div></li>', 'leira-cron-jobs' );
		} else {
			//$status .= __( '<div class="notice notice-success notice-alt inline"><p class="success">The <strong>DISABLE_WP_CRON</strong> constant is set to <strong>FALSE</strong>.</p></div></li>', 'leira-cron-jobs' );
		}

		$status .= '<ul>' .
		           //'<li><div class="notice notice-error notice-alt inline"><p class="error">0</p> </div></li>'.
		           '<li>' . sprintf( __( 'The <strong>DISABLE_WP_CRON</strong> constant is set to <strong>%s</strong>. ', 'leira-cron-jobs' ), $disable_cron ? 'TRUE' : 'FALSE' ) . ( $disable_cron ? __( 'Make sure to create a server CRON daemon that points to the file <strong>wp-cron.php</strong> located in your Wordpress installation root folder', 'leira-cron-jobs' ) : '' ) . '</li>' .
		           '<li>' . sprintf( __( 'The <strong>ALTERNATE_WP_CRON</strong> constant is set to <strong>%s</strong>. ', 'leira-cron-jobs' ), $alternate_cron ? 'TRUE' : 'FALSE' ) . '</li>' .
		           '<ul>';

		get_current_screen()->add_help_tab(
			array(
				'id'      => 'status',
				'title'   => __( 'Status', 'leira-cron-jobs' ),
				'content' => $status,
			)
		);

		get_current_screen()->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'leira-cron-jobs' ) . '</strong></p>' .
			'<p>' . __( '<a href="https://developer.wordpress.org/plugins/cron/">Documentation on Crons</a>', 'leira-cron-jobs' ) . '</p>' .
			'<p>' . __( '<a href="https://wordpress.org/support/">Support</a>', 'leira-cron-jobs' ) . '</p>' . //TODO: Change to github plugin page
			'<p>' . __( '<a href="https://wordpress.org/support/">Report an issue</a>', 'leira-cron-jobs' ) . '</p>' //TODO: Change to github plugin page
		);

		get_current_screen()->set_screen_reader_content(
			array(
				'heading_views'      => __( 'Filter Cron Job list', 'leira-cron-jobs' ),
				'heading_pagination' => __( 'Cron Job list navigation', 'leira-cron-jobs' ),
				'heading_list'       => __( 'Cron Job list', 'leira-cron-jobs' ),
			)
		);
	}

	/**
	 * Filter the per_page screen option fot the admin list table
	 *
	 * @param $false
	 * @param $option
	 * @param $value
	 *
	 * @return int
	 */
	public function filter_set_screen_option( $false, $option, $value ) {

		if ( $option === 'tools_page_leira_cron_jobs_per_page' ) {
			$value = (int) $value;
			if ( $value > 0 && $value < 1000 ) {
				return $value;
			}
		}

		return $false;
	}

	/**
	 * Handle actions
	 */
	protected function handle_actions() {
		$action = $this->get_list_table()->current_action();
		if ( ! empty( $action ) ) {

			$query_arg = '_wpnonce';
			$checked   = isset( $_REQUEST[ $query_arg ] ) && wp_verify_nonce( $_REQUEST[ $query_arg ], 'bulk-cron-jobs' );

			if ( ! $checked ) {
				//no action to handle, just show the page
				return;
			}

			/** @var Leira_Cron_Jobs_Manager $manager */
			$manager = Leira_Cron_Jobs::instance()->get_loader()->get( 'manager' );

			$redirect = wp_get_referer();
			if ( empty( $redirect ) ) {
				$params   = array(
					'page' => 'leira-cron-jobs',
				);
				$redirect = add_query_arg( $params, admin_url( 'tools.php' ) );
			}
			$jobs = isset( $_REQUEST['job'] ) && is_array( $_REQUEST['job'] ) ? $_REQUEST['job'] : array();

			if ( empty( $jobs ) ) {
				//No jobs to execute action
				$this->enqueue_message( 'warning', __( 'You most select at least one cron job to perform this action', 'leira-cron-jobs' ) );
			} else {
				//action logic.
				switch ( $action ) {
					case 'run':
						$manager->bulk_run( $jobs );
						$this->enqueue_message( 'success', __( 'The selected cron jobs are being executed in this moment', 'leira-cron-jobs' ) );
						wp_safe_redirect( $redirect );
						die();
						break;
					case 'delete':
						$manager->bulk_delete( $jobs );
						$this->enqueue_message( 'success', __( 'Selected cron jobs were successfully deleted', 'leira-cron-jobs' ) );
						wp_safe_redirect( $redirect );
						die();
						break;
					default:

				}
			}
		} else {
			if ( isset( $_REQUEST['action'] ) ) {
				//if we click "Apply" button
				//TODO: This message is show if we search for cron jobs. Show it only if we hit Apply
				//$this->enqueue_message( 'warning', __( 'Please select a bulk action to execute', 'leira-cron-jobs' ) );
			}
		}
	}

	/**
	 * Handle Quick Edit action.
	 * A Cron Job is edited by deleting the current one and creating a new one with the new parameters.
	 */
	public function ajax_save() {
		/**
		 * Check user capability
		 */
		if ( ! current_user_can( $this->capability ) ) {
			$out = __( 'You do not have sufficient permissions to edit this cron job.', 'leira-cron-jobs' );
			//$out .= '<script>setTimeout(function() {}, 5000)</script>'; //refresh the page
			wp_die( $out );
		}

		/**
		 * Check nonce
		 */
		$query_arg = '_inline_edit';
		$checked   = isset( $_REQUEST[ $query_arg ] ) && wp_verify_nonce( $_REQUEST[ $query_arg ], 'cronjobinlineeditnonce' );
		if ( ! $checked ) {
			$out = __( 'Your link has expired, refresh the page and try again.', 'leira-cron-jobs' );
			//$out .= '<script>setTimeout(function() {alert("");}, 5000)</script>'; //refresh the page
			wp_die( $out );
		}

		/**
		 * Validate input data
		 */
		$values = array(
			'event'    => '',
			'_action'  => '',
			'md5'      => '',
			'schedule' => '',
			'time'     => '',
			'mm'       => '',
			'jj'       => '',
			'aa'       => '',
			'hh'       => '',
			'mn'       => '',
			'ss'       => '',
			'offset'   => 0 //UTC
		);
		foreach ( $values as $key => $value ) {
			if ( ! isset( $_REQUEST[ $key ] ) || trim( $_REQUEST[ $key ] ) == '' ) {
				$out = __( 'Missing parameters. Refresh the page and try again.', 'leira-cron-jobs' );
				wp_die( $out );
			}
			$request_value = sanitize_text_field( $_REQUEST[ $key ] );
			if ( in_array( $key, array( 'mm', 'jj', 'hh', 'mn', 'ss' ) ) ) {
				//add leading zeros to date time fields
				$request_value = str_pad( $request_value, 2, "0", STR_PAD_LEFT );
			}
			$values[ $key ]            = $request_value;
			$schedules                 = wp_get_schedules();
			$schedules['__single_run'] = array();
			if ( $key === 'schedule' && $schedules = wp_get_schedules() && ! isset( $schedules[ $request_value ] ) ) {
				$out = __( 'Incorrect schedule. Please select a valid schedule from the dropdown menu and try again.', 'leira-cron-jobs' );
				wp_die( $out );
			}
			if ( $key == 'aa' ) {

			}
		}

		/**
		 * Validate execution datetime input data
		 */
		$format        = 'Y-m-d H:i:s';
		$date_str      = sprintf( '%s-%s-%s %s:%s:%s', $values['aa'], $values['mm'], $values['jj'], $values['hh'], $values['mn'], $values['ss'] );
		$timezone_name = timezone_name_from_abbr( '', ( 0 - $values['offset'] ) * 60, 1 );
		try{
			$timezone = new DateTimeZone( $timezone_name );
		}catch( Exception $e ){
			//UTC by default
			$timezone = new DateTimeZone( 'UTC' );
		}
		$date = DateTime::createFromFormat( $format, $date_str, $timezone );

		if ( $date && $date->format( $format ) === $date_str ) {
			// The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
			//date time is valid
		} else {
			//invalid date time information
			$out = __( 'Invalid "Execution" datetime. Please select a valid datetime and try again.', 'leira-cron-jobs' );
			wp_die( $out );
		}
		//convert to UTC
		$date->setTimezone( new DateTimeZone( timezone_name_from_abbr( '', 0, 1 ) ) );

		/**
		 * Edit the Cron Job
		 */

		/** @var Leira_Cron_Jobs_Manager $manager */
		$manager = Leira_Cron_Jobs::instance()->get_loader()->get( 'manager' );
		$args    = isset( $_REQUEST['args'] ) ? sanitize_text_field( $_REQUEST['args'] ) : '';
		$args    = @json_decode( $args, true );
		if ( ! is_array( $args ) ) {
			$args = array();
		}
		$edited = $manager->edit( $values['event'], $values['md5'], $values['time'], $values['schedule'], $date->format( 'U' ), $args );

		if ( ! $edited ) {
			//The cron job does not exist or WP wasn't able to create it
			$out = __( 'An Error occurred while editing the cron job. Refresh the page and try again.', 'leira-cron-jobs' );
			wp_die( $out );
		}

		/**
		 * Output the row table with the new updated data
		 */
		$GLOBALS['hook_suffix'] = '';//avoid notice error
		$table                  = $this->get_list_table();

		$action = $manager->get_cron_action( $values['event'] );
		$table->single_row( array(
			'event'    => $values['event'],
			'action'   => ! empty( $action ) ? $action : '',
			'args'     => ! empty( $args ) ? json_encode( $args ) : '',
			'schedule' => $values['schedule'],
			'time'     => $date->format( 'U' ),
			'md5'      => md5( serialize( $args ) ),
		) );
		wp_die();
	}

	/**
	 * Enqueue an admin flash message notice
	 *
	 * @param $type
	 * @param $text
	 */
	protected function enqueue_message( $type, $text ) {
		update_option( 'leira-cron-jobs-flash-message', array(
			$type => $text
		) );
	}

	/**
	 * Display admin flash notices
	 */
	public function admin_notices() {
		$messages = get_option( 'leira-cron-jobs-flash-message', array() );
		foreach ( $messages as $type => $text ) {
			echo sprintf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', $type, $text );
		}
		update_option( 'leira-cron-jobs-flash-message', array() );
	}
}
