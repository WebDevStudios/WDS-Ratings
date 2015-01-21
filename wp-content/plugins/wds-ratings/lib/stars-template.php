<!-- data-rating is the saved rating -->
<div id="star-rating-<?php echo isset( $post_id ) ? $post_id : 0; ?>" 
	class="stars-ratings" 
	data-rating="<?php echo isset( $post_rating ) ? $post_rating : 0; ?>" 
	<?php echo isset( $user_rating ) ? 'data-userrating="'.$user_rating.'"' : ''; ?>>
		
	<div class="stars-inner-wrap">
		<div>
			<div class="stars" data-stars="5">
				<span class="star-1">&#x2605;</span>
				<span class="star-2">&#x2605;</span>
				<span class="star-3">&#x2605;</span>
				<span class="star-4">&#x2605;</span>
				<span class="star-5">&#x2605;</span>
			</div>
		    <div class="stars" data-stars="4">
			    <span class="star-1">&#x2605;</span>
			    <span class="star-2">&#x2605;</span>
			    <span class="star-3">&#x2605;</span>
			    <span class="star-4">&#x2605;</span>
			</div>
		    <div class="stars" data-stars="3">
			    <span class="star-1">&#x2605;</span>
			    <span class="star-2">&#x2605;</span>
			    <span class="star-3">&#x2605;</span>
			</div>
		    <div class="stars" data-stars="2">
			    <span class="star-1">&#x2605;</span>
			    <span class="star-2">&#x2605;</span>
			</div>
			<div class="stars" data-stars="1">
				<span class="star-1">&#x2605;</span>
			</div>
		</div>
	</div>
</div>