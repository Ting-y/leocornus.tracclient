<?php
/*
 * Template Name: Trac Tickets
 * Description: a page templage to show a list of tickets.
 */
?>

<?php 
get_header(); 
wp_enqueue_style('wptc-css');
wp_enqueue_script('jquery-masonry');

// the page slug will be the project name.
$version = $_GET['version'];
if (empty($version)) {
    // using the default sprint.
    //$defaults = wptc_widget_ticket_defaults();
    //$version = $defaults['version'];
    //$project = $defaults['project'];
} else {
    $project = wptc_get_project_name($version);
}
?>

</div>

  <div id="left_column">
    <div class='leftnav'>
<?php if (!empty($version)) { ?>
      <div id='sprint-nav' class="widget">
        <h2 class='widgettitle'>
          Project: <b><?php echo $project;?></b>
        </h2>
        <?php echo wptc_widget_version_nav($project)?>
      </div>
<?php } ?>
      <div id='ticket-finder' class="widget">
        <h2 class='widgettitle'>Ticket Finder</h2>
        <?php echo wptc_widget_ticket_finder('trac/ticket')?>
      </div>
    </div>
  </div> <?php // END left_column ?>

  <div id="right_column">

<?php if (empty($version)) {

  echo wptc_widget_trac_homepage();

} else { ?>

  <h2>Tickets for Version: <em><?php echo $version ?></em></h2>

  <?php 
    $tickets = wptc_get_tickets_by_version($version);
    echo wptc_widget_tickets_list($tickets, 'trac/ticket');
  ?>

<?php } ?>

  </div> <?php // END right_column ?>

<?php get_footer(); ?>
