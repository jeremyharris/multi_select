<?php
/**
 * MultiSelectComponentTest file
 *
 * @copyright     Copyright 2011, Jeremy Harris
 * @link          http://42pixels.com
 * @package       multi_select
 * @subpackage    multi_select.tests.cases.components
 */

/**
 * Includes
 */
App::uses('SelectsController', 'MultiSelect.Controller');
App::uses('MultiSelectComponent', 'MultiSelect.Controller/Component');
App::uses('SessionComponent', 'Controller/Component');
App::uses('RequestHandlerComponent', 'Controller/Component');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');

/**
 * MultiSelectComponentTest class
 *
 * @package       multi_select
 * @subpackage    multi_select.tests.cases.components
 */
class MultiSelectComponentTest extends CakeTestCase {

	function setUp() {
		$request = new CakeRequest('selects/index');
		$response = new CakeResponse();
		$this->Controller = new SelectsController($request, $response);
		$this->Controller->constructClasses();
		$this->MultiSelect = $this->Controller->Components->MultiSelect;

		$RequestHandler = $this->getMockBuilder('RequestHandlerComponent')
			->setMethods(array('isPost'))
			->setConstructorArgs(array($this->Controller->Components))
			->getMock();

		$this->MultiSelect->RequestHandler = $RequestHandler;
	}

	function tearDown() {
		unset($this->Controller);
	}

	function startTest($method) {
		$this->Controller->request->params['named'] = array();
	}

	function testPostPersist() {
		$this->MultiSelect->RequestHandler
			->expects($this->any())
			->method('isPost')
			->will($this->returnValue(true));
		$this->Controller->request->params['named']['mspersist'] = 1;
		$this->Controller->request->params['named']['mstoken'] = $this->MultiSelect->_token;
		$this->MultiSelect->Session->write('MultiSelect.'.$this->MultiSelect->_token.'.selected', array(1));
		$this->MultiSelect->startup($this->Controller);
		$result = $this->MultiSelect->Session->read('MultiSelect.'.$this->MultiSelect->_token.'.selected');
		$expected = array(1);
		$this->assertEqual($result, $expected);
	}

	function testNewSessionOnPost() {
		$this->MultiSelect->RequestHandler
			->expects($this->any())
			->method('isPost')
			->will($this->onConsecutiveCalls(false, true));

		$this->Controller->request->params['named']['mstoken'] = 'testtoken';
		$this->MultiSelect->Session->write('MultiSelect.testtoken.selected', array(1));
		$this->MultiSelect->startup($this->Controller);
		$result = $this->MultiSelect->Session->read('MultiSelect.'.$this->MultiSelect->_token);
		$expected = array(
			'selected' => array(1)
		);
		$this->assertEqual($result, $expected);

		$this->MultiSelect->startup($this->Controller);
		$result = $this->MultiSelect->Session->read('MultiSelect.'.$this->MultiSelect->_token.'.selected');
		$expected = array();
		$this->assertEqual($result, $expected);
	}

	function testExpiration() {
		// persist so we don't create new tokens
		$this->Controller->request->params['named']['mspersist'] = 1;
		$this->Controller->request->params['named']['mstoken'] = 'test1';

		$tokens = array(
			'test1' => array(
				'selected' => array(),
				'search' => array(),
				'page' => array(1),
				'created' => strtotime('yesterday')
			),
			'test2' => array(
				'selected' => array(),
				'search' => array(),
				'page' => array(2),
				'created' => strtotime('now')
			),
			'test3' => array(
				'selected' => array(),
				'search' => array(),
				'page' => array(3),
				'created' => strtotime('+1 day')
			)
		);
		$this->MultiSelect->Session->write('MultiSelect', $tokens);
		$this->MultiSelect->startup($this->Controller);

		$results = $this->MultiSelect->Session->read('MultiSelect');
		$expected = array(
			'test2' => array(
				'selected' => array(),
				'search' => array(),
				'page' => array(2),
				'created' => strtotime('now')
			),
			'test3' => array(
				'selected' => array(),
				'search' => array(),
				'page' => array(3),
				'created' => strtotime('+1 day')
			)
		);
		$this->assertEqual($results, $expected);
	}

	function testStartup() {
		$this->MultiSelect->startup($this->Controller);

		$this->assertTrue($this->MultiSelect->Session->check('MultiSelect'));
		$this->assertTrue(!is_null($this->MultiSelect->_token));
		$now = time();
		$result = $this->MultiSelect->Session->read('MultiSelect.'.$this->MultiSelect->_token);
		$expected = array(
			'selected' => array(),
			'search' => array(),
			'page' => array(),
			'usePages' => false,
			'all' => false,
			'created' => $now
		);
		$this->assertEqual($result, $expected);

		$this->Controller->request->params['named']['mstoken'] = $this->MultiSelect->_token;
		$this->MultiSelect->Session->write('MultiSelect.'.$this->MultiSelect->_token.'.page', array(1));
		$this->MultiSelect->Session->write('MultiSelect.'.$this->MultiSelect->_token.'.usePages', true);
		$this->MultiSelect->startup($this->Controller);
		$result = $this->MultiSelect->Session->read('MultiSelect.'.$this->MultiSelect->_token);
		$expected = array(
			'selected' => array(),
			'search' => array(),
			'page' => array(1),
			'usePages' => true,
			'all' => false,
			'created' => $now
		);
		$this->assertEqual($result, $expected);

		$this->assertTrue($this->MultiSelect->usePages);
	}

