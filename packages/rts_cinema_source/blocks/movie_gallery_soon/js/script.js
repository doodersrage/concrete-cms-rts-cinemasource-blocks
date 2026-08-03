// converts and presents movie listing data to user
var movieListingGallery = '';
$(function() {
	'use strict';
	
	movieListingGallery = {
		listing: '',
		cnt: 0,
		// combine array values
		combineArr: function(arr){
			if( Object.prototype.toString.call( arr ) === '[object Array]' ) {
				return arr.join(', ');
			} else {
				return arr
			}
		},
		// generate listing item
		createItem: function(movie, selDate){
			// gather extended movie data
			var movieInfo = movieData[movie.movie_id];
			// assemble movie listing object
			var itm = '<div class="item'+(this.cnt === 0 ? ' active' : '')+'">';
				itm += '<p style="text-align:center"><a target="_blank" href="http://media.westworldmedia.com/thbmb/mp4/'+movie.movie_id+'_high.mp4"><img src="'+movieInfo.photos.photo+'" alt="'+movieInfo.movie_name+'"></a></p>';
				itm += '<div class="carousel-caption">';
				itm += '<h3>' + movie.movie_name + '</h3>';
				itm += '<p>'+moment(selDate).format("dddd, MMMM D, YYYY")+'</p>';
				itm += '<p>'+movieInfo.synopsis.substring(0,120)+'...</p>';
				itm += '</div>';
				itm += '</div>';
				
				$('.carousel-indicators').append('<li data-target="#myCarousel" data-slide-to="'+this.cnt+'" class="'+(this.cnt === 0 ? 'active' : '')+'"></li>');
				
				this.cnt += 1;
				
			return itm;
		},
		// generate block listing based on selected date
		genListing: function(selDate){
			var i = 0,
				j = 0,
				limit = listingData.length;
				
			// reset listing var
			this.listing = '';

			// walk though found movies
			for(i; i < limit; ++i){

				// determine showtimes layout
				if(listingData[i].showtimes['@attributes']){
					if(listingData[i].showtimes['@attributes'].date == selDate){
						this.listing += this.createItem(listingData[i], selDate);
					}
				} else {
					var limitShows = listingData[i].showtimes.length;
					
					for(j = 0; j < limitShows; ++j){
						if(listingData[i].showtimes[j]['@attributes'].date == selDate){
							this.listing += this.createItem(listingData[i], selDate);
						}
					}
				}
			}
			
			// add listing to block output
			$('.carousel-inner').append(this.listing);
		}
	};
	
	// add found filter dates
	$.each(soonDateOpts, function (index, value) {
		movieListingGallery.genListing(index);
	});  

});
