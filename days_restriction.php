<?php
/*
Plugin Name: Days Restriction
Version: 3.0
Description: This Plugin restricts the pages, posts to manage after some interval of days. 

*/

require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/josefwordpress/days-restriction',
	__FILE__,
	'unique-plugin-or-theme-slug'
);

//Optional: Set the branch that contains the stable release.
$myUpdateChecker->setBranch('master');

if (is_admin())
   {   
      function form_admin_restrict_data() 
		{  
			
			add_menu_page("Restrict Data","Restrict Data",1,"restrict_days","restrict_days");
			
			/* add_submenu_page("restrict_days","Restrict Data","Restrict Data",2,"Restrict Data","restrict_days"); */
	
		}   
       add_action('admin_menu','form_admin_restrict_data'); 
   }
   	function restrict_days()
	{
		global $wpdb;

		if(isset($_POST['submit']))
		{
			$restrict_days=$_POST['restrict_days'];
			$table_name=$wpdb->prefix."restrict_data";
			$wpdb->update($table_name, array('restrict_days'=>$restrict_days), array('restrict_id'=>1));
		}
		 $querystr = "SELECT restrict_days
					  FROM ".$wpdb->prefix."restrict_data";
		 $result = $wpdb->get_row($querystr);
		?>

				<div class="wp-form">
				<h2 class="defHeading">Restriction Panel</h2>
					<form name="form1" action="" enctype="multipart/form-data" method="post">
						<fieldset>
							 <table>
								<tr>
									<td>Number of Days</td>
									<td><input type='number' name='restrict_days' value='<?php echo $result->restrict_days; ?>'></td>
								</tr>
								<tr>
									<td><input type='submit' name='submit' value='Submit'></td>
								</tr>
							 </table>
						</fieldset>
					</form>
				</div>
		<?php
	}

// add the action 
 
function current_screen( $current_screen ) {
/*  	echo "<pre>";
	print_r($current_screen);echo "</pre>"; */
	//die;  
	if (!current_user_can('administrator')) {
			add_filter('post_row_actions','my_disable_quick_edit',10, 1);
			add_filter('page_row_actions', 'my_disable_quick_edit', 10, 2 );
			//wp_die( __('Sorry, you are not allowed.'), 403 );
	}
	
	
	
	if ($current_screen->post_type == 'post' && ($current_screen->base == 'post' || $_REQUEST['action'] == 'edit' || $_REQUEST['action'] == 'trash')) {
		
		
		if (!current_user_can('administrator')) {
			
			 $date = get_the_time('Y-m-d', $_REQUEST['post']);
			 //Get Days
			 global $wpdb;
			 $querystr = "SELECT restrict_days
						  FROM ".$wpdb->prefix."restrict_data";
			 $result = $wpdb->get_row($querystr);
			 $restrict_days =$result->restrict_days;
			 
			 $current_date=date('Y-m-d');
			 $post_restrict_date= date('Y-m-d', strtotime($date. ' + '.$restrict_days.' days')); 
			 
			 if(strtotime($post_restrict_date)<strtotime($current_date))
			 {
				 wp_die( __('Sorry, you are not allowed.'), 403 );
			 }
		}
	} 

	if ($current_screen->post_type == 'page' && ($current_screen->base == 'page' || $_REQUEST['action'] == 'trash' || $_REQUEST['action'] == 'edit')) {
		$date = get_the_time('Y-m-d', $_REQUEST['post']);
		if (!current_user_can( 'administrator' )) {
			$date = get_the_time('Y-m-d', $_REQUEST['post']);
			 //Get Days
			 global $wpdb;
			 $querystr = "SELECT restrict_days
						  FROM ".$wpdb->prefix."restrict_data";
			 $result = $wpdb->get_row($querystr);
			 $restrict_days =$result->restrict_days;
			 
			 $current_date=date('Y-m-d');
			 $post_restrict_date= date('Y-m-d', strtotime($date. ' + '.$restrict_days.' days')); 
			 
			 if(strtotime($post_restrict_date)<strtotime($current_date))
			 {
				 wp_die( __('Sorry, you are not allowed.'), 403 );
			 }
		}
	} 
	/* if ($current_screen->base == 'edit-tags') { 
		//echo $current_screen->base;die;
		if (!current_user_can( 'administrator' )) {
			 wp_die( __('Sorry, you are not allowed.'), 403 );
			/* if ($current_screen->post_type == 'post' && ($current_screen->base == 'term' || $_REQUEST['action'] == 'edit' || $_REQUEST['action'] == 'trash')) {
				echo "<pre>";
				print_r($current_screen);echo "</pre>"; die;
			}
			 echo "<pre>";
			 print_r($current_screen);echo "</pre>"; die; */
			//die;
			//echo $date = get_the_time('Y-m-d', $_REQUEST['post']);die;
			//wp_die( __('Sorry, you are not allowed.'), 403 );
		/* }
	} */
    if ($current_screen->base == 'toplevel_page_restrict_days') {
		if (!current_user_can( 'administrator' )) {
			wp_die( __('Sorry, you are not allowed.'), 403 );
		}
	}  
}
add_action( 'current_screen', 'current_screen' );  


function my_disable_quick_edit( $actions = array(), $post = null ) {

    // Remove the Quick Edit link
    if ( isset( $actions['inline hide-if-no-js'] ) ) {
        unset( $actions['inline hide-if-no-js'] );
    }

    // Return the set of links without Quick Edit
    return $actions;

}

add_action( 'admin_menu', 'remove_menu_links' ); 
function remove_menu_links() {
    global $submenu;
	if (!current_user_can( 'administrator' )) {
		remove_menu_page('upload.php');
		remove_menu_page('tools.php');
		foreach( $submenu['upload.php'] as $position => $data ) {
			$submenu['upload.php'][$position][1] = 'desired cap here';
		}
	}
}

function my_plugin_create_table()
{

	global $wpdb;
 
	// this if statement makes sure that the table does not exist already
	$sql = "CREATE TABLE ".$wpdb->prefix."restrict_data (
			restrict_id INT(1) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			restrict_days INT(1) NOT NULL
			)";
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	
	$wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "restrict_data"); 

	$wpdb->num_rows;
	
	if($wpdb->num_rows<1)
	{
		$wpdb->insert($wpdb->prefix."restrict_data", array(
			'restrict_days' => '1'
		));
	}
	
}
// this hook will cause our creation function to run when the plugin is activated
register_activation_hook( __FILE__, 'my_plugin_create_table' );