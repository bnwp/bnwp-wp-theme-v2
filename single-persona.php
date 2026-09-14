<?php get_header(); ?>
<?php while (have_posts()) : the_post();
$cover = bnwp_get_meta('_bnwp_cover');
$img = bnwp_clean_image_url(bnwp_get_meta('_bnwp_img'), bnwp_get_avatar_placeholder());
$name = bnwp_get_meta('_bnwp_name', get_the_ID(), get_the_title());
$role = bnwp_get_meta('_bnwp_role');
$username = bnwp_get_meta('_bnwp_username');
$email = bnwp_get_meta('_bnwp_email');
$location = bnwp_get_meta('_bnwp_location');
$bio = bnwp_get_meta('_bnwp_bio');
?>
<div id="project__header" style="background-image:url(<?php echo esc_url($cover); ?>);background-size:cover;background-repeat:no-repeat;background-color:#fff3cd;background-blend-mode:screen;">
  <div class="container py-5"><div class="d-flex flex-column flex-md-row align-items-center align-items-md-start gap-4">
    <div class="flex-shrink-0 text-center"><div style="width:160px;height:160px;border-radius:50%;overflow:hidden;border:4px solid #fff;box-shadow:0 4px 16px rgba(0,0,0,.18);"><img width="160" height="160" style="object-fit:cover;width:160px;height:160px;" src="<?php echo esc_url($img); ?>" onerror="this.onerror=null;this.src='<?php echo esc_url(bnwp_get_avatar_placeholder()); ?>';" alt="<?php echo esc_attr($name); ?>"></div></div>
    <div><h1 class="display-4"><?php echo esc_html($name); ?></h1><?php if ($role) : ?><h5 class="fw-bold"><?php echo esc_html($role); ?></h5><?php endif; ?>
      <h5><?php if ($username) : ?><i class="bi bi-at me-1"></i><a href="https://bn.wikipedia.org/wiki/User:<?php echo rawurlencode($username); ?>"><?php echo esc_html($username); ?></a><?php endif; ?><?php if ($email) : ?> <i class="bi bi-envelope-at ms-2"></i><a class="ms-1" href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a><?php endif; ?><?php if ($location) : ?> <i class="bi bi-geo-alt ms-2"></i> <?php echo esc_html($location); ?><?php endif; ?></h5>
      <?php if ($bio) : ?><p class="lead my-3"><?php echo esc_html($bio); ?></p><?php endif; ?>
    </div>
  </div></div>
</div>
<div class="container py-5"><?php the_content(); ?></div>
<div class="container py-5"><h2><?php echo esc_html(bnwp_text('ব্লগ', 'Blogs')); ?></h2><ul>
<?php if ($username) : $blogs = new WP_Query(array('post_type' => 'post', 'posts_per_page' => 10, 'meta_query' => array(array('key' => '_bnwp_user', 'value' => $username), array('key' => '_bnwp_language', 'value' => bnwp_current_language())))); while ($blogs->have_posts()) : $blogs->the_post(); ?><li><a href="<?php echo esc_url(bnwp_lang_arg(get_permalink())); ?>"><?php the_title(); ?></a></li><?php endwhile; wp_reset_postdata(); endif; ?>
</ul></div>
<?php endwhile; ?>
<?php get_footer(); ?>
