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
class CreateAdView extends CMS_Form_View {
	public static $browserAccess = false;
	
	public function init($args = array()) {
		$this->css('createad');
		$this->js('createad');
	}
		
	public function getContent($options = array()) {
		if ($user = $this->tool('security')->getActiveUser() and $company = $user->getCompany()) {
			$contactInfo = $user->get(array('email', 'name', 'telephone'), true);
			$contactInfo['contactPosition'] = $user->getCompanyRole($company->id);
			$contactInfo['contactName'] = $contactInfo['name'];
			$this->tool('html')->addJSVariable('contactInfo', $contactInfo);
			
			if ($company->get('city_id')) {
				$address = $company->get(array('district_id', 'zipcode'), true);
				$address['lang'] = $company->get(array('address'), true, $this->m->getActiveLanguages(NULL, $company->id));
				$this->tool('html')->addJSVariable('address', $address);
			}
		}	
		$cats = $this->m->getCategories();
		$ids = array_transpose($cats, 'id');
		$count = count($ids);
		$showForm = $cats && ($count == 3 or ($count == 2 and !$this->db()->count('categories', array('category_id' => $ids[1]))));
		
		$catModel = $this->model->module('categories');
		$categories = $catModel->getCategories($this->name, array('selectableOnly' => true, 'js' => true));
		$tag = $this->tool('tag');
		ob_start();
?>
		<form id="categorySelectForm" action="<?=$this->url(array('m' => $this->name, 'view' => 'post'));?>" method="get">
			<div>
				<div class="section">
					<h2 class="caption"><?=$this->lang('SELECT_CATEGORY');?></h2>
					<input type="hidden" name="city" value="<?=$this->arg('city')?>">
					<div id="categorySelect">
						<?=$tag->select('category[]', (array) request($categories['NULL']), request($ids[0]), array('noEmpty' => true, 'key' => 'id', 'value' => 'langName', 'attr' => array('size' => 8), 'class' => 'selectCategory1'))?>
						<?=$tag->select('category[]', (array) request($categories[(int) request($ids[0])]), request($ids[1]), array('noEmpty' => true, 'key' => 'id', 'value' => 'langName', 'attr' => array('size' => 8), 'class' => 'selectCategory2'));?>
						<?=$tag->select('category[]', (array) request($categories[(int) request($ids[1])]), request($ids[2]), array('noEmpty' => true, 'key' => 'id', 'value' => 'langName', 'attr' => array('size' => 8), 'class' => 'selectCategory3'));?>
					</div>
					<input class="submit" id="next" type="submit" value="<?=$this->lang('SELECT_CATEGORY', false, false, true);?>"><br>
					<div class="clear"></div>
				</div>
			</div>
		</form>
<?
		if ($showForm) {
			$groups = $catModel->getCategoryFields($ids, NULL, 'group');
?>
		<form action="<?=$this->url(false, false, true);?>" method="post">
			<div>
<? foreach($ids as $index => $cat_id) printf("\t\t\t<input type=\"hidden\" name=\"data[ad][category%d]\" value=\"%d\">\n", $index + 1, $cat_id); ?>
				<input type="hidden" name="action" value="createAd">
				<div class="section">
					<h2 class="caption"><?=$this->lang('LANGUAGE_SELECT');?></h2>
					<?=$this->getLanguageSelect(NULL, isset($company) ? $company->id : NULL);?>
					<div class="clear"></div>
				</div>
<?
			foreach($groups as $group => $fields) {
				$langKey = $group == 'NULL' ? 'AD_DETAILS' : str_replace(' ', '_', strtoupper($group));
?>
				<div class="section">
					<h2 class="caption"><?=$this->lang($langKey);?></h2>
<?
				foreach($fields as $field) {
					switch($field['type']) {
						case 'list':
							$values = $this->fieldValues($field['id']);
							if ($values) {
								if (count($values) < 5) echo "<label>" . $this->lang($this->m->getInputLangKey($field['name'], 'adFields')) . "</label>" . $this->getInputRadio($field['name'], $values, false, array('group' => 'adFields', 'title' => false, 'required' => true));
								else echo $this->getSelect($field['name'], $values, array('group' => 'adFields', 'required' => true));
							}
						break;
						case 'colour':
							echo "<label>" . $this->lang($this->m->getInputLangKey($field['name'], 'adFields')) . "</label>" . $this->getInputRadio($field['name'], $this->fieldValues('colour'), false, array('group' => 'adFields', 'title' => false, 'required' => true));
						break;
						case 'text':
							echo $this->getInput($field['name'], array('group' => '!lang'));
						break;
						case 'multi-select':
							$values = $this->fieldValues($field['id']);
							echo "<div class=\"multiselect\">\n";
							echo "<label class=\"multiselectLabel\">" . $this->lang($this->m->getInputLangKey($field['name'], 'adFields')) . "</label>\n" . $this->getCheckbox($field['name'], $values, false, array('group' => 'adFields', 'title' => false, 'id' => false));
							echo "</div>\n";
						break;
						case 'date':
						case 'number':
							echo $this->getInput($field['name'], array('group' => 'adFields', 'type' => $field['type'], 'required' => true));
							if ($field['unit']) {
								if (strpos($field['unit'], ',') > 0) {
									$units = array_filter(explode(',', $field['unit']));
									$units = array_combine($units, $units);
									echo $this->getInputRadio($field['name'], $units, false, array('group' => 'adFields', 'required' => true, 'title' => false));
								} else printf('<span class="unit">%s</span>', $field['unit']);
							}
						break;
						case 'year':
							$years = array();
							$year = date('Y');
							for ($i = $year; $i > 1970; $i--) $years[] = $i;
							echo $this->getSelect($field['name'], $years, array('group' => 'adFields', 'required' => true));
						break;
						default:
							echo sprint_rf($field);
						break;
					}
				}
?>
					<div class="clear"></div>
				</div>
<?			} ?>
				<div class="section">
					<h2 class="caption"><?=$this->lang('AD_TEXT');?></h2>
					<?=$this->getInput('title', array('group' => '!lang'));?>
					<?=$this->getTextarea('description', array('group' => '!lang'));?>
					<div class="clear"></div>
				</div>
				<div class="section">
					<?=sprintf('<span style="display: none;" class="button useAddressButton">%s</span>', $this->lang('USE_COMPANY_ADDRESS'));?>
					<h2 class="caption"><?=$this->lang('ADDRESS');?></h2>
					<?=$this->model->template('postAd')->getLocationInput('ad', $this, $options['city']['name']);?>
					<div class="clear"></div>
				</div>
				<div class="section contactSection">
					<? if (request($address)) { printf('<span style="display: none;" class="button useContactInfoButton">%s</span>', $this->lang('USE_CONTACT_INFO')); } ?>
					<h2 class="caption"><?=$this->lang('CONTACT_INFO');?></h2>
					<?=$this->getInput('contactName', array('group' => 'ad'))?>
					<?=$this->getSelect('contactPosition', $this->fieldValues('role', 'userObject'), array('group' => 'ad'))?>
					<?=$this->getInput('email', array('group' => 'ad', 'type' => 'email'))?>
					<?=$this->getInput('telephone', array('group' => 'ad', 'type' => 'tel'))?>
					<div class="clear"></div>
				</div>
<?
		}
		
?>
				<input class="submit" id="next" type="submit" value="<?=$this->lang('NEXT', false, false, true);?>">			
			</div>
		</form>
<?
		$content = ob_get_clean();
		
		return $content;
	}
	
	public function getCategories() {
		$content = "\t\t\t<ul class=\"categorySelect\">\n";
		ob_start();
		$categories = $this->model->module('categories')->getCategories($this->name, array('selectableOnly' => true, 'icons' => true, 'noGroupBy' => true, 'noSubCats' => true));
		foreach($categories as $cat) {
			$icon = $cat['icon'] ? $this->m->getCategoryIcon($cat['icon']) : false;
?>
				<li>
					<a href="<?=$this->url(array('category' => $cat['id']), false, true)?>">
						<? if ($icon = $this->m->getCategoryIcon($cat['icon'])) printf("\t\t\t\t<span class=\"icon\"><img src=\"%s\"></span>\n", $icon); ?>
						<?=$cat['langName']?>
					</a>
				</li>
<? 
		}
		$content .= ob_get_clean();
		$content .= "\t\t\t</ul>\n";
		return $content;
	}
}

?>