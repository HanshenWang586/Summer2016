<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$p = new AdminPage($admin_user);
$p->setModuleKey('contacts');

$contact = new Contact;

$body = "
<div id=\"contacts_left\">

	<div id=\"search_contacts\"><h1>Search</h1>
	<input onkeyup=\"rapidSearch(this.value)\">
	</div>
	
	<div id=\"search_notes\"><h1>Search Notes</h1>
	<input onkeyup=\"rapidSearchNotes(this.value)\">
	</div>
	
	<div id=\"callbacks\"><h1>Callbacks</h1>
	".$admin_user->getCallbacks()."</div>
	
	<div id=\"create_contact\">".$contact->displayForm('contact')."</div>
	
	<div id=\"create_organisation\">".$contact->displayForm('organisation')."</div>
	
</div>

<div id=\"contacts_right\">
<div id=\"results\"></div>
</div>
";

$p->setTag('main', $body);
$p->output();
?>