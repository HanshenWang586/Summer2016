<?php
class XmlTools extends CMS_Class {
	private $output;
	private $data;
	private $module = false;
	private $type = false;
	
	public function init($args) {
		$this->calculateContent();
	}

	private function calculateContent() {
		$this->module = $_GET['m'];
		$this->type = $_GET['type'];

		switch ($this->module) {
			case "collections":
				if ($view_id = (int) request($_GET['view'])) {
					// Get the collection_id
					$id = query('collectionviews', false, $view_id, array('selectField' => 'collection_id'));
				}
				if ($id || ($id = (int) request($_GET['doc_id']))) {
					// TODO: Sortering standaard op last modified?
					$collection = $this->pageBuilder->getCollection($id, 10, 0, 'title', 'ASC');
				}
				// The type of feed requested
				$func = $this->type;
					
				if ($collection && method_exists($this, $func)) {
					if (count($collection['items']) > 0) {
						$params['id'] = $collection['id'];
						$params['module'] = $collection['module'];
						$params['title'] = $collection['name'];
						// TODO: Last modified van de geselecteerde items
						$params['updated'] = strftime("%Y-%m-%d", $collection['modified']);
							
						$this->output = $this->$func($params, $collection['items']);
					}
					else {
						$this->output = 'The requested feed is empty.';
					}
				}
				else {
					$this->output = 'The requested feed is unavailable.';
				}
				/*
					$function = $collection['module'];
					if (method_exists($this->pageBuilder->printCol, $function)) {
					$this->output = $this->pageBuilder->printCol->$function($collection);
					} else {
					$this->pageBuilder->log->add(constant('LOG_SYSTEM_ERROR'), "<strong>HTML Printing Tool:</strong> The Collection Printer has no \"$function\" printer.");
					}
					*/
				break;
			default:
				break;
		}
	}

	public function printXML() {
		echo $this->output;
		//XMLOut($this->data);
		die();
	}

	private function atom($params, $entries) {
		$content = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>";
		$content .= "<feed xmlns=\"http://www.w3.org/2005/Atom\"\n";
		$content .= "\txml:lang=\"en\"\n";
		$content .= "\txml:base=\"" . $this->pageBuilder->paths['root'] . "\">\n";
		$content .= "\t<id>" . $this->pageBuilder->paths['root'] . $params['id'] . "</id>\n";
		$content .= "\t<title>" . $params['title'] . "</title>\n";
		$content .= "\t<updated>" . $params['updated'] . "T00:00:00Z</updated>\n";
		//$content .= "\t<link rel=\"self\" href=\"/myfeed\" />\n";
		$content .= "\t<author>\n";
		$content .= "\t<name>wharf</name>\n";
		$content .= "\t</author>\n";
		$content .= $this->getAtomEntries($params, $entries);
		$content .= "</feed>\n";
		return $content;
	}

	private function getAtomEntries($params, $entries) {
		$return = '';
		for($i=0; $i<count($entries); $i++) {
			$entry = $entries[$i];

			$return .= "\t<entry>\n";
			$return .= "\t\t<id>" . $this->pageBuilder->paths['root'] . $entries[$i]['id'] . "</id>\n";
			$return .= "\t\t<title>" . $entries[$i]['title'] . "</title>\n";
			$return .= "\t\t<updated>" . strftime("%Y-%m-%d", $entries[$i]['modified']) . "T00:00:00Z</updated>\n";
			$return .= "\t\t<link href=\"" . $entries[$i]['url'] . "\" />\n";
			// see if we have an asset
			$asset = $params['module'] == 'assets' ? $entry : request($entry['asset_id']);
			if ($asset) {
				$assetLink = $this->pageBuilder->getTool('linker')->getImageLink($asset, 300, 300);
				$imageLink = $GLOBALS['imagesPath'] . $asset['name'];
				if($mime_type = mime_content_type($imageLink)) {
					$return .= "\t\t<content type=\"" . $mime_type . "\" src=\"" . $assetLink. "\" />\n";
				}
			}
			//$return .= "\t\t<content type=\"html\" &lt;img src=&quot;" . $assetLink. "&quot;&gt; />\n";
			$return .= "\t\t<summary>" . $entries[$i]['short'] . "</summary>\n";
			$return .= "\t</entry>\n";
		}
		return $return;
	}
}

?>