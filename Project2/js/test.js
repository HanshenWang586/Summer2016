var root = 'http://jsonplaceholder.typicode.com';

$.ajax({
    url: root + '/posts',
  	method: 'GET'
}).then(function(data) {	
	console.log(data);
	var posts = [];
	for (var i=0; i<10; i++){
		posts[i] = data[i];
	};
	$(posts).each(function(index,post){
		console.log(index,post);
		$('#post').append("<div class='single-post'><a href='postpage.html?PostID="+post.id+"''>" + "Name: " +  post.title + "<br>" + "Id: " + post.userId + "</a></div>");	
	// 	$('li').click(function(){
	// 		// $(this).attr('userpage.html', '_blank');
	// 	window.location.replace('postpage.html?postID='+post.id);
	// }) 		
	})	
		
				
});
