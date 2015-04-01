<?php

/**
 * add_query_vars_filter function.
 *
 * @access public
 * @param mixed $vars
 * @return void
 */
function add_query_vars_filter($vars){
  $vars[] = "title";
  $vars[] = "doc-title";
  $vars[] = "teams";
  $vars[] = "keyword";
  $vars[] = "area_of_law";
  $vars[] = "publication";
  $vars[] = "lccp";
  $vars[] = "start";
  $vars[] = "end";
  return $vars;
}
add_filter('query_vars', 'add_query_vars_filter');

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  $data = esc_sql($data);
  return $data;
}

/**
 * fix_posts_per_page function.
 *
 * @access public
 * @param mixed $value
 * @return void
 */
function fix_posts_per_page( $value ) {
  return (is_post_type_archive('project') || is_post_type_archive('document')) ? 1 : $value;
}
add_filter( 'option_posts_per_page', 'fix_posts_per_page' );

/**
 * project_query function.
 *
 * @access public
 * @return void
 */
function project_query() {
  if(isset($wp_query)) {
    $temp = $wp_query;
    $wp_query = NULL;
  }
  $args = array();
  $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

  $title = test_input(get_query_var('title'));
  if(!empty($title)) {
    $args['meta_query'][] = array(
      'relation' => 'OR',
      array(
        'key' => 'title',
        'value' => $title,
        'compare' => 'LIKE',
      ),
      array(
        'key' => 'description',
        'value' => $title,
        'compare' => 'LIKE',
      ),
      array(
        'key' => 'keywords',
        'value' => $title,
        'compare' => 'LIKE',
      ),
    );
  }

  $area_of_law = test_input(get_query_var('area_of_law'));
  if(!empty($area_of_law)) {
    $args['tax_query'][] = array(
      'taxonomy' => 'areas_of_law',
      'terms' => $area_of_law,
    );
  }

  if(isset($args['tax_query']) && count($args['tax_query']) > 1) {
    $args['tax_query']['relation'] = 'AND';
  }

  $args['post_type'] = 'project';
  $args['paged'] = $paged;
  $args['posts_per_page'] = 10;
  $wp_query = new WP_Query();
  $wp_query->query($args);

  return $wp_query;
}


/**
 * document_query function.
 *
 * @access public
 * @return void
 */
function document_query() {
  $title = test_input(get_query_var('title'));
  $area_of_law = test_input(get_query_var('area_of_law'));
  $teams = test_input(get_query_var('teams'));

  if(!empty($title) || !empty($area_of_law) || !empty($teams)) {
    $projectArgs = array(
      'post_type' => 'project',
      'posts_per_page' => -1
    );

    if(!empty($title)) {
      $projectArgs['meta_query'][] = array(
        'relation' => 'OR',
        array(
          'key' => 'title',
          'value' => $title,
          'compare' => 'LIKE',
        ),
        array(
          'key' => 'description',
          'value' => $title,
          'compare' => 'LIKE',
        ),
        array(
          'key' => 'keywords',
          'value' => $title,
          'compare' => 'LIKE',
        ),
      );
    }

    if(!empty($area_of_law)) {
      $projectArgs['tax_query'][] = array(
        'taxonomy' => 'areas_of_law',
        'terms' => $area_of_law,
      );
    }

    if(!empty($teams)) {
      $projectArgs['tax_query'][] = array(
        'taxonomy' => 'team',
        'terms' => $teams,
      );
    }

    if(!empty($projectArgs['tax_query']) && count($projectArgs['tax_query']) > 1) {
      $projectArgs['tax_query']['relation'] = 'AND';
    }

    $projects = get_posts($projectArgs);
    $projectIDs = array();
    foreach($projects as $project) {
      $projectIDs[] = $project->ID;
    }
  }

  if(!empty($wp_query)) {
    $temp = $wp_query;
    $wp_query = NULL;
  }
  $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

  if(!empty($projectIDs)) {
    $args['meta_query'][] = array(
      'key' => 'project',
      'value' => $projectIDs,
      'compare' => 'IN',
    );
  } else {
    $args['meta_query'][] = array(
      'key' => 'project',
      'value'   => "null",
      'compare' => 'NOT IN'
    );
    $args['meta_query'][] = array(
      'key' => 'project',
      'value'   => null,
      'compare' => 'NOT IN'
    );
  }

  $doc_title = test_input(get_query_var('doc-title'));
  if(!empty($doc_title)) {
    $args['meta_query'][][] = array(
      'relation' => 'OR',
      array(
        'key' => 'title',
        'value' => $doc_title,
        'compare' => 'LIKE',
      ),
      array(
        'key' => 'description',
        'value' => $doc_title,
        'compare' => 'LIKE',
      ),
      array(
        'key' => 'keywords',
        'value' => $doc_title,
        'compare' => 'LIKE',
      ),
    );
  }

  $lccp = test_input(get_query_var('lccp'));
  if(!empty($lccp)) {
    $args['meta_query'][] = array(
      'key' => 'reference_number',
      'value' => $lccp,
      'compare' => 'LIKE',
    );
  }

  $publication = test_input(get_query_var('publication'));
  if(!empty($publication)) {
    $args['tax_query'][] = array(
      'taxonomy' => 'document_type',
      'terms' => $publication,
    );
  }

  $start = test_input(get_query_var('start'));
  $end = test_input(get_query_var('end'));
  if(!empty($start) && !empty($end)) {
    $stime = DateTime::createFromFormat("m/d/Y", $start);
    $etime = DateTime::createFromFormat("m/d/Y", $end);

    $args['meta_query'][] = array(
      'key' => 'publication_date',
      'value' => array(date_format($stime, 'Y-m-d'),date_format($etime, 'Y-m-d')),
      'compare' => 'BETWEEN',
      'type' => 'DATE'
    );
  }

  $args['post_type'] = 'document';
  $args['paged'] = $paged;
  $args['posts_per_page'] = 10;
  $wp_query = new WP_Query();
  $wp_query->query($args);
  return $wp_query;

}
