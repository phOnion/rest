## Serializers Intro

Serializers are the object that do the heavy lifting. They serialize
the entity representing the extracted data and it's relations. They are responsible to serialize the object to a string representation for the client.

Serializers should not accept any external parameters and their behavior should depend only on the datastructure they are working with.
