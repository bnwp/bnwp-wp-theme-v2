<?php
/**
 * One person tile. Used by the team archive for both the current team and the
 * former members below it, so the two never drift apart.
 */
if (!defined('ABSPATH')) { exit; }

$username = bnwp_get_meta('_bnwp_username');
$role     = bnwp_get_meta_i18n('_bnwp_role');
?>
<a class="person reveal" href="<?php echo esc_url(bnwp_person_url()); ?>"<?php echo bnwp_person_is_external() ? ' rel="noopener"' : ''; ?>>
    <?php bnwp_image(bnwp_get_meta('_bnwp_img'), array(
        'w' => 176, 'h' => 176, 'class' => 'person__avatar', 'alt' => get_the_title(),
    )); ?>
    <span class="person__name"><?php the_title(); ?></span>
    <?php if ($username) : ?><span class="person__handle">@<?php echo esc_html($username); ?></span><?php endif; ?>
    <?php if ($role) : ?><span class="person__role"><?php echo esc_html($role); ?></span><?php endif; ?>
    <?php if (bnwp_person_is_external()) { bnwp_external_mark(); } ?>
</a>
