<?php
/**
 * Front page.
 */
if (!defined('ABSPATH')) { exit; }

get_header();

$lang    = bnwp_current_language();
$persona = get_post_type_archive_link('persona');
$project = get_post_type_archive_link('project');

$stats = bnwp_stats();
?>

<section class="hero">
    <div class="wrap">

        <?php if ($stats) : ?>
        <div class="hero__showcase">
            <div class="hero__mark reveal">
                <?php bnwp_logo_img(300, 'hero__mark-img', 'eager'); ?>
            </div>

            <div class="stats reveal">
                <p class="panel__title"><?php echo esc_html(bnwp_text('এখন পর্যন্ত আমাদের অবদান', 'Our impact so far')); ?></p>
                <div class="stats__grid">
                    <?php foreach ($stats as $s) : ?>
                        <div class="stat">
                            <div class="stat__value tabular"<?php
                                if ($s['count'] !== null) {
                                    printf(
                                        ' data-count="%d" data-suffix="%s"',
                                        (int) $s['count'],
                                        esc_attr($s['suffix'])
                                    );
                                }
                            ?>><?php echo esc_html($s['value']); ?></div>
                            <p class="stat__label"><?php echo esc_html($s['label']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="hero__intro">
            <p class="eyebrow reveal"><?php echo esc_html(bnwp_text('মুক্ত জ্ঞান আন্দোলন · বাংলাদেশ', 'Free knowledge movement · Bangladesh')); ?></p>

            <h1 class="reveal"><?php echo esc_html(bnwp_text(
                'বাংলা উইকিসংযোগ একটি সহযোগিতামূলক উদ্যোগ',
                'Bangla WikiConnect is a collaborative initiative'
            )); ?></h1>

            <p class="hero__lead reveal"><?php echo esc_html(bnwp_text(
                'বাংলা ভাষায় উইকিপিডিয়ার বিষয়বস্তু বৃদ্ধি এবং সম্প্রসারণের উপর আমরা দৃষ্টি নিবদ্ধ করি। বিভিন্ন আকর্ষণীয় প্রতিযোগিতা, সম্পাদনা-অ-থন এবং প্রশিক্ষণ কর্মসূচির মাধ্যমে উইকিপিডিয়া ও এর সহযোগী প্রকল্প — উইকিউক্তি, উইকিভ্রমণ, উইকিবই ও উইকিঅভিধানে উচ্চমানের, অন্তর্ভুক্তিমূলক বিষয়বস্তু তৈরি করাই আমাদের লক্ষ্য।',
                'We focus on growing and expanding Wikipedia content in Bangla. Through contests, edit-a-thons and training programmes, we aim to build high-quality, inclusive content across Wikipedia and its sister projects — Wikiquote, Wikivoyage, Wikibooks and Wiktionary.'
            )); ?></p>

            <div class="hero__actions reveal">
                <a class="btn btn--primary" href="<?php echo esc_url(bnwp_page_url('about', $lang)); ?>">
                    <?php echo esc_html(bnwp_text('আরও জানুন', 'Learn more')); ?>
                </a>
                <a class="btn btn--ghost" href="https://meta.wikimedia.org/wiki/Bangla_WikiConnect">
                    <?php echo esc_html(bnwp_text('মেটা’উইকিতে পড়ুন', 'Read on Meta-Wiki')); ?>
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 17 17 7M9 7h8v8"/></svg>
                </a>
            </div>
        </div>

    </div>
</section>


<?php
// Only filter by language where that language actually has records — otherwise
// the English homepage would show no projects at all.
$project_args = array('post_type' => 'project', 'posts_per_page' => 6, 'no_found_rows' => true);
if (bnwp_lang_has_content('project')) {
    $project_args['meta_query'] = array(bnwp_lang_meta_query());
}
$projects = new WP_Query($project_args);
if ($projects->have_posts()) : ?>
<section class="section section--sunken">
    <div class="wrap">
        <div class="section__head">
            <div>
                <h2><?php echo esc_html(bnwp_text('আমাদের প্রকল্পসমূহ', 'Our projects')); ?></h2>
                <p><?php echo esc_html(bnwp_text('চলমান ও সদ্য সমাপ্ত প্রতিযোগিতা এবং কর্মসূচি', 'Ongoing and recently completed contests and programmes')); ?></p>
            </div>
            <a class="arrow-link" href="<?php echo esc_url(bnwp_lang_arg($project ? $project : home_url('/projects/'), $lang)); ?>">
                <?php echo esc_html(bnwp_text('সব প্রকল্প দেখুন', 'All projects')); ?>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h15M13 6l6 6-6 6"/></svg>
            </a>
        </div>

        <div class="grid grid--3" data-stagger>
            <?php while ($projects->have_posts()) : $projects->the_post();
                $logo = bnwp_get_meta('_bnwp_logo');
                $lead = bnwp_get_meta('_bnwp_lead');
            ?>
            <article class="card reveal">
                <div class="card__top">
                    <?php if ($logo) : ?>
                        <span class="logo-tile"><?php bnwp_image($logo, array('w' => 128, 'alt' => '')); ?></span>
                    <?php endif; ?>
                </div>
                <h3 class="card__title">
                    <a href="<?php echo esc_url(bnwp_lang_arg(get_permalink())); ?>"><?php the_title(); ?></a>
                </h3>
                <?php if ($lead) : ?>
                    <p class="card__text"><?php echo esc_html(wp_trim_words($lead, 24, '…')); ?></p>
                <?php endif; ?>
                <a class="arrow-link" href="<?php echo esc_url(bnwp_lang_arg(get_permalink())); ?>" tabindex="-1" aria-hidden="true">
                    <?php echo esc_html(bnwp_text('বিস্তারিত দেখুন', 'View details')); ?>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h15M13 6l6 6-6 6"/></svg>
                </a>
            </article>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </div>
</section>
<?php endif; ?>


<?php
$recent_args = array('post_type' => 'post', 'posts_per_page' => 4, 'no_found_rows' => true);
if (bnwp_lang_has_content('post')) {
    $recent_args['meta_query'] = array(bnwp_lang_meta_query());
}
$recent = new WP_Query($recent_args);
if ($recent->have_posts()) : ?>
<section class="section">
    <div class="wrap">
        <div class="section__head">
            <h2><?php echo esc_html(bnwp_text('বার্তাকক্ষ', 'Newsroom')); ?></h2>
            <a class="arrow-link" href="<?php echo esc_url(bnwp_page_url('posts', $lang)); ?>">
                <?php echo esc_html(bnwp_text('সব পোস্ট', 'All posts')); ?>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h15M13 6l6 6-6 6"/></svg>
            </a>
        </div>

        <ul class="postlist reveal">
            <?php while ($recent->have_posts()) : $recent->the_post(); ?>
            <li class="postlist__item">
                <a class="postlist__link" href="<?php echo esc_url(bnwp_lang_arg(get_permalink())); ?>">
                    <time class="postlist__meta" datetime="<?php echo esc_attr(bnwp_iso_date()); ?>"><?php echo esc_html(bnwp_post_date()); ?></time>
                    <span>
                        <span class="postlist__title"><?php the_title(); ?></span>
                        <?php $ex = get_the_excerpt(); if ($ex) : ?>
                            <span class="postlist__excerpt"><?php echo esc_html(wp_trim_words($ex, 20, '…')); ?></span>
                        <?php endif; ?>
                    </span>
                    <span class="postlist__meta"><?php echo esc_html(bnwp_reading_time()); ?></span>
                </a>
            </li>
            <?php endwhile; wp_reset_postdata(); ?>
        </ul>
    </div>
</section>
<?php endif; ?>


<?php
$people_args = array(
    'post_type'      => 'persona',
    'posts_per_page' => 8,
    'no_found_rows'  => true,
    'tax_query'      => array(array('taxonomy' => 'team', 'field' => 'slug', 'terms' => 'cot')),
);
if (bnwp_lang_has_content('persona')) {
    $people_args['meta_query'] = array(bnwp_lang_meta_query());
}
$people = new WP_Query($people_args);
if ($people->have_posts()) : ?>
<section class="section section--sunken">
    <div class="wrap">
        <div class="section__head">
            <div>
                <h2><?php echo esc_html(bnwp_text('মূল দল', 'Core team')); ?></h2>
                <p><?php echo esc_html(bnwp_text('যাঁরা এই উদ্যোগ এগিয়ে নিচ্ছেন', 'The people driving this initiative')); ?></p>
            </div>
            <a class="arrow-link" href="<?php echo esc_url(bnwp_lang_arg($persona ? $persona : home_url('/persona/'), $lang)); ?>">
                <?php echo esc_html(bnwp_text('সব সদস্য', 'All members')); ?>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h15M13 6l6 6-6 6"/></svg>
            </a>
        </div>

        <div class="grid grid--4" data-stagger>
            <?php while ($people->have_posts()) : $people->the_post();
                $username = bnwp_get_meta('_bnwp_username');
                $role     = bnwp_get_meta('_bnwp_role');
            ?>
            <a class="person reveal" href="<?php echo esc_url(bnwp_lang_arg(get_permalink())); ?>">
                <?php bnwp_image(bnwp_get_meta('_bnwp_img'), array(
                    'w' => 176, 'h' => 176, 'class' => 'person__avatar',
                    'alt' => get_the_title(),
                )); ?>
                <span class="person__name"><?php the_title(); ?></span>
                <?php if ($username) : ?><span class="person__handle">@<?php echo esc_html($username); ?></span><?php endif; ?>
                <?php if ($role) : ?><span class="person__role"><?php echo esc_html($role); ?></span><?php endif; ?>
            </a>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </div>
</section>
<?php endif; ?>


<section class="section">
    <div class="wrap" style="text-align:center;">
        <h2 style="font-size:var(--step-3);"><?php echo esc_html(bnwp_text('আমাদের অংশীদার', 'Our partners')); ?></h2>
        <div class="partners" data-stagger style="margin-top:2rem;">
            <?php
            // file, name, intrinsic w, intrinsic h, url
            $partners = array(
                array('Wikimedia_Foundation_logo_-_vertical.png', 'Wikimedia Foundation', 614, 459, 'https://wikimediafoundation.org/'),
                array('Wikimedia_Bangladesh_logo.png',            'Wikimedia Bangladesh',  500, 512, 'https://bd.wikimedia.org/'),
                array('WikiNandini_text_logo_2024.png',           'WikiNandini',          1042, 240, 'https://meta.wikimedia.org/wiki/WikiNandini'),
                array('Wiki_Loves_Women_South_Asia.png',          'Wiki Loves Women',      695, 353, 'https://meta.wikimedia.org/wiki/Wiki_Loves_Women'),
            );
            foreach ($partners as $p) :
                $h = 58; // must match .partner img height in app.css
                $w = (int) round($h * ($p[2] / $p[3]));
            ?>
                <a class="partner reveal" href="<?php echo esc_url($p[4]); ?>" rel="noopener">
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/uploads/' . $p[0]); ?>"
                         width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>"
                         alt="<?php echo esc_attr($p[1]); ?>" loading="lazy" decoding="async">
                    <span class="partner__name"><?php echo esc_html($p[1]); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php get_footer(); ?>
