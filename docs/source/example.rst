Example
=======

The following example is supposed to wrap all features of Airmad like getting records, filtering 
and building a pagination. To allow for quick testing, the base for this example is the 
**Project tracker** database that serves as sample content when creating a new account on Airtable.
Therefore it should be easy to just copy and paste the code --- by replacing the app ID of course --- 
after setting up authentication. 

.. attention::

    Make sure that you already have added the **AIRMAD_TOKEN** to your configuration as described 
    in the **Get Stared** guide. And don't forget to replace the base ID in the snippet below with
    the one in your API documentation!

To be easily understandable, this example code is boken down into three section. You can simply paste 
all sections together into a **Template Snippet** block.

Filters
-------

The first part is creating the filter menu. Note that the naming of the input fields is essential here!
In our example we want to filter the records by the **Client** field that is linked to the **Clients** table 
(note the "s" in the table name).

.. code-block:: php
    :emphasize-lines: 3

    <form action="">
        <input type="text" name="Name" value="@{ ?Name }">
        <input type="text" list="clients" name="Client" value="@{ ?Client }">
        <@ Airmad/Airmad {
            base: 'appXXXXXXXXXXXXXX',
            table: 'Clients',
            view: 'All clients',
            template: '<option value="{{ Name }}">',
            prefix: ':clients'
        } @>
        <datalist id="clients">
            @{ :clientsOutput }
        </datalist>
        <button type="submit">Filter</button>
    </form>

Listing Records 
---------------

The second part is building the actual list of record. Again, the mapping of linked tables is important 
here! The code below will generate a grid of record including a little slider to be used with 
with the **Standard** theme.

.. code-block:: php
    :emphasize-lines: 5,16,18

    <@ Airmad/Airmad {
        base: 'appXXXXXXXXXXXXXX',
        table: 'Design projects',
        view: 'All projects',
        linked: 'Client => Clients',
        template: '
            <div class="card">
                <div class="card-content uk-panel uk-panel-box">
                    <div class="uk-panel-teaser">
                        {{#slider Project images 75%}}
                    </div>
                    <div class="uk-panel-title">
                        {{ Name }}
                    </div>
                    <p>
                        {{# Client }}
                            <b>{{ Name }}</b>
                        {{/ Client }}
                    </p>
                </div>
            </div>
        ',
        filters: 'Name, Client',
        limit: 8,
        page: @{ ?Page | def(1) }
    } @>

    <div class="cards grid am-stretched">
        @{ :airmadOutput }
    </div>

Pagination 
----------

The last part will create the pagination navigation. Again the generated markup will work out of the 
box with the **Standard** theme.

.. code-block:: php
    
    <ul class="uk-pagination">
        <@ if @{ ?Page } > 1 @>
            <li><a href="?<@ queryStringMerge { Page: @{ ?Page | -1 } } @>">←</a></li>
        <@ end @>
        <@ for @{ :airmadPage | -3 } to @{ :airmadPage | +3 } @>
            <@ if @{ :i } > 0 and @{ :i } <= @{ :airmadPages } @>
                <li><a href="?<@ queryStringMerge { Page: @{ :i } } @>" <@ if @{ ?Page | def(1) } = @{ :i } @>
                    class="uk-active"
                <@ end @>>@{:i}</a></li>
            <@ end @>
        <@ end @>
        <@ if @{ ?Page } < @{ :airmadPages } @>
            <li><a href="?<@ queryStringMerge { Page: @{ ?Page | +1 } } @>">→</a></li>
        <@ end @>
    </ul>