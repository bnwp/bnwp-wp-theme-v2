<?php get_header(); ?>
<div class="page-title-overlap bg-primary py-5"><div class="container"><div class="py-5 mb-4 block-center"><h3 class="text-light pt-1"><?php the_title(); ?></h3></div></div></div>
<div class="container py-4 mb-2 mb-md-4"><div class="bg-body shadow-lg p-4 mt-n5 mb-4"><?php get_search_form(); ?></div><p><?php echo esc_html(bnwp_text('উপরে কীওয়ার্ড লিখে অনুসন্ধান করুন।', 'Enter keywords above to search.')); ?></p></div>
<?php get_footer(); ?>
