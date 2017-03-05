## Intro

The transformer is the object which creates the intermediate representation of the resource to be serialized. This is especially useful, since it abstracts away the actual object from the serializer and does not require you to follow any conventions or anything that can get in the way of your object.
Unless , obviously, if you consider the requirement to implement `HydratebleInterface`, but if you put the time to implement a single 'hydratable' trait you will be fine. 
 
 This decision came from the need to sometimes hydrate objects in bulk without writing boilerplate code, handling naming translations, etc or extracting all data from the object. Also there is no mandatory requirement to use one big array with all the mappings, for example you might create a method and store the meta-data inside the hydratable object itself, but that is up to a personal preference and up to your taste (also, remember.. no convention required :) )

## Filter relations
 The `transform` method also supports 2 additional arguments, which specify, the 2nd being `array $includes = []` that is should contain a list of values representing the relations to retrieve. So for example if you make a request to `/products?include=price` this should return a list `product`s and only retrieve the `price` relation, ignoring anything else like, list of components, similar products, etc.
 
## Filter relation fields
 This sort of filtering allows for clients to request only a set of fields for a given relation. This might be useful for requests that intend to update only a small set of their details and not go through the whole process of recreating the data and/or being on a metered data connection (because we should care!). So building upon the previous example with products and price, this one could be like: `/products?include=price&fields[price]=total` this will return all products only with their price relation, which now will only include the total price, instead of - say - delivery, VAT or anything else.
 
 ## Conclusion
This might prove to be especially useful for mobile clients which work on metered connections so that they only update what they need to check or to provide a speedier experience, for example when checking for messages, instead of puling the whole messages, a client might pull only the id and sender and on interaction to pull the actual message (instead of wasting data in bulk most of the time).

*Note that the examples above use only examples of how data can be provided. Actual passing of the filtering criteria is up to the utilizing implementation and nothing is being handled by default (why would object need access to globals, special keys or anything - Tell don't ask :) ). Some ideas:*

 - `?include=product,provider&fields=product:id,title;provider:name`
 - `?include=product,provider&fields[product]=id,title&fields[provider]=name`
 - `?include[]=product&include[]=provider&fields[product][]=id&fields[product][]=name`
