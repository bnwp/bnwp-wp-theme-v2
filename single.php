<?php get_header(); ?>
<?php while (have_posts()) : the_post(); ?>
<article id="article" itemscope itemtype="https://schema.org/Article">
  <div id="articleSchema" class="bg-body-tertiary border-bottom">
    <div class="container py-5">
      <h3 id="articleTitle" itemprop="headline"><?php the_title(); ?></h3>
      <?php $user = bnwp_get_meta('_bnwp_user'); if ($user) : ?>
        <div id="articleAuthor" itemprop="author"><i class="bi bi-person-fill-check pe-1"></i><?php echo esc_html($user); ?></div>
      <?php endif; ?>
      <span id="articleTags" class="d-block"><?php the_tags('<i class="bi bi-tag-fill pe-1"></i>', ' ▪ ', ''); ?></span>
      <span id="articleReadingTime" class="fs-6"><i class="bi bi-alarm-fill pe-2"></i><?php echo esc_html(bnwp_text('পড়ার সময়:', 'Reading time:')); ?> <?php echo esc_html(bnwp_reading_time()); ?> <?php echo esc_html(bnwp_text('মিনিট', 'min')); ?></span>
      <span id="articlePublicationDate" class="fs-6 ms-3"><i class="bi bi-calendar-week-fill pe-2"></i><?php echo esc_html(bnwp_text('প্রকাশ:', 'Published:')); ?> <?php echo esc_html(bnwp_post_date()); ?></span>
    </div>
  </div>
  <div class="container">
    <div id="articleGrid" class="row g-5 my-1">
      <div id="articleContent" class="col-md-8 text-justify">
        <div id="articleBody" itemprop="articleBody"><?php the_content(); ?></div>
        <div id="articleShare" class="border-top border-bottom py-2">
          <label class="me-2"><?php echo esc_html(bnwp_text('শেয়ার করুন:', 'Share:')); ?></label>
          <a class="me-2" href="https://www.facebook.com/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" rel="nofollow noopener noreferrer" target="_blank" title="Facebook Share"><i class="bi-facebook"></i></a>
          <a class="me-2" href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>" rel="nofollow noopener noreferrer" target="_blank" title="Tweet This"><i class="bi-twitter"></i></a>
          <a class="me-2" href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(get_permalink()); ?>" rel="nofollow noopener noreferrer" target="_blank" title="Linkedin Share"><i class="bi-linkedin"></i></a>
        </div>
      </div>
      <aside id="articleSidebar" class="col-md-4">
        <div class="position-sticky" style="top:2rem;">
          <h4><?php echo esc_html(bnwp_text('সাম্প্রতিক পোস্ট', 'Recent posts')); ?></h4>
          <ul class="list-unstyled">
            <?php $recent = new WP_Query(array('post_type' => 'post', 'posts_per_page' => 3, 'post__not_in' => array(get_the_ID()), 'meta_key' => '_bnwp_language', 'meta_value' => bnwp_current_language())); while ($recent->have_posts()) : $recent->the_post(); ?>
              <li class="py-3 border-top"><a href="<?php echo esc_url(bnwp_lang_arg(get_permalink())); ?>"><h5 class="mb-0"><?php the_title(); ?></h5><span class="d-block fs-6 text-muted"><?php echo esc_html(bnwp_post_date()); ?></span></a></li>
            <?php endwhile; wp_reset_postdata(); ?>
          </ul>
          <h4><?php echo esc_html(bnwp_text('ট্যাগ', 'Tags')); ?></h4><?php wp_tag_cloud(array('smallest' => 10, 'largest' => 16, 'unit' => 'px')); ?>
        </div>
      </aside>
    </div>
  </div>
</article>
<?php endwhile; ?>
<?php get_footer(); ?>
