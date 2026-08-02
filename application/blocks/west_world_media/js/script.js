// setup and process customer orders
var orderSys = {};
var movieListing = {};

// get page querystrings
function getParameterByName(name, url) {
	if (!url) url = window.location.href;
	name = name.replace(/[\[\]]/g, "\\$&");
	var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
		results = regex.exec(url);
	if (!results) return null;
	if (!results[2]) return '';
	return decodeURIComponent(results[2].replace(/\+/g, " "));
}

$(function(){
	'use strict';

	var cfg = (typeof rtsConfig !== 'undefined') ? rtsConfig : {};
	
	orderSys = {
		maxTickets: [0,1,2,3,4,5,6,7,8,9,10],
		selTime: '',
		selShow: {},
		selShowID: '',
		selTickets: {},
		selTicketsQty: [],
		customerInfo: {},
		convFee: cfg.convFee || 1.35,
		orderSum: 0,
		email: '',
		defMaxSales: 64,
		validateEmail: function(email) {
			var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
			return re.test(email);
		},
		submitOrder: function(movieID){
			var movieInfo = movieData[movieID],
				order = {},
				custInfoErr = 0,
				custInfoEntryErr = 0,
				customerInfo = {},
				hcp = '',
				emlLngth = 0;
				
			// pull assigned email
			if(!orderSys.validateEmail(orderSys.email)){
				orderSys.email = $('.rtsEmail').val();
			}
			
			// customer info validate
			if(!$('.fullNameValidate').val() && typeof orderSys.customerInfo.fullName === 'undefined'){
				custInfoErr = 1;
			}
			if(!$('.orderAddressValidate').val() && typeof orderSys.customerInfo.streetAddress === 'undefined'){
				custInfoErr = 1;
			}
			if($('.zipCodeValidate').val()){
				emlLngth = $('.zipCodeValidate').val().length;
			} else if(typeof orderSys.customerInfo.zipCode !== 'undefined') {
				emlLngth = orderSys.customerInfo.zipCode.length;
			}
			if(!$('.zipCodeValidate').val() && typeof orderSys.customerInfo.zipCode === 'undefined'){
				custInfoErr = 1;
			} else {
				if(emlLngth <= 4){
					custInfoErr = 1;
				}
			}

			// check for valid customer information before submission
			if(!orderSys.validateEmail(orderSys.email) || orderSys.email.length === 0 || $('.rtsEmail').val() !== $('.rtsEmailValidate').val() || custInfoErr){
				// reset warnings
				$('.modal-body .mismatch').remove();
				
				// validate email
				if(!orderSys.validateEmail(orderSys.email)){
					$('.modal-body').prepend('<div class="alert alert-warning mismatch" role="alert">Please enter a valid email address before continuing!</div>');
				}
				
				if($('.rtsEmail').val() !== $('.rtsEmailValidate').val()){
					$('.modal-body').prepend('<div class="alert alert-warning mismatch" role="alert">Email addresses do not match!</div>');
				}
				
				// validate other user data
				if(!$('.fullNameValidate').val() && typeof orderSys.customerInfo.fullName === 'undefined'){
					$('.modal-body').prepend('<div class="alert alert-warning mismatch" role="alert">You must enter your full name as designated on your selected payment method.</div>');
				}
				if(!$('.orderAddressValidate').val() && typeof orderSys.customerInfo.streetAddress === 'undefined'){
					$('.modal-body').prepend('<div class="alert alert-warning mismatch" role="alert">You must enter the street address as designated on your selected payment method.</div>');
				}
				if(!$('.zipCodeValidate').val() && typeof orderSys.customerInfo.zipCode === 'undefined'){
					$('.modal-body').prepend('<div class="alert alert-warning mismatch" role="alert">You must enter the valid zip or postal code as designated on your selected payment method.</div>');
				}
				if($('.zipCodeValidate').val() && typeof orderSys.customerInfo.zipCode === 'undefined'){
					if($('.zipCodeValidate').val().length <= 4){
						$('.modal-body').prepend('<div class="alert alert-warning mismatch" role="alert">You must enter the valid zip or postal code as designated on your selected payment method.</div>');
					}
				}
				
			} else {
				
				// store customer info for request values
				customerInfo.fullName = $('.fullNameValidate').val() || orderSys.customerInfo.fullName;
				customerInfo.address = $('.orderAddressValidate').val() || orderSys.customerInfo.streetAddress;
				customerInfo.zip = $('.zipCodeValidate').val() || orderSys.customerInfo.zipCode;
				
				// generate host checkout payment
				hcp = '<Request>';
				hcp += '<Version>1</Version>';
				hcp += '<Command>CreatePayment</Command>';
				hcp += '<Data>';
				hcp += '<Packet>';
				hcp += '<ChargeAmount>'+orderSys.orderSum+'</ChargeAmount>';
				hcp += '<StreetAddress>'+customerInfo.address+'</StreetAddress>';
				hcp += '<ZipCode>'+customerInfo.zip+'</ZipCode>';
				hcp += '<CustomerName>'+customerInfo.fullName+'</CustomerName>';
				hcp += '<ProcessCompleteUrl>'+encodeURI(cfg.processCompleteUrl || (window.location.origin + '/rts/procComp.php'))+'</ProcessCompleteUrl>';
				hcp += '<ReturnUrl>'+encodeURI(cfg.returnUrl || (window.location.origin + '/showtimes'))+'</ReturnUrl>';
				hcp += '</Packet>';
				hcp += '</Data>';
				hcp += '</Request>';

				// send xhr request
				$.ajax({
				  url: cfg.reqUrl || '/rts/req.php',
				  method: 'post',
				  data: {'req':hcp}
				}).done(function(data) {
					var res = jQuery.parseJSON(data);
					console.log(res);
				
					if(res.Packet.CreatePayment.Worked == 1){
					
					
						// store current order data
						order = {
								hostCheckout: res,
								selTime: orderSys.selTime,
								movieData: movieInfo,
								performanceId: orderSys.selShowID,
								selTicketsQty: orderSys.selTicketsQty,
								orderSum: orderSys.orderSum,
								email: orderSys.email,
								customerInfo: {
										fullName: $('.fullNameValidate').val(),
										streetAddress: $('.orderAddressValidate').val(),
										zipCode: $('.zipCodeValidate').val()
									}
							};
						
						// save transaction data to local session variable for retrieval after payment
						$.ajax({
						  url: cfg.sessUrl || '/rts/sess.php',
						  method: 'post',
						  data: {'method':'set','data':order}
						}).done(function(data) {
							// redirect script to payment processor
							window.location = (cfg.redirUrl || '/rts/redir.php') + '?RedirectUrl=' + res.Packet.CreatePayment.RedirectUrl + '&paymentID=' + res.Packet.CreatePayment.PostData + '&TransactionId=' + res.Packet.CreatePayment.TransactionId;
						});
					
					}
				});
			}
		},
		orderReview: function(movieID){
			var k = 0,
				curTicket = 0,
				orderSum = 0,
				curSum = 0,
				movieInfo = movieData[movieID],
				ticketsTot = orderSys.selTickets.length;
			
			// reset order sum
			orderSys.orderSum = 0;
			orderSys.selTicketsQty = [];
			
			var tiOpts = '<div class="row ticket-select">';
				tiOpts += '<div class="col-md-5">';
				tiOpts += '<img src="' + movieInfo.photos.photo + '">';
				tiOpts += '<h4>' + movieInfo.title + '</h4>';
				tiOpts += '<p>Rated: ' + movieInfo.rating + '<br>';
				tiOpts += 'Runtime: ' + movieInfo.runtime + ' minutes</p>';
				tiOpts += '</div>';
				tiOpts += '<div class="col-md-7"><h4>'+orderSys.selTime+'</h4><p>';
				tiOpts += '<em style="color:#ca0012">There is a $' + Number(orderSys.convFee).toFixed(2) + ' online convenience fee per ticket</em></br>';

				for(k = 0; k < ticketsTot; ++k){
					curTicket = $('.'+orderSys.selTickets[k].Code).val();
					orderSys.selTicketsQty.push({code:orderSys.selTickets[k].Code, qty:curTicket});
					if(curTicket > 0){
						curSum = (Number(orderSys.selTickets[k].Price) + Number(orderSys.convFee)) * curTicket;
						orderSys.orderSum += curSum;
						tiOpts += orderSys.selTickets[k].Name + ' @ $' + Number(orderSys.selTickets[k].Price).toFixed(2) + ' + $' + Number(orderSys.convFee).toFixed(2)  + ' Fee x ' + curTicket + ' = $' + Number(curSum).toFixed(2) + '</br>';
					}
				}

				if(orderSys.orderSum > 0){
					tiOpts += '<p style="text-align:right">';
					tiOpts += 'Total: $' + Number(orderSys.orderSum).toFixed(2);
				}
				tiOpts += '<p style="color:red;text-align:left"><em>**All fields are required.</em></p>';
				tiOpts += '<input name="rtsEmail" class="rtsEmail form-control" id="rtsEmail" placeholder="please enter your email address">';
				tiOpts += '<input name="rtsEmailValidate" class="rtsEmailValidate form-control" id="rtsEmailValidate" placeholder="please re-enter your email address">';
				tiOpts += '<input name="fullName" class="fullNameValidate form-control" id="fullName" placeholder="please enter your full name">';
				tiOpts += '<input name="orderAddress" class="orderAddressValidate form-control" id="orderAddress" placeholder="please enter your street address">';
				tiOpts += '<input name="zipCode" class="zipCodeValidate form-control" id="zipCode" placeholder="please enter your zip or postal code">';
				tiOpts += '</div>';
				tiOpts += '</div>';
			
			// write selection options to modal body
			if(orderSys.orderSum > 0){
				$('.modal-body').html(tiOpts);
				// update modal submission trigger
				$('.modal-footer .btn-primary').attr('onclick','orderSys.submitOrder('+movieID+')');
			}
		},
		// gather show data
		getShow: function(performanceID, movieID, callback){
			
			var m = 0,
				s = 0,
				showLimit = 0,
				shows = [],
				films = rtsListingData.ShowSchedule.Films.Film,
				limit = films.length,
				buyLink = '',
				results = [];
				
			// store selected performanceID
			orderSys.selShowID = performanceID;
				
			// walk through found movie showtimes
			for(m; m < limit; ++m){
				
				// check cc film code match
				if(String(films[m].CSCode) === String(movieID)){
					
					showLimit = films[m].Shows.Show.length;
					shows = films[m].Shows.Show;
					
					if(typeof films[m].Shows.Show.DT === 'undefined'){
					
						// compare event dates
						for(s = 0; s < showLimit; ++s){
							if(Number(shows[s].ID) === Number(performanceID)){
								orderSys.selShow = shows[s];
								callback(shows[s], movieID);
							}
						}
						
					} else {
						
						if(Number(films[m].Shows.Show.ID) === Number(performanceID)){
							orderSys.selShow = shows[s];
							callback(films[m].Shows.Show, movieID);
						}
					}
				}
			}
		},
		// generate ticketing options
		genTicketOpts: function(show, movieID){
			var tickOps = [],
				tickets = rtsListingData.ShowSchedule.Tickets.Ticket,
				limitTickets = tickets.length,
				i = 0,
				j = 0,
				k = 0,
				Tis = show.TIs.TI,
				showTicksLimit = Tis.length,
				ticketsTot = 0,
				movieInfo = movieData[movieID];
				
			if(typeof show.TIs.TI.length !== 'undefined'){
				// walkthrough available ticket options
				for(i = 0; i < limitTickets; ++i){
					for(j = 0; j < showTicksLimit; ++j){
						if(tickets[i].Code === Tis[j].C){
							// only add ticket options available on internet
							if(Number(tickets[i].HideOnInternet) !== 1){
								tickOps.push(tickets[i]);
								
							}
						}
					}
				}
			} else {
				// only add ticket options available on internet
				for(i = 0; i < limitTickets; ++i){
					if(tickets[i].Code === show.TIs.TI.C){
						if(Number(tickets[i].HideOnInternet) !== 1){
							tickOps.push(tickets[i]);
						}
					}
				}
			}
			
			orderSys.selTickets = tickOps;
			ticketsTot = orderSys.selTickets.length;
			// generate ticket options output
			var tiOpts = '<div class="row ticket-select">';
				tiOpts += '<div class="col-md-5">';
				tiOpts += '<img src="' + movieInfo.photos.photo + '">';
				tiOpts += '<h4>' + movieInfo.title + '</h4>';
				tiOpts += '<p>Rated: ' + movieInfo.rating + '<br>';
				tiOpts += 'Runtime: ' + movieInfo.runtime + ' minutes</p>';
				tiOpts += '</div>';
				tiOpts += '<div class="col-md-7"><h4>'+orderSys.selTime+'</h4><p>';

				if(ticketsTot){
					for(k = 0; k < ticketsTot; ++k){
						tiOpts += '<div class="row">';
						tiOpts += '<div class="col-md-3">';
						tiOpts += '<select name="tickets['+orderSys.selTickets[k].Code+']" class="form-control ticketOptions '+orderSys.selTickets[k].Code+'"></select> ';
						tiOpts += '</div>';
						tiOpts += '<div class="col-md-9"><p>';
						tiOpts += (String(orderSys.selTickets[k].Name).charAt(0) === 'e' ? String(orderSys.selTickets[k].Name).substring(1) : orderSys.selTickets[k].Name) + ' @ $' + Number(orderSys.selTickets[k].Price).toFixed(2) + '</br>';
						tiOpts += '</p></div>';
						tiOpts += '</div>';
					}
				} else {
					tiOpts += '<p>Sorry! No tickets available at this time!</p>';
				}
				tiOpts += '</p></div>';
				tiOpts += '</div>';
			
			// write selection options to modal body
			$('.modal-body').html(tiOpts);
			
			// set ticket quantity options
			$.each(orderSys.maxTickets, function(key, value) {   
				$('.ticketOptions')
				 .append($("<option></option>")
							.attr("value",key)
							.text(value)); 
			});
			
			// update modal submission trigger
			$('.modal-footer .btn-primary').attr('onclick','orderSys.orderReview('+movieID+')');
		},
		// check availability, present options
		pickTickets: function(performanceID, movieID, selTime){
			
			// init modal
			$('.modal-footer .btn-primary').html('Continue');
			$('.modal-footer .btn-default').show();
			$('.modal-body').html('<p style="text-align:center"><img src="/application/blocks/west_world_media/img/loading.gif" alt="loading" style="width:200px;height:auto"></p>');
			$('#orderModal').modal({
				backdrop: jQuery.usingSafari(true) ? "static" : true,
				show:true
			});

			// check availability
			var req = '<Request>';
				req += '<Version>1</Version>';
				req += '<Command>CheckSoldOut</Command>';
				req += '<Data>';
				req += '<Packet>';
				req += '<PerformanceId>'+performanceID+'</PerformanceId>';
				req += '</Packet>';
				req += '</Data>';
				req += '</Request>';
				
			// store selected time
			orderSys.selTime = selTime;
				
			// send xhr request
			$.ajax({
			  url: '/rts/req.php',
			  method: 'post',
			  data: {'req':req}
			}).done(function(data) {
				var res = jQuery.parseJSON(data),
					maxSales = 0;
				var intSoldOut = res.SoldOut_Internet;
				
				// set maxmimum available seats
				if(Number(res.TotalSeats) === 0){
					maxSales = orderSys.defMaxSales;
				} else {
					maxSales = res.TotalSeats;
				}

				if(Number(res.Sold) < Number(maxSales)){
					orderSys.getShow(performanceID, movieID, orderSys.genTicketOpts);
				} else {
					$('.modal-body').html('Sorry! No tickets available at this time!');
				}
			});
				
			//if(tktsAvailable){
			//} else {
			//}
		},
		// print payment results
		payResults: function(){
			
			$.ajax({
			  url: cfg.sessUrl || '/rts/sess.php',
			  method: 'post',
			  data: {'method':'get'}
			}).done(function(data) {

				var res = jQuery.parseJSON(data),
					movieInfo = res.movieData,
					respCode = 0,
					resMsg = '';
														
				// assign return message
				if(typeof res.rtsResult.ErrorText !== 'undefined'){
					resMsg = res.rtsResult.ErrorText + '<br> A possible hold has been applied to your account in the purchase amount for up to 24 or 48 hours.';
				} else {
					resMsg = res.paymentRes.ReturnMessage;
				}

				// gen output markup
				var tiOpts = '<div class="row ticket-select">';
					tiOpts += '<div class="col-md-6">';
					tiOpts += '<img src="' + movieInfo.photos.photo + '">';
					tiOpts += '<h4>' + movieInfo.title + '</h4>';
					tiOpts += '<p>Rated: ' + movieInfo.rating + '<br>';
					tiOpts += 'Runtime: ' + movieInfo.runtime + ' minutes</p>';
					tiOpts += '</div>';
					tiOpts += '<div class="col-md-6"><h4>'+res.selTime+'</h4><p>';

					tiOpts += '<p style="text-align:right">';
					tiOpts += '<b>Payment Results:</b><br>';
					tiOpts += resMsg+'<br>';
					
					// get response code
					if(typeof res.rtsResult.Packet !== 'undefined'){
						respCode = res.rtsResult.Packet.Response.Code;
					} else {
						respCode = res.rtsResult.Code;
					}

					// if order fails reset order values and try again
					if(Number(respCode) !== 0){
						orderSys.email = res.email;
						orderSys.orderSum = res.orderSum;
						orderSys.selTime = res.selTime;
						orderSys.selShowID = res.performanceId;
						orderSys.selTicketsQty = res.selTicketsQty;
						orderSys.customerInfo = res.customerInfo;
						$('.modal-footer .btn-primary').html('Try Again?');
						$('.modal-footer .btn-primary').attr('onclick','orderSys.submitOrder('+res.movieData.movie_id+')');
					} else {
						tiOpts += 'Your receipt has been emailed. Please print or have your email available on mobile device upon arrival. See you at the movies!<br>';
						$('.modal-footer .btn-primary').attr('onclick','$(\'#orderModal\').modal(\'hide\')');
						$('.modal-footer .btn-default').hide();
					}
					tiOpts += '</p>';

					tiOpts += '</div>';
					tiOpts += '</div>';
				
				// gen user readable output
				$('.modal-body').html(tiOpts);
				
				// show output modal
				$('#orderModal').modal({'show':true});

			});
					
		}
	};
	
	// check for returning user on payment authorization or failure
	var payRes = getParameterByName('paymentRes');
	// if returning querystring set load result output
	if(Number(payRes) === 1){
		orderSys.payResults();
	} else {
		// prime session state if inital page load
		$.ajax({
		  url: cfg.sessUrl || '/rts/sess.php',
		  method: 'post',
		  data: {'method':'set','data':{}}
		});
	}
});