<?php
/**
 * Team member listing. Also used for the `team` taxonomy (see taxonomy-team.php).
 */
if (!defined('ABSPATH')) { exit; }

get_header();

if (is_tax('team')) {
    $term     = get_queried_object();
    $heading  = $term ? $term->name : bnwp_text('দল', 'Team');
    $subtitle = $term && $term->description !== '' ? $term->description : '';
} else {
    $heading  = bnwp_text('সদস্যবৃন্দ', 'Members');
    $subtitle = bnwp_text('বাংলা উইকিসংযোগের স্বেচ্ছাসেবী দল।', 'The volunteer team behind Bangla WikiConnect.');
}

$teams = get_terms(array('taxonomy' => 'team', 'hide_empty' => true));
?>

<div class="pagehead pagehead--sunken">
    <div class="wrap">
        <h1><?php echo esc_html($heading); ?></h1>
        <?php if ($subtitle) : ?>
            <p style="color:var(--ink-soft);max-width:62ch;margin:0 0 1.25rem;"><?php echo esc_html($subtitle); ?></p>
        <?php endif; ?>

        <?php if (!is_wp_error($teams) && count($teams) > 1) : ?>
            <nav aria-label="<?php echo esc_attr(bnwp_text('দল বাছাই', 'Filter by team')); ?>" style="display:flex;flex-wrap:wrap;gap:.5rem;">
                <?php
                $all = get_post_type_archive_link('persona');
                $is_all = !is_tax('team');
                ?>
                <a class="chip <?php echo $is_all ? 'chip--live' : ''; ?>" style="text-decoration:none;padding:.5rem 1rem;"
                   href="<?php echo esc_url(bnwp_lang_arg($all ? $all : home_url('/persona/'))); ?>"
                   <?php echo $is_all ? 'aria-current="page"' : ''; ?>><?php echo esc_html(bnwp_text('সবাই', 'Everyone')); ?></a>
                <?php foreach ($teams as $t) :
                    $link = get_term_link($t);
                    if (is_wp_error($link)) { continue; }
                    $active = is_tax('team', $t->slug);
                ?>
                    <a class="chip <?php echo $active ? 'chip--live' : ''; ?>" style="text-decoration:none;padding:.5rem 1rem;"
                       href="<?php echo esc_url(bnwp_lang_arg($link)); ?>"
                       <?php echo $active ? 'aria-current="page"' : ''; ?>><?php echo esc_html($t->name); ?></a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
    </div>
</div>

<div class="section">
    <div class="wrap">
        <?php if (have_posts()) : ?>
            <div class="grid grid--4" data-stagger>
                <?php while (have_posts()) : the_post();
                    $username = bnwp_get_meta('_bnwp_username');
                    $role     = bnwp_get_meta('_bnwp_role');
                ?>
                <a class="person reveal" href="<?php echo esc_url(bnwp_lang_arg(get_permalink())); ?>">
                    <?php bnwp_image(bnwp_get_meta('_bnwp_img'), array(
                        'w' => 176, 'h' => 176, 'class' => 'person__avatar', 'alt' => get_the_title(),
                    )); ?>
                    <span class="person__name"><?php the_title(); ?></span>
                    <?php if ($username) : ?><span class="person__handle">@<?php echo esc_html($username); ?></span><?php endif; ?>
                    <?php if ($role) : ?><span class="person__role"><?php echo esc_html($role); ?></span><?php endif; ?>
                </a>
                <?php endwhile; ?>
            </div>

            <div class="pagination"><?php bnwp_pagination(); ?></div>
        <?php else : ?>
            <p class="notice"><?php echo esc_html(bnwp_text('কোনো সদস্য পাওয়া যায়নি।', 'No members found.')); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
