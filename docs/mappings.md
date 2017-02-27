## Mappings

The mappings are arrays passed to the transformer, from which it can workout what goes where but mainly which are the white-listed fields to use as response data. 

They consist of an assoc array, with the class name as key and the value as mapping, that describes the object. This is especially useful since when passing class name, we retrieve it from the passed instance, so no boilerplate in order to allow the transformer to work-it-out or interfaces to implement. you just need a `HydratableInterface` implementing entity but that is it.

**Keep in mind that the `HydratableInterface` is a general purpose interface to allow hydration/extraction of object data.**
