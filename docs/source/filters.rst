Filters
=======

Searching and filtering are essential functions for displaying database content. 
In Airmad filtering records is pretty straight forward. The following example 
demonstrates the basic idea:

.. code-block:: php
   :emphasize-lines: 2,3,9,10

    <form action="">
        <input type="text" name="Category">
        <input type="text" name="Client">
    </form>
    <@ Airmad/Airmad {
        base: 'appXXXXXXXXXXXXXX',
        table: 'Design projects',
        view: 'All projects',
        filters: 'Client, Category',
        linked: 'Client => Clients',
        template: '
            {{#records}}
                {{#fields}}
                    <div class="card">
                        <h3>{{Name}}</h3>
                        <p>
                            {{#Client}}
                                {{Name}}
                            {{/Client}}
                        </p>
                    </div>
                {{/fields}}
            {{/records}}
        ',
        limit: 20,
        page: @{ ?Page | def(1) }
    } @>

In the snippet above, we have a simple form at the top including two input fields 
with the names ``Category`` and ``Client``. The Airmad instance below that form has those names defined as ``filters`` as you 
can see in the highlighted line. Note that since in this example **Client** is a linked table, defining the ``linked`` parameter
allows for searching in linked records as well.

Exact Matches 
-------------

By default, all items with a field that contain the filter string are included in the list of matched records. However in case 
you prefer to only include exact matches where the filter string equals the actual field value or one of the field values, 
the filter value has to be wrapped in double quotes.

So basically a query string like 

.. code-block:: php

    https://domain.com?name=value 

has to be changed to 

.. code-block:: php

    https://domain.com?name="value" 

to only match records with a ``name`` that is equals ``value``.

Multiple Values per Field
-------------------------

It is also possible to combine **multiple** values to filter a particular field. In that case, all 
provided filter values have to match the a record field in order to include that record in the resulting set.
The commonly used array-like notation for query strings can be used as follows:

.. code-block:: php 

    https://domain.com?name[]=value1&name[]=value2

Now only records with a field called ``name`` that matches both, ``value1`` as well as ``value2`` are include
in the filtered collection.

Autocompletion
--------------

To enhance the user experience for your visitors, you might want to provide an autocompletion list of categories 
for the **Category** input and **Client** names for the second input field. 
The Airmad data model contains a **filters** element at the top level for such purpose. It contains lists of 
records that are contained in one ore more items in **records** for each name defined in the ``filters`` option.
In the following example, such **filters** element contains all **Client** elements that match any record in the 
**records** list. Note that it is also possible to just have a reduced list of filters that actually match an
already filtered set of records. Such a reduced list can be accessed by using the **filteredFilters** element.
You can use filters as follows:

.. code-block:: php
   :emphasize-lines: 1,3,5,9,11,23

    {{#with filteredFilters}}
        <form action="">
            <input type="text" list="Categories" name="Category">
            <datalist id="Categories">
                {{#each Category}}
                    <option value="{{this}}">
                {{/each}}
            </datalist>
            <input type="text" list="Clients" name="Client">
            <datalist id="Clients">
                {{#each Client}}
                    <option value="{{Name}}">
                {{/each}}
            </datalist>
            <button type="submit">Apply</button>
        </form>
    {{/with}}
    <@ Airmad/Airmad {
        base: 'appXXXXXXXXXXXXXX',
        table: 'Design projects',
        view: 'All projects',
        linked: 'Client => Clients',
        filters: 'Client, Category',
        template: '
            {{#records}}
                {{#fields}}
                    <div class="card">
                        <h3>{{Name}}</h3>
                        <p>
                            {{#Client}}
                                {{Name}}
                            {{/Client}}
                        </p>
                    </div>
                {{/fields}}
            {{/records}}
        ',
        limit: 20,
        page: @{ ?Page | def(1) }
    } @>