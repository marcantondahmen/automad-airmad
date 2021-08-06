Templates
=========

As mentioned earlier, Airmad uses `Handlebars <https://github.com/salesforce/handlebars-php#expressions>`_ 
to render table data. Basically all **model** data is structured in a multi-dimensional array and can be accessed in a template.
You can see the actual data while developing your templates by enabling `debugging`_. 

Model
-----

The model contains seven main elements --- ``records``, ``filters``, ``filteredFilters``, ``query``,
``count``, ``pages`` and ``automad``. A typical structure looks as follows:

.. code-block:: php
    :emphasize-lines: 1,14,16,18,20,21,22

    records
        record
            id
            fields
                column
                column
                ...
        record
            id
            fields
                column
                column
                ...
    filters
        ...
    filteredFilters
        ...
    query
        ...
    count
    pages 
    automad 
        title 
        date
        ...

======================	===============================================================================
Name					Description
======================	===============================================================================
``records``	            The records element basically contains all rows in the given table 
``filters``	            A collection of items of columns specified in the filters option
``filteredFilters``     The ``filteredFilters`` reprensent a relevant and unique collection of items 
                        of columns specified in the filters option that match the actual set of records 
``query``               The query element contains all parameters of the query string 
``count``               The count of records in filtered the model 
``pages``               The number of pages needed to display all records in the model 
``automad``             A bridge to a merged array of shared and page data.
======================	===============================================================================

In a template you can therefore iterate all records using the **Handlebars** syntax as demonstarted below.
Note that ``column`` just represents any column name in your table.

