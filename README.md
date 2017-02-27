# rest

RESTful additions for onion/framework

As of now this package provides support for:
 - `application/json`: returns all data from the entity
 to the client as a `json_encode`d value
 - `application/hal+json`: "Big" HAL response with automatically
 replacing link placeholders with the items available as data for 
 the object.
 - `application/api+json`: The foundation for JSON-API responses
 - `application/ld+json`: (Needs testing) A minimal JSON-LD capable serialization.


ToDo:

 - Write more tests
 - ~JSONAPI Transformation~
 - ~Investigate JSON-LD~
