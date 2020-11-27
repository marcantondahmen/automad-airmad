![](https://raw.githubusercontent.com/marcantondahmen/automad-airmad/master/airmad.svg)

# Airmad

An [Airtable](https://airtable.com) integration for [Automad](https://automad.org). Airmad let's you easily pull and integrate records from any Airtable database. Record data can be rendered using [Handlebars](https://handlebarsjs.com) templates.

- [Introduction](#introduction)
- [Configuration](#configuration)
- [Usage](#usage)
  - [Options](#options)
  - [Runtime Variables](#runtime-variables)
  - [Using Multiple Instances](#using-multiple-instances)
- [Templates](#templates)
  - [Image Sliders](#image-sliders)
  - [Record Data in Linked Tables](#record-data-in-linked-tables)
  - [Active or Selected Filters](#active-or-selected-filters)

## Introduction

Airtable is a great tool to quickly create your own database using a intuitive UI. While the possibilities of structuring data go far beyond the capabilities of Automad as a blogging platform, you might find out that Airtable lacks of flexibility and design options when it comes to sharing tables publicly. This is where Airmad comes in. The concept is rather simple. Airmad pulls a table &mdash; and optionally also its **linked** tables &mdash; using Airtable's REST API. To speed things up and align them with the user experience of a small and lightweight Automad site, all retrieved recordes are cached on your server. Updated data is pulled from time to time.    

## Configuration

Airtable requires an [API](https://airtable.com/api) token to authenticate when accessing bases using their REST API. In case you don't have one, you can easily create one on your Airtable profile page. After successfully creating such token, it has to be added to Automad's `config/config.php` file. That can be done by navigating to **System Settings > More > Edit Configuration File** in the Automad dashboard as demonstrated below. Aside from the authentictaion, there you can also configure the Airtable cache lifetime in seconds.

    {
        "AIRMAD_TOKEN": "keyXXXXXXXXXXXXXX",
        "AIRMAD_CACHE_LIFETIME": 43200
        ...
    }

## Usage

Airmad can either be used in template files as part of a theme or, as recommended, in a snippet block. The latter one allows for integrating Airmad into any existing theme that supports Automad's block editor. The markup looks as follows:

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

The code above doesn't produce any output. Instead it populates some runtime [variables](#runtime-variables) that can be used in the Automad template to at any point after the Airmad instance above. To display the generated output, the `:airmadOutput` variable can be used in a template for example as follows.

    <div class="cards grid am-stretched">
        @{ :airmadOutput }
    </div>

### Options

The example above shows a typical use case of an Airtable integration. Find below a list of all availabe options.

| Name | Description |
| ---- | ----------- |
| `base` | The Airtable base |
| `table` | The main table to be used to pull records from |
| `view` | The view of the main `table` to be used |
| `linked` | A comma separated list of table that are linked in the records of the main table &mdash; note that is only required to list linked tables here that include information that you want to display |
| `template` | The Handlebar template to be used to render a record &mdash; can be either a string, a variable containing a string or a file path |
| `filters` | A comma separated list of fields that can be used to filter the records by &mdash; check out the examples below for more information about [filtering](#filters) |
| `limit` | The maximum number of records to be displayed on a page |
| `page` | The current page of records (pagination) |
| `prefix` | An optional prefix for the generated runtime variables instead of the default `:airmad` &mdash; it is required to define unique prefixes, in case [more than one](#using-multiple-instances) Airmad instance is used on a page |

### Runtime Variables

Aside from the output, Airmad provides more variables as shown in the table below.

| Name | Description |
| ---- | ----------- |
| `:airmadOutput` | The rendered output of the table records |
| `:airmadCount` | The number of found records |
| `:airmadPage` | The current page number &mdash; this has to be seen in context to the `limit` of items displayed on a page |
| `:airmadPages` | The amount of pages the records are spread over, also related to the `limit` option |
| `:airmadMemory` | The max memory used by Automad in bytes |

### Using Multiple Instances

As soon as you want to use filters and select dropdowns to let a user control the displayed set of records on a page, you will have to use multiple instances of Airmad on one page. For example one instance request all records of a fictional table called `Type` to generate a list of all existing product types in your database, while another one gets the actual products for example from a table called `Products`. To avoid overwriting the output the first table with the output of the second one, the generated runtime variables need to have a unique prefix that can be defined in the options by using the `prefix` parameter.

## Templates

As mentioned earlier, Airmad uses [Handlebars](https://github.com/salesforce/handlebars-php#expressions) templates to render record data. While iterating table records, all record data is exposed to the engine and can be accessed by using the normal variable tags. The main items here are the `id`, the `fields` and the `createdTime`. The `fields` item actually contains all table fields entered by you. For example to get the `Name` of a record, you can simply use `{{ fields.Name }}` in a template. 
     
Aside from the default tags, Airmad provides some other useful helpers to let you easily use fields in linked tables or build slideshow.

### Image Sliders

In case your table has an attachement field, you can use the `{{#slider fields.images}}` helper function to create an image slider containing all provided images as that are listed in a field called `fields.images`.

### Record Data in Linked Tables

In case you have fields that actually link to other tables in your base, the content of such a field is just a bunch of record IDs. In most cases you would want to be able to actually get the values of the one or more fields of that record. Therefore Airmad adds a dedicated fields to your data model at runtime called `fields.@`. The `@` field contains all referenced records in linked tables. The example below demonstrates the usage of such fields.    

To simply get the IDs of the records in a linked table, you can just loop over the list of IDs as usual.

    {{# fields.Type }}
        {{ . }}
    {{/ fields.Type }}

Instead of just getting the ID, you can directly loop over a list of the linked records by replacing `{{# fields.Type }}` with `{{# fields.@.Type }}`. Note the `@` in the variable name.

    {{# fields.@.Type }}
        <i>{{ Name }}</i>
    {{/ fields.@.Type }}

### Active or Selected Filters

When building dropdown menus or similar to filter the set of elements, it is imortant to know what filter is currently active. Therefore Airmad the `active` field to any record that appears as value for a table filter in the query string. The field can be used as follows:

    <option value="{{ id }}" {{#if active}}selected{{/if}}>
        {{ fields.Name }}
    </option>

---

&copy; 2020 [Marc Anton Dahmen](https://marcdahmen.de) &mdash; MIT license