<?php

/**
 * generate href link to a commit id.
 */
function wptc_auto_link_commit_id($subject) {

    // #12 or #3
    $pattern = '/( ){1}([0-9a-fA-F]{7,40})(\]| |\)){1}/';
    if(preg_match($pattern, $subject) === 1) {
        $base_url = wptc_get_git_base_url();
        $href = "\\1<a href='" . $base_url . "?id=\\2'>\\2</a>\\3";
        $subject = preg_replace($pattern, $href, $subject);
    }

    return $subject;
}
// filter all wiki content to link commit_id.
add_filter('wptc_widget_parse_content', 'wptc_auto_link_commit_id', 
           10, 1);

/**
 *
 */
function wptc_apply_page_template($template) {

    if(!is_main_site()) {
        // current site is not main site.
        // skip it.
        return $template;
    }
    
    // using default value to make things easier.
    // trac datatable homepage.
    $page_id = get_site_option('wptc_page_trac_dt', "-1");
    if(($page_id != "-1") && is_page($page_id)) {
        // apply trac homepage template.
        $template = WPTC_PLUGIN_PATH . '/templates/page-trac-list.php';
        return $template;
    }
    // trac homepage.
    $page_id = get_site_option('wptc_page_trac', "-1");
    if(($page_id != "-1") && is_page($page_id)) {
        // apply trac homepage template.
        $template = WPTC_PLUGIN_PATH . '/templates/page-trac.php';
        return $template;
    }
    // the ticket details page template.
    $page_id = get_site_option('wptc_page_trac_ticket', "-1");
    if(($page_id != "-1") && is_page($page_id)) {
        // apply trac homepage template.
        $template = WPTC_PLUGIN_PATH . 
                    '/templates/page-trac-ticket.php';
        return $template;
    }
    // my tickets page.
    $page_id = get_site_option('wptc_page_trac_mytickets', "-1");
    if(($page_id != "-1") && is_page($page_id)) {
        // apply trac homepage template.
        $template = WPTC_PLUGIN_PATH . 
                    '/templates/page-trac-mytickets.php';
        return $template;
    }
    // my watchlist page.
    $page_id = get_site_option('wptc_page_trac_watchlist', "-1");
    if(($page_id != "-1") && is_page($page_id)) {
        // apply trac homepage template.
        $template = WPTC_PLUGIN_PATH . 
                    '/templates/page-trac-watchlist.php';
        return $template;
    }
    // the testing page.
    $page_id = get_site_option('wptc_page_trac_testing', "-1");
    if(($page_id != "-1") && is_page($page_id)) {
        // apply trac homepage template.
        $template = WPTC_PLUGIN_PATH . 
                    '/templates/page-trac-test.php';
        return $template;
    }


    return $template;
}
add_filter('template_include', 'wptc_apply_page_template');

/**
 * get all attachment settings and return them as a array.
 */
function wptc_attachment_get_settings() {

    $handler_url = get_site_option('wptc_attachment_handler_url');
    $desc_template = get_site_option('wptc_attachment_description');
    $tags_template = get_site_option('wptc_attachment_tags');
    $comment = get_site_option('wptc_attachment_comment');
    $image_wikitext = 
        get_site_option('wptc_attachment_image_wikitext');
    $file_wikitext = 
        get_site_option('wptc_attachment_file_wikitext');
    $multi_selection = 
        get_site_option('wptc_attachment_multi_selection', 'false');
    $unique_filename = 
        get_site_option('wptc_attachment_unique_filename', 'true');

    $settings = array(
        'handler_url' => $handler_url,
        'desc' => $desc_template,
        'tags' => $tags_template,
        'comment' => $comment,
        'multi_selection' => $multi_selection,
        'unique_filename' => $unique_filename,
        'image_wikitext' => $image_wikitext,
        'file_wikitext' => $file_wikitext
    );

    return $settings;
}

/**
 * return true if the add BuddyPress activity option is set to true.
 */
function wptc_buddypress_activity_on() {

    $add = get_site_option('wptc_buddypress_activity', 'false');

    return ($add === 'true');
}

/**
 * this function will record a Trac update as a BuddyPress activity
 * $ticket_id
 * $action    This is the Trac workflow action. 
 *            The default workflow actions are: leave, create,
 *            accept, reassign, resolve, reopen
 * $content   ticket summary.
 */
function wptc_add_buddypress_activity($ticket_id, 
    $ticket_action, $ticket_attr) {

    if(!wptc_buddypress_activity_on() or 
       !function_exists('bp_activity_add')) {
        // skip!
        return;
    }

    // get ready the URL to ticket.
    $ticket_url = wptc_widget_parse_content('ticket #' . $ticket_id);
    // get ready the link to user, using current user.
    $user = wp_get_current_user();
    $user_url = bp_core_get_userlink($user->ID);
    $owner = get_user_by('login', $ticket_attr['owner']);
    $owner_url = bp_core_get_userlink($owner->ID);

    if(!isset($ticket_action)) {
        // give a default action here!
        $ticket_action = 'leave';
    }

    switch($ticket_action) {
        case "leave":
            $message = $user_url . ' update ' . $ticket_url;
            break;
        case "reassign":
            // get the new owner.
            $message = $user_url . ' reassign ' . $ticket_url .
                       ' to ' . $owner_url;
            break;
        case "resolve":
            // get the resolution.
            $message = $user_url . ' resolve ' . $ticket_url .
                       " as <strong>" . $ticket_attr['resolution'] .
                       "</strong>";
            break;
        default:
            $message = $user_url . ' ' . $ticket_action . ' ' . 
                       $ticket_url;
            break;
    }

    bp_activity_add(array(
        'action' => $message,
        'content' => $ticket_attr['summary'],
        'component' => 'OPSpedia Ticket',
        'type' => 'ticket_update'
    ));

    return true;
}

/**
 * action function for create ticket.
 */
function wptc_create_ticket_action($id, $summary, $desc, $attrs) {

    wptc_add_buddypress_activity($id, 'create', $attrs);
    return;
}
add_action('wptc_create_ticket', 'wptc_create_ticket_action', 10, 4);

/**
 * action function for update ticket.
 */
function wptc_update_ticket_action($id, $comment, $attrs, $author) {

    wptc_add_buddypress_activity($id, $attrs['action'], $attrs);
    return;
}
add_action('wptc_update_ticket', 'wptc_update_ticket_action', 10, 4);
