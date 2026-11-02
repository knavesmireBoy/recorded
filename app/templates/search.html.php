
<div id="search">
<?php if(isset($search) && $search) { 

	if($searched) { ?>
<p><a href="<?= TV_EXIT; ?>" class="cancel">Clear Search</a></p>
<?php } else { ?>
	<a href="<?= TV_EXIT; ?>">X</a>
<form action="/tvlist/search" method="get" name="search" class="details">
	<fieldset>
	<label for="text">By title</label><input id="text" type="search" name="text" minlength=3 maxlength=12 required/>
</fieldset>
	<input type="submit" value="Search" />
</form>
<?php }
}
else {  
	if(isset($search) && !$search){ ?>
		<a>No results found</a>
	<?php }	
	?>
	<p><a href="<?= TV_FIND; ?>">Search</a></p>
<?php } ?>
</div>