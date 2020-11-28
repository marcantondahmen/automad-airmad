Templates
=========

As mentioned earlier, Airmad uses `Handlebars <https://github.com/salesforce/handlebars-php#expressions>`_ 
templates to render record data. While iterating table records, all record data is exposed to the engine 
and can be accessed by using the normal variable tags. 
The main items here are the ``id``, the ``fields`` and the ``createdTime``. 
The ``fields`` item actually contains all table fields entered by you. For example to get the ``Name`` of a record, 
you can simply use ``{{ fields.Name }}`` in a template. 
Aside from the default tags, Airmad provides some other useful helpers to let you easily use fields in 
linked tables or build slideshow.

Image Sliders
-------------

In case your table has an attachement field, you can use the ``{{#slider fields.images}}`` helper function to 
create an image slider containing all provided images as that are listed in a field called ``fields.images``. 
By default the slide will have an aspect ratio of 1:1 --- in other words a height of 100% relative to the width. 
You can pass an optional second argument to the helper to define a custom height as follows:

.. code-block:: php
   :linenos:

    {{#slider fields.images 75%}}

Linked Tables
-------------

In case you have fields that actually link to other tables in your base, the content of such a field is just a 
bunch of record IDs. In most cases you would want to be able to actually get the values of the one or more 
fields of that record. Therefore Airmad adds a dedicated fields to your data model at runtime called ``fields.@``. 
The ``@`` field contains all referenced records in linked tables. The example below demonstrates the usage of such fields.    

To simply get the IDs of the records in a linked table, you can just loop over the list of IDs as usual.

.. code-block:: php
   :linenos:

    {{# fields.Type }}
        {{ . }}
    {{/ fields.Type }}

Instead of just getting the ID, you can directly loop over a list of the linked records by replacing ``{{# fields.Type }}`` 
with ``{{# fields.@.Type }}``. Note the ``@`` in the variable name.

.. code-block:: php
   :linenos:

    {{# fields.@.Type }}
        <i>{{ Name }}</i>
    {{/ fields.@.Type }}

Active Filters
--------------

When building dropdown menus or similar to filter the set of elements, it is imortant to know what filter is currently active. 
Therefore Airmad the ``active`` field to any record that appears as value for a table filter in the query string. 
The field can be used as follows:

.. code-block:: php
   :linenos:

    <option value="{{ id }}" {{#if active}}selected{{/if}}>
        {{ fields.Name }}
    </option>
