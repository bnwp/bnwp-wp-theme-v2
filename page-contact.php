<?php
/**
 * The "Contact" page.
 *
 * The contact details live here rather than in the page body, which is why
 * the page looks empty without this template. Carried over from v1.
 */
if (!defined('ABSPATH')) { exit; }

get_header();

$channels = array(
    array('Facebook', 'https://facebook.com/banglawikiconnect', 'M15 8h-2.5c-.5 0-1 .4-1 1v2H15l-.4 3h-3v8H8.4v-8H6v-3h2.4V9.2C8.4 6.9 9.9 5 12.6 5H15Z'),
    array('YouTube',  'https://youtube.com/@banglawikiconnect', 'M21.6 7.2c-.2-.9-.9-1.6-1.8-1.8C18.2 5 12 5 12 5s-6.2 0-7.8.4c-.9.2-1.6.9-1.8 1.8C2 8.8 2 12 2 12s0 3.2.4 4.8c.2.9.9 1.6 1.8 1.8C5.8 19 12 19 12 19s6.2 0 7.8-.4c.9-.2 1.6-.9 1.8-1.8.4-1.6.4-4.8.4-4.8s0-3.2-.4-4.8ZM10 15V9l5 3-5 3Z'),
    array('LinkedIn', 'https://www.linkedin.com/company/wikiconnect', 'M6.9 8.5H4V20h2.9V8.5ZM5.4 4a1.7 1.7 0 1 0 0 3.4 1.7 1.7 0 0 0 0-3.4ZM20 13.4c0-3-1.6-4.4-3.8-4.4-1.7 0-2.5.9-3 1.6V8.5H10.4V20h2.9v-6.2c0-1.3.6-2.2 1.8-2.2s1.9.8 1.9 2.2V20H20Z'),
    array('Telegram', 'https://t.me/bnwikiconnect', 'M21.7 4.4 2.9 11.6c-.9.3-.9 1.6 0 1.9l4.6 1.5 1.8 5.4c.2.7 1.1.9 1.6.3l2.5-2.7 4.7 3.4c.6.5 1.5.1 1.7-.6l3-14.8c.2-.9-.7-1.6-1.1-1.6ZM9.6 14.5l8.2-5.3-6.9 6.5-.4 3.4-.9-4.6Z'),
    array('GitHub',   'https://github.com/bnwp', 'M12 2a10 10 0 0 0-3.2 19.5c.5.1.7-.2.7-.5v-1.8c-2.8.6-3.4-1.3-3.4-1.3-.4-1.2-1.1-1.5-1.1-1.5-.9-.6.1-.6.1-.6 1 .1 1.5 1 1.5 1 .9 1.5 2.3 1.1 2.9.8.1-.6.3-1.1.6-1.3-2.2-.3-4.6-1.1-4.6-5 0-1.1.4-2 1-2.7-.1-.3-.4-1.3.1-2.7 0 0 .8-.3 2.7 1a9.4 9.4 0 0 1 5 0c1.9-1.3 2.7-1 2.7-1 .5 1.4.2 2.4.1 2.7.6.7 1 1.6 1 2.7 0 3.9-2.4 4.7-4.6 5 .3.3.7 1 .7 2v2.9c0 .3.2.6.7.5A10 10 0 0 0 12 2Z'),
);
?>

<div class="pagehead pagehead--sunken">
    <div class="wrap">
        <h1><?php the_title(); ?></h1>
    </div>
</div>

<div class="section">
    <div class="wrap wrap--narrow">

        <?php if (trim(wp_strip_all_tags(get_the_content())) !== '') : ?>
            <div class="prose" style="margin-bottom:2.5rem;"><?php the_content(); ?></div>
        <?php else : ?>
            <p class="hero__lead" style="margin-top:0;"><?php echo esc_html(bnwp_text(
                'প্রকল্প, অংশগ্রহণ বা সহযোগিতা সংক্রান্ত যেকোনো বিষয়ে আমাদের সাথে যোগাযোগ করুন। আমরা সাধারণত কয়েক দিনের মধ্যে উত্তর দিই।',
                'Get in touch about our projects, taking part, or working together. We usually reply within a few days.'
            )); ?></p>
        <?php endif; ?>

        <div class="panel" style="margin-bottom:1.5rem;">
            <h2 class="panel__title"><?php echo esc_html(bnwp_text('ইমেইল', 'Email')); ?></h2>
            <p style="margin:0;font-size:var(--step-1);">
                <a href="mailto:connect@bnwp.org">connect@bnwp.org</a>
            </p>
        </div>

        <div class="panel">
            <h2 class="panel__title"><?php echo esc_html(bnwp_text('সামাজিক মাধ্যম', 'Find us online')); ?></h2>
            <ul class="channels">
                <?php foreach ($channels as $c) : ?>
                    <li>
                        <a class="channel" href="<?php echo esc_url($c[1]); ?>" rel="noopener">
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="<?php echo esc_attr($c[2]); ?>"/></svg>
                            <span><?php echo esc_html($c[0]); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

    </div>
</div>

<?php get_footer(); ?>
