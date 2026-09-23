<?php
/**
 * Single team member.
 */
if (!defined('ABSPATH')) { exit; }

get_header();

while (have_posts()) : the_post();
    $username = bnwp_get_meta('_bnwp_username');
    $role     = bnwp_get_meta('_bnwp_role');
    $location = bnwp_get_meta('_bnwp_location');
    $email    = bnwp_get_meta('_bnwp_email');
    $bio      = bnwp_get_meta('_bnwp_bio');
    $archive  = get_post_type_archive_link('persona');
    $terms    = get_the_terms(get_the_ID(), 'team');
?>

<article>
    <div class="pagehead pagehead--sunken">
        <div class="wrap">
            <nav class="breadcrumb" aria-label="<?php echo esc_attr(bnwp_text('ব্রেডক্রাম্ব', 'Breadcrumb')); ?>">
                <a href="<?php echo esc_url(bnwp_lang_arg(home_url('/'))); ?>"><?php echo esc_html(bnwp_text('প্রচ্ছদ', 'Home')); ?></a>
                <span>/</span>
                <a href="<?php echo esc_url(bnwp_lang_arg($archive ? $archive : home_url('/persona/'))); ?>"><?php echo esc_html(bnwp_text('সদস্যবৃন্দ', 'Members')); ?></a>
            </nav>

            <div style="display:flex;flex-wrap:wrap;gap:1.75rem;align-items:center;">
                <?php bnwp_image(bnwp_get_meta('_bnwp_img'), array(
                    'w' => 320, 'h' => 320, 'alt' => get_the_title(),
                    'class' => 'person__avatar', 'loading' => 'eager',
                )); ?>
                <div>
                    <h1 style="margin-bottom:.2em;"><?php the_title(); ?></h1>
                    <div class="meta-row">
                        <?php if ($username) : ?>
                            <a href="<?php echo esc_url('https://meta.wikimedia.org/wiki/User:' . rawurlencode($username)); ?>">@<?php echo esc_html($username); ?></a>
                        <?php endif; ?>
                        <?php if ($role) : ?><span aria-hidden="true">·</span><span><?php echo esc_html($role); ?></span><?php endif; ?>
                        <?php if ($location) : ?><span aria-hidden="true">·</span><span><?php echo esc_html($location); ?></span><?php endif; ?>
                    </div>
                    <?php if ($terms && !is_wp_error($terms)) : ?>
                        <p style="margin:.9rem 0 0;display:flex;gap:.5rem;flex-wrap:wrap;">
                            <?php foreach ($terms as $t) :
                                $link = get_term_link($t);
                                if (is_wp_error($link)) { continue; } ?>
                                <a class="chip" style="text-decoration:none;" href="<?php echo esc_url(bnwp_lang_arg($link)); ?>"><?php echo esc_html(bnwp_term_name($t)); ?></a>
                            <?php endforeach; ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="wrap layout-aside">
            <div>
                <?php if ($bio) : ?>
                    <p class="hero__lead" style="margin-top:0;"><?php echo esc_html($bio); ?></p>
                <?php endif; ?>
                <div class="prose"><?php the_content(); ?></div>
            </div>

            <aside>
                <div class="panel">
                    <h2 class="panel__title"><?php echo esc_html(bnwp_text('যোগাযোগ', 'Contact')); ?></h2>
                    <dl class="factlist">
                        <?php if ($username) : ?>
                            <dt><?php echo esc_html(bnwp_text('উইকি', 'Wiki')); ?></dt>
                            <dd><a href="<?php echo esc_url('https://meta.wikimedia.org/wiki/User:' . rawurlencode($username)); ?>">@<?php echo esc_html($username); ?></a></dd>
                        <?php endif; ?>
                        <?php if ($email) : ?>
                            <dt><?php echo esc_html(bnwp_text('ইমেইল', 'Email')); ?></dt>
                            <dd><a href="mailto:<?php echo esc_attr(antispambot($email)); ?>"><?php echo esc_html(antispambot($email)); ?></a></dd>
                        <?php endif; ?>
                        <?php if ($location) : ?>
                            <dt><?php echo esc_html(bnwp_text('অবস্থান', 'Location')); ?></dt>
                            <dd><?php echo esc_html($location); ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </aside>
        </div>
    </div>
</article>

<?php
endwhile;
get_footer();
