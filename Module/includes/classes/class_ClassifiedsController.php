<?php
class ClassifiedsController {

	public function index() {
		global $model;
		if (request($model->args['search'])) HTTP::redirect($model->url(array('view' => 'all'), false, true));
		else $this->folderListing();
	}

	public function all() {
		global $user, $model;

		$p = new Page;
		
		$cl = new ClassifiedsList;

		$view = new View;
		$view->setPath('classifieds/folder.html');
		
		$view->setTag('name', '<a href="/en/classifieds/">Classifieds</a> > All');
		
		$pager = new Pager;
		$pager->setLimit(15);
		$view->setTag('content', $cl->getClassifieds($pager, false, false, request($model->args['search'])));

		$p->setTag('page_title', 'All Classifieds');
		$p->setTag('main', $view->getOutput());
		$p->output();
	}
	
	private function folderListing($folder_id = false) {
		$p = new Page();
		$cf = new ClassifiedsFolder($folder_id);
		
		$body = $cf->showFolderListing();

		$view = new View;
		$view->setPath('classifieds/folder.html');
		$view->setTag('name', $cf->getPath());
		$view->setTag('body_id', 'classifieds_folder');
		$view->setTag('content', $body);

		$p->setTag('page_title', trim($cf->getTitle().' Classifieds'));
		$p->setTag('main', $view->getOutput());
		$p->output();
	}
	
	public function folder($folder_id = false) {
		global $user, $model;
		if (!$folder_id or !is_numeric($folder_id)) HTTP::Throw404();
		$p = new Page();
		
		$cf = new ClassifiedsFolder($folder_id);
		
		$search = request($model->args['search']);
		
		// Show list of folders of it has any
		if (!$search and $cf->hasChildren()) {
			$this->folderListing($folder_id);
			die();
		}
		
		// Otherwise, let's show a list
		$pager = new Pager;
		$pager->setLimit(10);
		$body = $cf->displayPosts($pager, $search);

		$view = new View;
		$view->setPath('classifieds/list.html');
		$view->setTag('name', $cf->getPath());
		$view->setTag('folder_id', $cf->getFolderID());
		$view->setTag('subscribed', $cf->userIsSubscribed($user));
		$view->setTag('description', $cf->getDescription());
		$view->setTag('pagination', $pager->getNav());
		$view->setTag('content', $body);

		$p->setTag('page_title', trim($cf->getTitle().' Classifieds'));
		$p->setTag('main', $view->getOutput());
		$p->output();
	}

	public function folder_rss($folder_id = false) {
		global $user, $model;
		
		if (!$folder_id or !is_numeric($folder_id)) HTTP::throw404();
		$cf = new ClassifiedsFolder($folder_id);
		if (!$cf->getFolderID()) HTTP::throw404();
		
		header('Content-type: text/xml; charset=utf-8');
		echo '<?xml version="1.0" encoding="utf-8"?>';
		
		$view = new View;
		if (!$content = $view->setPath('blog/rss/index.html', false, 600, 'classifieds/folder/' . $folder_id)) {
			$view->setTag('title', $model->lang('SITE_NAME').' '.htmlspecialchars(strip_tags($cf->getPath()), ENT_NOQUOTES, 'UTF-8'));
			$view->setTag('link', $cf->getURL());
			$view->setTag('atom_link', $model->tool('linker')->prettifyURL(array('m' => 'classifieds', 'view' => 'folder_rss', 'id' => $folder_id)));
			$view->setTag('description', $cf->getDescription());
			$view->setTag('items', $cf->getRSS());
			$content = $view->getOutput();
		}
		
		echo $content;
	}

	public function post($folder_id = false) {
		global $user, $model;
		$p = new Page();
		
		if (!$user->isLoggedIn()) HTTP::disallowed();
		
		$body = sprintf('<h1 class="dark">%s</h1>', $model->lang('POST_NEW_CLASSIFIEDS', 'ClassifiedsModel'));
		
		$body .= sprintf('<div id="controls"><a class="icon-link" href="%s"><span class="icon icon-arrow-left"> </span> %s</a></div>', $model->url(array('m' => 'classifieds')), $model->lang('BACK_TO_CLASSIFIEDS', 'ClassifiedsModel'));
		
		$form = isset($_SESSION['ci_form']) ? $_SESSION['ci_form'] : new ClassifiedsItemForm;
		$form->setFolderID($folder_id);
		$body .= $form->display();
		unset($_SESSION['ci_form']);

		$p->setTag('main', $body);
		$p->output();
	}
	
