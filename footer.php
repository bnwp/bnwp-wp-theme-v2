<?php
/**
 * Site footer.
 */
if (!defined('ABSPATH')) { exit; }

$lang    = bnwp_current_language();
$persona = get_post_type_archive_link('persona');
$project = get_post_type_archive_link('project');
?>
</main><!-- /#main -->

<footer class="site-footer">
    <div class="wrap">
        <div class="site-footer__grid">

            <div>
                <a class="brand" href="<?php echo esc_url(bnwp_lang_arg(home_url('/'))); ?>" rel="home">
                    <?php bnwp_logo_mark(34); ?>
                    <span class="brand__name"><?php echo esc_html(bnwp_site_name()); ?></span>
                </a>
                <p style="margin-top:1rem;color:var(--ink-soft);max-width:42ch;">
                    <?php echo esc_html(bnwp_text(
                        'উইকিমিডিয়ানদের একটি সহযোগিতামূলক উদ্যোগ, যা বাংলা ভাষায় মুক্ত জ্ঞান সম্প্রসারণে কাজ করে।',
                        'A collaborative initiative of Wikimedians working to expand free knowledge in Bangla.'
                    )); ?>
                </p>
            </div>

            <div>
                <h3><?php echo esc_html(bnwp_text('সাইট', 'Site')); ?></h3>
                <?php if (has_nav_menu('footer')) : ?>
                    <?php wp_nav_menu(array('theme_location' => 'footer', 'container' => false, 'menu_class' => '', 'depth' => 1)); ?>
                <?php else : ?>
                    <ul>
                        <li><a href="<?php echo esc_url(bnwp_page_url('about', $lang)); ?>"><?php echo esc_html(bnwp_text('পরিচিতি', 'About')); ?></a></li>
                        <li><a href="<?php echo esc_url(bnwp_lang_arg($project ? $project : home_url('/projects/'), $lang)); ?>"><?php echo esc_html(bnwp_text('প্রকল্পসমূহ', 'Projects')); ?></a></li>
                        <li><a href="<?php echo esc_url(bnwp_lang_arg($persona ? $persona : home_url('/persona/'), $lang)); ?>"><?php echo esc_html(bnwp_text('সদস্য', 'Members')); ?></a></li>
                        <li><a href="<?php echo esc_url(bnwp_page_url('posts', $lang)); ?>"><?php echo esc_html(bnwp_text('পোস্টসমূহ', 'Posts')); ?></a></li>
                        <li><a href="<?php echo esc_url(bnwp_page_url('contact', $lang)); ?>"><?php echo esc_html(bnwp_text('যোগাযোগ', 'Contact')); ?></a></li>
                    </ul>
                <?php endif; ?>
            </div>

            <div>
                <h3><?php echo esc_html(bnwp_text('উইকিমিডিয়া', 'Wikimedia')); ?></h3>
                <ul>
                    <li><a href="https://meta.wikimedia.org/wiki/Bangla_WikiConnect">Meta-Wiki</a></li>
                    <li><a href="https://bn.wikipedia.org/"><?php echo esc_html(bnwp_text('বাংলা উইকিপিডিয়া', 'Bangla Wikipedia')); ?></a></li>
                    <li><a href="https://bn.wiktionary.org/"><?php echo esc_html(bnwp_text('বাংলা উইকিঅভিধান', 'Bangla Wiktionary')); ?></a></li>
                    <li><a href="https://commons.wikimedia.org/"><?php echo esc_html(bnwp_text('উইকিমিডিয়া কমন্স', 'Wikimedia Commons')); ?></a></li>
                </ul>
            </div>

            <div>
                <h3><?php echo esc_html(bnwp_text('যুক্ত হোন', 'Connect')); ?></h3>
                <ul>
                    <li><a href="<?php echo esc_url(bnwp_page_url('contact', $lang)); ?>"><?php echo esc_html(bnwp_text('যোগাযোগ', 'Contact us')); ?></a></li>
                    <li><a href="mailto:connect@bnwp.org">connect@bnwp.org</a></li>
                </ul>
            </div>

        </div>

        <div class="site-footer__legal">
            <p><?php
                printf(
                    /* translators: %s: current year */
                    esc_html(bnwp_text('© %s বাংলা উইকিসংযোগ', '© %s Bangla WikiConnect')),
                    esc_html(bnwp_num(wp_date('Y')))
                );
            ?></p>
            <p><?php echo esc_html(bnwp_text(
                'লেখা CC BY-SA 4.0 লাইসেন্সে প্রকাশিত।',
                'Text is available under CC BY-SA 4.0.'
            )); ?></p>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
