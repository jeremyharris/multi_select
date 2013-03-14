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
App::uses('SelectsController', 'MultiSelect.Controller');
App::uses('RequestHandlerComponent', 'Controller/Component');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');

class SelectsControllerTestCase extends ControllerTestCase {

	public function setUp() {
		Router::parseExtensions('json');
	}

	public function tearDown() {
		unset($this->Selects);
		CakeSession::destroy();
		ClassRegistry::flush();
	}

	public function generateMock($isAjax = null) {
		$this->Selects = $this->generate('MultiSelect.Selects', array(
			'components' => array(
				'RequestHandler' => array('isAjax', 'isPost')
			)
		));
		if (!is_null($isAjax)) {
			$this->Selects->RequestHandler
				->expects($this->any())
				->method('isAjax')
				->will($this->returnValue($isAjax));
		}
		// persist across requests
		$this->Selects->RequestHandler
			->expects($this->any())
			->method('isPost')
			->will($this->returnValue(false));
		return $this->Selects;
	}

	public function testNotFound() {
		$this->generateMock(false);

		// invalid request due to not ajax
		$this->expectException('NotFoundException');
		$this->testAction('/multi_select/selects/session.json');
		$this->assertTrue(empty($this->vars['data']));
	}

	public function testNotFoundWithAjax() {
		$this->generateMock(true);

		// invalid request due to bad ext
		$this->expectException('NotFoundException');
		$this->testAction('/multi_select/selects/session');
		$this->assertTrue(empty($this->vars['data']));
	}

	public function testSession() {
		CakeSession::write('MultiSelect.testSession.usePages', true);

		// add a single value
		$this->generateMock(true);
		$this->testAction('/multi_select/selects/session/mstoken:testSession.json?value=1&selected=true');
		sort($this->vars['data']);
		$this->assertEqual($this->vars['data'], array(1));

		// add another value
		$this->generateMock(true);
		$this->testAction('/multi_select/selects/session/mstoken:testSession.json?value=2&selected=true');
		sort($this->vars['data']);
		$this->assertEqual($this->vars['data'], array(1, 2));

		// add another value
		$this->generateMock(true);
		$this->testAction('/multi_select/selects/session/mstoken:testSession.json?value=2&selected=false');
		sort($this->vars['data']);
		$this->assertEqual($this->vars['data'], array(1));

		// make a page
		$this->Selects->Session->write('MultiSelect.testSession.page', array(1,2,3,4,5));
		// add the current page
		$this->generateMock(true);
		$this->testAction('/multi_select/selects/session/mstoken:testSession.json?value=all&selected=true');
		sort($this->vars['data']);
		$this->assertEqual($this->vars['data'], array(1,2,3,4,5));

		// add another single value
		$this->generateMock(true);
		$this->testAction('/multi_select/selects/session/mstoken:testSession.json?value=6&selected=true');
		sort($this->vars['data']);
		$this->assertEqual($this->vars['data'], array(1,2,3,4,5,6));

		// deselect page
		$this->generateMock(true);
		$this->testAction('/multi_select/selects/session/mstoken:testSession.json?value=all&selected=false');
		sort($this->vars['data']);
		$this->assertEqual($this->vars['data'], array(6));
	}

}