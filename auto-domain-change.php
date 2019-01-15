<?php
/*
Plugin Name: Automatic Domain Changer
Plugin URI: http://www.nuagelab.com/wordpress-plugins/auto-domain-change
Description: Automatically changes the domain of a WordPress blog
Author: NuageLab <wordpress-plugins@nuagelab.com>
Version: 2.0.2
License: GPLv2 or later
Author URI: http://www.nuagelab.com/wordpress-plugins
*/

// --

/**
* Automatic Domain Changer class
*
* @author	Tommy Lacroix <tlacroix@nuagelab.com>
*/
class auto_domain_change {

	private static $_instance = null;

	/**
	* Bootstrap
	*
	* @author	Tommy Lacroix <tlacroix@nuagelab.com>
	* @access	public
	*/
	public static function boot()
	{
		if (self::$_instance === null) {
			self::$_instance = new auto_domain_change();
			self::$_instance->setup();
			return true;
		}

		return false;
	} // boot()


	/**
	* Setup plugin
	*
	* @author	Tommy Lacroix <tlacroix@nuagelab.com>
	* @access	public
	*/
	public function setup()
	{
		global $current_blog;

		// Add admin menu
		add_action('admin_menu', array(&$this, 'add_admin_menu'));
		add_action( 'admin_enqueue_scripts', array(&$this, 'load_adc_admin_style' ));
		add_action( 'admin_enqueue_scripts', array(&$this, 'load_adc_admin_script' ));
		add_action( 'admin_enqueue_scripts', array(&$this, 'load_adc_admin_ajax_script' ));
		add_action( 'admin_enqueue_scripts', array(&$this, 'load_adc_admin_datatables_ajax_script' ));

		add_action( 'wp_ajax_adcGetTables', array(&$this, 'adcGetTables' ));
		add_action( 'wp_ajax_adcGetDefaultTables', array(&$this, 'adcGetDefaultTables' ));
		add_action( 'wp_ajax_adcGetPlugins', array(&$this, 'adcGetPlugins' ));
		add_action( 'wp_ajax_adcGetPluginTables', array(&$this, 'adcGetPluginTables' ));
		add_action( 'wp_ajax_adcGetQuery', array(&$this, 'adcGetQuery' ));
		add_action( 'wp_ajax_adcGetColumns', array(&$this, 'adcGetColumns' ));
		add_action( 'wp_ajax_adcGetTablesInSnippets', array(&$this, 'adcGetTablesInSnippets' ));

		// Add options
		add_option('auto_domain_change-https', false);
		add_option('auto_domain_change-www', true);
		add_option('auto_domain_change-plugin-tables', array());


		// Load text domain
		load_plugin_textdomain('auto-domain-change', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');

		// Check if the domain was changed
		if (is_admin()) {
			$this->check_domain_change();

			// Handle some actions
			if (isset($_POST['action'])) {
				require_once(ABSPATH .'wp-includes/pluggable.php');

				if (wp_verify_nonce(@$_POST['nonce'],@$_POST['action'])) {
					$parts = explode('+',$_POST['action']);
					switch ($parts[0]) {
						case 'backup-database':
						if (current_user_can('export')) {
							switch ($_POST['type']) {
								case 'sql':
								default:
								return $this->do_backup_sql();
								case 'php':
								return $this->do_backup_php();
							}
						}
						default:
						// ignore
						break;
					}
				}
			}
		}
	} // setup()
	public function load_adc_admin_style($hook) {
		//wp_die($hook);
		// Load only on ?page=mypluginname
		if($hook != 'tools_page_auto-domain-change') {
			return;
		}
		wp_enqueue_style( 'adc_wp_admin_css', plugins_url('/assets/css/adc-admin.css', __FILE__) );
		wp_enqueue_style( 'adc_wp_admin_datatables_css', 'https://cdn.datatables.net/v/bs4/dt-1.10.18/kt-2.5.0/r-2.2.2/sc-1.5.0/datatables.min.css' );
		wp_enqueue_style( 'adc_wp_admin_datatables_bootstrap_css', 'https://cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css' );


	}
	public function load_adc_admin_script($hook) {
		//wp_die($hook);
		// Load only on ?page=mypluginname
		if($hook != 'tools_page_auto-domain-change') {
			return;
		}
		wp_enqueue_script( 'adc_wp_admin_script', plugins_url('/assets/js/adc-admin.js', __FILE__) );
		//      wp_register_script( 'adc_datatables', 'https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js', array());
		wp_enqueue_script('adc_datatables','https://cdn.datatables.net/v/bs4/dt-1.10.18/kt-2.5.0/r-2.2.2/sc-1.5.0/datatables.min.js');
		wp_enqueue_script('adc_datatables_bootstrap','https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js');

	}
	/**
	* enqueue and localize the general ajax script
	*
	* @author	Michael DeWitt <michael.dewitt@gmail.com>
	* @access	public
	*/
	public function load_adc_admin_ajax_script($hook) {
		//wp_die($hook);
		// Load only on ?page=mypluginname
		if($hook != 'tools_page_auto-domain-change') {
			return;
		}
		wp_enqueue_script( 'adc_wp_admin_ajax_script', plugins_url('/assets/js/adc-admin-ajax.js', __FILE__),array('jquery') );
		$adc_nonce = wp_create_nonce( 'adc_tables' );
		wp_localize_script( 'adc_wp_admin_ajax_script', 'adc_ajax_obj', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'    => $adc_nonce, // It is common practice to comma after
		) );                // the last array item for easier maintenance
	}  // load_adc_admin_ajax_script($hook)

