#Lyquix Items Module for FLEXIcontent#

Generates a list of FLEXIcontent items. Provides several advanced settings for selection, ordering, and displaying. Useful for recent blog posts, banners, and events.

##Installation##

Download the most recent version from https://github.com/Lyquix/mod_lyquix_items/releases and install via Joomla extension installer

##Settings##

###Items Selection###

* __Category Scope__: select what categories to include or exclude from selection
* __Type Scope__: select what content types to include or exclide from selection
* __Current Item Scope__: select whether the current item should be included in selection
* __Language Scope__: select whether only current language or all languages should be included in selection

Selection Modes:

* __Basic__: sets the number of items and ordering by date, title, category, or random
* __Events__: allows the selection of items based on date ranges and date ordering
* __Advanced__: custom SQL FROM, WHERE and ORDER clauses to be used in selection

##Display Settings##

* __Output__: selects HTML, JSON or HTML+JSON output
* __Layout Order__: controls the order in which the various elements will be rendered: title, image, date, author, intro, read more, and fields. Using additional "open" and "close" tags you can create more complex HTML structures.
* __Banners Layout__: adds HTML structures, attributes and classes necessary for banner systems

Settings for specific fields:

* Title
* Date
* Author
* Intro text
* Image
* Read more link
* Additional fields

##JSON Settings##

Control whether to include the following in the JSON output:

* Item ID
* Item link
* Fields ID
* Fields Label
* Fields Value
* Fields Display
* Custom JSON variable to use for output data
* Optional callback function name
* Optionally wrap in jQuery document ready listener

##Advanced Settings##

* Custom CSS class
* Custom pre- and post- module and items HTML
* Dynamic CSS class from field value
* Custom CSS classes for field sections
* Custom PHP function that generates CSS classes for each item
* Custom PHP function that generates HTML attributes for each item
* Custom PHP function for rendering fields
