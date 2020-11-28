Pagination
==========

In many cases, the amount of records in a table is simple to much for a single page.
You will probably break down the list of records into multiple pages by setting the ``limit``
`option <usage.html#options>`_ to a fixed number. To help you building a simple pagination navigation,
Airmad provides the ``:airmadPage`` and ``:airmadPages`` `runtime <usage.html#runtime-variables>`_ 
variables.

Example
-------

A very simple example for a pagination within an Automad snippet could look as follows:

.. code-block:: php
   :linenos:

    <ul class="uk-pagination">

        <@ if @{ ?Page } > 1 @>
            <li><a href="?<@ queryStringMerge { Page: @{ ?Page | -1 } } @>">←</a></li>
        <@ end @>

        <@ for @{ :airmadPage | -3 } to @{ :airmadPage | +3 } @>
            <@ if @{ :i } > 0 and @{ :i } <= @{ :airmadPages } @>
                <li>
                    <a 
                    href="?<@ queryStringMerge { Page: @{ :i } } @>" 
                    <@ if @{ ?Page | def(1) } = @{ :i } @>class="uk-active"<@ end @>
                    >
                        @{:i}
                    </a>
                </li>
            <@ end @>
        <@ end @>

        <@ if @{ ?Page } < @{ :airmadPages } @>
            <li><a href="?<@ queryStringMerge { Page: @{ ?Page | +1 } } @>">→</a></li>
        <@ end @>

    </ul>

You can simply copy and paste this into a snippet block after creating an Airmad instance. 
The classes in use will work out of the box with the **Standard** and **Adam** themes.