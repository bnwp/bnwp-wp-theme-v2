<form role="search" method="get" class="d-flex order-sm-1" action="<?php echo esc_url(home_url('/')); ?>">
    <?php if (bnwp_current_language() === 'en') : ?><input type="hidden" name="lang" value="en"><?php endif; ?>
    <input autocomplete="off" class="form-control" name="s" placeholder="<?php echo esc_attr(bnwp_text('অনুসন্ধান', 'Search')); ?>" type="search" value="<?php echo esc_attr(get_search_query()); ?>" aria-label="<?php echo esc_attr(bnwp_text('অনুসন্ধান', 'Search')); ?>">
    <button class="input-group-text btn btn-primary" aria-label="SearchButton" type="submit"><i class="bi-search"></i></button>
</form>
