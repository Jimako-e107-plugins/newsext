# Extended core news plugin

## Multi categories news support

Admin part only 

## It allows multiple categories for core news 


### Version 1.1:

*News Categories Management*
- added news categories administration, copied from newspost.php

_Changes in edit mode:_ 
    - removed second tab, all fields are now on one page


_Changes in list mode:_ 
- added Quick View of category


### Version 1.0:

- admin fully working
- newscategory.php is just customized copy of news.php 
- newscategories_class.php is just customized copy of news_handler.php  (category part) 


*How to use it:*

- install 
- there are 2 ways in admin area (news admin area and plugin admin area)
- use plugin category menu (newsext/newsext_categories) instead core news/news_categories

*Displaying on news view page:*

1. option -  change {NEWSCATEGORY} to {NEWSCATEGORIES} in news_view_template.php in your theme

2. option - copy function sc_newscategories() from plugin e_shortcode to your theme_shortcodes, rename it as sc_newscategory().  This way you don't need to change news view template. 

*Category URL*

It is hardcoded for now:
- alias (default news-category) - don't use slash or just news 
- Category ID
- Category SEF
- ".html " - Google just likes html pages and it solves issue with last slash 


### Important:  there is a lot of work to do.  This plugin is not recommended to use on live site. 


## TODO LIST in admin area

1. Hide delete button from record options (deleting is not possible, but button is displayed)
2. Add event on delete news (delete related record or add check for orphans)
3. Not allow action create, removing from menu is not enough (core stuff)
4. Batch support (what is beforeUpdate alternative for batch) 
5. Add event on create news (create related record)

## TODO LIST in frontend area

1. cleaning core code (there is a lot of reductant code)
2. solve canonical URLs, Routes
3. add e_metatag support
4. remove hardcoded markup 
5. add preferences 
6. add plugin own categories list - plugin frontpage 
7. fix frontend plugin page
8. related news ? 




