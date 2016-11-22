<?php

/**
 * @author yereth
 *
 * This class contains the login pages, which can be selected as a sitetool from ewyse.
 *
 * Most of the actual logging in is handled by the tool Security, found in the tools folder.
 *
 * TODO: Abstract some of the contact handling to a new Contact Tool class.
 *
 */
class EditView extends CMS_View {
	public function init($args = array()) {
		$this->css('default');
		$this->js('general');
		$this->js('jquery.form', $this->model->urls['root'] . '/js/jquery/');
		$this->js('jquery.metadata', $this->model->urls['root'] . '/js/jquery/');
		$this->js('jquery.tmpl', $this->model->urls['root'] . '/js/jquery/');
		$this->js('popupmessage', $this->model->urls['root'] . '/js/jquery/');
		$this->js('jquery.easing.1.3', $this->model->urls['root'] . '/js/jquery/');
	}
	
	public function getContent($options = array()) {
		ob_start();
		
		$modules = $this->db()->query('modules', array('hasCategories' => 1), array('transpose' => 'name'));
		$module = $this->arg('module');
		if ($module && in_array($module, $modules)) {
			$categories = $this->m->getCategories($module, array('js' => true));
		}
		$tag = $this->tool('tag');
?>
		<div class="textContent">
			<form method="GET" action="<?=$this->url(array('m' => 'categories', 'view' => 'edit'))?>"><div>
				<?=$tag->select('module', $modules, $module, array('caption' => $this->lang('MODULE_SELECT'), 'emptyCaption' => $this->lang('MODULE_EMPTY_CAPTION')))?>
				<input class="submit" type="submit" value="<?=$this->lang('GET_CHANGES', false, false, true);?>"><br>
			</div></form>
		</div>
<? if (isset($categories)) { ?>						
		<div class="textContent">
			<form method="GET" id="categoriesForm" action="<?=$this->url(array('m' => 'categories', 'view' => 'edit', 'module' => $this->arg('module')))?>">
				<div>
					<input type="hidden" name="module" value="<?=$module?>">
					<ul id="selectCategories">
						<li class="category">
							<?=$tag->select('category', (array) request($categories['NULL']), $this->arg('category'), array('caption' => $this->lang('CATEGORY_SELECT'), 'key' => 'id', 'value' => 'langName', 'class' => 'selectCategory1', 'emptyCaption' => $this->lang('CATEGORY_EMPTY_CAPTION')))?>
							<span class="actions">
								<a class="addCategory" href="<?=$this->url(array('output' => 'json', 'get' => 'addCategory'), false, true)?>"><img src="assets/icons/plus.gif"></a>
								<a class="removeCategory <?=$tag->getMeta(array('depends' => 'select.selectCategory1'));?>" href="<?=$this->url(array('action' => 'deleteCategory', 'args' => 'id'), false, true)?>"><img src="assets/icons/close-small.png"></a>
							</span>
						</li>
						<li class="subcategory"><?
							$values = ($category = $this->arg('category')) ? (array) request($categories[$category]) : array(); 
							echo $tag->select('subcategory', $values, $this->arg('subcategory'), array('caption' => $this->lang('SUBCATEGORY_SELECT'), 'key' => 'id', 'value' => 'langName', 'class' => 'selectCategory2', 'emptyCaption' => $this->lang('SUBCATEGORY_EMPTY_CAPTION')))
						?>
							<span class="actions">
								<a class="addCategory <?=$tag->getMeta(array('depends' => 'select.selectCategory1'));?>" href="<?=$this->url(array('output' => 'json', 'get' => 'addCategory', 'args' => 'id'), false, true)?>"><img src="assets/icons/plus.gif"></a>
								<a class="removeCategory <?=$tag->getMeta(array('depends' => 'select.selectCategory2'));?>" href="<?=$this->url(array('action' => 'deleteCategory', 'args' => 'id'), false, true)?>"><img src="assets/icons/close-small.png"></a>
							</span>
						</li>
						<li class="subsubcategory"><?
							$values = ($subcategory = $this->arg('subcategory')) ? (array) request($categories[$subcategory]) : array(); 
							echo $tag->select('subsubcategory', $values, $this->arg('subsubcategory'), array('caption' => $this->lang('SUBSUBCATEGORY_SELECT'), 'key' => 'id', 'value' => 'langName', 'class' => 'selectCategory3', 'emptyCaption' => $this->lang('SUBSUBCATEGORY_EMPTY_CAPTION')))
							?>
							<span class="actions">
								<a class="addCategory <?=$tag->getMeta(array('depends' => 'select.selectCategory2'));?>" href="<?=$this->url(array('output' => 'json', 'get' => 'addCategory', 'args' => 'id'), false, true)?>"><img src="assets/icons/plus.gif"></a>
								<a class="removeCategory <?=$tag->getMeta(array('depends' => 'select.selectCategory3'));?>" href="<?=$this->url(array('action' => 'deleteCategory', 'args' => 'id'), false, true)?>"><img src="assets/icons/close-small.png"></a>
							</span>
						</li>
					</ul>
					<input class="submit" type="submit" value="<?=$this->lang('GET_CATEGORY_INFO', false, false, true);?>"><br>
				</div>
			</form>
		</div>
<?
	 echo $this->getCategoryFields($options);
} 
		$content = ob_get_clean();
		$content = sprintf("<div class=\"textContent\">\n\t%s</div>\n",
			$content
		);
		return $content;
	}
	
