<?php
/*
Plugin Name: etivite bbPress Action & Filter API Parser
Plugin URI: http://etivite.com
Description: Find all filters and action hooks for bbPress 2.*
Version: 0.0.3
Author: rich @ etivite
Author URI: http://buddypress.org/developers/etivite/
*/

ini_set('auto_detect_line_endings',true);
ini_set('memory_limit','64M');
//ini_set('max_execution_time', 300);

ignore_user_abort(true); 
set_time_limit(0);


define ( 'HOOKFINDER_BBP_DB_VERSION', '1090' );

function bbp_hookfinder_files() {
	global $wpdb, $bbp, $wp_version, $hookfunctions;

	$hookfunctions = array();

$wpdb->query("TRUNCATE ". $wpdb->base_prefix ."hf_bbp_add_action");
$wpdb->query("TRUNCATE ". $wpdb->base_prefix ."hf_bbp_add_action_source_child");
$wpdb->query("TRUNCATE ". $wpdb->base_prefix ."hf_bbp_add_filter");
$wpdb->query("TRUNCATE ". $wpdb->base_prefix ."hf_bbp_add_filter_source_child");
$wpdb->query("TRUNCATE ". $wpdb->base_prefix ."hf_bbp_apply_filters");
$wpdb->query("TRUNCATE ". $wpdb->base_prefix ."hf_bbp_apply_filters_source_child");
$wpdb->query("TRUNCATE ". $wpdb->base_prefix ."hf_bbp_do_action");
$wpdb->query("TRUNCATE ". $wpdb->base_prefix ."hf_bbp_do_action_source_child");
$wpdb->query("TRUNCATE ". $wpdb->base_prefix ."hf_bbp_files");
$wpdb->query("TRUNCATE ". $wpdb->base_prefix ."hf_bbp_function_source");
$wpdb->query("TRUNCATE ". $wpdb->base_prefix ."hf_bbp_svn");

$wpdb->query( $wpdb->prepare("INSERT INTO ". $wpdb->base_prefix . "hf_bbp_svn ( bbp_version, svn_url ) VALUES ( %s, %s )", bbp_get_version(), "http://bbpress.trac.wordpress.org/browser/tags/". bbp_get_version() ."/") );

echo '<p><strong>bbPress</strong><br/></p>';

	foreach ( bbp_hookfinder_findfiles( $bbp->plugin_dir ) as $key=>$file ) {

		$file = str_replace( "bbpress//", "bbpress/", $file );

		/*
		* store file info
		*/
		
		//for now just replace the windoze vs nix file path - on the front end we'll filter out the baseurl and grab just buddypress/ and down for trac urls
		$filerow = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ". $wpdb->base_prefix ."hf_bbp_files WHERE file_path = %s AND bbp_version = %s", $file, bbp_get_version() ) );
		if ( !$filerow ) {

			$lfile = substr( $file, 39, strlen( $file) );

			if ( strpos( $lfile, "/plugins/bbpress" ) === false ) {

				$pluginpath = $lfile;
				$svnpath = 'http://core.trac.wordpress.org/browser/tags/'. $wp_version . $lfile;
				$core = "WordPress";

			} else {



				$lfile = str_replace( "wp-content/plugins/bbpress/", "", $lfile );

				$pluginpath = $lfile;
				$svnpath = 'http://bbpress.trac.wordpress.org/browser/tags/'. bbp_get_version() . $lfile;

				$core = "bbPress";
				
				if ( strpos( $lfile, "/bbp-admin" ) !== false ) {
					$component = "bbp-admin";
				} else if ( strpos( $lfile, "/bbp-themes" ) !== false ) {
					$component = "bbp-themes";
				} else if ( strpos( $lfile, "/bbp-forum-functions" ) !== false ) {
					$component = "bbp-forum";
				} else if ( strpos( $lfile, "/bbp-forum-template" ) !== false ) {
					$component = "bbp-forum";
				} else if ( strpos( $lfile, "/bbp-reply-functions" ) !== false ) {
					$component = "bbp-reply";
				} else if ( strpos( $lfile, "/bbp-reply-template" ) !== false ) {
					$component = "bbp-reply";
				} else if ( strpos( $lfile, "/bbp-topic-functions" ) !== false ) {
					$component = "bbp-topic";
				} else if ( strpos( $lfile, "/bbp-topic-template" ) !== false ) {
					$component = "bbp-topic";
				} else if ( strpos( $lfile, "/bbp-user-functions" ) !== false ) {
					$component = "bbp-user";
				} else if ( strpos( $lfile, "/bbp-user-template" ) !== false ) {
					$component = "bbp-user";
				} else {
					$component = "bbp-core";
				}

			}
		
			$q = $wpdb->prepare( "INSERT INTO ". $wpdb->base_prefix ."hf_bbp_files ( file_path, bbp_version, svn_path, plugin_path, core, component ) VALUES ( %s, %s, %s, %s, %s, %s )", $file, bbp_get_version(), $svnpath, $pluginpath, $core, $component );
			$wpdb->query($q);
		}
		
		
		
			
		if ( empty( $filerow->id ) )
			$filerow->id = $wpdb->insert_id;
		
		/*
		* open file to parse out hooks
		*/
		
		$sourcelines = file( $file );
		$sourcebyline = $sourcelines;
 
 		$l = 1;
		foreach ( $sourcebyline as $line ) {
		
			/*
			* find hooks on each line
			*/
		   
		    if ( !bbp_hookfinder_startsWith($line, '//') && !bbp_hookfinder_startsWith($line, ' *') ) {
		   
				//do_action( $tag, $arg );
				preg_match_all('/do_action\((.*)\)/i', $line, $matches);
				if ( $matches ) {
					
					foreach($matches[1] as $key => $val) {
					
						if ( !bbp_hookfinder_startsWith( ltrim( $matches[0][$key]), 'do_action()' ) ) {

$matches[0][$key] = htmlspecialchars(ltrim( $matches[0][$key]), ENT_NOQUOTES);

							$pieces = explode(",", $val);
							$tag = str_replace( "'", "", trim( $pieces[0] ) );
				
							$args = array_slice($pieces, 1); 

//if no opening ( - remove closing ) that is overwhelmed with simple regex
/*
foreach ( $args as $akey => $aval ) {
	if ( false !== strpos( $aval, '(' ) ) {
		if ( bbp_hookfinder_endsWith( rtrim( $aval ), ')' ) ) $args[ $akey ] = substr( rtrim( $aval ), 0, -1);
	}
}
*/
							
							$doactionrow = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ". $wpdb->base_prefix ."hf_bbp_do_action WHERE action_raw_call = %s AND action_tag = %s AND bbp_version = %s", $matches[0][$key], $tag, bbp_get_version() ) );
							if ( !$doactionrow ) {
								$q = $wpdb->prepare( "INSERT INTO ". $wpdb->base_prefix ."hf_bbp_do_action ( action_raw_call, action_tag, action_arg, action_arg_num, did_action, bbp_version ) VALUES ( %s, %s, %s, %d, %d, %s )", $matches[0][$key], $tag, maybe_serialize( $args ), count( $args ), did_action( $tag ), bbp_get_version() );
								$wpdb->query($q);
//echo '<p>inserting: '. $matches[0][$key] .'<br/></p>';
							}

							if ( empty( $doactionrow->id ) )
								$doactionrow->id = $wpdb->insert_id;

							
							//grab some source code around the do_action call
							if ($l < 11) {
								$code_start = 0;
							} else {
								$code_start = $l - 10;
							}
							if ( ($l + 10) > count($sourcelines) ) {
								$length = count($sourcelines) - $l;
							} else {
								$length = 20;
							}
							$code_end = $length + $code_start;

							$a = array_slice($sourcelines, $code_start, $code_end - $code_start, true);

							$doactionchildrow = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ". $wpdb->base_prefix ."hf_bbp_do_action_source_child WHERE do_action_id = %d AND line_num = %d", $doactionrow->id, $l ) );
							if ( !$doactionchildrow ) {
								$q = $wpdb->prepare( "INSERT INTO ". $wpdb->base_prefix ."hf_bbp_do_action_source_child ( do_action_id, line_num, file_id, source, start_line, end_line ) VALUES ( %d, %d, %d, %s, %d, %d )", $doactionrow->id, $l, $filerow->id, maybe_serialize( $a ), $code_start, $code_end );
								$wpdb->query($q);
							}
						
						}

					}
				
				}
				
				
				//add_action( $tag, $function_to_add, $priority, $accepted_args );
				preg_match_all('/add_action\((.*)\)/i', $line, $addmatches);
				if ( $addmatches ) {
					
					foreach($addmatches[1] as $key => $val) {
					
						if ( !bbp_hookfinder_startsWith( ltrim( $addmatches[0][$key]), 'add_action()' ) ) {

$addmatches[0][$key] = htmlspecialchars(ltrim( $addmatches[0][$key]), ENT_NOQUOTES);

							$pieces = explode(",", $val);
							$tag = str_replace( "'", "", trim( $pieces[0] ) );
							$func = str_replace( "'", "", trim( $pieces[1] ) );
				
							$pri = 0;
							if ( $pieces[2] )
								 $pri = trim( $pieces[2] );
							
							$args = 0;
							if ( $pieces[3] )
								 $args = trim( $pieces[3] );
							
							$addactionrow = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ". $wpdb->base_prefix ."hf_bbp_add_action WHERE action_raw_call = %s AND action_tag = %s AND bbp_version = %s", $addmatches[0][$key], $tag, bbp_get_version() ) );
							if ( !$addactionrow ) {
								$q = $wpdb->prepare( "INSERT INTO ". $wpdb->base_prefix ."hf_bbp_add_action ( action_raw_call, action_tag, function_call, priority, action_arg_num, bbp_version ) VALUES ( %s, %s, %s, %d, %d, %s )", $addmatches[0][$key], $tag, $func, $pri, $args, bbp_get_version() );
								$wpdb->query($q);
							}
	
							if ( empty( $addactionrow->id ) )
								$addactionrow->id = $wpdb->insert_id;
	
							//grab some source code around the do_action call
							if ($l < 11) {
								$code_start = 0;
							} else {
								$code_start = $l - 10;
							}
							if ( ($l + 10) > count($sourcelines) ) {
								$length = count($sourcelines) - $l;
							} else {
								$length = 20;
							}
							$code_end = $length + $code_start;
	
							$a = array_slice($sourcelines, $code_start, $code_end - $code_start, true);
							
							$addactionchildrow = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ". $wpdb->base_prefix ."hf_bbp_add_action_source_child WHERE add_action_id = %d AND line_num = %d", $addactionrow->id, $l ) );
							if ( !$addactionchildrow ) {
								$q = $wpdb->prepare( "INSERT INTO ". $wpdb->base_prefix ."hf_bbp_add_action_source_child ( add_action_id, line_num, file_id, source, start_line, end_line ) VALUES ( %d, %d, %d, %s, %d, %d )", $addactionrow->id, $l, $filerow->id, maybe_serialize( $a ), $code_start, $code_end );
								$wpdb->query($q);
							}
						
						}

					}
				
				}
				
				//apply_filters( $tag, $value );
				//can't comma delimit due to chaining functions inline
				preg_match_all('/apply_filters\((.*)\)/i', $line, $filtermatches);
				if ( $filtermatches ) {
					
					foreach($filtermatches[1] as $key => $val) {

						if ( !bbp_hookfinder_startsWith( ltrim( $filtermatches[0][$key]), 'apply_filters()' ) ) {

$filtermatches[0][$key] = htmlspecialchars(ltrim( $filtermatches[0][$key]), ENT_NOQUOTES);

							$pieces = explode(",", $val);
							$tag = str_replace( "'", "", trim( $pieces[0] ) );
				
							$args = array_slice($pieces, 1); 

//if no opening ( - remove closing ) that is overwhelmed with simple regex
/*
foreach ( $args as $akey => $aval ) {
	if ( false !== strpos( $aval, '(' ) ) {
		if ( bbp_hookfinder_endsWith( rtrim( $aval ), ')' ) ) $args[ $akey ] = substr( rtrim( $aval ), 0, -1);
	}
}
*/		

							$applyfiltersrow = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ". $wpdb->base_prefix ."hf_bbp_apply_filters WHERE filter_raw_call = %s AND filter_tag = %s AND bbp_version = %s", $filtermatches[0][$key], $tag, bbp_get_version() ) );
							if ( !$applyfiltersrow ) {
								$q = $wpdb->prepare( "INSERT INTO ". $wpdb->base_prefix ."hf_bbp_apply_filters ( filter_raw_call, filter_tag, filter_arg, filter_arg_num, bbp_version ) VALUES ( %s, %s, %s, %d, %s )", $filtermatches[0][$key], $tag, maybe_serialize( $args ), count( $args ),  bbp_get_version() );
								$wpdb->query($q);
							}
	
							if ( empty( $applyfiltersrow->id ) ) {
								$applyfiltersrow->id = $wpdb->insert_id;
								$wpdb->query($q);
							}
	
							//grab some source code around the do_action call
							if ($l < 11) {
								$code_start = 0;
							} else {
								$code_start = $l - 10;
							}
							if ( ($l + 10) > count($sourcelines) ) {
								$length = count($sourcelines) - $l;
							} else {
								$length = 20;
							}
							$code_end = $length + $code_start;
	
							$a = array_slice($sourcelines, $code_start, $code_end - $code_start, true);
	
							$applyfilterschildrow = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ". $wpdb->base_prefix ."hf_bbp_apply_filters_source_child WHERE apply_filters_id = %d AND line_num = %d", $applyfiltersrow->id, $l ) );
							if ( !$applyfilterschildrow ) {
								$q = $wpdb->prepare( "INSERT INTO ". $wpdb->base_prefix ."hf_bbp_apply_filters_source_child ( apply_filters_id, line_num, file_id, source, start_line, end_line ) VALUES ( %d, %d, %d, %s, %d, %d )", $applyfiltersrow->id, $l, $filerow->id, maybe_serialize( $a ), $code_start, $code_end );
								$wpdb->query($q);
//echo '<p>inserting: '. $filtermatches[0][$key] .'<br/></p>';
							}
						}
					}
				
				}
				
				//add_filter( $tag, $function_to_add, $priority, $accepted_args );
				preg_match_all('/add_filter\((.*)\)/i', $line, $addfiltermatches);
				if ( $addfiltermatches ) {
					
					foreach($addfiltermatches[1] as $key => $val) {
					
						if ( !bbp_hookfinder_startsWith( ltrim(  $addfiltermatches[0][$key]), 'apply_filters()' ) ) {

$addfiltermatches[0][$key] = htmlspecialchars(ltrim( $addfiltermatches[0][$key]), ENT_NOQUOTES);


							$pieces = explode(",", $val);
							$tag = str_replace( "'", "", trim( $pieces[0] ) );
							$func = str_replace( "'", "", trim( $pieces[1] ) );
				
							$pri = 0;
							if ( $pieces[2] )
								 $pri = trim( $pieces[2] );
							
							$args = 0;
							if ( $pieces[3] )
								 $args = trim( $pieces[3] );
							
							$addfilterrow = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ". $wpdb->base_prefix ."hf_bbp_add_filter WHERE filter_raw_call = %s AND filter_tag = %s AND bbp_version = %s", $addfiltermatches[0][$key], $tag,  bbp_get_version() ) );
							if ( !$addfilterrow ) {
								$q = $wpdb->prepare( "INSERT INTO ". $wpdb->base_prefix ."hf_bbp_add_filter ( filter_raw_call, filter_tag, function_call, priority, filter_arg_num, bbp_version ) VALUES ( %s, %s, %s, %d, %d, %s )", $addfiltermatches[0][$key], $tag, $func, $pri, $args,  bbp_get_version() );
								$wpdb->query($q);
							}
	
							if ( empty( $addfilterrow->id ) )
								$addfilterrow->id = $wpdb->insert_id;
	
							//grab some source code around the do_action call
							if ($l < 11) {
								$code_start = 0;
							} else {
								$code_start = $l - 10;
							}
							if ( ($l + 10) > count($sourcelines) ) {
								$length = count($sourcelines) - $l;
							} else {
								$length = 20;
							}
							$code_end = $length + $code_start;
	
							$a = array_slice($sourcelines, $code_start, $code_end - $code_start, true);
	
							$addfilterchildrow = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ". $wpdb->base_prefix ."hf_bbp_add_filter_source_child WHERE add_filter_id = %d AND line_num = %d", $addfilterrow->id, $l ) );
							if ( !$addfilterchildrow ) {
								$q = $wpdb->prepare( "INSERT INTO ". $wpdb->base_prefix ."hf_bbp_add_filter_source_child ( add_filter_id, line_num, file_id, source, start_line, end_line ) VALUES ( %d, %d, %d, %s, %d, %d )", $addfilterrow->id, $l, $filerow->id, maybe_serialize( $a ), $code_start, $code_end );
								$wpdb->query($q);
							}
						}

					}
				
				}
		   
		   }
		   
		   $l++;
		   

		}
		
		$sourcelines = null;
		$sourcebyline = null;
		
	}
	
	//select dinstict list of function_call in add_filter and add_action tables - and save code snippet
	$functioncalls = $wpdb->get_results( $wpdb->prepare( "(SELECT function_call FROM ". $wpdb->base_prefix ."hf_bbp_add_filter WHERE bbp_version = %s) UNION (SELECT function_call FROM ". $wpdb->base_prefix ."hf_bbp_add_action WHERE bbp_version = %s) ORDER BY function_call", bbp_get_version(), bbp_get_version() ) );
	
	//some hidden random files that do not get included due to routing
	require_once( $bbp->plugin_dir . '/bbp-admin/bbp-admin.php' );
	require_once( $bbp->plugin_dir . '/bbp-admin/bbp-settings.php' );
	require_once( $bbp->plugin_dir . '/bbp-admin/bbp-forums.php' );
	require_once( $bbp->plugin_dir . '/bbp-admin/bbp-replies.php' );
	require_once( $bbp->plugin_dir . '/bbp-admin/bbp-topics.php' );
	require_once( $bbp->plugin_dir . '/bbp-admin/bbp-functions.php' );
	require_once( $bbp->plugin_dir . '/bbp-includes/bbp-core-hooks.php' );
	
	foreach ($functioncalls as $funcque) {
	
//since some callback functions are dynamic - right now lets just manually exclude what we find from being looked up
//add_action( $hookname, $function );
//add_action( 'wp', $screen_function, 3 );
//add_action( 'wp', array( &$screen_function[0], $screen_function[1] ), 3 );
//add_action( 'bp_' . $fieldname . '_errors', create_function( '', 'echo "<div class=\"error\">' . $error_message . '</div>";' ) );

		if ( !bbp_hookfinder_startsWith($funcque->function_call, '$') && !bbp_hookfinder_startsWith($funcque->function_call, 'array')  && !bbp_hookfinder_startsWith($funcque->function_call, 'create_function')  && !bbp_hookfinder_startsWith($funcque->function_call, 'my_function') ) {
			
			try {
			    $reflector = new ReflectionFunction( $funcque->function_call );
			} catch (Exception $e) {
			    echo $funcque->function_call .'</br><p></p>';
			    $reflector = false;
			}
			
			if ($reflector) {
				
				$reflectfile = file( $reflector->getFileName() );
				
				if ($reflectfile) {
				
				
					$funcfilerow = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ". $wpdb->base_prefix ."hf_bbp_files WHERE file_path = %s AND bbp_version = %s", $reflector->getFileName(), bbp_get_version() ) );
					if ( !$funcfilerow ) {
					
						$lfile = substr( $reflector->getFileName(), 39, strlen( $reflector->getFileName() ) );

						if ( strpos( $lfile, "/plugins/bbpress" ) === false ) {

							$pluginpath = $lfile;
							$svnpath = 'http://core.trac.wordpress.org/browser/tags/'. $wp_version . $lfile;
							$core = "WordPress";

						} else {
							$core = "bbPress";
						}
					
						$q = $wpdb->prepare( "INSERT INTO ". $wpdb->base_prefix ."hf_bbp_files ( file_path, bbp_version, svn_path, plugin_path, core ) VALUES (  %s, %s, %s, %s, %s )", $reflector->getFileName(), bbp_get_version(), $svnpath, $pluginpath, $core );
						$wpdb->query($q);
					}
					
					if ( empty( $funcfilerow->id ) )
						$funcfilerow->id = $wpdb->insert_id;
				
				
					//we want the full function code
					$startline = $reflector->getStartLine() - 1;
					$endline = $reflector->getEndLine() + 1;
					
					$a = array_slice( file($reflector->getFileName()), $startline, $endline - $startline, true);
					//$code = implode( "\n", $a );
				
					$functionrow = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ". $wpdb->base_prefix ."hf_bbp_function_source WHERE function = %s AND bbp_version = %s", $funcque->function_call, bbp_get_version() ) );
					if ( !$functionrow ) {
						$q = $wpdb->prepare( "INSERT INTO ". $wpdb->base_prefix ."hf_bbp_function_source ( function, bbp_version, file_id, source, start_line, end_line, doc ) VALUES ( %s, %s, %d, %s, %d, %d, %s )", $funcque->function_call, bbp_get_version(), $funcfilerow->id, maybe_serialize( $a ), $startline, $endline, $reflector->getDocComment() );
						$wpdb->query($q);
						
					}
				
				}
				
			}
		}
	}


	//loop over all total functions, reflect and check scope code for resulting do_action, apply_filters for parent_function info
	//foreach( $hookfunctions as $hf ) {
	//
	//	$reflector = new ReflectionFunction( $hf ); // foo() being a valid function
	//	$body = array_slice( file($reflector->getFileName()), $reflector->getStartLine(), $reflector->getEndLine() - $reflector->getStartLine() );
	//
	//}
	
}



