 $(function(){
 	var Index = 0,
 		frams = $(".images li"),
 		slideLength = frams.length;
 

 function ShowSlide(){
 	var slide = $(".images li").eq(Index);
 	frams.hide();
 	slide.fadeIn();
 }


 var autoSlide = setInterval(function(){
 	Index += 1;
 	
 	if (Index > slideLength-1) {
 		Index = 0;
 	} 
 	
 	ShowSlide();

 }, 3000);

 $('#Prev').click(function(){
 	Index -= 1;
 	if(Index < 0) {
 		Index = slideLength-1;

 	}
 	ShowSlide();
 })

 $('#Next').click(function(){
 	Index += 1;
 	if(Index > slideLength-1) {
 		Index = 0;
 	}

 	ShowSlide();
 })



})