	public function getAddCategory($id) {
		if ($id && !is_numeric($id)) {
			$this->logL(constant('LOG_USER_WARNING'), 'E_ADD_CATEGORY_PARAMS_INCORRECT');
			return false;
		}
		$tag = $this->tool('tag');
		if ($id && !$category = $this->m->getCategory($id)) return false;
		if (request($category)) $module = $category['module'];
		else $module = $this->arg('module');
		if (!$module) return false;
		ob_start();
?>
		<form class="addCategoryForm" enctype="multipart/form-data" method="POST" action="<?=$this->url(array('action' => 'addCategory'), false, true)?>"><div>
			<h1><?=$this->lang('ADD_CATEGORY_TITLE');?></h1>
			<h2><?=$this->lang('ADD_CATEGORY_TO')?>: <?=$id ? $category['langName'] : $this->lang('MAIN_CATEGORY')?></h2>
			<?=$tag->input(array('attr' => array('value' => $module, 'type' => 'hidden', 'name' => 'data[module]')));?>
			<? if ($id) echo $tag->input(array('attr' => array('value' => (int) $id, 'type' => 'hidden', 'name' => 'data[category_id]'))); ?>
			<?=$tag->input(array('caption' => $this->lang('ADD_CATEGORY_ICON'), 'attr' => array('type' => 'file', 'name' => 'data[icon]')));?>
			<?=$tag->input(array('caption' => $this->lang('FIELD_CAT_NAME'), 'attr' => array('name' => 'data[name]')));?>
			<?
				foreach($this->model->module('lang')->allowedLanguages as $lang) {
					echo $tag->input(array('caption' => $this->lang('FIELD_CAT_' . $lang), 'attr' => array('class' => strtolower($lang), 'name' => $lang . '[name]')));
				}
			?>
			<input class="submit" type="submit" value="<?=$this->lang('ADD_CATEGORY', false, false, true);?>"><br>
		</div></form>
<?
		$content = ob_get_clean();
		return $content;
	}
	
	public function getCategoryFields($options = array()) {
		$cats = array('category', 'subcategory', 'subsubcategory');
		$tag = $this->tool('tag');
		ob_start();
		echo '<div class="textContent">';
		foreach($cats as $cat) {
			if ($id = (int) $this->arg($cat) and $category = $this->db()->query('categories', $id)) {
				$name = $this->m->getLangKey($category['name']);
?>
			<div class="category">
				<h2><?=$this->lang(strtoupper($cat) . '_FIELDS') . ': "' . $name . '"';?></h2>
				<h3><?=$this->lang('ICON')?></h3>
<? if ($category['icon']) { ?>
				<span class="icon">
					<img src="<?=$this->m->getIconURL($category['module']) . $category['icon'];?>">
					<a class="deleteIcon" href="<?=$this->url(array('action' => 'deleteCategoryIcon', 'data' => array('id' => $id)), false, true)?>" title="<?=$this->lang('DELETE_ICON', false, false, true)?>"><img src="assets/icons/close-small.png"></a>
				</span>
<? } ?>				
				<form class="editCategoryIconForm" enctype="multipart/form-data" method="POST" action="<?=$this->url(array('action' => 'editCategoryIcon'), false, true)?>"><div>
					<?=$tag->input(array('attr' => array('value' => $id, 'type' => 'hidden', 'name' => 'data[id]')));?>
					<?=$tag->input(array('caption' => $this->lang('CHANGE_CATEGORY_ICON'), 'attr' => array('type' => 'file', 'name' => 'data[icon]')));?>
					<input class="smallSubmit" type="submit" value="<?=$this->lang('EDIT_CATEGORY_ICON', false, false, true);?>"><br>
				</div></form>
				<h3><?=$this->lang('TRANSLATIONS')?></h3>
				<ul class="langNames">
<? foreach($this->model->allowedLanguages as $lang) { ?>
					<li class="<?=strtolower($lang)?>Flag"><?=$this->lang($name, $category['module'] . 'Categories', $lang, false, true)?></li>
<? } ?>
				</ul>
				<div class="fieldsWrapper">
					<h3><?=$this->lang('CURRENT_FIELDS')?></h3>
	<?
					echo $this->getFields($id);
	?>
					<form class="addFieldForm" method="POST" action="<?=$this->url(false, false, true)?>"><div>
						<h3><?=$this->lang('ADD_FIELD')?></h3>
						<?=$tag->input(array('attr' => array('value' => 'addFieldToCategory', 'type' => 'hidden', 'name' => 'action')));?>
						<?=$tag->input(array('attr' => array('value' => $category['module'], 'type' => 'hidden', 'name' => 'data[module]')));?>
						<?=$tag->input(array('attr' => array('value' => $id, 'type' => 'hidden', 'name' => 'data[category_id]')));?>
						<?=$tag->input(array('caption' => $this->lang('FIELD_NAME'), 'attr' => array('name' => 'data[name]')));?>
						<?=$tag->select('data[type]', $this->fieldValues('type'), false, array('caption' => $this->lang('TYPE_SELECT'), 'emptyCaption' => $this->lang('TYPE_EMPTY_CAPTION')))?>
						<?=$tag->input(array('caption' => $this->lang('FIELD_UNIT'), 'labelAttr' => array('class' => 'unit'), 'attr' => array('name' => 'data[unit]')));?>
						<?=$tag->input(array('caption' => $this->lang('FIELD_AUTOCOMPLETE'), 'labelAttr' => array('class' => 'checkbox autocomplete'), 'labelAfter' => true, 'attr' => array('type' => 'checkbox', 'name' => 'data[autocomplete]', 'value' => 1)));?>
						<?=$tag->input(array('caption' => $this->lang('FIELD_SEARCHABLE'), 'labelAttr' => array('class' => 'checkbox'), 'labelAfter' => true, 'attr' => array('type' => 'checkbox', 'name' => 'data[searchable]', 'value' => 1)));?>
						<input class="smallSubmit" type="submit" value="<?=$this->lang('ADD_FIELD_BUTTON', false, false, true);?>">
					</div></form>
				</div>
			</div>
<?
			}
		}
		echo "</div>";
		return ob_get_clean();
	}
	
