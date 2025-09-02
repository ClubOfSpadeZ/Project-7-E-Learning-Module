<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
get_header();
?>
<div class="elearn-dashboard">
    <h2>Welcome to Your E-Learning Dashboard</h2>
    <a href="<?php echo home_url('?elearn_page=modules'); ?>" class="button">View Modules</a>
    <a href="<?php echo home_url('?elearn_page=assessment-results'); ?>" class="button">View Results</a>
</div>
<?php
get_footer();