// converts and presents movie listing data to user

// show full synopsis
var fullSynop = function(movie_id){
	$('.movie-syn-'+movie_id).html(movieData[movie_id].synopsis);
};

$(function() {
	'use strict';
	
	movieListing = {
		listing: '',
		// combine array values
		combineArr: function(arr){
			if( Object.prototype.toString.call( arr ) === '[object Array]' ) {
				return arr.join(', ');
			} else {
				return arr
			}
		},
		// generate listing item
		createItem: function(movie){
			// gather extended movie data
			var movieInfo = movieData[movie.movie_id];
			// assemble movie listing object
			var itm  = '<div class="row">';
				itm += '<div class="col-md-2">'+'<a target="_blank" href="http://media.westworldmedia.com/thbmb/mp4/'+movie.movie_id+'_high.mp4"><img src="'+movieInfo.photos.photo+'"></a>'+'</div>';
				itm += '<div class="col-md-4">';
				itm += '<h4>' + movie.movie_name + '</h4>';
				itm += '<p>Rated: ' + movie.movie_rating + '<br>';
				itm += 'Runtime: ' + movieInfo.runtime + ' minutes';
				//if(movieInfo.website.length > 0) itm += '<br>Website: <a href="'+movieInfo.website+'" target="_blank">' + movieInfo.website + '</a>';
				itm += '</p>';
				itm += '<p>Genre: ' + this.combineArr(movieInfo.genres.genre) + '</p>';
				itm += '<p>Staring: ' + this.combineArr(movieInfo.actors.actor) + '</p>';
				itm += '<p>Director(s): ' + this.combineArr(movieInfo.directors.director) + '</p>';
				itm += '</div>';
				itm += '<div class="col-md-4">';
				itm += '<p class="movie-syn-'+movie.movie_id+'">'+movieInfo.synopsis.substring(0,120)+'...'+'<a href="javascript:fullSynop('+movie.movie_id+')">Show More</a></p>';
				itm += '</div>';
				itm += '</div>';
				
			return itm;
		},
		// generate block listing based on selected date
		genListing: function(){
			var i = 0,
				j = 0,
				selDate = $('#listing-date-soon').val(),
				limit = listingData.length;
				
			// reset listing var
			this.listing = '';

			// walk though found movies
			for(i; i < limit; ++i){

				// determine showtimes layout
				if(listingData[i].showtimes['@attributes']){
					if(listingData[i].showtimes['@attributes'].date == selDate){
						this.listing += this.createItem(listingData[i]);
					}
				} else {
					var limitShows = listingData[i].showtimes.length;
					
					for(j = 0; j < limitShows; ++j){
						if(listingData[i].showtimes[j]['@attributes'].date == selDate){
							this.listing += this.createItem(listingData[i]);
						}
					}
				}
			}
			
			// add listing to block output
			$('.listing-output').html(this.listing);
		}
	};
	
	// add found filter dates
	$.each(soonDateOpts, function (index, value) {
		$('#listing-date-soon').append($('<option/>', { 
			value: index,
			text : moment(index).format("dddd, MMMM D, YYYY")
		}));
	});  
	
	// load initial movie listing
	movieListing.genListing();
	
	// bind change movie time selection
	$('#listing-date-soon').bind('change', function(){
		movieListing.genListing();
	});
});
