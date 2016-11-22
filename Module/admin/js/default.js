// JavaScript Document
function conf_del() {
	del = confirm("Are you sure you want to delete this?");
	return del;
}

function showDiv(id) {
    $('#' + id).show();
    return false;
}

function hideDiv(id) {
    $('#' + id).hide();
    return false;
}