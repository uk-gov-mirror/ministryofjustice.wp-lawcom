<article <?php post_class(); ?>>
  <header>
    <h2 class="entry-title">
      <a href="<?php the_permalink(); ?>">
        <?php

        if (is_search() && get_post_type() == 'project') {
          echo 'Project: ';
        }

        the_title();

        ?>
      </a>
    </h2>
    <?php if (get_post_type() !== 'project'): ?>
      <p><?php get_template_part('templates/entry-meta'); ?></p>
    <?php endif; ?>
  </header>
  <div class="entry-summary">
    <?php the_excerpt(); ?>
  </div>

  <hr/>
</article>
