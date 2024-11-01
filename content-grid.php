<?php
/**
 * Template part for displaying posts in grid layout
 */
?>
<article <?php post_class('blog-grid-item'); ?>>
    <a href="<?php the_permalink(); ?>" class="post-thumbnail">
        <?php if (has_post_thumbnail()) : ?>
            <?php the_post_thumbnail('medium'); ?>
        <?php else : ?>
            <div class="no-image"></div>
        <?php endif; ?>
    </a>
    
    <div class="post-content">
        <?php the_title('<h2 class="entry-title"><a href="' . esc_url(get_permalink()) . '">', '</a></h2>'); ?>
        <div class="post-date"><?php echo get_the_date(); ?></div>
    </div>
</article>