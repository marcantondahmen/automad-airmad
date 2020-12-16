Get Started
===========

Airtable is a great tool to quickly create your own database using a intuitive UI. 
While the possibilities of structuring data go far beyond the capabilities of **Automad** 
as a blogging platform, you might find out that Airtable lacks of flexibility and 
design options when it comes to sharing tables publicly. This is where **Airmad** comes in. 
The concept is rather simple. Airmad pulls a table --- and optionally also its **linked** tables ---
using Airtable's REST API. To speed things up and align them with the user experience 
of a small and lightweight Automad site, all retrieved recordes are cached on your server. 
Updated data is pulled from time to time. 

.. attention::

    Airmad requires your webserver to run **PHP 7+** in order to work properly!

Installation
------------

Airmad can be installed by using the Automad dashboard. However in case you would like to install
the package by using Composer, just run the following command on your command line::

    $ composer require airmad/airmad

Configuration
-------------

Airtable requires an `API <https://airtable.com/api>`_ token to authenticate when 
accessing bases using their REST API. In case you don't have one, you can easily 
create one on your Airtable profile page. After successfully creating such token, 
it has to be added to Automad's ``config/config.php`` file. That can be done by 
navigating to **System Settings > More > Edit Configuration File** in the Automad 
dashboard as demonstrated below. Aside from the authentictaion, there you can also 
configure the Airtable cache lifetime in seconds.

.. code-block:: php

    {
      "AIRMAD_TOKEN": "keyXXXXXXXXXXXXXX",
      "AIRMAD_CACHE_LIFETIME": 43200,
      ...
    }

