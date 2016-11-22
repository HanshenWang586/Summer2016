var root = 'http://jsonplaceholder.typicode.com';
var search = $(location).attr('search');
var id = search.substring(8);44

$.ajax({
    url: root + '/users/' + id,
  	method: 'GET'
  	}).then(function(data) {
	console.log(data);
	$('#post').append("<div id='detail'> Email: "+ data.email + "<br> Website: " + data.website +"<br> Phone: "+ data.phone +"</div>");

	$('li').click(function(){
			// $(this).attr('userpage.html', '_blank');
			window.open('postpage.html?PostID='+post.id,'_blank','width=300,height=200,menubar=no,toolbar=no, status=no,scrollbars=yes');
		}) 	
})
;