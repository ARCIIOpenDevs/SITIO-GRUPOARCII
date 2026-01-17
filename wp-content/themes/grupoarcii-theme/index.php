<?php get_header(); ?>

<main id="main" class="site-main">
    
    <?php if (have_posts()) : ?>
        
        <div class="posts-container">
            <div class="container">
                <div class="posts-grid">
                    
                    <?php while (have_posts()) : the_post(); ?>
                        
                        <article id="post-<?php the_ID(); ?>" <?php post_class('post-card'); ?>>
                            
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="post-thumbnail">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail('medium_large', array('class' => 'post-image')); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <div class="post-content">
                                <div class="post-meta">
                                    <span class="post-date">
                                        <i class="fas fa-calendar-alt"></i>
                                        <?php echo get_the_date('d F Y'); ?>
                                    </span>
                                    <span class="post-category">
                                        <i class="fas fa-folder"></i>
                                        <?php echo get_the_category_list(', '); ?>
                                    </span>
                                </div>
                                
                                <h2 class="post-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h2>
                                
                                <div class="post-excerpt">
                                    <?php the_excerpt(); ?>
                                </div>
                                
                                <div class="post-footer">
                                    <a href="<?php the_permalink(); ?>" class="read-more">
                                        Leer más <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                            
                        </article>
                        
                    <?php endwhile; ?>
                    
                </div>
                
                <!-- Pagination -->
                <div class="posts-pagination">
                    <?php
                    the_posts_pagination(array(
                        'mid_size' => 2,
                        'prev_text' => '<i class="fas fa-chevron-left"></i> Anterior',
                        'next_text' => 'Siguiente <i class="fas fa-chevron-right"></i>',
                    ));
                    ?>
                </div>
                
            </div>
        </div>
        
    <?php else : ?>
        
        <div class="no-posts">
            <div class="container">
                <div class="no-posts-content">
                    <h1>No se encontraron publicaciones</h1>
                    <p>Parece que no hay contenido disponible en este momento.</p>
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary">
                        Volver al inicio
                    </a>
                </div>
            </div>
        </div>
        
    <?php endif; ?>
    
</main><!-- #main -->

<?php get_footer(); ?>