<?php
/**
 * MultiSelectHelperTest file
 *
 * @copyright     Copyright 2011, Jeremy Harris
 * @link          http://42pixels.com
 * @package       multi_select
 * @subpackage    multi_select.tests.cases.helpers
 */

/**
 * Includes
 */
App::uses('MultiSelectHelper', 'MultiSelect.View/Helper');
App::uses('SelectsController', 'MultiSelect.Controller');
App::uses('PrototypeEngineHelper', 'View/Helper');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');

/**
 * TheMultiSelectTestController class
 *
 * @package       multi_select
 * @subpackage    multi_select.tests.cases.helpers
 */
class TheMultiSelectTestController extends SelectsController {

/**
 * Name
 *
 * @var string
 * @access public
 */
	var $name = 'TheMultiSelectTest';

/**
 * No model
 *
 * @var string
 */
	var $uses = null;

/**
 * Components
 *
 * @var array
 * @access public
 */
	var $components = array(
		'Session',
		'RequestHandler',
		'MultiSelect.MultiSelect'
	);
}

/**
 * MultiSelectTest class
 *
 * @package       multi_select
 * @subpackage    multi_select.tests.cases.helpers
 */
class MultiSelectTest extends CakeTestCase {

	function setUp() {
		$request = new CakeRequest('selects/index');
		$response = new CakeResponse();
		$this->Controller = new SelectsController($request, $response);
		$this->Controller->constructClasses();
		$this->Controller->Components->trigger('startup', array($this->Controller));
		$this->View = new View($this->Controller);
		$this->MultiSelect = new MultiSelectHelper($this->View);
	}

	function startTest() {
		$this->MultiSelect->request->params['named']['mstoken'] = $this->Controller->MultiSelect->_token;
		$this->MultiSelect->create();
	}

	function testCreate() {
		$this->Controller->Session->delete('MultiSelect');

		$this->expectException('CakeException');
		$this->MultiSelect->create();

		$this->Controller->Session->write('MultiSelect', array(
			'selected' => array(),
			'search' => array(),
			'page' => array()
		));
		$this->MultiSelect->create();
		$this->assertNoErrors();

		$this->MultiSelect->params['named']['mstoken'] = 'test';
		$this->Session->write('MultiSelect', array(
			'test' => array(
				'selected' => array(1, 2, 3),
				'search' => array(),
				'page' => array(),
				'all' => true
			)
		));
		$this->MultiSelect->create();

		$results = $this->MultiSelect->selected;
		$expected = array(1, 2, 3);
		$this->assertEqual($results, $expected);

		$results = $this->MultiSelect->all;
		$this->assertTrue($results);
	}

	function testCheckedAllBox() {
		$uidReg = "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}";

		$this->MultiSelect->selected = array();
		$this->MultiSelect->all = true;

		$result = $this->MultiSelect->checkbox('all');
		$expected = array(
			'input' => array(
				'type' => 'checkbox',
				'name' => 'data[]',
				'value' => 'all',
				'id' => 'preg:/'.$uidReg.'/',
				'class' => ' multi-select-box',
				'checked' => 'checked',
				'data-multiselect-token' => 'preg:/[a-z0-9]+/'
			)
		);
		$this->assertTags($result, $expected);

		$result = $this->MultiSelect->checkbox(1);
		$expected = array(
			'input' => array(
				'type' => 'checkbox',
				'name' => 'data[]',
				'value' => 1,
				'id' => 'preg:/'.$uidReg.'/',
				'class' => ' multi-select-box',
				'disabled' => 'disabled',
				'data-multiselect-token' => 'preg:/[a-z0-9]+/'
			)
		);
		$this->assertTags($result, $expected);
	}

