<?php

abstract class CMS_View extends CMS_Model {
	/**
	 * Stores the model of the view
	 * @var CMS_Model
	 */
	public $m;
	
	public $viewName;
	
	/**
	 * If the view can be directly called by browser parameters. Default is true
	 * @var bool
	 */
	public static $browserAccess = true;
	
	public function __construct(MainModel $model, CMS_Model $m, $className, $name, $args = array()) {
		// Set the local model
		$this->m = $m;
		$this->viewName = $name;
		parent::__construct($model, $this->m->className, $this->m->name, $args);
		// We don't want view specific language strings... so we use the name and classname of the model
	}
}

?>