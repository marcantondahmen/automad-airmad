Templates
=========

As mentioned earlier, Airmad uses `Handlebars <https://github.com/salesforce/handlebars-php#expressions>`_ 
to render record data. While iterating table records, all record fields are exposed to the engine 
and can be accessed by using the normal variable tags. For example to get the ``Name`` of a record, 
you can simply use ``{{ Name }}`` in a template. 
Aside from the default tags, Airmad provides some other useful helpers to let you easily use fields in 
linked tables or build slideshow.

Image Sliders
-------------

In case your table has an attachement field, you can use the ``{{#slider images}}`` helper function to 
create an image slider containing all provided images as that are listed in a field called ``images``. 
By default the slide will have an aspect ratio of 1:1 --- in other words a height of 100% relative to the width. 
You can pass an optional second argument to the helper to define a custom height as follows:

.. code-block:: php

    {{#slider images 75%}}

If Equals
---------

In case you quickly want to compare a field value with any string or number you can use the ``if==`` helper: 

.. code-block:: php

    {{#if== field, value}} ... {{/if==}}

If Not Equals
-------------

The counterpart to ``if==`` helper is the ``if!=`` helper that lets you check for inequality:

.. code-block:: php

    {{#if!= field, value}} ... {{/if!=}}

Record ID
---------

Since the actual record ID is by default not a field, Airmad provides the dedicated ``_ID`` field 
that contains the actual record ID. 

.. code-block:: php

    {{ _ID }}

Linked Tables
-------------

In case you have fields that actually link to other tables in your base, the content of such a field is just a 
bunch of record IDs. In most cases you would want to be able to actually get the values of the one or more 
fields of that record. Fortunately Airmad automatically looks up the linked fields for you and replaces the ID string 
with an array of the actual fields. The replaced ID is then moved to the ``_ID`` field of the record's array. 
Let's assume you have a ``Type`` table and you want to access the ``Name`` of each type linked to your product.
The data returned by the Airtable API looks for example as follows:

.. code-block:: 

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
            "_ID": "recmD5WiE2GeV3ZIW"
          },
          {
            "Name": "Carpet",
            "Product": ["recJcjDC9IN8Vws16"],
            "_ID": "recmD5WiE2GeV3ZIW"
          }
        ]
      }
    }

In a template you can therefore simple loop over the types and get the ``Name`` as follows:

.. code-block:: php

    {{# Type }}
        {{ Name }}
    {{/ Type }}

Each Loops
----------

Handlebars provides a great feature to enhance the use of lists. While it is possible to simply
loop over items like:

.. code-block:: php

    {{# Type }}
        {{ Name }}
    {{/ Type }}

You can alternatively use the ``{{#each Type}} ... {{/each}}`` helper to get more access to 
built-in data variables like ``@first``, ``@last`` and ``@index``. This is for example very 
useful in case you need to concatenate a list of items with a comma: 

.. code-block:: php

    {{#each Type }}
        <i>{{Name}}</i>{{#unless @last}},{{/unless}}
    {{/each}}

You can find more about the use of data variables in `here <https://github.com/salesforce/handlebars-php#data-variables-for-each>`_.