	public function getFields($category_id) {
		if (!$category_id or !is_numeric($category_id)) return false;
		$category_id = (int) $category_id;
		$tag = $this->tool('tag');
		$groups = $this->db()->query('categoryFields', array('category_id' => $category_id), array('arrayGroupBy' => 'group'));
		$content = sprintf("\t\t\t<div><div class=\"fields {id: %d}\">\n", $category_id);
		if ($groups) {
			ob_start();
			foreach($groups as $group => $fields) {
				if ($group == 'NULL') $group = '(ad details)';
				echo sprintf("\t\t\t<h4>%s</h4>\n", $group);
				echo "\t\t\t<ul class=\"fieldsList\">\n";
				foreach($fields as $field) {
					$isList = in_array($field['type'], array('list','multi-select'));
?>
					<li class="field">
						<span class="item">
<?
						echo $this->tool('tag')->input(array(
							'caption' => $field['name'],
							'attr' => array(
								'name' => 'fields[]',
								'value' => $field['id'],
								'type' => 'checkbox'
							),
							'labelAfter' => true
						));
?>							
							<span class="fieldType">(<?=$field['type']?>) <?=$field['searchable'] ? '(' . strtolower($this->lang('FIELD_SEARCHABLE')) . ')' : '';?> <?=$field['autocomplete'] ? '(' . strtolower($this->lang('AUTOCOMPLETE')) . ')' : '';?> <?=$field['unit'] ? '(' . strtolower($this->lang('UNIT')) . ': ' . $field['unit'] . ')' : '';?></span>
							<span class="actions">
								<a class="deleteField" href="<?=$this->url(array('action' => 'deleteField', 'data' => array('id' => $field['id'])), false, true)?>" title="<?=$this->lang('DELETE_FIELD', false, false, true)?>"><img src="assets/icons/close-small.png"></a>
<? if ($isList) { ?>
								<span class="showFieldOptions"><?=$this->lang('SHOW_OPTIONS')?></span>
<? } ?>
							</span>
						</span>
<?
					if ($isList) {
						$tag = $this->tool('tag'); 
						echo "\t\t\t<span class=\"optionsWrapper\">\n";
						echo $this->getFieldOptions($field);
?>
							<form class="addFieldValueForm" method="POST" enctype="multipart/form-data" action="<?=$this->url(false, false, true)?>"><span>
								<?=$tag->input(array('attr' => array('value' => 'addOptionToField', 'type' => 'hidden', 'name' => 'action')));?>
								<?=$tag->input(array('attr' => array('value' => $field['module'] . 'Model', 'type' => 'hidden', 'name' => 'data[module]')));?>
								<?=$tag->input(array('attr' => array('value' => $field['id'], 'type' => 'hidden', 'name' => 'data[categoryField_id]')));?>
								<?=$tag->input(array('attr' => array('value' => $field['name'], 'type' => 'hidden', 'name' => 'data[field]')));?>
								<?=$tag->input(array('caption' => $this->lang('ADD_FIELDVALUE_NAME'), 'attr' => array('name' => 'data[value]')));?>
								<?=$tag->input(array('caption' => $this->lang('ADD_FIELDVALUE_ICON'), 'attr' => array('type' => 'file', 'name' => 'data[icon]')));?>
								<input class="smallSubmit" type="submit" value="<?=$this->lang('ADD_FIELDVALUE_BUTTON', false, false, true);?>">
								<span class="bulkWrapper">
									<label>
										<span><?=$this->lang('LABEL_OPTIONS_BULK')?></span>
										<textarea name="bulk"></textarea>
									</label>
									<input class="smallSubmit" type="submit" value="<?=$this->lang('ADD_FIELDVALUE_BUTTON', false, false, true);?>">
								</span>
							</span></form>
						</span>
<? 
					}
					echo "\t\t\t\t</li>\n";
				}
				echo "\t\t\t</ul>\n";
			}
			echo '</div>';
			$groups = $this->db()->query('categoryFields', false, array('modifier' => 'DISTINCT', 'transpose' => 'group'));
?>
				<form method="POST" class="moveFieldsToGroupForm" action="<?=$this->url(array('action' => 'moveFieldsToGroup'), false, true)?>"><div class="moveFields">
					<span><?=$this->lang('MOVE_TO_GROUP')?></span>
					<?=$tag->input(array('attr' => array('class' => 'fields', 'type' => 'hidden', 'name' => 'data[fields]')));?>
					<?=$tag->input(array(
							'attr' => array(
								'name' => 'data[which]',
								'value' => 'group',
								'type' => 'radio'
							),
							'default' => true,
							'labelAfter' => true
						));
					?>
					<?=$tag->select('data[group]', $groups, false, array('caption' => $this->lang('SELECT_GROUP'), 'emptyCaption' => $this->lang('SELECT_GROUP')))?>
					<?=$tag->input(array(
							'attr' => array(
								'name' => 'data[which]',
								'value' => 'new',
								'type' => 'radio'
							),
							'labelAfter' => true
						));
					?>
					<?=$tag->input(array('caption' => $this->lang('NEW_GROUP'), 'attr' => array('name' => 'data[new]')));?>
					<input class="smallSubmit" type="submit" value="<?=$this->lang('MOVE', false, false, true);?>">
				</div></form>
<?
			$content .= ob_get_clean();
		} else {
			$content .= sprintf("\t\t\t<div class=\"noFieldsInfo\">%s</div>", $this->lang('CAT_NO_FIELDS'));
		}
		$content .= "\t\t\t</div>\n";
		return $content;
	}
	
