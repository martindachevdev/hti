 
    <?php do_action('before_blog_sidebar'); ?>
    
    <?php if (is_active_sidebar('blog-sidebar')) : ?>
        <?php dynamic_sidebar('blog-sidebar'); ?>
    <?php else : ?>
        <!-- Default widgets -->
        <section class="widget widget_search">
            <?php get_search_form(); ?>
        </section>
        <section class="widget widget_categories">
            <h2 class="widget-title"><?php _e('Категории', 'storefront-child'); ?></h2>
            <ul>
                <?php
                wp_list_categories(array(
                    'title_li' => '',
                    'show_count' => true
                ));
                ?>
            </ul>
        </section>
        <section class="widget widget_recent_entries">
            <h2 class="widget-title"><?php _e('Скорошни публикации', 'storefront-child'); ?></h2>
            <ul>
                <?php
                $recent_posts = wp_get_recent_posts(array(
                    'numberposts' => 5,
                    'post_status' => 'publish'
                ));
                
                foreach ($recent_posts as $recent) {
                    printf(
                        '<li><a href="%1$s">%2$s</a></li>',
                        esc_url(get_permalink($recent['ID'])),
                        esc_html($recent['post_title'])
                    );
                }
                ?>
            </ul>
        </section>
        
    
    <?php endif; ?>
    
    <?php do_action('after_blog_sidebar'); ?>
 