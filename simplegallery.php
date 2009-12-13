<?php $theme->display('header'); ?>
<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $css; ?>">
<!--begin content-->
<div id="content">

	<div id='gallery-breadcrumbs'>
	<?php
		$links = array(array_pop($breadcrumbs));
		$base = Site::get_url( 'habari' );
		while ( !empty($breadcrumbs) ) {
			$link = $base . '/' . implode('/', $breadcrumbs);
			$breadcrumb = array_pop($breadcrumbs);
			$links[] = "<span class='gallery-breadcrumb'><a href='{$link}'>$breadcrumb</a></span>";
		}
		echo implode(" &raquo; ", array_reverse($links));
	?>
	</div>

	<h2><?php echo $title; ?></h2>

	<?php foreach ( $dirs as $dir ): ?>

		<?php if ( $dir->thumbnail != null ): ?>

		<div class='gallery-img'>
			<a href='<?php echo $dir->url; ?>' title='<?php echo $dir->pretty_title; ?>'>
				<img src='<?php echo $dir->thumbnail->url; ?>'>
			</a>
			<h3><?php echo $dir->pretty_title; ?></h3>
		</div>

		<?php else: ?>

		<div class='gallery-dir'>
			Gallery: <a href='<?php echo $dir->url; ?>' title='<?php echo $dir->pretty_title; ?>'><?php echo $dir->pretty_title; ?></a>
		</div>

		<?php endif; ?>

	<?php endforeach; ?>

	<div class='clear'></div>

	<?php foreach ( $images as $image ): ?>

		<div class='gallery-img'>
			<a href='<?php echo $image->url; ?>' title='<?php echo $image->pretty_title; ?>'>
				<img src='<?php echo $image->thumbnail_url; ?>'>
			</a>
			<h3><?php echo $image->pretty_title; ?></h3>
		</div>

	<?php endforeach; ?>

</div><!--end content-->

<?php $theme->display('footer'); ?>
