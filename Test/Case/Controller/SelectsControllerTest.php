<?php
/**
 * SelectsControllerTest file
 *
 * @copyright     Copyright 2011, Jeremy Harris
 * @link          http://42pixels.com
 * @package       multi_select
 * @subpackage    multi_select.tests.cases.controllers
 */

/**
 * Includes
 */
App::import('Controller', array('MultiSelect.Selects'));
App::import('Component', array('RequestHandler'));

Mock::generatePartial('RequestHandlerComponent', 'MockRequestHandlerComponent', array('isAjax'));

class TestSelectsController extends SelectsController {
	var $autoRender = false;

	function redirect($url, $status = null, $exit = true) {
		$this->redirectUrl = $url;
	}

	function cakeError($type = '') {
		$this->cakeError = $type;
	}
}

class SelectsControllerTestCase extends CakeTestCase {

	function startTest() {
		$this->Selects =& new TestSelectsController();
		$this->Selects->constructClasses();
		$this->Selects->Component->initialize($this->Selects);
		$this->Selects->RequestHandler = new MockRequestHandlerComponent();
		$this->Selects->RequestHandler->ext = 'json';
	}

	function endTest() {
		unset($this->Selects);
		ClassRegistry::flush();
	}

	function _reset() {
		$this->Selects->params['url'] = $this->viewVars = array();
		$this->redirectUrl = $this->cakeError = null;
		$this->Selects->MultiSelect->startup();
	}

	function testSession() {
		// invalid request
		$this->_reset();
		$this->Selects->session();
		$this->assertTrue(empty($this->Selects->viewVars['data']));
		$this->assertEqual($this->Selects->cakeError, 'error404');

		$this->Selects->RequestHandler->setReturnValue('isAjax', true);

		// invalid request
		$this->_reset();
		$this->Selects->session();
		$this->assertTrue(empty($this->Selects->viewVars['data']));
		$this->assertEqual($this->Selects->cakeError, 'error404');

		// add a single value
		$this->_reset();
		$this->Selects->params['url'] = array(
			'value' => '1',
			'selected' => 'true'
		);
		$this->Selects->session();
		sort($this->Selects->viewVars['data']);
		$this->assertEqual($this->Selects->viewVars['data'], array(1));

		// add a single value
		$this->_reset();
		$this->Selects->params['url'] = array(
			'value' => '2',
			'selected' => 'true'
		);
		$this->Selects->session();
		sort($this->Selects->viewVars['data']);
		$this->assertEqual($this->Selects->viewVars['data'], array(1, 2));

		// remove a single value
		$this->_reset();
		$this->Selects->params['url'] = array(
			'value' => '1',
			'selected' => 'false'
		);
		$this->Selects->session();
		sort($this->Selects->viewVars['data']);
		$this->assertEqual($this->Selects->viewVars['data'], array(2));

		$this->Selects->Session->write('MultiSelect.'.$this->Selects->MultiSelect->_token.'.page', array(1,2,3,4,5));
		// add all of current page
		$this->_reset();
		$this->Selects->params['url'] = array(
			'value' => 'all',
			'selected' => 'true'
		);
		$this->Selects->session();
		sort($this->Selects->viewVars['data']);
		$this->assertEqual($this->Selects->viewVars['data'], array(1,2,3,4,5));

		// add another
		$this->_reset();
		$this->Selects->params['url'] = array(
			'value' => '6',
			'selected' => 'true'
		);
		$this->Selects->session();
		sort($this->Selects->viewVars['data']);
		$this->assertEqual($this->Selects->viewVars['data'], array(1,2,3,4,5,6));

		// remove page
		$this->_reset();
		$this->Selects->params['url'] = array(
			'value' => 'all',
			'selected' => 'false'
		);
		$this->Selects->session();
		sort($this->Selects->viewVars['data']);
		$this->assertEqual($this->Selects->viewVars['data'], array(6));
	}

}