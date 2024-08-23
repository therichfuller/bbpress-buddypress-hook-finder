<?php


//db stuff


function bp_hf_hooks_db_hook_count() {
	global $wpdb, $bp;

	$a = (int)$wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM ". $wpdb->base_prefix ."hf_bp_do_action WHERE bp_version = %s", HOOK_BP_VERSION ) );
	$f = (int)$wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM ". $wpdb->base_prefix ."hf_bp_apply_filters WHERE bp_version = %s", HOOK_BP_VERSION ) );
	
	return $a+$f;
}


function bp_hf_hooks_groups_loop_doactions() {
	global $wpdb, $bp;

	return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ". $wpdb->base_prefix ."hf_bp_do_action WHERE bp_version = %s  GROUP BY action_tag", HOOK_BP_VERSION ) );
}

function bp_hf_hooks_groups_loop_applyfilters() {
	global $wpdb, $bp;

	return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ". $wpdb->base_prefix ."hf_bp_apply_filters WHERE bp_version = %s  GROUP BY filter_tag", HOOK_BP_VERSION ) );
}


function bp_hf_hooks_groups_loop_doaction() {
	global $wpdb, $bp;

	return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ". $wpdb->base_prefix ."hf_bp_do_action WHERE bp_version = %s AND action_tag = %s", HOOK_BP_VERSION, $bp->action_variables[1] ) );
}
function bp_hf_hooks_groups_loop_doaction_by( $data ) {
	global $wpdb, $bp;

	return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ". $wpdb->base_prefix ."hf_bp_do_action WHERE bp_version = %s AND action_tag = %s", HOOK_BP_VERSION, $data ) );
}

function bp_hf_hooks_groups_loop_applyfilter() {
	global $wpdb, $bp;

	return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ". $wpdb->base_prefix ."hf_bp_apply_filters WHERE bp_version = %s AND filter_tag = %s", HOOK_BP_VERSION, $bp->action_variables[1] ) );
}
function bp_hf_hooks_groups_loop_applyfilter_by( $data ) {
	global $wpdb, $bp;

	return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ". $wpdb->base_prefix ."hf_bp_apply_filters WHERE bp_version = %s AND filter_tag = %s", HOOK_BP_VERSION, $data ) );
}


function bp_hf_hooks_groups_loop_doaction_source( $id ) {
	global $wpdb, $bp;

	return $wpdb->get_results( $wpdb->prepare( "SELECT s.*, f.file_path, f.component, f.core, f.plugin_path, f.svn_path FROM ". $wpdb->base_prefix ."hf_bp_do_action_source_child s, ". $wpdb->base_prefix ."hf_bp_files f  WHERE s.file_id = f.id AND s.do_action_id = %d", $id ) );
}

function bp_hf_hooks_groups_loop_applyfilters_source( $id ) {
	global $wpdb, $bp;

	return $wpdb->get_results( $wpdb->prepare( "SELECT s.*, f.file_path, f.component, f.core, f.plugin_path, f.svn_path FROM ". $wpdb->base_prefix ."hf_bp_apply_filters_source_child s, ". $wpdb->base_prefix ."hf_bp_files f  WHERE s.file_id = f.id AND s.apply_filters_id = %d", $id ) );
}


function bp_hf_hooks_groups_loop_addaction() {
	global $wpdb, $bp;

	return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ". $wpdb->base_prefix ."hf_bp_add_action WHERE bp_version = %s AND action_tag = %s", HOOK_BP_VERSION, $bp->action_variables[1] ) );
}

function bp_hf_hooks_groups_loop_addaction_by( $data ) {
	global $wpdb, $bp;

	return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ". $wpdb->base_prefix ."hf_bp_add_action WHERE bp_version = %s AND action_tag = %s", HOOK_BP_VERSION, $data ) );
}

