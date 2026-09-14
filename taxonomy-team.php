<?php get_header(); ?>
<section class="container" id="TeamMembers">
  <div class="d-flex align-items-center p-3 my-3 bg-dark text-white"><div class="lh-1 m-auto"><h1 class="mb-0 lh-1"><?php single_term_title(); ?></h1></div></div>
  <div class="row">
  <?php if (have_posts()) : while (have_posts()) : the_post(); $img = bnwp_clean_image_url(bnwp_get_meta('_bnwp_img'), bnwp_get_avatar_placeholder()); $username = bnwp_get_meta('_bnwp_username'); $role = bnwp_get_meta('_bnwp_role'); ?>
    <div class="col-lg-4 my-3 text-center fw-bold"><div class="p-3 rounded-3 border bnwp-member-card">
      <div class="cots__avatar bnwp-member-avatar m-auto mb-3"><img width="100%" height="100%" style="object-fit:cover" class="rounded-circle" src="<?php echo esc_url($img); ?>" onerror="this.onerror=null;this.src='<?php echo esc_url(bnwp_get_avatar_placeholder()); ?>';" alt="<?php the_title_attribute(); ?>"></div>
      <h3 class="cots__name fw-normal"><?php the_title(); ?></h3>
      <?php if ($username) : ?><small class="d-block bnwp-member-username">@<?php echo esc_html($username); ?></small><?php endif; ?>
      <?php if ($role) : ?><small class="cots__role"><?php echo esc_html($role); ?></small><?php endif; ?>
      <p class="mb-0 mt-auto"><a class="py-3 d-block bnwp-member-link" href="<?php echo esc_url(bnwp_lang_arg(get_permalink())); ?>"><?php echo esc_html(bnwp_text('বিস্তারিত দেখুন', 'View details')); ?> <i class="bi bi-arrow-right-circle-fill"></i></a></p>
    </div></div>
  <?php endwhile; bnwp_posts_pagination(); else : ?><p><?php echo esc_html(bnwp_text('এই দলে কোনো সদস্য পাওয়া যায়নি।', 'No members found in this team.')); ?></p><?php endif; ?>
  </div>
</section>
<?php get_footer(); ?>
