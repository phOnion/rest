# Documentation

This is a minimal package that provides the different types
of REST compatible response serializers. It uses object mappings
to define how and what to build a response entity. At the moment
it only supports JSON responses, although there are plans for 
supporting HAL-XML it is on the todo list and with low priority
since JSON-LD and JSON-API responses are more important and more
widely used in general + the overhead is minimal.

Currently this package supports transformation of objects to:

 - `application/json` - This is almost the same as doing `json_encode` on 
 a data array.
 - `application/hal+json` - This is a fully HAL compliant response
 along with links and embedded entities.
 - `application/api+json` - Well... JSON-API most of it should be working file
 - `application/ld+json` - This is the most tricky part. The spec is kinda complex and some features are not supported as of now.
 
For more information on each of the serializers take a look at their respective section in the documentation.
