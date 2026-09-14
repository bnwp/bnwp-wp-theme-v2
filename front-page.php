<?php get_header(); ?>

<section class="container my-5">
    <div class="row align-items-center py-5 px-3 px-md-0">
        <div class="col-lg-7">
            <h1 class="display-4 text-body-emphasis"><?php echo esc_html(bnwp_text('বাংলা উইকিসংযোগ একটি সহযোগিতামূলক উদ্যোগ যা...', 'Bangla WikiConnect is a collaborative initiative that...')); ?></h1>
            <p class="lead"><?php echo esc_html(bnwp_text('... বাংলা ভাষায় উইকিপিডিয়ার বিষয়বস্তু বৃদ্ধি এবং সম্প্রসারণের উপর দৃষ্টি নিবদ্ধ করে। বিভিন্ন আকর্ষণীয় প্রতিযোগিতা, সম্পাদনা-অ-থন এবং প্রশিক্ষণ কর্মসূচির মাধ্যমে, আমরা উইকিপিডিয়া এবং এর সহযোগী প্রকল্প যেমন উইকিকোট, উইকিভ্রমণ, উইকিবই এবং উইকশনারিতে উচ্চমানের, অন্তর্ভুক্তিমূলক বিষয়বস্তু তৈরি করার লক্ষ্য রাখি।', '...focuses on growing and expanding Wikipedia content in Bangla. Through engaging competitions, edit-a-thons, and training programs, we aim to create high-quality, inclusive content across Wikipedia and sister projects such as Wikiquote, Wikivoyage, Wikibooks, and Wiktionary.')); ?></p>
            <div class="front-hero-buttons">
                <a href="<?php echo esc_url(bnwp_page_url('about')); ?>" class="btn btn-primary fw-bold">
                    <?php echo esc_html(bnwp_text('আরও জানুন', 'Learn more')); ?>
                </a>

                <a href="https://meta.wikimedia.org/wiki/Bangla_WikiConnect" class="btn btn-secondary">
                    <?php echo esc_html(bnwp_text("মেটা'উইকিতে পড়ুন", 'Read on Meta-Wiki')); ?>
                </a>
            </div>
        </div>
        <div class="col-lg-4 offset-lg-1 p-0">
            <img class="rounded-lg-3" src="<?php echo esc_url(get_template_directory_uri() . '/assets/uploads/Bangla_WikiConnect_LOGO.png'); ?>" alt="Bangla WikiConnect" width="720">
        </div>
    </div>
</section>

<section class="bg-green-5 position-relative overflow-hidden" id="homeHero">
    <div class="container py-10 py-5">
        <div class="carousel slide" data-bs-ride="carousel" id="QuoteCarousel">
            <?php $quotes = bnwp_current_language() === 'en'
                ? array('✨ 1.6M+ words added so far!', '📝 2,000+ articles!', '🖼️ 100+ images uploaded!', '👥 20+ organizers!', '🛠️ 2 workshops', '📘 2 tutorials')
                : array('✨ এখন পর্যন্ত ১৬ লক্ষ+ শব্দ যোগ!', '📝 ২০০০+ নিবন্ধ!', '🖼️ ১০০+ চিত্র আপলোড!', '👥 ২০+ আয়োজক!', '🛠️ ২ টি কর্মশালা', '📘 ২ টি টিউটোরিয়াল'); ?>
            <ul class="carousel-indicators">
                <?php foreach ($quotes as $i => $quote) : ?>
                    <li data-bs-slide-to="<?php echo esc_attr($i); ?>" data-bs-target="#QuoteCarousel" class="<?php echo $i === 0 ? 'active' : ''; ?>"></li>
                <?php endforeach; ?>
            </ul>
            <div class="carousel-inner">
                <?php foreach ($quotes as $i => $quote) : ?>
                    <div class="carousel-item<?php echo $i === 0 ? ' active' : ''; ?>">
                        <figure class="text-center"><blockquote class="blockquote fs-3 fw-bold"><?php echo esc_html($quote); ?></blockquote></figure>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section class="container py-5">
    <h2 class="text-center mb-5 fw-bold"><?php echo esc_html(bnwp_text('আমাদের প্রকল্পসমূহ', 'Our Projects')); ?></h2>
    <div class="row row-cols-1 row-cols-sm-3 g-4">
        <?php
        $projects = new WP_Query(array('post_type' => 'project', 'posts_per_page' => 6, 'meta_key' => '_bnwp_language', 'meta_value' => bnwp_current_language()));
        if (!$projects->have_posts()) {
            echo '<p>' . esc_html(bnwp_text('কোনো প্রকল্প পাওয়া যায়নি।', 'No projects found.')) . '</p>';
        }
        while ($projects->have_posts()) : $projects->the_post();
            $logo = bnwp_get_meta('_bnwp_logo');
            $lead = bnwp_get_meta('_bnwp_lead');
        ?>
            <div class="col">
                <?php if ($logo) : ?><div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-4"><img src="<?php echo esc_url($logo); ?>" alt="" style="width:auto;height:100px"></div><?php endif; ?>
                <h4 class="fw-semibold mb-0 text-body-emphasis"><?php the_title(); ?></h4>
                <a class="py-3 d-block" href="<?php echo esc_url(bnwp_lang_arg(get_permalink())); ?>"><?php echo esc_html(bnwp_text('বিস্তারিত দেখুন', 'View details')); ?> <i class="bi bi-arrow-right-circle-fill"></i></a>
                <?php if ($lead) : ?><p class="text-body-secondary"><?php echo esc_html(wp_trim_words($lead, 22)); ?></p><?php endif; ?>
            </div>
        <?php endwhile; wp_reset_postdata(); ?>
    </div>
