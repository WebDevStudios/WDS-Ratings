window.StarRatings = (function(window, document, $, undefined){
	'use strict';

	var l10n = window.wds_ratings_config;
	var app = { $stars : {}, $currRating : null };

	app.log = function() {
		app.log.history = app.log.history || [];
		app.log.history.push( arguments );
		if ( window.console && l10n.debug ) {
			window.console.log( Array.prototype.slice.call(arguments) );
		}
	};

	app.init = function() {
		$.extend( app, l10n );

		$( '.wds-ratings' )
			.each( app.handleRatingForSection )
			.find( '.wds-ratings-stars' )
				.on( 'mouseover', app.handleHover )
				.on( 'mouseleave', app.handleOffHover )
				.on( 'click', app.handleClickRating );
	};

	app.handleRatingForSection = function() {
		app.$currRating = $(this);
		var id = app.$currRating.attr( 'id' );

		// Loop through and cache our stars lookups
		app.$stars[ id ] = {};
		for ( var j = 5; j >= 0; j-- ) {
			app.$stars[ id ][ j ] = app.$currRating.find( '[data-stars="'+ j +'"]' );
		}

		var rating = app.$currRating.data( 'rating' );
		var userRating = app.$currRating.data( 'userrating' );

		if ( userRating ) {
			app.$currRating.addClass( 'has-user-rating' );
		}

		// Handle the rating styling
		app.handleRating( userRating ? userRating : rating, id, userRating );
	};

	app.handleRating = function( rating, id, isUserrating ) {
		var ceil    = Math.ceil( rating );
		var floor   = Math.floor( rating );
		var percent = rating - floor;

		// Remove previous css block for handling percentage stars
		$( '.wds-ratings-stars', '#'+ id ).removeClass( 'current-rating user-current-rating' ).find( '.wds-ratings-percent' ).removeClass( 'wds-ratings-percent' );
		$( document.getElementById( id +'-star-percent-style' ) ).remove();

		// Add current rating
		for (var i = floor; i >= 0; i--) {
			app.$stars[ id ][i].addClass( isUserrating ? 'user-current-rating' : 'current-rating' );
		}

		if ( percent ) {
			// Get star which needs percentage shown
			var $percent_star = app.$stars[ id ][ ceil ].find( '.star-'+ ceil ).addClass( 'wds-ratings-percent' );
			// Set width of before attribute to percentage of star
			app.$currRating.append('<style id="'+ id +'-star-percent-style">#'+ id +' .wds-ratings-percent:before { width: '+ (percent * 100) +'%;}</style>');
		}
	};

	app.handleHover = function( evt ) {
		var $this = $(this);
		var rating = $this.data( 'stars' );
		var id = $this.parents( '.wds-ratings' ).attr( 'id' );

		for (var i = rating; i >= 0; i--) {
			app.$stars[id][i].addClass( 'wds-ratings-active' );
		}
	};

	app.handleOffHover = function( evt ) {
		$(this).parents( '.wds-ratings' ).find( '.wds-ratings-stars' ).removeClass( 'wds-ratings-active' );
	};

	app.handleClickRating = function( evt ) {
		var $this = $(this);
		var userRating = $this.data( 'stars' );
		app.$currRating = $this.parents( '.wds-ratings' );
		var id = app.$currRating.attr( 'id' );
		var rating = app.$currRating.data( 'rating' );

		// return early if user is not logged in
		if ( parseInt(app.user_id) < 1) {
			alert(l10n.no_auth_alert);
			return;
		}

		app.post_id = app.$currRating.data( 'postid' );

		app.$currRating.data( 'userrating', userRating ).addClass( 'has-user-rating' );

		// Handle the rating styling
		app.handleRating( userRating, id, true );

		$this.trigger( 'mouseleave' );

		var data = {
			'action' : 'wds_ratings_post_user_rating',
			'nonce' : app.nonce,
			'rating' : userRating,
			'post_id' : app.post_id,
			'user_id' : app.user_id,
		};

		var fail = function( response ) {
			app.log( 'Something went wrong!', response );

			app.$currRating.removeClass( 'has-user-rating' );

			// Handle the rating styling
			app.handleRating( rating, id, true );

			$this.trigger( 'mouseleave' );
		};

		$.ajax({
			'type' : 'POST',
			'url' : app.ajaxurl,
			'dataType' : 'JSON',
			'data' : data,
			'success' : function( response ) {

				if ( response.success ) {
					return app.log( 'W00t!', response );
				}

				fail( response );
			},
			'error' : function( jqXHR, textStatus, errorThrown ) {
				fail( {
					'jqXHR'       : jqXHR,
					'textStatus'  : textStatus,
					'errorThrown' : errorThrown
				} );
			}
		});

	};

  	$(document).ready( app.init );

  	return app;

})(window, document, jQuery);