	/**
	* enqueue and localize ajax script for just the datables script
	*
	* @author	Michael DeWitt <michael.dewitt@gmail.com>
	* @access	public
	*/  
	public function load_adc_admin_datatables_ajax_script($hook) {
		//wp_die($hook);
		// Load only on ?page=mypluginname
		if($hook != 'tools_page_auto-domain-change') {
			return;
		}
		wp_enqueue_script( 'adc_wp_admin_datatables_ajax_script', plugins_url('/assets/js/adc-admin-datatables-ajax.js', __FILE__),array() );
		$adc_nonce = wp_create_nonce( 'adc_tables' );
		wp_localize_script( 'adc_wp_admin_datatables_ajax_script', 'adc_datatables_ajax_obj', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'    => $adc_nonce, // It is common practice to comma after
		) );                // the last array item for easier maintenance
	}  // load_adc_admin_datatables_ajax_script()

	/**
	* method as datatables ajax calling action to return table column definitions
	*
	* @author	Michael DeWitt <michael.dewitt@gmail.com>
	* @access	public
	*/
	public function adcGetColumns()
	{
		global $wpdb;
    
    	if (!isset($_POST['table_name']) || empty($_POST['table_name'])) {
				$message=array('error'=>'No table name was given');
			wp_send_json($message);
			wp_die();
			}
			if (!check_ajax_referer( 'adc_tables' )) {
		    $error=array('error'=>'bad nonce');
		   wp_send_json($error);
		   wp_die();
		}
		$table=$_POST['table_name'];
		//   wp_send_json(print_r($_POST));
		//  wp_die();
		//   $table_name =preg_match('/select (<?columns>[A-Z0-9,\*\_\- ]+) from (<?table_name>[A-Z0-9\_\-]+)
		$sql = 'SHOW COLUMNS FROM '.$table;
		$res = $wpdb->get_results($sql);

		$columns = array();

		foreach ($res as $key=>$row) {
			$row = get_object_vars($row);
			//		$columns [] =  array( 'data' => $row['Field'], 'dt' => $row['Field'] );
			$columns [] =  array( 'data' => $row['Field'] );
		}
		wp_send_json($columns);
		wp_die();

	}//adcGetColumns()

	/**
	* method as datatables ajax calling action to return query results based on given table name
	*
	* @author	Michael DeWitt <michael.dewitt@gmail.com>
	* @access	public
	*/
	public function adcGetQuery()
	{
		global $wpdb;
		 	if (!isset($_POST['table_name']) || empty($_POST['table_name'])) {
				$message=array('error'=>'No table name was given');
			wp_send_json($message);
			wp_die();
			}
			if (!check_ajax_referer( 'adc_tables' )) {
		    $error=array('error'=>'bad nonce');
		   wp_send_json($error);
		   wp_die();
		}
		$table=$_POST['table_name'];
//first get column details to determine the index of the table
		$sql = 'SHOW COLUMNS FROM '.$table;
		$res = $wpdb->get_results($sql);
		$primaryKey = '';
		$columns = array();
		$_POST['columns_array']=array();
		foreach ($res as $key=>$row) {
			$row = get_object_vars($row);
			if ($row['Key']=='PRI') $primaryKey = $row['Field'];
			$columns [] =  array( 'db' => $row['Field'], 'dt' => $row['Field'] );
			$columns_array[]= $row['Field'];
		}
    //SSP requires a key even for tables with no key. set the key to first field.
		if (empty($primaryKey)) $primaryKey =  $columns_array[0];
		
		// SQL server connection information needed since SSP is PDO based
		//$sql_details = array();

		$sql_details = array(
		'user' => DB_USER,
		'pass' => DB_PASSWORD,
		'db'   => DB_NAME,
		'host' => DB_HOST
		);

/*
This is the dataTables server-side processing class and requires php-PDO to run

*/
		require( '/www/rebi/wp-content/plugins/automatic-domain-changer/lib/ssp.class.php');
		$out = SSP::simple( $_POST, $sql_details, $table, $primaryKey, $columns );

		wp_send_json( $out);
		wp_die(); // this is required to terminate immediately and return a proper response
	}//adcGetQuery()

	/**
	* method as general ajax calling action to code snippts for a given table name
	*
	* @author	Michael DeWitt <michael.dewitt@gmail.com>
	* @access	public
	*/
	public function adcGetTablesInSnippets()
	{
		global $wpdb;

		if (!isset($_POST['table_name']) || empty($_POST['table_name'])) {
			$message=array('error'=>'No table name given');
			wp_send_json($message);
			wp_die();
		}
		$table_name=$_POST['table_name'];
		if (!check_ajax_referer( 'adc_tables' )) {
			$error=array('error'=>'bad nonce');
			wp_send_json($error);
			wp_die();
		}
		$sql = 'select * from wp_snippets where code like "%'.$table_name.'%";';
		$ret = $wpdb->get_results($sql);
		$tables = array();
		$found_it = 0;
		foreach ($ret as $row) {
			$row = get_object_vars($row);
			$tables [] = reset($row);
		}
		if (count($tables)) {
			$found_it = 1;
		}
		$data=array('found_it'=>$found_it);

		wp_send_json($data);
		wp_die(); // this is required to terminate immediately and return a proper response
	}//adcGetTablesInSnippets()

	/**
	* method as generals ajax calling action to return all table names
	*
	* @author	Michael DeWitt <michael.dewitt@gmail.com>
	* @access	public
	*/
	public function adcGetTables()
	{
		global $wpdb;
		if (!check_ajax_referer( 'adc_tables' )) {
			$error=array('error'=>'bad nonce');
			wp_send_json($error);
			wp_die();
		}
		$ret = $wpdb->get_results('SHOW TABLES;');
		$tables = array();
		foreach ($ret as $row) {
			$row = get_object_vars($row);
			$tables [] = reset($row);
		}

		wp_send_json($tables);
		wp_die(); // this is required to terminate immediately and return a proper response
	}//adcGetTables()

 /**
	* method as general ajax calling action to return list of core WP table names
	*
	* @author	Michael DeWitt <michael.dewitt@gmail.com>
	* @access	public
	*/
	public function adcGetDefaultTables () {

		global $wpdb;

    if (!check_ajax_referer( 'adc_tables' )) {
			$error=array('error'=>'bad nonce');
			wp_send_json($error);
			wp_die();
		}
    //core table list is made from checking schema.php file
		$admin_path = str_replace( get_bloginfo( 'url' ) . '/', ABSPATH, get_admin_url() );
		require_once($admin_path .'includes/schema.php');

		$wp_db_schema = wp_get_db_schema('all');
		// Separate individual queries into an array
		$queries = explode(';', $wp_db_schema);
		if (''==$queries[count($queries)-1]) {
			array_pop($queries);
		}

		$wpTablesList = array();

		foreach ($queries as $query) {
			if (preg_match("|CREATE TABLE ([^ ]*)|", $query, $matches)) {
				$wpTablesList[trim( strtolower($matches[1]), '`' )] = $query;
			}
		}
		wp_send_json($wpTablesList);
		wp_die(); // this is required to terminate immediately and return a proper response
	}//adcDefaultTables

  	/**
	* method as general ajax calling action to return active plugins list
	*
	* @author	Michael DeWitt <michael.dewitt@gmail.com>
	* @access	public
	*/
	public function adcGetPlugins() {
    
		global $wpdb;

    @set_time_limit(0); //<=not sure if this needed
		if (!check_ajax_referer( 'adc_tables' )) {
			$error=array('error'=>'bad nonce');
			wp_send_json($error);
			wp_die();
		}
		$ret = $wpdb->get_results('select option_value from wp_options where option_name="active_plugins";');
		$plugins = array();
		foreach ($ret as $row) {
			$row = get_object_vars($row);
			$plugins= (array)unserialize($row['option_value']);
		}

		$disp_plugins=array();
		foreach ($plugins as $key=>$value) {
			$plugin_full=$value;
			$plugins_parts = explode('/',$value);
			$plugin_name=$plugins_parts[0];
			$disp_plugins[$plugin_name]=$plugin_full;

		} //foreach plugin
		ksort($disp_plugins,SORT_STRING);
		//      reset($disp_plugins);
		wp_send_json($disp_plugins);
		wp_die(); // this is required to terminate immediately and return a proper response
	}//adcGetPlugins()

	/**
	* method as general ajax calling action to return tables associated with a plugin
	*
	* @author	Michael DeWitt <michael.dewitt@gmail.com>
	* @access	public
	*/  
	public function adcGetPluginTables() {
    
		global $wpdb;

    if (!isset($_POST['plugin_full']) || empty($_POST['plugin_full'])) {
			$message=array('error'=>'No plugin was given');
			wp_send_json($message);
			wp_die();
		}
    
    if (!check_ajax_referer( 'adc_tables' )) {
			$error=array('error'=>'bad nonce');
			wp_send_json($error);
			wp_die();
		}
		$plugin_post=json_decode($_POST['plugin_full'],1);
		$plugin_full=$_POST['plugin_full'];
		$ret = $wpdb->get_results('SHOW TABLES;');
		$tables = array();
		foreach ($ret as $row) {
			$row = get_object_vars($row);
			$tables [] = reset($row);
		}

		$plugin_files = get_plugin_files($plugin_full);
		$used_tables=array();
		foreach ($plugin_files as $plugin_file) {
			$ext = pathinfo($plugin_file, PATHINFO_EXTENSION);

			if ($ext=='php' || $ext=='PHP') {
				$fh = fopen(WP_PLUGIN_DIR.'/'.$plugin_file, 'r');
				if (!$fh) {
					continue;
				}
				while(!feof($fh)) {
					$s = fgets($fh);
					$s = strtolower($s);

					foreach ($tables as $table) {
						$table_no_prefix=str_replace($wpdb->prefix,'',$table);
						if (strpos($s, $table_no_prefix)!==false ) {

							if (!in_array($table,$used_tables))
							$used_tables[] = $table;
						} //if strpos
					} // foreach()
				} //while not eof
				fclose($fh);
			} //if ext=php
		}  //foreach plugin files
		//	} //foreach plugin

		sort($used_tables,SORT_STRING);
		//      reset($disp_plugins);
		wp_send_json($used_tables);
		wp_die(); // this is required to terminate immediately and return a proper response
	}//adcGetPluginTables()

	/**
	* Check if domain has changed, and display admin notice if necessary
	*
	* @author	Tommy Lacroix <tlacroix@nuagelab.com>
	* @access	private
	*/
	private function check_domain_change()
	{
		if (!isset($_SERVER['HTTP_HOST'])) return false;

		$old_domain = get_option('auto_domain_change-domain');
		if (!$old_domain) {
			update_option('auto_domain_change-domain', $_SERVER['HTTP_HOST']);
			return;
		}

		if (($old_domain != $_SERVER['HTTP_HOST']) && (!isset($_POST['new-domain']))) {
			if ((isset($_GET['dismiss-domain-change'])) && ($_GET['dismiss-domain-change'])) {
				update_option('auto_domain_change-dismiss', $_SERVER['HTTP_HOST']);
			} else if (strtolower($_SERVER['HTTP_HOST']) != strtolower(get_option('auto_domain_change-dismiss'))) {
				add_action('admin_notices', array(&$this, 'add_admin_notice'));
			}
		}
	} // check_domain_change()


	/**
	* Add admin notice action; added by check_domain_change()
	*
	* @author	Tommy Lacroix <tlacroix@nuagelab.com>
	* @access	public
	*/
	public function add_admin_notice()
	{
		if (current_user_can('update_core')) {
			echo '<div class="update-nag">
			' . sprintf( __( 'The domain name of your WordPress blog appears to have changed! <a href="%1$s">Click here to update your config</a> or <a href="%2$s">dismiss</a>.', 'auto-domain-change' ),
			'/wp-admin/tools.php?page=' . basename( __FILE__ ),
			add_query_arg( 'dismiss-domain-change', '1' )
			) . '
			</div>';
		}
	} // add_admin_notice()


	/**
	* Add admin menu action; added by setup()
	*
	* @author	Tommy Lacroix <tlacroix@nuagelab.com>
	* @access	public
	*/
	public function add_admin_menu()
	{
		add_management_page(__("Change Domain",'auto-domain-change'), __("Change Domain",'auto-domain-change'), 'update_core', basename(__FILE__), array(&$this, 'admin_page'));
	} // add_admin_menu()


	/**
	* Admin page action; added by add_admin_menu()
	*
	* @author	Tommy Lacroix <tlacroix@nuagelab.com>
	* @access	public
	*/
	public function admin_page()
	{
		global $wpdb;

		if (isset($_POST['https-domain'])) {
			update_option('auto_domain_change-https', $_POST['https-domain']);
		}
		if (isset($_POST['www-domain'])) {
			update_option('auto_domain_change-www', $_POST['www-domain']);
		}
		if ( !isset($_POST['email_domain']) || empty($_POST['email_domain']) ) $_POST['email_domain']=0;
		if ( !isset($_POST['adc_entities']) ) $_POST['adc_entities']=1;
		if ( !isset($_POST['dry_run']) || empty($_POST['dry_run']) ) $_POST['dry_run']=0;
		if ( !isset($_POST['change']) || empty($_POST['change']) ) $_POST['change']='domain';
		if ( !isset($_POST['search']) || empty($_POST['search']) ) $_POST['search']='';
		if ( !isset($_POST['case']) || empty($_POST['case']) ) $_POST['case']='1';
		if ( !isset($_POST['replace']) || empty($_POST['replace']) ) $_POST['replace']='';
		if ( !isset($_POST['timeout']) || empty($_POST['timeout']) ) $_POST['timeout']='0';
		if ( !isset($_POST['wordwrap']) || empty($_POST['wordwrap']) ) $_POST['wordwrap']=150;
		if ( !isset($_POST['debug']) || empty($_POST['debug']) ) $_POST['debug']=0;

		if (isset($_POST['action'])) {
			if (wp_verify_nonce($_POST['nonce'],$_POST['action'])) {
				$parts = explode('+',$_POST['action']);
				switch ($parts[0]) {
					case 'change-domain':
					if (!isset($_POST['new-domain']) || (isset($_POST['new-domain']) && empty($_POST['new-domain'])) ) {
						$error_new_domain = true;
					} elseif (!isset($_POST['old-domain']) || (isset($_POST['old-domain']) && empty($_POST['old-domain'])) ) {
						$error_old_domain = true;
					} elseif (!isset($_POST['accept-terms']) || (isset($_POST['accept-terms']) && !$_POST['accept-terms']) ) {
						$error_terms = true;
					} elseif ( ($_POST['change']=='replace' && empty($_POST['search']) && empty($_POST['replace'])) ||  ($_POST['change']=='replace' && empty($_POST['search']) && !empty($_POST['replace']))) {
						$error_search = true;
					} else {
						return $this->do_change($_POST['old-domain'], $_POST['new-domain'], $_POST['force-protocol'] ? $_POST['force-protocol'] : null);
					}
					break;
					default:
					// ignore
				}
			}
		}
		if (!isset($_POST['old-domain'])) $_POST['old-domain']=esc_html(get_option('auto_domain_change-domain'));
		if (!isset($_POST['new-domain'])) $_POST['new-domain']=esc_html($_SERVER['HTTP_HOST']);

		if (!isset($error_new_domain)) $error_new_domain = false;
		if (!isset($error_old_domain)) $error_old_domain = false;
		if (!isset($error_terms)) $error_terms = false;
		if (!isset($error_search)) $error_search = false;

		echo '<div class="wrap">';

		echo '<div id="icon-tools" class="icon32"><br></div>';
		echo '<h3>'.__('Change Domain','auto-domain-change').'</h3>';
		echo '<div class="adc-notes">
		<p><B>Important:</b>This plugin is intended for use on development WordPress systems only. Changes may break your WordPress installation. Take this seriously and use with caution.</p>
		<p>This plugin has only been tested with MySQL-based databases. If you are using something else, it may not work.The "table explorer requires PHP-PDO to work.</p>
		<P><b>Basic Usage:</b> Review your site&quot;s information by checking the Current, Tables, and table explorer sections. If you want to see more details of what tables are associated with which plugins, click on the "Map Plugins to Tables" button. The "Table Explorer" allows for the display (not changes) of the contents of every table.<p>
		<p>Tables my be excluded from the scope of any changes by clicking on the table&quot;s name. With the table selections set, choose the kind of change to make: "Domain" or "Search and Replace."
		<p>It is recommended to first perform a "dry-run," to understand the scale of changes and help tune the changes that need to be made. The "dry-run" will produce a report of changes and re-cap by displaying the tables with the most changes. When you are satified that the "dry-run" is ready, uncheck it, and check that you have acknowledged the warnings and do have a backup of your data. </p>
		<p>The "Backup database as SQL," button will create a file that you may upload and import to restore the database. Progams like phpMyAdmin, can make this even easier as it allows you to backup/restore a database with just a few clicks. This can be invaluable for complicated changes that may require several runs.</p>


		</div>';
		echo '<form method="post">';

		$action = 'change-domain+'.uniqid();

		wp_nonce_field($action,'nonce');
		echo '<input type="hidden" name="action" value="'.$action.'" />';

		if (array_key_exists('force-protocol', $_POST)) {
			$force_protocol = $_POST['force-protocol'];
		} else if (array_key_exists('HTTPS', $_SERVER)) {
			$force_protocol = 'https';
		} else if (array_key_exists('HTTP_X_FORWARDED_PROTO', $_SERVER) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
			$force_protocol = 'https';
		} else {
			$force_protocol = 'http';
		}
		/*
    //style/script was originally here
		*/

		echo '<table class="form-table" style="width: 100%;padding: 10px;border: 1px solid gray;">';
		echo '<tbody>';
		echo '<tr valign="middle">';
		echo '<th scope="row" colspan="2" style="padding-left: 10px">';
		echo '<h4>Current</h4>';
		echo '<hr>';
		echo '<p>Host ($_SERVER["HTTP_HOST"]) is '.$_SERVER['HTTP_HOST'].'</p>';
		echo '<p>WP site URL (get_site_url()) is '.get_site_url().'</th>';
		echo '</tr>';
		echo '</tbody></table>';

		echo '<table id="table-dbtable" class="form-table" style="width: 100%;padding: 10px;border: 1px solid gray">';
		echo '<tbody>';
		echo '<tr valign="middle">';
		echo '<td colspan="2" style="padding-left: 10px;">
		<h4>Tables/Plugins</h4>
		<hr>
		<div class="dbtable-container">
		<h4>Included</h4>
		(Click to exclude)<br>
		<div id="dbtable-included" class="dbtable-box dbtable-content">

		</div>
		</div>
		<div class="dbtable-container">
		<h4>Excluded</h4>
		(Click to include)<br>
		<div id="dbtable-excluded" class="dbtable-box dbtable-content">

		</div>
		</div>
		<div class="dbtable-container">
		<h4>Active Plugins</h4>
		<div id="dbtable-plugins" class="dbtable-box dbtable-content">
		</div>
		<p><button type="button"  name="map-plugins" id="map-plugins" class="button-primary" title="Click to map plugins to tables" value="">';
		echo esc_html(__('Map plugins to tables','auto-domain-change')).'</button></p>
		<div id="progressBar"><div></div></div>
		<div id="legend">
		<h4>Color Legend</h4>
		<div class="legend-item dbtable-default">WP core table</div>
		<div class="legend-item dbtable-item-hover">Tables associated with plugin (displayed after mapped when hovered)</div>
		<div class="legend-item no-plugin">No active plugin associated (displayed after mapped)</div>
		<div class="legend-item snippet-table">Associated with Code Snippet (displayed after mapped)</div>
		</div>

		</div>

		</td>';
		echo '</tr>';
		echo '<tr valign="middle">';
		echo '<td colspan="2" style="padding-left: 10px"></td>';
		echo '</tr>';
		echo '</tbody></table>';

		//table explorer
		echo '<table id="table-explorer-container" class="form-table" style="width: 100%;padding: 10px;border: 1px solid gray">';
		echo '<tbody>';
		echo '<tr valign="middle">';
		echo '<th scope="row" colspan="2" style="padding-left: 10px">';
		echo '<div id="dbtable-explorer">';
		echo '<h4>Table Explorer</h4>';
		echo '<hr>';
		echo '<table id="table-explorer" class="form-table" style="width: 100%;padding: 10px;">';
		echo '<tbody>';
		echo '<tr valign="middle">';
		echo '<td colspan="2">
		<label for="dbtable-explorer-name">'.__('Select table to display: ','auto-domain-change').'</label>';
		echo '<select name="dbtable-explorer-name" id="dbtable-explorer-name" class="adc-select">';
		$ret = $wpdb->get_results('SHOW TABLES;');
		$tables = array();
		$i=0;
		foreach ($ret as $row) {
			$row = get_object_vars($row);
			$tables [] = reset($row);
			echo '<option value="'.reset($row).'" ';
			if ($i == 0 ) echo ' selected="selected" ';
			echo ' >'.reset($row).'</option>';
			$i++;
		}

		echo '</select>';
		echo '<input type="checkbox" name="adc_entities" id="adc-entities" value="1" '.
		($_POST['adc_entities'] ? 'checked="checked"' : ''). ' /> <label for="adc-entities" style="margin-bottom: 0">'.__('Display table data as entities','auto-domain-change').'</label>';
		echo '  <button id="open-datatable" type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#myModal">Open datatable</button>';
		echo '</td>';
		echo  '</tr></table>';
		echo '
		<div class="modal fade bd-example-modal-lg" id="myModal" tabindex="-1" role="dialog">
		<div class="modal-dialog modal-dialog modal-lg">
		<div class="modal-content modal-dialog modal-lg">
		<div class="modal-header">
		<h5 class="modal-title">Datatable</h5>
		<button type="button" class="close" data-dismiss="modal" aria-label="Close">
		<span aria-hidden="true">&times;</span>
		</button>
		</div>
		<div class="modal-body">
		<table id="datatable" class="table table-striped table-bordered" style="width:100%">
		<thead>';
		/*
		if (!isset($_POST['table_name'])) {
		$table_name=$tables[0];
		} else {
		$table_name=$_POST['table_name'];
		}
		$sql = 'SHOW COLUMNS FROM '.$table_name;
		$res = $wpdb->get_results($sql);
		if (count($res)) {
		echo '<tr>';
		foreach ($res as $key=>$row) {
		$row = get_object_vars($row);
		echo '<th>'. $row['Field'].'</th>';
		}
		echo '</tr>';
		} //if count
		*/
		echo '      </thead>


		</table>
		</div>
		<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		</div>
		</div>

		</div>
		</div>

		</div>
		';
		echo '</th></tr></table>';
		echo '';

		//table explorer
		echo '<table id="table-change-container" class="form-table" style="width: 100%;padding: 10px;border: 1px solid green">';
		echo '<tbody>';
		echo '<tr valign="middle">';
		echo '<td colspan="2" style="padding-left: 10px">';
		echo '<h4>Make Changes</h4>';
		echo '<hr>';
		echo '<table id="table-domain" class="form-table" style="clear: none;float: left;margin: 0 10px;width: 48%;padding: 10px;border: 1px solid gray">';
		echo '<tbody>';
		echo '<tr valign="middle">';
		echo '<td colspan="2" style="padding-left: 10px"><h5>Change Domain</h5></td>';
		echo '</tr>';
		echo '<tr valign="middle">';
		echo '<th scope="row" colspan="2" style="padding-left: 10px">';
		echo '<input type="radio" name="change" id="change-domain" value="domain" '.($_POST['change']=="domain" ? 'checked="checked"' : '').'><label for="change-domain">'.__('Change domain','auto-domain-change').'</label></th>';
		echo '</tr>';
		echo '<tr valign="middle">';
		echo '<th scope="row" style="padding-left: 10px;'.($error_old_domain?'margin-bottom: 0;color:red;font-weight:bold;"':'').'"><label for="old-domain">'.__('Change domain from: ','auto-domain-change').'</label></th>';
		echo '<td>http://<input class="adc-widefat" class="regular-text" type="text" name="old-domain" id="old-domain" value="'.esc_html($_POST['old-domain']).'" /></td>';
		echo '</tr>';
		echo '<tr valign="middle">';
		echo '<th scope="row" style="padding-left: 10px;'.($error_new_domain?'margin-bottom: 0;color:red;font-weight:bold;"':'').'"><label for="new-domain">'.__('Change domain to: ','auto-domain-change').'</label></th>';
		echo '<td>';
		echo '<select name="force-protocol" id="force-protocol" class="adc-select">';
		foreach (array(
		'https'=>__('https://', 'auto-domain-change'),
		'http'=>__('http://', 'auto-domain-change'),
		''=>__('(same)', 'auto-domain-change'),
		) as $protocol=>$name) {
			printf('<option value="%1$s"%3$s>%2$s</option>',
			$protocol,
			$name,
			($force_protocol == $protocol ? ' selected' : '')
			);
		}
		echo '</select>';
		echo '<input class="adc-widefat" class="regular-text" type="text" name="new-domain" id="new-domain" value="'.esc_html($_POST['new-domain']).'" /></td>';
		echo '</tr>';
		echo '<tr valign="middle">';
		echo '<td colspan="2" style="padding-left: 10px"><input type="checkbox" name="email_domain" id="email_domain" value="1" '.
		($_POST['email_domain'] ? 'checked="checked"' : ''). ' /> <label for="email_domain" style="margin-bottom: 0">'.__('Also change email addresses to new domain','auto-domain-change').'</label></td>';
		echo '</tr>';
		echo '<tr valign="middle">';
		echo '<td colspan="2" style="padding-left: 10px"><input type="checkbox" name="https-domain" id="https-domain" value="1" '.
		(get_option('auto_domain_change-https') ? 'checked="checked"' : ''). ' /> <label for="https-domain" style="margin-bottom: 0">'.__('Also change secure <code>https</code> links','auto-domain-change').'</label></td>';
		echo '</tr>';
		echo '<tr valign="middle">';
		echo '<td colspan="2" style="padding-left: 10px"><input type="checkbox" name="www-domain" id="www-domain" value="1" '.
		(get_option('auto_domain_change-www') ? 'checked="checked"' : ''). ' /> <label for="www-domain" style="margin-bottom: 0">'.__('Change both <code>www.old-domain.com</code> and <code>old-domain.com</code> links','auto-domain-change').'</label></td>';
		echo '</tr>';
		echo '</tbody></table>';

		echo '<table id="table-search" class="form-table" style="clear: none;float: right;width: 48%;margin: 0 10px;padding: 10px;border: 1px solid gray">';
		echo '<tbody>';
		echo '<tr valign="middle">';
		echo '<td colspan="2" style="padding-left: 10px"><h5>(Or) Search and Replace</h5></td>';
		echo '</tr>';
		echo '<tr valign="middle">';
		echo '<th scope="row" colspan="2" style="padding-left: 10px"><input type="radio" name="change" id="change-replace" value="replace" '.($_POST['change']=="replace" ? 'checked="checked"' : '').'><label for="change-domain">'.__('Search and replace','auto-domain-change').'</label></th>';
		echo '</tr>';
		echo '<tr valign="middle">';
		echo '<td colspan="2" style="padding-left: 10px;'.($error_search?'margin-bottom: 0;color:red;font-weight:bold;"':'').'">Search for <input class="adc-widefat" class="regular-text" type="text" name="search" id="search" value="'.esc_html($_POST['search']).'"> <br><input type="checkbox" name="case" id="case" '.($_POST['case'] ? 'checked="checked"' : '').' value="1"><label for="case">'.__('Ignore case when searching','auto-domain-change').'</label> </td>';
		echo '</tr>';
		echo '<tr valign="middle">';
		echo '<td colspan="2" style="padding-left: 10px">Replace with <input class="adc-widefat" class="regular-text" type="text" name="replace" id="replace" value="'.esc_html($_POST['replace']).'"> (leaving blank will delete the "searched" term) </td>';
		echo '</tr>';
		echo '</tbody></table>';
		echo '</td></tr></table>';
  
		echo '<table id="misc" class="form-table" style="width: 100%;padding: 10px;border: 1px solid gray;">';
		echo '<tbody>';
		echo '<tr valign="middle">';
		echo '<td colspan="2" style="padding-left: 10px">';
		echo '<h4>Run Conditions</h4>';
		echo '<hr>';
		echo '<input type="checkbox" name="dry_run" id="dry-run" value="1" '.
		($_POST['dry_run'] ? 'checked="checked"' : ''). ' /> <label for="dry-run" style="margin-bottom: 0">'.__('Perform dry-run only - no changes will be made').'</label></td>';
		echo '</tr>';
		echo '<tr valign="middle">';
		echo '<td colspan="2" style="padding-left: 10px"><input type="checkbox" name="debug" id="adc-debug" value="1" '.
		($_POST['debug'] ? 'checked="checked"' : ''). ' /> <label for="adc-debug" style="margin-bottom: 0">'.__('Turn on debugging messages').'</label></td>';
		echo '</tr>';
		echo '<tr valign="middle">';
		echo '<td colspan="2" style="padding-left: 10px"><label for="timeout" style="margin-bottom: 0">'.__('Set script timeout in seconds (default is 0=None)').'</label> <input type="number" min="0" max="9600" name="timeout" id="timeout" value="'.esc_html($_POST['timeout']).'"  /> </td>';
		echo '</tr>';
		echo '<tr valign="middle">';
		echo '<td colspan="2" style="padding-left: 10px"><label for="wordwrap" style="margin-bottom: 0">'.__('Set wordwrap in characters (applies to reporting, default is 150)').'</label> <input type="number" min="0" max="256" name="wordwrap" id="wordwrap" value="'.esc_html($_POST['wordwrap']).'"  /> </td>';
		echo '</tr>';
		echo '<tr valign="middle">';
		echo '<td colspan="2" style="padding-left: 10px">';
		echo '<div id="accept-container" style="float:left;padding: 10px;border: 2px solid black;">';
		echo '<input type="checkbox" name="accept-terms" id="accept-terms" value="1" /> <label for="accept-terms"'.($error_terms?' style="margin-bottom: 0;color:red;font-weight:bold;"':' style="margin-bottom: 0;"').'>'.__('I have backed up my database, checked the backups integrity, know how to restore it, and will assume the responsability of any data loss or corruption.','auto-domain-change').'</label>';
		echo '<br>';
		echo '<br>';
		echo '<p class="backup">';
		echo '<button class="adc-backup-button" data-type="sql">'.__('Backup database as SQL','auto-domain-change').'</button>';
		echo '</p>';
		echo '</div>';
		echo '</td>';
		echo '</tr>';
		echo '</tbody></table>';

		echo '<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="'.esc_html(__('Make Changes','auto-domain-change')).'"></p>';
		echo '<div class="timer" id="timer" style="display:block;clear:left;">
		<p><b>Elapsed time:</b></p>
		<span id="minutes">0</span> minute(s)
		<span id="seconds">0</span> seconds.
		</div>';
		echo '</form>';

		echo '<form method="post" id="adc-backup-db">';
		$action = 'backup-database+'.uniqid();
		wp_nonce_field($action,'nonce');
		echo '<input type="hidden" name="action" value="'.$action.'" />';
		echo '<input type="hidden" name="type" value="sql" />';
		echo '</form>';
		/*
    //style/script was originally here
		*/   
		echo '</div>';
	} // admin_page()


	/**
	* Change domain. This is where the magic happens.
	* Called by admin_page() upon form submission.
	* @since 2.02
  * added tests for tables/columns to avoid data that is not searchable
	* @author	Tommy Lacroix <tlacroix@nuagelab.com>
	* @access	private
	*/
	private function do_change($old, $new, $forceProtocol=null)
	{
		global $wpdb;
		@set_time_limit($_POST['timeout']);
    /* report style: ins/del was here 
    */
		echo '<div class="wrap">';
		echo '<div id="icon-tools" class="icon32"><br></div>';
		echo '<h3>Changing domain</h3>';
		echo '<pre>';
		printf(__('Old domain: %1$s','auto-domain-changer').'<br>', $old);
		printf(__('New domain: %1$s','auto-domain-changer').'<br>', $new);
		$dry_run_text='';
		if ($_POST['dry_run']) {
			$dry_run_text='<b>DRY-RUN: No changes will be made</b>&nbsp;';
			printf(__($dry_run_text.'<br><hr>').'');
		}
		//		$ret = $wpdb->get_results('SHOW TABLES where tables_in_wp_rebi = "wp_postmeta";');
		$ret = $wpdb->get_results('SHOW TABLES;');
		$tables = array();
		foreach ($ret as $row) {
			$row = get_object_vars($row);
			$tables [] = reset($row);
		}
		$total_tables=count($tables);
		$total_changes=0;
		$tables_skipped=0;
		$current_table=0;
		$table_report_summary=array();
		$table_report_detail=array();
//    $sql='select option_value from wp_options where option_id=134888';
//    $ret = $wpdb->get_results($sql);
//    	foreach ($ret as $row) {
//				$row = get_object_vars($row);
//				$raw=$row['option_value'];
//          echo '<p><pre>';
//        echo $raw;
//        echo '<p>';
//          var_dump(unserialize($raw));
//        echo '</pre><p>';
//        wp_die();
//      }
		foreach ($tables as $t) {
			ob_start();
			$table_changes=0;
			$current_table++;
			printf(__('<p>do_change: %1$s. Processing table %2$s','auto-domain-change').'',$current_table, $t);

			// Skip if the table name doesn't match the wordpress prefix
			if (substr($t,0,strlen($wpdb->prefix)) != $wpdb->prefix) {
				$tables_skipped++;
				printf(__('<br>do_change: Skipping table %1$s because no prefix %2$s','auto-domain-change').'', $t,$wpdb->prefix);
				continue;
			}
			// Get table indices
			$ret = $wpdb->get_results('describe '.$t);
			$fields=array();
			foreach ($ret as $row) {
				$row = get_object_vars($row);
				$fields[$row['Field']] =array('type'=>$row['Type'],'null'=>$row['Null'],'default'=>$row['Default']);
			}
			$ret = $wpdb->get_results('SHOW INDEX FROM '.$t);
			$id = null;
			foreach ($ret as $row) {
				$row = get_object_vars($row);
				if ($row['Key_name'] == 'PRIMARY') {
					$id = $row['Column_name'];
					break;
				} else if ($row['Non_unique'] == 0) {
					$id = $row['Column_name'];
				}
			}
			if ($id === null) {
				// No unique index found, skip table.
				$tables_skipped++;
				$table_report_summary[$t]=array('changes'=>0);
				printf(__('<br>do_change: Skipping table %1$s because no unique/binary id','auto-domain-change').'', $t);
				continue;
			}

			$sql = 'DESCRIBE ' . $t . ' ;';
			$res =  $wpdb->get_results( $sql );
			$column_type=array();
			foreach ($res as $row) {
				$row = get_object_vars($row);
				$column_type[$row['Field']]=$row['Type'];
			} //foreach
			// Process all rows
			$o = 0;
			do {
				$sql = 'SELECT * FROM ' . $t. ' LIMIT '.$o.',50;';
				$ret = $wpdb->get_results( $sql );
				foreach ($ret as $row) {
					$row = get_object_vars($row);
					$fields = array();
					$sets   = array();

					// Process all columns
					if ($_POST['change']=='domain') {

						$search_string='/'.preg_quote($old,'/').'/i';
					} else { //search and replace
						$search_string='/'. preg_quote($_POST['search'],'/').'/i';
					}
					//			echo '<br>search is '.$search_string;
          // check column definition to see if it is searchable
					foreach ( $row as $k => $v ) {
						if (!preg_match('/^(CHAR|VARCHAR|TINYTEXT|TEXT|MEDIUMTEXT|LONGTEXT)/i',$column_type[$k])) {
							//		if ($_POST['debug']) printf(__('<br>do_change: Skipping column %1$s with column type %2$s','auto-domain-change').'', $k,$column_type[$k]);
							continue;
						}
						// check and see if search is in column data
						if (!preg_match($search_string,$v)) {
							//if ($_POST['debug']) printf(__('<br>do_change: Skipping column %1$s with no match on domain %2$s','auto-domain-change').'', $k,$old);
							continue;
						}
						// Save original value
						$ov = $v;

						// Process value
						$disp_id=$row[$id];
            //check if column index is binary
            // if yes, rewite key's value so it matches
						if (preg_match('/^(BINARY)/i',$column_type[$id])) {
							$row[$id]="CAST(0x".bin2hex($row[$id])." AS BINARY)";
							$disp_id=bin2hex($row[$id]);
						}
						if ($_POST['debug']) printf(__('<h3 style="margin: 20px 0 5px 0;">Start processing value</h3>','auto-domain-change').'');

						$v = $this->processValue( $v, $old, $new, $forceProtocol,$t,$k,$id,$disp_id );
						//           }



						if ($ov != $v && $v!= NULL) {
							if ($_POST['debug']) printf(__('<br>do_change: A change has been made','auto-domain-change').'');
							//            if ($_POST['debug']) printf(__('<br><b>Original value is</b> %1$s','auto-domain-change').'',htmlentities($ov));
							//						if ($_POST['debug']) printf(__('<br><b>Updated value is</b> %1$s','auto-domain-change').'',htmlentities($v));



							if ($_POST['debug']) {
								//		$this->displayDiff($ov,$v,1024,$t,$k);
							}//debug

						} else { //$ov!=$v
							if ($_POST['debug']) printf(__('<br>do_change: No change has been made','auto-domain-change').'');
						}
						// If value changed, replace it
						if ( $ov != $v && $v!= NULL) {
							$sets[] = '`' . $k . '`="' . esc_sql( $v ) . '"';
							$table_report_detail[$t][]=array('orig'=>$k,'replace'=>esc_sql( $v ));
						}
					} //foreach column

					// Update table if we have something to set
					if ( count( $sets ) > 0 ) {
						$table_changes+=count($sets);
						$total_changes+=count($sets);
            //check if key's value is character
            // if so, quote it so it matches
						if (preg_match('/^(CHAR|VARCHAR|TINYTEXT|TEXT|MEDIUMTEXT|LONGTEXT)/i',$column_type[$id])) $row[$id]="'".$row[$id]."'";
						//							if ($_POST['debug']) printf(__('<br>index is type %1$s','auto-domain-change').'',$column_type[$id]);


						if ($_POST['debug']) printf(__('<br>index %1$s is type %2$s row is %3$s','auto-domain-change').'',$id,$column_type[$id],$row[ $id ]);

						$sql = 'UPDATE ' . $t . ' SET ' . implode( ',', $sets ) . ' WHERE `' . $id . '`=' . $row[ $id ] . ' LIMIT 1;';
						if ($_POST['debug']) printf(__('<br>do_change:'.$dry_run_text.'SQL update is: %1$s','auto-domain-change').'',wordwrap(htmlentities($sql),$_POST['wordwrap'],"<br />",1));
						if (!$_POST['dry_run']){
							$wpdb->get_results($sql);
						}

					}

				} //foreach table row


				$o += count($ret);
			} while (count($ret) > 0);
			printf(__('<br>Table %1$s updates %2$d','auto-domain-change').'',$t,$table_changes);
			$table_report_summary[$t]=array('changes'=>$table_changes);
			$output = ob_get_clean();
			echo $output;
		} //foreach table

		update_option('auto_domain_change-domain', $new);
		echo '</pre>';
		echo '<hr>';
		echo '<form method="post"><input type="submit" value="'.esc_html(__('Back','auto-domain-change')).'" /><p><hr><p>';
		if ($_POST['dry_run']) {
			$dry_run_text='<b>DRY-RUN: No changes have been made</b>&nbsp;';
			printf(__($dry_run_text.'<br><hr>').'<br>');
		}
//added some statistics to end of run report
		printf(__('Total tables %1$s','auto-domain-change').'<br/>',$total_tables);
		printf(__('Tables skipped %1$s','auto-domain-change').'<br/>',$tables_skipped);
		printf(__('Total changes %1$s','auto-domain-change').'<br/>',$total_changes);
		$executionTime = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
		printf(__('Total script execution time %1$d seconds','auto-domain-change').'<br/>',(int)$executionTime);
		$sorted_results=array();
		foreach ($table_report_summary as $key=>$value) {
			if (!$value['changes']) continue;
			$sorted_results[$key]=$value['changes'];
		}
		if (count($sorted_results)) {
			arsort($sorted_results);
			echo '<br><hr>';
			echo '<h3 style="margin: 20px 0 5px 0;">Tables with changes by number of changes descending</h3>';
			foreach ($sorted_results as $key=>$value) {
				printf(__('<br>Table %1$s updates %2$d','auto-domain-change').'',$key,$value);
			}
		} else {
			printf(__('<br>No changes in any tables','auto-domain-change').'');
		}


	} // do_change()

	/**
	* reduce serialized data to strings.
	*
	* @author	Tommy Lacroix <tlacroix@nuagelab.com>
	* @param	mixed		$v		Data to search and replace in
	* @param	string		$old	Old domain name
	* @param	string		$new	New domain name
	* @return	mixed				Modified data
	* @access	private
	*/  
	/* this is where serialized data is broken down in order to search each element 
	* &$v is the column value brought in by reference
	**/