	public function getFieldOptions($field) {
		if (is_numeric($field)) $field = $this->db()->query('categoryFields', (int) $field);
		if (!is_array($field)) return false;
		$model = $field['module'] . 'Model';
		$options = $this->fieldValues($field['id'], false, true, true);
		ob_start();
		if ($options) {
			echo "\t\t\t\t<ul class=\"optionsList\">\n";
			$even = false;
			$tag = $this->tool('tag');
			foreach($options as $option) {
?>
								<li class="<?=($even = !$even) ? 'odd' : 'even';?> option <?=$tag->getMeta(array('value' => $option['value']))?>">
<? if ($option['icon']) { ?>
									<span class="icon">
										<img src="<?=$this->m->getIconURL($field['module']) . 'options/' . $option['icon'];?>">
										<a class="deleteIcon" href="<?=$this->url(array('action' => 'deleteOptionIcon', 'data' => array('value' => $option['value'], 'categoryField_id' => $field['id'], 'module' => $model, 'field' => $field['name'])), false, true)?>" title="<?=$this->lang('DELETE_ICON', false, false, true)?>"><img src="assets/icons/close-small.png"></a>
									</span>
<? } ?>							
									<span class="<?=strtolower($this->model->lang)?> optionValue"><?=$option['langName'];?></span>
									<span class="actions">
										<span title="<?=$this->lang('EDIT_OPTION', false, false, true)?>" class="editOption"><img src="assets/icons/edit.gif"></span>
										<a class="deleteOption" href="<?=$this->url(array('action' => 'deleteOption', 'data' => array('value' => $option['value'], 'categoryField_id' => $field['id'], 'module' => $model, 'field' => $field['name'])), false, true)?>" title="<?=$this->lang('DELETE_OPTION', false, false, true)?>"><img src="assets/icons/close-small.png"></a>
									</span>
								</li>
<?
			}
			echo "\t\t\t\t</ul>\n";
		}
		return ob_get_clean();
	}
}

?>