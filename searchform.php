<?php
/**
 * Search form.
 */
if (!defined('ABSPATH')) { exit; }

$id = 'search-' . wp_unique_id();
?>
<form class="searchform" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
    <label class="screen-reader-text" for="<?php echo esc_attr($id); ?>"><?php echo esc_html(bnwp_text('খুঁজুন', 'Search')); ?></label>
    <input type="search" id="<?php echo esc_attr($id); ?>" name="s"
           value="<?php echo esc_attr(get_search_query()); ?>"
           placeholder="<?php echo esc_attr(bnwp_text('খুঁজুন…', 'Search…')); ?>">
    <?php if (bnwp_is_en()) : ?>
        <input type="hidden" name="lang" value="en">
    <?php endif; ?>
    <button class="btn btn--primary" type="submit"><?php echo esc_html(bnwp_text('খুঁজুন', 'Search')); ?></button>
</form>
