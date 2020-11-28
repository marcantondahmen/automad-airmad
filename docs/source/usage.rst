Usage
=====

Airmad can either be used in template files as part of a theme or, as recommended, 
in a snippet block. The latter one allows for integrating Airmad into any existing 
theme that supports Automad's block editor. The markup looks as follows:

.. attention::

    You can simply paste an Airmad snippet directly into a code field of the new 
    **Template Snippet** block on any page in the Automad dashboard. 

.. code-block:: 
   :linenos:

    <@ Airmad/Airmad {
        base: 'appXXXXXXXXXXXXXX',
        table: 'Products',
        view: 'Grid view',
        linked: 'Type',
        template: '
            <div class="card">
                <div class="card-content uk-panel uk-panel-box">
                    <div class="uk-panel-title">
                        {{ fields.Name }}
                    </div>
                    <p>
                        {{# fields.@.Type }}
                            <i>{{ Name }}</i>
                        {{/ fields.@.Type }}
                    </p>
                </div>
            </div>
        ',
        filters: 'Name, Type',
        limit: 20,
        page: @{ ?page | 1 }
    } @>

The code above doesn't produce any output. Instead it populates some Runtime 
`variables <#runtime-variables>`_ that can be used in the 
Automad template to at any point after the Airmad instance above. 
To display the generated output, the ``:airmadOutput`` variable can be used in a 
template for example as follows.

.. code-block:: php
   :linenos:

    <div class="cards grid am-stretched">
        @{ :airmadOutput }
    </div>

.. attention:: 

    In case you want to use multiple Airmad instances on one page, you will have to 
    define unique prefixes for each one in order to avoid conflicts between them. Read more about
    using `multiple <#multiple-instances>`_ instances below.

Options
-------

The example above shows a typical use case of an Airtable integration. 
Find below a list of all availabe options.

==============  ===============================================================================
Name            Description
==============  ===============================================================================
``base``        The Airtable base ID
``table``       The main table to be used to pull records from
``view``        The view of the main `table` to be used
``linked``      A comma separated list of table that are linked in the records 
                of the main table --- note that is only required to list linked tables 
                here that include information that you want to display
``template``    The Handlebar template to be used to render a record --- 
                can be either a string, a variable containing a string or a file path
``filters``     A comma separated list of fields that can be used to filter the records by --- 
                check out the examples below for more information about :doc:`filtering <filters>`
``limit``       The maximum number of records to be displayed on a page
``page``        The current page of records (pagination)
``prefix``      An optional prefix for the generated runtime variables instead of the 
                default ``:airmad`` --- it is required to define unique prefixes, in case 
                `more than one <#multiple-instances>`_ Airmad instance is used on a page
==============  ===============================================================================

Runtime Variables
-----------------

Aside from the output, Airmad provides more variables as shown in the table below.

==================  ===============
Name                Description
==================  ===============
``:airmadOutput``   The rendered output of the table records
``:airmadCount``    The number of found records
``:airmadPage``     The current page number --- this has to be seen in context to 
                    the ``limit`` of items displayed on a page
``:airmadPages``    The amount of pages the records are spread over, 
                    also related to the ``limit`` option
``:airmadMemory``   The max memory used by Automad in bytes
==================  ===============

Multiple Instances
------------------

As soon as you want to use filters and select dropdowns to let a user control the displayed 
set of records on a page, you will have to use multiple instances of Airmad on one page. 
For example one instance request all records of a fictional table called ``Type`` 
to generate a list of all existing product types in your database, while another one 
gets the actual products for example from a table called ``Products``. 
To avoid overwriting the output the first table with the output of the second one, 
the generated runtime variables need to have a unique prefix that can be defined in the 
options by using the ``prefix`` parameter.
