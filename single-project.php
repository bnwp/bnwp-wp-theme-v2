<?php
/**
 * Single project.
 */
if (!defined('ABSPATH')) { exit; }

get_header();

while (have_posts()) : the_post();
    $logo    = bnwp_get_meta('_bnwp_logo');
    $cover   = bnwp_get_meta('_bnwp_cover');
    $wiki    = bnwp_get_meta('_bnwp_wiki');
    $lead    = bnwp_get_meta('_bnwp_lead');
    $status  = bnwp_get_meta('_bnwp_status');
    $archive = get_post_type_archive_link('project');
?>

<article>
    <div class="pagehead pagehead--sunken">
        <div class="wrap">
            <nav class="breadcrumb" aria-label="<?php echo esc_attr(bnwp_text('ব্রেডক্রাম্ব', 'Breadcrumb')); ?>">
                <a href="<?php echo esc_url(bnwp_lang_arg(home_url('/'))); ?>"><?php echo esc_html(bnwp_text('প্রচ্ছদ', 'Home')); ?></a>
                <span>/</span>
                <a href="<?php echo esc_url(bnwp_lang_arg($archive ? $archive : home_url('/projects/'))); ?>"><?php echo esc_html(bnwp_text('প্রকল্পসমূহ', 'Projects')); ?></a>
            </nav>

            <div class="layout-aside" style="align-items:center;">
                <div>
                    <?php if ($status) : ?>
                        <p style="margin:0 0 1rem;">
                            <span class="chip <?php echo $status === 'ongoing' ? 'chip--live' : 'chip--past'; ?>"><?php echo esc_html(bnwp_project_status_label($status)); ?></span>
                        </p>
                    <?php endif; ?>

                    <h1><?php the_title(); ?></h1>

                    <?php if ($lead) : ?>
                        <p class="hero__lead"><?php echo esc_html($lead); ?></p>
                    <?php endif; ?>

                    <?php if ($wiki) : ?>
                        <div class="hero__actions">
                            <a class="btn btn--primary" href="<?php echo esc_url($wiki); ?>">
                                <?php echo esc_html(bnwp_text('প্রকল্প পাতায় যান', 'Go to project page')); ?>
                                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 17 17 7M9 7h8v8"/></svg>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($logo) : ?>
                    <div style="justify-self:center;">
                        <span class="logo-tile logo-tile--lg"><?php bnwp_image($logo, array('w' => 240, 'alt' => get_the_title(), 'loading' => 'eager')); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="wrap layout-aside">
            <div>
                <?php if ($cover) : ?>
                    <figure style="margin:0 0 2rem;">
                        <?php bnwp_image($cover, array('w' => 1000, 'alt' => get_the_title(), 'class' => 'project__cover', 'fit' => 'cover')); ?>
                    </figure>
                <?php endif; ?>

                <div class="prose"><?php the_content(); ?></div>
            </div>

            <aside class="stack" style="--flow:1.25rem;">
                <div class="panel">
                    <h2 class="panel__title"><?php echo esc_html(bnwp_text('সংক্ষেপে', 'At a glance')); ?></h2>
                    <dl class="factlist">
                        <?php if ($status) : ?>
                            <dt><?php echo esc_html(bnwp_text('অবস্থা', 'Status')); ?></dt>
                            <dd><?php echo esc_html(bnwp_project_status_label($status)); ?></dd>
                        <?php endif; ?>
                        <?php if ($wiki) : ?>
                            <dt><?php echo esc_html(bnwp_text('উইকি', 'Wiki')); ?></dt>
                            <dd><a href="<?php echo esc_url($wiki); ?>"><?php echo esc_html(wp_parse_url($wiki, PHP_URL_HOST)); ?></a></dd>
                        <?php endif; ?>
                        <dt><?php echo esc_html(bnwp_text('ভাষা', 'Language')); ?></dt>
                        <dd><?php echo esc_html(bnwp_is_en() ? 'English' : 'বাংলা'); ?></dd>
                        <dt><?php echo esc_html(bnwp_text('আয়োজক', 'Organiser')); ?></dt>
                        <dd><?php echo esc_html(bnwp_site_name()); ?></dd>
                    </dl>
                </div>

                <?php
                $jury = new WP_Query(array(
                    'post_type'      => 'persona',
                    'posts_per_page' => 6,
                    'no_found_rows'  => true,
                    'tax_query'      => array(array('taxonomy' => 'team', 'field' => 'slug', 'terms' => 'jury')),
                    'meta_query'     => array(
                        'relation' => 'OR',
                        array('key' => '_bnwp_language', 'value' => bnwp_current_language(), 'compare' => '='),
                        array('key' => '_bnwp_language', 'compare' => 'NOT EXISTS'),
                    ),
                ));
                if ($jury->have_posts()) : ?>
                <div class="panel">
                    <h2 class="panel__title"><?php echo esc_html(bnwp_text('বিচারকমণ্ডলী', 'Jury')); ?></h2>
                    <div class="stack" style="--flow:.9rem;">
                        <?php while ($jury->have_posts()) : $jury->the_post(); ?>
                            <a href="<?php echo esc_url(bnwp_lang_arg(get_permalink())); ?>" style="display:flex;gap:.75rem;align-items:center;text-decoration:none;color:var(--ink);">
                                <?php bnwp_image(bnwp_get_meta('_bnwp_img'), array(
                                    'w' => 88, 'h' => 88, 'alt' => '',
                                    'class' => 'person__avatar', 'fit' => 'cover',
                                )); ?>
                                <span>
                                    <span style="display:block;"><?php the_title(); ?></span>
                                    <?php $u = bnwp_get_meta('_bnwp_username'); if ($u) : ?>
                                        <span class="person__handle">@<?php echo esc_html($u); ?></span>
                                    <?php endif; ?>
                                </span>
                            </a>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </div>
                </div>
                <?php endif; ?>
            </aside>
        </div>
    </div>

    <?php
    $related = new WP_Query(array(
        'post_type'      => 'project',
        'posts_per_page' => 3,
        'post__not_in'   => array(get_the_ID()),
        'no_found_rows'  => true,
        'orderby'        => 'rand',
        'meta_query'     => array(
            'relation' => 'OR',
            array('key' => '_bnwp_language', 'value' => bnwp_current_language(), 'compare' => '='),
            array('key' => '_bnwp_language', 'compare' => 'NOT EXISTS'),
        ),
    ));
    if ($related->have_posts()) : ?>
    <section class="section section--sunken">
        <div class="wrap">
            <h2 style="font-size:var(--step-3);margin-bottom:1.75rem;"><?php echo esc_html(bnwp_text('সম্পর্কিত প্রকল্প', 'Related projects')); ?></h2>
            <div class="grid grid--3" data-stagger>
                <?php while ($related->have_posts()) : $related->the_post(); $rl = bnwp_get_meta('_bnwp_logo'); ?>
                <article class="card reveal">
                    <div class="card__top">
                        <?php if ($rl) : ?><span class="logo-tile"><?php bnwp_image($rl, array('w' => 128, 'alt' => '')); ?></span><?php endif; ?>
                    </div>
                    <h3 class="card__title"><a href="<?php echo esc_url(bnwp_lang_arg(get_permalink())); ?>"><?php the_title(); ?></a></h3>
                </article>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
</article>

<?php
endwhile;
get_footer();
