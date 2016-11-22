<?php
class EventsEditForm extends PrimordialForm {
	public function displayForm() {
		global $model, $site, $user;
		
		$event_id = $this->args['event_id'];
		
		$content = $this->displayErrors('<p>' . $model->lang('FORM_EDIT_EVENT_ERRORS', 'CalendarModel') . '</p>');
		$content .= FormHelper::open($model->url(false, false, true), array('id' => 'eventForm'));
		
		if ($event_id) $content .= FormHelper::hidden('event_id', $event_id);
		
		$location = sprintf('<label id="location_label" for="location">%s</label><div class="inputWrapper" id="selected_location">', $model->lang('FORM_LOCATION_CAPTION', 'CalendarModel'));

		if ($listing_id = $this->getDatum('listing_id')) {
			$listing = new ListingsItem($listing_id);
			$location .= $listing->getCalendarFormSummary();
		}
		else {
			$location .= "<input class=\"text\" id=\"location\" onkeyup=\"calendarSuggestLocations()\">
			<div id=\"suggested_locations_loading\">loading...</div>
			<div id=\"suggested_locations\"></div>";
		}

		$location .= '</div>';
		
		$f[] = $location;
		
		$f[] = FormHelper::input(
			$model->lang('FORM_TITLE_CAPTION', 'CalendarModel'),
			'title',
			$this->getDatum('title'),
			array(
				'mandatory' => true,
				'minlength' => 10,
				'maxlength' => 50,
				'guidetext' => $model->lang('FORM_TITLE_DESCR', 'CalendarModel')
			)
		);
		$f[] = FormHelper::radio(
			$model->lang('FORM_TYPE_CAPTION', 'CalendarModel'),
			'type',
			$this->args['types'],
			$this->getDatum('type'),
			array('default' => 'one-day')
		);
		$f[] = FormHelper::input(
			$model->lang('FORM_EVENT_DATE_CAPTION', 'CalendarModel'),
			'event_date',
			$this->getDatum('event_date'),
			array(
				'type' => 'date',
				'placeholder' => 'eg 2014-02-14',
				'guidetext' => $model->lang('FORM_EVENT_DATE_DESCR', 'CalendarModel')
			)
		);
		$f[] = FormHelper::input(
			$model->lang('FORM_END_DATE_CAPTION', 'CalendarModel'),
			'end_date',
			$this->getDatum('end_date'),
			array(
				'type' => 'date',
				'placeholder' => 'eg 2014-02-14',
				'guidetext' => $model->lang('FORM_END_DATE_DESCR', 'CalendarModel')
			)
		);
		$days = $this->getDatum('days');
		if (!$days) $days = array();
		elseif(is_string($days)) $days = explode(',', $days);
		$f[] = FormHelper::checkbox_array($model->lang('FORM_DAYS_CAPTION', 'CalendarModel'), 'days', $model->tool('datetime')->getDays(), $days);
		$f[] = FormHelper::checkbox($model->lang('FORM_ALL_DAY_CAPTION', 'CalendarModel'), 'all_day', $this->getDatum('all_day'),
			array(
				'guidetext' => $model->lang('FORM_ALL_DAY_DESCR', 'CalendarModel')
			));
		$f[] = FormHelper::input(
			$model->lang('FORM_STARTING_TIME_CAPTION', 'CalendarModel'),
			'starting_time',
			$this->getDatum('starting_time'),
			array(
				'type' => 'time',
				'placeholder' => 'eg 15:30',
				'guidetext' => $model->lang('FORM_STARTING_TIME_DESCR', 'CalendarModel')
			)
		);
		$f[] = FormHelper::textarea($model->lang('FORM_DESCRIPTION_CAPTION', 'CalendarModel'), 'description', $this->getDatum('description'),
			array(
				'mandatory' => true,
				'guidetext' => $model->lang('FORM_DESCRIPTION_DESCR', 'CalendarModel')
			));
		$f[] = FormHelper::input(
			$model->lang('FORM_PRICE_CAPTION', 'CalendarModel'),
			'price',
			$this->getDatum('price'),
			array(
				'type' => 'number',
				'placeholder' => 'eg 40',
				'guidetext' => $model->lang('FORM_PRICE_DESCR', 'CalendarModel')
			)
		);
		$f[] = FormHelper::select($model->lang('FORM_CATEGORY_CAPTION', 'CalendarModel'), 'category', array('' => $model->lang('FORM_CATEGORY_EMPTY', 'CalendarModel')) + $this->args['categories'], $this->getDatum('category'), array('mandatory' => true));
		$f[] = FormHelper::checkbox($model->lang('FORM_SPECIALS_CAPTION', 'CalendarModel'), 'specials', $this->getDatum('group') == 'specials',
			array(
				'guidetext' => $model->lang('FORM_SPECIALS_DESCR', 'CalendarModel')
			));
		if ($user->getPower()) {
			$f[] = FormHelper::checkbox($model->lang('FORM_SIDEBAR_CAPTION', 'CalendarModel'), 'sidebar', $this->getDatum('sidebar'),
				array(
					'guidetext' => $model->lang('FORM_SIDEBAR_DESCR', 'CalendarModel')
				));
			$f[] = FormHelper::checkbox($model->lang('FORM_APPROVED_CAPTION', 'CalendarModel'), 'approved', $this->getDatum('approved'),
				array(
					'guidetext' => $model->lang('FORM_APPROVED_DESCR', 'CalendarModel')
				));
		}
		$live = $this->getDatum('live');
		if (!is_numeric($live)) $live = 1;
		$f[] = FormHelper::checkbox($model->lang('FORM_LIVE_CAPTION', 'CalendarModel'), 'live', $live,
			array(
				'guidetext' => $model->lang('FORM_LIVE_DESCR', 'CalendarModel')
			));
		
		$langCaption = $event_id ? 'FORM_EDIT_EVENT_CAPTION' : 'FORM_POST_EVENT_CAPTION';
		
		$f[] = FormHelper::submit($model->lang('FORM_SUBMIT_CAPTION', 'CalendarModel'));
		
		$content .= FormHelper::fieldset($model->lang($langCaption, 'CalendarModel'), $f);
		$content .= FormHelper::close();
		
		return $content;
	}

	public function processForm() {
		
	}
}
?>