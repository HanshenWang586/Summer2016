var root = 'http://jsonplaceholder.typicode.com';
var search = $(location).attr('search');
var id = search.substring(8);

$.ajax({
    url: root + '/posts/' + id,
  	method: 'GET'
  	}).then(function(data) {
	console.log(data);
	$('#info').append("<div id='users'> Title: " + data.title + "<br><a href='userpage.html?PostID="+data.userId+"''> UserID: " + data.userId +"</a>ID: "+ data.id +"<br>Body: " + data.body + "</div>");	
 	 
});