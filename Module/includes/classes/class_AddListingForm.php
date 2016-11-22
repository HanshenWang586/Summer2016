<?php
class AddListingForm extends PrimordialForm {
	public function displayForm() {
		global $model, $site;

		$content = $this->displayErrors('<p>' . $model->lang('ADD_LISTING_FORM_ERRORS', 'ListingsModel') . '</p>');

		$content .= FormHelper::open($model->url(false, false, true));

		if ($this->listing_id) $content .= FormHelper::hidden('listing_id', $this->listing_id);
		
		$f[] = FormHelper::input(
			$model->lang('FORM_NAME_EN_CAPTION', 'ListingsModel'),
			'name_en',
			$this->getDatum('name_en'),
			array(
				'mandatory' => true,
				'guidetext' => $model->lang('FORM_NAME_EN_DESCR', 'ListingsModel')
			)
		);
		$f[] = FormHelper::input(
			$model->lang('FORM_NAME_CN_CAPTION', 'ListingsModel'),
			'name_zh',
			$this->getDatum('name_zh'),
			array(
				'class' => 'chinese',
				'guidetext' => $model->lang('FORM_NAME_CN_DESCR', 'ListingsModel')
			)
		);
		$city_id = $this->getDatum('city_id');
		$f[] = FormHelper::select($model->lang('FORM_CITY_CAPTION', 'ListingsModel'), 'city_id', CityList::getArray(), $city_id ? $city_id : $site->getHomeCity()->getCityID(),
			array(
				'mandatory' => true,
				'guidetext' => $model->lang('FORM_SELECT_CITY', 'ListingsModel')
			));
		$f[] = FormHelper::input(
			$model->lang('FORM_ADDRESS_EN_CAPTION', 'ListingsModel'),
			'address_en',
			$this->getDatum('address_en'),
			array(
				'mandatory' => true,
				'guidetext' => $model->lang('FORM_ADDRESS_EN_DESCR', 'ListingsModel')
			)
		);
		$f[] = FormHelper::input(
			$model->lang('FORM_ADDRESS_CN_CAPTION', 'ListingsModel'),
			'address_zh',
			$this->getDatum('address_zh'),
			array(
				'mandatory' => true,
				'class' => 'chinese',
				'guidetext' => $model->lang('FORM_ADDRESS_CN_DESCR', 'ListingsModel')
			)
		);
		$f[] = FormHelper::input($model->lang('FORM_HOURS_CAPTION', 'ListingsModel'), 'hours', $this->getDatum('hours'),
			array(
				'guidetext' => $model->lang('FORM_HOURS_DESCR', 'ListingsModel')
			));
		$f[] = FormHelper::input($model->lang('FORM_HAPPY_HOUR_CAPTION', 'ListingsModel'), 'happy_hour', $this->getDatum('happy_hour'),
			array(
				'guidetext' => $model->lang('FORM_HAPPY_HOUR_DESCR', 'ListingsModel')
			));
		$f[] = FormHelper::input($model->lang('FORM_MOBILE_CAPTION', 'ListingsModel'), 'mobile', $this->getDatum('mobile'),
			array('type' => 'tel')
		);
		$f[] = FormHelper::input($model->lang('FORM_PHONE_CAPTION', 'ListingsModel'), 'phone', $this->getDatum('phone'),
			array(
				'type' => 'tel',
				'guidetext' => $model->lang('FORM_PHONE_DESCR', 'ListingsModel')
			));
		$f[] = FormHelper::checkbox($model->lang('FORM_PHONE_CODE_OVERRIDE_CAPTION', 'ListingsModel'), 'phone_code_override', $this->getDatum('phone_code_override'),
			array(
				'guidetext' => $model->lang('FORM_PHONE_CODE_OVERRIDE_DESCR', 'ListingsModel')
			));
		$f[] = FormHelper::input($model->lang('FORM_FAX_CAPTION', 'ListingsModel'), 'fax', $this->getDatum('fax'),
			array(
				'type' => 'tel',
				'guidetext' => $model->lang('FORM_FAX_DESCR', 'ListingsModel')
			));
		$f[] = FormHelper::checkbox($model->lang('FORM_FAX_CODE_OVERRIDE_CAPTION', 'ListingsModel'), 'fax_code_override', $this->getDatum('phone_code_override'),
			array(
				'guidetext' => $model->lang('FORM_FAX_CODE_OVERRIDE_DESCR', 'ListingsModel')
			));
		$url = $this->getDatum('url');
		if ($url) $url = addHttp($url);
		$f[] = FormHelper::input($model->lang('FORM_URL_CAPTION', 'ListingsModel'), 'url', $url,
			array('type' => 'url')
		);
		$f[] = FormHelper::textarea($model->lang('FORM_DESCRIPTION_CAPTION', 'ListingsModel'), 'description', $this->getDatum('description'),
			array(
				'mandatory' => true,
				'guidetext' => $model->lang('FORM_DESCRIPTION_DESCR', 'ListingsModel')
			));
		$f[] = FormHelper::submit();
		
		$content .= FormHelper::fieldset($model->lang('FORM_ADD_LISTING_DETAILS', 'ListingsModel'), $f);
		$content .= FormHelper::close();
		
		return $content;
	}

	public function processForm() {
		
	}
}
?>