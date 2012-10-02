# CakePHP MultiSelect Plugin

The MultiSelect plugin is a collection of tools that make it easy to implement
Session-based persisted selected checkboxes. This is useful if you have
paginated data that you wish users to be able to select multiple items between
pages and remember what they have selected.

## Usage

There are two main parts of the plugin that integrate together. The first helps
you construct the checkboxes - the MultiSelect helper. Much like the FormHelper,
to create a group of items that are to be included in the MultiSelect process
you must wrap your table in `MultiSelectHelper::create()` and `MultiSelectHelper::end()`.

## Notes

- MultiSelect **requires** JavaScript and Sessions to work
- Even though there are test cases, things can still get missed. File a ticket
  if you find something wrong!

## Example

For example, to apply a "delete" action on a simple list of users:

### View

In order for the MultiSelect to work, you need to wrap your table in the 
`create()` and `end()` methods and use its `checkbox()` method to create
checkboxes.

    echo $this->MultiSelect->create();
    // table goes here, and where you want a checkbox that selects a user id
    <td><?php echo $this->MultiSelect->checkbox($result['User']['id']); ?></td>
    // the rest of the table
    echo $this->MultiSelect->end();

The helper also comes with 'check all' functionality. Simply pass 'all' to
`MultiSelectHelper::checkbox()`'s value. The function of the 'check all' box
depends on if you have the `$usePages` option set on the component (see more
info below).

### Controller

Now that we've set up the view, let's look at the controller code. To pull the
selected checkboxes, you need to use the MultiSelectComponent. Here's the delete
function that works with the MultiSelectComponent.

    var $components = array('MultiSelect.MultiSelect');
    var $helpers = array('MultiSelect.MultiSelect');
    
    function delete() {
        $selected = $this->MultiSelect->getSelected();
        // $selected is an array of ids that were checked
        foreach ($selected as $deleteMe) {
            $this->User->delete($deleteMe);
        }

        $this->Session->setFlash(count($selected).' Users deleted.');
        $this->redirect(array('action' => 'index'));
    }

The MultiSelect plugin uses tokens to handle multiple sets of MultiSelect data
being saved at once (i.e., having two browser tabs open and MultiSelecting both
tables). To handle this in your links and controllers, you'll have to pass a 
named parameter `mstoken`.

    // pass the current token to the controller action
    $this->Html->link('Delete selected', array('action' => 'delete', 'mstoken' => $this->MultiSelect->token));

And then in your controller

    function delete() {
        $selected = $this->MultiSelect->getSelected();
        // do stuff
    }

#### Persisting across `POST` requests

MultiSelect considers `POST` requests to be new requests and therefore resets. 
This is useful for filter forms. Sometimes, however, you may want a `POST` action
to take advantage of the selected items, such as a bulk edit function. To persist 
the MultiSelect session, pass the `mspersist:1` named parameter to your action.

    $this->Html->link('Bulk Edit', array(
      'action' => 'edit', 
      'mstoken' => $this->MultiSelect->token
    ));

In your edit form, include the named parameter:

    $this->Form->create(array(
      'url' => array(
        'mspersist' => 1
      )
    ));
    echo $this->Form->input('status');
    echo $this->Form->end('Submit');

When the form is submitted, MultiSelect will use the current session rather than
starting a new, empty one.

### Component options

#### `usePages`

The `$usePages` option on the component dictates the behavior of the 'check all'
box.

When `$usePages` is `true`, the check all box will treat "all" as "everything
on that page". When you click it, the entire page is added to the selected items.

When `$usePages` is `false`, the check all box mark that everything should be
selected. Then you'll need to check for this case in your controller.

    function delete() {
        $selected = $this->MultiSelect->getSelected();
        if ($selected === 'all') {
            // find all from a saved search
            $search = $this->MultiSelect->getSearch();
            $results = $this->User->find('all', $search);
            $selected = Set::extract('/User/id', $results);
        }
        foreach ($selected as $deleteMe) {
            $this->User->delete($deleteMe);
        }

        $this->Session->setFlash(count($selected).' Users deleted.');
        $this->redirect(array('action' => 'index'));
    }

## Future

Some things I'd like to do in the future:

* Contextable actions. This would be nice for actions that you wouldn't want to
show or enable if, say, nothing was selected.
* Getting a count of what's selected. If users don't know that their checkboxes
are persisted from page to page, it could be confusing
* Turn off persistence. Maybe you don't want the selected items to be persisted
from page to page but still want to store a list of selected boxes for the
current page and perform an action on those

## License

Licensed under The MIT License
[http://www.opensource.org/licenses/mit-license.php][1]
Redistributions of files must retain the above copyright notice.

[1]: http://www.opensource.org/licenses/mit-license.php