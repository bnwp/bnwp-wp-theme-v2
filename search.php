<?php get_header(); ?>
<div class="page-title-overlap bg-primary py-5"><div class="container"><div class="py-5 mb-4 block-center"><h3 class="text-light pt-1"><?php echo esc_html(bnwp_text('অনুসন্ধান', 'Search')); ?></h3></div></div></div>
<div class="container py-4 mb-2 mb-md-4"><div class="bg-body shadow-lg p-4 mt-n5 mb-4"><?php get_search_form(); ?></div>
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
<article class="border-bottom py-4"><h4><a href="<?php echo esc_url(bnwp_lang_arg(get_permalink())); ?>"><?php the_title(); ?></a></h4><p><?php echo esc_html(wp_trim_words(get_the_excerpt() ?: wp_strip_all_tags(get_the_content()), 45)); ?></p></article>
<?php endwhile; bnwp_posts_pagination(); else : ?><p><?php echo esc_html(bnwp_text('কোনো ফলাফল পাওয়া যায়নি।', 'No results found.')); ?></p><?php endif; ?>
</div>
<?php get_footer(); ?>
