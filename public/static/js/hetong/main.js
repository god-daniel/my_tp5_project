;(function () {
	'use strict';
	var owl = function(){
		if ($('#owl').length > 0 ) {
			$('#owl').waypoint( function( direction ) {
				$('#owl').addClass('animated');
				setTimeout( owlanimateWork , 200);
			} , { offset: '70%' } );
		}
	}
	var owlanimateWork = function() {
		if ( $('#owl .work-box').length > 0 ) {
			$('#owl .work-box').each(function( k ) {
				var el = $(this);
				setTimeout ( function () {
					el.animate({opacity: 1} , 600 );
					el.addClass('fadeInUp animated');
				},  k * 200, 'easeInOutExpo' );
			});
		}
	};
	var bo1 = function(){
		if ($('#bo1').length > 0 ) {
			$('#bo1').waypoint( function( direction ) {
				$('#bo1').addClass('animated');
				setTimeout( bo1animateWork , 200);
			} , { offset: '70%' } );
		}
	}
	var bo1animateWork = function() {
		if ( $('#bo1 .work-box').length > 0 ) {
			$('#bo1 .work-box').each(function( k ) {
				var el = $(this);
				setTimeout ( function () {
					el.animate({opacity: 1} , 600 );
					el.addClass('fadeInUp animated');
				},  k * 200, 'easeInOutExpo' );
			});
		}
	};
	var bo2 = function(){
		if ($('#bo2').length > 0 ) {
			$('#bo2').waypoint( function( direction ) {
				$('#bo2').addClass('animated');
				setTimeout( bo2animateWork , 200);
			} , { offset: '70%' } );
		}
	}
	var bo2animateWork = function() {
		if ( $('#bo2 .work-box').length > 0 ) {
			$('#bo2 .work-box').each(function( k ) {
				var el = $(this);
				setTimeout ( function () {
					el.animate({opacity: 1} , 600 );
					el.addClass('zoomIn animated');
				},  k * 200, 'easeInOutExpo' );
			});
		}
	};
	var bo3 = function(){
		if ($('#bo3').length > 0 ) {
			$('#bo3').waypoint( function( direction ) {
				$('#bo3').addClass('animated');
				setTimeout( bo3animateWork , 200);
			} , { offset: '70%' } );
		}
	}
	var bo3animateWork = function() {
		if ( $('#bo3 .work-box').length > 0 ) {
			$('#bo3 .work-box').each(function( k ) {
				var el = $(this);
				setTimeout ( function () {
					el.animate({opacity: 1} , 600 );
					el.addClass('fadeInUp animated');
				},  k * 200, 'easeInOutExpo' );
			});
		}
	};
	var bo4 = function(){
		if ($('#bo4').length > 0 ) {
			$('#bo4').waypoint( function( direction ) {
				$('#bo3').addClass('animated');
				setTimeout( bo4animateWork , 200);
			} , { offset: '70%' } );
		}
	}
	var bo4animateWork = function() {
		if ( $('#bo4 .work-box').length > 0 ) {
			$('#bo4 .boxl').each(function( k ) {
				var el = $(this);
				setTimeout ( function () {
					el.animate({opacity: 1} , 600 );
					el.addClass('fadeInLeft animated');
				},  k * 200, 'easeInOutExpo' );
			});
			$('#bo4 .boxr').each(function( k ) {
				var el = $(this);
				setTimeout ( function () {
					el.animate({opacity: 1} , 600 );
					el.addClass('fadeInRight animated');
				},  k * 400, 'easeInOutExpo' );
			});
		}
	};

	var gwfooter = function(){
		if ($('#gw-footer').length > 0 ) {
			$('#gw-footer').waypoint( function( direction ) {
				$('#gw-footer').addClass('animated');
				setTimeout( gwfooteranimateWork , 200);
			} , { offset: '100%' } );
		}
	}
	var gwfooteranimateWork = function() {
		if ( $('#gw-footer .work-box').length > 0 ) {
			$('#gw-footer .work-box').each(function( k ) {
				var el = $(this);
				setTimeout ( function () {
					el.animate({opacity: 1} , 600 );
					el.addClass('fadeInUp animated');
				},  k * 200, 'easeInOutExpo' );
			});
		}
	};
	var helpanimateWork = function() {
		if ( $('#child .work-box').length > 0 ) {
			$('#child .work-box').each(function( k ) {
				var el = $(this);
				setTimeout ( function () {
					el.addClass('fadeInDown animated');
					if(k == 3)
					{
						$('#child .box-img').each(function( k ) {
							var el = $(this);
							setTimeout(function(){
								el.removeClass('fadeInDown animated work-box');;
								shake();
							},k*200,'easeInOutExpo')
						})
					}
				},  k * 200, 'easeInOutExpo' );
			});
			$('.bceln').addClass('bounceIn animated')
		}
		};
		var help = function() {
		if ( $('#child').length > 0 ) {
			$('#child').waypoint( function( direction ) {
				if( direction === 'down' && !$(this).hasClass('animated') ) {
					setTimeout( helpanimateWork , 200);
					$(this).addClass('animated');
				}
				// 95%
			} , { offset: '70%' } );
		}
		};	
		var news = function(){
			if ( $('.tongzi').length > 0 ) {
				$('.tongzi').waypoint( function( direction ) {
					if( direction === 'down' && !$(this).hasClass('animated') ) {
						setTimeout( newsanimateWork , 200);
						$(this).addClass('animated');
					}
					// 95%
				} , { offset: '70%' } );
			}
		}
		var newsanimateWork = function(){
			if ( $('.tongzi .work-box').length > 0 ) {
				$('.tongzi .work-box').each(function( k ) {
					var el = $(this);
					setTimeout ( function () {
						el.animate({opacity: 1} , 600 );
						el.addClass('rubberBand animated');
					},  k * 200, 'easeInOutExpo' );
				});
			}
		}
		var shake = function(){
			$('#child').hover(function(){
				$('.box-img').addClass('shake shake-slow');
			})
		}
	$(function(){
		owl();
		bo1();
		bo2();
		bo3();
		bo4();
		gwfooter();
		help();
		news();
	});
}());