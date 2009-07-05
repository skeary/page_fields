Page Fields
-------------------------------------------------------------------------------

Version: 1.0.3
Author: Simon Keary
Build Date: 2009-07-05
Requirements: Symphony 2.0.3 or greater

Compatibilty: Tested on Symphony 2.0.3 (2009-07-05)

This extension aims to to improve the usability of Symphony and provide a simple
way to associate developer-defined content blocks or fields for pages and simple
interface to edit them.

Essentially, the developer can define a collection of unique fields for each page and
the end-user is provided a simple interface to set the values for the fields.  Each page
can have a different set of fields. These values are provided as a data source for the
associated page so that they can be accessed from the page's xslt.

Under the hood this extension creates a special hidden section for each page that you
specify page fields for.  Because the section is only available through the added "Page Content"
menu only one entry can be added to a Page Field section.  This data is then made available
to the pages xslt through a provided "Page Fields" data source that is automatically added to
each page.
 

An example of where this functionality might be useful is where a specific page has individual
elements that you want an author to be able to edit.  You could, of course, create a standard
section for this, with fields for each element, but then the normal admin interface will let
you create as many entries as you like in the section.  This isn't really ideal and can be confusing
so this extension hopefully improves the usability for this approach.
 

[INSTALLATION]

1. Upload the 'skeary-page_fields_.....' folder in this archive to your Symphony 
   'extensions' folder and rename to page_fields.

2. Enable it by selecting the "Page Fields", choose Enable from the 
   with-selected menu, then click Apply.

   
[NOTES]

For each configured page this extension will create a page fields section with name 'pfpfpfpf <page_title>'
and handle 'pfpfpfpf-<page_handle>'.  For all practical purposes this should not be visible to the user
since these sections are not listed under the section list and the edit section page will hide these
attributes for page fields sections.  The handle will be reflected in the edit section urls and data source, however.
You can change these prefixes if you like by editing lib/page_fields_defines.php.


[CHANGES]

1.0
- Initial release

1.0.1
- Correct "Page Title" links on Blueprints/Pages Fields admin page.
- Added configuration option to allow use to change "Page Content" menu label.

1.0.2
- Changed PF section handles to contain the page id rather than handle.  This makes the mapping between pages and PF sections tolerant of changes to page handles.  Naming of PF sections has also cleaned up a bit.

1.0.3
- Update of install instructions in readme.
