<nav>
  <ul>
    <li class="left"><a href="/?app=recipes" class="brand-logo" style="padding: 0 15px;">Recipes</a></li>
    <?php if (get_var('p') == 'view'): ?>
      <li class="right"><a href="/?app=recipes&amp;p=cook&amp;id=<?= $recipe['id'] ?>">Cook Mode</a></li>
    <?php elseif (get_var('p') == 'cook'): ?>
      <li class="right"><a href="/?app=recipes&amp;p=view&amp;id=<?= $recipe['id'] ?>">Back to Recipe</a></li>
    <?php endif; ?>
  </ul>
</nav>