<ul>
<?php foreach($tagSuggestions as $suggestion): ?>
<?php // No extraneous whitespace here, it shows up in text() ?>
<li><span class="tag-spacer left"><?php echo $suggestion['left']?></span><a href="#"><?php echo $suggestion['suggested']?></a><span class="tag-spacer left"><?php echo $suggestion['right']?></span></li>
<?php endforeach ?>
</ul>