function bp_hf_hooks_groups_loop_addfilter() {
	global $wpdb, $bp;

	return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ". $wpdb->base_prefix ."hf_bp_add_filter WHERE bp_version = %s AND filter_tag = %s", HOOK_BP_VERSION, $bp->action_variables[1] ) );
}
function bp_hf_hooks_groups_loop_addfilter_by( $data ) {
	global $wpdb, $bp;

	return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ". $wpdb->base_prefix ."hf_bp_add_filter WHERE bp_version = %s AND filter_tag = %s", HOOK_BP_VERSION, $data ) );
}


function bp_hf_hooks_groups_loop_addaction_source( $id ) {
	global $wpdb, $bp;

	return $wpdb->get_results( $wpdb->prepare( "SELECT s.*, f.file_path, f.component, f.core, f.plugin_path, f.svn_path FROM ". $wpdb->base_prefix ."hf_bp_add_action_source_child s, ". $wpdb->base_prefix ."hf_bp_files f  WHERE s.file_id = f.id AND s.add_action_id = %d", $id ) );
}

function bp_hf_hooks_groups_loop_addfilter_source( $id ) {
	global $wpdb, $bp;

	return $wpdb->get_results( $wpdb->prepare( "SELECT s.*, f.file_path, f.component, f.core, f.plugin_path, f.svn_path FROM ". $wpdb->base_prefix ."hf_bp_add_filter_source_child s, ". $wpdb->base_prefix ."hf_bp_files f  WHERE s.file_id = f.id AND s.add_filter_id = %d", $id ) );
}


function bp_hf_hooks_groups_loop_function_source( $id ) {
	global $wpdb, $bp;

	return $wpdb->get_row( $wpdb->prepare( "SELECT s.*, f.file_path, f.component, f.core, f.plugin_path, f.svn_path FROM ". $wpdb->base_prefix ."hf_bp_function_source s, ". $wpdb->base_prefix ."hf_bp_files f  WHERE s.file_id = f.id AND s.function = %s", $id ) );
}



function bp_hf_hooks_groups_search_doaction_file( ) {
	global $wpdb, $bp;

	if ( !isset( $_REQUEST['f'] ) )
		return false;
		
	if ( !bp_hf_hooks_endswith( $_REQUEST['f'], '.php') )
		return false;

	return $wpdb->get_results( $wpdb->prepare( "SELECT f.file_path, f.component, f.core, f.plugin_path, f.svn_path, da.* FROM ". $wpdb->base_prefix ."hf_bp_do_action_source_child ds, ". $wpdb->base_prefix ."hf_bp_do_action da, ". $wpdb->base_prefix ."hf_bp_files f WHERE ds.do_action_id = da.id AND f.id = ds.file_id AND f.plugin_path = %s AND da.bp_version = %s GROUP BY da.action_tag", $_REQUEST['f'], HOOK_BP_VERSION ) );
}

function bp_hf_hooks_groups_search_applyfilters_file( ) {
	global $wpdb, $bp;

	if ( !isset( $_REQUEST['f'] ) )
		return false;

	if ( !bp_hf_hooks_endswith( $_REQUEST['f'], '.php') )
		return false;

	return $wpdb->get_results( $wpdb->prepare( "SELECT f.file_path, f.component, f.core, f.plugin_path, f.svn_path, da.* FROM ". $wpdb->base_prefix ."hf_bp_apply_filters_source_child ds, ". $wpdb->base_prefix ."hf_bp_apply_filters da, ". $wpdb->base_prefix ."hf_bp_files f WHERE ds.apply_filters_id = da.id AND f.id = ds.file_id AND f.plugin_path = %s AND da.bp_version = %s GROUP BY da.filter_tag", $_REQUEST['f'], HOOK_BP_VERSION ) );
}

function bp_hf_hooks_groups_search_doaction_fileinfo() {
	global $wpdb, $bp;

	if ( !isset( $_REQUEST['f'] ) )
		return false;
		
	if ( !bp_hf_hooks_endswith( $_REQUEST['f'], '.php') )
		return false;

	return $wpdb->get_row( $wpdb->prepare( "SELECT f.file_path, f.component, f.core, f.plugin_path, f.svn_path FROM ". $wpdb->base_prefix ."hf_bp_files f WHERE f.plugin_path = %s AND f.bp_version = %s", $_REQUEST['f'], HOOK_BP_VERSION ) );
}


