<?php get_header(); ?>
<?php while (have_posts()) : the_post(); $cover = bnwp_get_meta('_bnwp_cover'); $lead = bnwp_get_meta('_bnwp_lead'); $wiki = bnwp_get_meta('_bnwp_wiki'); ?>
<section>
  <div id="project__header" style="background-image:url(<?php echo esc_url($cover); ?>);background-size:cover;background-repeat:no-repeat;background-color:#ffc107;background-blend-mode:screen;">
    <div class="container py-5">
      <h1 class="text-body-emphasis"><?php the_title(); ?></h1>
      <?php if ($lead) : ?><p class="fs-5 col-md-8"><?php echo esc_html($lead); ?></p><?php endif; ?>
      <?php if ($wiki) : ?><div class="mb-5"><a href="<?php echo esc_url($wiki); ?>" class="btn btn-primary btn-lg px-4"><?php echo esc_html(bnwp_text('উইকিতে দেখুন', 'View on the wiki')); ?></a></div><?php endif; ?>
    </div>
  </div>
  <div class="container"><hr><?php the_content(); ?></div>
</section>
<?php endwhile; ?>
<?php get_footer(); ?>
