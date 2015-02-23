<!-- data-rating is the saved rating -->
<div id="star-rating-<?php echo isset( $post_id ) ? $post_id : 0; ?>" 
	class="stars-ratings" 
	data-rating="<?php echo isset( $post_rating ) ? $post_rating : 0; ?>" 
	<?php echo isset( $user_rating ) ? 'data-userrating="'.$user_rating.'"' : ''; ?>
	data-post_id="<?php echo isset( $post_id ) ? $post_id : 0; ?>">

	<div class="stars-inner-wrap">
		<div>
			<div class="stars" data-stars="5">
				<span class="star-1">✭</span>
				<span class="star-2">✭</span>
				<span class="star-3">✭</span>
				<span class="star-4">✭</span>
				<span class="star-5">✭</span>
			</div>
		    <div class="stars" data-stars="4">
			    <span class="star-1">✭</span>
			    <span class="star-2">✭</span>
			    <span class="star-3">✭</span>
			    <span class="star-4">✭</span>
			</div>
		    <div class="stars" data-stars="3">
			    <span class="star-1">✭</span>
			    <span class="star-2">✭</span>
			    <span class="star-3">✭</span>
			</div>
		    <div class="stars" data-stars="2">
			    <span class="star-1">✭</span>
			    <span class="star-2">✭</span>
			</div>
			<div class="stars" data-stars="1">
				<span class="star-1">✭</span>
			</div>
		</div>
	</div>
</div>