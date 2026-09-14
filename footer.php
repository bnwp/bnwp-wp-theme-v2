<?php if (!defined('ABSPATH')) { exit; } ?>

</main>

<footer class="bg-dark text-white mt-auto">
    <div class="container py-5">

        <div class="row align-items-center mb-4 px-2">
            <div class="col-12 col-md-6 text-center text-md-start mb-3 mb-md-0">
                <a class="navbar-brand text-white text-decoration-none d-inline-flex flex-column flex-md-row align-items-center gap-2 gap-md-3" href="<?php echo esc_url(bnwp_lang_arg(home_url('/'))); ?>">
                    <img
                        class="bnwp-footer-brand-logo"
                        src="<?php echo esc_url(get_template_directory_uri() . '/assets/uploads/Bangla_WikiConnect_Logo_small.png'); ?>"
                        alt="<?php echo esc_attr(bnwp_site_name()); ?>"
                        loading="lazy"
                    >

                    <div class="text-center text-md-start">
                        <h5 class="mb-1 fw-bold"><?php echo esc_html(bnwp_site_name()); ?></h5>
                        <span class="d-block fs-6 text-white-50">
                            <?php bloginfo('description'); ?>
                        </span>
                    </div>
                </a>
            </div>

            <div class="col-12 col-md-6 text-center text-md-end">
                <a class="btn btn-secondary me-2 mb-2" href="<?php echo esc_url(bnwp_page_url('contact')); ?>">
                    <?php echo esc_html(bnwp_text('যোগাযোগ', 'Contact')); ?>
                </a>

                <a class="btn btn-secondary mb-2" href="<?php echo esc_url(bnwp_lang_arg(get_post_type_archive_link('persona'))); ?>">
                    <?php echo esc_html(bnwp_text('সদস্য', 'Members')); ?>
                </a>
            </div>
        </div>

        <div class="border-top border-secondary pt-4 text-center">
            <div class="fs-6 text-white-50">
                <?php echo esc_html(bnwp_text('এই সাইটের সমস্ত চিত্র ও ভিডিও কন্টেন্ট সিসি বাই-এসএ ৪.০ লাইসেন্সের আওতায় প্রকাশিত যদি না সংশ্লিষ্ট কনটেন্টে পৃথক লাইসেন্সের উল্লেখ থাকে। তবে এই সাইটের সমস্ত পাঠ্য কনটেন্ট মেধাসত্ত্বের অন্তর্ভুক্ত বলে গন্য হবে।', 'Unless otherwise noted, all image and video content on this site is available under CC BY-SA 4.0. All text content remains the intellectual property of the site.')); ?>
                <br>
                © <?php echo esc_html(date_i18n('Y')); ?>, <?php echo esc_html(bnwp_text('সিসি বাই-এসএ ৪.০', 'CC BY-SA 4.0')); ?> <?php echo esc_html(bnwp_site_name()); ?>
            </div>
        </div>

    </div>
</footer>

<?php wp_footer(); ?>

</body>
</html>
