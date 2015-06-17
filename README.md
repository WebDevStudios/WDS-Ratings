# WDS Ratings
**Contributors:** WebDevStudios
**Tags:** ratings  

Users can rate posts on your WP site.

## Description
Allows posts to be rated by users and displays the average rating.

Based on [WP-PostRatings](https://wordpress.org/plugins/wp-postratings/) plugin by Lester Chan.

## Install
1. Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.
2. Add the 'wds_post_ratings' action wherever you want ratings to appear in your template.
	`<?php do_action( 'wds_post_ratings', true ); ?>`
3. Options can be configured in Settings > Ratings Options.