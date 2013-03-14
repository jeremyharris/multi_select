<?php
/**
 * Selects controller class.
 *
 * @copyright     Copyright 2011, Jeremy Harris
 * @link          http://42pixels.com
 * @package       multi_select
 * @subpackage    multi_select.controllers
 */

/**
 * Includes
 */
App::uses('MultiSelectAppController', 'MultiSelect.Controller');

/**
 * Selects Controller
 *
 * @package       multi_select
 * @subpackage    multi_select.controllers
 */
class SelectsController extends MultiSelectAppController {

/**
 * The name of the controller
 *
 * @var string
 */
	var $name = 'Selects';

/**
 * Disable models for this controller
 *
 * @var array
 */
	var $uses = array();

/**
 * Components used by this controller
 *
 * @var array
 */
	var $components = array(
		'RequestHandler',
		'MultiSelect.MultiSelect'
	);

/**
 * Allows simple session storage and manipulation for MultiSelectHelper and MultiSelectComponent
 *
 * ### GET params
 * - 'value' The checkbox value. 'all' for all of the current page.
 * - 'selected' Checkbox selected value
 *
 * @access public
 */
	function session() {
		$ext = isset($this->request->params['ext']) ? $this->request->params['ext'] : null;
		if (!$this->RequestHandler->isAjax() || $ext != 'json') {
			throw new NotFoundException();
			return;
		}

		$this->request->query += array('value' => null, 'selected' => null);
		if ($this->request->query['value'] == null || $this->request->query['selected'] == null) {
			$data = array();
		} else {
			if ($this->request->query['value'] == 'all') {
				$action = $this->request->query['selected'] == 'true' ? 'selectAll' : 'deselectAll';
			} else {
				$action = $this->request->query['selected'] == 'true' ? 'merge' : 'delete';
			}
			$data = $this->MultiSelect->{$action}(array($this->request->query['value']));
		}

		$this->set('data', $data);
	}

}