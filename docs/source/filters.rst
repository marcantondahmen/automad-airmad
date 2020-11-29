Filters
=======

Searching and filtering are essential functions for displaying database content. 
In Airmad filtering records is pretty straight forward. The following example 
demonstrated the basic idea:

.. code-block:: php
   :emphasize-lines: 2,3,10,12

    <form class="uk-forms" action="">
        <input type="text" name="Name" value="@{ ?Name }">
        <input type="text" name="Type" value="@{ ?Type }">
    </form>
    <ul>
    <@ Airmad/Airmad {
        base: 'appXXXXXXXXXXXXXX',
        table: 'Products',
        view: 'Grid view',
        linked: 'Type',
        template: '<li>{{ fields.Product Name }}</li>',
        filters: 'Name, Type',
        limit: 20,
        page: @{ ?Page | def(1) }
    } @>
    </ul>

In the snippet above, we have a simple form at the top including two input fields 
with the names ``Name`` and ``Type``. The Airmad instance below that form has those names defined as ``filters`` as you 
can see in the highlighted line. Note that since in this example **Type** is a linked table, defining the ``linked`` parameter
allows for searching in linked records as well.

Autocompletion
--------------

To enhance the user experience for your visitors, you might want to provide an autocompletion list of **Type** names
for the second input field. You can simply use a second Airmad instance to pull all type names from the **Type** table and
populate such a list with the ``Name`` field of each record. In the following example we use a datalist for such purpose.

.. warning:: 
    
    Note in the snippet below that this time the **prefix** parameter must be set to a 
    unique value to avoid conflict between both Airmad instances.

.. code-block:: php
   :emphasize-lines: 9,12

    <form action="">
        <input type="text" name="Name" value="@{ ?Name }">
        <input type="text" list="types" name="Type" value="@{ ?Type }">
        <@ Airmad/Airmad {
            base: 'appXXXXXXXXXXXXXX',
            table: 'Type',
            view: 'Grid view',
            template: '<option value="{{ fields.Name }}">',
            prefix: ':type'
        } @>
        <datalist id="types">
            @{ :typeOutput }
        </datalist>
    </form>
    <ul>
    <@ Airmad/Airmad {
        base: 'appXXXXXXXXXXXXXX',
        table: 'Products',
        view: 'Grid view',
        linked: 'Type',
        template: '<li>{{ fields.Product Name }}</li>',
        filters: 'Name, Type',
        limit: 20,
        page: @{ ?Page | def(1) }
    } @>
    </ul>