</section>

<section class="container" id="TeamMembers">
    <h3 class="section__title cots__title text-center my-3"><?php echo esc_html(bnwp_text('মূল দল', 'Core Team')); ?></h3>
    <div class="row">
        <?php
        $people = new WP_Query(array(
            'post_type' => 'persona',
            'posts_per_page' => 12,
            'meta_key' => '_bnwp_language',
            'meta_value' => bnwp_current_language(),
            'tax_query' => array(array('taxonomy' => 'team', 'field' => 'slug', 'terms' => 'cot')),
        ));
        if (!$people->have_posts()) {
            echo '<p>' . esc_html(bnwp_text('কোনো সদস্য পাওয়া যায়নি।', 'No members found.')) . '</p>';
        }
        while ($people->have_posts()) : $people->the_post();
            $img = bnwp_clean_image_url(bnwp_get_meta('_bnwp_img'), bnwp_get_avatar_placeholder());
            $username = bnwp_get_meta('_bnwp_username');
        ?>
            <div class="col-lg-4 my-3 text-center fw-bold">
                <div class="p-3 rounded-3 border bnwp-member-card">
                    <div class="cots__avatar bnwp-member-avatar m-auto mb-3">
                        <img width="100%" height="100%" style="object-fit:cover" class="rounded-circle" src="<?php echo esc_url($img); ?>" onerror="this.onerror=null;this.src='<?php echo esc_url(bnwp_get_avatar_placeholder()); ?>';" alt="<?php the_title_attribute(); ?>">
                    </div>
                    <h3 class="cots__name fw-normal"><?php the_title(); ?></h3>
                    <?php if ($username) : ?><small class="d-block bnwp-member-username">@<?php echo esc_html($username); ?></small><?php endif; ?>
                    <p class="mb-0 mt-auto"><a class="py-3 d-block bnwp-member-link" href="<?php echo esc_url(bnwp_lang_arg(get_permalink())); ?>"><?php echo esc_html(bnwp_text('বিস্তারিত দেখুন', 'View details')); ?> <i class="bi bi-arrow-right-circle-fill"></i></a></p>
                </div>
            </div>
        <?php endwhile; wp_reset_postdata(); ?>
    </div>
</section>

<section class="container my-5 text-center py-5">
    <h2 class="my-5"><?php echo esc_html(bnwp_text('আমাদের অংশীদার', 'Our Partners')); ?></h2>
    <div class="d-flex flex-wrap" style="justify-content:center;gap:1rem">
        <div class="text-secondary bg-light p-4"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/uploads/Wikimedia_Foundation_logo_-_vertical.png'); ?>" alt="Wikimedia Foundation" style="height:80px;width:auto"></div>
        <div class="text-secondary bg-light p-4"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/uploads/WikiNandini_text_logo_2024.png'); ?>" alt="WikiNandini" style="height:80px;width:auto"></div>
        <div class="text-secondary bg-light p-4"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/uploads/Wikimedia_Bangladesh_logo.png'); ?>" alt="Wikimedia Bangladesh" style="height:80px;width:auto"></div>
    </div>
</section>

<?php get_footer(); ?>
