![](https://raw.githubusercontent.com/marcantondahmen/automad-airmad/master/airmad.svg)

# Airmad

An [Airtable](https://airtable.com) integration for [Automad](https://automad.org). Airmad let's you easily pull and integrate records from any Airtable database. Record data can be rendered using [Handlebars](https://handlebarsjs.com) templates.


## Introduction

Airtable is a great tool to quickly create your own database using a intuitive UI. While the possibilities of structuring data go far beyond the capabilities of Automad as a blogging platform, you might find out that Airtable lacks of flexibility and design options when it comes to sharing tables publicly. This is where Airmad comes in. The concept is rather simple. [Airmad](https://airmad.readthedocs.io) pulls a table &mdash; and optionally also its **linked** tables &mdash; using Airtable's REST API. To speed things up and align them with the user experience of a small and lightweight Automad site, all retrieved recordes are cached on your server. Updated data is pulled from time to time.    

## Documentation

Take a look at the Airmad [documentation](https://airmad.readthedocs.io) for more details on how to integrate Airtable bases, build filter menus and slideshows.

---

&copy; 2020 [Marc Anton Dahmen](https://marcdahmen.de) &mdash; MIT license