	function testCheck() {
		$this->MultiSelect->startup($this->Controller);
		$this->assertTrue($this->MultiSelect->check());
		$this->assertTrue($this->MultiSelect->check($this->MultiSelect->_token));
	}

	function testSaveSearch() {
		$this->MultiSelect->startup($this->Controller);

		$search = array(
			'conditions' => array(
				'User.id' => array(1,2,3)
			),
			'limit' => 2,
			'order' => 'User.username ASC'
		);

		$this->MultiSelect->saveSearch($search);

		$result = $this->MultiSelect->Session->read('MultiSelect.'.$this->MultiSelect->_token.'.search');
		$expected = array(
			'conditions' => array(
				'User.id' => array(1,2,3)
			),
			'order' => 'User.username ASC'
		);
		$this->assertEqual($result, $expected);
		$this->assertFalse($this->MultiSelect->saveSearch('notanarray'));
	}

	function testGetSelected() {
		$this->MultiSelect->startup($this->Controller);

		$expected = array('1','2','3');
		$this->MultiSelect->_save($expected);
		$this->assertEqual($expected, $this->MultiSelect->Session->read('MultiSelect.'.$this->MultiSelect->_token.'.selected'));
	}

	function testGetSearch() {
		$this->MultiSelect->startup($this->Controller);

		$search = array(
			'conditions' => array(
				'User.id' => array(1,2,3)
			),
			'limit' => 2,
			'order' => 'User.username ASC'
		);

		$this->MultiSelect->saveSearch($search);

		$expected = array(
			'conditions' => array(
				'User.id' => array(1,2,3)
			),
			'order' => 'User.username ASC'
		);
		$this->assertEqual($this->MultiSelect->getSearch(), $expected);
	}

	function testGet() {
		$this->MultiSelect->startup($this->Controller);

		$expected = array();
		$result = $this->MultiSelect->_get();
		$this->assertEqual($expected, $result);

		$expected = array('1','2','3');
		$this->MultiSelect->merge(array('1','2','3'));
		$result = $this->MultiSelect->_get();
		$this->assertEqual($expected, $result);

		$result = $this->MultiSelect->_get('NonExistentKey');
		$expected = array();
		$this->assertIdentical($result, $expected);
	}

	function testSave() {
		$this->MultiSelect->startup($this->Controller);

		$expected = array('1','2','3');
		$this->assertTrue($this->MultiSelect->_save($expected));
		$this->assertEqual($expected, $this->MultiSelect->Session->read('MultiSelect.'.$this->MultiSelect->_token.'.selected'));
	}

	function testMerge() {
		$this->MultiSelect->startup($this->Controller);

		$expected = array();
		$result = $this->MultiSelect->merge(array());
		$this->assertEqual($expected, $result);

		$expected = array('1');
		$result = $this->MultiSelect->merge(array('1'));
		$this->assertEqual($expected, $result);

		$expected = array('1', '2', '3');
		$result = $this->MultiSelect->merge(array('2', '3'));
		$this->assertEqual($expected, $result);

		$expected = array('1', '2', '3');
		$result = $this->MultiSelect->merge(array('2'));
		$this->assertEqual($expected, $result);
	}

	function testDelete() {
		$this->MultiSelect->startup($this->Controller);

		$this->MultiSelect->merge(array('1','2','3','4'));

		$expected = array('1','2','3','4');
		$result = $this->MultiSelect->delete(array());
		$this->assertEqual($expected, $result);

		$expected = array('2','3','4');
		$result = $this->MultiSelect->delete(array('1'));
		$this->assertEqual($expected, $result);

		$expected = array('2');
		$result = $this->MultiSelect->delete(array('3', '4'));
		$this->assertEqual($expected, $result);
	}

	function testSelectAll() {
		$this->MultiSelect->startup($this->Controller);

		$this->MultiSelect->usePages = true;
		$this->MultiSelect->merge(array('1','2','3'));
		$this->MultiSelect->Session->write('MultiSelect.'.$this->MultiSelect->_token.'.page', array('4','5','6'));
		$expected = array('1','2','3','4','5','6');
		$result = $this->MultiSelect->selectAll();
		$this->assertEqual($result, $expected);

		$this->MultiSelect->usePages = false;
		$result = $this->MultiSelect->selectAll();
		$this->assertTrue($result);

		$results = $this->MultiSelect->getSelected();
		$expected = 'all';
		$this->assertEqual($results, $expected);
	}

	function testdeselectAll() {
		$this->MultiSelect->startup($this->Controller);

		$this->MultiSelect->usePages = true;
		$this->MultiSelect->merge(array('1','2','3','4','5','6'));
		$this->MultiSelect->Session->write('MultiSelect.'.$this->MultiSelect->_token.'.page', array('4','5','6'));
		$expected = array('1','2','3');
		$result = $this->MultiSelect->deselectAll();
		$this->assertEqual($result, $expected);

		$this->MultiSelect->usePages = false;
		$result = $this->MultiSelect->deselectAll();
		$this->assertTrue($result);

		$results = $this->MultiSelect->getSelected();
		$expected = array();
		$this->assertEqual($results, $expected);
	}

}