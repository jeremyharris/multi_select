<?php
/**
 * Multi select helper class.
 *
 * @copyright     Copyright 2011, Jeremy Harris
 * @link          http://42pixels.com
 * @package       multi_select
 * @subpackage    multi_select.views.helpers
 */

App::uses('AppHelper', 'View/Helper');
App::uses('CakeSession', 'Model/Datasource');

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
	var $helpers = array('Form', 'Js');

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
 * Is "check all" active?
 *
 * @var string
 */
	var $all = false;

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
		if (!isset($this->request->params['named']['mstoken']) || !CakeSession::check('MultiSelect')) {
			trigger_error('MultiSelectHelper::create() :: Missing MultiSelect key in session or MultiSelect token. Make sure to include the MultiSelectComponent in your controller file.', E_USER_WARNING);
		}

		$this->token = $this->request->params['named']['mstoken'];

		$this->all = CakeSession::read('MultiSelect.'.$this->token.'.all');

		// get cache and store
		$this->selected = CakeSession::read('MultiSelect.'.$this->token.'.selected');
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
		$uid = String::uuid();

		$defaultOptions = array(
			'hiddenField' => false,
			'class' => '',
			'data-multiselect-token' => $this->token
		);

		$options = array_merge($defaultOptions, $options);
		$options['id'] = $uid;
		$options['value'] = $value;
		$options['class'] .= ' multi-select-box';

		if (in_array($value, $this->selected)) {
			$options['checked'] = 'checked';
		}

		if ($value !== 'all') {
			$this->page[] = $value;
			if ($this->all) {
				$options['disabled'] = true;
			}
		} else {
			if ($this->all) {
				$options['checked'] = 'checked';
			}
		}

		$output = $this->Form->checkbox('', $options);

		return $output;
	}

/**
 * Buffers JavaScript to tie it all together
 *
 * @access public
 */
	function end() {
		CakeSession::write('MultiSelect.'.$this->token.'.page', $this->page);

		$usePages = CakeSession::read('MultiSelect.'.$this->token.'.usePages');

		$url = Router::url(array(
			'controller' => 'selects',
			'action' => 'session',
			'plugin' => 'multi_select',
			'mstoken' => $this->token,
			'ext' => 'json'
		));

		$this->Js->get(".multi-select-box[data-multiselect-token=$this->token]");
		$each = <<<JS
this.disabled = false;
if (autoCheck) {
	this.setAttribute('checked', 'checked');
	this.checked = true;
} else {
	this.removeAttribute('checked');
	this.checked = false;
}
if (this.value !== 'all' && shouldDisable) {
	if (autoCheck) {
		this.checked = false;
		this.disabled = true;
	} else {
		this.disabled = false;
	}
}
if (document.createEventObject){
	this.fireEvent('onchange', document.createEventObject());
} else {
	var evt = document.createEvent("HTMLEvents")
	evt.initEvent('change', true, true);
	this.dispatchEvent(evt);
};
JS;
		$each = $this->Js->each($each, array('buffer' => false));
		$disable = $usePages ? 'false' : 'true';
		$click = <<<JS
if (this.value === 'all') {
	var autoCheck = this.checked;
	var shouldDisable = $disable;
	$each
}
JS;
		$request = $this->Js->request($url, array(
			'dataExpression' => true,
			'data' => '{value:this.value,selected:this.checked}',
			'method' => 'get',
			'dataType' => 'json'
		));
		$this->Js->event('click', $request.$click, array('stop' => false));
	}
}