	function testCheckbox() {
		$this->MultiSelect->selected = array(1);
		$tokenReg = '(.){13}';
		$uidReg = "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}";

		$result = $this->MultiSelect->checkbox(2);
		$expected = array(
			'input' => array(
				'type' => 'checkbox',
				'name' => 'data[]',
				'value' => 2,
				'id' => 'preg:/'.$uidReg.'/',
				'data-multiselect-token' => 'preg:/'.$tokenReg.'/',
				'class' => ' multi-select-box'
			)
		);
		$this->assertTags($result, $expected);

		$result = $this->MultiSelect->checkbox('2');
		$expected = array(
			'input' => array(
				'type' => 'checkbox',
				'name' => 'data[]',
				'value' => 2,
				'id' => 'preg:/'.$uidReg.'/',
				'data-multiselect-token' => 'preg:/'.$tokenReg.'/',
				'class' => ' multi-select-box'
			)
		);
		$this->assertTags($result, $expected);

		$result = $this->MultiSelect->checkbox(2, array('id' => 'anything'));
		$expected = array(
			'input' => array(
				'type' => 'checkbox',
				'name' => 'data[]',
				'value' => 2,
				'id' => 'preg:/'.$uidReg.'/',
				'data-multiselect-token' => 'preg:/'.$tokenReg.'/',
				'class' => ' multi-select-box'
			)
		);
		$this->assertTags($result, $expected);

		$result = $this->MultiSelect->checkbox(2, array('value' => 'anything'));
		$expected = array(
			'input' => array(
				'type' => 'checkbox',
				'name' => 'data[]',
				'value' => 2,
				'id' => 'preg:/'.$uidReg.'/',
				'data-multiselect-token' => 'preg:/'.$tokenReg.'/',
				'class' => ' multi-select-box'
			)
		);
		$this->assertTags($result, $expected);

		$result = $this->MultiSelect->checkbox(2, array('class' => 'myclass'));
		$expected = array(
			'input' => array(
				'type' => 'checkbox',
				'name' => 'data[]',
				'value' => 2,
				'id' => 'preg:/'.$uidReg.'/',
				'data-multiselect-token' => 'preg:/'.$tokenReg.'/',
				'class' => 'myclass multi-select-box'
			)
		);
		$this->assertTags($result, $expected);

		$result = $this->MultiSelect->checkbox(2, array('hiddenField' => true));
		$expected = array(
			'input' => array('type' => 'hidden', 'name' => 'data[]', 'value' => '0', 'id' => 'preg:/'.$uidReg.'_/'),
			array('input' => array(
				'type' => 'checkbox',
				'name' => 'data[]',
				'value' => 2,
				'id' => 'preg:/'.$uidReg.'/',
				'data-multiselect-token' => 'preg:/'.$tokenReg.'/',
				'class' => ' multi-select-box'
			))
		);
		$this->assertTags($result, $expected);

		$result = $this->MultiSelect->checkbox('all');
		$expected = array(
			'input' => array(
				'type' => 'checkbox',
				'name' => 'data[]',
				'value' => 'all',
				'id' => 'preg:/'.$uidReg.'/',
				'data-multiselect-token' => 'preg:/'.$tokenReg.'/',
				'class' => ' multi-select-box'
			)
		);
		$this->assertTags($result, $expected);


		$result = $this->MultiSelect->checkbox(1);
		$expected = array(
			'input' => array(
				'type' => 'checkbox',
				'name' => 'data[]',
				'value' => 1,
				'checked' => 'checked',
				'id' => 'preg:/'.$uidReg.'/',
				'data-multiselect-token' => 'preg:/'.$tokenReg.'/',
				'class' => ' multi-select-box'
			)
		);
		$this->assertTags($result, $expected);
	}

	function testEnd() {
		$uidReg = "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}";
		$selector = '\"\.multi-select-box\[data-multiselect-token=[a-z0-9]+\]\"';

		$this->MultiSelect->end();
		$buffer = $this->MultiSelect->Js->getBuffer();
		$this->assertPattern('/\$\('.$selector.'\)\.bind/', $buffer[0]);
		$this->assertPattern('/\.ajax/', $buffer[0]);

		$this->MultiSelect->Js =& new JsHelper($this->View, array('Prototype'));
		$this->MultiSelect->end();
		$buffer = $this->MultiSelect->Js->getBuffer();
		$this->assertPattern('/\$\$\('.$selector.'\)\.observe/', $buffer[0]);
		$this->assertPattern('/new Ajax\.Request/', $buffer[0]);
	}
}