function bbp_hookfinder_startsWith($haystack,$needle,$case=true) {
    //if($case){return (strcmp(substr($haystack, 0, strlen($needle)),$needle)===0);}
    //return (strcasecmp(substr($haystack, 0, strlen($needle)),$needle)===0);
    
    return ( substr( $haystack, 0, strlen($needle) ) === $needle );

    //return preg_match('/^'.preg_quote($needle).'/', $haystack);
}

function bbp_hookfinder_endsWith($haystack,$needle,$case=true) {
   if($case){return (strcmp(substr($haystack, strlen($haystack) - strlen($needle)),$needle)===0);}
   return (strcasecmp(substr($haystack, strlen($haystack) - strlen($needle)),$needle)===0);
}


function bbp_hookfinder_get_defined_functions( $source ) {
	global $hookfunctions;

    $tokens = token_get_all($source);

    $nextStringIsFunc = false;
    $inClass = false;
    $bracesCount = 0;

    foreach($tokens as $token) {
        switch($token[0]) {
            case T_CLASS:
                $inClass = true;
                break;
            case T_FUNCTION:
                if(!$inClass) $nextStringIsFunc = true;
                break;

            case T_STRING:
                if($nextStringIsFunc) {
                    $nextStringIsFunc = false;
                    $hookfunctions[] = $token[1];
                }
                break;

            // Anonymous functions
            case '(':
            case ';':
                $nextStringIsFunc = false;
                break;

            // Exclude Classes
            case '{':
                if($inClass) $bracesCount++;
                break;

            case '}':
                if($inClass) {
                    $bracesCount--;
                    if($bracesCount === 0) $inClass = false;
                }
                break;
        }
    }
}



