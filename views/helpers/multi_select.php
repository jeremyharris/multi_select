<?php
/**
 * Multi select helper class.
 *
 * @copyright     Copyright 2011, Jeremy Harris
 * @link          http://42pixels.com
 * @package       multi_select
 * @subpackage    multi_select.views.helpers
 */

/**
 * MultiSelect Helper
 *
 * Allows creation of multi select lists and actions
 *
 * Creates checkboxes and works with MultiSelectComponent and the PaginatorHelper to allow for
 * persisted selections across Ajax paginated pages. Pass MultiSelectComponent::cache to controllers
 * and read with MultiSelectComponent.
 *
 * @package       multi_select
 * @subpackage    multi_select.views.helpers
 */
class MultiSelectHelper extends AppHelper {

/**
 * Additional helpers
 *
 * @var array
 */ 
	var $helpers = array('Session', 'Form', 'Js');

/**
 * Ids that should be selected (set automatically by MultiSelectComponent)
 *
 * @var array
 */ 	
	var $selected = array();

/**
 * Current page's ids
 *
 * @var string
 */
	var $page = array();

/**
 * The token
 *
 * @var string
 */
	var $token = null;

/**
 * Initializes the Helper
 *
 * @access public
 */ 
	function create() {
		// check for session key
		if (!isset($this->params['named']['mstoken']) || !$this->Session->check('MultiSelect')) {
			trigger_error('MultiSelectHelper::create() :: Missing MultiSelect key in session or MultiSelect token. Make sure to include the MultiSelectComponent in your controller file.', E_USER_WARNING);
		}
		
		$this->token = $this->params['named']['mstoken'];

		// get cache and store
		$this->selected = $this->Session->read('MultiSelect.'.$this->token.'.selected');
	}

/**
 * Creates a checkbox
 *
 * @param mixed $value The id to save, or `all` for a checkbox that selects all
 * @param array $options Array of options to merge with the checkbox
 * @return null|string The generated checkbox widget
 * @access public
 */ 
	function checkbox($value = '', $options = array()) {
		if (!is_numeric($value) && is_string($value) && $value != 'all') {
			return null;
		}

		$uid = String::uuid();
		
		$defaultOptions = array(
			'hiddenField' => false,
			'class' => 'multi-select-box'
		);
		
		$options = array_merge($defaultOptions, $options);
		$options['id'] = $uid;
		$options['value'] = $value;
		
		if (in_array($value, $this->selected)) {
			$options['checked'] = 'checked';
		}

		$output = $this->Form->checkbox('', $options);
		
		if (is_numeric($value)) {
			$this->page[] = $value;
		}

		return $output;
	}
	
/**
 * Buffers JavaScript to tie it all together
 *
 * @access public
 */
	function end() {
		App::import('Component', 'Session');
		$Session = new SessionComponent();
		$Session->write('MultiSelect.'.$this->token.'.page', $this->page);
		
		$url = Router::url(array(
			'controller' => 'selects',
			'action' => 'session',
			'plugin' => 'multi_select',
			'mstoken' => $this->token,
			'ext' => 'json'
		));
		
		$this->Js->get('.multi-select-box');
		$each = <<<JS
checked ? this.setAttribute('checked', 'checked') : this.removeAttribute('checked');
if (document.createEventObject){
	this.fireEvent('onchange', document.createEventObject())
} else {
	var evt = document.createEvent("HTMLEvents")
	evt.initEvent('change', true, true);
	this.dispatchEvent(evt);
};
JS;
		$each = $this->Js->each($each, array('buffer' => false));
		$clickall = " if (this.value == 'all') {var checked = this.checked; $each}";
		$request = $this->Js->request($url, array(
			'dataExpression' => true,
			'data' => '{value:this.value,selected:this.checked}',
			'method' => 'get',
			'dataType' => 'json'
		));
		$this->Js->get('.multi-select-box');
		$this->Js->event('click', $request.$clickall, array('stop' => false));
	}
}