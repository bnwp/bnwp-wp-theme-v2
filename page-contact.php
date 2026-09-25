<?php
/**
 * The "Contact" page.
 *
 * The contact details live here rather than in the page body, which is why
 * the page looks empty without this template. Carried over from v1.
 */
if (!defined('ABSPATH')) { exit; }

get_header();

$channels = bnwp_socials();
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

        <?php if ($channels) : ?>
        <div class="panel" style="margin-bottom:1.5rem;">
            <h2 class="panel__title"><?php echo esc_html(bnwp_text('সামাজিক মাধ্যম', 'Find us online')); ?></h2>
            <ul class="channels">
                <?php foreach ($channels as $c) : ?>
                    <li>
                        <a class="channel" href="<?php echo esc_url($c['url']); ?>" rel="noopener">
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="<?php echo esc_attr(bnwp_social_icon($c['icon'])); ?>"/></svg>
                            <span><?php echo esc_html($c['label']); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="panel" style="margin-bottom:1.5rem;">
            <h2 class="panel__title"><?php echo esc_html(bnwp_text('ইমেইল', 'Email')); ?></h2>
            <p style="margin:0;font-size:var(--step-1);">
                <a href="mailto:<?php echo esc_attr(bnwp_contact_address()); ?>"><?php echo esc_html(bnwp_contact_address()); ?></a>
            </p>
        </div>

        <?php $form = bnwp_contact_form(); ?>
        <?php if ($form) : ?>
        <div class="panel">
            <h2 class="panel__title"><?php echo esc_html(bnwp_text('বার্তা পাঠান', 'Send a message')); ?></h2>
            <p class="contactform__note">
                <?php printf(
                    /* translators: %s is the address messages are delivered to. */
                    esc_html(bnwp_text('এই ফর্মে পাঠানো বার্তা যাবে %s ঠিকানায়।', 'Messages sent through this form are delivered to %s.')),
                    '<strong>' . esc_html(bnwp_contact_address()) . '</strong>'
                ); ?>
            </p>
            <div class="contactform"><?php echo $form; // already run through do_shortcode ?></div>
        </div>
        <?php endif; ?>


    </div>
</div>

<?php get_footer(); ?>