function bbp_hookfinder_findfiles( $dir ) {

    if ( $dh = opendir( $dir ) ) {

        $files = Array();
        $inner_files = Array();

        while ( $file = readdir( $dh ) ) {
            if ( $file != "." && $file != ".." && $file[0] != '.' ) {
			
			
					if ( is_dir( $dir . "/" . $file ) ) {

						$inner_files = bbp_hookfinder_findfiles( $dir . "/" . $file );
						
						if( is_array( $inner_files ) ) 
							$files = array_merge( $files, $inner_files ); 
						
					} else {
					
						$extension = end( explode( '.', $file ) );
						if ( $extension == 'php')
							array_push( $files, $dir . "/" . $file );
					}
				
            }
        }

        closedir($dh);
		
        return $files;
    }
}

function bbp_hookfinder_install( ) {
	global $wpdb;

	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET ". $wpdb->charset;

	$sql[] = "CREATE TABLE ". $wpdb->base_prefix . "hf_bbp_do_action (
		  		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				action_raw_call varchar(255) NOT NULL,
				action_tag varchar(255) NOT NULL,
				action_arg text NOT NULL,
				action_arg_num int NOT NULL default 0,
				wp_post_id int NOT NULL default 0,
				did_action int NOT NULL default 0,
				bbp_version varchar(10) NOT NULL,
				UNIQUE (action_raw_call),
				KEY action_tag (action_tag),
				KEY bbp_version (bbp_version)
		 	   ) {$charset_collate};";
				
	$sql[] = "CREATE TABLE ". $wpdb->base_prefix . "hf_bbp_do_action_source_child (
				do_action_id bigint(20) NOT NULL,
				line_num int NOT NULL,
				file_id bigint(20) NOT NULL,
				source text NOT NULL,
				start_line int NOT NULL,
				end_line int NOT NULL,
				PRIMARY KEY (do_action_id, line_num),
				KEY file_id (file_id)
		 	   ) {$charset_collate};";


	$sql[] = "CREATE TABLE ". $wpdb->base_prefix . "hf_bbp_add_action (
		  		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				action_raw_call varchar(255) NOT NULL,
				action_tag varchar(255) NOT NULL,
				function_call varchar(255) NOT NULL,
				priority int NOT NULL,
				action_arg_num int NOT NULL,
				wp_post_id int NOT NULL default 0,
				bbp_version varchar(10) NOT NULL,
				UNIQUE (action_raw_call),
				KEY action_tag (action_tag),
				KEY function_call (function_call),
				KEY priority (priority),
				KEY bbp_version (bbp_version),
				KEY action_arg_num (action_arg_num)
		 	   ) {$charset_collate};";			   

	$sql[] = "CREATE TABLE ". $wpdb->base_prefix . "hf_bbp_add_action_source_child (
				add_action_id bigint(20) NOT NULL,
				line_num int NOT NULL,
				file_id bigint(20) NOT NULL,
				source text NOT NULL,
				start_line int NOT NULL,
				end_line int NOT NULL,
				PRIMARY KEY (add_action_id, line_num),
				KEY file_id (file_id)
		 	   ) {$charset_collate};";


	$sql[] = "CREATE TABLE ". $wpdb->base_prefix . "hf_bbp_apply_filters (
		  		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				filter_raw_call varchar(255) NOT NULL,
				filter_tag varchar(255) NOT NULL,
				filter_arg text NOT NULL,
				filter_arg_num int NOT NULL,
				wp_post_id int NOT NULL default 0,
				bbp_version varchar(10) NOT NULL,
				UNIQUE (filter_raw_call),
				KEY filter_tag (filter_tag),
				KEY bbp_version (bbp_version)
		 	   ) {$charset_collate};";
			   
	$sql[] = "CREATE TABLE ". $wpdb->base_prefix . "hf_bbp_apply_filters_source_child (
				apply_filters_id bigint(20) NOT NULL,
				line_num int NOT NULL,
				file_id bigint(20) NOT NULL,
				source text NOT NULL,
				start_line int NOT NULL,
				end_line int NOT NULL,
				PRIMARY KEY (apply_filters_id, line_num),
				KEY file_id (file_id)
		 	   ) {$charset_collate};";
			   

	$sql[] = "CREATE TABLE ". $wpdb->base_prefix . "hf_bbp_add_filter (
		  		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				filter_raw_call varchar(255) NOT NULL,
				filter_tag varchar(255) NOT NULL,
				function_call varchar(255) NOT NULL,
				priority int NOT NULL,
				filter_arg_num int NOT NULL,
				wp_post_id int NOT NULL default 0,
				bbp_version varchar(10) NOT NULL,
				KEY filter_tag (filter_tag),
				KEY priority (priority),
				KEY bbp_version (bbp_version),
				KEY filter_arg_num (filter_arg_num)
		 	   ) {$charset_collate};";

	$sql[] = "CREATE TABLE ". $wpdb->base_prefix . "hf_bbp_add_filter_source_child (
				add_filter_id bigint(20) NOT NULL,
				line_num int NOT NULL,
				file_id bigint(20) NOT NULL,
				source text NOT NULL,
				start_line int NOT NULL,
				end_line int NOT NULL,
				PRIMARY KEY (add_filter_id, line_num),
				KEY file_id (file_id)
		 	   ) {$charset_collate};";

	//meta tables

	$sql[] = "CREATE TABLE ". $wpdb->base_prefix . "hf_bbp_files (
		  		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				svn_path varchar(255) NOT NULL,
				plugin_path varchar(255) NOT NULL,
				file_path varchar(255) NOT NULL,
				core varchar(100) NOT NULL,
				component varchar(100),
				bbp_version varchar(10) NOT NULL,
				KEY bbp_version (bbp_version)
		 	   ) {$charset_collate};";

	$sql[] = "CREATE TABLE ". $wpdb->base_prefix . "hf_bbp_function_source (
				function varchar(255) NOT NULL PRIMARY KEY,
				file_id bigint(20) NOT NULL,
				source longtext NOT NULL,
				start_line int NOT NULL,
				end_line int NOT NULL,
				doc text,
				bbp_version varchar(10) NOT NULL,
				KEY file_id (file_id),
				KEY bbp_version (bbp_version)
		 	   ) {$charset_collate};";

	$sql[] = "CREATE TABLE ". $wpdb->base_prefix . "hf_bbp_svn (
		  		bbp_version varchar(10) NOT NULL PRIMARY KEY,
				svn_url varchar(255) NOT NULL
		 	   ) {$charset_collate};";
	
	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	
	dbDelta($sql);

	update_site_option( 'hookfinder-bbp-db-version', HOOKFINDER_BBP_DB_VERSION );

}

function bbp_hookfinder_check_installed() {

	if ( get_site_option( 'hookfinder-bbp-db-version' ) < HOOKFINDER_BBP_DB_VERSION )
		bbp_hookfinder_install();
}
add_action( 'admin_menu', 'bbp_hookfinder_check_installed' );

require( dirname( __FILE__ ) . '/etivite-bbp-hookfuncs.php' );

//hook it to run just once
//add_action( 'bp_after_footer', 'bbp_hookfinder_files', 9999 );

?>
