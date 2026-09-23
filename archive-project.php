<?php
/**
 * Projects archive.
 */
if (!defined('ABSPATH')) { exit; }

get_header();
?>

<div class="pagehead pagehead--sunken">
    <div class="wrap">
        <h1><?php echo esc_html(bnwp_text('প্রকল্পসমূহ', 'Projects')); ?></h1>
        <p style="color:var(--ink-soft);max-width:62ch;margin:0;"><?php echo esc_html(bnwp_text(
            'বাংলা উইকিসংযোগ আয়োজিত প্রতিযোগিতা, সম্পাদনা-অ-থন ও প্রশিক্ষণ কর্মসূচি।',
            'Contests, edit-a-thons and training programmes organised by Bangla WikiConnect.'
        )); ?></p>
    </div>
</div>

<div class="section">
    <div class="wrap">
        <?php if (have_posts()) : ?>
            <div class="grid grid--3" data-stagger>
                <?php while (have_posts()) : the_post();
                    $logo   = bnwp_get_meta('_bnwp_logo');
                    $lead   = bnwp_get_meta('_bnwp_lead');
                    $status = bnwp_get_meta('_bnwp_status');
                ?>
                <article class="card reveal">
                    <div class="card__top">
                        <?php if ($logo) : ?>
                            <span class="logo-tile"><?php bnwp_image($logo, array('w' => 128, 'alt' => '')); ?></span>
                        <?php else : ?>
                            <span></span>
                        <?php endif; ?>
                        <?php if ($status) : ?>
                            <span class="chip <?php echo $status === 'ongoing' ? 'chip--live' : 'chip--past'; ?>"><?php
                                echo esc_html(bnwp_project_status_label($status));
                            ?></span>
                        <?php endif; ?>
                    </div>
                    <h2 class="card__title">
                        <a href="<?php echo esc_url(bnwp_lang_arg(get_permalink())); ?>"><?php the_title(); ?></a>
                    </h2>
                    <?php if ($lead) : ?>
                        <p class="card__text"><?php echo esc_html(wp_trim_words($lead, 26, '…')); ?></p>
                    <?php endif; ?>
                    <a class="arrow-link" href="<?php echo esc_url(bnwp_lang_arg(get_permalink())); ?>" tabindex="-1" aria-hidden="true">
                        <?php echo esc_html(bnwp_text('বিস্তারিত দেখুন', 'View details')); ?>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h15M13 6l6 6-6 6"/></svg>
                    </a>
                </article>
                <?php endwhile; ?>
            </div>

            <div class="pagination"><?php bnwp_pagination(); ?></div>
        <?php else : ?>
            <p class="notice"><?php echo esc_html(bnwp_text('কোনো প্রকল্প পাওয়া যায়নি।', 'No projects found.')); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