.. code-block:: php

    {{#records}}
        {{#fields}}
            {{column}}
        {{/fields}}
    {{/records}}

.. attention::

    Airtable provides some example bases when setting up an account. The Airmad repository includes some 
    example snipptes that are made to work with the **Project tracker** example base of Airtable. 
    Take a look at the example template on `GitHub <https://github.com/marcantondahmen/automad-airmad/tree/master/snippets>`_.


Query String Parameters 
~~~~~~~~~~~~~~~~~~~~~~~

To access a parameter in a **query string** like for example ``https://domain.com?parameter=value`` you can simply use:

.. code-block:: php

    {{query.parameter}}

Automad Data 
~~~~~~~~~~~~

You can also access Automad's page and shared data as follows as shown below. For example to get the title of the current page 
within a template you can simply use: 

.. code-block:: php 

    {{automad.title}}

Record ID
~~~~~~~~~

Since the actual record ID is by default not a field, Airmad provides the dedicated ``_ID`` field 
that contains the actual record ID. 

.. code-block:: php

    {{#records}}
        {{ _ID }}
    {{/records}}

Linked Tables
~~~~~~~~~~~~~

In case you have fields that actually link to other tables in your base, the content of such a field is just a 
bunch of record IDs. In most cases you would want to be able to actually get the values of the one or more 
fields of that record. Fortunately Airmad automatically looks up the linked fields for you and replaces the ID string 
with an array of the actual fields. The replaced ID is then moved to the ``_ID`` field of the record's array. 
Let's assume you have a ``Type`` table and you want to access the ``Name`` of each type linked to your product.
The data returned by the Airtable API looks for example as follows:

.. code-block:: 
   :emphasize-lines: 4,5,6

    {
      "fields": { 
        "Type": [
          "recmD5WiE2GeV3ZIW",
          "recuBUENcDgqnzSww",
          "recj0zpg9qo8M7SeM"
        ]
      }
    }

Airmad will look up all contained fields automatically and expose the following data to the render engine:

.. code-block:: 
   :emphasize-lines: 7,12,17

    {
      "fields": {
        "Type": [
          {
            "Name": "Chair",
            "Product": ["recUtSDeLJ4HQI0uD", "recJcjDC9IN8Vws16"],
            "_ID": "recmD5WiE2GeV3ZIW"
          },
          {
            "Name": "Table",
            "Product": ["recUtSDeLJ4HQI0uD"],
            "_ID": "recuBUENcDgqnzSww"
          },
          {
            "Name": "Carpet",
            "Product": ["recJcjDC9IN8Vws16"],
            "_ID": "recj0zpg9qo8M7SeM"
          }
        ]
      }
    }

In a template you can therefore simple loop over the types and get the ``Name`` as follows:

.. code-block:: php

    {{#Type}}
        {{Name}}
    {{/Type}}

Debugging
---------

To quickly understand the actual structure of the model returned by the Airtable API, you can enable the 
`Debug Mode <https://automad.org/system/debugging>`_ in Automad and then take a look at the browser console.
Since there will be a lot of output, you can then simply filter the console by ``Airmad->Airmad``. 

Helpers 
-------

While there are all official Handlebars helpers available in templates, 
Airmad also provides some additional useful helpers as listed here below.

Image Sliders
~~~~~~~~~~~~~

In case your table has an attachement field, you can use the ``{{#slider images}}`` or 
``{{#sliderLarge images}}`` helper functions to create an image slider containing all 
provided images as that are listed in a field called ``images`` in the field context of a record. 
By default the slide will have an aspect ratio of 1:1 --- in other words a height of 100% relative to the width. 
You can pass an optional second argument to the helper to define a custom height as follows:

.. code-block:: php

    {{#slider images 75%}}

The normal slider uses resized thumbnails as source files. 
It is also possible to get the original image in a slider as follows:

.. code-block:: php

    {{#sliderLarge images 75%}}

Sanitize Values 
~~~~~~~~~~~~~~~

In order to use values in a query string, it is good practice to sanitize those before as follows:

.. code-block:: php

    {{#sanitize field}}

JSON Output 
~~~~~~~~~~~

To get the actual context data instead of a rendered HTML output, you can render JSON output instead. To get 
all data simply use this as your template:

.. code-block:: php

    {{#json this}}

To just get the records for example, you can use:

.. code-block:: php

    {{#json records}}

Markdown
~~~~~~~~

Rich text content in Airtable fields is returned from the Airtable API in the Markdown format.
In order to convert such content to actual HTML, the `markdown` helper can be used as follows:

.. code-block:: php

    {{#markdown field}}

If Equals
~~~~~~~~~

In case you quickly want to compare a field value with any other value or string you can use the ``if==`` helper: 

.. code-block:: php

    {{#if== field, "value"}} ... {{/if==}}
    {{#if== field, otherField}} ... {{/if==}}

Note that it might sometimes be required to compare **sanitized** values. This can be done as follows as well:

.. code-block:: php

    {{#ifsan== field, "value"}} ... {{/ifsan==}}
    {{#ifsan== field, query.field}} ... {{/ifsan==}}

If Not Equals
~~~~~~~~~~~~~

The counterpart to ``if==`` helper is the ``if!=`` helper that lets you check for inequality:

.. code-block:: php

    {{#if!= field, "value"}} ... {{/if!=}}
    {{#if!= field, otherField}} ... {{/if!=}}

To compare sanitized values, please use:

.. code-block:: php

    {{#ifsan!= field, "value"}} ... {{/ifsan!=}}
    {{#ifsan!= field, query.field}} ... {{/ifsan!=}}

Each Loops
~~~~~~~~~~

Handlebars provides a great feature to enhance the use of lists. While it is possible to simply
loop over items like:

.. code-block:: php

    {{#Type}}
        {{Name}}
    {{/Type}}

You can alternatively use the ``{{#each Type}} ... {{/each}}`` helper to get more access to 
built-in data variables like ``@first``, ``@last`` and ``@index``. This is for example very 
useful in case you need to concatenate a list of items with a comma: 

.. code-block:: php

    {{#each Type}}
        <i>{{Name}}</i>{{#unless @last}},{{/unless}}
    {{/each}}

You can find more about the use of data variables in 
`here <https://github.com/salesforce/handlebars-php#data-variables-for-each>`_.

Unique Loops
~~~~~~~~~~~~

The ``unique`` loop works exactly like a normal ``each`` loop except the fact that duplicate items are ignored.

.. code-block:: php

    {{#unique Type}}
        <i>{{Name}}</i>{{#unless @last}},{{/unless}}
    {{/unique}}

More Handlebars Helpers
~~~~~~~~~~~~~~~~~~~~~~~

Aside from the examples above, Handlebars offers even more helpers that can be used in templates 
such as ``with``, ``if``, ``unless`` and others. 
You can find the `documentation <https://github.com/salesforce/handlebars-php#control-structures>`_ 
of those features as well on GitHub. 

Partials
--------

In order to use Handlebars partials, you have to define an absolute path in relation to your Automad directory 
as the value for the ``partials`` parameter when creating a new Airmad instance. All ``*.handlbars`` files in that 
directory can be used as partials as follows: 

.. code-block:: php

    {{> myPartial }}