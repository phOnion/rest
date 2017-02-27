# Documentation

This is the minimal documentation for the REST content handlers 
provided by this package, to complement the `onion/framework` 
currently the package is not framework-agnostic, but will be 
once the main framework moves some of it's components to external
packages.

As of now this package provides support for:

 - `application/json`: returns all data from the entity
 to the client as a `json_encode`d value
 - `application/hal+json`: "Big" HAL response with automatically
 replacing link placeholders with the items available as data 
 for the object.
 - `application/api+json`: The foundation for JSON-API responses
 - `application/ld+json`: (Needs testing) A minimal JSON-LD capable serialization.
 
 Note that since I am not 100% familiar with json-api and especially with json-ld those 2 might need more extensive testing and I am fully aware that the latter is lacking some features, but they tend to be more complex than the time I am currently able to invest. So that being said, feel free to drop make a PR providing the functionality that you need