	public function edit($id) {
		global $user, $model;
		
		$p = new Page();
		
		if (!$id or !is_numeric($id)) HTTP::Throw404();
		if (!$user->isLoggedIn()) HTTP::disallowed();
		
		$body = sprintf('<h1 class="dark">%s</h1>', $model->lang('EDIT_CLASSIFIED_POST', 'ClassifiedsModel'));
		
		$body .= sprintf('<div id="controls"><a class="icon-link" href="%s"><span class="icon icon-arrow-left"> </span> %s</a></div>', $model->url(array('m' => 'classifieds')), $model->lang('BACK_TO_CLASSIFIEDS', 'ClassifiedsModel'));
		
		$form = isset($_SESSION['ci_form']) ? $_SESSION['ci_form'] : new ClassifiedsItemForm;
		$form->setClassifiedID($id);
		$body .= $form->display();
		unset($_SESSION['ci_form']);
		
		$p->setTag('main', $body);
		$p->output();
	}

	public function proc_classified() {
		global $user;
		
		if (!$user->isLoggedIn()) HTTP::disallowed();
		
		$form = new ClassifiedsItemForm;
		
		if (isset($_POST['classified_id'])) $form->setClassifiedID($_POST['classified_id']);
		
		$form->setData($_POST);
		
		$exists_validator = new ExistenceValidator($form);
		$exists_validator->validate('folder_id', '- please choose a classifieds section');
		
		$lv = new LengthValidator($form);
		$lv->setMinLength(10);
		$lv->setMaxLength(50);
		$lv->validate('title', '- please enter an ad title between 10 and 50 characters');
		
		$exists_validator->validate('body', '- please enter your ad text');
		
		$date = strtotime($form->getDatum('ts_end'));
		if (!$date) {
			$form->addError('- please enter a valid date, eg 2014-02-14');
		} elseif ($date < time()) {
			$form->addError('- please select an expiry date after today');
		} elseif ($date > (time() + 3600 * 24 * 31)) {
			$form->addError('- please select an expiry date of maximum 31 days');
		} else $form->setDatum('ts_end', unixToDate($date));
		
		if (!$form->getErrorCount()) {
			if ($form->processForm()) HTTP::redirect('/en/users/classifieds/');
			else $form->addError('- a problem occurred while saving your classified post');
		}
		$_SESSION['ci_form'] = $form;
		HTTP::redirect('/en/classifieds/post/');
	}

	public function item($classified_id = false) {
		global $user, $model;
		
		if (!$classified_id or !is_numeric($classified_id)) HTTP::throw404();
		
		$classified = new ClassifiedsItem($classified_id);

		if ((!$classified->isExpired() and $classified->isLive()) or $user->getPower() or $classified->user_id == $user->getUserID()) {
			$p = new Page();
			$classified->setShowPath(true);
			$classified->setShowRespondButton(false);
			$classified->setShowTitle(false);

			$body = sprintf('<h1 class="dark"><a href="%s">Classifieds</a></h1>', $model->url(array('m' => 'classifieds')));

			$body .= '<article class="userContentList" id="classifiedsItem">'; // it's a little messy but i get to reuse css
			$body .= sprintf('<h1>%s</h1>', $classified->getTitle());
			$body .= $classified->displayPublic();
			$body .= '</article>';

			if ($user->isLoggedIn()) {
				$crf = isset($_SESSION['classified_respond_form']) ? $_SESSION['classified_respond_form'] : new ClassifiedsRespondForm;
				$crf->setClassifiedID($classified_id);
				$body .= $crf->display();
				unset($_SESSION['classified_respond_form']);
			} else {
				$body .= sprintf(
					"<p>
						<a class=\"icon-link\" href=\"/en/users/login/\"><span class=\"icon icon-login\"> </span>%s</a>
						<a class=\"icon-link\" href=\"/en/users/register/\"><span class=\"icon icon-user-add\"> </span>%s</a>
					</p>",
					$model->lang('LOGIN_TO_REPLY', 'ClassifiedsModel'),
					$model->lang('REGISTER_TO_REPLY', 'ClassifiedsModel')
				);
			}
			
			$p->setTag('page_title', strip_tags($classified->getTitle()).' Classified Ad');
			$p->setTag('main', $body);
			$p->output();
		}
		else
			HTTP::throw404();
	}

	public function respond_proc() {
		global $user;
		
		$response = new ClassifiedsRespondForm;
		if ($_POST) $response->setData($_POST);
		if ($_FILES) $response->setFiles($_FILES);
		
		$response->processForm();
		
		$_SESSION['classified_respond_form'] = $response;

		$ci = new ClassifiedsItem($_POST['classified_id']);
		HTTP::redirect($ci->getURL());
	}

	public function delete() {
		global $user;
		$classified_id = func_get_arg(0);

		if (ctype_digit($classified_id)) {
			$ci = new ClassifiedsItem($classified_id);
			$ci->deleteUser($user);
		}

		HTTP::redirect('/en/users/classifieds/');
	}

	public function subscribe($folder_id = false) {
		global $user;
		if (!$folder_id or !is_numeric($folder_id)) HTTP::Throw404();
		$folder = new ClassifiedsFolder($folder_id);

		if ($folder->userIsSubscribed($user)) $folder->unsubscribeUser($user);
		else $folder->subscribeUser($user);

		HTTP::redirect($_GET['from'] == 'dashboard' ? '/en/users/classifieds_subscriptions/' : $folder->getURL());
	}
}
?>