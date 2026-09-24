<?php
/**
 * Single project.
 */
if (!defined('ABSPATH')) { exit; }

get_header();

while (have_posts()) : the_post();
    $logo    = bnwp_get_meta('_bnwp_logo');
    $wiki    = bnwp_get_meta('_bnwp_wiki');
    $wikitxt = bnwp_get_meta_i18n('_bnwp_wiki_label');
    $dates   = bnwp_project_dates();

    $lead    = bnwp_get_meta_i18n('_bnwp_lead');
    $status  = bnwp_project_status();
    $archive = get_post_type_archive_link('project');
?>

<article>
    <?php if ($status === 'completed') : ?>
        <div class="endedbar">
            <div class="wrap endedbar__inner">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16.5h.01"/>
                </svg>
                <span class="endedbar__text">
                    <strong><?php echo esc_html(bnwp_text('এই প্রকল্পটি সমাপ্ত হয়েছে।', 'This project has ended.')); ?></strong>
                    <span><?php echo esc_html(bnwp_text('নতুন করে আর অংশগ্রহণ করা যাবে না।', 'New entries are no longer being accepted.')); ?></span>
                </span>
            </div>
        </div>
    <?php endif; ?>

    <div class="pagehead pagehead--sunken">
        <div class="wrap">
            <nav class="breadcrumb" aria-label="<?php echo esc_attr(bnwp_text('ব্রেডক্রাম্ব', 'Breadcrumb')); ?>">
                <a href="<?php echo esc_url(bnwp_lang_arg(home_url('/'))); ?>"><?php echo esc_html(bnwp_text('প্রচ্ছদ', 'Home')); ?></a>
                <span>/</span>
                <a href="<?php echo esc_url(bnwp_lang_arg($archive ? $archive : home_url('/projects/'))); ?>"><?php echo esc_html(bnwp_text('প্রকল্পসমূহ', 'Projects')); ?></a>
            </nav>

            <div class="layout-aside layout-aside--hero">
                <div>
                    <?php if ($status && $status !== 'completed') : ?>
                        <p style="margin:0 0 1rem;"><?php bnwp_status_chip($status); ?></p>
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
                    <div class="hero-logo">
                        <span class="logo-tile logo-tile--lg"><?php bnwp_image($logo, array('w' => 500, 'alt' => get_the_title(), 'loading' => 'eager', 'fit' => 'contain')); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="wrap layout-aside">
            <div>
                <div class="prose"><?php the_content(); ?></div>
            </div>

            <aside class="stack aside--project" style="--flow:1.25rem;">
                <div class="panel panel--facts">
                    <h2 class="panel__title"><?php echo esc_html(bnwp_text('সংক্ষেপে', 'At a glance')); ?></h2>
                    <dl class="factlist">
                        <?php if ($status) : ?>
                            <dt><?php echo esc_html(bnwp_text('অবস্থা', 'Status')); ?></dt>
                            <dd><?php echo esc_html(bnwp_project_status_label($status)); ?></dd>
                        <?php endif; ?>
                        <?php if ($dates) : ?>
                            <dt><?php echo esc_html(bnwp_text('সময়ক্রম', 'Timeline')); ?></dt>
                            <dd><?php echo esc_html($dates); ?></dd>
                        <?php endif; ?>
                        <?php if ($wiki) : ?>
                            <dt><?php echo esc_html(bnwp_text('উইকি', 'Wiki')); ?></dt>
                            <dd><a href="<?php echo esc_url($wiki); ?>"><?php
                                echo esc_html($wikitxt !== '' ? $wikitxt : wp_parse_url($wiki, PHP_URL_HOST));
                            ?></a></dd>
                        <?php endif; ?>
                        <dt><?php echo esc_html(bnwp_text('ভাষা', 'Language')); ?></dt>
                        <dd><?php echo esc_html(bnwp_project_language()); ?></dd>
                        <dt><?php echo esc_html(bnwp_text('আয়োজক', 'Organiser')); ?></dt>
                        <dd><?php echo esc_html(bnwp_site_name()); ?></dd>
                    </dl>
                </div>

                <?php $organisers = bnwp_people_entries(bnwp_get_meta('_bnwp_organisers')); ?>
                <?php if ($organisers) : ?>
                <div class="panel panel--people">
                    <h2 class="panel__title"><?php echo esc_html(bnwp_text('আয়োজক', 'Organisers')); ?></h2>
                    <?php bnwp_person_rows($organisers); ?>
                </div>
                <?php endif; ?>

                <?php
                // Named jury for this project — team members and guests in one
                // list, in the order written; otherwise the Reviewers team.
                $jury = bnwp_people_entries(bnwp_get_meta('_bnwp_jury'));
                if (!$jury) {
                    $jury = bnwp_team_entries('jury', 6);
                }
                if ($jury) : ?>
                <div class="panel panel--people">
                    <h2 class="panel__title"><?php echo esc_html(bnwp_text('বিচারকমণ্ডলী', 'Jury')); ?></h2>
                    <?php bnwp_person_rows($jury); ?>
                </div>
                <?php endif; ?>
            </aside>
        </div>
    </div>

    <?php
    $related = bnwp_related_projects(3);
    if ($related) : ?>
    <section class="section section--sunken">
        <div class="wrap">
            <h2 style="font-size:var(--step-3);margin-bottom:1.75rem;"><?php echo esc_html(bnwp_text('সম্পর্কিত প্রকল্প', 'Related projects')); ?></h2>
            <div class="grid grid--3" data-stagger>
                <?php while ($related->have_posts()) : $related->the_post(); $rl = bnwp_get_meta('_bnwp_logo'); ?>
                <article class="card reveal">
                    <div class="card__top">
                        <?php if ($rl) : ?><span class="logo-tile"><?php bnwp_image($rl, array('w' => 128, 'alt' => '', 'fit' => 'contain')); ?></span><?php endif; ?>
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
