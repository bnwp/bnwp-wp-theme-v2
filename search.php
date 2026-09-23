<?php
/**
 * Search results.
 */
if (!defined('ABSPATH')) { exit; }

get_header();

$query = get_search_query();
$found = (int) $GLOBALS['wp_query']->found_posts;
?>

<div class="pagehead pagehead--sunken">
    <div class="wrap wrap--narrow">
        <h1><?php echo esc_html(bnwp_text('অনুসন্ধান', 'Search')); ?></h1>
        <?php get_search_form(); ?>
        <?php if ($query !== '') : ?>
            <p style="margin:1.25rem 0 0;color:var(--ink-soft);">
                <?php
                printf(
                    esc_html(bnwp_text('“%1$s” এর জন্য %2$s টি ফলাফল', '%2$s results for “%1$s”')),
                    esc_html($query),
                    esc_html(bnwp_num(number_format_i18n($found)))
                );
                ?>
            </p>
        <?php endif; ?>
    </div>
</div>

<div class="section">
    <div class="wrap wrap--narrow">
        <?php if (have_posts()) : ?>
            <ul class="postlist">
                <?php while (have_posts()) : the_post(); ?>
                <li class="postlist__item reveal">
                    <a class="postlist__link" href="<?php echo esc_url(bnwp_lang_arg(get_permalink())); ?>">
                        <span class="postlist__meta"><?php
                            $pt = get_post_type_object(get_post_type());
                            echo esc_html($pt ? $pt->labels->singular_name : '');
                        ?></span>
                        <span>
                            <span class="postlist__title"><?php the_title(); ?></span>
                            <span class="postlist__excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 24, '…')); ?></span>
                        </span>
                    </a>
                </li>
                <?php endwhile; ?>
            </ul>

            <div class="pagination"><?php bnwp_pagination(); ?></div>
        <?php else : ?>
            <p class="notice"><?php echo esc_html(bnwp_text(
                'কিছু পাওয়া যায়নি। অন্য শব্দ দিয়ে চেষ্টা করুন।',
                'Nothing found. Try a different term.'
            )); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
