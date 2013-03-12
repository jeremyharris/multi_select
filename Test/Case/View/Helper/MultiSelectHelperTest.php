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
App::import('Helper', array('Js', 'Form', 'Html', 'Session', 'MultiSelect.MultiSelect', 'JqueryEngine', 'PrototypeEngine'));

/**
 * TheMultiSelectTestController class
 *
 * @package       multi_select
 * @subpackage    multi_select.tests.cases.helpers
 */
class TheMultiSelectTestController extends Controller {

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

	function startCase() {
		$this->Controller =& new TheMultiSelectTestController();
		$this->Controller->constructClasses();
		$this->Controller->RequestHandler->initialize($this->Controller);
		$this->Controller->MultiSelect->initialize($this->Controller);
		$this->Controller->MultiSelect->startup();
		$this->View =& new View($this->Controller);

		$this->MultiSelect =& new MultiSelectHelper();
		$this->MultiSelect->Session =& new SessionHelper();
		$this->MultiSelect->Form =& new FormHelper();
		$this->MultiSelect->Form->Html =& new HtmlHelper();
		$this->MultiSelect->Js =& new JsHelper(array('Jquery'));
		$this->MultiSelect->Js->JqueryEngine =& new JqueryEngineHelper();
		$this->Session =& new SessionComponent();
	}

	function startTest() {
		$this->MultiSelect->params['named']['mstoken'] = $this->Controller->MultiSelect->_token;
		$this->MultiSelect->create();
	}

	function testCreate() {
		$this->Session->delete('MultiSelect');
		$this->MultiSelect->create();
		$this->assertError('MultiSelectHelper::create() :: Missing MultiSelect key in session or MultiSelect token. Make sure to include the MultiSelectComponent in your controller file.');

		$this->Session->write('MultiSelect', array(
			'selected' => array(),
			'search' => array(),
			'page' => array()
		));
		$this->MultiSelect->create();
		$this->assertNoErrors();
	}

	function testCheckbox() {
		$this->MultiSelect->selected = array(1);
		$uidReg = "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}";

		$result = $this->MultiSelect->checkbox(2);
		$expected = array(
			'input' => array(
				'type' => 'checkbox',
				'name' => 'data[]',
				'value' => 2,
				'id' => 'preg:/'.$uidReg.'/',
				'class' => 'multi-select-box'
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
				'class' => 'multi-select-box'
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
				'class' => 'multi-select-box'
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
				'class' => 'multi-select-box'
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
				'class' => 'myclass'
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
				'class' => 'multi-select-box'
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
				'class' => 'multi-select-box'
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
				'class' => 'multi-select-box'
			)
		);
		$this->assertTags($result, $expected);

		$result = $this->MultiSelect->checkbox('invalid');
		$this->assertNull($result);

		$result = $this->MultiSelect->checkbox();
		$this->assertNull($result);
	}

	function testEnd() {
		$this->MultiSelect->end();
		$buffer = $this->MultiSelect->Js->getBuffer();
		$this->assertPattern('/\$\("\.multi-select-box"\)\.bind/', $buffer[0]);
		$this->assertPattern('/\.ajax/', $buffer[0]);

		$this->MultiSelect->Js =& new JsHelper(array('Prototype'));
		$this->MultiSelect->Js->PrototypeEngine =& new PrototypeEngineHelper();
		$this->MultiSelect->end();
		$buffer = $this->MultiSelect->Js->getBuffer();
		$this->assertPattern('/\$\$\("\.multi-select-box"\)\.observe/', $buffer[0]);
		$this->assertPattern('/new Ajax\.Request/', $buffer[0]);
	}
}