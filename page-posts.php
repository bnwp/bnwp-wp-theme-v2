<?php get_header(); ?>
<div class="bg-primary py-4">
  <div class="container py-5 text-center text-lg-start"><h1 class="h3 mb-0 text-light"><?php the_title(); ?></h1></div>
</div>
<div class="container"><div class="row justify-content-center pt-5 mt-2"><section class="col-lg-9">
<?php
$paged = max(1, get_query_var('paged'));
$q = new WP_Query(array(
    'post_type' => 'post',
    'posts_per_page' => 6,
    'paged' => $paged,
    'meta_key' => '_bnwp_language',
    'meta_value' => bnwp_current_language(),
));
if ($q->have_posts()) : while ($q->have_posts()) : $q->the_post(); ?>
  <article class="row border-bottom py-5 bnwp-post-list-item">
    <div class="col-12 col-md-5 bnwp-post-list-meta">
      <span class="dateofpost"><?php echo esc_html(bnwp_post_date()); ?></span>
      <h5 class="bnwp-post-list-title"><a class="fw-bold" href="<?php echo esc_url(bnwp_lang_arg(get_permalink())); ?>"><?php the_title(); ?></a></h5>
      <div class="fs-6 pe-2 mb-2"><?php the_tags('<span class="badge text-bg-dark">#', '</span> <span class="badge text-bg-dark">#', '</span>'); ?></div>
    </div>
    <div class="col-12 col-md-7 text-justify"><?php echo esc_html(wp_trim_words(get_the_excerpt() ?: wp_strip_all_tags(get_the_content()), 70)); ?> <a class="text-muted" href="<?php echo esc_url(bnwp_lang_arg(get_permalink())); ?>">[<?php echo esc_html(bnwp_text('আরও পড়ুন', 'Read more')); ?>]</a></div>
  </article>
<?php endwhile; wp_reset_postdata(); else : ?><p><?php echo esc_html(bnwp_text('কোনো ব্লগ পোস্ট পাওয়া যায়নি।', 'No blog posts found.')); ?></p><?php endif; ?>
</section></div></div>
<?php get_footer(); ?>
