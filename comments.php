<?php
/**
 * Comments: the list and the reply form, in the language being read.
 *
 * Without it WordPress used its own fallback template, which printed an
 * unstyled form whose strings came from the site locale — so a Bengali page
 * asked for a comment in English. Every visible string here
 * goes through bnwp_text(), and the markup takes the theme's own tokens.
 * The helpers live in functions.php, section 11.
 */
if (!defined('ABSPATH')) { exit; }

if (post_password_required()) {
    return;
}

// get_comments_number() counts approved comments only, but a reader's own
// comment awaiting moderation is listed for them too — so the heading counts
// whichever is larger, rather than announcing "0 comments" above one.
$bnwp_comment_count = max((int) get_comments_number(), (int) $GLOBALS['wp_query']->comment_count);
?>

<section id="comments" class="comments">
    <?php if (have_comments()) : ?>
        <h2 class="comments__title">
            <?php echo esc_html(bnwp_comment_count_label($bnwp_comment_count)); ?>
        </h2>

        <ol class="comment-list">
            <?php
            wp_list_comments(array(
                'style'      => 'ol',
                'short_ping' => true,
                'callback'   => 'bnwp_comment',
            ));
            ?>
        </ol>

        <?php
        the_comments_pagination(array(
            'class'              => 'pagination comments-pagination',
            'aria_label'         => bnwp_text('মন্তব্যের পাতা', 'Comment pages'),
            'screen_reader_text' => bnwp_text('মন্তব্যের পাতা', 'Comment pages'),
            'prev_text'          => '&larr; ' . esc_html(bnwp_text('আগের মন্তব্য', 'Older comments')),
            'next_text'          => esc_html(bnwp_text('পরের মন্তব্য', 'Newer comments')) . ' &rarr;',
        ));
        ?>
    <?php endif; ?>

    <?php if (!comments_open() && $bnwp_comment_count) : ?>
        <p class="notice"><?php echo esc_html(bnwp_text('এই লেখায় মন্তব্য বন্ধ রয়েছে।', 'Comments are closed on this post.')); ?></p>
    <?php endif; ?>

    <?php comment_form(bnwp_comment_form_args()); ?>
</section>
