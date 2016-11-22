<?php
class LengthValidator extends DataValidator
{
var $min_length = null;
var $max_length = null;

	function __construct(&$formObserver)
	{
	parent::__construct($formObserver);
	}
	
	function setMinLength($min_length)
	{
	$this->min_length = $min_length;
	}
	
	function setMaxLength($max_length)
	{
	$this->max_length = $max_length;
	}
	
	function validate($data_tag, $error_message)
	{
		if (!isset($this->max_length) && !isset($this->min_length))
		{
		// you being dumb
		}
		else if (!isset($this->max_length))
		{
			// check only against min_length
			if (strlen($this->formObserver->getDatum($data_tag)) < $this->min_length)
			{
			$this->notifyObserver($error_message);
			}
		}
		else if (!isset($this->min_length))
		{
			// check only against max_length
			if (strlen($this->formObserver->getDatum($data_tag)) > $this->max_length)
			{
			$this->notifyObserver($error_message);
			}
		}
		else if (isset($this->min_length) && isset($this->max_length))
		{
			// check against both max_length and min_length
			if (	strlen($this->formObserver->getDatum($data_tag)) > $this->max_length ||
					strlen($this->formObserver->getDatum($data_tag)) < $this->min_length
				)
			{
			$this->notifyObserver($error_message);
			}
		}
	}
}
?>