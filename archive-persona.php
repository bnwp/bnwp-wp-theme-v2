<?php
/**
 * Team member listing. Also used for the `team` taxonomy (see taxonomy-team.php).
 */
if (!defined('ABSPATH')) { exit; }

get_header();

if (is_tax('team')) {
    $term     = get_queried_object();
    $heading  = $term ? bnwp_term_name($term) : bnwp_text('দল', 'Team');
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
                       <?php echo $active ? 'aria-current="page"' : ''; ?>><?php echo esc_html(bnwp_term_name($t)); ?></a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
    </div>
</div>

<div class="section">
    <div class="wrap">
        <?php if (have_posts()) : ?>
            <div class="grid grid--4" data-stagger>
                <?php while (have_posts()) : the_post(); ?>
                    <?php get_template_part('partial-person-card'); ?>
                <?php endwhile; ?>
            </div>

            <div class="pagination"><?php bnwp_pagination(); ?></div>
        <?php else : ?>
            <p class="notice"><?php echo esc_html(bnwp_text('কোনো সদস্য পাওয়া যায়নি।', 'No members found.')); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php
/**
 * Former members, under their own heading. They are held out of the listing
 * above by bnwp_order_people(), so this is the only place they appear — except
 * on the Former team filter itself, where showing them twice would be silly.
 */
$former = is_tax('team', bnwp_former_team()) ? null : bnwp_former_people();
if ($former) : ?>
<div class="section section--sunken">
    <div class="wrap">
        <div class="section__head">
            <div>
                <h2><?php echo esc_html(bnwp_text('প্রাক্তন সদস্যবৃন্দ', 'Former members')); ?></h2>
                <p><?php echo esc_html(bnwp_text(
                    'যাঁরা অতীতে এই উদ্যোগে অবদান রেখেছেন।',
                    'People who contributed to this initiative in the past.'
                )); ?></p>
            </div>
        </div>
        <div class="grid grid--4" data-stagger>
            <?php while ($former->have_posts()) : $former->the_post(); ?>
                <?php get_template_part('partial-person-card'); ?>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php get_footer(); ?>
