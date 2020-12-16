Usage
=====

Airmad can either be used in template files as part of a theme or, as recommended, 
in a snippet block. The latter one allows for integrating Airmad into any existing 
theme that supports Automad's block editor. The markup looks as follows:

.. attention::

    You can simply paste an Airmad snippet directly into a code field of the new 
    **Template Snippet** block on any page in the Automad dashboard. 

.. code-block:: php

    <@ Airmad/Airmad {
        base: 'appXXXXXXXXXXXXXX',
        table: 'Design projects',
        view: 'All projects',
        filters: 'Client, Category',
        linked: 'Client => Clients',
        prefix: ':example',
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

The code above doesn't produce any output. Instead it populates some Runtime 
`variables <#runtime-variables>`_ that can be used in the 
Automad template to at any point after the Airmad instance above. Note the ``prefix`` 
parameter. The prefix is required to make sure that all runtime variables have unique names.
To display the generated output, the ``:exampleOutput`` variable can be used in a 
template for example as follows.

.. code-block:: php

    <div class="cards">
        @{ :exampleOutput }
    </div>

.. attention:: 

    In case you want to use multiple Airmad instances on your site, you will have to 
    define unique prefixes for each one in order to avoid conflicts between them. 

Options
-------

The example above shows a typical use case of an Airtable integration. 
Find below a list of all availabe options.

==============  ====================================================================================
Name            Description
==============  ====================================================================================
``base``        The Airtable base ID
``table``       The main table to be used to pull records from
``view``        The view of the main `table` to be used
``prefix``      A **required** prefix for the generated runtime variables --- 
                prefixes have to be unique, in case 
                more than one Airmad instance is used on the site 
``linked``      A comma separated list of tables that are linked to a field  
                of the main table records --- note that is only required to list linked tables 
                here that include information that you want to display. In case the field name 
                differs from the actual table name to be linked, it is also possible to pass 
                a list of strings like ``fieldName1 => tableName1, fieldName2 => tableName2`` 
                to the parameter to link such fields to any table.
``template``    The Handlebar template to be used to render the model 
                (the collection of records) --- 
                can be either a string, a variable containing a string or a file path
``filters``     A comma separated list of fields that can be used to filter the records by --- 
                check out the examples below for more information about :doc:`filtering <filters>`
``limit``       The maximum number of records to be displayed on a page
``page``        The current page of records (pagination)
==============  ====================================================================================

Runtime Variables
-----------------

Aside from the output, Airmad provides more variables as shown in the table below.

==================  ===============
Name                Description
==================  ===============
``:prefixOutput``   The rendered output of the table records
``:prefixCount``    The number of found records
``:prefixPage``     The current page number --- this has to be seen in context to 
                    the ``limit`` of items displayed on a page
``:prefixPages``    The amount of pages the records are spread over, 
                    also related to the ``limit`` option
``:prefixMemory``   The max memory used by Automad in bytes
==================  ===============

.. attention::

    Note that you **must** define an unique prefix to be used instead of ``:prefix*`` in the 
    Airmad `options <#options>`_ when creating a new instance.

