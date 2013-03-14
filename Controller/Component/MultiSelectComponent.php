<?php
/**
 * Multi select component class.
 *
 * @copyright     Copyright 2011, Jeremy Harris
 * @link          http://42pixels.com
 * @package       multi_select
 * @subpackage    multi_select.controllers.components
 */

/**
 * Includes
 */
App::uses('Component', 'Controller');

/**
 * MultiSelect Component
 *
 * Allows for storing multiple ids of a search to return later.
 *
 * In your controller, use MultiSelectComponent::saveSearch() to save your search. Pass the conditions,
 * contain - anything you would use in a Model::find() or Controller::paginate(). Use the MultiSelectHelper
 * to build the checkboxes.
 *
 * When an action passes the key to your new controller action, use MultiSelectComponent::getSearch() to get
 * your saved search data, then use MultiSelectComponent::getSelected() and modify the search parameters
 * appropriately. MultiSelectComponent::getSelected() returns `all` if the check all checkbox was selected.
 *
 * @package       multi_select
 * @subpackage    multi_select.controllers.components
 */
class MultiSelectComponent extends Component {

/**
 * Components the MultiSelectComponent uses
 *
 * @var array
 */
	public $components = array(
		'Session',
		'RequestHandler'
	);

/**
 * Current token
 *
 * @var string
 */
	public $_token = null;

/**
 * Changes the behavior of the 'check all' box.
 *
 * @var boolean
 * @see README
 */
	public $usePages = false;

/**
 * Creates session keys and removes expired ones
 *
 * @todo check for pagination in url instead of layout
 */
	public function startup($controller) {
		if (empty($controller->request->params['named'])) {
			$controller->request->params['named'] = array();
		}

		if ($this->Session->check('MultiSelect')) {
			$currentTokens = $this->Session->read('MultiSelect');
			$expires = strtotime('-10 minutes');
			foreach ($currentTokens as $token => $values) {
				if (isset($values['created']) && $values['created'] < $expires) {
					$this->Session->delete('MultiSelect.'.$token);
				}
			}
		}

		$newRequest = !isset($controller->request->params['named']['mstoken']) || $this->RequestHandler->isPost();

		if ($newRequest && !isset($controller->request->params['named']['mspersist'])) {
			$this->_token = uniqid();
			$controller->request->params['named']['mstoken'] = $this->_token;
			$success = $this->Session->write('MultiSelect.'.$this->_token, array(
				'selected' => array(),
				'search' => array(),
				'page' => array(),
				'usePages' => $this->usePages,
				'created' => time(),
				'all' => false
			));
		} else {
			$this->_token = $controller->request->params['named']['mstoken'];
			$this->usePages = $this->Session->read('MultiSelect.'.$this->_token.'.usePages');
		}
	}

/**
 * Stores search data for later use
 *
 * @param array $search The search data
 * @return boolean Save success
 */
	public function saveSearch($search = array()) {
		if (!is_array($search)) {
			return false;
		}

		unset($search['limit']);
		return $this->Session->write('MultiSelect.'.$this->_token.'.search', $search);
	}

/**
 * Retrieves search data
 *
 * @param string $uid The token to get, or null for current
 * @return array Search data that was previously saved
 */
	public function getSearch($uid = null) {
		if (!$uid) {
			$uid = $this->_token;
		}
		return $this->Session->read('MultiSelect.'.$uid.'.search');
	}

/**
 * Retrieves selected ids
 *
 * @param string $uid The token to get, or null for current
 * @return mixed Array of ids, or `all`
 */
	public function getSelected($uid = null) {
		if (!$uid) {
			$uid = $this->_token;
		}
		if ($this->Session->read('MultiSelect.'.$uid.'.all')) {
			return 'all';
		} else {
			return $this->Session->read('MultiSelect.'.$uid.'.selected');
		}
	}

/**
 * Checks to see if MultiSelect session is set, or if an id is a MultiSelect id
 *
 * @param $uid The id to check, if any
 * @return boolean Exists?
 */
	public function check($uid = null) {
		if (!$uid) {
			$uid = $this->_token;
		}
		return $this->Session->check('MultiSelect.'.$uid);
	}

/**
 * Merges ids with current selected ids
 *
 * @param array $data Array of ids
 * @return array New data
 */
	public function merge($data = array()) {
		$cache = array_values(array_unique(array_merge($this->_get(), $data)));
		$this->_save($cache);
		return $cache;
	}

/**
 * Removes ids from current selected ids
 *
 * @param array $data Array of ids
 * @return array New data
 */
	public function delete($data = array()) {
		$cache = array_values(array_diff($this->_get(), $data));
		$this->_save($cache);
		return $cache;
	}

/**
 * Saves current page into selected
 *
 * @return boolean Set success
 */
	public function selectAll() {
		if ($this->usePages) {
			return $this->merge($this->Session->read('MultiSelect.'.$this->_token.'.page'));
		} else {
			$this->_save(array());
			return $this->Session->write('MultiSelect.'.$this->_token.'.all', true);
		}
	}

/**
 * Removes current page from selected
 *
 * @return boolean Reset success
 */
	public function deselectAll() {
		if ($this->usePages) {
			return $this->delete($this->Session->read('MultiSelect.'.$this->_token.'.page'));
		} else {
			$this->_save(array());
			return $this->Session->write('MultiSelect.'.$this->_token.'.all', false);
		}
	}

/**
 * Saves ids in the session key
 *
 * @param array $data Array of ids
 * @return boolean Write success
 */
	public function _save($data = array()) {
		return $this->Session->write('MultiSelect.'.$this->_token.'.selected', $data);
	}

/**
 * Gets ids from the session key
 *
 * @return array Array of ids
 */
	public function _get($uid = null) {
		if (!$uid) {
			$uid = $this->_token;
		}
		return (array)$this->Session->read('MultiSelect.'.$uid.'.selected');
	}
}