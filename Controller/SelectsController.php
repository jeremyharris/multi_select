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
		if (!$this->RequestHandler->isAjax() || $this->RequestHandler->ext != 'json') {
			$this->cakeError('error404');
			return;
		}

		$this->request->params['url'] += array('value' => null, 'selected' => null);

		if ($this->request->params['url']['value'] == null || $this->request->params['url']['selected'] == null) {
			$data = array();
		} else {
			if ($this->request->params['url']['value'] == 'all') {
				$action = $this->request->params['url']['selected'] == 'true' ? 'selectAll' : 'deselectAll';
			} else {
				$action = $this->request->params['url']['selected'] == 'true' ? 'merge' : 'delete';
			}
			$data = $this->MultiSelect->{$action}(array($this->request->params['url']['value']));
		}

		$this->set('data', $data);
	}

}