private function processValue(&$v, $old, $new, $forceProtocol,$table,$column,$id,$row)
{
	$save_v=$v;
	$sfalse = serialize(false);
	$jfalse = json_encode(false);
	$jnull = json_encode(null);

	$sv = @unserialize($v);
	$jv =  @json_decode($v,1); //decode as array
	$serialized = $json = false;
	if (($sv !== false) || ($sv == $sfalse)) {
		if ($_POST['debug']) printf(__('<br>processValue:'.$table.' '.$column.' '.$id.'='.$row.' Column value was serialized').'');
		// Column value was serialized
		$v = (array)$sv; //make sure objects are cast to array
		$serialized = true;
	} else if (($jv !== null) && ($jv != $v) && ($jv != $jfalse) && ($jv != $jnull)) {
		// Column value was JSON encoded
		if ($_POST['debug']) printf(__('<br>processValue:'.$table.' '.$column.' '.$id.'='.$row.' Column value was JSON encoded').'');
		$v = $jv;
		$json = true;
	} /*else {
	// Column value was not serialized
	}*/
	//	if (is_object($v)) {
	//		if ($_POST['debug']) printf(__('<br>processValue:'.$table.' '.$column.' '.$id.'='.$row.' is_object - convert to array').'');
	//		$v=json_decode(json_encode($v), True); //force object to array
	//      $this->processValue($v, $old, $new, $forceProtocol,$table,$column,$id,$row);
	//	}
	if (is_array($v)) {
		foreach ($v as $k=>&$vv) {
			if (is_string($vv)) {
				//if ($_POST['debug']) printf(__('<br>processValue:'.$table.' '.$column.' '.$id.'='.$row.' Try and replace').'');
				$save_v=$vv; //save_v is needed to check for changes after replace
				//replace should only see strings
				$v[$k]=$this->replace($vv, $old, $new, $forceProtocol,$table,$column,$id,$row);
				if ($_POST['debug']) {
					if ($save_v!=$vv) {
						if ($_POST['debug']) printf(__('<br>processValue:'.$table.' '.$column.' '.$id.'='.$row.' array part is string').'');
						$this->displayDiff($save_v,$vv,1024,$table,$column);
					}
				}
			} elseif (is_object($vv) || is_array($vv)) { //is_string, object, array
				if (is_object($vv)) $vv=json_decode(json_encode($vv), True); //force object to array
				$v[$k]=$this->processValue($vv, $old, $new, $forceProtocol,$table,$column,$id,$row);
			} else {
				$v[$k]=$vv;
			} // if else not string vv
		} //foreach $v
	} elseif (is_string($v)) { //should be a string here
		$save_v=$v;
		if ($_POST['debug']) printf(__('<br>processValue:'.$table.' '.$column.' '.$id.'='.$row.' processvalue was given string').'');
		//since is string, go and replace
		$v = $this->replace($v, $old, $new, $forceProtocol,$table,$column,$id,$row);
		if ($_POST['debug']) {
			if ($save_v!=$v) {
				$this->displayDiff($save_v,$v,1024,$table,$column);
			} else {
				if ($_POST['debug']) printf(__('<br>processValue:'.$table.' '.$column.' '.$id.'='.$row.' No change').'');
			} //save_v!=v
		} //if debug
	} else { //is array, string, other
		//ignore numbers, null, binary

		//if ($_POST['debug'])
		printf(__('<br>processValue:'.$table.' '.$column.' '.$id.'='.$row.' '.htmlentities($v).' processvalue not string, not array').'');
	} //if array, string, or something else

	// Reserialize if needed
	if ($serialized) $v = serialize($v);
	elseif ($json) $v = json_encode($v);

	return $v;
} //processValue()




	/**
	* Replace domain in data.
	*
	* @author	Tommy Lacroix <tlacroix@nuagelab.com>
	* @param	mixed		$v		Data to search and replace in
	* @param	string		$old	Old domain name
	* @param	string		$new	New domain name
	* @return	mixed				Modified data
	* @access	private
	*/
	private function replace(&$v, $old, $new, $forceProtocol=null,$table,$column,$id,$row)
	{
		//    global $v;
		$protocols = array('http');
		if (get_option('auto_domain_change-https')) $protocols[] = 'https';
		$domains = array($old=>$new);
		if (get_option('auto_domain_change-www')) {
			$hold = preg_replace('/^www\./i', '', $old);
			if (strtolower($hold) != strtolower($old)) $domains[$hold] = $new;
			$hold = 'www.'.$hold;
			if (strtolower($hold) != strtolower($old)) $domains[$hold] = $new;
		}
//		if (is_object($v)) {
      //just to be safe?
//			$v=json_decode(json_encode($v), True);
//		}
		if (is_array($v) ) {
			if ($_POST['debug']) printf(__('<br>>replace:'.$table.' '.$column.' '.$id.'='.$row.' array/object got through re-Replacing').'');
			foreach ($v as $key=>&$vv) {
				$v[$key] =	$this->replace($vv, $old, $new, $forceProtocol,$table,$column,$id,$row);
			}
		} elseif (is_string($v)) {
			//		if ($_POST['debug']) printf(__('<br>>replace:'.$table.' '.$column.' '.$id.'='.$row.' Replacing').'');
			if ($_POST['change']=='domain') {
				$search=array();
				$replace=array();
				foreach ($protocols as $protocol) {
					foreach ($domains as $o=>$n) {
						$toProtocol = $forceProtocol ? $forceProtocol : $protocol;
						$search[]='/'.$protocol.':\/\/'.preg_quote($o,'/').'/i';
						$replace[]=''.$toProtocol.'://'.$n.'';
						if ($_POST['email_domain']) {
							$search[]='/@'.preg_quote($o,'/').'/i';
							$replace[]='@'.$n.'';
						}
					}
				}

				ksort($search);
				ksort($replace);
			} else { //we are searching and replacing
				$search[]='/'.preg_quote($_POST['search'],'/').($_POST['case'] ? '/i':'/');
				$replace[]=''.$_POST['replace'].'';
			}
			$nv = preg_replace($search,$replace, $v);
			if ($nv!=$v) {
				if ($_POST['debug']) printf(__('<br>replace:'.$table.' '.$column.' '.$id.'='.$row.' Successful replace').'');
				$v=$nv;
			}

		}

		return $v;
	} // replace()



	/**

	* Backup database as SQL
	*
	* @author	Tommy Lacroix <tlacroix@nuagelab.com>
	* @access	private
	*/
	private function do_backup_sql()

	{

		global $wpdb;
		@set_time_limit(0);
		$fn = preg_replace('/[^a-zA-Z0-9_\-]/', '_', preg_replace(',http(|s)://,i','',get_bloginfo('url'))).'-'.date('Ymd-His').'.sql';

		header('Content-Type: text/plain; charset="UTF-8"');
		header('Content-Disposition: attachment; filename="'.$fn.'"');

		$ret = $wpdb->get_results('SHOW TABLES;');
		$tables = array();
		foreach ($ret as $row) {
			$row = get_object_vars($row);
			$row = array_values($row);
			$tables [] = reset($row);
		}

		foreach ($tables as $t) {
			// Skip if the table name doesn't match the wordpress prefix
			if (substr($t,0,strlen($wpdb->prefix)) != $wpdb->prefix) continue;
			// Get table indices
			$ret = $wpdb->get_results('SHOW CREATE TABLE '.$t);
			$id = null;
			foreach ($ret as $row) {
				$ct = $row->{'Create Table'};
				echo $ct.';'.PHP_EOL;
			}

			// Process all rows
			$o = 0;
			do {
				$ret = $wpdb->get_results( 'SELECT * FROM ' . $t . ' LIMIT '.$o.',50;' );
				foreach ($ret as $row) {
					$row = get_object_vars($row);
					$ak = array();
					$av = array();
					foreach ( $row as $k => $v ) {
						$ak[] = '`' . esc_sql( $k ) . '`';
						if ( $v === null ) {
							$av[] = 'NULL';
						} else {
							$av[] = '"' . esc_sql( $v ) . '"';
						}
					}

					printf( 'INSERT INTO `%1$s` (%2$s) VALUES (%3$s);' . PHP_EOL, $t, implode( ',', $ak ), implode( ',', $av ) );
				}
				$o += count($ret);
			} while (count($ret) > 0);
			echo PHP_EOL;
			echo PHP_EOL;

		}
		die;

	} // do_backup_sql()

	/**
	* Backup database as PHP
	*
	* @author	Tommy Lacroix <tlacroix@nuagelab.com>
	* @access	private
	*/

	private function do_backup_php()
	{
		global $wpdb;
		@set_time_limit(0);
		$fn = preg_replace('/[^a-zA-Z0-9_\-]/', '_', preg_replace(',http(|s)://,i','',get_bloginfo('url'))).'-'.date('Ymd-His').'.php';

		header('Content-Type: text/plain; charset="UTF-8"');
		header('Content-Disposition: attachment; filename="'.$fn.'"');
		echo '<?php'.PHP_EOL;
		echo '// Put this file at the root of your WordPress installation and execute it.'.PHP_EOL.PHP_EOL;
		echo 'require "wp-config.php";' . PHP_EOL;
		echo 'global $wpdb;' . PHP_EOL;

		$ret = $wpdb->get_results('SHOW TABLES;');
		$tables = array();
		foreach ($ret as $row) {
			$row = get_object_vars($row);
			$row = array_values($row);
			$tables [] = reset($row);
		}

		$ifdrop = 'if (isset($_GET["drop"])) $wpdb->get_var("DROP TABLE IF EXISTS `%T%`;"); if ($wpdb->last_error) die("Query failed");'.PHP_EOL;
		foreach ($tables as $t) {
			// Skip if the table name doesn't match the wordpress prefix
			if (substr($t,0,strlen($wpdb->prefix)) != $wpdb->prefix) continue;
			// Get table indices
			$ret = $wpdb->get_results('SHOW CREATE TABLE '.$t);
			$id = null;
			foreach ($ret as $row) {
				$ct = $row->{'Create Table'};
				echo str_replace('%T%', $t, $ifdrop);
				printf('$wpdb->get_results('.var_export($ct, true).'); if ($wpdb->last_error) die("Query failed");');
				echo PHP_EOL;
			}

			// Process all rows
			$o = 0;
			do {
				$ret = $wpdb->get_results( 'SELECT * FROM ' . $t . ' LIMIT ' . $o . ',50;' );
				foreach ( $ret as $row ) {
					$row = get_object_vars( $row );
					$ak  = array();
					$av  = array();
					foreach ( $row as $k => $v ) {
						$ak[] = '`' . esc_sql( $k ) . '`';
						if ( $v === null ) {
							$av[] = 'NULL';
						} else {
							$av[] = '"' . esc_sql( $v ) . '"';
						}
					}
					$query = sprintf( 'INSERT INTO `%1$s` (%2$s) VALUES (%3$s);', $t, implode( ',', $ak ), implode( ',', $av ) );
					echo '$wpdb->get_results(' . var_export($query,true). '); if ($wpdb->last_error) die("Query failed");';
					echo PHP_EOL;
				}
				$o += count($ret);
			} while (count($ret) > 0);

			echo PHP_EOL;
			echo PHP_EOL;

		}

		die;
	} // do_backup_php()

	private function displayDiff($ov,$v,$max_str_size=1024,$table,$column) {
		//							$max_str_size=1024;
		$str_size=strlen($ov);
		if ($str_size>$max_str_size) {
			$run_times=(int)($str_size/$max_str_size);
			$run_remainder=$str_size % $max_str_size;
			echo '<br><br>'.$table.' '.$column.' str_size = '.$str_size.' run_times = '.$run_times.' run_remainder = '.$run_remainder.'<br>';
			for ($i=0;$i<=$run_times;$i++) {
				$start=$i*$max_str_size;
				$disp_ov=htmlentities(substr($ov,$start,$max_str_size));
				$disp_v=htmlentities(substr($v,$start,$max_str_size));
				printf(__('<br><b>'.$table.' '.$column.' disp_ov</b><br><hr>%1$s','auto-domain-change').'',wordwrap($disp_ov,$_POST['wordwrap'],"<br />",1)).'<br><hr>';
				printf(__('<br><b>'.$table.' '.$column.'  disp_v</b><br><hr>%1$s','auto-domain-change').'',wordwrap($disp_v,$_POST['wordwrap'],"<br />",1)).'<br><hr>';
				printf(__('<br><b>'.$table.' '.$column.' Changes</b><br><hr>%1$s','auto-domain-change').'',wordwrap($this->diffline($disp_ov,$disp_v),$_POST['wordwrap'],"<br />\n",1)).'<br><hr>';

			} //for
			$disp_ov=htmlentities(substr($ov,$i,$run_remainder));
			$disp_v=htmlentities(substr($v,$i,$run_remainder));
			printf(__('<br><b>'.$table.' '.$column.' Changes</b><br><hr>%1$s','auto-domain-change').'',wordwrap($this->diffline($disp_ov,$disp_v),$_POST['wordwrap'],"<br />\n",1)).'<br><hr>';
		} else { //string size<max_str_size
			printf(__('<br><br><b>'.$table.' '.$column.' Changes</b><br><hr>%1$s','auto-domain-change').'',$this->diffline(htmlentities($ov),htmlentities($v))).'<br><hr>';
		}//string_size
		echo '<br><hr>';
	}//displayDiff()
	private function computeDiff($from, $to)
	{
		$diffValues = array();
		$diffMask = array();

		$dm = array();
		$n1 = count($from);
		$n2 = count($to);

		for ($j = -1; $j < $n2; $j++) $dm[-1][$j] = 0;
		for ($i = -1; $i < $n1; $i++) $dm[$i][-1] = 0;
		for ($i = 0; $i < $n1; $i++)
		{
			for ($j = 0; $j < $n2; $j++)
			{
				if ($from[$i] == $to[$j])
				{
					$ad = $dm[$i - 1][$j - 1];
					$dm[$i][$j] = $ad + 1;
				}
				else
				{
					$a1 = $dm[$i - 1][$j];
					$a2 = $dm[$i][$j - 1];
					$dm[$i][$j] = max($a1, $a2);
				}
			}
		}

		$i = $n1 - 1;
		$j = $n2 - 1;
		while (($i > -1) || ($j > -1))
		{
			if ($j > -1)
			{
				if ($dm[$i][$j - 1] == $dm[$i][$j])
				{
					$diffValues[] = $to[$j];
					$diffMask[] = 1;
					$j--;
					continue;
				}
			}
			if ($i > -1)
			{
				if ($dm[$i - 1][$j] == $dm[$i][$j])
				{
					$diffValues[] = $from[$i];
					$diffMask[] = -1;
					$i--;
					continue;
				}
			}
			{
				$diffValues[] = $from[$i];
				$diffMask[] = 0;
				$i--;
				$j--;
			}
		}

		$diffValues = array_reverse($diffValues);
		$diffMask = array_reverse($diffMask);

		return array('values' => $diffValues, 'mask' => $diffMask);
	} //computediff()

	private function diffline($line1, $line2)
	{
		$diff = $this->computeDiff(str_split($line1), str_split($line2));
		$diffval = $diff['values'];
		$diffmask = $diff['mask'];

		$n = count($diffval);
		$pmc = 0;
		$result = '';
		for ($i = 0; $i < $n; $i++)
		{
			$mc = $diffmask[$i];
			if ($mc != $pmc)
			{
				switch ($pmc)
				{
					case -1: $result .= '</del>'; break;
					case 1: $result .= '</ins>'; break;
				}
				switch ($mc)
				{
					case -1: $result .= '<del>'; break;
					case 1: $result .= '<ins>'; break;
				}
			}
			$result .= $diffval[$i];

			$pmc = $mc;
		}
		switch ($pmc)
		{
			case -1: $result .= '</del>'; break;
			case 1: $result .= '</ins>'; break;
		}

		return $result;
	} //  diffline()
} //auto-domain_change class
// Initialize
auto_domain_change::boot();

