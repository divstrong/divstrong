;(function($){

  $(document).ready(function(){

      //========== SIDEBAR/SEARCH AREA ============= //
       $(".header-search-btn").on("click", function (e) {
            e.preventDefault();
            $(".header-search-form-wrapper").addClass("open");
            $('.header-search-form-wrapper input[type="search"]').focus();
            $('.body-overlay').addClass('active');
       });
       $(".tx-search-close").on("click", function (e) {
            e.preventDefault();
            $(".header-search-form-wrapper").removeClass("open");
            $("body").removeClass("active");
            $('.body-overlay').removeClass('active');
       });

       $('#mobile-menu-active').metisMenu();
  
       $('#mobile-menu-active .dropdown > a').on('click', function (e) {
            e.preventDefault();
       });
       $(".hamburger_menu").on("click", function (e) {
            e.preventDefault();
            $(".slide-bar").toggleClass("show");
            $("body").addClass("on-side");
            $('.body-overlay').addClass('active');
            $(this).addClass('active');
       });
  
       $(".close-mobile-menu > a").on("click", function (e) {
            e.preventDefault();
            $(".slide-bar").removeClass("show");
            $("body").removeClass("on-side");
            $('.body-overlay').removeClass('active');
            $('.hamburger_menu').removeClass('active');
       });
      //========== SIDEBAR/SEARCH AREA ============= //

  });

      //========== MODAL VIDEO ============= //
   $(".video-play-button1").modalVideo();

 //========== LOAD FUNCTION ============= //
  $(window).on('load', function(event) {

       //========== PROGRESS BAR ============= //
       $(".progress-bar").each(function(){
            each_bar_width = $(this).attr('aria-valuenow');
            $(this).width(each_bar_width + '%');
       });
      //========== PROGRESS BAR END ============= //

     //========== NICE SELECT DROPDOWN ============= //
       $('select').niceSelect();
       
      //========== HEADER ACTIVE STRATS ============= //
      if ($("#header").length > 0) {
        $(window).on("scroll", function (event) {
          var scroll = $(window).scrollTop();
          if (scroll < 1) {
            $(".header-area").removeClass("sticky");
          } else {
            $(".header-area").addClass("sticky");
          }
        });
      }
     //========== HEADER ACTIVE ENDS ============= //
      
      //========== PRELOADER ============= //
     setTimeout(function () {
       $(".se-pre-con").fadeToggle();
     }, 800);  

      // preloader //
       $("#status").fadeOut();
       $("#preloader").delay(500).fadeOut("slow");
     //========== PRELOADER END ============= //

     //========== AOS ============= //
      AOS.init();
      
   });
   
//========== COUNTER UP============= //
 const ucounter = $('.counter');
 if (ucounter.length > 0) {
  ucounter.countUp();  
 };

 //========== TESTIMONIAL AREA STARTS ============= // 
   $(".product-slider-single").slick({
       slidesToShow: 1,
       slidesToScroll: 1,
       asNavFor: ".product-slider-nav",
       dots: false,
       arrows: false,
       centerMode: false,
       focusOnSelect: true,
       fade:true,
       smartSpeed:1000,
       responsive: [
            {
              breakpoint: 1024,
              settings: {
                slidesToShow: 1,
                slidesToScroll: 1,
                infinite: true,
              }
            },
            {
              breakpoint: 600,
              settings: {
                slidesToShow: 1,
                slidesToScroll: 1,
              }
            },
            {
              breakpoint: 480,
              settings: {
                slidesToShow: 1,
                slidesToScroll: 1
              }
            },
            {
                 breakpoint: 375,
                 settings: {
                   slidesToShow: 1,
                   slidesToScroll: 1
                 }
               },
               {
                 breakpoint: 320,
                 settings: {
                   slidesToShow: 1,
                   slidesToScroll: 1
                 }
               },
          ]
     });

     // testimonial //
     $('.testimonial-owlcarousel-area').owlCarousel({
       loop:true,
       margin:30,
       nav:false,
       dots:true,
       items:10,
       autoplay:true,
       smartSpeed:1300,
       autoplayTimeout:3000,
       responsiveClass:true,
       responsive:{
           0:{
               items:1,
               nav:false,
            
               
           },
           600:{
               items:2,
           },
           1000:{
               items:2,
           }
       }
     });
       // testimonial //
       $('.slider-carousel-area').owlCarousel({
         loop:true,
         margin:30,
         nav:false,
         dots:true,
         mouseDrag:false,
         items:10,
         autoplay:true,
         animateOut: 'fadeOut',
         animateIn: 'fadeIn',
         smartSpeed:2000,
         autoplayTimeout:3000,
         responsiveClass:true,
         responsive:{
             0:{
                 items:1,
                 nav:false,
             
                 
             },
             600:{
                 items:1,
             },
             1000:{
                 items:1,
             }
         }
       });

       // testimonial //
       $('.testimonial-auhtor-main-area').owlCarousel({
         loop:true,
         margin:30,
         nav:true,
         dots:false,
         items:10,
         navText:["<i class='fa-light fa-angle-right'></i>" , "<i class='fa-light fa-angle-left'></i>"],
         autoplay:true,
         smartSpeed:1500,
         autoplayTimeout:3000,
         responsiveClass:true,
         responsive:{
             0:{
                 items:1,
                 nav:true,
              
                 
             },
             600:{
                 items:1,
             },
             1000:{
                 items:1,
             }
         }
       });

     // testimonial RTL //
     $('.testimonial-owlcarousel-area2').owlCarousel({
       loop:true,
       margin:30,
       nav:false,
       dots:true,
       items:10,
       autoplay:true,
       smartSpeed:1300,
       autoplayTimeout:3000,
       responsiveClass:true,
       rtl:true,
       responsive:{
           0:{
               items:1,
               nav:false,
            
               
           },
           600:{
               items:2,
           },
           1000:{
               items:2,
           }
       }
     });
 
     // testimonial //
     $(".product-slider-nav").slick({
       slidesToShow: 5,
       slidesToScroll: 1,
       arrows: true,
       focusOnSelect: true,
       asNavFor: ".product-slider-single",
       autoplay:true,
       autoplayTimeout:500,
       prevArrow: $('.testimonial-next-arrow'),
       nextArrow: $('.testimonial-prev-arrow'),
     });
     $('.slider-nav1').slick({
       slidesToShow: 1,
       slidesToScroll: 1,
       asNavFor: '.foter-carousel',
       dots: false,
       arrows:true,
       nav:true,
       loop:true,
       centerMode: false,
       focusOnSelect: true,
       autoplay:true,
       autoplayTimeout:500,
       prevArrow: $('.testimonial-next-arrow1'),
       nextArrow: $('.testimonial-prev-arrow1'),
    });
    
   
    $('.foter-carousel').slick({
       slidesToShow: 1,
       slidesToScroll: 1,
       arrows: true,
       autoplay:true,
       nav:true,
       prevArrow: $('.testimonial-next-arrow1'),
       nextArrow: $('.testimonial-prev-arrow1'),
       fade: true,
       loop:true,
       asNavFor: '.slider-nav1',
      });


     // testimonial//
      $('.slider-galeria').slick({
       slidesToShow: 1,
       slidesToScroll: 1,
       arrows: true,
       infinite: false,
       asNavFor: '.slider-galeria-thumbs',
       prevArrow: $('.testimonial-next-arrow'),
       nextArrow: $('.testimonial-prev-arrow'),
     });
     $('.slider-galeria-thumbs').slick({
       slidesToShow: 5,
       slidesToScroll: 1,
       items:15,
       arrows: true,
       asNavFor: '.slider-galeria',
       vertical: true,
       verticalSwiping: true,
       focusOnSelect: true,
       infinite: false,
       prevArrow: $('.testimonial-next-arrow'),
       nextArrow: $('.testimonial-prev-arrow'),
     });
     
     
// testimonial //
 $('.service4-boxarea-carousel').owlCarousel({
   loop:true,
   margin:30,
   nav:true,
   dots:false,
   navText:["<i class='fa-light fa-arrow-left'></i>" ,"<i class='fa-light fa-arrow-right'></i>"],
   items: 10,
   autoplay:true,
   autoplayTimeout:3000,
   smartSpeed:1000,
   responsiveClass:true,
   responsive:{
       0:{
           items:1,
           nav:true,
        
           
       },
       600:{
           items:2,
       },
       1000:{
           items:3,
       }
   }
 });


// testimonial1 //
$('.product-slider-single1').owlCarousel({
   loop:true,
   margin:30,
   nav:true,
   dots:true,
   navText:["<i class='fa-light fa-arrow-left'></i>" ,"<i class='fa-light fa-arrow-right'></i>"],
   items: 10,
   autoplay:true,
   smartSpeed:1000,
   responsiveClass:true,
   responsive:{
       0:{
           items:1,
           nav:false,
        
           
       },
       600:{
           items:2,
       },
       1000:{
           items:3,
       }
   }
 });


// testimonial //
$('.service5-carousel-area').owlCarousel({
loop:true,
margin:30,
nav:true,
dots:false,
navText:["<i class='fa-light fa-arrow-left'></i>" ,"<i class='fa-light fa-arrow-right'></i>"],
items: 10,
autoplay:true,
autoplayTimeout:3000,
smartSpeed:1000,
responsiveClass:true,
responsive:{
   0:{
       items:1,
       nav:true,
    
       
   },
   600:{
       items:2,
   },
   1000:{
       items:3,
   }
}
});


// testimonial //
$('.testimonial6-boxarea').owlCarousel({
 loop:true,
 margin:30,
 nav:true,
 dots:true,
 navText:["<i class='fa-light fa-arrow-left'></i>" ,"<i class='fa-light fa-arrow-right'></i>"],
 items: 10,
 autoplay:true,
 autoplayTimeout:3000,
 smartSpeed:1000,
 responsiveClass:true,
 responsive:{
     0:{
         items:1,
         nav:true,
         dots:true,
      
         
     },
     600:{
         items:2,
     },
     1000:{
         items:2,
     }
 }
});

// testimonial //
$('.casestudy-carousel-area').owlCarousel({
loop:true,
margin:30,
nav:true,
dots:false,
items:10,
navText:["<i class='fa-light fa-arrow-left'></i>" ,"<i class='fa-light fa-arrow-right'></i>"],
autoplay:true,
smartSpeed:1500,
autoplayTimeout:3000,
responsiveClass:true,
responsive:{
    0:{
        items:1,
        nav:true,
     
        
    },
    600:{
        items:2,
    },
    1000:{
        items:3,
    }
}
});

// testimonial //
$('.testimonial7-main-area').owlCarousel({
 loop:true,
 margin:30,
 nav:true,
 dots:true,
 items:10,
 navText:["<i class='fa-light fa-arrow-right'></i>","<i class='fa-light fa-arrow-left'></i>"],
 autoplay:true,
 smartSpeed:1500,
 autoplayTimeout:3000,
 responsiveClass:true,
 responsive:{
     0:{
         items:1,
         nav:true,
      
         
     },
     600:{
         items:2,
     },
     1000:{
         items:3,
     }
 }
});

//========== TESTIMONIAL AREA ENDS ============= // 

//========== PAGE PROGRESS STARTS ============= // 
   var progressPath = document.querySelector(".progress-wrap path");
   var pathLength = progressPath.getTotalLength();
   progressPath.style.transition = progressPath.style.WebkitTransition =
     "none";
   progressPath.style.strokeDasharray = pathLength + " " + pathLength;
   progressPath.style.strokeDashoffset = pathLength;
   progressPath.getBoundingClientRect();
   progressPath.style.transition = progressPath.style.WebkitTransition =
     "stroke-dashoffset 10ms linear";
   var updateProgress = function () {
     var scroll = $(window).scrollTop();
     var height = $(document).height() - $(window).height();
     var progress = pathLength - (scroll * pathLength) / height;
     progressPath.style.strokeDashoffset = progress;
   };
   updateProgress();
   $(window).scroll(updateProgress);
   var offset = 50;
   var duration = 550;
   jQuery(window).on("scroll", function () {
     if (jQuery(this).scrollTop() > offset) {
       jQuery(".progress-wrap").addClass("active-progress");
     } else {
       jQuery(".progress-wrap").removeClass("active-progress");
     }
   });
   jQuery(".progress-wrap").on("click", function (event) {
     event.preventDefault();
     jQuery("html, body").animate({ scrollTop: 0 }, duration);
     return false;
   });
//========== PAGE PROGRESS STARTS ============= // 

//========== HISTORY TABS STRATS============= // 
   $('nav a').click(function(){
     var tabId = $(this).attr('data-tab');
     
     $('nav a').removeClass('active');
     $('.Tabcondent').removeClass('active');
     
     $(this).addClass('active');
     $('#'+tabId).addClass('active');
   });
 //========== HISTORY TABS ENDS============= // 

})(jQuery);
