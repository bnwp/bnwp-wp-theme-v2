<?php get_header(); ?>
<div class="container text-center py-5">
<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/uploads/error_404.svg'); ?>" alt="404" style="max-width:360px;">
<h1><?php echo esc_html(bnwp_text('পৃষ্ঠা পাওয়া যায়নি', 'Page not found')); ?></h1>
<p><?php echo esc_html(bnwp_text('আপনি যে পৃষ্ঠাটি খুঁজছেন সেটি নেই বা সরানো হয়েছে।', 'The page you are looking for does not exist or has been moved.')); ?></p>
<a class="btn btn-primary" href="<?php echo esc_url(bnwp_lang_arg(home_url('/'))); ?>"><?php echo esc_html(bnwp_text('নীড়ে ফিরুন', 'Return home')); ?></a>
</div>
<?php get_footer(); ?>