function bp_hf_hooks_groups_search_doaction_component( ) {
	global $wpdb, $bp;

	if ( !isset( $_REQUEST['c'] ) )
		return false;

	return $wpdb->get_results( $wpdb->prepare( "SELECT f.file_path, f.component, f.core, f.plugin_path, f.svn_path, da.* FROM ". $wpdb->base_prefix ."hf_bp_do_action_source_child ds, ". $wpdb->base_prefix ."hf_bp_do_action da, ". $wpdb->base_prefix ."hf_bp_files f WHERE ds.do_action_id = da.id AND f.id = ds.file_id AND f.component = %s AND da.bp_version = %s GROUP BY da.action_tag", $_REQUEST['c'], HOOK_BP_VERSION ) );
}

function bp_hf_hooks_groups_search_applyfilters_component( ) {
	global $wpdb, $bp;

	if ( !isset( $_REQUEST['c'] ) )
		return false;

	return $wpdb->get_results( $wpdb->prepare( "SELECT f.file_path, f.component, f.core, f.plugin_path, f.svn_path, da.* FROM ". $wpdb->base_prefix ."hf_bp_apply_filters_source_child ds, ". $wpdb->base_prefix ."hf_bp_apply_filters da, ". $wpdb->base_prefix ."hf_bp_files f WHERE ds.apply_filters_id = da.id AND f.id = ds.file_id AND f.component = %s AND da.bp_version = %s GROUP BY da.filter_tag", $_REQUEST['c'], HOOK_BP_VERSION ) );
}

function bp_hf_hooks_groups_search_doaction_componentinfo() {
	global $wpdb, $bp;

	if ( !isset( $_REQUEST['c'] ) )
		return false;

	return $wpdb->get_row( $wpdb->prepare( "SELECT f.file_path, f.component, f.core, f.plugin_path, f.svn_path FROM ". $wpdb->base_prefix ."hf_bp_files f WHERE f.component = %s AND f.bp_version = %s", $_REQUEST['c'], HOOK_BP_VERSION ) );
}



//add_filter( 'bp_hf_hooks_groups_source_shortcodeit', 'convert_chars' );
//add_filter( 'bp_hf_hooks_groups_source_shortcodeit', 'stripslashes_deep' );
//add_filter( 'bp_hf_hooks_groups_source_shortcodeit', 'bp_forums_filter_kses', 1 );
//add_filter( 'bp_hf_hooks_groups_source_shortcodeit', 'force_balance_tags' );
//add_filter( 'bp_hf_hooks_groups_source_shortcodeit', 'wpautop' );


function bp_hf_hooks_groups_source_shortcodeit( $source, $startline, $line ) {

	//we need to loop over the source array and merge it, and then add the shortcode, then process the syntaxhighlighter

	//$source = implode ("\r\n", $source);
	$source = implode ("", $source);
	
	$startline = $startline + 1;

	$source = '[php firstline="'. $startline .'" toolbar="false" highlight="'. $line .'"]' . $source . '[/php]';

	echo bp_get_hf_hooks_groups_source_shortcodeit( $source );
}

function bp_get_hf_hooks_groups_source_shortcodeit( $source ) {
	return apply_filters( 'bp_hf_hooks_groups_source_shortcodeit', stripslashes( $source ) );
	//return apply_filters( 'bp_hf_hooks_groups_source_shortcodeit', $source );
}

function bp_hf_hooks_groups_source_shortcodeit_raw( $source, $startline, $line ) {

	$source = implode ("", $source);
	
	$startline = $startline + 1;

	$source = '[php firstline="'. $startline .'" toolbar="false" highlight="'. $line .'"]' . $source . '[/php]';

	return $source;
}


function bp_hf_hooks_endswith($haystack,$needle,$case=true) {
    if($case){return (strcmp(substr($haystack, strlen($haystack) - strlen($needle)),$needle)===0);}
    return (strcasecmp(substr($haystack, strlen($haystack) - strlen($needle)),$needle)===0);
}
?>
