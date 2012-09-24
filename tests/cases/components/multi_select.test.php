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
App::import('Core', 'Component');
App::import('Component', array('Session', 'MultiSelect.MultiSelect', 'RequestHandler'));

/**
 * MultiSelectComponentTestController class
 *
 * @package       multi_select
 * @subpackage    multi_select.tests.cases.components
 */
class MultiSelectComponentTestController extends Controller {

/**
 * name property
 *
 * @var string
 * @access public
 */
	var $name = 'TheMultiSelectComponentTestController';

/**
 * uses property
 *
 * @var mixed null
 * @access public
 */
	var $uses = null;

/**
 * construct method
 *
 * @param array $params
 * @access private
 * @return void
 */
	function __construct($params = array()) {
		foreach ($params as $key => $val) {
			$this->{$key} = $val;
		}
		parent::__construct();
	}
}

Mock::generatePartial('RequestHandlerComponent', 'MockMultiSelectComponentRequestHandlerComponent', array('isPost'));

/**
 * MultiSelectComponentTest class
 *
 * @package       multi_select
 * @subpackage    multi_select.tests.cases.components
 */
class MultiSelectComponentTest extends CakeTestCase {
	
	function startCase() {
		$this->Controller =& new MultiSelectComponentTestController(array('components' => array('RequestHandler')));
		$this->Controller->constructClasses();
		$this->View =& new View($this->Controller);
		$this->MultiSelect =& new MultiSelectComponent($this->Controller);
		$this->MultiSelect->initialize($this->Controller);
		$this->MultiSelect->Session =& new SessionComponent();
		$this->MultiSelect->RequestHandler =& new MockMultiSelectComponentRequestHandlerComponent();
		$this->MultiSelect->RequestHandler->initialize($this->Controller);
	}

	function startTest() {
		unset($this->Controller->params['named']['mstoken']);
		$this->MultiSelect->startup();
	}
	
	function testNewSessionOnPost() {
		$this->MultiSelect->RequestHandler->setReturnValueAt(0, 'isPost', false);
		$this->Controller->params['named']['mstoken'] = $this->MultiSelect->_token;
		$this->MultiSelect->Session->write('MultiSelect.'.$this->MultiSelect->_token.'.selected', array(1));
		$this->MultiSelect->startup();
		$result = $this->MultiSelect->Session->read('MultiSelect.'.$this->MultiSelect->_token);
		unset($result['created']);
		$expected = array(
			'selected' => array(1),
			'search' => array(),
			'page' => array()
		);
		$this->assertEqual($result, $expected);
		
		$this->MultiSelect->RequestHandler->setReturnValueAt(1, 'isPost', false);
		$this->MultiSelect->startup();
		$result = $this->MultiSelect->Session->read('MultiSelect.'.$this->MultiSelect->_token.'.selected');
		$expected = array(1);
		$this->assertEqual($result, $expected);
		
		$this->MultiSelect->RequestHandler->setReturnValueAt(2, 'isPost', true);
		$this->MultiSelect->startup();
		$result = $this->MultiSelect->Session->read('MultiSelect.'.$this->MultiSelect->_token.'.selected');
		$expected = array();
		$this->assertEqual($result, $expected);
	}
	
	function testExpiration() {
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
		$this->MultiSelect->startup();
		
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
		$this->assertTrue($this->MultiSelect->Session->check('MultiSelect'));
		$this->assertIsA($this->MultiSelect->_token, 'string');
		$now = time();
		$result = $this->MultiSelect->Session->read('MultiSelect.'.$this->MultiSelect->_token);
		$expected = array(
			'selected' => array(),
			'search' => array(),
			'page' => array(),
			'created' => $now
		);
		$this->assertEqual($result, $expected);

		$this->Controller->params['named']['mstoken'] = $this->MultiSelect->_token;
		$this->MultiSelect->Session->write('MultiSelect.'.$this->MultiSelect->_token.'.page', array(1));
		$this->MultiSelect->startup();
		$result = $this->MultiSelect->Session->read('MultiSelect.'.$this->MultiSelect->_token);
		$expected = array(
			'selected' => array(),
			'search' => array(),
			'page' => array(1),
			'created' => $now
		);
		$this->assertEqual($result, $expected);
	}

	function testCheck() {
		$this->assertTrue($this->MultiSelect->check());
		$this->assertTrue($this->MultiSelect->check($this->MultiSelect->_token));
	}

	function testSaveSearch() {
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
		$expected = array('1','2','3');
		$this->MultiSelect->_save($expected);
		$this->assertEqual($expected, $this->MultiSelect->Session->read('MultiSelect.'.$this->MultiSelect->_token.'.selected'));
	}

	function testGetSearch() {
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

	function test_get() {
		$expected = array();
		$result = $this->MultiSelect->_get();
		$this->assertEqual($expected, $result);

		$expected = array('1','2','3');
		$this->MultiSelect->merge(array('1','2','3'));
		$result = $this->MultiSelect->_get();
		$this->assertEqual($expected, $result);
	}

	function test_save() {
		$expected = array('1','2','3');
		$this->assertTrue($this->MultiSelect->_save($expected));
		$this->assertEqual($expected, $this->MultiSelect->Session->read('MultiSelect.'.$this->MultiSelect->_token.'.selected'));
	}

	function testMerge() {
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
		$this->MultiSelect->usePages = true;
		$this->MultiSelect->merge(array('1','2','3'));
		$this->MultiSelect->Session->write('MultiSelect.'.$this->MultiSelect->_token.'.page', array('4','5','6'));
		$expected = array('1','2','3','4','5','6');
		$result = $this->MultiSelect->selectAll();
		$this->assertEqual($result, $expected);
	}

	function testdeselectAll() {
		$this->MultiSelect->usePages = true;
		$this->MultiSelect->merge(array('1','2','3','4','5','6'));
		$this->MultiSelect->Session->write('MultiSelect.'.$this->MultiSelect->_token.'.page', array('4','5','6'));
		$expected = array('1','2','3');
		$result = $this->MultiSelect->deselectAll();
		$this->assertEqual($result, $expected);
	}

}