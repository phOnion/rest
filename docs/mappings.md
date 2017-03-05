## Introduction

The mappings are arrays passed to the transformer, from which it can workout what goes where. 

An typical mapping looks something like:
```
$mapping = [
    \Acme\DataObject::class => [
        'links' => [
            ['rel' => 'self', 'href' => '/entityLocation/{id}'],
            // more links
        ],
        'fields' => ['id', 'field1', 'field2', ...],
        'relations' => [
            'relationType' => // This is important in some serializations
                'method' // The name of the method to call on the object to retrieve the related data
        ],
        'meta' => [
            'ld' => [], // JSON-LD meta data
            'api' => [], // JSON-API meta data
        ]
    ]
];
```

### Links
 Links are part of HATEOAS in order to allow clients to "navigate" inside the returned data, also they allow lowering the footprint of the response by allowing the client to request the resources they need instead of receiving a huge JSON blob, regardless of the client's needs.
 
 A link definition is an array with mandatory: `rel` & `href` fields, everything else is pushed as additional attributes to the link.
 
### Fields
 The list of fields, which *can* be retrieved from the object (*can*, because the transformer provides the ability to provide fields and resources to return).
 
 
### Relations
 Relations are a `'field' => 'method'` relation. This is mainly useful for LD responses, since with them the type of the object is not the same as the filed they get populated because of the schema usage as opposed to HAL.
 
 
### Meta
 Meta data should be handled with respect to `ld` and `api` keys, since those are special-case where data for JSON- LD and API have different purpose, but in order to define mappings for multiple response types (most appropriate scenario one is content negotiation) so care should be taken.

**Keep in mind that the `HydratableInterface` is a general purpose interface to allow hydration/extraction of object data.**
