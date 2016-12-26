How to build a minified version of converse.js for this plugin
==============================================================

The default convers.min.js script includes jquery which is already integrated 
part of Roundcube. Thus including it will cause conflicts and massive errors.

The following instructions describe how to build the converse.js minified
script for the use in Roundcube.

1. Run the build process
  ```
  cd devel/converse.js
  make dev
  make build
  ```

2. Copy the necessary resources from converse.js
   ```
   cp devel/converse.js/dist/converse.nojquery.min.js js/
   cp devel/converse.js/dist/converse-no-dependencies.min.js js/
   cp devel/converse.js/dist/templates.js js/
   cp -r devel/converse.js/css/* css/
   cp -r devel/converse.js/fonticons .
   ```

[conversedocs]: http://conversejs.org/docs/html/index.html#development
