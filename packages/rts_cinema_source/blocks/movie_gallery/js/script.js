var movieListing = {};
// show full synopsis
var fullSynop = function(movie_id){
	$('.movie-syn-'+movie_id).html(movieData[movie_id].synopsis);
};
$(function() {
	'use strict';
	var mobileWidth = 750;
	
	// reset carousel and current selected image values
	$('#body-static-inner .selected-image').html("");
	$('#body-static-inner .carousel').html("");

	// display carousel for desktop users
	if($(window).width() > mobileWidth){
		$("#body-static-inner .carousel").smoothDivScroll({
			autoScrollingMode: "onStart",
			autoScrollingInterval: 50
		});
	}

	movieListing = {
		itmCnt: 0,
		listing: {},
		// generate listing item show times
		genBuyLnk: function(showData){
			var buyLink = '',
				showTime = '',
				showTime12 = '',
				showDateTime = '';
			
			// compare date strings
			if(moment(showData.selDate).format("YYYYMMDD") == showData.showDate.substring(0,8)){
				
				if(moment(showData.showDate,["YYYYMMDDHHmm"]) > moment()){ 
					// convert from 24 hour to 12 hour time
					showTime = showData.showDate.substr(-4);
					showTime12 = moment(showTime,["HHmm"]).format("h:mm A");
					showDateTime = moment(showData.showDate,["YYYYMMDDHHmm"]).format("dddd, MMMM D, YYYY h:mm A");
					
					buyLink = '<a href="javascript:void(0)" onclick="orderSys.pickTickets(\''+showData.show.ID+'\','+showData.movie.movie_id+',\''+showDateTime+'\')">' + showTime12 + '</a>';
					
					return buyLink;
				}
			}
		},
		// generate listing item show times
		getShowTimes: function(movie){
			var m = 0,
				s = 0,
				showLimit = 0,
				shows = [],
				showDate = '',
				selDate = $('#listing-date').val(),
				films = rtsListingData.ShowSchedule.Films.Film,
				limit = films.length,
				buyLink = '',
				results = [],
				showDateTime = '';
				
			// walk through found movie showtimes
			for(m; m < limit; ++m){
				// check cc film code match
				if(films[m].CSCode === movie.movie_id){
					showLimit = films[m].Shows.Show.length;
					shows = films[m].Shows.Show;
					
					if(typeof showLimit === 'undefined'){
						showDate = String(shows.DT);
						buyLink = movieListing.genBuyLnk({selDate:selDate, showDate: showDate, movie:movie, show:shows});
						if(buyLink){
							results.push(buyLink);
						}
					} else {
						// compare event dates
						for(s = 0; s < showLimit; ++s){
							showDate = String(shows[s].DT);
							
							buyLink = movieListing.genBuyLnk({selDate:selDate, showDate: showDate, movie:movie, show:shows[s]});
							if(buyLink){
								results.push(buyLink);
							}
						}
					}
				}
			}
			
			// return found results
			return '<div class="buy-tickets"><p>Select a movie time to buy tickets online now</p><p>' + results.join(' ') + '</p></div>';
		},
		// combine array values
		combineArr: function(arr){
			if( Object.prototype.toString.call( arr ) === '[object Array]' ) {
				return arr.join(', ');
			} else {
				return arr
			}
		},
		printMovieData: function(id){
			$('#movie-data-op').html(this.listing[id]);
		},
		// generate listing item
		createItem: function(movie){
			// gather extended movie data
			var movieInfo = movieData[movie.movie_id],
				imgLnk = '';

			// assemble movie listing object
			if($(window).width() > mobileWidth){
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
						
					// print showtimes for selected date
					itm += this.getShowTimes(movie);
					
					itm += '</div>';
					itm += '<div class="col-md-12">';
					itm += '<p class="movie-syn-'+movie.movie_id+'">'+movieInfo.synopsis.substring(0,120)+'...'+'<a href="javascript:fullSynop('+movie.movie_id+')">Show More</a></p>';
					itm += '</div>';
					itm += '</div>';
			} else {
				var itm  = '<div class="row">';
					itm += '<div class="col-md-12 col-xs-12">';
					itm += '<h4>' + movie.movie_name + '</h4>';
					itm += '<p>Rated: ' + movie.movie_rating + '<br>';
					itm += 'Runtime: ' + movieInfo.runtime + ' minutes';
					//if(movieInfo.website.length > 0) itm += '<br>Website: <a href="'+movieInfo.website+'" target="_blank">' + movieInfo.website + '</a>';
					itm += '</p>';
					itm += '<p>Genre: ' + this.combineArr(movieInfo.genres.genre) + '</p>';
					itm += '<p>Staring: ' + this.combineArr(movieInfo.actors.actor) + '</p>';
					itm += '<p>Director(s): ' + this.combineArr(movieInfo.directors.director) + '</p>';
					itm += '</div>';
					itm += '<div class="col-md-12 col-xs-12">';
						
					// print showtimes for selected date
					itm += this.getShowTimes(movie);
					
					itm += '</div>';
					itm += '<div class="col-md-12 col-xs-12">';
					itm += '<p class="movie-syn-'+movie.movie_id+'">'+movieInfo.synopsis.substring(0,120)+'...'+'<a href="javascript:fullSynop('+movie.movie_id+')">Show More</a></p>';
					itm += '</div>';
					itm += '</div>';
			}
				
			// store listing item
			this.listing[movie.movie_id] = itm;
			
			// gen carousel image
			if($(window).width() > mobileWidth){
				imgLnk = '<img style="width:135px;height:auto" class="'+movie.movie_id+'" src="'+movieInfo.photos.photo+'">';
				
				$('#body-static-inner .carousel .scrollableArea').append(imgLnk);
				$('#body-static-inner .carousel img.'+movie.movie_id).bind('click', function(){
					var id = $(this).attr('class');
					movieListing.printMovieData(id);
				});

			} else {
				imgLnk = '<div class="row" style="border-bottom:1px solid #000;margin-bottom:10px">';
				imgLnk += '<div class="col-md-2 col-xs-4">';
				imgLnk += '<a target="_blank" href="http://media.westworldmedia.com/thbmb/mp4/'+movie.movie_id+'_high.mp4"><img style="width:135px;height:auto" class="'+movie.movie_id+'" src="'+movieInfo.photos.photo+'"></a>';
				imgLnk += '</div>';
				imgLnk += '<div class="col-md-8 col-xs-8">';
				imgLnk += itm;
				imgLnk += '</div>';
				imgLnk += '</div>';
				
				$('#body-static-inner .carousel').append(imgLnk);
			}
			
			// update list cnt
			this.itmCnt += 1;
				
		},
		// generate block listing based on selected date
		genListing: function(){
			var i = 0,
				j = 0,
				selDate = $('#listing-date').val(),
				limit = listingData.length;
				
			// walk though found movies
			for(i; i < limit; ++i){

				// determine showtimes layout
				if(listingData[i].showtimes['@attributes']){
					if(listingData[i].showtimes['@attributes'].date == selDate){
						this.createItem(listingData[i]);
					}
				} else {
					var limitShows = listingData[i].showtimes.length;
					
					for(j = 0; j < limitShows; ++j){
						if(listingData[i].showtimes[j]['@attributes'].date == selDate){
							this.createItem(listingData[i]);
						}
					}
				}
			}
			
			// add listing to block output
			//$('.listing-output').html(this.listing);
		}
	};
	
	// add found filter dates
	$.each(dateOpts, function (index, value) {
		$('#listing-date').append($('<option/>', { 
			value: index,
			text : moment(index).format("dddd, MMMM D, YYYY")
		}));
	});  
	
	// load initial movie listing
	// reset listing var
	movieListing.listing = {};
	movieListing.itmCnt = 0;
	// reload carousel
	movieListing.genListing();
	// only reload twice to fill active carousel
	if($(window).width() > mobileWidth){
		movieListing.genListing();
	}
		
	// bind change movie time selection
	$('#listing-date').bind('change', function(){
		movieListing.listing = {};
		movieListing.itmCnt = 0;
		// clear existing data
		if($(window).width() <= mobileWidth){
			$('#body-static-inner .carousel').html('');
		}
		$('#body-static-inner .carousel .scrollableArea').html('');
		// reload carousel
		movieListing.genListing();
		// only reload twice to fill active carousel
		if($(window).width() > mobileWidth){
			movieListing.genListing();
		}
		
	});
});
