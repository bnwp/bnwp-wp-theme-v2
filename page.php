<?php
/**
 * Static page.
 */
if (!defined('ABSPATH')) { exit; }

get_header();

while (have_posts()) : the_post(); ?>

<article>
    <div class="pagehead pagehead--sunken">
        <div class="wrap wrap--narrow">
            <h1><?php the_title(); ?></h1>
        </div>
    </div>

    <div class="section">
        <div class="wrap wrap--narrow">
            <?php if (has_post_thumbnail()) : ?>
                <figure style="margin:0 0 2rem;">
                    <?php the_post_thumbnail('large', array('style' => 'border-radius:var(--radius);width:100%;height:auto;')); ?>
                </figure>
            <?php endif; ?>

            <div class="prose"><?php the_content(); ?></div>

            <?php wp_link_pages(array(
                'before' => '<nav class="pagination"><div class="nav-links">',
                'after'  => '</div></nav>',
            )); ?>
        </div>
    </div>
</article>

<?php
endwhile;
get_footer();
